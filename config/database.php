<?php
class Database {
    private $host = 'localhost';//'localhost';
    private $db_name = 'vus8cb';//'wtm6hs';
    private $username = 'vus8cb';//'wtm6hs';
    private $password = 'IV8VPfI6DWzn';//'xTb7GT1RRuTh';
    private $port = 5432;
    private $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO("pgsql:host=" . $this->host . ";port=" . $this->port . ";dbname=" . $this->db_name, 
                                $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $e) {
            echo "Connection error: " . $e->getMessage();
        }
        return $this->conn;
    }
}

function initializeDatabase() {
    $database = new Database();
    $conn = $database->getConnection();
    
    $sql = "
    CREATE TABLE IF NOT EXISTS campus_users (
        id SERIAL PRIMARY KEY,
        email VARCHAR(255) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        first_name VARCHAR(100) NOT NULL,
        last_name VARCHAR(100) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        last_login TIMESTAMP
    );
    
    CREATE TABLE IF NOT EXISTS campus_events (
        id SERIAL PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        event_date TIMESTAMP NOT NULL,
        location VARCHAR(255),
        capacity INTEGER DEFAULT 0,
        category VARCHAR(100) DEFAULT 'general',
        status VARCHAR(50) DEFAULT 'active',
        created_by INTEGER REFERENCES campus_users(id),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );
    
    CREATE TABLE IF NOT EXISTS campus_event_registrations (
        id SERIAL PRIMARY KEY,
        event_id INTEGER REFERENCES campus_events(id) ON DELETE CASCADE,
        user_id INTEGER REFERENCES campus_users(id) ON DELETE CASCADE,
        registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        status VARCHAR(50) DEFAULT 'registered',
        UNIQUE(event_id, user_id)
    );
    
    CREATE TABLE IF NOT EXISTS campus_event_categories (
        id SERIAL PRIMARY KEY,
        name VARCHAR(100) UNIQUE NOT NULL,
        description TEXT,
        color VARCHAR(7) DEFAULT '#007bff'
    );
    
    CREATE TABLE IF NOT EXISTS campus_user_profiles (
        id SERIAL PRIMARY KEY,
        user_id INTEGER REFERENCES campus_users(id) ON DELETE CASCADE UNIQUE,
        phone VARCHAR(20),
        department VARCHAR(100),
        year_of_study INTEGER,
        bio TEXT,
        profile_image VARCHAR(255),
        preferences JSONB
    );";
    
    $conn->exec($sql);
}
?>
