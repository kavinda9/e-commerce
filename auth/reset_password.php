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
        
        // DEBUG: Check if token exists in table
        $checkStmt = $db->prepare("SELECT * FROM password_reset_tokens WHERE token = :token");
        $checkStmt->execute([':token' => $token]);
        $checkData = $checkStmt->fetch();
        error_log("DEBUG: Token lookup - exists: " . ($checkData ? 'YES' : 'NO'));
        if ($checkData) {
            error_log("DEBUG: Token data - user_id: {$checkData['user_id']}, used: {$checkData['used']}, expires_at: {$checkData['expires_at']}");
        }
        
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
        
        error_log("DEBUG: Full validation - passed: " . ($tokenData ? 'YES' : 'NO'));
        
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
                    
                    // Reset failed login attempts
                    $stmt = $db->prepare("UPDATE users SET failed_login_attempts = 0, account_locked_until = NULL WHERE user_id = :user_id");
                    $stmt->execute([':user_id' => $tokenData['user_id']]);
                    
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
    <title>Reset Password - ShopNet E-Commerce</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
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
        .reset-header h2 {
            color: #333;
            margin-bottom: 10px;
        }
        .reset-header p {
            color: #666;
            font-size: 14px;
        }
        .btn-reset {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            padding: 12px;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s;
        }
        .btn-reset:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }
        .form-control {
            padding: 12px;
            border-radius: 8px;
            border: 1px solid #ddd;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .alert {
            border-radius: 8px;
            border: none;
        }
        .back-link {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
        }
        .back-link:hover {
            color: #764ba2;
        }
        .password-requirements {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-top: 10px;
            font-size: 13px;
        }
        .password-requirements ul {
            margin: 5px 0 0 0;
            padding-left: 20px;
        }
        .password-requirements li {
            color: #666;
            margin: 3px 0;
        }
        .success-icon {
            font-size: 4rem;
            color: #28a745;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="reset-container">
        <div class="reset-card">
            <?php if ($success): ?>
                <!-- Success State -->
                <div class="text-center">
                    <i class="fas fa-check-circle success-icon"></i>
                    <h2>Password Reset Successful!</h2>
                    <div class="alert alert-success mt-3">
                        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                    </div>
                    <a href="login.php" class="btn btn-reset w-100 mt-3">
                        <i class="fas fa-sign-in-alt"></i> Go to Login
                    </a>
                </div>
            <?php else: ?>
                <!-- Reset Form State -->
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

                <?php if ($validToken): ?>
                    <form method="POST" action="" id="resetForm">
                        <div class="mb-3">
                            <label for="password" class="form-label">
                                <i class="fas fa-lock"></i> New Password
                            </label>
                            <input 
                                type="password" 
                                class="form-control" 
                                id="password" 
                                name="password" 
                                placeholder="Enter new password"
                                required
                                autofocus
                            >
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
                                placeholder="Confirm new password"
                                required
                            >
                        </div>

                        <div class="password-requirements">
                            <strong><i class="fas fa-info-circle"></i> Password Requirements:</strong>
                            <ul>
                                <li>At least 8 characters long</li>
                                <li>One uppercase letter (A-Z)</li>
                                <li>One lowercase letter (a-z)</li>
                                <li>One number (0-9)</li>
                                <li>One special character (@$!%*?&)</li>
                            </ul>
                        </div>

                        <button type="submit" class="btn btn-reset w-100 mt-3">
                            <i class="fas fa-check"></i> Reset Password
                        </button>
                    </form>
                <?php else: ?>
                    <div class="text-center">
                        <p class="text-danger mb-3">The reset link is invalid or has expired.</p>
                        <a href="forgot_password.php" class="btn btn-primary">
                            <i class="fas fa-redo"></i> Request New Reset Link
                        </a>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <hr class="my-4">

            <div class="text-center">
                <a href="login.php" class="back-link">
                    <i class="fas fa-arrow-left"></i> Back to Login
                </a>
            </div>
        </div>

        <div class="text-center mt-3">
            <small class="text-white">
                Group 9: FC222027, FC222041, FC222019, FC222034
            </small>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Client-side password validation
        document.getElementById('resetForm')?.addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
                return false;
            }
            
            // Check password strength
            const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/;
            if (!passwordRegex.test(password)) {
                e.preventDefault();
                alert('Password does not meet the requirements!');
                return false;
            }
        });
    </script>
</body>
</html>