<?php
session_start();
require_once 'config/database.php';
require_once 'controllers/EventManagementController.php';

initializeDatabase();

$action = $_GET['action'] ?? 'landing';

if ($action === 'landing') {
    include 'landing.html';
} else {
    $controller = new EventManagementController();
    $controller->handleRequest($action);
}
?>