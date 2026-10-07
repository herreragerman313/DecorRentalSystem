<?php
require __DIR__ . '/../includes/bootstrap.php';
$user = require_login();

$stmt = db()->prepare('SELECT r.*, p.name AS package_name,
                              (SELECT GROUP_CONCAT(i.name ORDER BY i.name SEPARATOR ", ")
                               FROM rental_items ri JOIN items i ON i.id = ri.item_id
                               WHERE ri.rental_id = r.id) AS item_names
                       FROM rentals r
                       LEFT JOIN packages p ON p.id = r.package_id
                       WHERE r.user_id = ?
                       ORDER BY r.event_date DESC');
$stmt->execute([$user['id']]);
$rentals = $stmt->fetchAll();

$stmt = db()->prepare('SELECT o.*,
                              (SELECT GROUP_CONCAT(CONCAT(oi.quantity, " x ", i.name) ORDER BY i.name SEPARATOR ", ")
                               FROM order_items oi JOIN items i ON i.id = oi.item_id
                               WHERE oi.order_id = o.id) AS item_list
                       FROM orders o WHERE o.user_id = ? ORDER BY o.created_at DESC');
$stmt->execute([$user['id']]);
$orders = $stmt->fetchAll();

$reused = pieces_reused((int) $user['id']);
$returning = gets_loyalty_discount((int) $user['id']);

$today = date('Y-m-d');
$upcoming = array_filter($rentals, fn($r) => $r['return_date'] >= $today && !in_array($r['status'], ['cancelled', 'returned']));
$past     = array_filter($rentals, fn($r) => !in_array($r, $upcoming, true));

function rental_rows(array $list): void
{
    foreach ($list as $r): ?>
      <a class="rental-row" href="<?= e(url('rental.php?id=' . $r['id'])) ?>">
        <span class="rental-date"><?= e(date('M j', strtotime($r['event_date']))) ?><small><?= e(date('Y', strtotime($r['event_date']))) ?></small></span>
        <span class="rental-what">
          <strong><?= e($r['package_name'] ?: $r['item_names']) ?></strong>
          <span class="muted"><?= $r['service'] === 'setup' ? 'We set it up for you' : 'Pickup ' . e(nice_date($r['pickup_date'])) ?></span>
        </span>
        <span class="status status-<?= e($r['status']) ?>"><?= e(status_label($r['status'], $r['service'] === 'setup')) ?></span>
        <span class="rental-total"><?= money($r['total_price']) ?></span>
      </a>
    <?php endforeach;
}

$pageTitle = 'My rentals';
require __DIR__ . '/../includes/header.php';
?>

<h1>My rentals</h1>

<?php if ($reused > 0 || $returning): ?>
  <div class="impact">
    <?php if ($reused > 0): ?>
      <p><strong><?= $reused ?></strong> pieces you've reused instead of buying and throwing away.</p>
    <?php endif; ?>
    <?php if ($returning): ?>
      <p class="perk">You're a returning customer, so you save <?= (int) LOYALTY_PERCENT ?>% on every rental.</p>
    <?php endif; ?>
  </div>
<?php endif; ?>

<?php if (!$rentals): ?>
  <div class="empty">
    <p>No rentals yet. Start with your event date.</p>
    <a class="btn" href="<?= e(url('items.php')) ?>">Browse decor</a>
  </div>
<?php else: ?>
  <h2>Coming up</h2>
  <?php if ($upcoming): ?>
    <div class="rental-list"><?php rental_rows($upcoming); ?></div>
  <?php else: ?>
    <p class="muted">Nothing coming up. <a href="<?= e(url('items.php')) ?>">Plan your next event</a>.</p>
  <?php endif; ?>

  <?php if ($past): ?>
    <h2>Past and cancelled</h2>
    <div class="rental-list"><?php rental_rows($past); ?></div>
  <?php endif; ?>
<?php endif; ?>

<?php if ($orders): ?>
  <h2 id="orders">Pieces you bought</h2>
  <div class="table-wrap">
    <table>
      <thead><tr><th>#</th><th>What</th><th>Total</th><th>Status</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($orders as $o): ?>
          <tr>
            <td><?= (int) $o['id'] ?></td>
            <td><?= e($o['item_list']) ?></td>
            <td><?= money($o['total_price']) ?></td>
            <td><span class="status status-order-<?= e($o['status']) ?>"><?= e(order_status_label($o['status'])) ?></span></td>
            <td>
              <?php if ($o['status'] === 'pending'): ?>
                <form method="post" action="<?= e(url('cancel-order.php')) ?>" class="inline" data-confirm="Cancel order #<?= (int) $o['id'] ?>?">
                  <?= csrf_field() ?>
                  <input type="hidden" name="id" value="<?= (int) $o['id'] ?>">
                  <button type="submit" class="btn btn-small btn-quiet">Cancel</button>
                </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
