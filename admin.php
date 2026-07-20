<?php
// admin.php - Administrative Control Panel
session_start();
require_once 'db.php';

// Access Control: check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit;
}

// Current Active Tab
$tab = $_GET['tab'] ?? 'dashboard';
$allowed_tabs = ['dashboard', 'profile', 'projects', 'documents', 'gallery', 'messages', 'security', 'skills'];
if (!in_array($tab, $allowed_tabs)) {
    $tab = 'dashboard';
}

// Session Notifications
$notify_msg = $_SESSION['notify_msg'] ?? '';
$notify_type = $_SESSION['notify_type'] ?? '';
unset($_SESSION['notify_msg'], $_SESSION['notify_type']);

// Helper function to set session notification and redirect
function set_notification($msg, $type, $tab) {
    $_SESSION['notify_msg'] = $msg;
    $_SESSION['notify_type'] = $type;
    header("Location: admin.php?tab=" . $tab);
    exit;
}

// ----------------------------------------------------
// POST FORM SUBMISSION HANDLERS
// ----------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    // 1. UPDATE PROFILE & CONTACT DETAILS
    if ($action === 'update_profile') {
        $full_name = trim($_POST['full_name'] ?? '');
        $title = trim($_POST['title'] ?? '');
        $bio_short = trim($_POST['bio_short'] ?? '');
        $bio_long = trim($_POST['bio_long'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $location = trim($_POST['location'] ?? '');
        if (empty($full_name)) {
            set_notification("Name cannot be empty.", "danger", "profile");
        }

        try {
            // Get current profile settings to check existing files
            $curr_stmt = $pdo->query("SELECT profile_pic, cv_file FROM profile_settings WHERE id = 1");
            $curr_profile = $curr_stmt->fetch();
            
            $profile_pic = $curr_profile['profile_pic'] ?? '';
            $cv_file = $curr_profile['cv_file'] ?? '';

            // Handle Profile Image Upload
            if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
                $file_tmp = $_FILES['profile_pic']['tmp_name'];
                $file_name = $_FILES['profile_pic']['name'];
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                
                $allowed_img_exts = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
                if (in_array($file_ext, $allowed_img_exts)) {
                    $new_filename = 'profile_' . time() . '.' . $file_ext;
                    $upload_path = 'images/' . $new_filename;
                    
                    if (move_uploaded_file($file_tmp, $upload_path)) {
                        // Delete old profile picture if exists
                        if (!empty($profile_pic) && file_exists($profile_pic)) {
                            unlink($profile_pic);
                        }
                        $profile_pic = $upload_path;
                    }
                }
            }

            // Handle CV/Resume Document Upload
            if (isset($_FILES['cv_file']) && $_FILES['cv_file']['error'] === UPLOAD_ERR_OK) {
                $file_tmp = $_FILES['cv_file']['tmp_name'];
                $file_name = $_FILES['cv_file']['name'];
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                
                $allowed_doc_exts = ['pdf', 'doc', 'docx'];
                if (in_array($file_ext, $allowed_doc_exts)) {
                    $new_filename = 'resume_' . time() . '.' . $file_ext;
                    $upload_path = 'documents/' . $new_filename;
                    
                    if (move_uploaded_file($file_tmp, $upload_path)) {
                        // Delete old CV if exists
                        if (!empty($cv_file) && file_exists($cv_file)) {
                            unlink($cv_file);
                        }
                        $cv_file = $upload_path;
                    }
                }
            }

            // Update Database
            $upd_stmt = $pdo->prepare("UPDATE profile_settings SET 
                full_name = ?, title = ?, bio_short = ?, bio_long = ?, 
                profile_pic = ?, cv_file = ?, email = ?, phone = ?, 
                location = ? 
                WHERE id = 1");
            
            $upd_stmt->execute([
                $full_name, $title, $bio_short, $bio_long,
                $profile_pic, $cv_file, $email, $phone,
                $location
            ]);

            set_notification("Profile and contact details updated successfully.", "success", "profile");

        } catch (PDOException $e) {
            set_notification("Database error: " . $e->getMessage(), "danger", "profile");
        }
    }

    // 2. ADD A NEW PROJECT
    if ($action === 'add_project') {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $project_link = trim($_POST['project_link'] ?? '');
        $technologies = trim($_POST['technologies'] ?? '');
        $image_path = '';

        if (empty($title)) {
            set_notification("Project title is required.", "danger", "projects");
        }

        // Handle Project Image Upload
        if (isset($_FILES['project_img']) && $_FILES['project_img']['error'] === UPLOAD_ERR_OK) {
            $file_tmp = $_FILES['project_img']['tmp_name'];
            $file_name = $_FILES['project_img']['name'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            
            $allowed_img_exts = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
            if (in_array($file_ext, $allowed_img_exts)) {
                $new_filename = 'project_' . time() . '_' . rand(100, 999) . '.' . $file_ext;
                $upload_path = 'images/' . $new_filename;
                
                if (move_uploaded_file($file_tmp, $upload_path)) {
                    $image_path = $upload_path;
                }
            }
        }

        try {
            $ins_stmt = $pdo->prepare("INSERT INTO projects (title, description, image_path, project_link, technologies) VALUES (?, ?, ?, ?, ?)");
            $ins_stmt->execute([$title, $description, $image_path, $project_link, $technologies]);
            
            set_notification("Project added successfully.", "success", "projects");
        } catch (PDOException $e) {
            set_notification("Database error: " . $e->getMessage(), "danger", "projects");
        }
    }

    // 3. EDIT EXISTING PROJECT
    if ($action === 'edit_project') {
        $proj_id = intval($_POST['project_id']);
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $project_link = trim($_POST['project_link'] ?? '');
        $technologies = trim($_POST['technologies'] ?? '');

        if (empty($title) || $proj_id <= 0) {
            set_notification("Invalid project data.", "danger", "projects");
        }

        try {
            // Get current project details to handle image update
            $curr_stmt = $pdo->prepare("SELECT image_path FROM projects WHERE id = ?");
            $curr_stmt->execute([$proj_id]);
            $curr_project = $curr_stmt->fetch();
            $image_path = $curr_project['image_path'] ?? '';

            // Handle Project Image Upload
            if (isset($_FILES['project_img']) && $_FILES['project_img']['error'] === UPLOAD_ERR_OK) {
                $file_tmp = $_FILES['project_img']['tmp_name'];
                $file_name = $_FILES['project_img']['name'];
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                
                $allowed_img_exts = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
                if (in_array($file_ext, $allowed_img_exts)) {
                    $new_filename = 'project_' . time() . '_' . rand(100, 999) . '.' . $file_ext;
                    $upload_path = 'images/' . $new_filename;
                    
                    if (move_uploaded_file($file_tmp, $upload_path)) {
                        // Delete old project image if exists
                        if (!empty($image_path) && file_exists($image_path)) {
                            unlink($image_path);
                        }
                        $image_path = $upload_path;
                    }
                }
            }

            // Update Database
            $upd_stmt = $pdo->prepare("UPDATE projects SET title = ?, description = ?, image_path = ?, project_link = ?, technologies = ? WHERE id = ?");
            $upd_stmt->execute([$title, $description, $image_path, $project_link, $technologies, $proj_id]);
            
            set_notification("Project details updated successfully.", "success", "projects");
        } catch (PDOException $e) {
            set_notification("Database error: " . $e->getMessage(), "danger", "projects");
        }
    }

    // 4. DELETE PROJECT
    if ($action === 'delete_project') {
        $proj_id = intval($_POST['project_id']);

        try {
            // Fetch image path to delete from disk
            $img_stmt = $pdo->prepare("SELECT image_path FROM projects WHERE id = ?");
            $img_stmt->execute([$proj_id]);
            $img_path = $img_stmt->fetchColumn();

            if (!empty($img_path) && file_exists($img_path)) {
                unlink($img_path);
            }

            $del_stmt = $pdo->prepare("DELETE FROM projects WHERE id = ?");
            $del_stmt->execute([$proj_id]);

            set_notification("Project deleted successfully.", "success", "projects");
        } catch (PDOException $e) {
            set_notification("Database error: " . $e->getMessage(), "danger", "projects");
        }
    }

    // 5. UPLOAD DOCUMENT
    if ($action === 'add_document') {
        $title = trim($_POST['title'] ?? '');
        $file_type = trim($_POST['file_type'] ?? 'Resume');
        
        if (empty($title)) {
            set_notification("Document title is required.", "danger", "documents");
        }

        if (isset($_FILES['doc_file']) && $_FILES['doc_file']['error'] === UPLOAD_ERR_OK) {
            $file_tmp = $_FILES['doc_file']['tmp_name'];
            $file_name = $_FILES['doc_file']['name'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            
            // Allow all file types (empty extension fallback is handled safely)
            $new_filename = 'doc_' . time() . '_' . rand(100, 999) . ($file_ext ? '.' . $file_ext : '');
            $upload_path = 'documents/' . $new_filename;
            
            if (move_uploaded_file($file_tmp, $upload_path)) {
                try {
                    $ins_stmt = $pdo->prepare("INSERT INTO documents (title, file_path, file_type) VALUES (?, ?, ?)");
                    $ins_stmt->execute([$title, $upload_path, $file_type]);
                    
                    set_notification("Document uploaded successfully.", "success", "documents");
                } catch (PDOException $e) {
                    set_notification("Database error: " . $e->getMessage(), "danger", "documents");
                }
            } else {
                set_notification("Failed to save uploaded file.", "danger", "documents");
            }
        } else {
            set_notification("Please select a file to upload.", "danger", "documents");
        }
    }

    // 6. DELETE DOCUMENT
    if ($action === 'delete_document') {
        $doc_id = intval($_POST['document_id']);

        try {
            $file_stmt = $pdo->prepare("SELECT file_path FROM documents WHERE id = ?");
            $file_stmt->execute([$doc_id]);
            $file_path = $file_stmt->fetchColumn();

            if (!empty($file_path) && file_exists($file_path)) {
                unlink($file_path);
            }

            $del_stmt = $pdo->prepare("DELETE FROM documents WHERE id = ?");
            $del_stmt->execute([$doc_id]);

            set_notification("Document deleted successfully.", "success", "documents");
        } catch (PDOException $e) {
            set_notification("Database error: " . $e->getMessage(), "danger", "documents");
        }
    }

    // 7. UPLOAD PHOTO TO GALLERY
    if ($action === 'add_gallery') {
        $title = trim($_POST['title'] ?? '');

        if (isset($_FILES['gallery_img']) && $_FILES['gallery_img']['error'] === UPLOAD_ERR_OK) {
            $file_tmp = $_FILES['gallery_img']['tmp_name'];
            $file_name = $_FILES['gallery_img']['name'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            
            $allowed_img_exts = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
            if (in_array($file_ext, $allowed_img_exts)) {
                $new_filename = 'gallery_' . time() . '_' . rand(100, 999) . '.' . $file_ext;
                $upload_path = 'images/' . $new_filename;
                
                if (move_uploaded_file($file_tmp, $upload_path)) {
                    try {
                        $ins_stmt = $pdo->prepare("INSERT INTO gallery (title, image_path) VALUES (?, ?)");
                        $ins_stmt->execute([$title, $upload_path]);
                        
                        set_notification("Photo uploaded to gallery successfully.", "success", "gallery");
                    } catch (PDOException $e) {
                        set_notification("Database error: " . $e->getMessage(), "danger", "gallery");
                    }
                } else {
                    set_notification("Failed to save image.", "danger", "gallery");
                }
            } else {
                set_notification("Invalid file type. Only JPG, PNG, WEBP, GIF allowed.", "danger", "gallery");
            }
        } else {
            set_notification("Please select an image to upload.", "danger", "gallery");
        }
    }

    // 8. DELETE PHOTO FROM GALLERY
    if ($action === 'delete_gallery') {
        $photo_id = intval($_POST['photo_id']);

        try {
            $img_stmt = $pdo->prepare("SELECT image_path FROM gallery WHERE id = ?");
            $img_stmt->execute([$photo_id]);
            $img_path = $img_stmt->fetchColumn();

            if (!empty($img_path) && file_exists($img_path)) {
                unlink($img_path);
            }

            $del_stmt = $pdo->prepare("DELETE FROM gallery WHERE id = ?");
            $del_stmt->execute([$photo_id]);

            set_notification("Photo deleted from gallery.", "success", "gallery");
        } catch (PDOException $e) {
            set_notification("Database error: " . $e->getMessage(), "danger", "gallery");
        }
    }

    // 9. DELETE MESSAGE OR MARK AS READ
    if ($action === 'delete_message') {
        $msg_id = intval($_POST['message_id']);
        try {
            $del_stmt = $pdo->prepare("DELETE FROM messages WHERE id = ?");
            $del_stmt->execute([$msg_id]);
            set_notification("Message deleted successfully.", "success", "messages");
        } catch (PDOException $e) {
            set_notification("Database error: " . $e->getMessage(), "danger", "messages");
        }
    }

    if ($action === 'mark_read') {
        $msg_id = intval($_POST['message_id']);
        try {
            $upd_stmt = $pdo->prepare("UPDATE messages SET status = 'read' WHERE id = ?");
            $upd_stmt->execute([$msg_id]);
            set_notification("Message marked as read.", "success", "messages");
        } catch (PDOException $e) {
            set_notification("Database error: " . $e->getMessage(), "danger", "messages");
        }
    }

    // 10. SECURITY SETTINGS
    if ($action === 'update_security') {
        $username = trim($_POST['username'] ?? '');
        $new_pass = $_POST['new_password'] ?? '';
        $confirm_pass = $_POST['confirm_password'] ?? '';
        $admin_id = $_SESSION['admin_user_id'];

        if (empty($username)) {
            set_notification("Username cannot be empty.", "danger", "security");
        }

        try {
            // Check if username is taken by another user
            $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM admins WHERE username = ? AND id != ?");
            $check_stmt->execute([$username, $admin_id]);
            if ($check_stmt->fetchColumn() > 0) {
                set_notification("Username is already taken.", "danger", "security");
            }

            // Simple details update
            $upd_stmt = $pdo->prepare("UPDATE admins SET username = ? WHERE id = ?");
            $upd_stmt->execute([$username, $admin_id]);
            $_SESSION['admin_username'] = $username;

            // Password update if filled
            if (!empty($new_pass)) {
                if ($new_pass !== $confirm_pass) {
                    set_notification("New passwords do not match.", "danger", "security");
                }
                
                if (strlen($new_pass) < 6) {
                    set_notification("Password must be at least 6 characters.", "danger", "security");
                }

                $pass_hash = md5($new_pass);
                $pass_stmt = $pdo->prepare("UPDATE admins SET password = ? WHERE id = ?");
                $pass_stmt->execute([$pass_hash, $admin_id]);
            }

            set_notification("Security settings updated successfully.", "success", "security");
        } catch (PDOException $e) {
            set_notification("Database error: " . $e->getMessage(), "danger", "security");
        }
    }

    // 11. MANAGE SKILLS
    if ($action === 'add_skill') {
        $name = trim($_POST['name'] ?? '');
        $percentage = intval($_POST['percentage'] ?? 0);

        if (empty($name)) {
            set_notification("Skill name cannot be empty.", "danger", "skills");
        }
        if ($percentage < 1 || $percentage > 100) {
            set_notification("Percentage must be between 1 and 100.", "danger", "skills");
        }

        try {
            $ins_stmt = $pdo->prepare("INSERT INTO `skills` (`name`, `percentage`) VALUES (?, ?)");
            $ins_stmt->execute([$name, $percentage]);
            set_notification("Skill added successfully.", "success", "skills");
        } catch (PDOException $e) {
            set_notification("Database error: " . $e->getMessage(), "danger", "skills");
        }
    }

    if ($action === 'delete_skill') {
        $skill_id = intval($_POST['skill_id']);

        try {
            $del_stmt = $pdo->prepare("DELETE FROM `skills` WHERE id = ?");
            $del_stmt->execute([$skill_id]);
            set_notification("Skill deleted successfully.", "success", "skills");
        } catch (PDOException $e) {
            set_notification("Database error: " . $e->getMessage(), "danger", "skills");
        }
    }

    // 12. MANAGE SOCIAL LINKS
    if ($action === 'add_social_link') {
        $platform = trim($_POST['platform'] ?? '');
        $url = trim($_POST['url'] ?? '');

        if (empty($platform) || empty($url)) {
            set_notification("Both platform name and profile link URL are required.", "danger", "profile");
        } else {
            try {
                $ins_stmt = $pdo->prepare("INSERT INTO `social_links` (`platform`, `url`) VALUES (?, ?)");
                $ins_stmt->execute([$platform, $url]);
                set_notification("Social link added successfully.", "success", "profile");
            } catch (PDOException $e) {
                set_notification("Database error: " . $e->getMessage(), "danger", "profile");
            }
        }
    }

    if ($action === 'delete_social_link') {
        $link_id = intval($_POST['link_id']);

        try {
            $del_stmt = $pdo->prepare("DELETE FROM `social_links` WHERE id = ?");
            $del_stmt->execute([$link_id]);
            set_notification("Social link deleted successfully.", "success", "profile");
        } catch (PDOException $e) {
            set_notification("Database error: " . $e->getMessage(), "danger", "profile");
        }
    }
}

// ----------------------------------------------------
// DATABASE QUERIES FOR DATA RENDERING
// ----------------------------------------------------

// Profile fetch
$stmt = $pdo->query("SELECT * FROM profile_settings WHERE id = 1");
$profile = $stmt->fetch();

// User Security info
$u_stmt = $pdo->prepare("SELECT username, email FROM admins WHERE id = ?");
$u_stmt->execute([$_SESSION['admin_user_id']]);
$user_info = $u_stmt->fetch();

// Dashboard Stats
$stats = [];
try {
    $stats['projects'] = $pdo->query("SELECT COUNT(*) FROM projects")->fetchColumn();
    $stats['documents'] = $pdo->query("SELECT COUNT(*) FROM documents")->fetchColumn();
    $stats['gallery'] = $pdo->query("SELECT COUNT(*) FROM gallery")->fetchColumn();
    $stats['messages'] = $pdo->query("SELECT COUNT(*) FROM messages")->fetchColumn();
    $stats['unread_messages'] = $pdo->query("SELECT COUNT(*) FROM messages WHERE status='unread'")->fetchColumn();
} catch (PDOException $e) {
    $stats = ['projects' => 0, 'documents' => 0, 'gallery' => 0, 'messages' => 0, 'unread_messages' => 0];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Portfolio Panel</title>
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>

    <!-- Sidebar Navigation -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <polygon points="12 2 2 7 12 12 22 7 12 2"></polygon>
                    <polyline points="2 17 12 22 22 17"></polyline>
                    <polyline points="2 12 12 17 22 12"></polyline>
                </svg>
                <span>Dashboard</span>
            </div>
        </div>
        
        <ul class="sidebar-menu">
            <li>
                <a href="admin.php?tab=dashboard" class="sidebar-link <?php echo $tab === 'dashboard' ? 'active' : ''; ?>">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="7" height="9"></rect>
                        <rect x="14" y="3" width="7" height="5"></rect>
                        <rect x="14" y="12" width="7" height="9"></rect>
                        <rect x="3" y="16" width="7" height="5"></rect>
                    </svg>
                    <span>Overview</span>
                </a>
            </li>
            <li>
                <a href="admin.php?tab=profile" class="sidebar-link <?php echo $tab === 'profile' ? 'active' : ''; ?>">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                    <span>Edit Profile</span>
                </a>
            </li>
            <li>
                <a href="admin.php?tab=projects" class="sidebar-link <?php echo $tab === 'projects' ? 'active' : ''; ?>">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                        <line x1="8" y1="21" x2="16" y2="21"></line>
                        <line x1="12" y1="17" x2="12" y2="21"></line>
                    </svg>
                    <span>Projects</span>
                </a>
            </li>
            <li>
                <a href="admin.php?tab=documents" class="sidebar-link <?php echo $tab === 'documents' ? 'active' : ''; ?>">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                    </svg>
                    <span>Documents</span>
                </a>
            </li>
            <li>
                <a href="admin.php?tab=gallery" class="sidebar-link <?php echo $tab === 'gallery' ? 'active' : ''; ?>">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                        <circle cx="8.5" cy="8.5" r="1.5"></circle>
                        <polyline points="21 15 16 10 5 21"></polyline>
                    </svg>
                    <span>Photo Gallery</span>
                </a>
            </li>
            <li>
                <a href="admin.php?tab=messages" class="sidebar-link <?php echo $tab === 'messages' ? 'active' : ''; ?>">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                        <polyline points="22,6 12,13 2,6"></polyline>
                    </svg>
                    <span>Messages <?php if($stats['unread_messages'] > 0): ?><b style="color:var(--danger); font-size:0.95rem;">(<?php echo $stats['unread_messages']; ?>)</b><?php endif; ?></span>
                </a>
            </li>
            <li>
                <a href="admin.php?tab=skills" class="sidebar-link <?php echo $tab === 'skills' ? 'active' : ''; ?>">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="20" x2="18" y2="10"></line>
                        <line x1="12" y1="20" x2="12" y2="4"></line>
                        <line x1="6" y1="20" x2="6" y2="14"></line>
                    </svg>
                    <span>Skills</span>
                </a>
            </li>
            <li>
                <a href="admin.php?tab=security" class="sidebar-link <?php echo $tab === 'security' ? 'active' : ''; ?>">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                    </svg>
                    <span>Security</span>
                </a>
            </li>
        </ul>
        
        <div class="sidebar-footer">
            <a href="logout.php" class="sidebar-link" style="color: var(--danger);">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                    <polyline points="16 17 21 12 16 7"></polyline>
                    <line x1="21" y1="12" x2="9" y2="12"></line>
                </svg>
                <span>Logout</span>
            </a>
        </div>
    </aside>

    <!-- Main Content Panel -->
    <main class="admin-content">
        
        <!-- Header -->
        <div class="content-header">
            <h1 class="content-title">
                <?php 
                echo ucfirst($tab) === 'Profile' ? 'Edit Profile & Details' : (ucfirst($tab) === 'Gallery' ? 'Photo Gallery Manager' : ucfirst($tab) . ' Control Panel'); 
                ?>
            </h1>
            <div style="font-size: 0.9rem; color: var(--text-muted);">
                Logged in as: <strong style="color: #fff;"><?php echo htmlspecialchars($_SESSION['admin_username']); ?></strong>
                | <a href="index.php" target="_blank" style="color: var(--accent); font-weight:600;">View Live Site &rarr;</a>
            </div>
        </div>

        <!-- Session Notifications -->
        <?php if (!empty($notify_msg)): ?>
            <div class="alert alert-<?php echo $notify_type; ?>">
                <span><?php echo htmlspecialchars($notify_msg); ?></span>
                <button class="close-alert" onclick="this.parentElement.style.display='none';">&times;</button>
            </div>
        <?php endif; ?>

        <!-- ----------------------------------------------------
             TAB RENDERER
             ---------------------------------------------------- -->

        <!-- 1. OVERVIEW DASHBOARD TAB -->
        <?php if ($tab === 'dashboard'): ?>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect><line x1="8" y1="21" x2="16" y2="21"></line><line x1="12" y1="17" x2="12" y2="21"></line></svg>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['projects']; ?></h3>
                        <p>Total Projects</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['documents']; ?></h3>
                        <p>Total Documents</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['gallery']; ?></h3>
                        <p>Gallery Photos</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: <?php echo $stats['unread_messages'] > 0 ? 'rgba(239, 68, 68, 0.1)' : 'rgba(16, 185, 129, 0.1)'; ?>; color: <?php echo $stats['unread_messages'] > 0 ? 'var(--danger)' : 'var(--success)'; ?>;">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['unread_messages']; ?></h3>
                        <p>Unread Messages</p>
                    </div>
                </div>
            </div>

            <!-- Recent Messages Preview -->
            <div class="card-panel">
                <div class="panel-title" style="display:flex; justify-content:space-between; align-items:center;">
                    <span>Recent Contact Messages</span>
                    <a href="admin.php?tab=messages" style="font-size:0.85rem; color:var(--accent);">View All Messages &rarr;</a>
                </div>
                
                <?php 
                try {
                    $msg_stmt = $pdo->query("SELECT * FROM messages ORDER BY id DESC LIMIT 3");
                    $recent_msgs = $msg_stmt->fetchAll();
                } catch(PDOException $e) {
                    $recent_msgs = [];
                }
                ?>
                
                <?php if(!empty($recent_msgs)): ?>
                    <div class="table-responsive">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Sender</th>
                                    <th>Subject</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($recent_msgs as $msg): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($msg['name']); ?></strong><br>
                                            <span style="font-size:0.8rem; color:var(--text-muted);"><?php echo htmlspecialchars($msg['email']); ?></span>
                                        </td>
                                        <td><?php echo htmlspecialchars($msg['subject']); ?></td>
                                        <td><?php echo date('M d, Y h:i A', strtotime($msg['created_at'])); ?></td>
                                        <td>
                                            <span class="badge badge-<?php echo $msg['status']; ?>">
                                                <?php echo ucfirst($msg['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p style="color:var(--text-muted); text-align:center; padding: 20px 0;">No messages received yet.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- 2. EDIT PROFILE TAB -->
        <?php if ($tab === 'profile'): ?>
            <div class="card-panel">
                <form action="admin.php?tab=profile" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="update_profile">
                    
                    <div class="panel-title">General Info & Social Links</div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="full_name">Full Name *</label>
                            <input type="text" name="full_name" id="full_name" class="form-control" required value="<?php echo htmlspecialchars($profile['full_name']); ?>">
                        </div>
                        <div class="form-group">
                            <label for="title">Title / Headline</label>
                            <input type="text" name="title" id="title" class="form-control" placeholder="e.g. Full-Stack Developer" value="<?php echo htmlspecialchars($profile['title'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="email">Contact Email</label>
                            <input type="email" name="email" id="email" class="form-control" value="<?php echo htmlspecialchars($profile['email'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="phone">Contact Phone</label>
                            <input type="text" name="phone" id="phone" class="form-control" value="<?php echo htmlspecialchars($profile['phone'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="location">Location</label>
                        <input type="text" name="location" id="location" class="form-control" placeholder="e.g. San Francisco, CA" value="<?php echo htmlspecialchars($profile['location'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="bio_short">Short Intro (displayed on homepage) *</label>
                        <textarea name="bio_short" id="bio_short" class="form-control" rows="3" required><?php echo htmlspecialchars($profile['bio_short'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="bio_long">Detailed Bio (displayed on about page)</label>
                        <textarea name="bio_long" id="bio_long" class="form-control" rows="6"><?php echo htmlspecialchars($profile['bio_long'] ?? ''); ?></textarea>
                    </div>

                    <!-- Social Links are managed dynamically below -->

                    <div class="panel-title" style="margin-top: 40px;">Profile Assets</div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="profile_pic">Profile Picture (JPG, PNG, WEBP)</label>
                            <div class="file-input-wrapper">
                                <?php if (!empty($profile['profile_pic']) && file_exists($profile['profile_pic'])): ?>
                                    <img src="<?php echo htmlspecialchars($profile['profile_pic']); ?>" alt="Profile Preview" class="file-preview">
                                <?php endif; ?>
                                <input type="file" name="profile_pic" id="profile_pic" class="form-control" accept="image/*">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="cv_file">Resume / CV Document (PDF, DOCX)</label>
                            <div class="file-input-wrapper">
                                <?php if (!empty($profile['cv_file']) && file_exists($profile['cv_file'])): ?>
                                    <span style="font-size:0.85rem; background:rgba(255,255,255,0.05); padding:6px 12px; border-radius:4px; border:1px solid var(--border-color);">
                                        File Uploaded
                                    </span>
                                <?php endif; ?>
                                <input type="file" name="cv_file" id="cv_file" class="form-control" accept=".pdf,.doc,.docx">
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary" style="margin-top: 20px;">
                        Save Profile Settings
                    </button>
                </form>
            </div>

            <!-- DYNAMIC SOCIAL LINKS MANAGER -->
            <div class="card-panel" style="margin-top: 30px;">
                <div class="panel-title">Manage Social Links</div>

                <!-- Form to Add a Link -->
                <form action="admin.php?tab=profile" method="POST" style="margin-bottom: 30px; border-bottom: 1px dashed var(--border-color); padding-bottom: 25px;">
                    <input type="hidden" name="action" value="add_social_link">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="platform_name">Platform Name *</label>
                            <input type="text" name="platform" id="platform_name" class="form-control" required placeholder="e.g. GitHub, LinkedIn, Instagram, YouTube, Behance">
                        </div>
                        <div class="form-group">
                            <label for="platform_url">Profile Link URL *</label>
                            <input type="url" name="url" id="platform_url" class="form-control" required placeholder="https://...">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">Add Social Link</button>
                </form>

                <!-- List of Current Links -->
                <div class="panel-title" style="font-size: 1.1rem; margin-bottom: 15px;">Active Social Profiles</div>
                <?php 
                try {
                    $soc_list_stmt = $pdo->query("SELECT * FROM `social_links` ORDER BY id ASC");
                    $all_socials = $soc_list_stmt->fetchAll();
                } catch(PDOException $e) {
                    $all_socials = [];
                }
                ?>
                <?php if(!empty($all_socials)): ?>
                    <div class="table-responsive">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th style="width: 50px;">Icon</th>
                                    <th>Platform</th>
                                    <th>Profile URL</th>
                                    <th style="width: 100px; text-align: right;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($all_socials as $sl): ?>
                                    <tr>
                                        <td>
                                            <span style="color: var(--primary); display: inline-flex; align-items: center; justify-content: center;">
                                                <?php echo get_social_icon($sl['platform']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($sl['platform']); ?></strong>
                                        </td>
                                        <td>
                                            <a href="<?php echo htmlspecialchars($sl['url']); ?>" target="_blank" style="color: var(--accent);"><?php echo htmlspecialchars($sl['url']); ?></a>
                                        </td>
                                        <td style="text-align: right;">
                                            <form action="admin.php?tab=profile" method="POST" onsubmit="return confirm('Are you sure you want to delete this social link?');">
                                                <input type="hidden" name="action" value="delete_social_link">
                                                <input type="hidden" name="link_id" value="<?php echo $sl['id']; ?>">
                                                <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p style="color: var(--text-muted); text-align: center; padding: 15px 0;">No social profiles linked yet.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- 3. MANAGE PROJECTS TAB -->
        <?php if ($tab === 'projects'): ?>
            <?php 
            // Check if editing a project
            $edit_proj = null;
            if (isset($_GET['edit'])) {
                $edit_id = intval($_GET['edit']);
                $pe_stmt = $pdo->prepare("SELECT * FROM projects WHERE id = ?");
                $pe_stmt->execute([$edit_id]);
                $edit_proj = $pe_stmt->fetch();
            }
            ?>

            <?php if ($edit_proj): ?>
                <!-- EDIT PROJECT PANEL -->
                <div class="card-panel">
                    <div class="panel-title" style="display:flex; justify-content:space-between; align-items:center;">
                        <span>Edit Project: <?php echo htmlspecialchars($edit_proj['title']); ?></span>
                        <a href="admin.php?tab=projects" class="btn btn-secondary btn-sm">&larr; Back to List</a>
                    </div>

                    <form action="admin.php?tab=projects" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="edit_project">
                        <input type="hidden" name="project_id" value="<?php echo $edit_proj['id']; ?>">

                        <div class="form-group">
                            <label for="edit_title">Project Title *</label>
                            <input type="text" name="title" id="edit_title" class="form-control" required value="<?php echo htmlspecialchars($edit_proj['title']); ?>">
                        </div>

                        <div class="form-group">
                            <label for="edit_description">Description</label>
                            <textarea name="description" id="edit_description" class="form-control" rows="4"><?php echo htmlspecialchars($edit_proj['description'] ?? ''); ?></textarea>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="edit_technologies">Technologies (comma separated)</label>
                                <input type="text" name="technologies" id="edit_technologies" class="form-control" placeholder="e.g. PHP, MySQL, Javascript, CSS" value="<?php echo htmlspecialchars($edit_proj['technologies'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label for="edit_project_link">Project URL / Live Link</label>
                                <input type="url" name="project_link" id="edit_project_link" class="form-control" placeholder="https://example.com" value="<?php echo htmlspecialchars($edit_proj['project_link'] ?? ''); ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="edit_project_img">Project Image (leave blank to keep current)</label>
                            <div class="file-input-wrapper">
                                <?php if (!empty($edit_proj['image_path']) && file_exists($edit_proj['image_path'])): ?>
                                    <img src="<?php echo htmlspecialchars($edit_proj['image_path']); ?>" alt="Project Preview" class="file-preview">
                                <?php endif; ?>
                                <input type="file" name="project_img" id="edit_project_img" class="form-control" accept="image/*">
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary" style="margin-top: 15px;">Update Project</button>
                    </form>
                </div>
            <?php else: ?>
                <!-- ADD PROJECT PANEL -->
                <div class="card-panel">
                    <div class="panel-title">Add New Project</div>
                    <form action="admin.php?tab=projects" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="add_project">

                        <div class="form-group">
                            <label for="title">Project Title *</label>
                            <input type="text" name="title" id="title" class="form-control" required placeholder="e.g. Personal Portfolio Website">
                        </div>

                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea name="description" id="description" class="form-control" placeholder="Describe the project scope, challenges, and solutions..." rows="4"></textarea>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="technologies">Technologies (comma separated)</label>
                                <input type="text" name="technologies" id="technologies" class="form-control" placeholder="e.g. HTML5, CSS3, Javascript, AJAX">
                            </div>
                            <div class="form-group">
                                <label for="project_link">Project URL / Live Link</label>
                                <input type="url" name="project_link" id="project_link" class="form-control" placeholder="https://github.com/...">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="project_img">Project Showcase Image</label>
                            <input type="file" name="project_img" id="project_img" class="form-control" accept="image/*">
                        </div>

                        <button type="submit" class="btn btn-primary" style="margin-top: 15px;">Upload & Save Project</button>
                    </form>
                </div>

                <!-- PROJECT LIST -->
                <div class="card-panel">
                    <div class="panel-title">Existing Projects</div>
                    
                    <?php 
                    try {
                        $p_list = $pdo->query("SELECT * FROM projects ORDER BY id DESC");
                        $all_projects = $p_list->fetchAll();
                    } catch(PDOException $e) {
                        $all_projects = [];
                    }
                    ?>

                    <?php if(!empty($all_projects)): ?>
                        <div class="table-responsive">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th style="width:100px;">Image</th>
                                        <th>Project Details</th>
                                        <th>Technologies</th>
                                        <th style="width:180px; text-align:right;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($all_projects as $proj): ?>
                                        <tr>
                                            <td>
                                                <?php if(!empty($proj['image_path']) && file_exists($proj['image_path'])): ?>
                                                    <img src="<?php echo htmlspecialchars($proj['image_path']); ?>" alt="Project Preview" style="width:80px; height:50px; object-fit:cover; border-radius:4px; border:1px solid var(--border-color);">
                                                <?php else: ?>
                                                    <div style="width:80px; height:50px; background:rgba(255,255,255,0.03); border-radius:4px; display:flex; align-items:center; justify-content:center; font-size:0.7rem; color:var(--text-muted); border:1px dashed var(--border-color);">
                                                        No Image
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <strong style="font-size:1.05rem;"><?php echo htmlspecialchars($proj['title']); ?></strong><br>
                                                <?php if(!empty($proj['project_link'])): ?>
                                                    <a href="<?php echo htmlspecialchars($proj['project_link']); ?>" target="_blank" style="font-size:0.8rem; color:var(--accent);"><?php echo htmlspecialchars($proj['project_link']); ?></a>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span style="font-size:0.85rem; color:var(--text-muted);"><?php echo htmlspecialchars($proj['technologies']); ?></span>
                                            </td>
                                            <td style="text-align:right;">
                                                <div style="display:inline-flex; gap:10px;">
                                                    <a href="admin.php?tab=projects&edit=<?php echo $proj['id']; ?>" class="btn btn-secondary btn-sm">Edit</a>
                                                    
                                                    <form action="admin.php?tab=projects" method="POST" onsubmit="return confirm('Are you sure you want to delete this project?');">
                                                        <input type="hidden" name="action" value="delete_project">
                                                        <input type="hidden" name="project_id" value="<?php echo $proj['id']; ?>">
                                                        <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p style="color:var(--text-muted); text-align:center; padding: 20px 0;">No projects created yet.</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <!-- 4. MANAGE DOCUMENTS TAB -->
        <?php if ($tab === 'documents'): ?>
            <div class="card-panel">
                <div class="panel-title">Upload New Document / Certificate</div>
                
                <form action="admin.php?tab=documents" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="add_document">

                    <div class="form-group">
                        <label for="doc_title">Document Title *</label>
                        <input type="text" name="title" id="doc_title" class="form-control" required placeholder="e.g. Google Analytics Certification">
                    </div>

                    <div class="form-group">
                        <label for="file_type">Document Type / Label</label>
                        <select name="file_type" id="file_type" class="form-control">
                            <option value="Resume">Resume</option>
                            <option value="Certificate">Certificate</option>
                            <option value="Degree">Degree / Transcript</option>
                            <option value="Letter of Recommendation">Recommendation</option>
                            <option value="Other Document">Other</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="doc_file">Document File *</label>
                        <input type="file" name="doc_file" id="doc_file" class="form-control" required>
                    </div>

                    <button type="submit" class="btn btn-primary" style="margin-top: 15px;">Upload Document</button>
                </form>
            </div>

            <!-- DOCUMENTS LIST -->
            <div class="card-panel">
                <div class="panel-title">Uploaded Documents</div>

                <?php 
                try {
                    $d_list = $pdo->query("SELECT * FROM documents ORDER BY id DESC");
                    $all_documents = $d_list->fetchAll();
                } catch(PDOException $e) {
                    $all_documents = [];
                }
                ?>

                <?php if(!empty($all_documents)): ?>
                    <div class="table-responsive">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Document Title</th>
                                    <th>Type</th>
                                    <th>Uploaded Date</th>
                                    <th style="width:120px; text-align:right;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($all_documents as $doc): ?>
                                    <tr>
                                        <td>
                                            <strong style="font-size:1.05rem;"><?php echo htmlspecialchars($doc['title']); ?></strong><br>
                                            <a href="<?php echo htmlspecialchars($doc['file_path']); ?>" download style="font-size:0.8rem; color:var(--accent);">Download File &darr;</a>
                                        </td>
                                        <td>
                                            <span style="font-size:0.9rem;"><?php echo htmlspecialchars($doc['file_type']); ?></span>
                                        </td>
                                        <td>
                                            <span style="font-size:0.85rem; color:var(--text-muted);"><?php echo date('M d, Y', strtotime($doc['uploaded_at'])); ?></span>
                                        </td>
                                        <td style="text-align:right;">
                                            <form action="admin.php?tab=documents" method="POST" onsubmit="return confirm('Are you sure you want to delete this document?');">
                                                <input type="hidden" name="action" value="delete_document">
                                                <input type="hidden" name="document_id" value="<?php echo $doc['id']; ?>">
                                                <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p style="color:var(--text-muted); text-align:center; padding: 20px 0;">No documents uploaded yet.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- 5. MANAGE GALLERY PHOTOS TAB -->
        <?php if ($tab === 'gallery'): ?>
            <div class="card-panel">
                <div class="panel-title">Upload Photo to Gallery</div>
                
                <form action="admin.php?tab=gallery" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="add_gallery">

                    <div class="form-group">
                        <label for="gal_title">Photo Title / Caption</label>
                        <input type="text" name="title" id="gal_title" class="form-control" placeholder="e.g. Working on UI Wireframes">
                    </div>

                    <div class="form-group">
                        <label for="gallery_img">Photo File (JPG, PNG, WEBP, GIF) *</label>
                        <input type="file" name="gallery_img" id="gallery_img" class="form-control" required accept="image/*">
                    </div>

                    <button type="submit" class="btn btn-primary" style="margin-top: 15px;">Upload Photo</button>
                </form>
            </div>

            <!-- PHOTOS GALLERY DISPLAY -->
            <div class="card-panel">
                <div class="panel-title">Current Gallery Photos</div>

                <?php 
                try {
                    $g_list = $pdo->query("SELECT * FROM gallery ORDER BY id DESC");
                    $all_photos = $g_list->fetchAll();
                } catch(PDOException $e) {
                    $all_photos = [];
                }
                ?>

                <?php if(!empty($all_photos)): ?>
                    <div class="admin-gallery-grid">
                        <?php foreach($all_photos as $photo): ?>
                            <div class="admin-gallery-item">
                                <?php if(file_exists($photo['image_path'])): ?>
                                    <img src="<?php echo htmlspecialchars($photo['image_path']); ?>" alt="Gallery Image">
                                    <form action="admin.php?tab=gallery" method="POST" onsubmit="return confirm('Delete this image from gallery?');">
                                        <input type="hidden" name="action" value="delete_gallery">
                                        <input type="hidden" name="photo_id" value="<?php echo $photo['id']; ?>">
                                        <button type="submit" class="admin-gallery-delete" title="Delete Photo">
                                            &times;
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p style="color:var(--text-muted); text-align:center; padding: 20px 0;">No gallery photos uploaded yet.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- 6. CONTACT MESSAGES TAB -->
        <?php if ($tab === 'messages'): ?>
            <div class="card-panel">
                <div class="panel-title">Inbox Messages</div>

                <?php 
                try {
                    $m_list = $pdo->query("SELECT * FROM messages ORDER BY id DESC");
                    $all_messages = $m_list->fetchAll();
                } catch(PDOException $e) {
                    $all_messages = [];
                }
                ?>

                <?php if(!empty($all_messages)): ?>
                    <div class="table-responsive">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Sender Details</th>
                                    <th>Subject & Message Content</th>
                                    <th>Received Date</th>
                                    <th>Status</th>
                                    <th style="width:180px; text-align:right;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($all_messages as $msg): ?>
                                    <tr style="<?php echo $msg['status'] === 'unread' ? 'background: rgba(124, 77, 255, 0.03);' : ''; ?>">
                                        <td style="vertical-align: top;">
                                            <strong style="font-size:1rem;"><?php echo htmlspecialchars($msg['name']); ?></strong><br>
                                            <a href="mailto:<?php echo htmlspecialchars($msg['email']); ?>" style="font-size:0.85rem; color:var(--accent);"><?php echo htmlspecialchars($msg['email']); ?></a>
                                        </td>
                                        <td style="vertical-align: top;">
                                            <div style="font-weight:600; font-size:1rem; margin-bottom:5px;"><?php echo htmlspecialchars($msg['subject']); ?></div>
                                            <div style="font-size:0.9rem; color:#e2e8f0; white-space:pre-wrap; border-left:2px solid var(--border-color); padding-left:10px; margin-top:8px;"><?php echo htmlspecialchars($msg['message']); ?></div>
                                        </td>
                                        <td style="vertical-align: top;">
                                            <span style="font-size:0.85rem; color:var(--text-muted);"><?php echo date('M d, Y h:i A', strtotime($msg['created_at'])); ?></span>
                                        </td>
                                        <td style="vertical-align: top;">
                                            <span class="badge badge-<?php echo $msg['status']; ?>">
                                                <?php echo ucfirst($msg['status']); ?>
                                            </span>
                                        </td>
                                        <td style="vertical-align: top; text-align:right;">
                                            <div style="display:inline-flex; gap:10px;">
                                                <?php if($msg['status'] === 'unread'): ?>
                                                    <form action="admin.php?tab=messages" method="POST">
                                                        <input type="hidden" name="action" value="mark_read">
                                                        <input type="hidden" name="message_id" value="<?php echo $msg['id']; ?>">
                                                        <button type="submit" class="btn btn-success btn-sm">Read</button>
                                                    </form>
                                                <?php endif; ?>
                                                
                                                <form action="admin.php?tab=messages" method="POST" onsubmit="return confirm('Are you sure you want to delete this message?');">
                                                    <input type="hidden" name="action" value="delete_message">
                                                    <input type="hidden" name="message_id" value="<?php echo $msg['id']; ?>">
                                                    <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p style="color:var(--text-muted); text-align:center; padding: 20px 0;">No messages in your inbox.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- 7. SECURITY SETTINGS TAB -->
        <?php if ($tab === 'security'): ?>
            <div class="card-panel">
                <div class="panel-title">Update Login Credentials</div>
                
                <form action="admin.php?tab=security" method="POST">
                    <input type="hidden" name="action" value="update_security">

                    <div class="form-group">
                        <label for="username">Username *</label>
                        <input type="text" name="username" id="username" class="form-control" required value="<?php echo htmlspecialchars($user_info['username']); ?>">
                    </div>

                    <div class="form-group">
                        <label for="email">Account Email</label>
                        <input type="email" class="form-control" disabled value="<?php echo htmlspecialchars($user_info['email']); ?>">
                        <span style="font-size:0.8rem; color:var(--text-muted); margin-top:4px; display:inline-block;">Email is for system reference.</span>
                    </div>

                    <div class="panel-title" style="margin-top:35px; border-bottom:1px dashed var(--border-color);">Change Password (leave blank if keeping current)</div>

                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <input type="password" name="new_password" id="new_password" class="form-control" placeholder="At least 6 characters">
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="Retype new password">
                    </div>

                    <button type="submit" class="btn btn-primary" style="margin-top: 15px;">Update Security Credentials</button>
                </form>
            </div>
        <?php endif; ?>

        <!-- 8. MANAGE SKILLS TAB -->
        <?php if ($tab === 'skills'): ?>
            <div class="card-panel">
                <div class="panel-title">Add New Skill</div>
                
                <form action="admin.php?tab=skills" method="POST">
                    <input type="hidden" name="action" value="add_skill">

                    <div class="form-row">
                        <div class="form-group">
                            <label for="skill_name">Skill Name *</label>
                            <input type="text" name="name" id="skill_name" class="form-control" required placeholder="e.g. React.js, PHP, Python">
                        </div>
                        <div class="form-group">
                            <label for="skill_percentage">Percentage (1 - 100) *</label>
                            <input type="number" name="percentage" id="skill_percentage" class="form-control" required min="1" max="100" placeholder="e.g. 90">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">Add Skill</button>
                </form>
            </div>

            <div class="card-panel">
                <div class="panel-title">Current Skills List</div>

                <?php 
                try {
                    $s_list = $pdo->query("SELECT * FROM `skills` ORDER BY percentage DESC");
                    $all_skills = $s_list->fetchAll();
                } catch(PDOException $e) {
                    $all_skills = [];
                }
                ?>

                <?php if(!empty($all_skills)): ?>
                    <div class="table-responsive">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Skill Name</th>
                                    <th>Percentage</th>
                                    <th style="width:120px; text-align:right;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($all_skills as $sk): ?>
                                    <tr>
                                        <td>
                                            <strong style="font-size:1.05rem;"><?php echo htmlspecialchars($sk['name']); ?></strong>
                                        </td>
                                        <td>
                                            <div style="display:flex; align-items:center; gap:10px;">
                                                <span><?php echo htmlspecialchars($sk['percentage']); ?>%</span>
                                                <div style="flex-grow:1; max-width:150px; height:6px; background:rgba(255,255,255,0.08); border-radius:3px; overflow:hidden;">
                                                    <div style="height:100%; background:var(--primary); width:<?php echo $sk['percentage']; ?>%;"></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td style="text-align:right;">
                                            <form action="admin.php?tab=skills" method="POST" onsubmit="return confirm('Are you sure you want to delete this skill?');">
                                                <input type="hidden" name="action" value="delete_skill">
                                                <input type="hidden" name="skill_id" value="<?php echo $sk['id']; ?>">
                                                <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p style="color:var(--text-muted); text-align:center; padding: 20px 0;">No skills added yet.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

    </main>

</body>
</html>
