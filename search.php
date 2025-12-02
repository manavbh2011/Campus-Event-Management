<?php
session_start();
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

$errors = [];
$searchResults = [];
$totalResults = 0;
$filters = [];
$currentUserId = $_SESSION['user_id'] ?? null;

if (isset($_GET['search']) && !empty($_GET['q'])) {
    setcookie('last_search', $_GET['q'], time() + (7 * 24 * 60 * 60), '/');
}

if (isset($_GET['search'])) {
    $filters['search']   = $_GET['q'] ?? '';
    $filters['location'] = $_GET['location'] ?? '';

    try {
        $params = [];
        $sql = "SELECT e.*, u.first_name, u.last_name";

        if ($currentUserId) {
            $sql .= ", r.id AS registration_id";
        }

        $sql .= " FROM campus_events e
                  LEFT JOIN campus_users u
                         ON e.created_by = u.id";

        if ($currentUserId) {
            $sql .= " LEFT JOIN campus_event_registrations r
                             ON r.event_id = e.id AND r.user_id = :uid";
            $params[':uid'] = (int)$currentUserId;
        }

        $sql .= " WHERE e.event_date >= CURRENT_DATE";

        if (!empty($filters['search'])) {
            $sql .= " AND (e.title ILIKE :search OR e.description ILIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        if (!empty($filters['location'])) {
            $sql .= " AND e.location ILIKE :location";
            $params[':location'] = '%' . $filters['location'] . '%';
        }

        $sql .= " ORDER BY e.event_date ASC LIMIT 50";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $searchResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $totalResults = count($searchResults);
    } catch (PDOException $e) {
        $errors[] = "Unable to load events.";
    }
}

$isLoggedIn = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Event Search - Campus Event Management</title>
    <link rel="stylesheet" href="static/css/style.css" />
</head>
<body>
    <header class="navbar">
        <div class="logo">EventConnect</div>
        <nav class="nav-menu">
            <?php if ($isLoggedIn): ?>
                <a href="index.php?action=dashboard" class="nav-btn">Dashboard</a>
                <a href="search.php" class="nav-btn">Find Events</a>
                <a href="create_event.php" class="nav-btn">Create Event</a>
                <a href="profile.php" class="nav-btn">Profile</a>
                <a href="index.php?action=logout" class="nav-btn">Logout</a>
            <?php else: ?>
                <a href="index.php?action=login" class="nav-btn">Login</a>
                <a href="index.php?action=register" class="nav-btn">Register</a>
            <?php endif; ?>
        </nav>
        <button class="mobile-menu-toggle" onclick="toggleMobileMenu()">☰</button>
        <div class="mobile-dropdown" id="mobileMenu">
            <?php if ($isLoggedIn): ?>
                <a href="index.php?action=dashboard" class="nav-btn">Dashboard</a>
                <a href="search.php" class="nav-btn">Find Events</a>
                <a href="create_event.php" class="nav-btn">Create Event</a>
                <a href="profile.php" class="nav-btn">Profile</a>
                <a href="index.php?action=logout" class="nav-btn">Logout</a>
            <?php else: ?>
                <a href="index.php?action=login" class="nav-btn">Login</a>
                <a href="index.php?action=register" class="nav-btn">Register</a>
            <?php endif; ?>
        </div>
    </header>

    <main class="search-main">
        <div class="search-content">
            <h1>Search Events</h1>

            <?php if (!empty($errors)): ?>
                <?php foreach ($errors as $error): ?>
                    <div style="background-color:#fee;color:#c33;padding:12px;border-radius:6px;margin:10px 0;">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <div class="search-container">
                <div class="filters-sidebar">
                    <h3>Search Events</h3>
                    <form method="GET" action="search.php">
                        <input type="hidden" name="search" value="1" />

                        <div class="filter-group">
                            <h4>Search</h4>
                            <input
                                type="text"
                                name="q"
                                placeholder="Search events..."
                                value="<?php echo htmlspecialchars($filters['search'] ?? ($_COOKIE['last_search'] ?? '')); ?>"
                                style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px;margin-bottom:10px;"
                            />
                        </div>

                        <div class="filter-group">
                            <h4>Location</h4>
                            <input
                                type="text"
                                name="location"
                                placeholder="Enter location"
                                value="<?php echo htmlspecialchars($filters['location'] ?? ''); ?>"
                                style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px;margin-bottom:10px;"
                            />
                        </div>

                        <button
                            type="submit"
                            style="padding:10px 16px;background-color:#2563eb;color:white;border:none;border-radius:4px;cursor:pointer;"
                        >
                            Search
                        </button>
                    </form>
                </div>

                <div class="search-results">
                    <?php if (isset($_GET['search'])): ?>
                        <h2>Events (<?php echo $totalResults; ?> results)</h2>

                        <div class="event-list">
                            <?php if (empty($searchResults)): ?>
                                <div class="event-item">
                                    <div>
                                        <h3>No events found</h3>
                                        <p>Try adjusting your search criteria.</p>
                                    </div>
                                </div>
                            <?php else: ?>
                                <?php foreach ($searchResults as $event): ?>
                                    <div class="event-card">
                                        <h3><?php echo htmlspecialchars($event['title']); ?></h3>
                                        <p>
                                            <strong>Date:</strong>
                                            <?php echo date('M j, Y g:i A', strtotime($event['event_date'])); ?>
                                        </p>
                                        <p>
                                            <strong>Location:</strong>
                                            <?php echo htmlspecialchars($event['location']); ?>
                                        </p>

                                        <?php if (!empty($event['first_name']) || !empty($event['last_name'])): ?>
                                            <p>
                                                <strong>Organizer:</strong>
                                                <?php echo htmlspecialchars(trim($event['first_name'] . ' ' . $event['last_name'])); ?>
                                            </p>
                                        <?php endif; ?>

                                        <p>
                                            <?php echo htmlspecialchars(substr($event['description'], 0, 150)); ?>...
                                        </p>

                                        <?php if ($isLoggedIn): ?>
                                            <div class="event-actions">
                                                <?php if (!empty($event['registration_id'])): ?>
                                                    <span class="badge-registered">You are registered</span>
                                                <?php elseif ((int)$event['created_by'] === (int)$_SESSION['user_id']): ?>
                                                    <span class="badge-owner">You created this event</span>
                                                <?php else: ?>
                                                    <button
                                                        type="button"
                                                        class="btn-register"
                                                        data-event-id="<?php echo (int)$event['id']; ?>"
                                                    >
                                                        Register
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        <?php else: ?>
                                            <p><a href="index.php?action=login">Log in to register</a></p>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <h2>Search for Events</h2>
                        <p>Use the search form to find events by title, description, or location.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <footer>
        <p>&copy; 2025 Campus Event Management System</p>
    </footer>

    <script>
    function toggleMobileMenu() {
        const menu = document.getElementById('mobileMenu');
        menu.classList.toggle('show');
    }
    </script>
    <script src="static/js/search.js"></script>
</body>
</html>
