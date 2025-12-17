<?php
/**
 * Homepage - Secure E-Commerce System
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
    <title>ShopNet - Secure E-Commerce Platform</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Fredoka+One&family=Chewy&display=swap" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: #0a0e27;
            color: #ffffffff;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow-x: hidden;
        }

        /* Animated Background */
        .animated-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            background: linear-gradient(45deg, #0a0e27, #1a1f3a, #2d1b4e);
            animation: gradientShift 15s ease infinite;
        }

        @keyframes gradientShift {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }

        .animated-bg::before {
            content: '';
            position: absolute;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(102, 126, 234, 0.1) 0%, transparent 50%);
            animation: pulse 8s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: translate(-25%, -25%) scale(1); }
            50% { transform: translate(-25%, -25%) scale(1.1); }
        }

        /* Navbar */
        .navbar {
            background: rgba(10, 14, 39, 0.95);
            backdrop-filter: blur(10px);
            padding: 0.5rem 0;
            position: sticky;
            top: 0;
            z-index: 1000;
            transition: all 0.3s ease;
        }

        .navbar.scrolled {
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.3);
        }

        .navbar-brand {
            display: flex;
            align-items: center;
            gap: 15px;
            font-size: 1.8rem;
            font-weight: 700;
            color: #fff !important;
            transition: transform 0.3s ease;
        }

        .navbar-brand:hover {
            transform: scale(1.05);
        }

        .navbar-brand img {
            height: 100px;
            width: auto;
            filter: drop-shadow(0 4px 8px rgba(148, 185, 255, 0.5));
        }

        .navbar-brand span {
            font-family: 'Fredoka One', cursive;
            font-size: 2.5rem;
            color: #94b9ff;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }

        /* Hero Section */
        .hero-section {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            padding: 2rem;
            position: relative;
            background-image: url('assets/images/background.png');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }

        .hero-content {
            max-width: 600px;
            text-align: left;
            padding-right: 5%;
            animation: fadeInUp 1s ease;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .hero-title {
            font-size: 4.5rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
            background: linear-gradient(135deg, #2444d2ff 0%, #40275aff 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: fadeInUp 1s ease 0.2s both;
        }

        .hero-subtitle {
            font-size: 1.5rem;
            color: #010623ff;
            margin-bottom: 3rem;
            animation: fadeInUp 1s ease 0.4s both;
        }

        .hero-buttons {
            display: flex;
            gap: 1.5rem;
            justify-content: flex-start;
            flex-wrap: wrap;
            animation: fadeInUp 1s ease 0.6s both;
        }

        .btn-custom {
            padding: 1rem 2.5rem;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 50px;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .btn-primary-custom {
            background: linear-gradient(135deg, #2745caff 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }

        .btn-primary-custom:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(102, 126, 234, 0.6);
        }

        .btn-outline-custom {
            background: transparent;
            color: white;
            border: 2px solid rgba(255, 255, 255, 0.3);
            backdrop-filter: blur(10px);
        }

        .btn-outline-custom:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.5);
            transform: translateY(-3px);
        }

        /* Features Section */
        .features-section {
            padding: 6rem 0;
            background: rgba(255, 255, 255, 0.02);
        }

        .section-title {
            text-align: center;
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .section-subtitle {
            text-align: center;
            font-size: 1.2rem;
            color: #b8c1ec;
            margin-bottom: 4rem;
        }

        .feature-card {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 20px;
            padding: 2.5rem;
            margin-bottom: 2rem;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.4s ease;
            position: relative;
            overflow: hidden;
        }

        .feature-card::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(102, 126, 234, 0.1) 0%, transparent 70%);
            opacity: 0;
            transition: opacity 0.4s ease;
        }

        .feature-card:hover::before {
            opacity: 1;
        }

        .feature-card:hover {
            transform: translateY(-10px);
            border-color: rgba(102, 126, 234, 0.5);
            box-shadow: 0 20px 40px rgba(102, 126, 234, 0.2);
        }

        .feature-icon {
            font-size: 3.5rem;
            margin-bottom: 1.5rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            display: inline-block;
        }

        .feature-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: #fff;
        }

        .feature-description {
            color: #b8c1ec;
            line-height: 1.6;
        }

        /* Footer */
        footer {
            background: rgba(10, 14, 39, 0.95);
            padding: 3rem 0;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            margin-top: 4rem;
        }

        .footer-content {
            text-align: center;
            color: #b8c1ec;
        }

        .footer-logo {
            font-size: 1.5rem;
            font-weight: 700;
            color: #fff;
            margin-bottom: 1rem;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        .footer-logo img {
            height: 35px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .hero-section {
                justify-content: center;
                background-position: left center;
            }

            .hero-content {
                text-align: center;
                padding-right: 0;
            }

            .hero-title {
                font-size: 2.5rem;
            }

            .hero-subtitle {
                font-size: 1.2rem;
            }

            .section-title {
                font-size: 2rem;
            }

            .hero-buttons {
                flex-direction: column;
                align-items: center;
                justify-content: center;
            }

            .btn-custom {
                width: 100%;
                max-width: 300px;
            }
        }
    </style>
</head>
<body>
    <!-- Animated Background -->
    <div class="animated-bg"></div>

    <!-- Navbar -->
    <nav class="navbar">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <img src="assets/images/logo.png" alt="ShopNet Logo">
                <span>ShopNet</span>
            </a>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="hero-content">
            <h1 class="hero-title">
                Welcome to ShopNet
            </h1>
            <p class="hero-subtitle">
                Your trusted platform for secure online shopping. Experience seamless transactions with enterprise grade security.
            </p>
            <div class="hero-buttons">
                <a href="auth/register.php" class="btn-custom btn-primary-custom">
                    <i class="fas fa-user-plus"></i> Get Started
                </a>
                <a href="auth/login.php" class="btn-custom btn-primary-custom">
                    <i class="fas fa-sign-in-alt"></i> Sign In
                </a>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features-section">
        <div class="container">
            <h2 class="section-title">Why Choose ShopNet?</h2>
            <p class="section-subtitle">Built with security and user experience at the core</p>
            
            <div class="row">
                <div class="col-md-4">
                    <div class="feature-card text-center">
                        <i class="fas fa-shield-alt feature-icon"></i>
                        <h3 class="feature-title">Bank-Level Security</h3>
                        <p class="feature-description">Advanced encryption and secure authentication protect your data at every step</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card text-center">
                        <i class="fas fa-bolt feature-icon"></i>
                        <h3 class="feature-title">Lightning Fast</h3>
                        <p class="feature-description">Optimized performance ensures smooth shopping experience across all devices</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card text-center">
                        <i class="fas fa-headset feature-icon"></i>
                        <h3 class="feature-title">24/7 Support</h3>
                        <p class="feature-description">Our dedicated team is always here to assist you with any questions</p>
                    </div>
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-md-4">
                    <div class="feature-card text-center">
                        <i class="fas fa-lock feature-icon"></i>
                        <h3 class="feature-title">Privacy First</h3>
                        <p class="feature-description">Your personal information is encrypted and never shared with third parties</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card text-center">
                        <i class="fas fa-mobile-alt feature-icon"></i>
                        <h3 class="feature-title">Mobile Friendly</h3>
                        <p class="feature-description">Shop anywhere, anytime with our responsive design</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card text-center">
                        <i class="fas fa-star feature-icon"></i>
                        <h3 class="feature-title">Best Quality</h3>
                        <p class="feature-description">Curated products from trusted sellers with verified reviews</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-logo">
                    <img src="assets/images/logo.png" alt="ShopNet Logo">
                    <span>ShopNet</span>
                </div>
                <p>&copy; <?php echo date('Y'); ?> ShopNet. All rights reserved.</p>
                <p class="mt-2">
                    <small>Secure E-Commerce Platform</small>
                </p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Add scrolled class to navbar on scroll
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });

        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth'
                    });
                }
            });
        });
    </script>
</body>
</html>