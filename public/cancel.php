<?php
require __DIR__ . '/../includes/bootstrap.php';
$user = require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('my-rentals.php');
}
check_csrf();

$id = (int) ($_POST['id'] ?? 0);

// The WHERE clause does the safety checks: it must be this user's rental,
// not already picked up, and the pickup day must still be in the future.
$stmt = db()->prepare("UPDATE rentals SET status = 'cancelled'
                       WHERE id = ? AND user_id = ?
                         AND status IN ('pending','confirmed')
                         AND pickup_date > CURDATE()");
$stmt->execute([$id, $user['id']]);

if ($stmt->rowCount() === 1) {
    flash('success', 'Rental cancelled.');
} else {
    flash('error', 'This rental can not be cancelled anymore. Call us if plans changed.');
}
redirect('rental.php?id=' . $id);
