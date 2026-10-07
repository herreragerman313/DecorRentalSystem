<?php
// All the business logic lives here: what can be booked, prices, discounts,
// and buying pieces.
//
// Rule: items are held from the day before the event to the day after.
// With pickup service the customer picks up and returns on those days.
// With setup service our team delivers, decorates, and takes it all down.

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
        return 'Events need at least 2 days notice so everything can go out the day before.';
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

// ---------- Loyalty ----------

// A customer counts as returning once they have finished one rental
function completed_rentals(int $userId): int
{
    $stmt = db()->prepare("SELECT COUNT(*) FROM rentals WHERE user_id = ? AND status = 'returned'");
    $stmt->execute([$userId]);
    return (int) $stmt->fetchColumn();
}

function gets_loyalty_discount(int $userId): bool
{
    return completed_rentals($userId) > 0;
}

// ---------- Reuse counter ----------

// Every piece that comes back is a piece nobody had to buy and throw away
function pieces_reused(?int $userId = null): int
{
    $sql = "SELECT COALESCE(SUM(ri.quantity), 0)
            FROM rental_items ri JOIN rentals r ON r.id = ri.rental_id
            WHERE r.status = 'returned'";
    $params = [];
    if ($userId !== null) {
        $sql .= ' AND r.user_id = ?';
        $params[] = $userId;
    }
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return (int) $stmt->fetchColumn();
}

// ---------- Booking ----------

// Creates a rental safely.
//   $rows: list of [item_id, quantity, charged]. charged = false for pieces
//          that are part of a package price, true for pieces priced one by one.
//   $opts: package_id, base_price, notes, service ('pickup' or 'setup'), address
// Throws RuntimeException with a friendly message if something is not available.
function create_rental(int $userId, string $eventDate, array $rows, array $opts = []): int
{
    $error = check_event_date($eventDate);
    if ($error) {
        throw new RuntimeException($error);
    }
    $rows = array_values(array_filter($rows, fn($r) => $r[1] > 0));
    if (!$rows) {
        throw new RuntimeException('Choose at least one item.');
    }

    $packageId = $opts['package_id'] ?? null;
    $basePrice = (float) ($opts['base_price'] ?? 0);
    $notes     = trim($opts['notes'] ?? '');
    $service   = ($opts['service'] ?? 'pickup') === 'setup' ? 'setup' : 'pickup';
    $address   = trim($opts['address'] ?? '');

    if ($service === 'setup' && strlen($address) < 8) {
        throw new RuntimeException('Add the event address so our team knows where to set up.');
    }

    // How many of each item this rental needs in total
    $needed = [];
    foreach ($rows as [$itemId, $qty]) {
        $needed[$itemId] = ($needed[$itemId] ?? 0) + $qty;
    }

    [$pickup, $return] = rental_window($eventDate);
    $pdo = db();
    $pdo->beginTransaction();

    try {
        // Lock these item rows. If two people book the same item at the same
        // moment, the second one waits here until the first one finishes.
        // This is what prevents double booking.
        $ids = array_keys($needed);
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
        foreach ($needed as $itemId => $qty) {
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
        }

        // Prices: package price (if any) plus any pieces charged one by one
        $subtotal = $basePrice;
        foreach ($rows as [$itemId, $qty, $charged]) {
            if ($charged) {
                $subtotal += $qty * (float) $items[$itemId]['price'];
            }
        }

        $setupFee = 0.0;
        if ($service === 'setup') {
            if ($packageId) {
                $stmt = $pdo->prepare('SELECT setup_fee FROM packages WHERE id = ?');
                $stmt->execute([$packageId]);
                $setupFee = (float) $stmt->fetchColumn();
            } else {
                $setupFee = ITEM_SETUP_FEE;
            }
        }

        $discount = gets_loyalty_discount($userId) ? round($subtotal * LOYALTY_PERCENT / 100, 2) : 0.0;
        $total = $subtotal + $setupFee - $discount;

        $stmt = $pdo->prepare('INSERT INTO rentals
            (user_id, package_id, event_date, pickup_date, return_date, service, address,
             subtotal, setup_fee, discount, total_price, notes)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $userId, $packageId, $eventDate, $pickup, $return, $service,
            $service === 'setup' ? $address : null,
            $subtotal, $setupFee, $discount, $total, $notes ?: null,
        ]);
        $rentalId = (int) $pdo->lastInsertId();

        $stmt = $pdo->prepare('INSERT INTO rental_items (rental_id, item_id, quantity, unit_price) VALUES (?, ?, ?, ?)');
        foreach ($rows as [$itemId, $qty, $charged]) {
            $stmt->execute([$rentalId, $itemId, $qty, $charged ? $items[$itemId]['price'] : 0]);
        }

        $pdo->commit();
        return $rentalId;
    } catch (Throwable $ex) {
        $pdo->rollBack();
        throw $ex;
    }
}

