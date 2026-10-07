<?php
require __DIR__ . '/../../includes/bootstrap.php';
require_admin();

$orders = db()->query("SELECT o.*, u.name AS customer, u.email,
        (SELECT GROUP_CONCAT(CONCAT(oi.quantity, ' x ', i.name) ORDER BY i.name SEPARATOR ', ')
         FROM order_items oi JOIN items i ON i.id = oi.item_id
         WHERE oi.order_id = o.id) AS item_list
    FROM orders o JOIN users u ON u.id = o.user_id
    ORDER BY FIELD(o.status, 'pending', 'ready', 'completed', 'cancelled'), o.created_at DESC")->fetchAll();

$sold = db()->query("SELECT COALESCE(SUM(total_price), 0) FROM orders WHERE status = 'completed'")->fetchColumn();

$next = [
    'pending' => ['ready' => 'Mark ready', 'cancelled' => 'Cancel'],
    'ready'   => ['completed' => 'Mark picked up', 'cancelled' => 'Cancel'],
];

$pageTitle = 'Shop orders';
require __DIR__ . '/../../includes/header.php';
?>

<p class="crumbs"><a href="<?= e(url('admin/index.php')) ?>">Rentals</a></p>
<h1>Shop orders</h1>
<p class="muted"><?= money($sold) ?> in pieces sold and picked up so far.</p>

<?php if (!$orders): ?>
  <div class="empty"><p>No one has bought anything yet.</p></div>
<?php else: ?>
  <div class="table-wrap">
    <table>
      <thead><tr><th>#</th><th>Customer</th><th>What</th><th>Total</th><th>Status</th><th>Update</th></tr></thead>
      <tbody>
        <?php foreach ($orders as $o): ?>
          <tr>
            <td><?= (int) $o['id'] ?></td>
            <td><?= e($o['customer']) ?><br><span class="small"><?= e($o['email']) ?></span></td>
            <td><?= e($o['item_list']) ?></td>
            <td><?= money($o['total_price']) ?></td>
            <td><span class="status status-order-<?= e($o['status']) ?>"><?= e(order_status_label($o['status'])) ?></span></td>
            <td>
              <?php foreach ($next[$o['status']] ?? [] as $to => $label): ?>
                <form method="post" action="<?= e(url('admin/update-order.php')) ?>" class="inline"<?= $to === 'cancelled' ? ' data-confirm="Cancel order #' . (int) $o['id'] . '? The pieces go back in stock."' : '' ?>>
                  <?= csrf_field() ?>
                  <input type="hidden" name="id" value="<?= (int) $o['id'] ?>">
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
