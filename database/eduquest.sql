-- EduQuest Database Schema
-- Student Achievement System

-- Drop database if it exists to ensure a clean install
DROP DATABASE IF EXISTS eduquest;
CREATE DATABASE eduquest;
USE eduquest;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'teacher', 'student') NOT NULL DEFAULT 'student',
    bio TEXT NULL,
    avatar VARCHAR(255) NULL,
    last_login DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Achievement categories table
CREATE TABLE achievement_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT NULL,
    icon VARCHAR(50) NULL,
    color VARCHAR(20) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Achievements table
CREATE TABLE achievements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    category_id INT NOT NULL,
    achievement_date DATE NOT NULL,
    points INT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES achievement_categories(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- Password reset tokens table
CREATE TABLE password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(100) NOT NULL,
    expires_at DATETIME NOT NULL,
    used TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- User activity log
CREATE TABLE activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action VARCHAR(255) NOT NULL,
    details TEXT NULL,
    ip_address VARCHAR(45) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Insert default admin user (password: admin123)
INSERT INTO users (name, email, password, role, created_at)
VALUES ('Admin User', 'admin@eduquest.com', '$2y$10$XTdwgD84JBBH96Xs9pYKI.5Cl.kdjpKfFjFrKfJOL/d9C4G5u5BQO', 'admin', NOW());

-- Insert default teacher user (password: teacher123)
INSERT INTO users (name, email, password, role, created_at)
VALUES ('Teacher User', 'teacher@eduquest.com', '$2y$10$NHOyeKfwuGXulFyEWhllN.K/c1n8JxCCjn0P4X8QTWClrAntJEhoy', 'teacher', NOW());

-- Insert default student user (password: student123)
INSERT INTO users (name, email, password, role, created_at)
VALUES ('Student User', 'student@eduquest.com', '$2y$10$T03m0JJZTWYoNfJpntbFLuRN7Hcr3QVbU/BgY80mFi7gUQIzniXwq', 'student', NOW());

-- Insert default achievement categories
INSERT INTO achievement_categories (name, description, icon, color)
VALUES 
('Academic Excellence', 'Achievements related to academic performance', 'fas fa-graduation-cap', '#007bff'),
('Sports', 'Achievements in sports and athletics', 'fas fa-running', '#28a745'),
('Arts & Creativity', 'Achievements in arts, music, and creative fields', 'fas fa-palette', '#fd7e14'),
('Leadership', 'Achievements demonstrating leadership qualities', 'fas fa-users', '#6f42c1'),
('Community Service', 'Achievements in volunteering and community service', 'fas fa-hands-helping', '#17a2b8'),
('Personal Growth', 'Achievements in personal development and growth', 'fas fa-seedling', '#20c997'),
('Technology', 'Achievements in technology and innovation', 'fas fa-laptop-code', '#6c757d'),
('Science', 'Achievements in scientific fields and research', 'fas fa-microscope', '#dc3545');

-- Insert sample achievements for the demo student
INSERT INTO achievements (user_id, title, description, category_id, achievement_date, points)
VALUES 
(3, 'Perfect Attendance', 'Attended all classes for the entire semester without any absences', 1, DATE_SUB(CURDATE(), INTERVAL 2 MONTH), 15),
(3, 'Science Fair Winner', 'First place in the annual science fair with project on renewable energy', 8, DATE_SUB(CURDATE(), INTERVAL 1 MONTH), 25),
(3, 'Student Council Member', 'Elected as a representative for the student council', 4, DATE_SUB(CURDATE(), INTERVAL 3 MONTH), 20);

-- Create indexes for better performance
CREATE INDEX idx_achievements_user_id ON achievements(user_id);
CREATE INDEX idx_achievements_category_id ON achievements(category_id);
CREATE INDEX idx_achievements_date ON achievements(achievement_date);
CREATE INDEX idx_users_role ON users(role);
