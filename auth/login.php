<?php
require_once '../config/database.php';

// Redirect if already logged in
redirectIfLoggedIn();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password';
    } else {
        try {
            $db = Database::getInstance()->getConnection();
            
            // Get user by email
            $stmt = $db->prepare("SELECT * FROM users WHERE email = :email AND is_active = 1");
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch();
            
            if (!$user) {
                $error = 'Invalid email or password';
                logAudit(null, 'LOGIN_FAILED', 'user', null, "Failed login attempt for: $email");
            } else {
                // Check if account is locked
                if ($user['account_locked_until'] && strtotime($user['account_locked_until']) > time()) {
                    $error = 'Account is temporarily locked due to multiple failed login attempts. Please try again later.';
                } else {
                    // Verify password
                    if (verifyPassword($password, $user['password_hash'])) {
                        // Reset failed login attempts
                        $stmt = $db->prepare("UPDATE users SET failed_login_attempts = 0, last_failed_login = NULL, account_locked_until = NULL WHERE user_id = :user_id");
                        $stmt->execute([':user_id' => $user['user_id']]);
                        
                        // Set session variables - THIS IS CRITICAL!
                        startSecureSession();
                        session_regenerate_id(true); // Prevent session fixation
                        
                        $_SESSION['user_id'] = $user['user_id'];
                        $_SESSION['email'] = $user['email'];
                        $_SESSION['first_name'] = $user['first_name'];
                        $_SESSION['last_name'] = $user['last_name'];
                        $_SESSION['role'] = $user['role']; // ← THIS IS THE KEY LINE!
                        $_SESSION['is_active'] = $user['is_active'];
                        $_SESSION['last_activity'] = time();
                        
                        // Log successful login
                        logAudit($user['user_id'], 'LOGIN_SUCCESS', 'user', $user['user_id'], "User logged in successfully");
                        
                        // Redirect based on role
                        if ($user['role'] === 'admin') {
                            header('Location: ../admin/index.php');
                        } else {
                            header('Location: ../dashboard.php');
                        }
                        exit;
                    } else {
                        // Increment failed login attempts
                        $failedAttempts = $user['failed_login_attempts'] + 1;
                        $lockUntil = null;
                        
                        if ($failedAttempts >= MAX_LOGIN_ATTEMPTS) {
                            $lockUntil = date('Y-m-d H:i:s', time() + LOCKOUT_TIME);
                            $error = 'Too many failed login attempts. Account locked for 15 minutes.';
                        } else {
                            $error = 'Invalid email or password';
                        }
                        
                        $stmt = $db->prepare("UPDATE users SET failed_login_attempts = :attempts, last_failed_login = NOW(), account_locked_until = :lock_until WHERE user_id = :user_id");
                        $stmt->execute([
                            ':attempts' => $failedAttempts,
                            ':lock_until' => $lockUntil,
                            ':user_id' => $user['user_id']
                        ]);
                        
                        logAudit($user['user_id'], 'LOGIN_FAILED', 'user', $user['user_id'], "Invalid password attempt #$failedAttempts");
                    }
                }
            }
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
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
    <title>Login - Secure E-Commerce</title>
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
        .login-container {
            max-width: 450px;
            width: 100%;
        }
        .login-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            padding: 40px;
        }
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .login-header i {
            font-size: 3rem;
            color: #667eea;
            margin-bottom: 15px;
        }
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            padding: 12px;
            font-size: 16px;
            font-weight: 600;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .admin-hint {
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 15px;
            margin-top: 20px;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <i class="fas fa-shield-alt"></i>
                <h2>Secure Login</h2>
                <p class="text-muted">Enter your credentials to continue</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success" role="alert">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

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
                        value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                        required 
                        autofocus
                    >
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">
                        <i class="fas fa-lock"></i> Password
                    </label>
                    <input 
                        type="password" 
                        class="form-control" 
                        id="password" 
                        name="password" 
                        required
                    >
                </div>

                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="remember">
                    <label class="form-check-label" for="remember">
                        Remember me
                    </label>
                </div>

                <button type="submit" class="btn btn-login w-100">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
            </form>

            <div class="text-center mt-3">
                <a href="forgot_password.php" class="text-decoration-none">
                    <i class="fas fa-question-circle"></i> Forgot Password?
                </a>
            </div>

            <hr>

            <div class="text-center">
                <p class="mb-0">Don't have an account?</p>
                <a href="register.php" class="btn btn-outline-primary btn-sm mt-2">
                    <i class="fas fa-user-plus"></i> Create Account
                </a>
            </div>

            <!-- Admin Credentials Hint (Remove in production) -->
            <div class="admin-hint">
                <small>
                    <strong><i class="fas fa-info-circle"></i> Default Admin Credentials:</strong><br>
                    Email: <code>admin@ecommerce.com</code><br>
                    Password: <code>Admin@123</code>
                </small>
            </div>
        </div>

        <div class="text-center text-white mt-3">
            <small>
                <i class="fas fa-shield-alt"></i> Protected by advanced security measures
            </small>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>