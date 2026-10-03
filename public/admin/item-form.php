<?php
require __DIR__ . '/../../includes/bootstrap.php';
require_admin();

$id = (int) ($_GET['id'] ?? 0);
$item = [
    'category_id' => '', 'name' => '', 'description' => '', 'color_name' => '',
    'color_hex' => '#CCCCCC', 'price' => '', 'quantity' => 1, 'image_url' => '',
];

if ($id) {
    $stmt = db()->prepare('SELECT * FROM items WHERE id = ?');
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if (!$found) {
        flash('error', 'That item does not exist.');
        redirect('admin/items.php');
    }
    $item = $found;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $item['category_id'] = (int) ($_POST['category_id'] ?? 0);
    $item['name']        = trim($_POST['name'] ?? '');
    $item['description'] = trim($_POST['description'] ?? '');
    $item['color_name']  = trim($_POST['color_name'] ?? '');
    $item['color_hex']   = strtoupper(trim($_POST['color_hex'] ?? '#CCCCCC'));
    $item['price']       = $_POST['price'] ?? '';
    $item['quantity']    = (int) ($_POST['quantity'] ?? 0);
    $item['image_url']   = trim($_POST['image_url'] ?? '');

    $stmt = db()->prepare('SELECT COUNT(*) FROM categories WHERE id = ?');
    $stmt->execute([$item['category_id']]);
    if (!$stmt->fetchColumn()) {
        $errors[] = 'Pick a category.';
    }
    if ($item['name'] === '' || strlen($item['name']) > 120) {
        $errors[] = 'Give the item a name (120 characters max).';
    }
    if ($item['description'] === '') {
        $errors[] = 'Add a short description.';
    }
    if ($item['color_name'] === '') {
        $errors[] = 'Name the color, like Gold or Blush.';
    }
    if (!preg_match('/^#[0-9A-F]{6}$/', $item['color_hex'])) {
        $errors[] = 'Pick a swatch color.';
    }
    if (!is_numeric($item['price']) || $item['price'] < 0) {
        $errors[] = 'Price has to be a number, like 12.50.';
    }
    if ($item['quantity'] < 0) {
        $errors[] = 'How many you own can not be negative.';
    }
    if ($item['image_url'] !== '' && !filter_var($item['image_url'], FILTER_VALIDATE_URL)) {
        $errors[] = 'Photo link has to be a full web address, or leave it empty.';
    }

    if (!$errors) {
        $values = [
            $item['category_id'], $item['name'], $item['description'], $item['color_name'],
            $item['color_hex'], $item['price'], $item['quantity'], $item['image_url'] ?: null,
        ];
        if ($id) {
            $stmt = db()->prepare('UPDATE items SET category_id = ?, name = ?, description = ?, color_name = ?,
                                   color_hex = ?, price = ?, quantity = ?, image_url = ? WHERE id = ?');
            $stmt->execute(array_merge($values, [$id]));
            flash('success', 'Saved changes to ' . $item['name'] . '.');
        } else {
            $stmt = db()->prepare('INSERT INTO items (category_id, name, description, color_name, color_hex, price, quantity, image_url)
                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute($values);
            flash('success', 'Added ' . $item['name'] . '.');
        }
        redirect('admin/items.php');
    }
}

$categories = db()->query('SELECT * FROM categories ORDER BY name')->fetchAll();
$pageTitle = $id ? 'Edit item' : 'Add item';
require __DIR__ . '/../../includes/header.php';
?>

<p class="crumbs"><a href="<?= e(url('admin/items.php')) ?>">Items</a></p>
<h1><?= $id ? 'Edit ' . e($item['name']) : 'Add an item' ?></h1>

<?php if ($errors): ?>
  <div class="flash flash-error"><ul><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<form method="post" class="stack-form narrow">
  <?= csrf_field() ?>
  <label>Category
    <select name="category_id" required>
      <option value="">Choose one</option>
      <?php foreach ($categories as $c): ?>
        <option value="<?= (int) $c['id'] ?>" <?= (int) $item['category_id'] === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </label>
  <label>Name <input name="name" value="<?= e($item['name']) ?>" required maxlength="120"></label>
  <label>Description <textarea name="description" rows="3" required><?= e($item['description']) ?></textarea></label>
  <div class="two-col">
    <label>Color name <input name="color_name" value="<?= e($item['color_name']) ?>" required maxlength="40"></label>
    <label>Swatch <input type="color" name="color_hex" value="<?= e($item['color_hex']) ?>"></label>
  </div>
  <div class="two-col">
    <label>Price per event <input type="number" name="price" value="<?= e((string) $item['price']) ?>" min="0" step="0.01" required></label>
    <label>How many we own <input type="number" name="quantity" value="<?= (int) $item['quantity'] ?>" min="0" required></label>
  </div>
  <label>Photo link (optional) <input type="url" name="image_url" value="<?= e((string) $item['image_url']) ?>" placeholder="https://"></label>
  <button type="submit" class="btn btn-big"><?= $id ? 'Save changes' : 'Add item' ?></button>
</form>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
