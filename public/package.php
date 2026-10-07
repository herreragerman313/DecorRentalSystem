<?php
require __DIR__ . '/../includes/bootstrap.php';

$id = (int) ($_GET['id'] ?? 0);
$stmt = db()->prepare('SELECT * FROM packages WHERE id = ? AND is_active = 1');
$stmt->execute([$id]);
$package = $stmt->fetch();

if (!$package) {
    http_response_code(404);
    $pageTitle = 'Package not found';
    require __DIR__ . '/../includes/header.php';
    echo '<div class="empty"><p>That package is not available anymore.</p><a class="btn" href="' . e(url('packages.php')) . '">See packages</a></div>';
    require __DIR__ . '/../includes/footer.php';
    exit;
}

$stmt = db()->prepare('SELECT i.id, i.name, i.color_name, i.color_hex, i.price, i.quantity AS owned, pi.quantity
                       FROM package_items pi JOIN items i ON i.id = pi.item_id
                       WHERE pi.package_id = ? ORDER BY i.name');
$stmt->execute([$id]);
$pieces = $stmt->fetchAll();

$separateTotal = 0;
foreach ($pieces as $p) {
    $separateTotal += $p['price'] * $p['quantity'];
}

$date = $_GET['date'] ?? '';
$dateError = $date !== '' ? check_event_date($date) : null;
$hasDate = $date !== '' && $dateError === null;
$short = [];
$extras = [];
if ($hasDate) {
    [$pickup, $return] = rental_window($date);
    $booked = booked_quantities($pickup, $return);
    foreach ($pieces as $p) {
        $free = (int) $p['owned'] - ($booked[(int) $p['id']] ?? 0);
        if ($free < (int) $p['quantity']) {
            $short[] = $p['name'];
        }
    }

    // Everything else that's free that day, so people can customize the package
    $inPackage = [];
    foreach ($pieces as $p) {
        $inPackage[(int) $p['id']] = (int) $p['quantity'];
    }
    $all = db()->query('SELECT id, name, price, quantity, color_hex FROM items WHERE is_active = 1 ORDER BY name')->fetchAll();
    foreach ($all as $it) {
        $free = (int) $it['quantity'] - ($booked[(int) $it['id']] ?? 0) - ($inPackage[(int) $it['id']] ?? 0);
        if ($free > 0) {
            $it['free'] = $free;
            $extras[] = $it;
        }
    }
}

$minDate = date('Y-m-d', strtotime('+2 days'));
$pageTitle = $package['name'];
require __DIR__ . '/../includes/header.php';
?>

<p class="crumbs"><a href="<?= e(url('packages.php')) ?>">Packages</a></p>

<div class="detail">
  <div class="detail-visual">
    <div class="swatch swatch-large" style="--swatch: <?= e($package['color_hex']) ?>"><span class="swatch-name"><?= e($package['event_type']) ?></span></div>
  </div>

  <div class="detail-info">
    <h1><?= e($package['name']) ?></h1>
    <?php if ($package['season']): ?><p class="season-tag"><?= e($package['season']) ?> collection</p><?php endif; ?>
    <p class="price-line"><?= money($package['price']) ?> for everything below
      <?php if ($separateTotal > $package['price']): ?>
        <span class="muted">(<?= money($separateTotal - $package['price']) ?> less than renting each piece)</span>
      <?php endif; ?>
    </p>
    <p><?= e($package['description']) ?></p>

    <h2>What's in the box</h2>
    <ul class="piece-list">
      <?php foreach ($pieces as $p): ?>
        <li>
          <span class="dot" style="--swatch: <?= e($p['color_hex']) ?>"></span>
          <span><?= (int) $p['quantity'] ?> &times; <a href="<?= e(url('item.php?id=' . $p['id'])) ?>"><?= e($p['name']) ?></a></span>
        </li>
      <?php endforeach; ?>
    </ul>

    <div class="book-box">
      <h2>Check your date</h2>
      <form method="get" class="row-form">
        <input type="hidden" name="id" value="<?= $id ?>">
        <label class="sr-only" for="date">Event date</label>
        <input id="date" type="date" name="date" value="<?= e($date) ?>" min="<?= e($minDate) ?>" required>
        <button type="submit" class="btn btn-ghost">Check</button>
      </form>

      <?php if ($dateError): ?>
        <p class="flash flash-error"><?= e($dateError) ?></p>
      <?php elseif ($hasDate && $short): ?>
        <p class="avail avail-no">Not enough for <?= e(nice_date($date)) ?>: <?= e(implode(', ', $short)) ?>. Try another date or rent pieces one by one.</p>
      <?php elseif ($hasDate): ?>
        <p class="avail avail-yes">Everything is free for <?= e(nice_date($date)) ?></p>
        <p class="small">Held for you <?= e(nice_date($pickup)) ?> through <?= e(nice_date($return)) ?>.</p>
        <form method="post" action="<?= e(url('book.php')) ?>" class="stack-form" data-package-price="<?= e($package['price']) ?>">
          <?= csrf_field() ?>
          <input type="hidden" name="type" value="package">
          <input type="hidden" name="id" value="<?= $id ?>">
          <input type="hidden" name="date" value="<?= e($date) ?>">

          <details class="extras">
            <summary>Make it yours: add extra pieces</summary>
            <p class="small">Extras are added at their normal rental price. Only what's free on your date is shown.</p>
            <ul class="extra-list">
              <?php foreach ($extras as $x): ?>
                <li>
                  <span class="dot" style="--swatch: <?= e($x['color_hex']) ?>"></span>
                  <label for="extra-<?= (int) $x['id'] ?>"><?= e($x['name']) ?> <small><?= money($x['price']) ?> each, <?= (int) $x['free'] ?> free</small></label>
                  <input id="extra-<?= (int) $x['id'] ?>" type="number" name="extra[<?= (int) $x['id'] ?>]" value="0" min="0" max="<?= (int) $x['free'] ?>" data-extra-price="<?= e($x['price']) ?>">
                </li>
              <?php endforeach; ?>
            </ul>
          </details>

          <?= service_fields((float) $package['setup_fee']) ?>
          <label>Notes for us (optional)
            <textarea name="notes" rows="2" maxlength="500" placeholder="Names for marquee letters, colors, anything else"></textarea>
          </label>
          <p class="total">Total before any discount <strong data-total><?= money($package['price']) ?></strong></p>
          <button type="submit" class="btn btn-big">Request this package</button>
        </form>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
