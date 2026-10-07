<?php
require __DIR__ . '/../../includes/bootstrap.php';
require_admin();

$filter = $_GET['status'] ?? 'open';
$allowed = ['open', 'pending', 'confirmed', 'picked_up', 'returned', 'cancelled', 'all'];
if (!in_array($filter, $allowed, true)) {
    $filter = 'open';
}

$sql = 'SELECT r.*, u.name AS customer, p.name AS package_name,
               (SELECT GROUP_CONCAT(CONCAT(ri.quantity, " x ", i.name) ORDER BY i.name SEPARATOR ", ")
                FROM rental_items ri JOIN items i ON i.id = ri.item_id
                WHERE ri.rental_id = r.id) AS item_list
        FROM rentals r
        JOIN users u ON u.id = r.user_id
        LEFT JOIN packages p ON p.id = r.package_id';
$params = [];
if ($filter === 'open') {
    $sql .= " WHERE r.status IN ('pending','confirmed','picked_up')";
} elseif ($filter !== 'all') {
    $sql .= ' WHERE r.status = ?';
    $params[] = $filter;
}
$sql .= ' ORDER BY r.pickup_date ' . ($filter === 'open' ? 'ASC' : 'DESC');
$stmt = db()->prepare($sql);
$stmt->execute($params);
$rentals = $stmt->fetchAll();

$stats = db()->query("SELECT
    SUM(status = 'pending') AS waiting,
    SUM(status = 'confirmed' AND pickup_date BETWEEN CURDATE() AND CURDATE() + INTERVAL 7 DAY) AS pickups_week,
    SUM(status = 'picked_up') AS out_now,
    SUM(status = 'picked_up' AND return_date < CURDATE()) AS late
  FROM rentals")->fetch();

$openOrders = (int) db()->query("SELECT COUNT(*) FROM orders WHERE status IN ('pending','ready')")->fetchColumn();

// What each status is allowed to move to next
$next = [
    'pending'   => ['confirmed' => 'Confirm', 'cancelled' => 'Cancel'],
    'confirmed' => ['picked_up' => 'Mark out', 'cancelled' => 'Cancel'],
    'picked_up' => ['returned' => 'Mark back'],
];

$pageTitle = 'Admin';
require __DIR__ . '/../../includes/header.php';
?>

<div class="section-head">
  <h1>Rentals</h1>
  <span>
    <a class="btn btn-ghost" href="<?= e(url('admin/orders.php')) ?>">Shop orders<?= $openOrders ? ' (' . $openOrders . ')' : '' ?></a>
    <a class="btn btn-ghost" href="<?= e(url('admin/items.php')) ?>">Manage items</a>
  </span>
</div>

<div class="stats">
  <div><strong><?= (int) $stats['waiting'] ?></strong> waiting for you to confirm</div>
  <div><strong><?= (int) $stats['pickups_week'] ?></strong> pickups in the next 7 days</div>
  <div><strong><?= (int) $stats['out_now'] ?></strong> out at events now</div>
  <div class="<?= $stats['late'] > 0 ? 'stat-alert' : '' ?>"><strong><?= (int) $stats['late'] ?></strong> late returns</div>
</div>

<nav class="tabs" aria-label="Filter rentals">
  <?php foreach (['open' => 'Open', 'pending' => 'Waiting', 'confirmed' => 'Confirmed', 'picked_up' => 'Out now', 'returned' => 'Returned', 'cancelled' => 'Cancelled', 'all' => 'All'] as $key => $label): ?>
    <a href="?status=<?= e($key) ?>"<?= $filter === $key ? ' aria-current="page"' : '' ?>><?= e($label) ?></a>
  <?php endforeach; ?>
</nav>

<?php if (!$rentals): ?>
  <div class="empty"><p>No rentals in this list.</p></div>
<?php else: ?>
  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>#</th><th>Customer</th><th>What</th><th>Service</th><th>Pickup</th><th>Return</th><th>Total</th><th>Status</th><th>Update</th></tr>
      </thead>
      <tbody>
        <?php foreach ($rentals as $r):
            $isLate = $r['status'] === 'picked_up' && $r['return_date'] < date('Y-m-d'); ?>
          <tr>
            <td><a href="<?= e(url('rental.php?id=' . $r['id'])) ?>"><?= (int) $r['id'] ?></a></td>
            <td><?= e($r['customer']) ?></td>
            <td><?= e($r['package_name'] ? $r['package_name'] . ' package' : $r['item_list']) ?></td>
            <td><?= $r['service'] === 'setup' ? '<strong>We set up</strong><br><span class="small">' . e($r['address']) . '</span>' : 'Customer pickup' ?></td>
            <td><?= e(date('M j', strtotime($r['pickup_date']))) ?></td>
            <td class="<?= $isLate ? 'late' : '' ?>"><?= e(date('M j', strtotime($r['return_date']))) ?><?= $isLate ? ' (late)' : '' ?></td>
            <td><?= money($r['total_price']) ?></td>
            <td><span class="status status-<?= e($r['status']) ?>"><?= e(status_label($r['status'], $r['service'] === 'setup')) ?></span></td>
            <td>
              <?php foreach ($next[$r['status']] ?? [] as $to => $label):
                  $setup = $r['service'] === 'setup';
                  if ($to === 'picked_up') { $label = $setup ? 'Mark set up' : 'Mark picked up'; }
                  if ($to === 'returned')  { $label = $setup ? 'Mark taken down' : 'Mark returned'; } ?>
                <form method="post" action="<?= e(url('admin/update-rental.php')) ?>" class="inline"<?= $to === 'cancelled' ? ' data-confirm="Cancel rental #' . (int) $r['id'] . '?"' : '' ?>>
                  <?= csrf_field() ?>
                  <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                  <input type="hidden" name="status" value="<?= e($to) ?>">
                  <button type="submit" class="btn btn-small<?= $to === 'cancelled' ? ' btn-quiet' : '' ?>"><?= e($label) ?></button>
                </form>
              <?php endforeach; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
