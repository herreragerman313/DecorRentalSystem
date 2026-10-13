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
    <span class="swatch shape-arch" style="--swatch: <?= e($p['color_hex']) ?>"></span>
    <span class="chip-body">
      <span class="chip-kicker"><?= e($p['event_type']) ?></span>
      <span class="chip-title"><?= e($p['name']) ?></span>
      <span class="chip-price"><?= money($p['price']) ?></span>
    </span>
  </a>
<?php }
?>

<section class="hero">
  <div class="hero-copy">
    <h1>Rent the look. Skip the waste.</h1>
    <p class="hero-sub">Stylish, reusable decor for birthdays, showers, grads, quinces, and weddings. Style it your way, give it back after, and skip the pile of single use party stuff.</p>
    <form class="date-card" method="get" action="<?= e(url('items.php')) ?>">
      <label for="hero-date">When's the party?</label>
      <div class="date-row">
        <input id="hero-date" type="date" name="date" min="<?= e($minDate) ?>" required>
        <button type="submit" class="btn btn-big">See what's free</button>
      </div>
      <p class="small">Pick up the day before, return the day after. Want us to decorate? Setup is an optional add-on.</p>
    </form>
  </div>
  <div class="hero-art">
    <div class="art" aria-hidden="true">
      <span class="blob b-ring"></span>
      <span class="blob b-arch"></span>
      <span class="blob b-sun"></span>
      <span class="blob b-pill"></span>
      <span class="blob b-dot1"></span>
      <span class="blob b-dot2"></span>
    </div>
    <?php if ($reused > 0): ?>
      <p class="sticker"><strong><?= number_format($reused) ?></strong> pieces reused, not trashed</p>
    <?php endif; ?>
  </div>
</section>

<section class="steps" aria-labelledby="how">
  <h2 id="how">How it works</h2>
  <ol class="step-list">
    <li><strong>Pick your date and decor</strong> Choose a themed package and add extras, or build your own look from single pieces.</li>
    <li><strong>Pick up and style it</strong> Grab everything the day before and make it yours. Or add setup and we'll decorate for you.</li>
    <li><strong>Give it back</strong> Return it the day after. Nothing to store, nothing in the trash, and it heads to the next party.</li>
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
  <h2 id="why">Why rent instead of buy?</h2>
  <ul class="perk-list">
    <li><strong>Less waste</strong> Most party decor gets used once and thrown out. Ours gets cleaned and used again and again.</li>
    <li><strong>Spend less</strong> Pay a fraction of what it costs to buy, and returning customers save <?= (int) LOYALTY_PERCENT ?>% on every rental after their first one.</li>
    <li><strong>No storage</strong> Nothing sitting in your closet or garage for years. New collections every season so your next party looks different.</li>
  </ul>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
