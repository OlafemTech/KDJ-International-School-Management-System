<?php
session_start();
error_reporting(0);
include('includes/dbconnection.php');

try {
    $dbh = new PDO("mysql:host=localhost;dbname=studentmsdb", "root", "");
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get enhanced statistics including subjects
    $stmt = $dbh->prepare("SELECT 
        (SELECT COUNT(*) FROM tblstudents) as students,
        (SELECT COUNT(*) FROM tblclass) as classes,
        (SELECT COUNT(*) FROM tblteacher) as teachers,
        (SELECT COUNT(*) FROM tblsubjects) as subjects,
        (SELECT COUNT(*) FROM tblsubjectteacherclass) as subject_assignments");
    $stmt->execute();
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = "Database connection failed. Please try again later.";
}
?>
<!doctype html>
<html>
<head>
<title>KDJ School Management System</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<!--bootstrap-->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
<!--custom css-->
<style>
:root {
    --primary-color: #2196F3;
    --secondary-color: #1976D2;
    --accent-color: #FFC107;
    --text-color: #333;
    --light-bg: #f8f9fa;
    --dark-bg: #343a40;
}

body {
    font-family: 'Poppins', sans-serif;
    background: var(--light-bg);
}

.hero-section {
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    position: relative;
    overflow: hidden;
}

.hero-content {
    text-align: center;
    padding: 2rem;
    max-width: 800px;
    z-index: 2;
}

.hero-title {
    font-size: 3.5rem;
    font-weight: 700;
    margin-bottom: 1.5rem;
    text-transform: uppercase;
}

.hero-subtitle {
    font-size: 1.5rem;
    margin-bottom: 2rem;
    opacity: 0.9;
}

.login-btn {
    background: white;
    color: var(--primary-color);
    padding: 1rem 2.5rem;
    border-radius: 50px;
    font-size: 1.2rem;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.3s ease;
    display: inline-block;
    margin: 10px;
}

.login-btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.2);
    color: var(--secondary-color);
}

.admin-btn {
    background: var(--dark-bg);
    color: white;
}

.admin-btn:hover {
    color: white;
    background: var(--secondary-color);
}

.animated-bg {
    position: absolute;
    width: 100%;
    height: 100%;
    top: 0;
    left: 0;
    background: linear-gradient(45deg, rgba(255,255,255,0.1) 25%, transparent 25%),
                linear-gradient(-45deg, rgba(255,255,255,0.1) 25%, transparent 25%),
                linear-gradient(45deg, transparent 75%, rgba(255,255,255,0.1) 75%),
                linear-gradient(-45deg, transparent 75%, rgba(255,255,255,0.1) 75%);
    background-size: 20px 20px;
    animation: backgroundMove 20s linear infinite;
    opacity: 0.3;
}

.section-title {
    text-align: center;
    margin-bottom: 3rem;
    position: relative;
    padding-bottom: 1rem;
}

.section-title::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 50%;
    transform: translateX(-50%);
    width: 50px;
    height: 3px;
    background: var(--primary-color);
}

.features-section {
    padding: 4rem 0;
    background: white;
}

.feature-card {
    padding: 2rem;
    text-align: center;
    border-radius: 10px;
    transition: all 0.3s ease;
    height: 100%;
    background: white;
    box-shadow: 0 5px 15px rgba(0,0,0,0.05);
}

.feature-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.1);
}

.feature-icon {
    font-size: 2.5rem;
    color: var(--primary-color);
    margin-bottom: 1rem;
}

.stats-section {
    padding: 3rem 0;
    background: var(--light-bg);
}

.stat-card {
    background: white;
    padding: 1.5rem;
    border-radius: 10px;
    text-align: center;
    box-shadow: 0 5px 15px rgba(0,0,0,0.05);
}

.stat-icon {
    font-size: 2.5rem;
    color: var(--primary-color);
    margin-bottom: 1rem;
}

.stat-number {
    font-size: 2.5rem;
    font-weight: 700;
    color: var(--primary-color);
    margin-bottom: 0.5rem;
}

.quick-links {
    position: fixed;
    right: 20px;
    bottom: 20px;
    z-index: 1000;
}

.quick-link-btn {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: white;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 10px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    color: var(--primary-color);
    text-decoration: none;
    transition: all 0.3s ease;
}

.quick-link-btn:hover {
    transform: scale(1.1);
    color: var(--secondary-color);
}

.steps-section {
    padding: 4rem 0;
    background: white;
}

.step-card {
    text-align: center;
    padding: 2rem;
    position: relative;
}

.step-number {
    width: 40px;
    height: 40px;
    background: var(--primary-color);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1rem;
    font-weight: bold;
}

.benefits-section {
    padding: 4rem 0;
    background: var(--dark-bg);
    color: white;
}

