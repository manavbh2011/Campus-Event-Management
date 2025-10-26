<?php
class EventManagementController {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    public function handleRequest($action) {
        $allowed_actions = ['login', 'register', 'dashboard', 'logout', 'api'];
        
        if (!in_array($action, $allowed_actions)) {
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
    
    private function validateEmail($email) {
        $pattern = '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/';
        return preg_match($pattern, $email);
    }
    
    private function validatePassword($password) {
        $pattern = '/^(?=.*[A-Z])(?=.*\d)[A-Za-z\d@$!%*?&]{8,}$/';
        return preg_match($pattern, $password);
    }
    
    private function sanitizeInput($data) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }
    
    private function generateSessionToken() {
        return bin2hex(random_bytes(32));
    }
    
    private function isLoggedIn() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
    
    private function requireLogin() {
        if (!$this->isLoggedIn()) {
            header('Location: index.php?action=login');
            exit();
        }
    }
    
    private function handleLogin() {
        $errors = [];
        
        if (empty($_POST['form_token']) || $_POST['form_token'] !== $_SESSION['login_token']) {
            $errors[] = "Invalid form submission";
        }
        
        if (empty($_POST['email'])) {
            $errors[] = "Email is required";
        } elseif (!$this->validateEmail($_POST['email'])) {
            $errors[] = "Invalid email format";
        }
        
        if (empty($_POST['password'])) {
            $errors[] = "Password is required";
        }
        
        if (empty($errors)) {
            $email = $this->sanitizeInput($_POST['email']);
            $password = $_POST['password'];
            
            $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password'])) {
                $stmt = $this->db->prepare("UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = ?");
                $stmt->execute([$user['id']]);
                
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
                

                
                header('Location: index.php?action=dashboard');
                exit();
            } else {
                $errors[] = "Invalid email or password";
            }
        }
        
        $this->showLoginForm($errors);
    }
    
    private function handleRegistration() {
        $errors = [];
        
        // Validate form token
        if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['register_token']) {
            $errors[] = "Invalid form submission";
        }
        
        $required_fields = ['email', 'password', 'first_name', 'last_name'];
        
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                $errors[] = ucfirst(str_replace('_', ' ', $field)) . " is required";
            }
        }
        
        if (!empty($_POST['email']) && !$this->validateEmail($_POST['email'])) {
            $errors[] = "Invalid email format";
        }
        
        if (!empty($_POST['password']) && !$this->validatePassword($_POST['password'])) {
            $errors[] = "Password must be at least 8 characters with 1 uppercase letter and 1 number";
        }
        
        if ($_POST['password'] !== $_POST['confirm_password']) {
            $errors[] = "Passwords do not match";
        }
        
        if (empty($errors)) {
            $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$this->sanitizeInput($_POST['email'])]);
            
            if ($stmt->fetch()) {
                $errors[] = "Email already registered";
            } else {
                $stmt = $this->db->prepare("INSERT INTO users (email, password, first_name, last_name) VALUES (?, ?, ?, ?)");
                $hashed_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                
                if ($stmt->execute([
                    $this->sanitizeInput($_POST['email']),
                    $hashed_password,
                    $this->sanitizeInput($_POST['first_name']),
                    $this->sanitizeInput($_POST['last_name'])
                ])) {
                    header('Location: index.php?action=login&registered=1');
                    exit();
                } else {
                    $errors[] = "Registration failed. Please try again.";
                }
            }
        }
        
        $this->showRegistrationForm($errors);
    }
    
    private function showLoginForm($errors = []) {
        $message = '';
        if (isset($_GET['registered'])) {
            $message = '<div class="success">Registration successful! Please log in.</div>';
        }
        $_SESSION['login_token'] = $this->generateSessionToken();
        include 'views/login.php';
    }
    
    private function showRegistrationForm($errors = []) {
        $_SESSION['register_token'] = $this->generateSessionToken();
        include 'views/register.php';
    }
    
    private function showDashboard() {
        $stmt = $this->db->prepare("SELECT * FROM events WHERE created_by = ? ORDER BY event_date DESC");
        $stmt->execute([$_SESSION['user_id']]);
        $user_events = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $stmt = $this->db->prepare("SELECT e.*, u.first_name, u.last_name FROM events e JOIN users u ON e.created_by = u.id ORDER BY e.event_date ASC");
        $stmt->execute();
        $all_events = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        include 'views/dashboard.php';
    }
    
    private function handleLogout() {
        session_destroy();
        header('Location: index.php?action=login');
        exit();
    }
    
    private function handleApiRequest() {
        header('Content-Type: application/json');
        
        $endpoint = $_GET['endpoint'] ?? '';
        
        switch ($endpoint) {
            case 'user_info':
                if ($this->isLoggedIn()) {
                    $stmt = $this->db->prepare("SELECT id, email, first_name, last_name, created_at FROM users WHERE id = ?");
                    $stmt->execute([$_SESSION['user_id']]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    echo json_encode(['success' => true, 'user' => $user]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Not logged in']);
                }
                break;
                
            case 'events':
                $stmt = $this->db->prepare("SELECT e.*, u.first_name, u.last_name FROM events e JOIN users u ON e.created_by = u.id ORDER BY e.event_date ASC");
                $stmt->execute();
                $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo json_encode(['success' => true, 'events' => $events]);
                break;
                
            default:
                echo json_encode(['success' => false, 'message' => 'Invalid endpoint']);
        }
        exit();
    }
}
?>