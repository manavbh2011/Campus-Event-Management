<?php
/**
 * profile.php — Campus Event Management (PostgreSQL)
 * Expects $_SESSION['user'] set by login (id, email, first_name, last_name)
 */

session_set_cookie_params([
  'lifetime' => 0,
  'path'     => '/Campus-Event-Management',
  'httponly' => true,
  'samesite' => 'Lax'
]);
if (session_status() === PHP_SESSION_NONE) { session_start(); }

require_once __DIR__ . '/config/database.php';

/* CSRF fallback */
if (!function_exists('csrf_field')) {
  function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) { $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); }
    return $_SESSION['csrf_token'];
  }
  function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="'.htmlspecialchars(csrf_token()).'">';
  }
  function csrf_validate(): void {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      $sent = $_POST['csrf_token'] ?? '';
      if (!hash_equals($_SESSION['csrf_token'] ?? '', $sent)) {
        http_response_code(400); die('Invalid CSRF token.');
      }
    }
  }
}

/* Access guard (absolute redirect) */
if (!isset($_SESSION['user'])) {
  header('Location: index.php?action=login');
  exit;
}

/* DB + current user */
$db  = new Database();
$pdo = $db->getConnection();
$user = $_SESSION['user'];

/* Helpers */
function profile_json($payload, int $status = 200): void {
  http_response_code($status);
  header('Content-Type: application/json');
  echo json_encode($payload);
  exit;
}
function valid_name($n): bool {
  return (bool)preg_match('/^[\p{L}\p{M}\s\'\-\.]{2,}$/u', trim($n));
}
function strong_password($p): bool {
  return (bool)preg_match('/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d).{8,}$/', $p);
}
function refresh_session_user(PDO $pdo, int $id): void {
  $stmt = $pdo->prepare('SELECT id, email, first_name, last_name FROM campus_users WHERE id=:id');
  $stmt->execute([':id' => $id]);
  $_SESSION['user'] = $stmt->fetch(PDO::FETCH_ASSOC);
}

/* JSON endpoint */
$action = $_GET['action'] ?? 'view';
if ($action === 'json') {
  profile_json(['user' => [
    'id'         => $user['id'],
    'email'      => $user['email'],
    'first_name' => $user['first_name'] ?? '',
    'last_name'  => $user['last_name'] ?? ''
  ]]);
}

/* POST handling */
$errors = [];
$success = false;
$profile_version = 'pg-p1';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_validate();

  $first_name       = trim($_POST['first_name'] ?? '');
  $last_name        = trim($_POST['last_name'] ?? '');
  $new_password     = (string)($_POST['new_password'] ?? '');
  $confirm_password = (string)($_POST['confirm_password'] ?? '');
  $v                = $_POST['profile_version'] ?? '';

  if (!valid_name($first_name)) $errors[] = 'First name must be 2+ letters.';
  if (!valid_name($last_name))  $errors[] = 'Last name must be 2+ letters.';

  if ($new_password !== '') {
    if (!strong_password($new_password))     $errors[] = 'Password must be 8+ chars with upper, lower, number.';
    if ($new_password !== $confirm_password) $errors[] = 'Passwords do not match.';
  }

  if ($v !== $profile_version) $errors[] = 'Profile version mismatch. Please refresh.';

  if (!$errors) {
    $stmt = $pdo->prepare('UPDATE campus_users SET first_name=:fn, last_name=:ln WHERE id=:id');
    $ok1  = $stmt->execute([':fn'=>$first_name, ':ln'=>$last_name, ':id'=>$user['id']]);

    $ok2 = true;
    if ($new_password !== '') {
      $hash  = password_hash($new_password, PASSWORD_DEFAULT);
      $stmt2 = $pdo->prepare('UPDATE campus_users SET password=:pw WHERE id=:id');
      $ok2   = $stmt2->execute([':pw'=>$hash, ':id'=>$user['id']]);
    }

    if ($ok1 && $ok2) {
      $success = true;
      refresh_session_user($pdo, (int)$user['id']);
      $user = $_SESSION['user'];
    } else {
      $errors[] = 'Database error updating profile.';
    }
  }
}

$page_title = 'Profile';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($page_title) ?> | EventConnect</title>
  <link rel="stylesheet" href="static/css/style.css">
</head>
<body>
<header class="navbar">
  <div class="logo">EventConnect</div>
  <nav>
    <a href="index.php?action=dashboard" class="nav-btn">Dashboard</a>
    <a href="search.php" class="nav-btn">Find Events</a>
    <a href="create_event.php" class="nav-btn">Create Event</a>
    <a href="profile.php" class="nav-btn">Profile</a>
    <a href="index.php?action=logout" class="nav-btn">Logout</a>
  </nav>
</header>

<main class="profile-main" role="main">
  <div class="profile-content">
    <div class="profile-card" role="region" aria-labelledby="profileHeading">
      <h1 id="profileHeading"><?= htmlspecialchars(($user['first_name'] ?? '').' '.($user['last_name'] ?? '')) ?></h1>
      <p class="muted">Email: <strong><?= htmlspecialchars($user['email']) ?></strong></p>

      <?php if ($success): ?><div class="notice success" role="status">Profile updated.</div><?php endif; ?>
      <?php if ($errors): ?>
        <div class="notice error" role="alert" aria-live="assertive">
          <ul><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
        </div>
      <?php endif; ?>

      <form method="post" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="profile_version" value="<?= htmlspecialchars($profile_version) ?>">

        <h3>Personal Information</h3>
        <div class="info-section">
          <div class="form-group">
            <label for="first_name">First Name <span aria-hidden="true">*</span></label>
            <input id="first_name" name="first_name" type="text" required value="<?= htmlspecialchars($user['first_name'] ?? '') ?>"/>
          </div>
          <div class="form-group">
            <label for="last_name">Last Name <span aria-hidden="true">*</span></label>
            <input id="last_name" name="last_name" type="text" required value="<?= htmlspecialchars($user['last_name'] ?? '') ?>"/>
          </div>
          <div class="form-group">
            <label for="email">Email</label>
            <input id="email" type="email" value="<?= htmlspecialchars($user['email']) ?>" readonly/>
          </div>
        </div>

        <h3>Change Password (optional)</h3>
        <div class="form-row">
          <div class="form-group">
            <label for="new_password">New Password</label>
            <input id="new_password" name="new_password" type="password" autocomplete="new-password" />
          </div>
          <div class="form-group">
            <label for="confirm_password">Confirm Password</label>
            <input id="confirm_password" name="confirm_password" type="password" autocomplete="new-password" />
          </div>
        </div>
        <small>Password rule: at least 8 chars, include upper, lower, and number.</small>

        <div class="actions">
          <button type="submit">Update Profile</button>
          <a class="nav-btn" href="profile.php?action=json" aria-label="View profile as JSON">View JSON</a>
        </div>
      </form>
    </div>
  </div>
</main>

<script src="static/js/profile.js"></script>
</body>
</html>
