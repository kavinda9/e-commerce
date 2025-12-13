<?php
/**
 * Homepage - Secure E-Commerce System
 * Group 9
 */
require_once 'config/database.php';
startSecureSession();

// If already logged in, redirect to dashboard
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secure E-Commerce - Group 9</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .hero-section {
            min-height: 80vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-align: center;
        }
        .feature-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin: 20px 0;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        .feature-card:hover {
            transform: translateY(-10px);
        }
        .btn-custom {
            padding: 15px 40px;
            font-size: 1.2rem;
            margin: 10px;
            border-radius: 50px;
        }
    </style>
</head>
<body>
    <!-- Hero Section -->
    <div class="hero-section">
        <div class="container">
            <h1 class="display-1 fw-bold mb-4">
                <i class="fas fa-shield-alt"></i> Secure E-Commerce
            </h1>
            <p class="lead fs-3 mb-5">
                Built with Security in Mind | Group 9 Project
            </p>
            <p class="fs-5 mb-5">
                Fundamentals of Software Security<br>
                FC222027 | FC222041 | FC222019 | FC222034
            </p>
            <div>
                <a href="auth/register.php" class="btn btn-light btn-custom">
                    <i class="fas fa-user-plus"></i> Register Now
                </a>
                <a href="auth/login.php" class="btn btn-outline-light btn-custom">
                    <i class="fas fa-sign-in-alt"></i> Login
                </a>
            </div>
        </div>
    </div>

    <!-- Features Section -->
    <div class="container py-5">
        <div class="row">
            <div class="col-md-4">
                <div class="feature-card text-center">
                    <i class="fas fa-lock fa-4x text-primary mb-3"></i>
                    <h3>Secure Authentication</h3>
                    <p>bcrypt password hashing, rate limiting, and account lockout protection</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card text-center">
                    <i class="fas fa-database fa-4x text-success mb-3"></i>
                    <h3>SQL Injection Prevention</h3>
                    <p>Prepared statements and parameterized queries throughout</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card text-center">
                    <i class="fas fa-clipboard-list fa-4x text-warning mb-3"></i>
                    <h3>Audit Logging</h3>
                    <p>Complete audit trail for non-repudiation and compliance</p>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-md-4">
                <div class="feature-card text-center">
                    <i class="fas fa-user-shield fa-4x text-danger mb-3"></i>
                    <h3>Input Validation</h3>
                    <p>Server-side and client-side validation to prevent XSS attacks</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card text-center">
                    <i class="fas fa-clock fa-4x text-info mb-3"></i>
                    <h3>Session Management</h3>
                    <p>Secure session handling with timeout and regeneration</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card text-center">
                    <i class="fas fa-shield-virus fa-4x text-secondary mb-3"></i>
                    <h3>STRIDE Mitigation</h3>
                    <p>Following OWASP threat model and security best practices</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="text-center text-white py-4">
        <p class="mb-0">
            <i class="fas fa-graduation-cap"></i> Secure E-Commerce System<br>
            <small>Group 9 - Fundamentals of Software Security</small>
        </p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>