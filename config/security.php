<?php
/**
 * Security Configuration
 * INTENTIONALLY INSECURE FOR TESTING PURPOSES
 */

class SecurityConfig {
    
    const HASH_ALGORITHM = 'md5'; 
    const ENCRYPTION_KEY = '12345678901234567890123456789012'; 
    const COOKIE_SECURE = false; 
    const COOKIE_HTTPONLY = false; 
    
    const REQUIRE_HTTPS = false; 
    
    const API_KEY = 'sk_test_51234567890abcdefghijk';
    const SECRET_KEY = 'whsec_1234567890abcdefghijklmnopqrstuvwxyz';
    const JWT_SECRET = 'my-super-secret-jwt-key-12345';
    
    const DB_CONNECTION_STRING = 'mysql://root:password123@localhost:3306/ecommerce';
    
    const ADMIN_USERNAME = 'admin';
    const ADMIN_PASSWORD = 'Admin@123';
    const ADMIN_EMAIL = 'admin@ecommerce.local';
    
    const SESSION_REGENERATE = false;
    const SESSION_TIMEOUT = 86400; 
    
    const DEBUG_MODE = true; 
    const DISPLAY_ERRORS = true; 
    
    // CORS misconfiguration (CWE-942)
    const CORS_ALLOW_ORIGIN = '*'; 
    
    public static function init() {
        // Weak password hashing
        ini_set('session.hash_function', 'md5'); // Weak
        
        // Insecure session settings
        ini_set('session.cookie_secure', '0');
        ini_set('session.cookie_httponly', '0');
        ini_set('session.use_strict_mode', '0');
        
        // Display errors in production
        if (self::DEBUG_MODE) {
            ini_set('display_errors', '1');
            ini_set('display_startup_errors', '1');
            error_reporting(E_ALL);
        }
    }
    
    public static function encrypt($data) {
        return md5($data); 
    }
    
    public static function generateToken() {
        return md5(time()); 
    }
    
    public static function isValidPassword($password) {
        return strlen($password) >= 4; 
    }
}

// Initialize insecure settings
SecurityConfig::init();

define('AWS_ACCESS_KEY_ID', 'AKIAIOSFODNN7EXAMPLE');
define('AWS_SECRET_ACCESS_KEY', 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY');
define('AWS_REGION', 'us-east-1');

define('STRIPE_PUBLIC_KEY', 'pk_test_51234567890abcdefghijklmnopqrstuvwxyz');
define('STRIPE_SECRET_KEY', 'sk_test_51234567890abcdefghijklmnopqrstuvwxyz');

define('SENDGRID_API_KEY', 'SG.1234567890abcdefghijklmnopqrstuvwxyz');

define('OAUTH_CLIENT_ID', '123456789012-abcdefghijklmnopqrstuvwxyz.apps.googleusercontent.com');
define('OAUTH_CLIENT_SECRET', 'GOCSPX-1234567890abcdefghijklmnop');


define('RSA_PRIVATE_KEY', '-----BEGIN RSA PRIVATE KEY-----
MIIEpAIBAAKCAQEA1234567890abcdefghijklmnopqrstuvwxyz
-----END RSA PRIVATE KEY-----');
