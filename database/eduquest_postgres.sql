-- EduQuest Database Schema for PostgreSQL
-- Student Achievement System

-- Create user role type
CREATE TYPE user_role AS ENUM ('admin', 'teacher', 'student');

-- Users table
CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role user_role NOT NULL DEFAULT 'student',
    bio TEXT NULL,
    avatar VARCHAR(255) NULL,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL
);

-- Achievement categories table
CREATE TABLE achievement_categories (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT NULL,
    icon VARCHAR(50) NULL,
    color VARCHAR(20) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL
);

-- Achievements table
CREATE TABLE achievements (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    category_id INTEGER NOT NULL,
    achievement_date DATE NOT NULL,
    points INTEGER NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES achievement_categories(id) ON DELETE RESTRICT
);

-- Password reset tokens table
CREATE TABLE password_resets (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL,
    token VARCHAR(100) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    used BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- User activity log
CREATE TABLE activity_logs (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL,
    action VARCHAR(255) NOT NULL,
    details TEXT NULL,
    ip_address VARCHAR(45) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Create triggers for updated_at timestamps
CREATE OR REPLACE FUNCTION update_modified_column() 
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ LANGUAGE 'plpgsql';

CREATE TRIGGER update_users_modtime
    BEFORE UPDATE ON users
    FOR EACH ROW
    EXECUTE FUNCTION update_modified_column();

CREATE TRIGGER update_achievement_categories_modtime
    BEFORE UPDATE ON achievement_categories
    FOR EACH ROW
    EXECUTE FUNCTION update_modified_column();

CREATE TRIGGER update_achievements_modtime
    BEFORE UPDATE ON achievements
    FOR EACH ROW
    EXECUTE FUNCTION update_modified_column();

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
(3, 'Perfect Attendance', 'Attended all classes for the entire semester without any absences', 1, CURRENT_DATE - INTERVAL '2 month', 15),
(3, 'Science Fair Winner', 'First place in the annual science fair with project on renewable energy', 8, CURRENT_DATE - INTERVAL '1 month', 25),
(3, 'Student Council Member', 'Elected as a representative for the student council', 4, CURRENT_DATE - INTERVAL '3 month', 20);

-- Create indexes for better performance
CREATE INDEX idx_achievements_user_id ON achievements(user_id);
CREATE INDEX idx_achievements_category_id ON achievements(category_id);
CREATE INDEX idx_achievements_date ON achievements(achievement_date);
CREATE INDEX idx_users_role ON users(role);