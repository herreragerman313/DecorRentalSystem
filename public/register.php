<?php
require __DIR__ . '/../includes/bootstrap.php';

if (current_user()) {
    redirect('my-rentals.php');
}

$errors = [];
$name = $email = $phone = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $name     = trim($_POST['name'] ?? '');
    $email    = strtolower(trim($_POST['email'] ?? ''));
    $phone    = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm'] ?? '';

    if ($name === '' || strlen($name) > 100) {
        $errors[] = 'Enter your name.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Enter a valid email address.';
    }
    if (strlen($password) < 8) {
        $errors[] = 'Password needs at least 8 characters.';
    }
    if ($password !== $confirm) {
        $errors[] = 'The two passwords do not match.';
    }

    if (!$errors) {
        $stmt = db()->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = 'That email already has an account. Log in instead.';
        }
    }

    if (!$errors) {
        $stmt = db()->prepare('INSERT INTO users (name, email, phone, password_hash) VALUES (?, ?, ?, ?)');
        $stmt->execute([$name, $email, $phone ?: null, password_hash($password, PASSWORD_DEFAULT)]);
        login_user((int) db()->lastInsertId());
        flash('success', 'Account made. Welcome, ' . $name . '.');

        $next = $_SESSION['after_login'] ?? url('items.php');
        unset($_SESSION['after_login']);
        header('Location: ' . $next);
        exit;
    }
}

$pageTitle = 'Sign up';
require __DIR__ . '/../includes/header.php';
?>

<div class="auth">
  <h1>Make an account</h1>
  <p class="muted">You need one to request a rental and track your pickup and return.</p>

  <?php if ($errors): ?>
    <div class="flash flash-error"><ul><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
  <?php endif; ?>

  <form method="post" class="stack-form">
    <?= csrf_field() ?>
    <label>Name <input name="name" value="<?= e($name) ?>" required maxlength="100" autocomplete="name"></label>
    <label>Email <input type="email" name="email" value="<?= e($email) ?>" required autocomplete="email"></label>
    <label>Phone (optional) <input type="tel" name="phone" value="<?= e($phone) ?>" autocomplete="tel"></label>
    <label>Password <input type="password" name="password" required minlength="8" autocomplete="new-password"></label>
    <label>Type it again <input type="password" name="confirm" required minlength="8" autocomplete="new-password"></label>
    <button type="submit" class="btn btn-big">Make account</button>
  </form>
  <p>Already have one? <a href="<?= e(url('login.php')) ?>">Log in</a></p>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
