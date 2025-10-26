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
      <a href="static/pages/search.html" class="nav-btn">Find Events</a>
      <a href="static/pages/create_event.html" class="nav-btn">Create Event</a>
      <a href="static/pages/profile.html" class="nav-btn">Profile</a>
      <a href="index.php?action=logout" class="nav-btn">Logout</a>
    </nav>
  </header>

  <main class="dashboard-main">
    <div class="dashboard-content">
      <h1>Welcome back, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</h1>
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
          <div><?php echo count(array_filter($all_events, function($e) { return strtotime($e['event_date']) > time(); })); ?></div>
          <div>Upcoming</div>
        </div>
      </div>

      <div class="events-section">
        <h2>Your Events</h2>
        <div class="event-list">
          <?php if (empty($user_events)): ?>
            <div class="event-item">
              <div>
                <h3>No events created yet</h3>
                <p>Create your first event to get started!</p>
              </div>
            </div>
          <?php else: ?>
            <?php foreach ($user_events as $event): ?>
              <div class="event-item">
                <div>
                  <h3><?php echo htmlspecialchars($event['title']); ?></h3>
                  <p><?php echo date('M j, Y g:i A', strtotime($event['event_date'])); ?> - <?php echo htmlspecialchars($event['location']); ?></p>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>

      <div class="events-section">
        <h2>All Campus Events</h2>
        <div class="event-list">
          <?php foreach (array_slice($all_events, 0, 6) as $event): ?>
            <div class="event-item">
              <div>
                <h3><?php echo htmlspecialchars($event['title']); ?></h3>
                <p><?php echo date('M j, Y g:i A', strtotime($event['event_date'])); ?> - <?php echo htmlspecialchars($event['location']); ?> - by <?php echo htmlspecialchars($event['first_name'] . ' ' . $event['last_name']); ?></p>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </main>

  <footer>
    <p>&copy; 2025 Campus Event Management System | Programming Languages for Web Applications</p>
  </footer>


</body>
</html>