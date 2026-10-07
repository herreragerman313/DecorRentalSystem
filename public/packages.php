<?php
require __DIR__ . '/../includes/bootstrap.php';

$packages = db()->query('SELECT p.*, COUNT(pi.item_id) AS piece_types, COALESCE(SUM(pi.quantity), 0) AS piece_count
                         FROM packages p
                         LEFT JOIN package_items pi ON pi.package_id = p.id
                         WHERE p.is_active = 1
                         GROUP BY p.id ORDER BY p.season IS NULL, p.price')->fetchAll();

$pageTitle = 'Packages';
require __DIR__ . '/../includes/header.php';
?>

<h1>Packages</h1>
<p class="lead">Themed sets picked to match. Add extra pieces to make one yours, and choose pickup or full setup when you book.</p>

<div class="package-list">
  <?php foreach ($packages as $p): ?>
    <article class="package-row">
      <span class="swatch" style="--swatch: <?= e($p['color_hex']) ?>"></span>
      <div>
        <p class="chip-kicker"><?= e($p['event_type']) ?><?php if ($p['season']): ?> <span class="season-tag"><?= e($p['season']) ?></span><?php endif; ?></p>
        <h2><a href="<?= e(url('package.php?id=' . $p['id'])) ?>"><?= e($p['name']) ?></a></h2>
        <p><?= e($p['description']) ?></p>
        <p class="muted"><?= (int) $p['piece_count'] ?> pieces. Setup and takedown <?= money($p['setup_fee']) ?>.</p>
      </div>
      <p class="package-price"><?= money($p['price']) ?></p>
    </article>
  <?php endforeach; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
