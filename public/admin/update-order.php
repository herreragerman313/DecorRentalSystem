<?php
require __DIR__ . '/../../includes/bootstrap.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('admin/orders.php');
}
check_csrf();

$id = (int) ($_POST['id'] ?? 0);
$to = $_POST['status'] ?? '';

if ($to === 'cancelled') {
    $ok = cancel_order($id);   // also puts the pieces back in stock
} else {
    $allowedFrom = ['ready' => 'pending', 'completed' => 'ready'];
    if (!isset($allowedFrom[$to])) {
        flash('error', 'That status change is not allowed.');
        redirect('admin/orders.php');
    }
    $stmt = db()->prepare('UPDATE orders SET status = ? WHERE id = ? AND status = ?');
    $stmt->execute([$to, $id, $allowedFrom[$to]]);
    $ok = $stmt->rowCount() === 1;
}

if ($ok) {
    flash('success', "Order #$id is now " . strtolower(order_status_label($to)) . '.');
} else {
    flash('error', "Order #$id could not be changed. Someone may have updated it already.");
}
redirect('admin/orders.php');
