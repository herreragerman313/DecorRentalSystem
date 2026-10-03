<?php
require __DIR__ . '/../includes/bootstrap.php';

$id = (int) ($_GET['id'] ?? 0);
$stmt = db()->prepare('SELECT i.*, c.name AS category_name, c.slug AS category_slug
                       FROM items i JOIN categories c ON c.id = i.category_id
                       WHERE i.id = ? AND i.is_active = 1');
$stmt->execute([$id]);
$item = $stmt->fetch();

if (!$item) {
    http_response_code(404);
    $pageTitle = 'Item not found';
    require __DIR__ . '/../includes/header.php';
    echo '<div class="empty"><p>That item is not available anymore.</p><a class="btn" href="' . e(url('items.php')) . '">Browse decor</a></div>';
    require __DIR__ . '/../includes/footer.php';
    exit;
}

$date = $_GET['date'] ?? '';
$dateError = $date !== '' ? check_event_date($date) : null;
$hasDate = $date !== '' && $dateError === null;
$free = null;
if ($hasDate) {
    [$pickup, $return] = rental_window($date);
    $booked = booked_quantities($pickup, $return, [$id]);
    $free = (int) $item['quantity'] - ($booked[$id] ?? 0);
}

// Which packages use this item
$stmt = db()->prepare('SELECT p.id, p.name FROM packages p
                       JOIN package_items pi ON pi.package_id = p.id
                       WHERE pi.item_id = ? AND p.is_active = 1');
$stmt->execute([$id]);
$inPackages = $stmt->fetchAll();

$minDate = date('Y-m-d', strtotime('+2 days'));
$pageTitle = $item['name'];
require __DIR__ . '/../includes/header.php';
?>

<p class="crumbs"><a href="<?= e(url('items.php?category=' . urlencode($item['category_slug']))) ?>"><?= e($item['category_name']) ?></a></p>

<div class="detail">
  <div class="detail-visual">
    <?php if ($item['image_url']): ?>
      <img class="swatch swatch-large" src="<?= e($item['image_url']) ?>" alt="<?= e($item['name']) ?>">
    <?php else: ?>
      <div class="swatch swatch-large" style="--swatch: <?= e($item['color_hex']) ?>"><span class="swatch-name"><?= e($item['color_name']) ?></span></div>
    <?php endif; ?>
  </div>

  <div class="detail-info">
    <h1><?= e($item['name']) ?></h1>
    <p class="price-line"><?= money($item['price']) ?> each per event</p>
    <p><?= e($item['description']) ?></p>
    <p class="muted">We own <?= (int) $item['quantity'] ?>. Color: <?= e($item['color_name']) ?>.</p>

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
      <?php elseif ($hasDate && $free <= 0): ?>
        <p class="avail avail-no">Booked for <?= e(nice_date($date)) ?>. Try a different date.</p>
      <?php elseif ($hasDate): ?>
        <p class="avail avail-yes"><?= $free ?> free for <?= e(nice_date($date)) ?></p>
        <p class="small">Pickup <?= e(nice_date($pickup)) ?>. Return <?= e(nice_date($return)) ?>.</p>

        <form method="post" action="<?= e(url('book.php')) ?>" class="stack-form" data-price="<?= e($item['price']) ?>">
          <?= csrf_field() ?>
          <input type="hidden" name="type" value="item">
          <input type="hidden" name="id" value="<?= $id ?>">
          <input type="hidden" name="date" value="<?= e($date) ?>">
          <label>How many
            <input type="number" name="quantity" value="1" min="1" max="<?= $free ?>" required data-qty>
          </label>
          <label>Notes for us (optional)
            <textarea name="notes" rows="2" maxlength="500" placeholder="Colors, venue, anything we should know"></textarea>
          </label>
          <p class="total">Total <strong data-total><?= money($item['price']) ?></strong></p>
          <button type="submit" class="btn btn-big">Request this rental</button>
        </form>
      <?php endif; ?>
    </div>

    <?php if ($inPackages): ?>
      <p class="muted">Also part of:
        <?php foreach ($inPackages as $i => $p): ?>
          <a href="<?= e(url('package.php?id=' . $p['id'])) ?>"><?= e($p['name']) ?></a><?= $i < count($inPackages) - 1 ? ',' : '' ?>
        <?php endforeach; ?>
      </p>
    <?php endif; ?>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
