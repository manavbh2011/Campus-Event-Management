# Campus-Event-Management
A campus event management system for UVA students and faculty

## Setup Instructions

### Database Setup
To create the database tables with sample data, visit: `setup.php`

This will create the PostgreSQL database schema and populate it with sample users and events.

### Database Schema

**users table:**
- id (SERIAL PRIMARY KEY)
- email (VARCHAR(255) UNIQUE NOT NULL)
- password (VARCHAR(255) NOT NULL)
- first_name (VARCHAR(100) NOT NULL)
- last_name (VARCHAR(100) NOT NULL)
- created_at (TIMESTAMP DEFAULT CURRENT_TIMESTAMP)
- last_login (TIMESTAMP)

**events table:**
- id (SERIAL PRIMARY KEY)
- title (VARCHAR(255) NOT NULL)
- description (TEXT)
- event_date (TIMESTAMP NOT NULL)
- location (VARCHAR(255))
- capacity (INTEGER DEFAULT 0)
- category (VARCHAR(100) DEFAULT 'general')
- status (VARCHAR(50) DEFAULT 'active')
- created_by (INTEGER REFERENCES users(id))
- created_at (TIMESTAMP DEFAULT CURRENT_TIMESTAMP)
- updated_at (TIMESTAMP DEFAULT CURRENT_TIMESTAMP)

**event_registrations table:**
- id (SERIAL PRIMARY KEY)
- event_id (INTEGER REFERENCES events(id))
- user_id (INTEGER REFERENCES users(id))
- registration_date (TIMESTAMP DEFAULT CURRENT_TIMESTAMP)
- status (VARCHAR(50) DEFAULT 'registered')

**event_categories table:**
- id (SERIAL PRIMARY KEY)
- name (VARCHAR(100) UNIQUE NOT NULL)
- description (TEXT)
- color (VARCHAR(7) DEFAULT '#007bff')

**user_profiles table:**
- id (SERIAL PRIMARY KEY)
- user_id (INTEGER REFERENCES users(id) UNIQUE)
- phone (VARCHAR(20))
- department (VARCHAR(100))
- year_of_study (INTEGER)
- bio (TEXT)
- profile_image (VARCHAR(255))
- preferences (JSONB)

## Sample Login Credentials
After running setup.php:
- admin@university.edu / Admin123!
- student1@university.edu / Student123!
- faculty@university.edu / Faculty123!
