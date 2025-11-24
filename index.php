<?php
/*Link to website: https://cs4640.cs.virginia.edu/vus8cb/Campus-Event-Management/*/ 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_set_cookie_params([
  'lifetime' => 0,
  'path'     => '/vus8cb/Campus-Event-Management',
  'httponly' => true,
  'samesite' => 'Lax'
]);
if (session_status() === PHP_SESSION_NONE) { session_start(); }

require_once __DIR__ . '/config/database.php';
if (!function_exists('initializeDatabase')) {
  throw new RuntimeException('initializeDatabase() not found. Is config/database.php correct?');
}

initializeDatabase();

$controllerPath = __DIR__ . '/controllers/EventManagementController.php';
if (!file_exists($controllerPath)) {
  throw new RuntimeException('Controller file missing: ' . $controllerPath);
}
require_once $controllerPath;
if (!class_exists('EventManagementController')) {
  throw new RuntimeException('EventManagementController class not found after include.');
}

$action = $_GET['action'] ?? 'landing';

if ($action === 'logout') {
  $_SESSION = [];
  if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'] ?? '', $params['secure'] ?? false, $params['httponly'] ?? true);
  }
  session_destroy();
  header('Location: index.php?action=login');
  exit;
}

if ($action === 'landing') {
  $landing = __DIR__ . '/landing.html';
  if (!file_exists($landing)) {
    throw new RuntimeException('landing.html not found at ' . $landing);
  }
  include $landing;
  exit;
}

try {
  $controller = new EventManagementController();
  $controller->handleRequest($action);
} catch (Throwable $e) {
  http_response_code(500);
  echo "<pre style='white-space:pre-wrap'>Controller error: " . htmlspecialchars($e->getMessage()) . "\n\n" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
