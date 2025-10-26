<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Register - Campus Event System</title>
  <link rel="stylesheet" href="static/css/style.css" />
</head>
<body>
  <header class="navbar">
    <div class="logo">EventConnect</div>
    <nav>
      <a href="index.php?action=login" class="nav-btn">Login</a>
      <a href="index.php?action=register" class="nav-btn">Sign Up</a>
    </nav>
  </header>

  <main class="main-section">
    <div class="main-content">
      <h1>Create Account</h1>
      <p>Join our campus event community and start managing events.</p>

      <?php if (!empty($errors)): ?>
        <div class="error">
          <?php foreach ($errors as $error): ?>
            <p><?php echo htmlspecialchars($error); ?></p>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <form class="login-form" action="index.php?action=register" method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['register_token']; ?>">
        
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

      <p>Already have an account? <a href="index.php?action=login">Login here</a></p>
    </div>
  </main>

  <footer>
    <p>&copy; 2025 Campus Event Management System | Programming Languages for Web Applications</p>
  </footer>
</body>
</html>