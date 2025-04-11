<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PUP Qualifying Exam Portal</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0" />
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #75343A;
            --primary-dark: #5a2930;
            --primary-light: #9e4a52;
            --secondary: #f8f0e3;
            --accent: #d4af37;
            --text-dark: #333333;
            --text-light: #ffffff;
            --gray-light: #f5f5f5;
            --gray: #e0e0e0;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Roboto', sans-serif;
            color: var(--text-dark);
            background-color: var(--gray-light);
            line-height: 1.6;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        /* Header Styles */
        header {
            background-color: var(--primary);
            color: var(--text-light);
            padding: 15px 0;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .logo img {
            height: 60px;
            width: auto;
        }
        
        .logo-text {
            display: flex;
            flex-direction: column;
        }
        
        .logo-text h1 {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 4px;
        }
        
        .logo-text p {
            font-size: 14px;
            opacity: 0.9;
        }
        
        .nav-links {
            display: flex;
            gap: 20px;
        }
        
        .nav-links a {
            color: var(--text-light);
            text-decoration: none;
            font-weight: 500;
            padding: 8px 12px;
            border-radius: 4px;
            transition: background-color 0.3s;
        }
        
        .nav-links a:hover {
            background-color: var(--primary-dark);
        }
        
        /* Hero Section */
        .hero {
            background: linear-gradient(rgba(117, 52, 58, 0.9), rgba(117, 52, 58, 0.8)), url('https://www.pup.edu.ph/about/images/campus.jpg');
            background-size: cover;
            background-position: center;
            color: var(--text-light);
            padding: 100px 0;
            text-align: center;
        }
        
        .hero h2 {
            font-family: 'Playfair Display', serif;
            font-size: 48px;
            margin-bottom: 20px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }
        
        .hero p {
            font-size: 18px;
            max-width: 700px;
            margin: 0 auto 30px;
            opacity: 0.9;
        }
        
        .cta-buttons {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 30px;
        }
        
        .cta-btn {
            padding: 12px 30px;
            border-radius: 30px;
            font-weight: 500;
            font-size: 16px;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .primary-btn {
            background-color: var(--accent);
            color: var(--primary-dark);
            border: 2px solid var(--accent);
        }
        
        .primary-btn:hover {
            background-color: transparent;
            color: var(--accent);
        }
        
        .secondary-btn {
            background-color: transparent;
            color: var(--text-light);
            border: 2px solid var(--text-light);
        }
        
        .secondary-btn:hover {
            background-color: var(--text-light);
            color: var(--primary);
        }
        
        /* Features Section */
        .features {
            padding: 80px 0;
            background-color: var(--text-light);
        }
        
        .section-title {
            text-align: center;
            margin-bottom: 60px;
        }
        
        .section-title h3 {
            font-size: 32px;
            color: var(--primary);
            margin-bottom: 15px;
        }
        
        .section-title p {
            font-size: 16px;
            color: var(--text-dark);
            max-width: 700px;
            margin: 0 auto;
            opacity: 0.8;
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
        }
        
        .feature-card {
            background-color: var(--gray-light);
            border-radius: 8px;
            padding: 30px;
            text-align: center;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        
        .feature-icon {
            background-color: var(--primary-light);
            color: var(--text-light);
            width: 70px;
            height: 70px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
        }
        
        .feature-icon span {
            font-size: 32px;
        }
        
        .feature-card h4 {
            font-size: 20px;
            margin-bottom: 15px;
            color: var(--primary);
        }
        
        .feature-card p {
            font-size: 15px;
            color: var(--text-dark);
            opacity: 0.8;
        }
        
        /* Login Section */
        .login-section {
            padding: 80px 0;
            background-color: var(--gray-light);
        }
        
        .login-container {
            display: flex;
            justify-content: center;
            gap: 40px;
            flex-wrap: wrap;
        }
        
        .login-card {
            background-color: var(--text-light);
            border-radius: 8px;
            padding: 40px;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .login-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
        }
        
        .login-card h4 {
            font-size: 24px;
            color: var(--primary);
            margin-bottom: 25px;
            text-align: center;
            position: relative;
            padding-bottom: 15px;
        }
        
        .login-card h4::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 50px;
            height: 3px;
            background-color: var(--primary);
        }
        
        .login-form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .form-group label {
            font-size: 14px;
            font-weight: 500;
            color: var(--text-dark);
        }
        
        .form-group input {
            padding: 12px 16px;
            border: 1px solid var(--gray);
            border-radius: 4px;
            font-size: 16px;
            transition: border-color 0.3s, box-shadow 0.3s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 2px rgba(117, 52, 58, 0.2);
        }
        
        .forgot-password {
            text-align: right;
            font-size: 14px;
        }
        
        .forgot-password a {
            color: var(--primary);
            text-decoration: none;
        }
        
        .forgot-password a:hover {
            text-decoration: underline;
        }
        
        .login-btn {
            background-color: var(--primary);
            color: var(--text-light);
            padding: 12px;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .login-btn:hover {
            background-color: var(--primary-dark);
        }
        
        .login-footer {
            margin-top: 20px;
            text-align: center;
            font-size: 14px;
            color: var(--text-dark);
        }
        
        .login-footer a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
        }
        
        .login-footer a:hover {
            text-decoration: underline;
        }
        
        /* Footer */
        footer {
            background-color: var(--primary);
            color: var(--text-light);
            padding: 50px 0 20px;
        }
        
        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 40px;
            margin-bottom: 40px;
        }
        
        .footer-column h5 {
            font-size: 18px;
            margin-bottom: 20px;
            position: relative;
            padding-bottom: 10px;
        }
        
        .footer-column h5::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 40px;
            height: 2px;
            background-color: var(--accent);
        }
        
        .footer-column p {
            margin-bottom: 15px;
            font-size: 14px;
            opacity: 0.8;
        }
        
        .footer-links {
            list-style: none;
        }
        
        .footer-links li {
            margin-bottom: 10px;
        }
        
        .footer-links a {
            color: var(--text-light);
            text-decoration: none;
            font-size: 14px;
            opacity: 0.8;
            transition: opacity 0.3s;
        }
        
        .footer-links a:hover {
            opacity: 1;
        }
        
        .contact-info {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
            font-size: 14px;
            opacity: 0.8;
        }
        
        .contact-info span {
            font-size: 20px;
        }
        
        .social-links {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }
        
        .social-links a {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            transition: background-color 0.3s;
        }
        
        .social-links a:hover {
            background-color: var(--accent);
        }
        
        .copyright {
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            font-size: 14px;
            opacity: 0.7;
        }
        
        /* Responsive Styles */
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 15px;
            }
            
            .nav-links {
                margin-top: 10px;
            }
            
            .hero h2 {
                font-size: 36px;
            }
            
            .hero p {
                font-size: 16px;
            }
            
            .cta-buttons {
                flex-direction: column;
                gap: 15px;
            }
            
            .login-card {
                padding: 30px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header>
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <img src="https://www.pup.edu.ph/about/images/PUPLogo.png" alt="PUP Logo">
                    <div class="logo-text">
                        <h1>PUP Qualifying Exam Portal</h1>
                        <p>Polytechnic University of the Philippines</p>
                    </div>
                </div>
                <nav class="nav-links">
                    <a href="#">Home</a>
                    <a href="#">About</a>
                    <a href="#">Contact</a>
                    <a href="#">FAQ</a>
                </nav>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <h2>PUP Qualifying Examination System</h2>
            <p>Welcome to the official Polytechnic University of the Philippines Qualifying Examination Portal. This platform is designed to streamline the examination process for students and administrators.</p>
            <div class="cta-buttons">
                <a href="stud_register.php" class="cta-btn primary-btn">
                    <span class="material-symbols-rounded">person</span>
                    Student Login
                </a>
                <a href="admin_login.php" class="cta-btn secondary-btn">
                    <span class="material-symbols-rounded">admin_panel_settings</span>
                    Admin Login
                </a>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features">
        <div class="container">
            <div class="section-title">
                <h3>Qualifying Exam Features</h3>
                <p>Our platform offers a comprehensive set of tools to facilitate the examination process for both students and administrators.</p>
            </div>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <span class="material-symbols-rounded">quiz</span>
                    </div>
                    <h4>Interactive Exams</h4>
                    <p>Take exams with various question types including multiple choice, true/false, and programming challenges.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <span class="material-symbols-rounded">timer</span>
                    </div>
                    <h4>Timed Assessments</h4>
                    <p>Experience real exam conditions with timed assessments that prepare you for the actual qualifying exams.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <span class="material-symbols-rounded">insights</span>
                    </div>
                    <h4>Instant Results</h4>
                    <p>Receive immediate feedback and detailed performance analytics after completing your exams.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-column">
                    <h5>About PUP</h5>
                    <p>The Polytechnic University of the Philippines (PUP) is a government educational institution governed by Republic Act Number 8292 known as the Higher Education Modernization Act of 1997.</p>
                    <div class="social-links">
                        <a href="#"><span class="material-symbols-rounded">facebook</span></a>
                        <a href="#"><span class="material-symbols-rounded">twitter</span></a>
                        <a href="#"><span class="material-symbols-rounded">instagram</span></a>
                        <a href="#"><span class="material-symbols-rounded">youtube</span></a>
                    </div>
                </div>
                <div class="footer-column">
                    <h5>Quick Links</h5>
                    <ul class="footer-links">
                        <li><a href="#">Home</a></li>
                        <li><a href="#">About the Exam</a></li>
                        <li><a href="#">Preparation Resources</a></li>
                        <li><a href="#">FAQ</a></li>
                        <li><a href="#">Contact Us</a></li>
                    </ul>
                </div>
                <div class="footer-column">
                    <h5>Contact Information</h5>
                    <div class="contact-info">
                        <span class="material-symbols-rounded">location_on</span>
                        <p>A. Mabini Campus, Anonas St., Sta. Mesa, Manila, Philippines</p>
                    </div>
                    <div class="contact-info">
                        <span class="material-symbols-rounded">phone</span>
                        <p>(+632) 5335-1787</p>
                    </div>
                    <div class="contact-info">
                        <span class="material-symbols-rounded">email</span>
                        <p>inquire@pup.edu.ph</p>
                    </div>
                </div>
            </div>
            <div class="copyright">
                <p>&copy; <?php echo date('Y'); ?> Polytechnic University of the Philippines. All rights reserved.</p>
            </div>
        </div>
    </footer>
</body>
</html>
