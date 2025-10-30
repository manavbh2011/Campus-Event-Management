<?php
/* ---------- Session + Cookie scope (must be first) ---------- */
session_set_cookie_params([
  'lifetime' => 0,
  'path'     => '/Campus-Event-Management', // <-- adjust if your app folder differs
  'httponly' => true,
  'samesite' => 'Lax'
]);
if (session_status() === PHP_SESSION_NONE) { session_start(); }

/* ---------- CSRF token for the register form ---------- */
if (empty($_SESSION['register_token'])) {
  $_SESSION['register_token'] = bin2hex(random_bytes(32));
}

/* ---------- Server-side handler ---------- */
require_once __DIR__ . '/config/database.php';

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // CSRF check
  $sent_token = $_POST['csrf_token'] ?? '';
  if (!hash_equals($_SESSION['register_token'] ?? '', $sent_token)) {
    $errors[] = 'Security check failed. Please refresh and try again.';
  }

  // Collect & trim inputs
  $first_name       = trim($_POST['first_name'] ?? '');
  $last_name        = trim($_POST['last_name'] ?? '');
  $email            = trim($_POST['email'] ?? '');
  $password         = (string)($_POST['password'] ?? '');
  $confirm_password = (string)($_POST['confirm_password'] ?? '');

  // Validation helpers
  $valid_name = function(string $n): bool {
    return (bool)preg_match('/^[\p{L}\p{M}\s\'\-\.]{2,}$/u', $n);
  };
  $strong_password = function(string $p): bool {
    // ≥ 8 chars, at least one lowercase, one uppercase, one digit
    return (bool)preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/', $p);
  };

  // Validate fields
  if (!$valid_name($first_name)) $errors[] = 'First name must be 2+ letters.';
  if (!$valid_name($last_name))  $errors[] = 'Last name must be 2+ letters.';
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email.';
  if (!$strong_password($password)) $errors[] = 'Password must be 8+ chars and include upper, lower, and a number.';
  if ($password !== $confirm_password) $errors[] = 'Passwords do not match.';

  if (!$errors) {
    try {
      $db  = new Database();
      $pdo = $db->getConnection();

      // Check for existing email
      $check = $pdo->prepare('SELECT 1 FROM users WHERE email = :email');
      $check->execute([':email' => $email]);
      if ($check->fetchColumn()) {
        $errors[] = 'An account with that email already exists.';
      } else {
        // Insert user and get id
        $stmt = $pdo->prepare('
          INSERT INTO users (email, password, first_name, last_name)
          VALUES (:e, :p, :f, :l)
          RETURNING id
        ');
        $stmt->execute([
          ':e' => $email,
          ':p' => password_hash($password, PASSWORD_DEFAULT),
          ':f' => $first_name,
          ':l' => $last_name
        ]);
        $newId = (int)$stmt->fetchColumn();

        // Normalize session for the rest of the app
        $_SESSION['user'] = [
          'id'         => $newId,
          'email'      => $email,
          'first_name' => $first_name,
          'last_name'  => $last_name,
        ];
        // Optional legacy keys (since your session_check showed them)
        $_SESSION['user_id']    = $newId;
        $_SESSION['user_email'] = $email;
        $_SESSION['user_name']  = trim($first_name . ' ' . $last_name);

        session_regenerate_id(true);
        session_write_close();
        header('Location: /Campus-Event-Management/profile.php');
        exit;
      }
    } catch (Throwable $e) {
      $errors[] = 'Registration error: ' . $e->getMessage();
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Register - Campus Event System</title>
  <link rel="stylesheet" href="/Campus-Event-Management/static/css/style.css" />
</head>
<body>
  <header class="navbar">
    <div class="logo">EventConnect</div>
    <nav>
      <a href="/Campus-Event-Management/index.php?action=login" class="nav-btn">Login</a>
      <a href="/Campus-Event-Management/index.php?action=register" class="nav-btn">Sign Up</a>
    </nav>
  </header>

  <main class="main-section">
    <div class="main-content">
      <h1>Create Account</h1>
      <p>Join our campus event community and start managing events.</p>

      <?php if (!empty($errors)): ?>
        <div class="error" role="alert" aria-live="assertive">
          <?php foreach ($errors as $error): ?>
            <p><?php echo htmlspecialchars($error); ?></p>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <!-- Keep your action routed through index.php if you prefer the controller -->
      <form class="login-form" action="/Campus-Event-Management/index.php?action=register" method="POST" novalidate>
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['register_token']); ?>">

        <div class="form-group">
          <label for="first_name">First Name</label>
          <input type="text" id="first_name" name="first_name"
                 value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>"
                 placeholder="Enter your first name" required>
        </div>

        <div class="form-group">
          <label for="last_name">Last Name</label>
          <input type="text" id="last_name" name="last_name"
                 value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>"
                 placeholder="Enter your last name" required>
        </div>

        <div class="form-group">
          <label for="email">Email</label>
          <input type="email" id="email" name="email"
                 value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                 placeholder="Enter your email" required>
        </div>

        <div class="form-group">
          <label for="password">Password</label>
          <input type="password" id="password" name="password"
                 placeholder="Min 8 chars, 1 uppercase, 1 number" required>
        </div>

        <div class="form-group">
          <label for="confirm_password">Confirm Password</label>
          <input type="password" id="confirm_password" name="confirm_password"
                 placeholder="Confirm your password" required>
        </div>

        <button type="submit" class="nav-btn">Create Account</button>
      </form>

      <p>Already have an account? <a href="/Campus-Event-Management/index.php?action=login">Login here</a></p>
    </div>
  </main>

  <footer>
    <p>&copy; 2025 Campus Event Management System | Programming Languages for Web Applications</p>
  </footer>
</body>
</html>
