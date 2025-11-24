<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'config/database.php';

try {
    initializeDatabase();
    echo "Database schema initialized successfully.<br>";
} catch (Throwable $e) {
    echo "Setup failed: " . htmlspecialchars($e->getMessage());
    exit;
}

/* Below is code for filling the tables with sample data (used for development) */ 

// $database = new Database();
// $conn = $database->getConnection();


// try {
//     $conn->exec("ALTER TABLE events ADD COLUMN IF NOT EXISTS category VARCHAR(100) DEFAULT 'general'");
//     $conn->exec("ALTER TABLE events ADD COLUMN IF NOT EXISTS capacity INTEGER DEFAULT 50");
//     $conn->exec("ALTER TABLE events ADD COLUMN IF NOT EXISTS status VARCHAR(50) DEFAULT 'active'");
//     $conn->exec("ALTER TABLE events ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
// } catch (PDOException $e) {

// }

// // Create sample users
// $sample_users = [
//     ['admin@university.edu', 'Admin123!', 'John', 'Admin'],
//     ['student1@university.edu', 'Student123!', 'Jane', 'Smith'],
//     ['faculty@university.edu', 'Faculty123!', 'Dr. Robert', 'Johnson']
// ];

// foreach ($sample_users as $user_data) {
//     $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
//     $stmt->execute([$user_data[0]]);
    
//     if (!$stmt->fetch()) {
//         $stmt = $conn->prepare("INSERT INTO users (email, password, first_name, last_name) VALUES (?, ?, ?, ?)");
//         $hashed_password = password_hash($user_data[1], PASSWORD_DEFAULT);
//         $stmt->execute([$user_data[0], $hashed_password, $user_data[2], $user_data[3]]);
//         echo "Created user: " . $user_data[0] . "<br>\n";
//     }
// }

// // Create sample events with categories
// $sample_events = [
//     ['Spring Career Fair', 'Annual career fair with 100+ employers', '2025-03-15 10:00:00', 'Student Union Ballroom', 'career', 200],
//     ['Tech Talk: AI in Education', 'Guest speaker from Google discussing AI applications', '2025-11-20 14:00:00', 'Engineering Building Room 101', 'academic', 50],
//     ['Campus Cleanup Day', 'Volunteer event to beautify our campus', '2025-11-28 09:00:00', 'Main Quad', 'social', 100],
//     ['Basketball Game: UVA vs VT', 'Exciting rivalry game - students get free entry!', '2025-11-15 19:00:00', 'John Paul Jones Arena', 'sports', 500],
//     ['Fall Music Festival', 'Live performances from student bands', '2025-11-22 18:00:00', 'Amphitheater', 'cultural', 300],
//     ['Python Workshop', 'Hands-on coding workshop for beginners', '2025-11-12 15:00:00', 'Rice Hall Lab', 'workshop', 30],
//     ['Study Abroad Fair', 'Learn about international exchange programs', '2025-11-18 12:00:00', 'Newcomb Hall', 'academic', 150]
// ];

// $stmt = $conn->prepare("SELECT id FROM users LIMIT 1");
// $stmt->execute();
// $first_user = $stmt->fetch(PDO::FETCH_ASSOC);

// if ($first_user) {
//     foreach ($sample_events as $event_data) {
//         $stmt = $conn->prepare("SELECT id FROM events WHERE title = ?");
//         $stmt->execute([$event_data[0]]);
        
//         if (!$stmt->fetch()) {
//             $stmt = $conn->prepare("INSERT INTO events (title, description, event_date, location, category, capacity, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
//             $stmt->execute([$event_data[0], $event_data[1], $event_data[2], $event_data[3], $event_data[4], $event_data[5], $first_user['id']]);
//             echo "Created event: " . $event_data[0] . " (Category: " . $event_data[4] . ")<br>\n";
//         }
//     }
// }

// echo "<br><strong>Setup completed successfully!</strong><br><br>\n";
// echo "<strong>Sample login credentials:</strong><br>\n";
// echo "Admin: admin@university.edu / Admin123!<br>\n";
// echo "Student: student1@university.edu / Student123!<br>\n";
// echo "Faculty: faculty@university.edu / Faculty123!<br><br>\n";
// echo "<a href='search.php'>Go to Event Search</a> | ";
// echo "<a href='index.php'>Go to Home</a>\n";
?>
