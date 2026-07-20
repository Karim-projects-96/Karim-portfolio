<?php
// api.php - JSON API endpoint for static HTML frontend
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, POST");

require_once 'db.php';

$get = $_GET['get'] ?? '';
$action = $_GET['action'] ?? '';

// GET requests
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        if ($get === 'profile') {
            // Profile settings
            $stmt = $pdo->query("SELECT * FROM `profile_settings` WHERE id = 1");
            $profile = $stmt->fetch();
            
            // Skills
            $skills_stmt = $pdo->query("SELECT * FROM `skills` ORDER BY percentage DESC");
            $skills = $skills_stmt->fetchAll();
            
            // Social links
            $soc_stmt = $pdo->query("SELECT * FROM `social_links` ORDER BY id ASC");
            $socials = $soc_stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'profile' => $profile,
                'skills' => $skills,
                'socials' => $socials
            ]);
            exit;
        }
        
        if ($get === 'projects') {
            $stmt = $pdo->query("SELECT * FROM `projects` ORDER BY id DESC");
            $projects = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'projects' => $projects
            ]);
            exit;
        }
        
        if ($get === 'documents') {
            $stmt = $pdo->query("SELECT * FROM `documents` WHERE `file_type` != 'Resume' ORDER BY id DESC");
            $documents = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'documents' => $documents
            ]);
            exit;
        }
        
        if ($get === 'gallery') {
            $stmt = $pdo->query("SELECT * FROM `gallery` ORDER BY id DESC");
            $gallery = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'gallery' => $gallery
            ]);
            exit;
        }
        
        echo json_encode(['success' => false, 'message' => 'Invalid GET parameter.']);
        exit;
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        exit;
    }
}

// POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'send_message') {
        // Read JSON input
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            $input = $_POST;
        }
        
        $name = trim($input['name'] ?? '');
        $email = trim($input['email'] ?? '');
        $subject = trim($input['subject'] ?? '');
        $message = trim($input['message'] ?? '');
        
        if (empty($name) || empty($email) || empty($message)) {
            echo json_encode(['success' => false, 'message' => 'Name, email, and message are required fields.']);
            exit;
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
            exit;
        }
        
        try {
            $stmt = $pdo->prepare("INSERT INTO messages (name, email, subject, message) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $email, $subject, $message]);
            
            echo json_encode(['success' => true, 'message' => 'Your message has been sent successfully!']);
            exit;
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
            exit;
        }
    }
    
    echo json_encode(['success' => false, 'message' => 'Invalid POST action.']);
    exit;
}
