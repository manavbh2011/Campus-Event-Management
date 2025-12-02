<?php
require_once __DIR__ . '/../config/database.php';

class EventManagementController {
    private PDO $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public function handleRequest($action) {
        $allowed = ['login','register','dashboard','logout','api'];
        if (!in_array($action, $allowed, true)) {
            $action = 'login';
        }

        switch ($action) {
            case 'login':
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $this->handleLogin();
                } else {
                    $this->showLoginForm();
                }
                break;

            case 'register':
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $this->handleRegistration();
                } else {
                    $this->showRegistrationForm();
                }
                break;

            case 'dashboard':
                $this->requireLogin();
                $this->showDashboard();
                break;

            case 'logout':
                $this->handleLogout();
                break;

            case 'api':
                $this->handleApiRequest();
                break;

            default:
                $this->showLoginForm();
        }
    }

    private function validateEmail($email): bool {
        return (bool)preg_match('/^[A-Za-z0-9._%+\-]+@[A-Za-z0-9.\-]+\.[A-Za-z]{2,}$/', (string)$email);
    }

    private function validatePassword($password): bool {
        return (bool)preg_match('/^(?=.*[A-Z])(?=.*\d).{8,}$/', (string)$password);
    }

    private function sanitizeInput($data): string {
        return htmlspecialchars(stripslashes(trim((string)$data)), ENT_QUOTES, 'UTF-8');
    }

    private function generateSessionToken(): string {
        return bin2hex(random_bytes(32));
    }

    private function isLoggedIn(): bool {
        return isset($_SESSION['user']) && !empty($_SESSION['user']['id']);
    }

    private function requireLogin(): void {
        if (!$this->isLoggedIn()) {
            header('Location: index.php?action=login');
            exit;
        }
    }

    private function handleLogin(): void {
        $errors = [];

        if (empty($_POST['form_token']) || !hash_equals($_SESSION['login_token'] ?? '', $_POST['form_token'])) {
            $errors[] = 'Invalid form submission.';
        }

        $email = $this->sanitizeInput($_POST['email'] ?? '');
        $password = (string)($_POST['password'] ?? '');

        if ($email === '' || !$this->validateEmail($email)) {
            $errors[] = 'Please enter a valid email.';
        }
        if ($password === '') {
            $errors[] = 'Password is required.';
        }

        if ($errors) {
            $this->showLoginForm($errors);
            return;
        }

        $stmt = $this->db->prepare('SELECT id, email, password, first_name, last_name FROM campus_users WHERE email = :email');
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($password, $user['password'])) {
            $this->showLoginForm(['Invalid email or password.']);
            return;
        }

        $this->db->prepare('UPDATE campus_users SET last_login = CURRENT_TIMESTAMP WHERE id = :id')
                 ->execute([':id' => $user['id']]);

        $_SESSION['user'] = [
            'id'         => (int)$user['id'],
            'email'      => $user['email'],
            'first_name' => $user['first_name'],
            'last_name'  => $user['last_name'],
        ];
        $_SESSION['user_id']    = (int)$user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_name']  = trim($user['first_name'].' '.$user['last_name']);

        session_regenerate_id(false);
        header('Location: index.php?action=dashboard');
        exit;
    }

    private function handleRegistration(): void {
        $errors = [];

        if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['register_token'] ?? '', $_POST['csrf_token'])) {
            $errors[] = 'Invalid form submission.';
        }

        $email     = $this->sanitizeInput($_POST['email'] ?? '');
        $password  = (string)($_POST['password'] ?? '');
        $confirm   = (string)($_POST['confirm_password'] ?? '');
        $first     = $this->sanitizeInput($_POST['first_name'] ?? '');
        $last      = $this->sanitizeInput($_POST['last_name'] ?? '');

        if ($first === '' || !preg_match('/^[\p{L}\p{M}\s\'\-\.]{2,}$/u', $first)) { $errors[] = 'First name must be 2+ letters.'; }
        if ($last  === '' || !preg_match('/^[\p{L}\p{M}\s\'\-\.]{2,}$/u', $last))  { $errors[] = 'Last name must be 2+ letters.'; }
        if ($email === '' || !$this->validateEmail($email)) { $errors[] = 'Please enter a valid email.'; }
        if (!$this->validatePassword($password)) { $errors[] = 'Password must be 8+ chars with at least 1 uppercase and 1 number.'; }
        if ($password !== $confirm) { $errors[] = 'Passwords do not match.'; }

        if ($errors) {
            $this->showRegistrationForm($errors);
            return;
        }

        $exists = $this->db->prepare('SELECT 1 FROM campus_users WHERE email = :email');
        $exists->execute([':email' => $email]);
        if ($exists->fetchColumn()) {
            $this->showRegistrationForm(['An account with that email already exists.']);
            return;
        }

        $stmt = $this->db->prepare('
            INSERT INTO campus_users (email, password, first_name, last_name)
            VALUES (:e, :p, :f, :l)
            RETURNING id
        ');
        $stmt->execute([
            ':e' => $email,
            ':p' => password_hash($password, PASSWORD_DEFAULT),
            ':f' => $first,
            ':l' => $last
        ]);
        $newId = (int)$stmt->fetchColumn();

        $_SESSION['user'] = [
            'id'         => $newId,
            'email'      => $email,
            'first_name' => $first,
            'last_name'  => $last,
        ];
        $_SESSION['user_id']    = $newId;
        $_SESSION['user_email'] = $email;
        $_SESSION['user_name']  = trim($first.' '.$last);

        session_regenerate_id(true);
        session_write_close();
        header('Location: index.php?action=dashboard');
        exit;
    }

    private function showLoginForm(array $errors = []): void {
        if (empty($_SESSION['login_token'])) {
            $_SESSION['login_token'] = $this->generateSessionToken();
        }
        $message = isset($_GET['registered']) ? 'Registration successful! Please log in.' : '';
        include __DIR__ . '/../views/login.php';
    }

    private function showRegistrationForm(array $errors = []): void {
        if (empty($_SESSION['register_token'])) {
            $_SESSION['register_token'] = $this->generateSessionToken();
        }
        $view_errors = $errors;
        include __DIR__ . '/../views/register.php';
    }

    private function showDashboard(): void {
        $uid = (int)($_SESSION['user']['id'] ?? 0);

        $mine = $this->db->prepare('
            SELECT id, title, description, event_date, location
            FROM campus_events
            WHERE created_by = :uid
            ORDER BY event_date DESC
        ');
        $mine->execute([':uid' => $uid]);
        $user_events = $mine->fetchAll(PDO::FETCH_ASSOC);

        $all = $this->db->query('
            SELECT e.id, e.title, e.description, e.event_date, e.location,
                   u.first_name, u.last_name
            FROM campus_events e
            LEFT JOIN campus_users u ON e.created_by = u.id
            ORDER BY e.event_date DESC
        ');
        $all_events = $all->fetchAll(PDO::FETCH_ASSOC);

        $upcoming_count = 0;
        $now = time();
        foreach ($all_events as $event) {
            if (strtotime($event['event_date']) > $now) {
                $upcoming_count++;
            }
        }


        $user = $_SESSION['user'] ?? []; // <-- pass $user
        $display_name = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
        if ($display_name === '') { $display_name = $user['email'] ?? 'User'; }

        include __DIR__ . '/../views/dashboard.php';
    }

    private function handleLogout(): void {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'] ?? '', $p['secure'] ?? false, $p['httponly'] ?? true);
        }
        session_destroy();
        header('Location: index.php?action=login');
        exit;
    }

    private function handleApiRequest(): void {
        header('Content-Type: application/json');
        $endpoint = $_GET['endpoint'] ?? '';

        switch ($endpoint) {
            case 'user_info':
                if ($this->isLoggedIn()) {
                    $uid = (int)$_SESSION['user']['id'];
                    $stmt = $this->db->prepare('SELECT id, email, first_name, last_name, created_at FROM campus_users WHERE id = :id');
                    $stmt->execute([':id' => $uid]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                    echo json_encode(['success' => true, 'user' => $user]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Not logged in']);
                }
                break;

            case 'events':
                $uid = $this->isLoggedIn() ? (int)$_SESSION['user']['id'] : null;

                if ($uid) {
                    $stmt = $this->db->prepare('
                        SELECT e.id,
                            e.title,
                            e.description,
                            e.event_date,
                            e.location,
                            e.capacity,
                            e.category,
                            e.status,
                            e.created_by,
                            e.created_at,
                            e.updated_at,
                            u.first_name,
                            u.last_name,
                            CASE WHEN e.created_by = :uid THEN TRUE ELSE FALSE END AS is_creator,
                            CASE WHEN r.user_id IS NOT NULL THEN TRUE ELSE FALSE END AS user_registered,
                            COALESCE(reg_count.count, 0) AS registration_count
                        FROM campus_events e
                        LEFT JOIN campus_users u
                            ON e.created_by = u.id
                        LEFT JOIN campus_event_registrations r
                            ON r.event_id = e.id AND r.user_id = :uid
                        LEFT JOIN (
                            SELECT event_id, COUNT(*) as count
                            FROM campus_event_registrations
                            GROUP BY event_id
                        ) reg_count ON e.id = reg_count.event_id
                        WHERE e.event_date > CURRENT_TIMESTAMP
                        ORDER BY e.event_date ASC
                    ');
                    $stmt->execute([':uid' => $uid]);
                } else {
                    $stmt = $this->db->query('
                        SELECT e.id,
                            e.title,
                            e.description,
                            e.event_date,
                            e.location,
                            e.capacity,
                            e.category,
                            e.status,
                            e.created_by,
                            e.created_at,
                            e.updated_at,
                            u.first_name,
                            u.last_name,
                            FALSE AS is_creator,
                            FALSE AS user_registered,
                            COALESCE(reg_count.count, 0) AS registration_count
                        FROM campus_events e
                        LEFT JOIN campus_users u
                            ON e.created_by = u.id
                        LEFT JOIN (
                            SELECT event_id, COUNT(*) as count
                            FROM campus_event_registrations
                            GROUP BY event_id
                        ) reg_count ON e.id = reg_count.event_id
                        WHERE e.event_date > CURRENT_TIMESTAMP
                        ORDER BY e.event_date ASC
                    ');
                }

                $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode(['success' => true, 'events' => $events]);
                break;
            
            case 'register_event':
                if (!$this->isLoggedIn()) {
                    http_response_code(401);
                    echo json_encode([
                        'success' => false,
                        'message' => 'You must be logged in to register for an event.'
                    ]);
                    break;
                }

                $eventId = isset($_POST['event_id']) ? (int)$_POST['event_id'] : 0;
                $uid     = (int)$_SESSION['user']['id'];

                if ($eventId <= 0) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Invalid event id.'
                    ]);
                    break;
                }

                try {
                    // Check if event is at capacity
                    $capacityStmt = $this->db->prepare('
                        SELECT e.capacity, COALESCE(COUNT(r.user_id), 0) as current_registrations
                        FROM campus_events e
                        LEFT JOIN campus_event_registrations r ON e.id = r.event_id
                        WHERE e.id = :event_id
                        GROUP BY e.id, e.capacity
                    ');
                    $capacityStmt->execute([':event_id' => $eventId]);
                    $eventInfo = $capacityStmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$eventInfo) {
                        echo json_encode([
                            'success' => false,
                            'message' => 'Event not found.'
                        ]);
                        break;
                    }
                    
                    if ($eventInfo['current_registrations'] >= $eventInfo['capacity']) {
                        echo json_encode([
                            'success' => false,
                            'message' => 'Event is at full capacity.'
                        ]);
                        break;
                    }

                    $stmt = $this->db->prepare('
                        INSERT INTO campus_event_registrations (event_id, user_id)
                        VALUES (:event_id, :user_id)
                        ON CONFLICT (event_id, user_id) DO NOTHING
                    ');
                    $stmt->execute([
                        ':event_id' => $eventId,
                        ':user_id'  => $uid,
                    ]);

                    echo json_encode([
                        'success'  => true,
                        'event_id' => $eventId,
                    ]);
                } catch (PDOException $e) {
                    http_response_code(500);
                    echo json_encode([
                        'success' => false,
                        'message' => 'Database error while registering.'
                    ]);
                }
                break;

            case 'unregister_event':
                if (!$this->isLoggedIn()) {
                    http_response_code(401);
                    echo json_encode([
                        'success' => false,
                        'message' => 'You must be logged in to unregister from an event.'
                    ]);
                    break;
                }

                $eventId = isset($_POST['event_id']) ? (int)$_POST['event_id'] : 0;
                $uid     = (int)$_SESSION['user']['id'];

                if ($eventId <= 0) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Invalid event id.'
                    ]);
                    break;
                }

                try {
                    $stmt = $this->db->prepare('
                        DELETE FROM campus_event_registrations 
                        WHERE event_id = :event_id AND user_id = :user_id
                    ');
                    $stmt->execute([
                        ':event_id' => $eventId,
                        ':user_id'  => $uid,
                    ]);

                    echo json_encode([
                        'success'  => true,
                        'event_id' => $eventId,
                    ]);
                } catch (PDOException $e) {
                    http_response_code(500);
                    echo json_encode([
                        'success' => false,
                        'message' => 'Database error while unregistering.'
                    ]);
                }
                break;

            case 'delete_event':
                if (!$this->isLoggedIn()) {
                    http_response_code(401);
                    echo json_encode([
                        'success' => false,
                        'message' => 'You must be logged in to delete an event.'
                    ]);
                    break;
                }

                $eventId = isset($_POST['event_id']) ? (int)$_POST['event_id'] : 0;
                $uid     = (int)$_SESSION['user']['id'];

                if ($eventId <= 0) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Invalid event id.'
                    ]);
                    break;
                }

                try {
                    // Check if user owns the event
                    $ownerStmt = $this->db->prepare('SELECT created_by FROM campus_events WHERE id = :event_id');
                    $ownerStmt->execute([':event_id' => $eventId]);
                    $event = $ownerStmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$event || (int)$event['created_by'] !== $uid) {
                        echo json_encode([
                            'success' => false,
                            'message' => 'You can only delete events you created.'
                        ]);
                        break;
                    }

                    // Delete registrations first
                    $this->db->prepare('DELETE FROM campus_event_registrations WHERE event_id = :event_id')
                             ->execute([':event_id' => $eventId]);
                    
                    // Delete the event
                    $this->db->prepare('DELETE FROM campus_events WHERE id = :event_id')
                             ->execute([':event_id' => $eventId]);

                    echo json_encode([
                        'success'  => true,
                        'event_id' => $eventId,
                    ]);
                } catch (PDOException $e) {
                    http_response_code(500);
                    echo json_encode([
                        'success' => false,
                        'message' => 'Database error while deleting event.'
                    ]);
                }
                break;

            default:
                echo json_encode(['success' => false, 'message' => 'Invalid endpoint']);
        }
        exit;
    }
}
