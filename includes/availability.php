<?php
// All the "can this be booked?" logic lives here.
//
// Rule: customers pick up the day before the event and return the day after.
// So a rental for Saturday holds the items Friday through Sunday.

const ACTIVE_STATUSES = "'pending','confirmed','picked_up'";

function rental_window(string $eventDate): array
{
    $event = new DateTimeImmutable($eventDate);
    return [
        $event->modify('-1 day')->format('Y-m-d'),
        $event->modify('+1 day')->format('Y-m-d'),
    ];
}

// Returns an error message, or null if the date is fine
function check_event_date(string $eventDate): ?string
{
    $d = DateTimeImmutable::createFromFormat('!Y-m-d', $eventDate);
    if (!$d || $d->format('Y-m-d') !== $eventDate) {
        return 'Pick a real date for your event.';
    }
    $earliest = new DateTimeImmutable('today +2 days');
    $latest   = new DateTimeImmutable('today +1 year');
    if ($d < $earliest) {
        return 'Events need at least 2 days notice so pickup can be the day before.';
    }
    if ($d > $latest) {
        return 'Bookings open up to one year ahead.';
    }
    return null;
}

// How many of each item are already held during a window.
// Returns [item_id => quantity_booked]
function booked_quantities(string $pickup, string $return, ?array $itemIds = null): array
{
    $sql = "SELECT ri.item_id, SUM(ri.quantity) AS booked
            FROM rental_items ri
            JOIN rentals r ON r.id = ri.rental_id
            WHERE r.status IN (" . ACTIVE_STATUSES . ")
              AND r.pickup_date <= ?
              AND r.return_date >= ?";
    $params = [$return, $pickup];

    if ($itemIds !== null) {
        if (count($itemIds) === 0) {
            return [];
        }
        $sql .= ' AND ri.item_id IN (' . implode(',', array_fill(0, count($itemIds), '?')) . ')';
        $params = array_merge($params, array_values($itemIds));
    }
    $sql .= ' GROUP BY ri.item_id';

    $stmt = db()->prepare($sql);
    $stmt->execute($params);

    $booked = [];
    foreach ($stmt->fetchAll() as $row) {
        $booked[(int) $row['item_id']] = (int) $row['booked'];
    }
    return $booked;
}

// Creates a rental safely. $lines is [item_id => quantity].
// Throws RuntimeException with a friendly message if something is not available.
function create_rental(int $userId, string $eventDate, array $lines, ?int $packageId = null, ?float $packagePrice = null, string $notes = ''): int
{
    $error = check_event_date($eventDate);
    if ($error) {
        throw new RuntimeException($error);
    }
    $lines = array_filter($lines, fn($q) => $q > 0);
    if (!$lines) {
        throw new RuntimeException('Choose at least one item.');
    }

    [$pickup, $return] = rental_window($eventDate);
    $pdo = db();
    $pdo->beginTransaction();

    try {
        // Lock these item rows. If two people book the same item at the same
        // moment, the second one waits here until the first one finishes.
        // This is what prevents double booking.
        $ids = array_keys($lines);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("SELECT id, name, price, quantity FROM items
                               WHERE id IN ($placeholders) AND is_active = 1
                               FOR UPDATE");
        $stmt->execute($ids);
        $items = [];
        foreach ($stmt->fetchAll() as $row) {
            $items[(int) $row['id']] = $row;
        }

        $booked = booked_quantities($pickup, $return, $ids);
        $total = 0.0;

        foreach ($lines as $itemId => $qty) {
            if (!isset($items[$itemId])) {
                throw new RuntimeException('One of those items is no longer available.');
            }
            $free = (int) $items[$itemId]['quantity'] - ($booked[$itemId] ?? 0);
            if ($qty > $free) {
                $name = $items[$itemId]['name'];
                throw new RuntimeException($free > 0
                    ? "Only $free of \"$name\" left for that date."
                    : "\"$name\" is fully booked for that date. Try another date.");
            }
            $total += $qty * (float) $items[$itemId]['price'];
        }

        if ($packagePrice !== null) {
            $total = $packagePrice;
        }

        $stmt = $pdo->prepare('INSERT INTO rentals
            (user_id, package_id, event_date, pickup_date, return_date, total_price, notes)
            VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$userId, $packageId, $eventDate, $pickup, $return, $total, $notes ?: null]);
        $rentalId = (int) $pdo->lastInsertId();

        $stmt = $pdo->prepare('INSERT INTO rental_items (rental_id, item_id, quantity, unit_price) VALUES (?, ?, ?, ?)');
        foreach ($lines as $itemId => $qty) {
            $unit = $packageId ? 0 : $items[$itemId]['price'];
            $stmt->execute([$rentalId, $itemId, $qty, $unit]);
        }

        $pdo->commit();
        return $rentalId;
    } catch (Throwable $ex) {
        $pdo->rollBack();
        throw $ex;
    }
}
