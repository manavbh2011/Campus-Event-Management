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
                $stmt = $this->db->query('
                    SELECT e.id, e.title, e.description, e.event_date, e.location, 
                           e.capacity, e.category, e.status, e.created_by, e.created_at, e.updated_at,
                           u.first_name, u.last_name
                    FROM campus_events e
                    LEFT JOIN campus_users u ON e.created_by = u.id
                    ORDER BY e.event_date ASC
                ');
                $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode(['success' => true, 'events' => $events]);
                break;

            default:
                echo json_encode(['success' => false, 'message' => 'Invalid endpoint']);
        }
        exit;
    }
}
