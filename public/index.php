<?php
require __DIR__ . '/../includes/bootstrap.php';

$packages = db()->query('SELECT * FROM packages WHERE is_active = 1 ORDER BY id LIMIT 4')->fetchAll();
$categories = db()->query('SELECT c.*, COUNT(i.id) AS item_count
                           FROM categories c
                           LEFT JOIN items i ON i.category_id = c.id AND i.is_active = 1
                           GROUP BY c.id ORDER BY c.name')->fetchAll();
$minDate = date('Y-m-d', strtotime('+2 days'));

$pageTitle = 'Rent decor for your event';
require __DIR__ . '/../includes/header.php';
?>

<section class="hero">
  <form class="hero-form" method="get" action="<?= e(url('items.php')) ?>">
    <h1>
      <label for="hero-date">My event is on</label>
      <input id="hero-date" type="date" name="date" min="<?= e($minDate) ?>" required>
    </h1>
    <p class="hero-sub">Backdrops, centerpieces, linens, and lights for quinces, weddings, and birthdays in the San Fernando Valley. Use it for one night, then bring it back.</p>
    <button type="submit" class="btn btn-big">See what's open that day</button>
  </form>
</section>

<section class="steps" aria-labelledby="how">
  <h2 id="how">How renting works</h2>
  <ol class="step-list">
    <li><strong>Pick your date and decor.</strong> You only see what's actually free for your event.</li>
    <li><strong>Pick up the day before.</strong> Everything comes packed with a checklist.</li>
    <li><strong>Return the day after.</strong> No packing up at 1 a.m. and nothing left in your garage.</li>
  </ol>
</section>

<section aria-labelledby="pk">
  <div class="section-head">
    <h2 id="pk">Ready made packages</h2>
    <a href="<?= e(url('packages.php')) ?>">See all packages</a>
  </div>
  <div class="grid">
    <?php foreach ($packages as $p): ?>
      <a class="chip-card" href="<?= e(url('package.php?id=' . $p['id'])) ?>">
        <span class="swatch" style="--swatch: <?= e($p['color_hex']) ?>"></span>
        <span class="chip-body">
          <span class="chip-kicker"><?= e($p['event_type']) ?></span>
          <span class="chip-title"><?= e($p['name']) ?></span>
          <span class="chip-price"><?= money($p['price']) ?></span>
        </span>
      </a>
    <?php endforeach; ?>
  </div>
</section>

<section aria-labelledby="cat">
  <h2 id="cat">Shop by category</h2>
  <ul class="category-list">
    <?php foreach ($categories as $c): ?>
      <li><a href="<?= e(url('items.php?category=' . urlencode($c['slug']))) ?>"><?= e($c['name']) ?> <span><?= (int) $c['item_count'] ?></span></a></li>
    <?php endforeach; ?>
  </ul>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
