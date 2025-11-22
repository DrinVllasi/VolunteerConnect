-- =========================================
-- CREATE DATABASE & USE IT
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
INSERT INTO opportunities (title, description, location, date, time, slots, organization_id) VALUES
('Central Park Clean-Up', 'Join us to clean and beautify Central Park!', 'Central Park', '2025-12-10', '09:00:00', 25, 2),
('Food Drive for Families', 'Collect and distribute food to families in need.', 'Community Center', '2025-12-15', '10:00:00', 15, 2),
('Beach Clean-Up Day', 'Help protect marine life by cleaning Sandy Shores.', 'Sandy Beach', '2025-12-20', '08:00:00', 40, 2),
('Tree Planting Event', 'Plant trees to improve air quality in the city.', 'Riverside Park', '2025-12-22', '09:30:00', 30, 2),
('Senior Home Visit', 'Spend time with seniors and bring joy!', 'Sunset Senior Home', '2025-12-18', '14:00:00', 12, 2);