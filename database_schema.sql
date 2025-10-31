-- Campus Event Management Database Schema
-- PostgreSQL Database: campus_events

-- Users table for authentication and profiles
CREATE TABLE IF NOT EXISTS campus_users (
    id SERIAL PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP
);



-- Events table for campus events
CREATE TABLE IF NOT EXISTS campus_events (
    id SERIAL PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    event_date TIMESTAMP NOT NULL,
    location VARCHAR(255),
    capacity INTEGER DEFAULT 0,
    category VARCHAR(100) DEFAULT 'general',
    status VARCHAR(50) DEFAULT 'active',
    created_by INTEGER REFERENCES users(id) ON DELETE CASCADE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Event registrations for tracking attendees
CREATE TABLE IF NOT EXISTS campus_event_registrations (
    id SERIAL PRIMARY KEY,
    event_id INTEGER REFERENCES events(id) ON DELETE CASCADE,
    user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status VARCHAR(50) DEFAULT 'registered',
    UNIQUE(event_id, user_id)
);

-- Event categories for organization
CREATE TABLE IF NOT EXISTS campus_event_categories (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    color VARCHAR(7) DEFAULT '#007bff'
);

-- User profiles for additional information
CREATE TABLE IF NOT EXISTS campus_user_profiles (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id) ON DELETE CASCADE UNIQUE,
    phone VARCHAR(20),
    department VARCHAR(100),
    year_of_study INTEGER,
    bio TEXT,
    profile_image VARCHAR(255),
    preferences JSONB
);

-- Insert default categories
INSERT INTO campus_event_categories (name, description, color) VALUES
('Academic', 'Academic events and lectures', '#28a745'),
('Social', 'Social gatherings and parties', '#ffc107'),
('Sports', 'Athletic events and competitions', '#dc3545'),
('Cultural', 'Cultural events and performances', '#6f42c1'),
('Career', 'Career fairs and networking', '#17a2b8'),
('Workshop', 'Educational workshops and training', '#fd7e14')
ON CONFLICT (name) DO NOTHING;