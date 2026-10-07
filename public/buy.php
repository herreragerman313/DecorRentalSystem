<?php
require __DIR__ . '/../includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('shop.php');
}

$id  = (int) ($_POST['id'] ?? 0);
$qty = min(500, max(1, (int) ($_POST['quantity'] ?? 1)));

if (!current_user()) {
    $_SESSION['after_login'] = url('item.php?id=' . $id);
    flash('info', 'Log in or make an account to buy.');
    redirect('login.php');
}
check_csrf();

try {
    $orderId = create_order((int) current_user()['id'], [$id => $qty]);
} catch (RuntimeException $ex) {
    flash('error', $ex->getMessage());
    redirect('item.php?id=' . $id);
}

flash('success', "Order #$orderId placed. We'll let you know when it's ready to pick up.");
redirect('my-rentals.php#orders');
