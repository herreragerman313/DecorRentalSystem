<?php
require __DIR__ . '/../includes/bootstrap.php';

$seasonal = db()->query('SELECT * FROM packages WHERE is_active = 1 AND season IS NOT NULL ORDER BY id')->fetchAll();
$packages = db()->query('SELECT * FROM packages WHERE is_active = 1 AND season IS NULL ORDER BY id LIMIT 4')->fetchAll();
$categories = db()->query('SELECT c.*, COUNT(i.id) AS item_count
                           FROM categories c
                           LEFT JOIN items i ON i.category_id = c.id AND i.is_active = 1
                           GROUP BY c.id ORDER BY c.name')->fetchAll();
$reused = pieces_reused();
$minDate = date('Y-m-d', strtotime('+2 days'));

$pageTitle = 'Reusable decor for your event';
require __DIR__ . '/../includes/header.php';

function package_card(array $p): void
{ ?>
  <a class="chip-card" href="<?= e(url('package.php?id=' . $p['id'])) ?>">
    <span class="swatch" style="--swatch: <?= e($p['color_hex']) ?>"></span>
    <span class="chip-body">
      <span class="chip-kicker"><?= e($p['event_type']) ?></span>
      <span class="chip-title"><?= e($p['name']) ?></span>
      <span class="chip-price"><?= money($p['price']) ?></span>
    </span>
  </a>
<?php }
?>

<section class="hero">
  <form class="hero-form" method="get" action="<?= e(url('items.php')) ?>">
    <h1>
      <label for="hero-date">My event is on</label>
      <input id="hero-date" type="date" name="date" min="<?= e($minDate) ?>" required>
    </h1>
    <p class="hero-sub">Stylish, reusable decor for birthdays, showers, graduations, quinces, and weddings. Rent it and decorate yourself, or let our team set it up and take it down. Either way, nothing ends up in the trash or your garage.</p>
    <button type="submit" class="btn btn-big">See what's open that day</button>
  </form>
  <?php if ($reused > 0): ?>
    <p class="reuse-count"><strong><?= number_format($reused) ?></strong> pieces reused so far. That is decor nobody had to buy for one night and throw away.</p>
  <?php endif; ?>
</section>

<section class="steps" aria-labelledby="how">
  <h2 id="how">How it works</h2>
  <ol class="step-list">
    <li><strong>Pick your date and decor.</strong> Choose a themed package and add extras, or build your own from single pieces.</li>
    <li><strong>Decorate your way.</strong> Pick it up the day before, or have our team set everything up at your venue.</li>
    <li><strong>Give it back.</strong> Return it the day after, or we take it down for you. Nothing to store, nothing to throw away.</li>
  </ol>
</section>

<?php if ($seasonal): ?>
<section aria-labelledby="season">
  <div class="section-head">
    <h2 id="season">New this season</h2>
  </div>
  <div class="grid">
    <?php foreach ($seasonal as $p) { package_card($p); } ?>
  </div>
</section>
<?php endif; ?>

<section aria-labelledby="pk">
  <div class="section-head">
    <h2 id="pk">Themed packages</h2>
    <a href="<?= e(url('packages.php')) ?>">See all packages</a>
  </div>
  <div class="grid">
    <?php foreach ($packages as $p) { package_card($p); } ?>
  </div>
</section>

<section aria-labelledby="cat">
  <h2 id="cat">Rent by category</h2>
  <ul class="category-list">
    <?php foreach ($categories as $c): ?>
      <li><a href="<?= e(url('items.php?category=' . urlencode($c['slug']))) ?>"><?= e($c['name']) ?> <span><?= (int) $c['item_count'] ?></span></a></li>
    <?php endforeach; ?>
  </ul>
</section>

<section class="perks" aria-labelledby="why">
  <h2 id="why">Why people come back</h2>
  <ul class="perk-list">
    <li><strong>Returning customers save <?= (int) LOYALTY_PERCENT ?>%</strong> on every rental after their first one.</li>
    <li><strong>New collections every season</strong> so your next party doesn't look like your last one.</li>
    <li><strong>Love a piece? Keep it.</strong> Some of our decor is <a href="<?= e(url('shop.php')) ?>">for sale</a> too.</li>
  </ul>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
