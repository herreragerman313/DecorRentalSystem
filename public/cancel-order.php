<?php
require __DIR__ . '/../includes/bootstrap.php';
$user = require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('my-rentals.php');
}
check_csrf();

$id = (int) ($_POST['id'] ?? 0);
if (cancel_order($id, (int) $user['id'])) {
    flash('success', "Order #$id cancelled.");
} else {
    flash('error', "Order #$id can't be cancelled anymore. Contact us if plans changed.");
}
redirect('my-rentals.php#orders');
