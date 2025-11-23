-- =================================================
-- VOLUNTEERCONNECT - FULL DATABASE (2025 Ready)
-- Portable version with organization approval workflow
-- =================================================

CREATE DATABASE IF NOT EXISTS volunteerconnect CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE volunteerconnect;

-- =================================================
-- USERS TABLE
-- =================================================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('user','volunteer','organization','admin') DEFAULT 'user',
    status ENUM('pending','approved','rejected') DEFAULT 'approved',
    total_hours INT DEFAULT 0,
    total_verified_hours INT DEFAULT 0,
    city VARCHAR(100) DEFAULT NULL,
    province VARCHAR(100) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_role (role),
    INDEX idx_email (email)
);

-- =================================================
-- OPPORTUNITIES TABLE
-- =================================================
CREATE TABLE IF NOT EXISTS opportunities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    category VARCHAR(50) DEFAULT 'General',
    location VARCHAR(255) DEFAULT 'Prishtina',
    location_name VARCHAR(255) DEFAULT NULL,
    latitude DECIMAL(10, 8) NULL,
    longitude DECIMAL(11, 8) NULL,
    date DATE NOT NULL,
    time TIME NULL,
    slots INT DEFAULT 10,
    organization_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (organization_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_date (date),
    INDEX idx_category (category),
    INDEX idx_org (organization_id)
);

-- =================================================
-- APPLICATIONS TABLE
-- =================================================
CREATE TABLE IF NOT EXISTS applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    opportunity_id INT NOT NULL,
    volunteer_id INT NOT NULL,
    status ENUM('pending','confirmed','cancelled') DEFAULT 'pending',
    hours_logged INT DEFAULT 0,
    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (opportunity_id) REFERENCES opportunities(id) ON DELETE CASCADE,
    FOREIGN KEY (volunteer_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_application (opportunity_id, volunteer_id)
);

-- =================================================
-- SKILLS & INTERESTS
-- =================================================
CREATE TABLE IF NOT EXISTS skills (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE
);

CREATE TABLE IF NOT EXISTS interests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE
);

CREATE TABLE IF NOT EXISTS volunteer_skills (
    volunteer_id INT NOT NULL,
    skill_id INT NOT NULL,
    PRIMARY KEY (volunteer_id, skill_id),
    FOREIGN KEY (volunteer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (skill_id) REFERENCES skills(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS volunteer_interests (
    volunteer_id INT NOT NULL,
    interest_id INT NOT NULL,
    PRIMARY KEY (volunteer_id, interest_id),
    FOREIGN KEY (volunteer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (interest_id) REFERENCES interests(id) ON DELETE CASCADE
);

-- =================================================
-- VOLUNTEER PREFERENCES
-- =================================================
CREATE TABLE IF NOT EXISTS volunteer_preferences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    volunteer_id INT NOT NULL UNIQUE,
    preferred_categories JSON DEFAULT '[]',
    max_distance INT DEFAULT 50,
    availability_days VARCHAR(50) DEFAULT 'any',
    preferred_time VARCHAR(50) DEFAULT 'any',
    skills JSON DEFAULT '[]',
    bio TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (volunteer_id) REFERENCES users(id) ON DELETE CASCADE
);

-- =================================================
-- INSERT DEFAULT DATA
-- =================================================
INSERT IGNORE INTO users (name, email, password, role, status) VALUES
('Site Admin', 'admin@volunteer.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'approved'),
('Green Kosovo NGO', 'org@volunteer.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'organization', 'approved'),
('Arta Krasniqi', 'arta@volunteer.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'volunteer', 'approved'),
('Donjet Shala', 'donjet@volunteer.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'volunteer', 'approved');

INSERT IGNORE INTO opportunities (title, description, category, location_name, latitude, longitude, date, time, slots, organization_id) VALUES
('Gërmia Park Cleanup Day', 'Join us to clean Parku i Gërmisë! Gloves and bags provided.', 'Environment', 'Parku i Gërmisë', 42.659167, 21.156944, '2025-12-07', '09:00:00', 40, 2),
('Blood Donation Drive', 'National blood drive at Mother Teresa Square. Every donor gets coffee!', 'Health', 'Sheshi Nëna Terezë', 42.662778, 21.165556, '2025-12-14', '10:00:00', 120, 2),
('Winter Food Package Distribution', 'Pack and deliver food to 200 families before winter.', 'Social Aid', 'Sheshi Nëna Terezë', 42.662778, 21.165556, '2025-12-20', '09:30:00', 30, 2);

INSERT IGNORE INTO skills (name) VALUES 
('First Aid'), ('Teaching'), ('Photography'), ('Event Organization'), ('Translation'), ('Driving');

INSERT IGNORE INTO interests (name) VALUES 
('Environment'), ('Education'), ('Health'), ('Animals'), ('Culture'), ('Social Aid'), ('Youth');

-- =================================================
-- Fully portable: safe to import on any device
-- Supports organization approval workflow, total_verified_hours
-- Can safely run multiple times
-- =================================================
