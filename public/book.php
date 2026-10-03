<?php
require __DIR__ . '/../includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('items.php');
}

$type  = $_POST['type'] ?? '';
$id    = (int) ($_POST['id'] ?? 0);
$date  = $_POST['date'] ?? '';
$notes = trim(substr($_POST['notes'] ?? '', 0, 500));
$back  = ($type === 'package' ? 'package.php' : 'item.php') . '?id=' . $id . '&date=' . urlencode($date);

// Not logged in? Send them to log in, then back to the same page
if (!current_user()) {
    $_SESSION['after_login'] = url($back);
    flash('info', 'Log in or make an account to finish your booking.');
    redirect('login.php');
}
check_csrf();
$user = current_user();

try {
    if ($type === 'item') {
        $qty = max(1, (int) ($_POST['quantity'] ?? 1));
        $rentalId = create_rental($user['id'], $date, [$id => $qty], null, null, $notes);
    } elseif ($type === 'package') {
        $stmt = db()->prepare('SELECT price FROM packages WHERE id = ? AND is_active = 1');
        $stmt->execute([$id]);
        $price = $stmt->fetchColumn();
        if ($price === false) {
            throw new RuntimeException('That package is not available anymore.');
        }
        $stmt = db()->prepare('SELECT item_id, quantity FROM package_items WHERE package_id = ?');
        $stmt->execute([$id]);
        $lines = [];
        foreach ($stmt->fetchAll() as $row) {
            $lines[(int) $row['item_id']] = (int) $row['quantity'];
        }
        $rentalId = create_rental($user['id'], $date, $lines, $id, (float) $price, $notes);
    } else {
        redirect('items.php');
    }
} catch (RuntimeException $ex) {
    flash('error', $ex->getMessage());
    redirect($back);
}

flash('success', 'Rental requested. We will confirm it soon.');
redirect('rental.php?id=' . $rentalId);
