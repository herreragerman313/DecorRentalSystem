<?php
// Every page starts with: require __DIR__ . '/../includes/bootstrap.php';

require_once __DIR__ . '/../config/config.php';

if (DEBUG) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
}

// ---------- Sessions ----------
session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Lax',
    'secure'   => !empty($_SERVER['HTTPS']),
]);
session_start();

// ---------- Database (PDO) ----------
function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }
    return $pdo;
}

// ---------- URLs ----------
// Works whether the site lives at / (AWS) or /DecorRentalSystem/public (XAMPP)
function base_url(): string
{
    static $base = null;
    if ($base === null) {
        $script = $_SERVER['SCRIPT_NAME'] ?? '';
        $dir = str_replace('\\', '/', dirname($script));
        if (substr($dir, -6) === '/admin') {
            $dir = substr($dir, 0, -6);
        }
        $base = rtrim($dir, '/');
    }
    return $base;
}

function url(string $path = ''): string
{
    return base_url() . '/' . ltrim($path, '/');
}

function redirect(string $path): void
{
    header('Location: ' . url($path));
    exit;
}

// ---------- Output helpers ----------
function e(?string $text): string
{
    return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
}

function money($amount): string
{
    return '$' . number_format((float) $amount, 2);
}

function nice_date(string $date): string
{
    return date('D, M j, Y', strtotime($date));
}

function status_label(string $status, bool $setup = false): string
{
    return [
        'pending'   => 'Waiting for confirmation',
        'confirmed' => 'Confirmed',
        'picked_up' => $setup ? 'Set up' : 'Picked up',
        'returned'  => $setup ? 'Taken down' : 'Returned',
        'cancelled' => 'Cancelled',
    ][$status] ?? $status;
}

// ---------- Flash messages ----------
function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function take_flashes(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

// ---------- CSRF protection ----------
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . csrf_token() . '">';
}

function check_csrf(): void
{
    $sent = $_POST['csrf'] ?? '';
    if (!hash_equals(csrf_token(), $sent)) {
        http_response_code(400);
        exit('Your session expired. Go back, refresh the page, and try again.');
    }
}

// ---------- Auth ----------
function current_user(): ?array
{
    static $user = false;
    if ($user === false) {
        $user = null;
        if (!empty($_SESSION['user_id'])) {
            $stmt = db()->prepare('SELECT id, name, email, phone, role FROM users WHERE id = ?');
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch() ?: null;
        }
    }
    return $user;
}

function is_admin(): bool
{
    $user = current_user();
    return $user !== null && $user['role'] === 'admin';
}

function require_login(): array
{
    $user = current_user();
    if ($user === null) {
        $_SESSION['after_login'] = $_SERVER['REQUEST_URI'] ?? url('index.php');
        flash('info', 'Log in to keep going.');
        redirect('login.php');
    }
    return $user;
}

function require_admin(): array
{
    $user = require_login();
    if ($user['role'] !== 'admin') {
        http_response_code(403);
        exit('Only admins can open this page.');
    }
    return $user;
}

function login_user(int $userId): void
{
    session_regenerate_id(true);   // stops session fixation attacks
    $_SESSION['user_id'] = $userId;
}

require_once __DIR__ . '/availability.php';

// ---------- Shared form parts ----------

// The "how do you want it" choice on item and package pages
function service_fields(float $setupFee): string
{
    ob_start(); ?>
    <fieldset class="service-choice" data-service>
      <legend>How do you want it?</legend>
      <label class="choice">
        <input type="radio" name="service" value="pickup" checked>
        <span><strong>I'll pick it up</strong>
          <small>Pick up the day before, return the day after.</small></span>
      </label>
      <label class="choice">
        <input type="radio" name="service" value="setup" data-fee="<?= e((string) $setupFee) ?>">
        <span><strong>You set it up for me</strong> <span class="fee">+<?= money($setupFee) ?></span>
          <small>Our team decorates before the event and takes it all down after.</small></span>
      </label>
      <label class="address-field" data-address>Event address (only if we set it up)
        <input type="text" name="address" maxlength="255" autocomplete="street-address" placeholder="Street, city">
      </label>
    </fieldset>
    <?php
    $user = current_user();
    if ($user && gets_loyalty_discount((int) $user['id'])): ?>
      <p class="perk">Welcome back. You save <?= (int) LOYALTY_PERCENT ?>% on this rental.</p>
    <?php elseif (!$user || $user['role'] !== 'admin'): ?>
      <p class="small">Returning customers save <?= (int) LOYALTY_PERCENT ?>% on every rental after their first one.</p>
    <?php endif;
    return ob_get_clean();
}
