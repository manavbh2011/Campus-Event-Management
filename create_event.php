<?php
/**
 * create_event.php — Campus Event Management (PostgreSQL version)
 * Uses your provided config/database.php and schema:
 *   users(id, email, password, first_name, last_name, ...)
 *   events(id, title, description, event_date TIMESTAMP, location, created_by, ...)
 *
 * Front controller (?action=...): supports JSON preview.
 * Server-side validation (regex), $_GET/$_POST, $_SESSION, cookie + hidden field.
 */


if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (empty($_SESSION['login_token'])) {
    $_SESSION['login_token'] = bin2hex(random_bytes(32));
}

require_once __DIR__ . '/config/database.php';

// Optional CSRF helpers (fallback if lib/csrf.php not present)
if (!function_exists('csrf_field')) {
  function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) { $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); }
    return $_SESSION['csrf_token'];
  }
  function csrf_field(): string { return '<input type="hidden" name="csrf_token" value="'.htmlspecialchars(csrf_token()).'">'; }
  function csrf_validate(): void {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      $sent = $_POST['csrf_token'] ?? '';
      if (!hash_equals($_SESSION['csrf_token'] ?? '', $sent)) {
        http_response_code(400); die('Invalid CSRF token.');
      }
    }
  }
}

$db = new Database();
$pdo = $db->getConnection();

