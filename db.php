<?php
// db.php - Database connection and automatic schema setup

function get_social_icon($platform) {
    $platform = strtolower(trim($platform));
    switch($platform) {
        case 'github':
            return '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 19c-5 1.5-5-2.5-7-3m14 6v-3.87a3.37 3.37 0 0 0-.94-2.61c3.14-.35 6.44-1.54 6.44-7A5.44 5.44 0 0 0 20 4.77 5.07 5.07 0 0 0 19.91 1S18.73.65 16 2.48a13.38 13.38 0 0 0-7 0C6.27.65 5.09 1 5.09 1A5.07 5.07 0 0 0 5 4.77a5.44 5.44 0 0 0-1.5 3.78c0 5.42 3.3 6.61 6.44 7A3.37 3.37 0 0 0 9 18.13V22"></path></svg>';
        case 'linkedin':
            return '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"></path><rect x="2" y="9" width="4" height="12"></rect><circle cx="4" cy="4" r="2"></circle></svg>';
        case 'twitter':
        case 'x':
            return '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 3a10.9 10.9 0 0 1-3.14 1.53 4.48 4.48 0 0 0-7.86 3v1A10.66 10.66 0 0 1 3 4s-4 9 5 13a11.64 11.64 0 0 1-7 2c9 5 20 0 20-11.5a4.5 4.5 0 0 0-.08-.83A7.72 7.72 0 0 0 23 3z"></path></svg>';
        case 'instagram':
            return '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line></svg>';
        case 'facebook':
            return '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"></path></svg>';
        case 'youtube':
            return '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22.54 6.42a2.78 2.78 0 0 0-1.94-2C18.88 4 12 4 12 4s-6.88 0-8.6.46a2.78 2.78 0 0 0-1.94 2A29 29 0 0 0 1 11.75a29 29 0 0 0 .46 5.33A2.78 2.78 0 0 0 3.4 19c1.72.46 8.6.46 8.6.46s6.88 0 8.6-.46a2.78 2.78 0 0 0 1.94-2 29 29 0 0 0 .46-5.25 29 29 0 0 0-.46-5.33z"></path><polygon points="9.75 15.02 15.5 11.75 9.75 8.48 9.75 15.02"></polygon></svg>';
        case 'dribbble':
            return '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="M8.56 2.75c4.37 6.03 6.02 9.42 8.03 17.72m2.54-15.38c-3.72 4.35-8.94 5.66-16.88 5.85m19.5 1.9c-3.5-.49-11.05 1-11.6 8.56"></path></svg>';
        default:
            return '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg>';
    }
}

$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'portfolio_db';
$port = '3307';

try {
    // 1. First connect to MySQL server without database to check/create it
    $pdo_init = new PDO("mysql:host=$host;port=$port;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    // Create database if not exists
    $pdo_init->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo_init = null; // Close connection

    // 2. Connect to the specific database
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    // 3. Create Tables if they don't exist
    
    // Admins table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `admins` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `username` VARCHAR(50) NOT NULL UNIQUE,
        `password` VARCHAR(32) NOT NULL,
        `email` VARCHAR(100) NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Profile Settings table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `profile_settings` (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Projects table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `projects` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `title` VARCHAR(100) NOT NULL,
        `description` TEXT DEFAULT NULL,
        `image_path` VARCHAR(255) DEFAULT NULL,
        `project_link` VARCHAR(255) DEFAULT NULL,
        `technologies` VARCHAR(255) DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Documents table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `documents` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `title` VARCHAR(100) NOT NULL,
        `file_path` VARCHAR(255) NOT NULL,
        `file_type` VARCHAR(50) DEFAULT NULL,
        `uploaded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Gallery table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `gallery` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `title` VARCHAR(100) DEFAULT NULL,
        `image_path` VARCHAR(255) NOT NULL,
        `uploaded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Messages table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `messages` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(100) NOT NULL,
        `email` VARCHAR(100) NOT NULL,
        `subject` VARCHAR(150) DEFAULT NULL,
        `message` TEXT NOT NULL,
        `status` ENUM('unread', 'read') DEFAULT 'unread',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Skills table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `skills` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(100) NOT NULL,
        `percentage` INT NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Social links table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `social_links` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `platform` VARCHAR(50) NOT NULL,
        `url` VARCHAR(255) NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // 4. Seed default data if tables are empty
    
    // Seed Admin User
    $stmt = $pdo->query("SELECT COUNT(*) FROM `admins`");
    if ($stmt->fetchColumn() == 0) {
        $default_username = 'admin';
        $default_password = md5('admin123');
        $default_email = 'admin@example.com';
        
        $insert_user = $pdo->prepare("INSERT INTO `admins` (`username`, `password`, `email`) VALUES (?, ?, ?)");
        $insert_user->execute([$default_username, $default_password, $default_email]);
    }

    // Seed Profile Settings
    $stmt = $pdo->query("SELECT COUNT(*) FROM `profile_settings`");
    if ($stmt->fetchColumn() == 0) {
        $insert_profile = $pdo->prepare("INSERT INTO `profile_settings` (
            `id`, `full_name`, `title`, `bio_short`, `bio_long`, `profile_pic`, `cv_file`, `email`, `phone`, `location`, `github_url`, `linkedin_url`, `twitter_url`
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $insert_profile->execute([
            1,
            'Alex Carter',
            'Creative Full-Stack Developer',
            'I design and build premium web applications, blending visual excellence with robust software engineering.',
            'Hi! I am Alex Carter, a developer based in San Francisco. I specialize in backend architectures, clean frontend UI components, and everything in between. I love crafting interfaces that feel responsive and alive, using modern web technologies to solve real-world problems.',
            '', // blank defaults (frontend will render nice placeholder styling)
            '', // blank defaults
            'alex.carter@example.com',
            '+1 (555) 019-2834',
            'San Francisco, CA',
            'https://github.com',
            'https://linkedin.com',
            'https://twitter.com'
        ]);
    }

    // Seed Skills
    $stmt = $pdo->query("SELECT COUNT(*) FROM `skills`");
    if ($stmt->fetchColumn() == 0) {
        $insert_skill = $pdo->prepare("INSERT INTO `skills` (`name`, `percentage`) VALUES (?, ?)");
        $insert_skill->execute(['Frontend Development', 90]);
        $insert_skill->execute(['Backend Engineering (PHP/Node)', 85]);
        $insert_skill->execute(['Database Management (SQL/NoSQL)', 80]);
        $insert_skill->execute(['UI/UX Design & Prototyping', 75]);
    }

    // Seed Social Links
    $stmt = $pdo->query("SELECT COUNT(*) FROM `social_links`");
    if ($stmt->fetchColumn() == 0) {
        $insert_social = $pdo->prepare("INSERT INTO `social_links` (`platform`, `url`) VALUES (?, ?)");
        $insert_social->execute(['GitHub', 'https://github.com']);
        $insert_social->execute(['LinkedIn', 'https://linkedin.com']);
        $insert_social->execute(['Twitter', 'https://twitter.com']);
    }

} catch (PDOException $e) {
    die("Database Connection failed: " . $e->getMessage());
}
?>
