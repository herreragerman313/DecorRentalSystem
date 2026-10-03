<?php
require __DIR__ . '/../includes/bootstrap.php';

$packages = db()->query('SELECT p.*, COUNT(pi.item_id) AS piece_types, COALESCE(SUM(pi.quantity), 0) AS piece_count
                         FROM packages p
                         LEFT JOIN package_items pi ON pi.package_id = p.id
                         WHERE p.is_active = 1
                         GROUP BY p.id ORDER BY p.price')->fetchAll();

$pageTitle = 'Packages';
require __DIR__ . '/../includes/header.php';
?>

<h1>Packages</h1>
<p class="lead">Everything for one kind of event, picked to match. One price, one pickup.</p>

<div class="package-list">
  <?php foreach ($packages as $p): ?>
    <article class="package-row">
      <span class="swatch" style="--swatch: <?= e($p['color_hex']) ?>"></span>
      <div>
        <p class="chip-kicker"><?= e($p['event_type']) ?></p>
        <h2><a href="<?= e(url('package.php?id=' . $p['id'])) ?>"><?= e($p['name']) ?></a></h2>
        <p><?= e($p['description']) ?></p>
        <p class="muted"><?= (int) $p['piece_count'] ?> pieces</p>
      </div>
      <p class="package-price"><?= money($p['price']) ?></p>
    </article>
  <?php endforeach; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
