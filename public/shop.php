<?php
require __DIR__ . '/../includes/bootstrap.php';

$items = db()->query('SELECT i.*, c.name AS category_name
                      FROM items i JOIN categories c ON c.id = i.category_id
                      WHERE i.is_active = 1 AND i.sale_price IS NOT NULL
                      ORDER BY c.name, i.name')->fetchAll();

$pageTitle = 'Shop';
require __DIR__ . '/../includes/header.php';
?>

<h1>Pieces to keep</h1>
<p class="lead">Love something you rented? Some of our reusable pieces are for sale. Buy them once and use them for years. Pick up at our next pop up or by appointment.</p>

<?php if (!$items): ?>
  <div class="empty"><p>Nothing is for sale right now. Everything can still be rented.</p>
    <a class="btn" href="<?= e(url('items.php')) ?>">Browse decor to rent</a></div>
<?php else: ?>
  <div class="grid">
    <?php foreach ($items as $item):
        $left = (int) $item['sale_stock']; ?>
      <div class="chip-card<?= $left <= 0 ? ' is-full' : '' ?>">
        <a class="card-link" href="<?= e(url('item.php?id=' . $item['id'])) ?>">
          <?php if ($item['image_url']): ?>
            <img class="swatch" src="<?= e($item['image_url']) ?>" alt="">
          <?php else: ?>
            <span class="swatch" style="--swatch: <?= e($item['color_hex']) ?>"><span class="swatch-name"><?= e($item['color_name']) ?></span></span>
          <?php endif; ?>
        </a>
        <span class="chip-body">
          <span class="chip-kicker"><?= e($item['category_name']) ?></span>
          <a class="chip-title" href="<?= e(url('item.php?id=' . $item['id'])) ?>"><?= e($item['name']) ?></a>
          <span class="chip-price"><?= money($item['sale_price']) ?> <small>to keep</small></span>
          <span class="small">Or rent it for <?= money($item['price']) ?></span>
          <?php if ($left > 0): ?>
            <form method="post" action="<?= e(url('buy.php')) ?>" class="row-form buy-row">
              <?= csrf_field() ?>
              <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
              <label class="sr-only" for="q<?= (int) $item['id'] ?>">How many</label>
              <input id="q<?= (int) $item['id'] ?>" type="number" name="quantity" value="1" min="1" max="<?= $left ?>">
              <button type="submit" class="btn btn-small">Buy</button>
            </form>
            <span class="small"><?= $left ?> left</span>
          <?php else: ?>
            <span class="avail avail-no">Sold out</span>
          <?php endif; ?>
        </span>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
