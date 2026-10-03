<?php
require __DIR__ . '/../includes/bootstrap.php';

$date     = $_GET['date'] ?? '';
$category = $_GET['category'] ?? '';
$search   = trim($_GET['q'] ?? '');

$dateError = $date !== '' ? check_event_date($date) : null;
$hasDate   = $date !== '' && $dateError === null;

// Build the query from whatever filters were used
$sql = 'SELECT i.*, c.name AS category_name, c.slug AS category_slug
        FROM items i JOIN categories c ON c.id = i.category_id
        WHERE i.is_active = 1';
$params = [];
if ($category !== '') {
    $sql .= ' AND c.slug = ?';
    $params[] = $category;
}
if ($search !== '') {
    $sql .= ' AND (i.name LIKE ? OR i.description LIKE ? OR i.color_name LIKE ?)';
    $like = '%' . $search . '%';
    array_push($params, $like, $like, $like);
}
$sql .= ' ORDER BY c.name, i.name';
$stmt = db()->prepare($sql);
$stmt->execute($params);
$items = $stmt->fetchAll();

$booked = [];
if ($hasDate) {
    [$pickup, $return] = rental_window($date);
    $booked = booked_quantities($pickup, $return);
}

$categories = db()->query('SELECT * FROM categories ORDER BY name')->fetchAll();
$minDate = date('Y-m-d', strtotime('+2 days'));

$pageTitle = 'Browse decor';
require __DIR__ . '/../includes/header.php';
?>

<h1>Browse decor</h1>

<form class="filters" method="get">
  <label>Event date
    <input type="date" name="date" value="<?= e($date) ?>" min="<?= e($minDate) ?>">
  </label>
  <label>Category
    <select name="category">
      <option value="">All categories</option>
      <?php foreach ($categories as $c): ?>
        <option value="<?= e($c['slug']) ?>" <?= $category === $c['slug'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </label>
  <label>Search
    <input type="search" name="q" value="<?= e($search) ?>" placeholder="gold, linens, arch">
  </label>
  <button type="submit" class="btn">Update list</button>
</form>

<?php if ($dateError): ?>
  <p class="flash flash-error"><?= e($dateError) ?></p>
<?php elseif ($hasDate): ?>
  <p class="date-note">Showing what's free for <strong><?= e(nice_date($date)) ?></strong>. Pickup <?= e(nice_date($pickup)) ?>, return <?= e(nice_date($return)) ?>.</p>
<?php else: ?>
  <p class="date-note">Add your event date to see what's free that day.</p>
<?php endif; ?>

<?php if (!$items): ?>
  <div class="empty">
    <p>Nothing matches those filters.</p>
    <a class="btn" href="<?= e(url('items.php' . ($hasDate ? '?date=' . urlencode($date) : ''))) ?>">Clear filters</a>
  </div>
<?php else: ?>
  <div class="grid">
    <?php foreach ($items as $item):
        $free = (int) $item['quantity'] - ($booked[(int) $item['id']] ?? 0);
        $link = 'item.php?id=' . $item['id'] . ($hasDate ? '&date=' . urlencode($date) : '');
    ?>
      <a class="chip-card<?= $hasDate && $free <= 0 ? ' is-full' : '' ?>" href="<?= e(url($link)) ?>">
        <?php if ($item['image_url']): ?>
          <img class="swatch" src="<?= e($item['image_url']) ?>" alt="">
        <?php else: ?>
          <span class="swatch" style="--swatch: <?= e($item['color_hex']) ?>"><span class="swatch-name"><?= e($item['color_name']) ?></span></span>
        <?php endif; ?>
        <span class="chip-body">
          <span class="chip-kicker"><?= e($item['category_name']) ?></span>
          <span class="chip-title"><?= e($item['name']) ?></span>
          <span class="chip-price"><?= money($item['price']) ?> <small>each</small></span>
          <?php if ($hasDate): ?>
            <span class="avail <?= $free > 0 ? 'avail-yes' : 'avail-no' ?>">
              <?= $free > 0 ? $free . ' of ' . (int) $item['quantity'] . ' free' : 'Booked that day' ?>
            </span>
          <?php endif; ?>
        </span>
      </a>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
