<?php
// Setup script to initialize database and create sample data
require_once 'config/database.php';

// Initialize database
initializeDatabase();

$database = new Database();
$conn = $database->getConnection();

// Create sample users
$sample_users = [
    ['admin@university.edu', 'Admin123!', 'John', 'Admin'],
    ['student1@university.edu', 'Student123!', 'Jane', 'Smith'],
    ['faculty@university.edu', 'Faculty123!', 'Dr. Robert', 'Johnson']
];

foreach ($sample_users as $user_data) {
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$user_data[0]]);
    
    if (!$stmt->fetch()) {
        $stmt = $conn->prepare("INSERT INTO users (email, password, first_name, last_name) VALUES (?, ?, ?, ?)");
        $hashed_password = password_hash($user_data[1], PASSWORD_DEFAULT);
        $stmt->execute([$user_data[0], $hashed_password, $user_data[2], $user_data[3]]);
        echo "Created user: " . $user_data[0] . "\n";
    }
}

// Create sample events
$sample_events = [
    ['Spring Career Fair', 'Annual career fair with 100+ employers', '2025-03-15 10:00:00', 'Student Union Ballroom'],
    ['Tech Talk: AI in Education', 'Guest speaker from Google discussing AI applications', '2025-02-20 14:00:00', 'Engineering Building Room 101'],
    ['Campus Cleanup Day', 'Volunteer event to beautify our campus', '2025-02-28 09:00:00', 'Main Quad']
];

$stmt = $conn->prepare("SELECT id FROM users LIMIT 1");
$stmt->execute();
$first_user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($first_user) {
    foreach ($sample_events as $event_data) {
        $stmt = $conn->prepare("SELECT id FROM events WHERE title = ?");
        $stmt->execute([$event_data[0]]);
        
        if (!$stmt->fetch()) {
            $stmt = $conn->prepare("INSERT INTO events (title, description, event_date, location, created_by) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$event_data[0], $event_data[1], $event_data[2], $event_data[3], $first_user['id']]);
            echo "Created event: " . $event_data[0] . "\n";
        }
    }
}

echo "Setup completed successfully!\n";
echo "Sample login credentials:\n";
echo "Admin: admin@university.edu / Admin123!\n";
echo "Student: student1@university.edu / Student123!\n";
echo "Faculty: faculty@university.edu / Faculty123!\n";
?>