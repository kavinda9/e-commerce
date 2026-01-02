<?php
/**
 * Reset Password
 * Group 9 - Secure E-Commerce System
 */

require_once '../config/database.php';
redirectIfLoggedIn();

$error = '';
$success = '';
$token = $_GET['token'] ?? '';
$validToken = false;

if (empty($token)) {
    $error = 'Invalid reset link';
} else {
    try {
        $db = Database::getInstance()->getConnection();
        
        // Verify token
        $stmt = $db->prepare("
            SELECT prt.*, u.user_id, u.email, u.first_name
            FROM password_reset_tokens prt
            JOIN users u ON prt.user_id = u.user_id
            WHERE prt.token = :token 
            AND prt.expires_at > NOW()
            AND prt.used = 0
            AND u.is_active = 1
        ");
        
        $stmt->execute([':token' => $token]);
        $tokenData = $stmt->fetch();
        
        if ($tokenData) {
            $validToken = true;
            
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $password = $_POST['password'] ?? '';
                $confirmPassword = $_POST['confirm_password'] ?? '';
                
                if (empty($password) || empty($confirmPassword)) {
                    $error = 'Please enter and confirm your password';
                } elseif ($password !== $confirmPassword) {
                    $error = 'Passwords do not match';
                } elseif (!validatePassword($password)) {
                    $error = 'Password must be at least 8 characters with uppercase, lowercase, number and special character';
                } else {
                    // Update password
                    $passwordHash = hashPassword($password);
                    
                    $stmt = $db->prepare("UPDATE users SET password_hash = :password_hash WHERE user_id = :user_id");
                    $stmt->execute([
                        ':password_hash' => $passwordHash,
                        ':user_id' => $tokenData['user_id']
                    ]);
                    
                    // Mark token as used
                    $stmt = $db->prepare("UPDATE password_reset_tokens SET used = 1 WHERE token_id = :token_id");
                    $stmt->execute([':token_id' => $tokenData['token_id']]);
                    
                    logAudit($tokenData['user_id'], 'PASSWORD_RESET_COMPLETED', 'user', $tokenData['user_id']);
                    
                    $success = 'Password reset successful! You can now login with your new password.';
                }
            }
        } else {
            $error = 'Invalid or expired reset link';
        }
    } catch (Exception $e) {
        error_log("Reset password error: " . $e->getMessage());
        $error = 'An error occurred. Please try again.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Secure E-Commerce</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .reset-container {
            max-width: 450px;
            width: 100%;
        }
        .reset-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            padding: 40px;
        }
        .reset-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .reset-header i {
            font-size: 3rem;
            color: #667eea;
            margin-bottom: 15px;
        }
        .btn-reset {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            padding: 12px;
            font-size: 16px;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="reset-container">
        <div class="reset-card">
            <div class="reset-header">
                <i class="fas fa-lock"></i>
                <h2>Reset Password</h2>
                <p class="text-muted">Enter your new password</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                </div>
                <div class="text-center mt-3">
                    <a href="login.php" class="btn btn-primary">
                        <i class="fas fa-sign-in-alt"></i> Go to Login
                    </a>
                </div>
            <?php elseif ($validToken): ?>
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="password" class="form-label">
                            <i class="fas fa-lock"></i> New Password
                        </label>
                        <input 
                            type="password" 
                            class="form-control" 
                            id="password" 
                            name="password" 
                            required
                            autofocus
                        >
                        <small class="text-muted">
                            Must be 8+ characters with uppercase, lowercase, number & special character
                        </small>
                    </div>

                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">
                            <i class="fas fa-lock"></i> Confirm New Password
                        </label>
                        <input 
                            type="password" 
                            class="form-control" 
                            id="confirm_password" 
                            name="confirm_password" 
                            required
                        >
                    </div>

                    <button type="submit" class="btn btn-reset w-100">
                        <i class="fas fa-check"></i> Reset Password
                    </button>
                </form>
            <?php else: ?>
                <div class="text-center">
                    <a href="forgot_password.php" class="btn btn-primary">
                        Request New Reset Link
                    </a>
                </div>
            <?php endif; ?>

            <hr>

            <div class="text-center">
                <a href="login.php" class="text-decoration-none">
                    <i class="fas fa-arrow-left"></i> Back to Login
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>