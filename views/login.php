<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Login - Campus Event System</title>
  <link rel="stylesheet" href="static/css/style.css" />
</head>
<body>
  <header class="navbar">
    <div class="logo">EventConnect</div>
    <nav class="nav-menu">
      <a href="index.php?action=login" class="nav-btn">Login</a>
      <a href="index.php?action=register" class="nav-btn">Sign Up</a>
    </nav>
    <button class="mobile-menu-toggle" onclick="toggleMobileMenu()">☰</button>
    <div class="mobile-dropdown" id="mobileMenu">
      <a href="index.php?action=login" class="nav-btn">Login</a>
      <a href="index.php?action=register" class="nav-btn">Sign Up</a>
    </div>
  </header>

  <main class="main-section">
    <div class="main-content">
      <h1>Welcome Back</h1>
      <p>Please log in to access your campus events and manage your profile.</p>

      <?php echo $message ?? ''; ?>
      
      <div id="login-error"<?php if (!empty($errors)): ?> style="color: red; margin-bottom: 15px; display: block;"<?php else: ?> style="color: red; margin-bottom: 15px; display: none;"<?php endif; ?>>
        <?php if (!empty($errors)): ?>
          <?php foreach ($errors as $error): ?>
            <?php echo htmlspecialchars($error); ?>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <form class="login-form" action="index.php?action=login" method="POST" novalidate>
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

      <p>Don't have an account? <a href="index.php?action=register">Sign up here</a></p>
    </div>
  </main>

  <footer>
    <p>&copy; 2025 EventConnect | Programming Languages for Web Applications</p>
  </footer>
  
  <script>
  function toggleMobileMenu() {
    const menu = document.getElementById('mobileMenu');
    menu.classList.toggle('show');
  }
  </script>
  <script src="static/js/register.js"></script>
  <script src="static/js/login.js"></script>
</body>
</html>
