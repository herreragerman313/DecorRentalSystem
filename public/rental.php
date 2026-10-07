<?php
require __DIR__ . '/../includes/bootstrap.php';
$user = require_login();

$id = (int) ($_GET['id'] ?? 0);
$stmt = db()->prepare('SELECT r.*, p.name AS package_name, u.name AS customer_name, u.email AS customer_email, u.phone AS customer_phone
                       FROM rentals r
                       JOIN users u ON u.id = r.user_id
                       LEFT JOIN packages p ON p.id = r.package_id
                       WHERE r.id = ?');
$stmt->execute([$id]);
$rental = $stmt->fetch();

// Customers can only see their own rentals. Admins can see all of them.
if (!$rental || ((int) $rental['user_id'] !== (int) $user['id'] && $user['role'] !== 'admin')) {
    http_response_code(404);
    $pageTitle = 'Rental not found';
    require __DIR__ . '/../includes/header.php';
    echo '<div class="empty"><p>We could not find that rental.</p><a class="btn" href="' . e(url('my-rentals.php')) . '">Go to my rentals</a></div>';
    require __DIR__ . '/../includes/footer.php';
    exit;
}

$stmt = db()->prepare('SELECT ri.*, i.name, i.color_name, i.color_hex
                       FROM rental_items ri JOIN items i ON i.id = ri.item_id
                       WHERE ri.rental_id = ? ORDER BY i.name');
$stmt->execute([$id]);
$lines = $stmt->fetchAll();
$pieceCount = array_sum(array_column($lines, 'quantity'));

$canCancel = in_array($rental['status'], ['pending', 'confirmed']) && $rental['pickup_date'] > date('Y-m-d');

$isSetup = $rental['service'] === 'setup';
$steps = $isSetup
    ? ['pending' => 'Requested', 'confirmed' => 'Confirmed', 'picked_up' => 'Set up', 'returned' => 'Taken down']
    : ['pending' => 'Requested', 'confirmed' => 'Confirmed', 'picked_up' => 'Picked up', 'returned' => 'Returned'];
$stepKeys = array_keys($steps);
$currentStep = array_search($rental['status'], $stepKeys, true);

$pageTitle = 'Rental #' . $id;
require __DIR__ . '/../includes/header.php';
?>

<p class="crumbs"><a href="<?= e(url($user['role'] === 'admin' && (int) $rental['user_id'] !== (int) $user['id'] ? 'admin/index.php' : 'my-rentals.php')) ?>">Back to rentals</a></p>

<div class="rental-head">
  <div>
    <h1><?= e($rental['package_name'] ?: 'Rental #' . $id) ?></h1>
    <p class="price-line">For your event on <?= e(nice_date($rental['event_date'])) ?></p>
  </div>
  <span class="status status-<?= e($rental['status']) ?>"><?= e(status_label($rental['status'], $isSetup)) ?></span>
</div>

<?php if ($rental['status'] === 'cancelled'): ?>
  <p class="flash flash-info">This rental was cancelled. The items went back on the calendar.</p>
<?php else: ?>
  <ol class="progress" aria-label="Rental progress">
    <?php foreach ($steps as $key => $label):
        $index = array_search($key, $stepKeys, true); ?>
      <li class="<?= $index < $currentStep ? 'done' : ($index === $currentStep ? 'current' : '') ?>"<?= $index === $currentStep ? ' aria-current="step"' : '' ?>><?= e($label) ?></li>
    <?php endforeach; ?>
  </ol>
<?php endif; ?>

<div class="rental-grid">
  <section class="card">
    <h2>Details</h2>
    <dl class="facts">
      <?php if ($isSetup): ?>
        <dt>Service</dt><dd>We set it up and take it down</dd>
        <dt>Where</dt><dd><?= e($rental['address']) ?></dd>
        <dt>Event</dt><dd><?= e(nice_date($rental['event_date'])) ?></dd>
      <?php else: ?>
        <dt>Pickup</dt><dd><?= e(nice_date($rental['pickup_date'])) ?></dd>
        <dt>Event</dt><dd><?= e(nice_date($rental['event_date'])) ?></dd>
        <dt>Return by</dt><dd><?= e(nice_date($rental['return_date'])) ?></dd>
      <?php endif; ?>
      <dt>Decor</dt><dd><?= money($rental['subtotal']) ?></dd>
      <?php if ((float) $rental['setup_fee'] > 0): ?>
        <dt>Setup and takedown</dt><dd><?= money($rental['setup_fee']) ?></dd>
      <?php endif; ?>
      <?php if ((float) $rental['discount'] > 0): ?>
        <dt>Returning customer</dt><dd>&minus;<?= money($rental['discount']) ?></dd>
      <?php endif; ?>
      <dt>Total</dt><dd><?= money($rental['total_price']) ?></dd>
    </dl>
    <p class="impact-line"><?= (int) $pieceCount ?> reusable <?= $pieceCount == 1 ? 'piece' : 'pieces' ?> that nobody has to buy for one night and throw away.</p>
    <?php if ($rental['notes']): ?>
      <p class="muted">Your notes: <?= e($rental['notes']) ?></p>
    <?php endif; ?>
    <?php if ($user['role'] === 'admin'): ?>
      <p class="muted">Customer: <?= e($rental['customer_name']) ?>, <?= e($rental['customer_email']) ?><?= $rental['customer_phone'] ? ', ' . e($rental['customer_phone']) : '' ?></p>
    <?php endif; ?>

    <?php if ($canCancel): ?>
      <form method="post" action="<?= e(url('cancel.php')) ?>" data-confirm="Cancel this rental? The items go back on the calendar for other people.">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= $id ?>">
        <button type="submit" class="btn btn-danger">Cancel rental</button>
      </form>
    <?php endif; ?>
  </section>

  <section class="card checklist" data-checklist>
    <h2><?= $isSetup ? 'What we bring' : 'Packing checklist' ?></h2>
    <p class="muted"><?= $isSetup ? 'Our team checks these off at setup and takedown.' : 'Check things off as you pack them up after your event.' ?> <span data-count>0</span> of <?= count($lines) ?> done, <?= (int) $pieceCount ?> pieces total.</p>
    <ul>
      <?php foreach ($lines as $line): ?>
        <li>
          <label>
            <input type="checkbox">
            <span class="dot" style="--swatch: <?= e($line['color_hex']) ?>"></span>
            <span><strong><?= (int) $line['quantity'] ?></strong> &times; <?= e($line['name']) ?></span>
          </label>
        </li>
      <?php endforeach; ?>
    </ul>
  </section>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
