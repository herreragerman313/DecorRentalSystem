<?php
require __DIR__ . '/../includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('items.php');
}

$type  = $_POST['type'] ?? '';
$id    = (int) ($_POST['id'] ?? 0);
$date  = $_POST['date'] ?? '';
$back  = ($type === 'package' ? 'package.php' : 'item.php') . '?id=' . $id . '&date=' . urlencode($date);

// Not logged in? Send them to log in, then back to the same page
if (!current_user()) {
    $_SESSION['after_login'] = url($back);
    flash('info', 'Log in or make an account to finish your booking.');
    redirect('login.php');
}
check_csrf();
$user = current_user();

$opts = [
    'notes'   => substr($_POST['notes'] ?? '', 0, 500),
    'service' => $_POST['service'] ?? 'pickup',
    'address' => substr($_POST['address'] ?? '', 0, 255),
];

try {
    if ($type === 'item') {
        $qty = max(1, (int) ($_POST['quantity'] ?? 1));
        $rentalId = create_rental($user['id'], $date, [[$id, $qty, true]], $opts);
    } elseif ($type === 'package') {
        $stmt = db()->prepare('SELECT price FROM packages WHERE id = ? AND is_active = 1');
        $stmt->execute([$id]);
        $price = $stmt->fetchColumn();
        if ($price === false) {
            throw new RuntimeException('That package is not available anymore.');
        }

        // Pieces that come with the package (included in the package price)
        $stmt = db()->prepare('SELECT item_id, quantity FROM package_items WHERE package_id = ?');
        $stmt->execute([$id]);
        $rows = [];
        foreach ($stmt->fetchAll() as $row) {
            $rows[] = [(int) $row['item_id'], (int) $row['quantity'], false];
        }

        // Extras the customer added to customize it (charged at the normal rental price)
        foreach ((array) ($_POST['extra'] ?? []) as $itemId => $qty) {
            $qty = min(500, max(0, (int) $qty));
            if ($qty > 0) {
                $rows[] = [(int) $itemId, $qty, true];
            }
        }

        $opts['package_id'] = $id;
        $opts['base_price'] = (float) $price;
        $rentalId = create_rental($user['id'], $date, $rows, $opts);
    } else {
        redirect('items.php');
    }
} catch (RuntimeException $ex) {
    flash('error', $ex->getMessage());
    redirect($back);
}

flash('success', 'Rental requested. We will confirm it soon.');
redirect('rental.php?id=' . $rentalId);
