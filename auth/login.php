<?php
/**
 * User Login Page
 * Group 9 - Secure E-Commerce System
 */

require_once '../config/database.php';
startSecureSession(); // Start session first
redirectIfLoggedIn(); // Redirect if already logged in

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    try {
        $db = Database::getInstance()->getConnection();
        
        // Get user details
        $stmt = $db->prepare("
            SELECT user_id, email, password_hash, first_name, last_name, role, is_active, 
                   failed_login_attempts, account_locked_until
            FROM users 
            WHERE email = :email
        ");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();
        
        if (!$user) {
            logAudit(null, 'LOGIN_FAILED', null, null, "Email not found: $email");
            $error = 'Invalid email or password';
        } elseif ($user['account_locked_until'] && strtotime($user['account_locked_until']) > time()) {
            $remainingTime = strtotime($user['account_locked_until']) - time();
            logAudit($user['user_id'], 'LOGIN_BLOCKED', 'user', $user['user_id'], 'Account locked');
            $error = "Account locked. Try again in " . ceil($remainingTime / 60) . " minutes.";
        } elseif (!$user['is_active']) {
            $error = 'Account is deactivated';
        } elseif (!verifyPassword($password, $user['password_hash'])) {
            // Increment failed attempts
            $stmt = $db->prepare("
                UPDATE users 
                SET failed_login_attempts = failed_login_attempts + 1,
                    last_failed_login = NOW(),
                    account_locked_until = IF(failed_login_attempts + 1 >= :max_attempts, 
                        DATE_ADD(NOW(), INTERVAL :lockout_time SECOND), 
                        account_locked_until)
                WHERE user_id = :user_id
            ");
            $stmt->execute([
                ':user_id' => $user['user_id'],
                ':max_attempts' => MAX_LOGIN_ATTEMPTS,
                ':lockout_time' => LOCKOUT_TIME
            ]);
            
            logAudit($user['user_id'], 'LOGIN_FAILED', 'user', $user['user_id'], 'Wrong password');
            $error = 'Invalid email or password';
        } else {
            // Reset failed attempts on successful login
            $stmt = $db->prepare("
                UPDATE users 
                SET failed_login_attempts = 0,
                    last_failed_login = NULL,
                    account_locked_until = NULL
                WHERE user_id = :user_id
            ");
            $stmt->execute([':user_id' => $user['user_id']]);
            
            // Regenerate session ID for security
            session_regenerate_id(true);
            
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['first_name'] = $user['first_name'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['last_activity'] = time();
            
            // Store session in database
            $sessionId = session_id();
            $stmt = $db->prepare("
                INSERT INTO sessions (session_id, user_id, ip_address, user_agent)
                VALUES (:session_id, :user_id, :ip_address, :user_agent)
                ON DUPLICATE KEY UPDATE 
                    last_activity = CURRENT_TIMESTAMP,
                    ip_address = VALUES(ip_address),
                    user_agent = VALUES(user_agent)
            ");
            $stmt->execute([
                ':session_id' => $sessionId,
                ':user_id' => $user['user_id'],
                ':ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);
            
            // Audit log
            logAudit($user['user_id'], 'LOGIN_SUCCESS', 'user', $user['user_id']);
            
            // Redirect based on role
            if ($user['role'] === 'admin') {
                header('Location: ../admin/index.php');
            } else {
                header('Location: ../dashboard.php');
            }
            exit;
        }
    } catch (PDOException $e) {
        error_log("Login error: " . $e->getMessage());
        $error = 'Login failed. Please try again.';
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
        }
        .login-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            padding: 40px;
            max-width: 450px;
            margin: 20px auto;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 12px;
            font-weight: 600;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo i {
            font-size: 3rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-container">
            <div class="logo">
                <i class="fas fa-lock"></i>
                <h2 class="mt-3">Welcome Back</h2>
                <p class="text-muted">Login to your secure account</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="mb-3">
                    <label for="email" class="form-label">Email Address</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                        <input type="email" class="form-control" id="email" name="email" 
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-key"></i></span>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                </div>

                <div class="mb-3 text-end">
                    <a href="forgot_password.php" class="text-decoration-none">Forgot Password?</a>
                </div>

                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
            </form>

            <div class="text-center mt-4">
                <p class="mb-0">Don't have an account? <a href="register.php">Register here</a></p>
                <p class="mt-2"><a href="../index.php">← Back to Home</a></p>
            </div>

            <div class="mt-4 p-3 bg-light rounded">
                <small class="text-muted">
                    <strong>Demo Credentials:</strong><br>
                    <i class="fas fa-user-shield"></i> Admin: admin@ecommerce.com / Admin@123<br>
                    <i class="fas fa-user"></i> Customer: Register to create account
                </small>
            </div>

            <div class="mt-3 text-center">
                <small class="text-muted">
                    <i class="fas fa-shield-alt"></i> Protected by advanced security measures
                </small>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>