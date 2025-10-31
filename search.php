<?php
/**
 * Event Search Page
 * Authors: Shivam Agrawal, [Partner Name]
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/Campus-Event-Management',
    'httponly' => true,
    'samesite' => 'Lax'
]);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


require_once __DIR__ . '/config/database.php';
$database = new Database();
$db = $database->getConnection();


function sanitizeInput($data) {
    if ($data === null) return '';
    return htmlspecialchars(stripslashes(trim((string)$data)), ENT_QUOTES, 'UTF-8');
}


function validateSearchQuery($query) {
    return preg_match('/^[a-zA-Z0-9\s\-\.,!?\'"]+$/', $query) === 1;
}


function validateLocation($location) {

    return preg_match('/^[a-zA-Z\s,\-]+$/', $location) === 1;
}


function buildFilterQuery($filters, &$params) {
    $conditions = [];
    
 
    if (!empty($filters['search']) && validateSearchQuery($filters['search'])) {
        $conditions[] = "(LOWER(e.title) LIKE LOWER(:search) OR LOWER(e.description) LIKE LOWER(:search))";
        $params[':search'] = '%' . $filters['search'] . '%';
    }

    if (!empty($filters['categories']) && is_array($filters['categories'])) {
        $categoryPlaceholders = [];
        foreach ($filters['categories'] as $idx => $cat) {
            $key = ':cat' . $idx;
            $categoryPlaceholders[] = $key;
            $params[$key] = sanitizeInput($cat);
        }
        if (count($categoryPlaceholders) > 0) {
            $conditions[] = "e.category IN (" . implode(',', $categoryPlaceholders) . ")";
        }
    }
    

    if (!empty($filters['location']) && validateLocation($filters['location'])) {
        $conditions[] = "LOWER(e.location) LIKE LOWER(:location)";
        $params[':location'] = '%' . $filters['location'] . '%';
    }
    
   
    if (!empty($filters['date_filter'])) {
        switch ($filters['date_filter']) {
            case 'today':
                $conditions[] = "DATE(e.event_date) = CURRENT_DATE";
                break;
            case 'tomorrow':
                $conditions[] = "DATE(e.event_date) = CURRENT_DATE + INTERVAL '1 day'";
                break;
            case 'this_week':
                $conditions[] = "e.event_date >= CURRENT_DATE AND e.event_date < CURRENT_DATE + INTERVAL '7 days'";
                break;
            case 'this_weekend':
                $conditions[] = "EXTRACT(DOW FROM e.event_date) IN (0, 6) AND e.event_date >= CURRENT_DATE";
                break;
            case 'next_week':
                $conditions[] = "e.event_date >= CURRENT_DATE + INTERVAL '7 days' AND e.event_date < CURRENT_DATE + INTERVAL '14 days'";
                break;
        }
    }
    

    return count($conditions) > 0 ? ' WHERE ' . implode(' AND ', $conditions) : '';
}


function getCategories($db) {
    $stmt = $db->query("SELECT DISTINCT category FROM events WHERE category IS NOT NULL ORDER BY category");
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function formatEventDate($dateString) {
    $date = new DateTime($dateString);
    return $date->format('M j, Y \a\t g:i A');
}

if (isset($_GET['api']) && $_GET['api'] === 'search') {
    header('Content-Type: application/json');
    

    $filters = [
        'search'      => $_GET['q'] ?? '',
        'categories'  => $_GET['categories'] ?? [],
        'location'    => $_GET['location'] ?? '',
        'date_filter' => $_GET['date'] ?? '',
        'price'       => $_GET['price'] ?? ''
    ];
    
    $params = [];
    $whereClause = buildFilterQuery($filters, $params);
    
    $sql = "
        SELECT 
            e.id, 
            e.title, 
            e.description, 
            e.event_date, 
            e.location, 
            e.category,
            e.capacity,
            COALESCE(COUNT(er.id), 0) as registration_count
        FROM events e
        LEFT JOIN event_registrations er ON e.id = er.event_id AND er.status = 'registered'
        {$whereClause}
        GROUP BY e.id
        ORDER BY e.event_date ASC
    ";
    
    try {
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($events as &$event) {
            $event['formatted_date'] = formatEventDate($event['event_date']);
            $event['spots_available'] = max(0, $event['capacity'] - $event['registration_count']);
        }
        
        echo json_encode([
            'success' => true,
            'count'   => count($events),
            'events'  => $events,
            'filters' => $filters
        ], JSON_PRETTY_PRINT);
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'error'   => 'Database error: ' . $e->getMessage()
        ]);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_preferences'])) {
    $preferences = [
        'default_location' => sanitizeInput($_POST['location'] ?? ''),
        'preferred_categories' => $_POST['categories'] ?? []
    ];
    
    setcookie(
        'search_preferences',
        json_encode($preferences),
        time() + (86400 * 30), // 30 days
        '/Campus-Event-Management',
        '',
        false,
        true 
    );
    
    $_SESSION['preference_saved'] = true;
}


$savedPreferences = [];
if (isset($_COOKIE['search_preferences'])) {
    $savedPreferences = json_decode($_COOKIE['search_preferences'], true) ?? [];
}

$searchResults = [];
$totalResults = 0;
$errors = [];
$searchPerformed = false;


if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['search'])) {
    $searchPerformed = true;
    

    $filters = [
        'search'      => sanitizeInput($_GET['q'] ?? ''),
        'categories'  => $_GET['categories'] ?? [],
        'location'    => sanitizeInput($_GET['location'] ?? ''),
        'date_filter' => sanitizeInput($_GET['date_filter'] ?? ''),
        'price'       => sanitizeInput($_GET['price'] ?? '')
    ];
    

    if (!empty($filters['search']) && !validateSearchQuery($filters['search'])) {
        $errors[] = "Search query contains invalid characters. Please use only letters, numbers, and basic punctuation.";
    }
    
    if (!empty($filters['location']) && !validateLocation($filters['location'])) {
        $errors[] = "Location contains invalid characters. Please use only letters, spaces, commas, and hyphens.";
    }
    

    if (empty($errors)) {
        $_SESSION['last_search'] = $filters;
        $_SESSION['search_count'] = ($_SESSION['search_count'] ?? 0) + 1;
        
        $params = [];
        $whereClause = buildFilterQuery($filters, $params);
        
        $sql = "
            SELECT 
                e.id, 
                e.title, 
                e.description, 
                e.event_date, 
                e.location, 
                e.category,
                e.capacity,
                e.status,
                COALESCE(COUNT(er.id), 0) as registration_count,
                u.first_name,
                u.last_name
            FROM events e
            LEFT JOIN event_registrations er ON e.id = er.event_id AND er.status = 'registered'
            LEFT JOIN users u ON e.created_by = u.id
            {$whereClause}
            GROUP BY e.id, u.first_name, u.last_name
            ORDER BY e.event_date ASC
        ";
        
        try {
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $searchResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $totalResults = count($searchResults);
        } catch (PDOException $e) {
            $errors[] = "Database error occurred. Please try again.";
        }
    }
} elseif (isset($_SESSION['last_search'])) {

    $filters = $_SESSION['last_search'];
} else {

    $filters = [
        'search'      => '',
        'categories'  => [],
        'location'    => $savedPreferences['default_location'] ?? '',
        'date_filter' => '',
        'price'       => ''
    ];
}


if (!$searchPerformed && empty($searchResults)) {
    try {
        $stmt = $db->query("
            SELECT 
                e.id, 
                e.title, 
                e.description, 
                e.event_date, 
                e.location, 
                e.category,
                e.capacity,
                e.status,
                COALESCE(COUNT(er.id), 0) as registration_count,
                u.first_name,
                u.last_name
            FROM events e
            LEFT JOIN event_registrations er ON e.id = er.event_id AND er.status = 'registered'
            LEFT JOIN users u ON e.created_by = u.id
            WHERE e.event_date >= CURRENT_DATE
            GROUP BY e.id, u.first_name, u.last_name
            ORDER BY e.event_date ASC
            LIMIT 50
        ");
        $searchResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $totalResults = count($searchResults);
    } catch (PDOException $e) {
        $errors[] = "Unable to load events.";
    }
}


$availableCategories = getCategories($db);


$isLoggedIn = isset($_SESSION['user']) && !empty($_SESSION['user']['id']);
$userName = $isLoggedIn ? ($_SESSION['user_name'] ?? 'User') : 'Guest';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="author" content="Shivam Agrawal, [Partner Name]" />
    <title>Event Search - Campus Event Management</title>
    <link rel="stylesheet" href="/Campus-Event-Management/static/css/style.css" />
    <style>
        .error-message {
            background-color: #fee;
            color: #c33;
            padding: 12px;
            border-radius: 6px;
            margin: 10px 0;
            border: 1px solid #fcc;
        }
        .success-message {
            background-color: #efe;
            color: #3c3;
            padding: 12px;
            border-radius: 6px;
            margin: 10px 0;
            border: 1px solid #cfc;
        }
        .event-card {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px;
            margin: 15px 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .event-card:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        .event-card h3 {
            color: #1d4ed8;
            margin: 0 0 10px 0;
        }
        .event-meta {
            color: #666;
            font-size: 0.9em;
            margin: 5px 0;
        }
        .category-tag {
            display: inline-block;
            background: #e3f2fd;
            color: #1976d2;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.85em;
            margin-right: 5px;
        }
        .filter-checkbox {
            display: block;
            margin: 8px 0;
        }
        .search-bar-container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .search-input {
            width: 70%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1em;
        }
        .search-btn {
            padding: 12px 24px;
            background: #1d4ed8;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1em;
            margin-left: 10px;
        }
        .search-btn:hover {
            background: #1e40af;
        }
        .filters-sidebar {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .filter-group {
            margin-bottom: 20px;
        }
        .filter-group h4 {
            margin: 0 0 10px 0;
            color: #333;
        }
        .results-count {
            color: #666;
            margin: 20px 0;
            font-size: 1.1em;
        }
    </style>
</head>
<body>
    <header class="navbar">
        <div class="logo">EventFinder</div>
        <nav>
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
    </header>

    <main class="search-main">
        <div class="search-content">
            <h1>Search Events</h1>
            
            <?php if (isset($_SESSION['preference_saved'])): ?>
                <div class="success-message">
                    Search preferences saved successfully!
                </div>
                <?php unset($_SESSION['preference_saved']); ?>
            <?php endif; ?>
            
            <?php if (!empty($errors)): ?>
                <?php foreach ($errors as $error): ?>
                    <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
                <?php endforeach; ?>
            <?php endif; ?>
            
            <!-- Search Form -->
            <div class="search-bar-container">
                <form method="GET" action="search.php">
                    <input type="hidden" name="search" value="1" />
                    <input 
                        type="text" 
                        name="q" 
                        class="search-input" 
                        placeholder="Search events by title or description..." 
                        value="<?php echo htmlspecialchars($filters['search'] ?? ''); ?>"
                    />
                    <button type="submit" class="search-btn">Search</button>
                </form>
            </div>
            
            <div class="search-container" style="display: grid; grid-template-columns: 250px 1fr; gap: 20px;">
                <!-- Filters Sidebar -->
                <div class="filters-sidebar">
                    <h3>Filters</h3>
                    <form method="GET" action="search.php" id="filterForm">
                        <input type="hidden" name="search" value="1" />
                        <input type="hidden" name="q" value="<?php echo htmlspecialchars($filters['search'] ?? ''); ?>" />
                        
                        <!-- Category Filter -->
                        <div class="filter-group">
                            <h4>Category</h4>
                            <?php foreach ($availableCategories as $category): ?>
                                <label class="filter-checkbox">
                                    <input 
                                        type="checkbox" 
                                        name="categories[]" 
                                        value="<?php echo htmlspecialchars($category); ?>"
                                        <?php echo (is_array($filters['categories']) && in_array($category, $filters['categories'])) ? 'checked' : ''; ?>
                                    />
                                    <?php echo htmlspecialchars(ucfirst($category)); ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Location Filter -->
                        <div class="filter-group">
                            <h4>Location</h4>
                            <input 
                                type="text" 
                                name="location" 
                                placeholder="Enter location" 
                                style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;"
                                value="<?php echo htmlspecialchars($filters['location'] ?? ''); ?>"
                            />
                        </div>
                        
                        <!-- Date Filter -->
                        <div class="filter-group">
                            <h4>Date</h4>
                            <label class="filter-checkbox">
                                <input type="radio" name="date_filter" value="" <?php echo empty($filters['date_filter']) ? 'checked' : ''; ?> />
                                Any time
                            </label>
                            <label class="filter-checkbox">
                                <input type="radio" name="date_filter" value="today" <?php echo ($filters['date_filter'] ?? '') === 'today' ? 'checked' : ''; ?> />
                                Today
                            </label>
                            <label class="filter-checkbox">
                                <input type="radio" name="date_filter" value="tomorrow" <?php echo ($filters['date_filter'] ?? '') === 'tomorrow' ? 'checked' : ''; ?> />
                                Tomorrow
                            </label>
                            <label class="filter-checkbox">
                                <input type="radio" name="date_filter" value="this_week" <?php echo ($filters['date_filter'] ?? '') === 'this_week' ? 'checked' : ''; ?> />
                                This Week
                            </label>
                            <label class="filter-checkbox">
                                <input type="radio" name="date_filter" value="this_weekend" <?php echo ($filters['date_filter'] ?? '') === 'this_weekend' ? 'checked' : ''; ?> />
                                This Weekend
                            </label>
                            <label class="filter-checkbox">
                                <input type="radio" name="date_filter" value="next_week" <?php echo ($filters['date_filter'] ?? '') === 'next_week' ? 'checked' : ''; ?> />
                                Next Week
                            </label>
                        </div>
                        
                        <!-- Price Filter -->
                        <div class="filter-group">
                            <h4>Price</h4>
                            <label class="filter-checkbox">
                                <input type="radio" name="price" value="" <?php echo empty($filters['price']) ? 'checked' : ''; ?> />
                                Any price
                            </label>
                            <label class="filter-checkbox">
                                <input type="radio" name="price" value="free" <?php echo ($filters['price'] ?? '') === 'free' ? 'checked' : ''; ?> />
                                Free
                            </label>
                            <label class="filter-checkbox">
                                <input type="radio" name="price" value="paid" <?php echo ($filters['price'] ?? '') === 'paid' ? 'checked' : ''; ?> />
                                Paid
                            </label>
                        </div>
                        
                        <button type="submit" class="search-btn" style="width: 100%; margin: 10px 0;">Apply Filters</button>
                    </form>
                    

                    <form method="POST" action="search.php" style="margin-top: 20px;">
                        <input type="hidden" name="location" value="<?php echo htmlspecialchars($filters['location'] ?? ''); ?>" />
                        <?php if (is_array($filters['categories'])): ?>
                            <?php foreach ($filters['categories'] as $cat): ?>
                                <input type="hidden" name="categories[]" value="<?php echo htmlspecialchars($cat); ?>" />
                            <?php endforeach; ?>
                        <?php endif; ?>
                        <button type="submit" name="save_preferences" value="1" class="search-btn" style="width: 100%; background: #059669;">
                            Save Preferences
                        </button>
                    </form>
                </div>

                <div class="search-results">
                    <div class="results-count">
                        <strong><?php echo $totalResults; ?></strong> event<?php echo $totalResults !== 1 ? 's' : ''; ?> found
                        <?php if (isset($_SESSION['search_count'])): ?>
                            <br><small>You have performed <?php echo $_SESSION['search_count']; ?> searches this session</small>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (empty($searchResults)): ?>
                        <div class="event-card">
                            <p>No events found matching your criteria. Try adjusting your filters.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($searchResults as $event): ?>
                            <div class="event-card">
                                <h3><?php echo htmlspecialchars($event['title']); ?></h3>
                                <div class="event-meta">
                                    📅 <?php echo formatEventDate($event['event_date']); ?>
                                </div>
                                <div class="event-meta">
                                    📍 <?php echo htmlspecialchars($event['location']); ?>
                                </div>
                                <?php if (!empty($event['category'])): ?>
                                    <div style="margin: 10px 0;">
                                        <span class="category-tag"><?php echo htmlspecialchars(ucfirst($event['category'])); ?></span>
                                    </div>
                                <?php endif; ?>
                                <p><?php echo htmlspecialchars($event['description']); ?></p>
                                <?php if ($event['capacity'] > 0): ?>
                                    <div class="event-meta">
                                        👥 <?php echo $event['registration_count']; ?> / <?php echo $event['capacity']; ?> registered
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($event['first_name'])): ?>
                                    <div class="event-meta">
                                        Organized by: <?php echo htmlspecialchars($event['first_name'] . ' ' . $event['last_name']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <footer>
        <p>&copy; 2025 Campus Event Management System | Programming Languages for Web Applications</p>
    </footer>
</body>
</html>