.benefit-item {
    display: flex;
    align-items: center;
    margin-bottom: 2rem;
}

.benefit-icon {
    font-size: 2rem;
    color: var(--accent-color);
    margin-right: 1rem;
}

.student-resources {
    padding: 4rem 0;
    background: var(--light-bg);
}

.resource-card {
    background: white;
    border-radius: 10px;
    padding: 2rem;
    height: 100%;
    transition: all 0.3s ease;
    box-shadow: 0 5px 15px rgba(0,0,0,0.05);
}

.resource-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.1);
}

.faq-section {
    padding: 4rem 0;
    background: white;
}

.accordion-item {
    border: none;
    margin-bottom: 1rem;
    background: var(--light-bg);
    border-radius: 10px;
    overflow: hidden;
}

.accordion-button {
    background: var(--light-bg);
    font-weight: 600;
    padding: 1.5rem;
}

.accordion-button:not(.collapsed) {
    background: var(--primary-color);
    color: white;
}

.accordion-button:focus {
    box-shadow: none;
    border-color: rgba(0,0,0,0.125);
}

.highlights-section {
    padding: 4rem 0;
    background: linear-gradient(135deg, var(--dark-bg), var(--secondary-color));
    color: white;
}

.highlight-card {
    background: rgba(255,255,255,0.1);
    border-radius: 10px;
    padding: 2rem;
    text-align: center;
    backdrop-filter: blur(10px);
    transition: all 0.3s ease;
}

.highlight-card:hover {
    transform: translateY(-5px);
    background: rgba(255,255,255,0.2);
}

.highlight-icon {
    font-size: 3rem;
    color: var(--accent-color);
    margin-bottom: 1rem;
}

.guide-section {
    padding: 4rem 0;
    background: var(--light-bg);
    position: relative;
    overflow: hidden;
}

.guide-card {
    background: white;
    border-radius: 10px;
    padding: 2rem;
    height: 100%;
    position: relative;
    z-index: 2;
    box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    transition: all 0.3s ease;
}

.guide-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.1);
}

.guide-icon {
    width: 60px;
    height: 60px;
    background: var(--light-bg);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 1.5rem;
}

.guide-icon i {
    font-size: 1.8rem;
    color: var(--primary-color);
}

.guide-step {
    font-size: 0.9rem;
    color: var(--primary-color);
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-bottom: 0.5rem;
}

@keyframes backgroundMove {
    0% { background-position: 0 0; }
    100% { background-position: 40px 40px; }
}

