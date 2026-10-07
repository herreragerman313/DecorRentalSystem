<?php
// Set $pageTitle before including this file
$pageTitle = isset($pageTitle) ? $pageTitle . ' | ' . SITE_NAME : SITE_NAME;
$user = current_user();
$here = basename($_SERVER['SCRIPT_NAME'] ?? '');
$inAdmin = strpos($_SERVER['SCRIPT_NAME'] ?? '', '/admin/') !== false;

function nav_link(string $path, string $label, bool $active): string
{
    return '<a href="' . e(url($path)) . '"' . ($active ? ' aria-current="page"' : '') . '>' . e($label) . '</a>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($pageTitle) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Atkinson+Hyperlegible:wght@400;700&family=Bricolage+Grotesque:opsz,wght@12..96,500;12..96,700;12..96,800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= e(url('assets/style.css')) ?>">
</head>
<body>
<a class="skip" href="#main">Skip to content</a>
<header class="site-header">
  <div class="wrap header-inner">
    <a class="brand" href="<?= e(url('index.php')) ?>"><?= e(SITE_NAME) ?></a>
    <nav class="main-nav" aria-label="Main">
      <?= nav_link('items.php', 'Browse decor', !$inAdmin && in_array($here, ['items.php', 'item.php'])) ?>
      <?= nav_link('packages.php', 'Packages', !$inAdmin && in_array($here, ['packages.php', 'package.php'])) ?>
      <?= nav_link('shop.php', 'Shop', !$inAdmin && $here === 'shop.php') ?>
      <?php if ($user): ?>
        <?= nav_link('my-rentals.php', 'My rentals', !$inAdmin && in_array($here, ['my-rentals.php', 'rental.php'])) ?>
        <?php if ($user['role'] === 'admin'): ?>
          <?= nav_link('admin/index.php', 'Admin', $inAdmin) ?>
        <?php endif; ?>
        <form method="post" action="<?= e(url('logout.php')) ?>" class="inline">
          <?= csrf_field() ?>
          <button type="submit" class="link-button">Log out</button>
        </form>
      <?php else: ?>
        <?= nav_link('login.php', 'Log in', $here === 'login.php') ?>
        <a class="btn btn-small" href="<?= e(url('register.php')) ?>">Sign up</a>
      <?php endif; ?>
    </nav>
  </div>
</header>
<main id="main" class="wrap">
<?php foreach (take_flashes() as $f): ?>
  <div class="flash flash-<?= e($f['type']) ?>" role="status"><?= e($f['message']) ?></div>
<?php endforeach; ?>
