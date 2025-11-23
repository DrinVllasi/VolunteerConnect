-- =========================================
-- VOLUNTEERCONNECT COMPLETE DATABASE
-- Full database schema with all features
-- =========================================
CREATE DATABASE IF NOT EXISTS volunteerconnect;
USE volunteerconnect;

-- =========================================
-- USERS TABLE (updated with all roles we use)
-- =========================================
DROP TABLE IF EXISTS users;
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('user','volunteer','organization','admin') DEFAULT 'user',
    total_hours INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =========================================
-- OPPORTUNITIES TABLE (your code uses this name!)
-- =========================================
DROP TABLE IF EXISTS opportunities;
CREATE TABLE opportunities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    category VARCHAR(50) DEFAULT 'General',
    location VARCHAR(255) NOT NULL,
    date DATE NOT NULL,
    time TIME NULL,
    slots INT DEFAULT 10,
    organization_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (organization_id) REFERENCES users(id) ON DELETE SET NULL
);

-- =========================================
-- APPLICATIONS TABLE (this is what your code uses!)
-- =========================================
DROP TABLE IF EXISTS applications;
CREATE TABLE applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    opportunity_id INT NOT NULL,
    volunteer_id INT NOT NULL,
    status ENUM('pending','confirmed','cancelled') DEFAULT 'pending',
    hours_logged INT DEFAULT 0,
    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (opportunity_id) REFERENCES opportunities(id) ON DELETE CASCADE,
    FOREIGN KEY (volunteer_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE(opportunity_id, volunteer_id) -- prevent double apply
);

-- =========================================
-- VOLUNTEER PREFERENCES TABLE
-- =========================================
CREATE TABLE IF NOT EXISTS volunteer_preferences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    volunteer_id INT NOT NULL UNIQUE,
    preferred_categories TEXT, -- JSON array of preferred categories
    max_distance INT DEFAULT 50, -- Maximum distance willing to travel (km)
    availability_days VARCHAR(20), -- e.g., "weekends", "weekdays", "any"
    preferred_time VARCHAR(20), -- e.g., "morning", "afternoon", "evening", "any"
    skills TEXT, -- JSON array of skills
    bio TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (volunteer_id) REFERENCES users(id) ON DELETE CASCADE
);

-- =========================================
-- OPPORTUNITY REQUIREMENTS TABLE (for advanced matching)
-- =========================================
CREATE TABLE IF NOT EXISTS opportunity_requirements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    opportunity_id INT NOT NULL,
    min_level INT DEFAULT 1, -- Minimum volunteer level required
    required_skills TEXT, -- JSON array of required skills
    preferred_experience_hours INT DEFAULT 0, -- Preferred minimum hours in similar category
    FOREIGN KEY (opportunity_id) REFERENCES opportunities(id) ON DELETE CASCADE
);

-- =========================================
-- TWO-WAY INTEREST SYSTEM TABLES
-- =========================================

-- Volunteer Interests Table
CREATE TABLE IF NOT EXISTS volunteer_interests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    volunteer_id INT NOT NULL,
    opportunity_id INT NOT NULL,
    interested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (volunteer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (opportunity_id) REFERENCES opportunities(id) ON DELETE CASCADE,
    UNIQUE(volunteer_id, opportunity_id)
);

-- Organization Invites Table
CREATE TABLE IF NOT EXISTS organization_invites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    organization_id INT NOT NULL,
    volunteer_id INT NOT NULL,
    opportunity_id INT NOT NULL,
    invited_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'accepted', 'declined') DEFAULT 'pending',
    FOREIGN KEY (organization_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (volunteer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (opportunity_id) REFERENCES opportunities(id) ON DELETE CASCADE,
    UNIQUE(organization_id, volunteer_id, opportunity_id)
);

-- =========================================
-- INDEXES FOR PERFORMANCE
-- =========================================
CREATE INDEX idx_opp_category ON opportunities(category);
CREATE INDEX idx_vol_prefs ON volunteer_preferences(volunteer_id);
CREATE INDEX idx_vol_interest ON volunteer_interests(volunteer_id, opportunity_id);
CREATE INDEX idx_org_invite ON organization_invites(organization_id, opportunity_id);
CREATE INDEX idx_vol_invite ON organization_invites(volunteer_id, status);

-- =========================================
-- INSERT DEFAULT DATA
-- =========================================

-- Admin (password = "password")
INSERT INTO users (name, email, password, role) VALUES
('Site Admin', 'admin@volunteer.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Sample Organization
INSERT INTO users (name, email, password, role) VALUES
('Green Earth Org', 'org@volunteer.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'organization');

-- Sample Volunteer
INSERT INTO users (name, email, password, role) VALUES
('Alex Rivera', 'alex@volunteer.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'volunteer');

-- Sample Opportunities (by the organization)
INSERT INTO opportunities (title, description, category, location, date, time, slots, organization_id) VALUES
('Central Park Clean-Up', 'Join us to clean and beautify Central Park!', 'Environment', 'Central Park', '2025-12-10', '09:00:00', 25, 2),
('Food Drive for Families', 'Collect and distribute food to families in need.', 'Food Service', 'Community Center', '2025-12-15', '10:00:00', 15, 2),
('Beach Clean-Up Day', 'Help protect marine life by cleaning Sandy Shores.', 'Environment', 'Sandy Beach', '2025-12-20', '08:00:00', 40, 2),
('Tree Planting Event', 'Plant trees to improve air quality in the city.', 'Environment', 'Riverside Park', '2025-12-22', '09:30:00', 30, 2),
('Senior Home Visit', 'Spend time with seniors and bring joy!', 'Healthcare', 'Sunset Senior Home', '2025-12-18', '14:00:00', 12, 2);
