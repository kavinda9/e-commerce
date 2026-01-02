<?php
/**
 * User Profile Page
 * Group 9 - Secure E-Commerce System
 */

require_once '../config/database.php';
requireLogin();

$db = Database::getInstance()->getConnection();
$userId = $_SESSION['user_id'];

$error = '';
$success = '';

// Get user data
$stmt = $db->prepare("SELECT * FROM users WHERE user_id = :user_id");
$stmt->execute([':user_id' => $userId]);
$user = $stmt->fetch();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $firstName = sanitizeInput($_POST['first_name'] ?? '');
    $lastName = sanitizeInput($_POST['last_name'] ?? '');
    $phone = sanitizeInput($_POST['phone'] ?? '');
    
    if (empty($firstName) || empty($lastName)) {
        $error = 'First name and last name are required';
    } else {
        try {
            $stmt = $db->prepare("
                UPDATE users 
                SET first_name = :first_name, last_name = :last_name, phone = :phone
                WHERE user_id = :user_id
            ");
            
            $stmt->execute([
                ':first_name' => $firstName,
                ':last_name' => $lastName,
                ':phone' => $phone,
                ':user_id' => $userId
            ]);
            
            // Update session
            $_SESSION['first_name'] = $firstName;
            $_SESSION['last_name'] = $lastName;
            
            logAudit($userId, 'PROFILE_UPDATED', 'user', $userId);
            $success = 'Profile updated successfully!';
            
            // Refresh user data
            $stmt = $db->prepare("SELECT * FROM users WHERE user_id = :user_id");
            $stmt->execute([':user_id' => $userId]);
            $user = $stmt->fetch();
            
        } catch (Exception $e) {
            error_log("Profile update error: " . $e->getMessage());
            $error = 'Failed to update profile';
        }
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        $error = 'All password fields are required';
    } elseif (!verifyPassword($currentPassword, $user['password_hash'])) {
        $error = 'Current password is incorrect';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'New passwords do not match';
    } elseif (!validatePassword($newPassword)) {
        $error = 'Password must be at least 8 characters with uppercase, lowercase, number and special character';
    } else {
        try {
            $newPasswordHash = hashPassword($newPassword);
            
            $stmt = $db->prepare("UPDATE users SET password_hash = :password_hash WHERE user_id = :user_id");
            $stmt->execute([
                ':password_hash' => $newPasswordHash,
                ':user_id' => $userId
            ]);
            
            logAudit($userId, 'PASSWORD_CHANGED', 'user', $userId);
            $success = 'Password changed successfully!';
            
        } catch (Exception $e) {
            error_log("Password change error: " . $e->getMessage());
            $error = 'Failed to change password';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Secure E-Commerce</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .profile-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .profile-header {
            text-align: center;
            padding: 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            margin-bottom: 30px;
        }
        .profile-avatar {
            width: 100px;
            height: 100px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 3rem;
            color: #667eea;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="../dashboard.php">
                <i class="fas fa-shopping-cart"></i> Secure E-Commerce
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="../dashboard.php">
                    <i class="fas fa-home"></i> Dashboard
                </a>
                <a class="nav-link" href="orders.php">
                    <i class="fas fa-box"></i> My Orders
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Profile Header -->
        <div class="profile-header">
            <div class="profile-avatar">
                <i class="fas fa-user"></i>
            </div>
            <h2><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h2>
            <p class="mb-0"><?php echo htmlspecialchars($user['email']); ?></p>
            <span class="badge bg-light text-dark mt-2">
                <?php echo ucfirst($user['role']); ?>
            </span>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Profile Information -->
            <div class="col-lg-6">
                <div class="profile-card">
                    <h4 class="mb-4"><i class="fas fa-user-edit"></i> Profile Information</h4>
                    
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="first_name" class="form-label">First Name</label>
                            <input 
                                type="text" 
                                class="form-control" 
                                id="first_name" 
                                name="first_name" 
                                value="<?php echo htmlspecialchars($user['first_name']); ?>"
                                required
                            >
                        </div>

                        <div class="mb-3">
                            <label for="last_name" class="form-label">Last Name</label>
                            <input 
                                type="text" 
                                class="form-control" 
                                id="last_name" 
                                name="last_name" 
                                value="<?php echo htmlspecialchars($user['last_name']); ?>"
                                required
                            >
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input 
                                type="email" 
                                class="form-control" 
                                id="email" 
                                value="<?php echo htmlspecialchars($user['email']); ?>"
                                disabled
                            >
                            <small class="text-muted">Email cannot be changed</small>
                        </div>

                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input 
                                type="tel" 
                                class="form-control" 
                                id="phone" 
                                name="phone" 
                                value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>"
                            >
                        </div>

                        <button type="submit" name="update_profile" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Profile
                        </button>
                    </form>
                </div>
            </div>

            <!-- Change Password -->
            <div class="col-lg-6">
                <div class="profile-card">
                    <h4 class="mb-4"><i class="fas fa-lock"></i> Change Password</h4>
                    
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Current Password</label>
                            <input 
                                type="password" 
                                class="form-control" 
                                id="current_password" 
                                name="current_password"
                                required
                            >
                        </div>

                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password</label>
                            <input 
                                type="password" 
                                class="form-control" 
                                id="new_password" 
                                name="new_password"
                                required
                            >
                            <small class="text-muted">
                                Must be 8+ characters with uppercase, lowercase, number & special character
                            </small>
                        </div>

                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <input 
                                type="password" 
                                class="form-control" 
                                id="confirm_password" 
                                name="confirm_password"
                                required
                            >
                        </div>

                        <button type="submit" name="change_password" class="btn btn-warning">
                            <i class="fas fa-key"></i> Change Password
                        </button>
                    </form>
                </div>

                <!-- Account Info -->
                <div class="profile-card">
                    <h5 class="mb-3"><i class="fas fa-info-circle"></i> Account Information</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <strong>User ID:</strong> <?php echo $user['user_id']; ?>
                        </li>
                        <li class="mb-2">
                            <strong>Role:</strong> 
                            <span class="badge bg-primary"><?php echo ucfirst($user['role']); ?></span>
                        </li>
                        <li class="mb-2">
                            <strong>Status:</strong> 
                            <span class="badge bg-<?php echo $user['is_active'] ? 'success' : 'danger'; ?>">
                                <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                            </span>
                        </li>
                        <li class="mb-2">
                            <strong>Member Since:</strong> 
                            <?php echo date('F d, Y', strtotime($user['created_at'])); ?>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>