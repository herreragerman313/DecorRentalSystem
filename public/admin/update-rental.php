<?php
require __DIR__ . '/../../includes/bootstrap.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('admin/index.php');
}
check_csrf();

$id = (int) ($_POST['id'] ?? 0);
$to = $_POST['status'] ?? '';

// Only allow moves that make sense. You can't un-cancel a rental, because
// those items might already be booked by someone else.
$allowedFrom = [
    'confirmed' => ['pending'],
    'picked_up' => ['confirmed'],
    'returned'  => ['picked_up'],
    'cancelled' => ['pending', 'confirmed'],
];

if (!isset($allowedFrom[$to])) {
    flash('error', 'That status change is not allowed.');
    redirect('admin/index.php');
}

$from = $allowedFrom[$to];
$placeholders = implode(',', array_fill(0, count($from), '?'));
$stmt = db()->prepare("UPDATE rentals SET status = ? WHERE id = ? AND status IN ($placeholders)");
$stmt->execute(array_merge([$to, $id], $from));

if ($stmt->rowCount() === 1) {
    flash('success', "Rental #$id is now " . strtolower(status_label($to)) . '.');
} else {
    flash('error', "Rental #$id could not be changed. Someone may have updated it already.");
}

redirect('admin/index.php');
