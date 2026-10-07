<?php
require __DIR__ . '/../../includes/bootstrap.php';
require_admin();

// Show or hide an item (we never delete, so old rentals keep their history)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $stmt = db()->prepare('UPDATE items SET is_active = 1 - is_active WHERE id = ?');
    $stmt->execute([(int) ($_POST['id'] ?? 0)]);
    flash('success', 'Item updated.');
    redirect('admin/items.php');
}

$items = db()->query("SELECT i.*, c.name AS category,
        (SELECT COALESCE(SUM(ri.quantity), 0) FROM rental_items ri
         JOIN rentals r ON r.id = ri.rental_id
         WHERE ri.item_id = i.id AND r.status = 'picked_up') AS out_now
    FROM items i JOIN categories c ON c.id = i.category_id
    ORDER BY c.name, i.name")->fetchAll();

$pageTitle = 'Manage items';
require __DIR__ . '/../../includes/header.php';
?>

<p class="crumbs"><a href="<?= e(url('admin/index.php')) ?>">Rentals</a></p>
<div class="section-head">
  <h1>Items</h1>
  <a class="btn" href="<?= e(url('admin/item-form.php')) ?>">Add item</a>
</div>

<div class="table-wrap">
  <table>
    <thead>
      <tr><th></th><th>Name</th><th>Category</th><th>Rent</th><th>Owned</th><th>For sale</th><th>Out now</th><th>On site</th><th></th></tr>
    </thead>
    <tbody>
      <?php foreach ($items as $item): ?>
        <tr class="<?= $item['is_active'] ? '' : 'row-muted' ?>">
          <td><span class="dot" style="--swatch: <?= e($item['color_hex']) ?>"></span></td>
          <td><?= e($item['name']) ?></td>
          <td><?= e($item['category']) ?></td>
          <td><?= money($item['price']) ?></td>
          <td><?= (int) $item['quantity'] ?></td>
          <td><?= $item['sale_price'] !== null ? money($item['sale_price']) . ' (' . (int) $item['sale_stock'] . ' left)' : 'No' ?></td>
          <td><?= (int) $item['out_now'] ?></td>
          <td><?= $item['is_active'] ? 'Shown' : 'Hidden' ?></td>
          <td>
            <form method="post" class="inline">
              <?= csrf_field() ?>
              <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
              <button type="submit" class="btn btn-small btn-quiet"><?= $item['is_active'] ? 'Hide' : 'Show' ?></button>
            </form>
            <a href="<?= e(url('admin/item-form.php?id=' . $item['id'])) ?>">Edit</a>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