// ---------- Buying pieces to keep ----------

// Creates an order and takes the pieces out of the sale stock.
// $lines is [item_id => quantity].
function create_order(int $userId, array $lines): int
{
    $lines = array_filter($lines, fn($q) => $q > 0);
    if (!$lines) {
        throw new RuntimeException('Choose how many you want to buy.');
    }

    $pdo = db();
    $pdo->beginTransaction();
    try {
        $ids = array_keys($lines);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("SELECT id, name, sale_price, sale_stock FROM items
                               WHERE id IN ($placeholders) AND is_active = 1 AND sale_price IS NOT NULL
                               FOR UPDATE");
        $stmt->execute($ids);
        $items = [];
        foreach ($stmt->fetchAll() as $row) {
            $items[(int) $row['id']] = $row;
        }

        $total = 0.0;
        foreach ($lines as $itemId => $qty) {
            if (!isset($items[$itemId])) {
                throw new RuntimeException('That piece is not for sale right now.');
            }
            $left = (int) $items[$itemId]['sale_stock'];
            if ($qty > $left) {
                $name = $items[$itemId]['name'];
                throw new RuntimeException($left > 0 ? "Only $left of \"$name\" left to buy." : "\"$name\" is sold out.");
            }
            $total += $qty * (float) $items[$itemId]['sale_price'];
        }

        $stmt = $pdo->prepare('INSERT INTO orders (user_id, total_price) VALUES (?, ?)');
        $stmt->execute([$userId, $total]);
        $orderId = (int) $pdo->lastInsertId();

        $add  = $pdo->prepare('INSERT INTO order_items (order_id, item_id, quantity, unit_price) VALUES (?, ?, ?, ?)');
        $take = $pdo->prepare('UPDATE items SET sale_stock = sale_stock - ? WHERE id = ?');
        foreach ($lines as $itemId => $qty) {
            $add->execute([$orderId, $itemId, $qty, $items[$itemId]['sale_price']]);
            $take->execute([$qty, $itemId]);
        }

        $pdo->commit();
        return $orderId;
    } catch (Throwable $ex) {
        $pdo->rollBack();
        throw $ex;
    }
}

// Cancels an order and puts the pieces back in the sale stock.
// $userId = null means an admin is cancelling.
function cancel_order(int $orderId, ?int $userId = null): bool
{
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $sql = "UPDATE orders SET status = 'cancelled' WHERE id = ? AND status IN ('pending','ready')";
        $params = [$orderId];
        if ($userId !== null) {
            $sql = "UPDATE orders SET status = 'cancelled' WHERE id = ? AND user_id = ? AND status = 'pending'";
            $params[] = $userId;
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        if ($stmt->rowCount() !== 1) {
            $pdo->rollBack();
            return false;
        }
        $pdo->prepare('UPDATE items i JOIN order_items oi ON oi.item_id = i.id
                       SET i.sale_stock = i.sale_stock + oi.quantity
                       WHERE oi.order_id = ?')->execute([$orderId]);
        $pdo->commit();
        return true;
    } catch (Throwable $ex) {
        $pdo->rollBack();
        throw $ex;
    }
}

function order_status_label(string $status): string
{
    return [
        'pending'   => 'Order received',
        'ready'     => 'Ready for pickup',
        'completed' => 'Picked up',
        'cancelled' => 'Cancelled',
    ][$status] ?? $status;
}
