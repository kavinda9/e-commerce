<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'secure_ecommerce');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Security settings
define('SESSION_LIFETIME', 1800); // 30 minutes
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_TIME', 900); // 15 minutes in seconds
define('PASSWORD_RESET_EXPIRY', 86400); // 24 hours

// Base URL (update this if your folder name is different)
define('BASE_URL', 'http://localhost/ecommerce/');

class Database {
    private $conn;
    private static $instance = null;
    
    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_PERSISTENT => false
            ];
            
            $this->conn = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log("Database Connection Error: " . $e->getMessage());
            die("Database connection failed. Please contact administrator.");
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->conn;
    }
    
    // Prevent cloning
    private function __clone() {}
    
    // Prevent unserialization
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}

// Audit logging function
function logAudit($userId, $action, $entityType = null, $entityId = null, $details = null) {
    try {
        $db = Database::getInstance()->getConnection();
        
        $stmt = $db->prepare("
            INSERT INTO audit_logs (user_id, action, entity_type, entity_id, ip_address, user_agent, details)
            VALUES (:user_id, :action, :entity_type, :entity_id, :ip_address, :user_agent, :details)
        ");
        
        $stmt->execute([
            ':user_id' => $userId,
            ':action' => $action,
            ':entity_type' => $entityType,
            ':entity_id' => $entityId,
            ':ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            ':details' => $details
        ]);
    } catch (Exception $e) {
        error_log("Audit logging failed: " . $e->getMessage());
    }
}

// Security helper functions
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validatePassword($password) {
    // At least 8 characters, 1 uppercase, 1 lowercase, 1 number, 1 special character
    $pattern = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&#])[A-Za-z\d@$!%*?&#]{8,}$/';
    return preg_match($pattern, $password);
}

function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

function generateSecureToken() {
    return bin2hex(random_bytes(32));
}

// Session helpers
function startSecureSession() {
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_secure', 0); // Set to 1 in production with HTTPS
        session_start();
    }
}

function isLoggedIn() {
    startSecureSession();
    
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    
    // Check session timeout
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_LIFETIME)) {
        session_unset();
        session_destroy();
        return false;
    }
    
    $_SESSION['last_activity'] = time();
    return true;
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . 'auth/login.php');
        exit;
    }
}

function requireAdmin() {
    requireLogin();
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        header('Location: ' . BASE_URL . 'index.php');
        exit;
    }
}

function redirectIfLoggedIn() {
    if (isLoggedIn()) {
        header('Location: ' . BASE_URL . 'dashboard.php');
        exit;
    }
}


// Email Configuration
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'shopnet.supp0rt@gmail.com');
define('SMTP_PASS', 'dehnhatcnxlnuxtl'); // App password (no spaces)
// Use the same address as SMTP user to avoid Gmail sender rejection
define('FROM_EMAIL', SMTP_USER);
define('FROM_NAME', 'ShopNet E-Commerce');

/**
 * Send Email Function - Using PHPMailer with Gmail SMTP
 */
function sendEmail($to, $toName, $subject, $body) {
    // Try to use PHPMailer if available
    if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
        require_once __DIR__ . '/../vendor/autoload.php';
        
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        
        try {
            // Server settings
            $mail->SMTPDebug = 3; // Verbose debug output
            $mail->Debugoutput = function($str) { error_log('SMTP: ' . $str); };
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USER;
            $mail->Password = SMTP_PASS;
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = SMTP_PORT;
            
            // Recipients
            $mail->setFrom(FROM_EMAIL, FROM_NAME);
            $mail->addAddress($to, $toName);
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->AltBody = strip_tags($body);
            
            $mail->send();
            error_log("Email sent successfully to: $to");
            return true;
        } catch (Exception $e) {
            error_log("Email Error: {$mail->ErrorInfo}");
            // Fall through to file logging
        }
    }
    
    // Fallback: Log to file if PHPMailer not available or fails
    $logsDir = __DIR__ . '/../logs';
    if (!is_dir($logsDir)) {
        mkdir($logsDir, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $emailLog = $logsDir . '/emails.log';
    
    $logEntry = "\n" . str_repeat('=', 70) . "\n";
    $logEntry .= "Timestamp: $timestamp\n";
    $logEntry .= "To: $toName <$to>\n";
    $logEntry .= "Subject: $subject\n";
    $logEntry .= str_repeat('-', 70) . "\n";
    $logEntry .= $body . "\n";
    $logEntry .= str_repeat('=', 70) . "\n";
    
    file_put_contents($emailLog, $logEntry, FILE_APPEND);
    error_log("Email logged to file for: $to");
    
    return false;
}

/**
 * Send Password Reset Email
 */
function sendPasswordResetEmail($email, $name, $token) {
    $resetLink = BASE_URL . "auth/reset_password.php?token=" . $token;
    
    $subject = "Password Reset Request - ShopNet E-Commerce";
    
    $body = "
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { 
                font-family: Arial, sans-serif; 
                line-height: 1.6; 
                color: #333; 
                margin: 0;
                padding: 0;
            }
            .container { 
                max-width: 600px; 
                margin: 0 auto; 
                padding: 20px; 
            }
            .header { 
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
                color: white; 
                padding: 30px; 
                text-align: center; 
                border-radius: 10px 10px 0 0; 
            }
            .header h1 {
                margin: 0;
                font-size: 24px;
            }
            .content { 
                background: #f9f9f9; 
                padding: 30px; 
                border-radius: 0 0 10px 10px; 
            }
            .button { 
                display: inline-block; 
                padding: 12px 30px; 
                background: #667eea; 
                color: white !important; 
                text-decoration: none; 
                border-radius: 5px; 
                margin: 20px 0;
                font-weight: bold;
            }
            .link-text {
                word-break: break-all; 
                color: #667eea;
                font-size: 14px;
            }
            .footer { 
                text-align: center; 
                margin-top: 20px; 
                color: #666; 
                font-size: 12px; 
            }
            .warning {
                background: #fff3cd;
                border-left: 4px solid #ffc107;
                padding: 12px;
                margin: 15px 0;
            }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🔐 Password Reset Request</h1>
            </div>
            <div class='content'>
                <p>Hello <strong>$name</strong>,</p>
                <p>We received a request to reset your password for your ShopNet E-Commerce account.</p>
                <p>Click the button below to reset your password:</p>
                <p style='text-align: center;'>
                    <a href='$resetLink' class='button'>Reset Password</a>
                </p>
                <p>Or copy and paste this link into your browser:</p>
                <p class='link-text'>$resetLink</p>
                
                <div class='warning'>
                    <strong>⚠️ Important:</strong> This link will expire in <strong>1 hour</strong>.
                </div>
                
                <p>If you didn't request this password reset, please ignore this email. Your password will remain unchanged.</p>
                <p>For security reasons, never share this link with anyone.</p>
            </div>
            <div class='footer'>
                <p>&copy; 2024 ShopNet E-Commerce</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    return sendEmail($email, $name, $subject, $body);
}
?>