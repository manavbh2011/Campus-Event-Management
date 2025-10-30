<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Login - Campus Event System</title>
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
      <h1>Welcome Back</h1>
      <p>Please log in to access your campus events and manage your profile.</p>

      <?php if (!empty($view_message)): ?>
        <div class="notice"><?php echo htmlspecialchars($view_message); ?></div>
      <?php endif; ?>

      <?php if (!empty($view_errors)): ?>
        <div class="error" role="alert" aria-live="assertive">
          <?php foreach ($view_errors as $error): ?>
            <p><?php echo htmlspecialchars($error); ?></p>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <form class="login-form" action="/Campus-Event-Management/index.php?action=login" method="POST" novalidate>
        <input type="hidden" name="form_token" value="<?php echo htmlspecialchars($_SESSION['login_token'] ?? ''); ?>">

        <div class="form-group">
          <label for="email">Email</label>
          <input type="email" id="email" name="email"
                 value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                 placeholder="Enter your email" required>
        </div>

        <div class="form-group">
          <label for="password">Password</label>
          <input type="password" id="password" name="password"
                 placeholder="Enter your password" required>
        </div>

        <button type="submit" class="nav-btn">Login</button>
      </form>

      <p>Don't have an account? <a href="/Campus-Event-Management/index.php?action=register">Sign up here</a></p>
    </div>
  </main>

  <footer>
    <p>&copy; 2025 Campus Event Management System | Programming Languages for Web Applications</p>
  </footer>
</body>
</html>
