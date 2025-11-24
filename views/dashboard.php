<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Dashboard - Campus Event System</title>
  <link rel="stylesheet" href="static/css/style.css" />
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

  <main class="dashboard-main">
    <div class="dashboard-content">
      <h1>Welcome back, 
        <?php
          $safeName = $display_name
            ?? (isset($user['first_name'], $user['last_name']) ? trim($user['first_name'].' '.$user['last_name']) : null)
            ?? ($_SESSION['user']['email'] ?? 'User');
          echo htmlspecialchars((string)$safeName, ENT_QUOTES, 'UTF-8');
        ?>!
      </h1>
      <p>Here's what's happening with your events today.</p>

      <div class="stats-grid">
        <div class="stat-card">
          <div><?php echo count($user_events); ?></div>
          <div>Your Events</div>
        </div>
        <div class="stat-card">
          <div><?php echo count($all_events); ?></div>
          <div>Total Events</div>
        </div>
        <div class="stat-card">
          <div><?php echo $upcoming_count; ?></div>
          <div>Upcoming</div>
        </div>
      </div>

      <div class="events-section">
        <h2>All Upcoming Events</h2>
        <div id="all-events" class="event-list">
          <p>Loading events...</p>
        </div>
      </div>

      <div class="events-section">
        <h2>Events You Registered For</h2>
        <div id="registered-events" class="event-list">
          <p>You have not registered for any events yet.</p>
        </div>
      </div>

    </div>
  </main>

  <footer>
    <p>&copy; 2025 Campus Event Management System | Programming Languages for Web Applications</p>
  </footer>
  
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="static/js/dashboard.js"></script>
</body>
</html>
