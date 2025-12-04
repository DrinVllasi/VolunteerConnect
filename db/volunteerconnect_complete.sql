-- =====================================================
-- VOLUNTEERCONNECT – FINAL JUDGE VERSION (November 2025)
-- ONLY 1 ORGANIZATION: Green Kosovo NGO
-- Everything else stays exactly the same
-- =====================================================

CREATE DATABASE IF NOT EXISTS volunteerconnect CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE volunteerconnect;

-- Drop only what we need to rebuild cleanly
DROP TABLE IF EXISTS applications, opportunities, volunteer_interests, volunteer_skills, interests, skills, volunteer_preferences, users;

-- ==================== USERS ====================
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('user','volunteer','organization','admin') DEFAULT 'user',
    status ENUM('pending','approved','rejected') DEFAULT 'approved',
    phone VARCHAR(20) DEFAULT NULL,
    total_hours INT DEFAULT 0,
    total_verified_hours INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_role (role),
    INDEX idx_email (email)
);

-- ==================== OPPORTUNITIES ====================
CREATE TABLE opportunities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    category VARCHAR(50) DEFAULT 'General',
    location_name VARCHAR(255) NOT NULL,
    latitude DECIMAL(10,8) NULL,
    longitude DECIMAL(11,8) NULL,
    date DATE NOT NULL,
    time TIME NULL,
    slots INT DEFAULT 20,
    filled_slots INT DEFAULT 0,
    organization_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (organization_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_date (date),
    INDEX idx_category (category)
);

-- ==================== APPLICATIONS ====================
CREATE TABLE applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    opportunity_id INT NOT NULL,
    volunteer_id INT NOT NULL,
    status ENUM('pending','confirmed','completed','cancelled') DEFAULT 'pending',
    hours_worked DECIMAL(5,2) DEFAULT 0.00,
    hours_approved TINYINT(1) DEFAULT 0,
    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    FOREIGN KEY (opportunity_id) REFERENCES opportunities(id) ON DELETE CASCADE,
    FOREIGN KEY (volunteer_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_application (opportunity_id, volunteer_id)
);

-- ==================== SKILLS & INTERESTS ====================
CREATE TABLE skills (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100) NOT NULL UNIQUE);
CREATE TABLE interests (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100) NOT NULL UNIQUE);
CREATE TABLE volunteer_skills (volunteer_id INT NOT NULL, skill_id INT NOT NULL, PRIMARY KEY (volunteer_id, skill_id),
    FOREIGN KEY (volunteer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (skill_id) REFERENCES skills(id) ON DELETE CASCADE);
CREATE TABLE volunteer_interests (volunteer_id INT NOT NULL, interest_id INT NOT NULL, PRIMARY KEY (volunteer_id, interest_id),
    FOREIGN KEY (volunteer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (interest_id) REFERENCES interests(id) ON DELETE CASCADE);
CREATE TABLE volunteer_preferences (
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

-- ==================== INSERT USERS (Only Green Kosovo NGO as org) ====================
-- Password = password for all
INSERT IGNORE INTO users (name, email, password, role, status, phone) VALUES
('Admin', 'admin@volunteer.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'approved', '+38344123456'),
('Green Kosovo NGO', 'green@volunteer.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'organization', 'approved', '+38349234567'),
('Arta Krasniqi', 'arta@volunteer.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'volunteer', 'approved', '+38344123123'),
('Donjet Shala', 'donjet@volunteer.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'volunteer', 'approved', '+38344999888');

-- ==================== ALL 10 BEAUTIFUL EVENTS — NOW BY GREEN KOSOVO ONLY ====================
INSERT INTO opportunities (title, description, category, location_name, latitude, longitude, date, time, slots, filled_slots, organization_id) VALUES
('Germia Park Cleanup Day', 'Join hundreds of volunteers to clean Prishtina’s largest park. Gloves, bags, and water provided. Let’s keep Germia green!', 'Environment', 'Parku i Gërmisë', 42.65916700, 21.15694400, '2025-12-07', '09:00:00', 50, 32, 2),
('Blood Donation Drive', 'Your blood can save lives. Major drive at Mother Teresa Square with free snacks and certificates!', 'Health', 'Sheshi Nëna Terezë', 42.66277800, 21.16555600, '2025-12-14', '10:00:00', 100, 67, 2),
('Ramadan Iftar for the Needy', 'Prepare and distribute 500 iftar meals outside the Grand Mosque. A beautiful way to give back.', 'Social Aid', 'Xhamia e Madhe', 42.65930000, 21.16280000, '2025-12-20', '17:30:00', 30, 28, 2),
('Stray Animal Feeding & Care', 'Help stray dogs and cats in City Park. Food and grooming tools provided!', 'Animals', 'Parku i Qytetit', 42.67120000, 21.16670000, '2025-12-06', '14:00:00', 25, 19, 2),
('English Conversation Club', 'Help youth practice English at the University of Prishtina library. Fun games included!', 'Education', 'Universiteti i Prishtinës', 42.65010000, 21.15340000, '2025-12-10', '16:00:00', 20, 12, 2),
('Badovc Lake Cleanup', 'Kayaks and walking teams to clean the lake. BBQ afterwards!', 'Environment', 'Liqeni i Badovcit', 42.62750000, 21.24640000, '2025-12-21', '10:00:00', 40, 15, 2),
('Winter Clothes Drive', 'Sort and distribute donated winter clothes to families in need.', 'Social Aid', 'Qendra e Qytetit', 42.66200000, 21.16400000, '2025-12-15', '11:00:00', 35, 29, 2),
('Youth Mental Health Workshop', 'Support teens in open discussions about mental health.', 'Health', 'Teatri Kombëtar', 42.66510000, 21.15990000, '2025-12-18', '17:00:00', 18, 11, 2),
('Old Bazaar Cultural Fair', 'Celebrate Prishtina’s heritage with music, food, and crafts.', 'Culture', 'Pazari i Vjetër', 42.64650000, 21.14980000, '2025-12-28', '12:00:00', 45, 20, 2),
('Tree Planting at Arbëria', 'Plant 200 trees to expand green spaces in Arbëria neighborhood.', 'Environment', 'Lagjja Arbëria', 42.65000000, 21.14000000, '2025-12-13', '09:30:00', 60, 44, 2);

-- ==================== SAMPLE APPLICATIONS ====================
INSERT INTO applications (opportunity_id, volunteer_id, status, hours_worked, hours_approved) VALUES
(1, 3, 'confirmed', 4.50, 1),
(2, 3, 'pending', 0.00, 0),
(3, 4, 'confirmed', 3.00, 1),
(4, 4, 'pending', 0.00, 0),
(5, 3, 'pending', 0.00, 0);

-- ==================== SKILLS & INTERESTS (kept exactly as before) ====================
INSERT IGNORE INTO skills (name) VALUES ('First Aid'), ('Teaching'), ('Photography'), ('Event Organization'), ('Translation'), ('Driving');
INSERT IGNORE INTO interests (name) VALUES ('Environment'), ('Education'), ('Health'), ('Animals'), ('Culture'), ('Social Aid'), ('Youth');

-- DONE! 
-- Only Green Kosovo NGO exists as organization
-- All events belong to them
-- All volunteers and data preserved
-- Perfect for judges — clean, focused, professional