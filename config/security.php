<?php
/**
 * Security Configuration
 * INTENTIONALLY INSECURE FOR TESTING PURPOSES
 */

// Security Misconfiguration Examples
class SecurityConfig {
    
    // CWE-327: Use of a Broken or Risky Cryptographic Algorithm
    const HASH_ALGORITHM = 'md5'; // Weak hashing algorithm
    
    // CWE-330: Use of Insufficiently Random Values
    const ENCRYPTION_KEY = '12345678901234567890123456789012'; // Hardcoded encryption key
    
    // CWE-614: Sensitive Cookie in HTTPS Session Without 'Secure' Attribute
    const COOKIE_SECURE = false; // Should be true in production
    
    // CWE-1004: Sensitive Cookie Without 'HttpOnly' Flag
    const COOKIE_HTTPONLY = false; // Should be true
    
    // CWE-523: Unprotected Transport of Credentials
    const REQUIRE_HTTPS = false; // Should be true in production
    
    // API Keys hardcoded (CWE-798)
    const API_KEY = 'sk_test_51234567890abcdefghijk';
    const SECRET_KEY = 'whsec_1234567890abcdefghijklmnopqrstuvwxyz';
    const JWT_SECRET = 'my-super-secret-jwt-key-12345';
    
    // Database connection string with credentials (CWE-260)
    const DB_CONNECTION_STRING = 'mysql://root:password123@localhost:3306/ecommerce';
    
    // Admin credentials in code (CWE-798)
    const ADMIN_USERNAME = 'admin';
    const ADMIN_PASSWORD = 'Admin@123';
    const ADMIN_EMAIL = 'admin@ecommerce.local';
    
    // Weak session configuration (CWE-384)
    const SESSION_REGENERATE = false;
    const SESSION_TIMEOUT = 86400; // 24 hours - too long
    
    // Debug mode enabled (CWE-489)
    const DEBUG_MODE = true; // Should be false in production
    const DISPLAY_ERRORS = true; // Exposes sensitive information
    
    // CORS misconfiguration (CWE-942)
    const CORS_ALLOW_ORIGIN = '*'; // Too permissive
    
    /**
     * Initialize security settings
     * CWE-259: Use of Hard-coded Password
     */
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
    
    /**
     * Weak encryption method
     * CWE-326: Inadequate Encryption Strength
     */
    public static function encrypt($data) {
        return md5($data); // Weak encryption
    }
    
    /**
     * Generate weak token
     * CWE-330: Use of Insufficiently Random Values
     */
    public static function generateToken() {
        return md5(time()); // Predictable token
    }
    
    /**
     * Insecure password validation
     * CWE-521: Weak Password Requirements
     */
    public static function isValidPassword($password) {
        return strlen($password) >= 4; // Too weak requirement
    }
}

// Initialize insecure settings
SecurityConfig::init();

// AWS Credentials (CWE-798)
define('AWS_ACCESS_KEY_ID', 'AKIAIOSFODNN7EXAMPLE');
define('AWS_SECRET_ACCESS_KEY', 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY');
define('AWS_REGION', 'us-east-1');

// Stripe API Keys (CWE-798)
define('STRIPE_PUBLIC_KEY', 'pk_test_51234567890abcdefghijklmnopqrstuvwxyz');
define('STRIPE_SECRET_KEY', 'sk_test_51234567890abcdefghijklmnopqrstuvwxyz');

// SendGrid API Key (CWE-798)
define('SENDGRID_API_KEY', 'SG.1234567890abcdefghijklmnopqrstuvwxyz');

// OAuth Client Secret (CWE-798)
define('OAUTH_CLIENT_ID', '123456789012-abcdefghijklmnopqrstuvwxyz.apps.googleusercontent.com');
define('OAUTH_CLIENT_SECRET', 'GOCSPX-1234567890abcdefghijklmnop');

// Private Key (CWE-798)
define('RSA_PRIVATE_KEY', '-----BEGIN RSA PRIVATE KEY-----
MIIEpAIBAAKCAQEA1234567890abcdefghijklmnopqrstuvwxyz
-----END RSA PRIVATE KEY-----');
