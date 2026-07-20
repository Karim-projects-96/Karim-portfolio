-- Database Schema for Portfolio Website
-- Database Name: portfolio_db

CREATE DATABASE IF NOT EXISTS `portfolio_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `portfolio_db`;

-- Admins Table
CREATE TABLE IF NOT EXISTS `admins` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password` VARCHAR(32) NOT NULL,
    `email` VARCHAR(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Profile Settings Table
CREATE TABLE IF NOT EXISTS `profile_settings` (
    `id` INT PRIMARY KEY,
    `full_name` VARCHAR(100) NOT NULL,
    `title` VARCHAR(100) DEFAULT NULL,
    `bio_short` TEXT DEFAULT NULL,
    `bio_long` TEXT DEFAULT NULL,
    `profile_pic` VARCHAR(255) DEFAULT NULL,
    `cv_file` VARCHAR(255) DEFAULT NULL,
    `email` VARCHAR(100) DEFAULT NULL,
    `phone` VARCHAR(20) DEFAULT NULL,
    `location` VARCHAR(100) DEFAULT NULL,
    `github_url` VARCHAR(255) DEFAULT NULL,
    `linkedin_url` VARCHAR(255) DEFAULT NULL,
    `twitter_url` VARCHAR(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Projects Table
CREATE TABLE IF NOT EXISTS `projects` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(100) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `image_path` VARCHAR(255) DEFAULT NULL,
    `project_link` VARCHAR(255) DEFAULT NULL,
    `technologies` VARCHAR(255) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Documents Table
CREATE TABLE IF NOT EXISTS `documents` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(100) NOT NULL,
    `file_path` VARCHAR(255) NOT NULL,
    `file_type` VARCHAR(50) DEFAULT NULL,
    `uploaded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Gallery Table
CREATE TABLE IF NOT EXISTS `gallery` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(100) DEFAULT NULL,
    `image_path` VARCHAR(255) NOT NULL,
    `uploaded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Messages Table
CREATE TABLE IF NOT EXISTS `messages` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) NOT NULL,
    `subject` VARCHAR(150) DEFAULT NULL,
    `message` TEXT NOT NULL,
    `status` ENUM('unread', 'read') DEFAULT 'unread',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed Default Admin (password: admin123, MD5 hash: 0192023a7bbd73250516f069df18b500)
INSERT INTO `admins` (`id`, `username`, `password`, `email`) 
VALUES (1, 'admin', '0192023a7bbd73250516f069df18b500', 'admin@example.com')
ON DUPLICATE KEY UPDATE id=id;

-- Seed Default Profile Settings
INSERT INTO `profile_settings` (
    `id`, `full_name`, `title`, `bio_short`, `bio_long`, `profile_pic`, `cv_file`, `email`, `phone`, `location`, `github_url`, `linkedin_url`, `twitter_url`
) VALUES (
    1,
    'Alex Carter',
    'Creative Full-Stack Developer',
    'I design and build premium web applications, blending visual excellence with robust software engineering.',
    'Hi! I am Alex Carter, a developer based in San Francisco. I specialize in backend architectures, clean frontend UI components, and everything in between. I love crafting interfaces that feel responsive and alive, using modern web technologies to solve real-world problems.',
    '',
    '',
    'alex.carter@example.com',
    '+1 (555) 019-2834',
    'San Francisco, CA',
    'https://github.com',
    'https://linkedin.com',
    'https://twitter.com'
) ON DUPLICATE KEY UPDATE id=id;

-- Skills Table
CREATE TABLE IF NOT EXISTS `skills` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `percentage` INT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed Default Skills
INSERT INTO `skills` (`id`, `name`, `percentage`) VALUES
(1, 'Frontend Development', 90),
(2, 'Backend Engineering (PHP/Node)', 85),
(3, 'Database Management (SQL/NoSQL)', 80),
(4, 'UI/UX Design & Prototyping', 75)
ON DUPLICATE KEY UPDATE id=id;
