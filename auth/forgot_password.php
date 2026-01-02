<?php
/**
 * Forgot Password
 * Group 9 - Secure E-Commerce System
 */

require_once '../config/database.php';
redirectIfLoggedIn();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitizeInput($_POST['email'] ?? '');
    
    if (empty($email)) {
        $error = 'Please enter your email address';
    } elseif (!validateEmail($email)) {
        $error = 'Invalid email format';
    } else {
        try {
            $db = Database::getInstance()->getConnection();
            
            // Check if user exists
            $stmt = $db->prepare("SELECT user_id, first_name FROM users WHERE email = :email AND is_active = 1");
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch();
            
            if ($user) {
                // Generate reset token
                $token = generateSecureToken();
                $expiresAt = date('Y-m-d H:i:s', time() + PASSWORD_RESET_EXPIRY);
                
                // Save token
                $stmt = $db->prepare("
                    INSERT INTO password_reset_tokens (user_id, token, expires_at)
                    VALUES (:user_id, :token, :expires_at)
                ");
                
                $stmt->execute([
                    ':user_id' => $user['user_id'],
                    ':token' => $token,
                    ':expires_at' => $expiresAt
                ]);
                
                logAudit($user['user_id'], 'PASSWORD_RESET_REQUESTED', 'user', $user['user_id']);
                
                // In production, send email here
                // For now, we'll show the reset link
                $resetLink = BASE_URL . "auth/reset_password.php?token=$token";
                $success = "Password reset link generated. In production, this would be sent to your email.<br><br>
                           <strong>Reset Link:</strong><br>
                           <a href='$resetLink' class='btn btn-sm btn-primary mt-2'>Reset Password</a>";
            } else {
                // Don't reveal if email exists (security best practice)
                $success = 'If your email is registered, you will receive a password reset link shortly.';
            }
        } catch (Exception $e) {
            error_log("Password reset error: " . $e->getMessage());
            $error = 'An error occurred. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Secure E-Commerce</title>
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
        .forgot-container {
            max-width: 450px;
            width: 100%;
        }
        .forgot-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            padding: 40px;
        }
        .forgot-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .forgot-header i {
            font-size: 3rem;
            color: #667eea;
            margin-bottom: 15px;
        }
        .btn-submit {
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
    <div class="forgot-container">
        <div class="forgot-card">
            <div class="forgot-header">
                <i class="fas fa-key"></i>
                <h2>Forgot Password?</h2>
                <p class="text-muted">Enter your email to reset your password</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <?php if (!$success): ?>
            <form method="POST" action="">
                <div class="mb-3">
                    <label for="email" class="form-label">
                        <i class="fas fa-envelope"></i> Email Address
                    </label>
                    <input 
                        type="email" 
                        class="form-control" 
                        id="email" 
                        name="email" 
                        required
                        autofocus
                    >
                </div>

                <button type="submit" class="btn btn-submit w-100">
                    <i class="fas fa-paper-plane"></i> Send Reset Link
                </button>
            </form>
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