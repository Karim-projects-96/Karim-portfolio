<?php
// admin_login.php - Admin authentication interface
session_start();
require_once 'db.php';

// Redirect to admin panel if already logged in
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: admin.php');
    exit;
}

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $error_message = 'Please enter both username and password.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM `admins` WHERE `username` = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user) {
                $authenticated = false;
                
                // 1. MD5 validation
                if (md5($password) === $user['password']) {
                    $authenticated = true;
                }
                // 2. Plain text fallback (in case database password was edited manually to a plaintext string)
                elseif ($password === $user['password']) {
                    $authenticated = true;
                    // Upgrade stored plaintext password to MD5 in the database
                    try {
                        $upd_stmt = $pdo->prepare("UPDATE `admins` SET `password` = ? WHERE `id` = ?");
                        $upd_stmt->execute([md5($password), $user['id']]);
                    } catch (PDOException $ex) {
                        // Suppress exception and log user in anyway
                    }
                }

                if ($authenticated) {
                    $_SESSION['admin_logged_in'] = true;
                    $_SESSION['admin_username'] = $user['username'];
                    $_SESSION['admin_user_id'] = $user['id'];
                    
                    header('Location: admin.php');
                    exit;
                } else {
                    $error_message = 'Invalid username or password.';
                }
            } else {
                $error_message = 'Invalid username or password.';
            }
        } catch (PDOException $e) {
            $error_message = 'Database error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | Portfolio Dashboard</title>
    <link rel="stylesheet" href="css/admin.css">
</head>
<body class="login-body">

    <div class="login-card">
        <div class="login-title">Admin Dashboard</div>
        <p class="login-subtitle">Sign in to manage your portfolio</p>

        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger" style="margin-bottom: 20px; font-size: 0.9rem; padding: 10px 15px;">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <form action="admin_login.php" method="POST">
            <div class="form-group" style="text-align: left;">
                <label for="username">Username</label>
                <input type="text" name="username" id="username" class="form-control" placeholder="Enter username" required value="<?php echo htmlspecialchars($username ?? ''); ?>">
            </div>

            <div class="form-group" style="text-align: left; margin-bottom: 25px;">
                <label for="password">Password</label>
                <input type="password" name="password" id="password" class="form-control" placeholder="Enter password" required>
            </div>

            <button type="submit" name="login" class="btn btn-primary" style="width: 100%; border-radius: 6px; justify-content: center; padding: 12px;">
                Login to Dashboard
            </button>
        </form>

        
    </div>

</body>
</html>
