<?php
/**
 * Debug Script - Check Admin Login Status
 * Place this in your root directory and access it to debug admin login issues
 */

require_once 'config/database.php';

// Start session
startSecureSession();

echo "<h2>🔍 Admin Login Debug Information</h2>";
echo "<hr>";

// Check if user is logged in
echo "<h3>1. Session Status</h3>";
echo "<pre>";
echo "Session Started: " . (session_status() === PHP_SESSION_ACTIVE ? "✅ YES" : "❌ NO") . "\n";
echo "Session ID: " . session_id() . "\n";
echo "</pre>";

// Check session variables
echo "<h3>2. Session Variables</h3>";
echo "<pre>";
if (isset($_SESSION['user_id'])) {
    echo "User ID: " . $_SESSION['user_id'] . " ✅\n";
    echo "First Name: " . ($_SESSION['first_name'] ?? 'Not set') . "\n";
    echo "Last Name: " . ($_SESSION['last_name'] ?? 'Not set') . "\n";
    echo "Email: " . ($_SESSION['email'] ?? 'Not set') . "\n";
    echo "Role: " . ($_SESSION['role'] ?? 'Not set') . " " . 
         ($_SESSION['role'] === 'admin' ? "✅ ADMIN" : "❌ NOT ADMIN") . "\n";
    echo "Is Active: " . ($_SESSION['is_active'] ?? 'Not set') . "\n";
} else {
    echo "❌ Not logged in - No user_id in session\n";
}
echo "\nAll Session Data:\n";
print_r($_SESSION);
echo "</pre>";

// Check database connection
echo "<h3>3. Database Connection</h3>";
try {
    $db = Database::getInstance()->getConnection();
    echo "<pre>✅ Database connected successfully</pre>";
    
    // Check admin user
    echo "<h3>4. Admin User in Database</h3>";
    $stmt = $db->query("SELECT user_id, email, first_name, last_name, role, is_active FROM users WHERE role = 'admin'");
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<pre>";
    if ($admins) {
        echo "Found " . count($admins) . " admin user(s):\n\n";
        foreach ($admins as $admin) {
            echo "User ID: " . $admin['user_id'] . "\n";
            echo "Email: " . $admin['email'] . "\n";
            echo "Name: " . $admin['first_name'] . " " . $admin['last_name'] . "\n";
            echo "Role: " . $admin['role'] . "\n";
            echo "Active: " . ($admin['is_active'] ? "Yes" : "No") . "\n";
            echo "---\n";
        }
    } else {
        echo "❌ No admin users found in database!\n";
    }
    echo "</pre>";
    
} catch (Exception $e) {
    echo "<pre>❌ Database error: " . $e->getMessage() . "</pre>";
}

// Check if current user matches admin
if (isset($_SESSION['user_id'])) {
    echo "<h3>5. Current User Details from Database</h3>";
    try {
        $stmt = $db->prepare("SELECT * FROM users WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $currentUser = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "<pre>";
        if ($currentUser) {
            echo "Database Record:\n";
            echo "User ID: " . $currentUser['user_id'] . "\n";
            echo "Email: " . $currentUser['email'] . "\n";
            echo "Name: " . $currentUser['first_name'] . " " . $currentUser['last_name'] . "\n";
            echo "Role: " . $currentUser['role'] . " " . 
                 ($currentUser['role'] === 'admin' ? "✅ ADMIN" : "❌ NOT ADMIN") . "\n";
            echo "Active: " . ($currentUser['is_active'] ? "Yes ✅" : "No ❌") . "\n";
            
            // Compare with session
            echo "\n🔄 Session vs Database Comparison:\n";
            if ($_SESSION['role'] === $currentUser['role']) {
                echo "Role matches ✅\n";
            } else {
                echo "❌ Role MISMATCH!\n";
                echo "Session role: " . ($_SESSION['role'] ?? 'not set') . "\n";
                echo "Database role: " . $currentUser['role'] . "\n";
            }
        } else {
            echo "❌ User not found in database!\n";
        }
        echo "</pre>";
    } catch (Exception $e) {
        echo "<pre>❌ Error: " . $e->getMessage() . "</pre>";
    }
}

// Recommendations
echo "<h3>6. Troubleshooting Steps</h3>";
echo "<div style='background: #f0f0f0; padding: 15px; border-left: 4px solid #667eea;'>";
echo "<ol>";
echo "<li><strong>If not logged in:</strong> Go to <a href='auth/login.php'>login page</a> and log in with admin credentials</li>";
echo "<li><strong>Default admin credentials:</strong><br>Email: admin@ecommerce.com<br>Password: Admin@123</li>";
echo "<li><strong>If logged in but role is not 'admin':</strong> Your user account needs admin role in database</li>";
echo "<li><strong>If session role doesn't match database:</strong> Logout and login again to refresh session</li>";
echo "</ol>";
echo "</div>";

echo "<hr>";
echo "<div style='margin: 20px 0;'>";
echo "<a href='auth/login.php' style='padding: 10px 20px; background: #667eea; color: white; text-decoration: none; border-radius: 5px;'>Go to Login</a> ";
echo "<a href='auth/logout.php' style='padding: 10px 20px; background: #dc3545; color: white; text-decoration: none; border-radius: 5px;'>Logout</a> ";
echo "<a href='dashboard.php' style='padding: 10px 20px; background: #28a745; color: white; text-decoration: none; border-radius: 5px;'>Dashboard</a>";
echo "</div>";

?>
<style>
    body {
        font-family: Arial, sans-serif;
        max-width: 1000px;
        margin: 20px auto;
        padding: 20px;
        background: #f5f5f5;
    }
    pre {
        background: white;
        padding: 15px;
        border-radius: 5px;
        border: 1px solid #ddd;
        overflow-x: auto;
    }
    h2, h3 {
        color: #333;
    }
</style>