/* ---------- User-defined helpers ---------- */
function respond_json(array $payload, int $status = 200): void {
  http_response_code($status);
  header('Content-Type: application/json');
  echo json_encode($payload);
  exit;
}
function s(string $v): string { return trim($v); }
function validate_date_iso(string $date): bool {
  return (bool)preg_match('/^\d{4}-\d{2}-\d{2}$/', $date);
}
function validate_time_24h(string $time): bool {
  return (bool)preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $time);
}
function combine_ts(string $date, string $time): string {
  return $date . ' ' . $time . ':00';
}
function insert_event(PDO $pdo, array $data): array {
  $stmt = $pdo->prepare('
    INSERT INTO campus_events (title, description, event_date, location, capacity, category, created_by)
    VALUES (:title, :description, :event_date, :location, :capacity, :category, :created_by)
  ');
  $ok = $stmt->execute([
    ':title' => $data['title'],
    ':description' => $data['description'],
    ':event_date' => $data['event_ts'],
    ':location' => $data['location'],
    ':capacity' => $data['capacity'],
    ':category' => $data['category'],
    ':created_by' => $_SESSION['user']['id'] ?? null
  ]);
  return [$ok, $ok ? null : 'Database insert failed.'];
}

/* ---------- Front controller ---------- */
$action = $_GET['action'] ?? 'view';

if ($action === 'preview') {
  if ($_SERVER['REQUEST_METHOD'] !== 'POST') respond_json(['error' => 'POST required'], 405);
  $title = s($_POST['title'] ?? '');
  $description = s($_POST['description'] ?? '');
  $date = $_POST['date'] ?? '';
  $time = $_POST['time'] ?? '';
  $location = s($_POST['location'] ?? '');

  $errors = [];
  if ($title === '' || mb_strlen($title) < 3) $errors['title'] = 'Title must be at least 3 characters.';
  if ($description === '') $errors['description'] = 'Description is required.';
  if (!validate_date_iso($date)) $errors['date'] = 'Use YYYY-MM-DD.';
  if (!validate_time_24h($time)) $errors['time'] = 'Use 24h HH:MM.';
  if ($location === '') $errors['location'] = 'Location is required.';

  if ($errors) respond_json(['ok' => false, 'errors' => $errors]);
  $event_ts = combine_ts($date, $time);
  respond_json(['ok' => true, 'preview' => [
    'title' => $title,
    'description' => $description,
    'event_date' => $event_ts,
    'location' => $location
  ]]);
}

/* ---------- Render + standard POST submission ---------- */
$errors = [];
$success = false;
$form_version = 'pg-v1';

// Defaults with cookie for last_location
$defaults = [
  'title' => '',
  'description' => '',
  'date' => '',
  'time' => '',
  'location' => $_GET['location'] ?? ($_COOKIE['last_location'] ?? ''),
  'capacity' => '0',
  'category' => 'general',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_validate();

  $payload = [
    'title' => s($_POST['title'] ?? ''),
    'description' => s($_POST['description'] ?? ''),
    'date' => $_POST['date'] ?? '',
    'time' => $_POST['time'] ?? '',
    'location' => s($_POST['location'] ?? ''),
    'capacity' => (int)($_POST['capacity'] ?? 0),
    'category' => s($_POST['category'] ?? 'general'),
    'version' => $_POST['form_version'] ?? ''
  ];

  if ($payload['title'] === '' || mb_strlen($payload['title']) < 3) $errors[] = 'Title must be at least 3 characters.';
  if ($payload['description'] === '') $errors[] = 'Description is required.';
  if (!validate_date_iso($payload['date'])) $errors[] = 'Invalid date (YYYY-MM-DD).';
  if (!validate_time_24h($payload['time'])) $errors[] = 'Invalid time (HH:MM in 24h).';
  if ($payload['location'] === '') $errors[] = 'Location is required.';
  if ($payload['version'] !== $form_version) $errors[] = 'Form version mismatch. Please refresh.';

  if (!$errors) {
    $payload['event_ts'] = combine_ts($payload['date'], $payload['time']);
    [$ok, $err] = insert_event($pdo, $payload);
    if ($ok) {
      $success = true;
      setcookie('last_location', $payload['location'], time() + 60*60*24*30, '/');
      $defaults = array_merge($defaults, ['title'=>'','description'=>'','date'=>'','time'=>'']);
    } else {
      $errors[] = $err ?? 'Unknown DB error.';
    }
  } else {
    $defaults = array_merge($defaults, $payload);
  }
}

$page_title = 'Create Event';
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

<main class="create-main" role="main">
  <div class="create-content">
    <div class="create-card" role="region" aria-labelledby="createHeading">
      <h1 id="createHeading">Create New Event</h1>
      <p>Post events for students to discover and join.</p>

      <?php if ($success): ?>
        <div class="notice success" role="status">Event created! You can create another below.</div>
      <?php endif; ?>

      <?php if ($errors): ?>
        <div class="notice error" role="alert" aria-live="assertive">
          <ul><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
        </div>
      <?php endif; ?>

      <form class="create-form" method="post" novalidate aria-describedby="reqnote">
        <?= csrf_field() ?>
        <input type="hidden" name="form_version" value="<?= htmlspecialchars($form_version) ?>">

        <div class="form-group">
          <label for="title">Event Title <span aria-hidden="true">*</span></label>
          <input id="title" name="title" type="text" required value="<?= htmlspecialchars($defaults['title']) ?>" />
        </div>

        <div class="form-group">
          <label for="description">Description <span aria-hidden="true">*</span></label>
          <textarea id="description" name="description" required><?= htmlspecialchars($defaults['description']) ?></textarea>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label for="date">Date <span aria-hidden="true">*</span></label>
            <input id="date" name="date" type="date" required value="<?= htmlspecialchars($defaults['date']) ?>" />
          </div>
          <div class="form-group">
            <label for="time">Time <span aria-hidden="true">*</span></label>
            <input id="time" name="time" type="time" required value="<?= htmlspecialchars($defaults['time']) ?>" />
          </div>
        </div>

        <div class="form-group">
          <label for="location">Location <span aria-hidden="true">*</span></label>
          <input id="location" name="location" type="text" required value="<?= htmlspecialchars($defaults['location']) ?>" />
          <small>We remember your last location choice (cookie).</small>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label for="capacity">Capacity</label>
            <input id="capacity" name="capacity" type="number" min="0" value="<?= htmlspecialchars($defaults['capacity'] ?? '0') ?>" />
          </div>
          <div class="form-group">
            <label for="category">Category</label>
            <select id="category" name="category">
              <option value="general" <?= ($defaults['category'] ?? 'general') === 'general' ? 'selected' : '' ?>>General</option>
              <option value="academic" <?= ($defaults['category'] ?? '') === 'academic' ? 'selected' : '' ?>>Academic</option>
              <option value="social" <?= ($defaults['category'] ?? '') === 'social' ? 'selected' : '' ?>>Social</option>
              <option value="sports" <?= ($defaults['category'] ?? '') === 'sports' ? 'selected' : '' ?>>Sports</option>
              <option value="cultural" <?= ($defaults['category'] ?? '') === 'cultural' ? 'selected' : '' ?>>Cultural</option>
              <option value="career" <?= ($defaults['category'] ?? '') === 'career' ? 'selected' : '' ?>>Career</option>
            </select>
          </div>
        </div>

        <div class="actions">
          <button type="submit">Create Event</button>
          <button type="button" id="btn-preview" aria-describedby="previewHelp">Preview JSON</button>
        </div>
      </form>

      <pre id="previewBox" aria-live="polite" style="white-space:pre-wrap;"></pre>
    </div>
  </div>
</main>

<script>
async function previewJSON(){
  const form = document.querySelector('.create-form');
  const fd = new FormData(form);
  const res = await fetch('create_event.php?action=preview', { method: 'POST', body: fd });
  const data = await res.json();
  document.getElementById('previewBox').textContent = JSON.stringify(data, null, 2);
}
document.getElementById('btn-preview').addEventListener('click', previewJSON);
</script>
</body>
</html>
