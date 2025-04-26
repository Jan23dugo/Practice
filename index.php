<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PUP Student Qualifying Exam Portal</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0" />
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
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
            --shadow-sm: 0 2px 4px rgba(0, 0, 0, 0.1);
            --shadow-md: 0 4px 6px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
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
            overflow-x: hidden;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            width: 100%;
        }
        
        /* Header Styles */
        header {
            background-color: var(--primary);
            color: var(--text-light);
            padding: 15px 0;
            box-shadow: var(--shadow-md);
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            transition: var(--transition);
        }
        
        header.scrolled {
            padding: 10px 0;
            background-color: rgba(117, 52, 58, 0.95);
            backdrop-filter: blur(10px);
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
            transition: var(--transition);
        }
        
        header.scrolled .logo img {
            height: 50px;
        }
        
        .logo-text {
            display: flex;
            flex-direction: column;
        }
        
        .logo-text h1 {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 4px;
            letter-spacing: 0.5px;
        }
        
        .logo-text p {
            font-size: 14px;
            opacity: 0.9;
        }
        
        .nav-links {
            display: flex;
            gap: 25px;
            align-items: center;
        }
        
        .nav-links a {
            color: var(--text-light);
            text-decoration: none;
            font-weight: 500;
            padding: 8px 16px;
            border-radius: 6px;
            transition: var(--transition);
            position: relative;
        }
        
        .nav-links a::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 0;
            height: 2px;
            background-color: var(--accent);
            transition: var(--transition);
        }
        
        .nav-links a:hover::after {
            width: 80%;
        }
        
        /* Hero Section */
        .hero {
            background: linear-gradient(rgba(117, 52, 58, 0.85), rgba(117, 52, 58, 0.9)), 
                        url('https://www.pup.edu.ph/about/images/campus.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            color: var(--text-light);
            padding: 180px 0 100px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .hero::before {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 100px;
            background: linear-gradient(to top, var(--gray-light), transparent);
        }
        
        .hero h2 {
            font-family: 'Playfair Display', serif;
            font-size: 56px;
            font-weight: 700;
            margin-bottom: 25px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
            animation: fadeInDown 1s ease;
        }
        
        .hero p {
            font-size: 18px;
            max-width: 800px;
            margin: 0 auto 40px;
            opacity: 0.95;
            line-height: 1.8;
            animation: fadeInUp 1s ease 0.3s both;
        }
        
        .cta-buttons {
            display: flex;
            justify-content: center;
            gap: 25px;
            margin-top: 40px;
            animation: fadeInUp 1s ease 0.6s both;
        }
        
        .cta-btn {
            padding: 14px 35px;
            border-radius: 30px;
            font-weight: 500;
            font-size: 16px;
            text-decoration: none;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 10px;
            position: relative;
            overflow: hidden;
            z-index: 1;
        }
        
        .primary-btn {
            background-color: var(--accent);
            color: var(--primary-dark);
            border: 2px solid var(--accent);
        }
        
        .primary-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        
        .secondary-btn {
            background-color: transparent;
            color: var(--text-light);
            border: 2px solid var(--text-light);
        }
        
        .secondary-btn:hover {
            background-color: var(--text-light);
            color: var(--primary);
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        
        /* Features Section */
        .features {
            padding: 100px 0;
            background-color: var(--text-light);
            position: relative;
        }
        
        .section-title {
            text-align: center;
            margin-bottom: 80px;
        }
        
        .section-title h3 {
            font-size: 40px;
            color: var(--primary);
            margin-bottom: 20px;
            font-family: 'Playfair Display', serif;
            font-weight: 600;
        }
        
        .section-title p {
            font-size: 18px;
            color: var(--text-dark);
            max-width: 700px;
            margin: 0 auto;
            opacity: 0.8;
            line-height: 1.8;
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 40px;
            padding: 20px;
        }
        
        .feature-card {
            background-color: var(--gray-light);
            border-radius: 16px;
            padding: 40px 30px;
            text-align: center;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }
        
        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(to right, var(--primary), var(--accent));
            transform: scaleX(0);
            transition: var(--transition);
        }
        
        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: var(--shadow-lg);
        }
        
        .feature-card:hover::before {
            transform: scaleX(1);
        }
        
        .feature-icon {
            background-color: var(--primary);
            color: var(--text-light);
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 25px;
            transition: var(--transition);
        }
        
        .feature-icon span {
            font-size: 35px;
            transition: var(--transition);
        }
        
        .feature-card:hover .feature-icon {
            background-color: var(--accent);
            transform: rotateY(360deg);
        }
        
        .feature-card h4 {
            font-size: 22px;
            margin-bottom: 15px;
            color: var(--primary);
            font-weight: 600;
        }
        
        .feature-card p {
            font-size: 16px;
            color: var(--text-dark);
            opacity: 0.8;
            line-height: 1.7;
        }
        
        /* Footer */
        footer {
            background-color: var(--primary);
            color: var(--text-light);
            padding: 80px 0 20px;
            position: relative;
        }
        
        footer::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(to right, var(--accent), var(--primary-light));
        }
        
        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 60px;
            margin-bottom: 60px;
        }
        
        .footer-column h5 {
            font-size: 20px;
            margin-bottom: 25px;
            position: relative;
            padding-bottom: 15px;
            color: var(--accent);
        }
        
        .footer-column h5::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 50px;
            height: 3px;
            background-color: var(--accent);
        }
        
        .footer-column p {
            margin-bottom: 20px;
            font-size: 15px;
            opacity: 0.9;
            line-height: 1.8;
        }
        
        .footer-links {
            list-style: none;
        }
        
        .footer-links li {
            margin-bottom: 12px;
        }
        
        .footer-links a {
            color: var(--text-light);
            text-decoration: none;
            font-size: 15px;
            opacity: 0.9;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .footer-links a:hover {
            opacity: 1;
            color: var(--accent);
            transform: translateX(5px);
        }
        
        .contact-info {
            display: flex;
            align-items: flex-start;
            gap: 15px;
            margin-bottom: 20px;
            font-size: 15px;
            opacity: 0.9;
        }
        
        .contact-info span {
            font-size: 22px;
            color: var(--accent);
        }
        
        .social-links {
            display: flex;
            gap: 20px;
            margin-top: 30px;
        }
        
        .social-links a {
            color: var(--primary);
            background-color: var(--text-light);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            transition: var(--transition);
            font-size: 18px;
        }
        
        .social-links a:hover {
            background-color: var(--accent);
            color: var(--text-light);
            transform: translateY(-3px);
        }
        
        .copyright {
            text-align: center;
            padding-top: 30px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            font-size: 14px;
            opacity: 0.8;
        }

        /* Animations */
        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
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
        
        /* Responsive Styles */
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                text-align: center;
                gap: 20px;
            }
            
            .nav-links {
                flex-direction: column;
                gap: 15px;
            }
            
            .hero {
                padding: 150px 0 80px;
            }
            
            .hero h2 {
                font-size: 40px;
            }
            
            .hero p {
                font-size: 16px;
                padding: 0 20px;
            }
            
            .cta-buttons {
                flex-direction: column;
                align-items: center;
                gap: 20px;
                padding: 0 20px;
            }
            
            .feature-card {
                padding: 30px 20px;
            }
            
            .footer-content {
                gap: 40px;
            }
            
            .footer-column {
                text-align: center;
            }
            
            .footer-column h5::after {
                left: 50%;
                transform: translateX(-50%);
            }
            
            .social-links {
                justify-content: center;
            }
            
            .contact-info {
                justify-content: center;
            }
        }

        /* New styles for process section */
        .process-section {
            padding: 100px 0;
            background-color: var(--gray-light);
        }

        .process-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 30px;
            margin-top: 50px;
        }

        .process-step {
            background: var(--text-light);
            padding: 30px;
            border-radius: 15px;
            position: relative;
            box-shadow: var(--shadow-md);
            transition: var(--transition);
        }

        .process-step:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }

        .step-number {
            position: absolute;
            top: -20px;
            left: 30px;
            width: 40px;
            height: 40px;
            background: var(--primary);
            color: var(--text-light);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 18px;
        }

        .process-step h4 {
            margin: 20px 0 15px;
            color: var(--primary);
            font-size: 20px;
        }

        .process-step p {
            color: var(--text-dark);
            opacity: 0.8;
            font-size: 15px;
            line-height: 1.6;
        }

        /* Student Portal Button Styles */
        .student-portal-section {
            text-align: center;
            padding: 60px 0;
            background: var(--text-light);
        }

        .portal-buttons {
            display: flex;
            gap: 20px;
            justify-content: center;
            margin-top: 30px;
        }

        .portal-btn {
            padding: 15px 40px;
            border-radius: 30px;
            font-size: 16px;
            font-weight: 500;
            text-decoration: none;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        .login-btn {
            background: var(--primary);
            color: var(--text-light);
        }

        .register-btn {
            background: var(--accent);
            color: var(--primary-dark);
        }

        .portal-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        /* Admin Link Style */
        .admin-link {
            background: rgba(255, 255, 255, 0.1);
            color: var(--text-light);
            padding: 8px 16px;
            border-radius: 20px;
            text-decoration: none;
            font-size: 14px;
            transition: var(--transition);
            border: 1px solid rgba(255, 255, 255, 0.3);
            display: flex;
            align-items: center;
            gap: 8px;
            margin-left: 15px;
        }

        .admin-link:hover {
            background: rgba(255, 255, 255, 0.2);
            border-color: rgba(255, 255, 255, 0.5);
        }

        .admin-link .material-symbols-rounded {
            font-size: 18px;
        }

        @media (max-width: 768px) {
            .admin-link {
                margin: 10px 0;
                justify-content: center;
                background: var(--primary-dark);
            }
            
            .admin-link:hover {
                background: var(--primary);
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
                    <img src="img/Logo.png" alt="PUP Logo">
                    <div class="logo-text">
                        <h1>PUP Qualifying Exam Portal</h1>
                        <p>Polytechnic University of the Philippines</p>
                    </div>
                </div>
                <nav class="nav-links">
                    <a href="#"><span class="material-symbols-rounded">home</span> Home</a>
                    <a href="#"><span class="material-symbols-rounded">info</span> About</a>
                    <a href="#"><span class="material-symbols-rounded">contact_support</span> Contact</a>
                    <a href="#"><span class="material-symbols-rounded">help</span> FAQ</a>
                    <a href="admin_login.php" class="admin-link">
                        <span class="material-symbols-rounded">admin_panel_settings</span>
                        Admin
                    </a>
                </nav>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <h2>CCIS Qualifying Examination Portal</h2>
            <p>Welcome to the College of Computing and Information Sciences (CCIS) qualifying examination portal for transferees, shiftees, and ladderized program students. This examination is a mandatory requirement for admission to BSIT and BSCS programs at PUP.</p>
        </div>
    </section>

    <!-- Student Portal Section -->
    <section class="student-portal-section">
        <div class="container">
            <div class="section-title">
                <h3>CCIS Admission Portal</h3>
                <p>Take your qualifying examination to begin your journey in PUP's College of Computing and Information Sciences</p>
            </div>
            <div class="portal-buttons">
                <a href="stud_register.php" class="portal-btn login-btn">
                    <span class="material-symbols-rounded">login</span>
                    Student Login
                </a>
                <a href="stud_register.php?register=true" class="portal-btn register-btn">
                    <span class="material-symbols-rounded">person_add</span>
                    New Student Registration
                </a>
            </div>
        </div>
    </section>

    <!-- Process Section -->
    <section class="process-section">
        <div class="container">
            <div class="section-title">
                <h3>Admission Process</h3>
                <p>Understanding the steps to complete your CCIS qualifying examination</p>
            </div>
            <div class="process-grid">
                <div class="process-step">
                    <div class="step-number">1</div>
                    <h4>Eligibility Check</h4>
                    <p>Verify that you qualify as either a transferee, shiftee, or ladderized program student seeking admission to BSIT or BSCS programs.</p>
                </div>
                <div class="process-step">
                    <div class="step-number">2</div>
                    <h4>Registration</h4>
                    <p>Create your account and submit required documents including transcript records and transfer credentials.</p>
                </div>
                <div class="process-step">
                    <div class="step-number">3</div>
                    <h4>Examination</h4>
                    <p>Complete the qualifying exam which covers programming fundamentals, logic, and computing concepts.</p>
                </div>
                <div class="process-step">
                    <div class="step-number">4</div>
                    <h4>Results & Admission</h4>
                    <p>Receive your examination results and further instructions for CCIS program admission.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features">
        <div class="container">
            <div class="section-title">
                <h3>Program Information</h3>
                <p>Learn more about our CCIS programs and examination requirements</p>
            </div>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <span class="material-symbols-rounded">computer</span>
                    </div>
                    <h4>BSIT Program</h4>
                    <p>Bachelor of Science in Information Technology focusing on network administration, web technologies, and information systems.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <span class="material-symbols-rounded">code</span>
                    </div>
                    <h4>BSCS Program</h4>
                    <p>Bachelor of Science in Computer Science specializing in software development, algorithms, and computing theory.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <span class="material-symbols-rounded">school</span>
                    </div>
                    <h4>Admission Types</h4>
                    <p>Open to transferees from other schools, shiftees within PUP, and students from ladderized programs.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <span class="material-symbols-rounded">quiz</span>
                    </div>
                    <h4>Exam Coverage</h4>
                    <p>Assessment includes programming logic, mathematics, computing fundamentals, and analytical skills.</p>
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
                    <p>The Polytechnic University of the Philippines is committed to providing quality education through our innovative online examination platform.</p>
                    <div class="social-links">
                        <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                        <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                        <a href="#" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
                <div class="footer-column">
                    <h5>Quick Links</h5>
                    <ul class="footer-links">
                        <li><a href="#"><span class="material-symbols-rounded">chevron_right</span>Home</a></li>
                        <li><a href="#"><span class="material-symbols-rounded">chevron_right</span>About the Exam</a></li>
                        <li><a href="#"><span class="material-symbols-rounded">chevron_right</span>Study Resources</a></li>
                        <li><a href="#"><span class="material-symbols-rounded">chevron_right</span>FAQ</a></li>
                        <li><a href="#"><span class="material-symbols-rounded">chevron_right</span>Contact Support</a></li>
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

    <script>
        // Header scroll effect
        window.addEventListener('scroll', function() {
            const header = document.querySelector('header');
            if (window.scrollY > 50) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        });
    </script>
</body>
</html>
