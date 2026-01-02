<?php
/**
 * User Registration
 * Group 9 - Secure E-Commerce System
 */

require_once '../config/database.php';
redirectIfLoggedIn();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = sanitizeInput($_POST['first_name'] ?? '');
    $lastName = sanitizeInput($_POST['last_name'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $phone = sanitizeInput($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // Validation
    if (empty($firstName) || empty($lastName) || empty($email) || empty($password)) {
        $error = 'All fields except phone are required';
    } elseif (!validateEmail($email)) {
        $error = 'Invalid email format';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match';
    } elseif (!validatePassword($password)) {
        $error = 'Password must be at least 8 characters with uppercase, lowercase, number and special character';
    } else {
        try {
            $db = Database::getInstance()->getConnection();
            
            // Check if email already exists
            $stmt = $db->prepare("SELECT user_id FROM users WHERE email = :email");
            $stmt->execute([':email' => $email]);
            
            if ($stmt->fetch()) {
                $error = 'Email already registered';
            } else {
                // Create user
                $passwordHash = hashPassword($password);
                
                $stmt = $db->prepare("
                    INSERT INTO users (email, password_hash, first_name, last_name, phone, role)
                    VALUES (:email, :password_hash, :first_name, :last_name, :phone, 'customer')
                ");
                
                $stmt->execute([
                    ':email' => $email,
                    ':password_hash' => $passwordHash,
                    ':first_name' => $firstName,
                    ':last_name' => $lastName,
                    ':phone' => $phone
                ]);
                
                $userId = $db->lastInsertId();
                logAudit($userId, 'USER_REGISTERED', 'user', $userId, "New user registration: $email");
                
                $success = 'Registration successful! Please login.';
                
                // Auto login after registration
                startSecureSession();
                $_SESSION['user_id'] = $userId;
                $_SESSION['email'] = $email;
                $_SESSION['first_name'] = $firstName;
                $_SESSION['last_name'] = $lastName;
                $_SESSION['role'] = 'customer';
                $_SESSION['is_active'] = 1;
                $_SESSION['last_activity'] = time();
                
                header('Location: ../dashboard.php');
                exit;
            }
        } catch (Exception $e) {
            error_log("Registration error: " . $e->getMessage());
            $error = 'Registration failed. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Secure E-Commerce</title>
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
        .register-container {
            max-width: 500px;
            width: 100%;
        }
        .register-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            padding: 40px;
        }
        .register-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .register-header i {
            font-size: 3rem;
            color: #667eea;
            margin-bottom: 15px;
        }
        .btn-register {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            padding: 12px;
            font-size: 16px;
            font-weight: 600;
        }
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .password-requirements {
            font-size: 0.85rem;
            color: #6c757d;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-card">
            <div class="register-header">
                <i class="fas fa-user-plus"></i>
                <h2>Create Account</h2>
                <p class="text-muted">Join our secure e-commerce platform</p>
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
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="first_name" class="form-label">
                            <i class="fas fa-user"></i> First Name *
                        </label>
                        <input 
                            type="text" 
                            class="form-control" 
                            id="first_name" 
                            name="first_name" 
                            value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>"
                            required
                        >
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="last_name" class="form-label">
                            <i class="fas fa-user"></i> Last Name *
                        </label>
                        <input 
                            type="text" 
                            class="form-control" 
                            id="last_name" 
                            name="last_name" 
                            value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>"
                            required
                        >
                    </div>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">
                        <i class="fas fa-envelope"></i> Email Address *
                    </label>
                    <input 
                        type="email" 
                        class="form-control" 
                        id="email" 
                        name="email" 
                        value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                        required
                    >
                </div>

                <div class="mb-3">
                    <label for="phone" class="form-label">
                        <i class="fas fa-phone"></i> Phone Number
                    </label>
                    <input 
                        type="tel" 
                        class="form-control" 
                        id="phone" 
                        name="phone" 
                        value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>"
                    >
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">
                        <i class="fas fa-lock"></i> Password *
                    </label>
                    <input 
                        type="password" 
                        class="form-control" 
                        id="password" 
                        name="password" 
                        required
                    >
                    <div class="password-requirements">
                        Must be 8+ characters with uppercase, lowercase, number & special character
                    </div>
                </div>

                <div class="mb-3">
                    <label for="confirm_password" class="form-label">
                        <i class="fas fa-lock"></i> Confirm Password *
                    </label>
                    <input 
                        type="password" 
                        class="form-control" 
                        id="confirm_password" 
                        name="confirm_password" 
                        required
                    >
                </div>

                <button type="submit" class="btn btn-register w-100 mb-3">
                    <i class="fas fa-user-plus"></i> Create Account
                </button>
            </form>

            <hr>

            <div class="text-center">
                <p class="mb-0">Already have an account?</p>
                <a href="login.php" class="btn btn-outline-primary btn-sm mt-2">
                    <i class="fas fa-sign-in-alt"></i> Login Here
                </a>
            </div>
        </div>

        <div class="text-center text-white mt-3">
            <a href="../index.php" class="text-white text-decoration-none">
                <i class="fas fa-home"></i> Back to Home
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>