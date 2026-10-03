<?php
require __DIR__ . '/../includes/bootstrap.php';

if (current_user()) {
    redirect('my-rentals.php');
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $email    = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    // Slow down guessing: after 5 misses in this session, wait 60 seconds
    $fails = $_SESSION['login_fails'] ?? 0;
    $lockedUntil = $_SESSION['login_locked_until'] ?? 0;

    if (time() < $lockedUntil) {
        $error = 'Too many tries. Wait a minute and try again.';
    } else {
        $stmt = db()->prepare('SELECT id, name, role, password_hash FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            unset($_SESSION['login_fails'], $_SESSION['login_locked_until']);
            login_user((int) $user['id']);
            flash('success', 'Welcome back, ' . $user['name'] . '.');

            $next = $_SESSION['after_login'] ?? url($user['role'] === 'admin' ? 'admin/index.php' : 'my-rentals.php');
            unset($_SESSION['after_login']);
            header('Location: ' . $next);
            exit;
        }

        // Same message whether the email or password was wrong,
        // so nobody can use this page to find out who has an account
        $error = 'That email and password do not match.';
        $_SESSION['login_fails'] = ++$fails;
        if ($fails >= 5) {
            $_SESSION['login_locked_until'] = time() + 60;
            $_SESSION['login_fails'] = 0;
        }
    }
}

$pageTitle = 'Log in';
require __DIR__ . '/../includes/header.php';
?>

<div class="auth">
  <h1>Log in</h1>

  <?php if ($error): ?>
    <p class="flash flash-error"><?= e($error) ?></p>
  <?php endif; ?>

  <form method="post" class="stack-form">
    <?= csrf_field() ?>
    <label>Email <input type="email" name="email" value="<?= e($email) ?>" required autocomplete="email"></label>
    <label>Password <input type="password" name="password" required autocomplete="current-password"></label>
    <button type="submit" class="btn btn-big">Log in</button>
  </form>
  <p>New here? <a href="<?= e(url('register.php')) ?>">Make an account</a></p>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