@media (max-width: 768px) {
    .hero-title {
        font-size: 2.5rem;
    }
    .hero-subtitle {
        font-size: 1.2rem;
    }
    .stat-number {
        font-size: 2rem;
    }
}
</style>
<!--fonts-->
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<!--scripts-->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
    <?php include_once('includes/header.php'); ?>
    <main>
        <section class="hero-section">
            <div class="animated-bg"></div>
            <div class="hero-content">
                <h1 class="hero-title">Welcome to KDJ International School</h1>
                <p class="hero-subtitle">Access your personalized learning portal</p>
                <div class="d-flex justify-content-center gap-3">
                    <a href="user/login.php" class="login-btn">
                        <i class="bi bi-mortarboard me-2"></i>Student Portal
                    </a>
                    <a href="teacher/login.php" class="login-btn">
                        <i class="bi bi-person-workspace me-2"></i>Teacher Portal
                    </a>
                    <a href="admin/login.php" class="login-btn admin-btn">
                        <i class="bi bi-shield-lock me-2"></i>Admin Portal
                    </a>
                </div>
            </div>
        </section>

        <!-- <section class="stats-section">
            <div class="container">
                <h2 class="section-title">Our Numbers</h2>
                <div class="row g-4">
                    <div class="col-md-3">
                        <div class="stat-card">
                            <i class="bi bi-mortarboard-fill stat-icon"></i>
                            <div class="stat-number"><?php echo $stats['students']; ?></div>
                            <p class="stat-label">Students</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <i class="bi bi-person-workspace stat-icon"></i>
                            <div class="stat-number"><?php echo $stats['teachers']; ?></div>
                            <p class="stat-label">Teachers</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <i class="bi bi-book-fill stat-icon"></i>
                            <div class="stat-number"><?php echo $stats['subjects']; ?></div>
                            <p class="stat-label">Subjects</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <i class="bi bi-grid-3x3-gap-fill stat-icon"></i>
                            <div class="stat-number"><?php echo $stats['classes']; ?></div>
                            <p class="stat-label">Classes</p>
                        </div>
                    </div>
                </div>
            </div>
        </section> -->

        <!-- <section class="features-section">
            <div class="container">
                <h2 class="section-title">Portal Features</h2>
                <div class="row g-4">
                    <div class="col-md-4">
                        <div class="feature-card">
                            <i class="bi bi-mortarboard feature-icon"></i>
                            <h3>Student Portal</h3>
                            <p>Access class schedules, assignments, and track your academic progress.</p>
                            <a href="user/login.php" class="btn btn-outline-primary mt-3">Login as Student</a>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="feature-card">
                            <i class="bi bi-person-workspace feature-icon"></i>
                            <h3>Teacher Portal</h3>
                            <p>Manage subjects, assignments, and track student performance.</p>
                            <a href="teacher/login.php" class="btn btn-outline-primary mt-3">Login as Teacher</a>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="feature-card">
                            <i class="bi bi-shield-lock feature-icon"></i>
                            <h3>Admin Portal</h3>
                            <p>Complete school management and administrative controls.</p>
                            <a href="admin/login.php" class="btn btn-outline-primary mt-3">Login as Admin</a>
                        </div>
                    </div>
                </div>
            </div>
        </section> -->

        <!-- <section class="student-resources">
            <div class="container">
                <h2 class="section-title">Quick Access</h2>
                <div class="row g-4">
                    <div class="col-md-3">
                        <div class="feature-card">
                            <i class="bi bi-calendar-check feature-icon"></i>
                            <h3>Class Schedule</h3>
                            <p>View your daily and weekly class schedules with subject details.</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="feature-card">
                            <i class="bi bi-book feature-icon"></i>
                            <h3>My Subjects</h3>
                            <p>Access your subject list, materials, and assigned teachers.</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="feature-card">
                            <i class="bi bi-pencil-square feature-icon"></i>
                            <h3>Homework</h3>
                            <p>Track and submit your homework for different subjects.</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="feature-card">
                            <i class="bi bi-bell feature-icon"></i>
                            <h3>Notices</h3>
                            <p>Stay updated with important announcements and notices.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="steps-section">
            <div class="container">
                <h2 class="section-title">How It Works</h2>
                <div class="row g-4">
                    <div class="col-md-3">
                        <div class="step-card">
                            <div class="step-number">1</div>
                            <h4>Register</h4>
                            <p>Create your student account with basic information</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="step-card">
                            <div class="step-number">2</div>
                            <h4>Login</h4>
                            <p>Access your personalized dashboard</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="step-card">
                            <div class="step-number">3</div>
                            <h4>Get ID Card</h4>
                            <p>Generate your digital student ID</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="step-card">
                            <div class="step-number">4</div>
                            <h4>Start Learning</h4>
                            <p>Access all student features and resources</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="benefits-section">
            <div class="container">
                <h2 class="section-title">Student Benefits</h2>
                <div class="row">
                    <div class="col-md-6">
                        <div class="benefit-item">
                            <i class="bi bi-check-circle-fill benefit-icon"></i>
                            <div>
                                <h4>Digital ID Cards</h4>
                                <p>Easy access to your student identification</p>
                            </div>
                        </div>
                        <div class="benefit-item">
                            <i class="bi bi-check-circle-fill benefit-icon"></i>
                            <div>
                                <h4>Homework Tracking</h4>
                                <p>Never miss an assignment deadline</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="benefit-item">
                            <i class="bi bi-check-circle-fill benefit-icon"></i>
                            <div>
                                <h4>Class Schedule</h4>
                                <p>Easy access to your daily schedule</p>
                            </div>
                        </div>
                        <div class="benefit-item">
                            <i class="bi bi-check-circle-fill benefit-icon"></i>
                            <div>
                                <h4>Teacher Connect</h4>
                                <p>Direct access to your teachers</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section> -->

        <section class="faq-section">
            <div class="container">
                <h2 class="section-title">Frequently Asked Questions</h2>
                <div class="accordion" id="faqAccordion">
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                How do I get my student ID card?
                            </button>
                        </h2>
                        <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                After logging in, navigate to the ID Card section. You can generate and download your digital ID card instantly.
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                How do I submit homework assignments?
                            </button>
                        </h2>
                        <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Access the Homework Management section from your dashboard. You can view assignments and submit them directly through the platform.
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                Where can I find my class schedule?
                            </button>
                        </h2>
                        <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Your class schedule is available in the Class Management section. View daily and weekly schedules at a glance.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <div class="quick-links">
            <a href="user/login.php" class="quick-link-btn" title="Student Portal">
                <i class="bi bi-person"></i>
            </a>
            <a href="admin/login.php" class="quick-link-btn" title="Admin Portal">
                <i class="bi bi-shield-lock"></i>
            </a>
            <a href="#" class="quick-link-btn" onclick="window.scrollTo({top: 0, behavior: 'smooth'})" title="Back to Top">
                <i class="bi bi-arrow-up"></i>
            </a>
        </div>
    </main>

    <?php include_once('includes/footer.php'); ?>
</body>
</html>
