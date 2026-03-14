<?php
require_once 'config/auth.php';
require_once 'config/database.php';

$pageTitle = 'Home';
include 'includes/header.php';

$conn = getDBConnection();
$courseStmt = $conn->prepare("SELECT COUNT(*) as total FROM courses WHERE status = 'active'");
$courseStmt->execute();
$courseResult = $courseStmt->get_result();
$totalCourses = $courseResult->fetch_assoc()['total'];
$courseStmt->close();

$userStmt = $conn->prepare("SELECT COUNT(*) as total FROM users WHERE role = 'student'");
$userStmt->execute();
$userResult = $userStmt->get_result();
$totalStudents = $userResult->fetch_assoc()['total'];
$userStmt->close();

$conn->close();
?>

<div class="container">
    <div class="hero-section">
        <h1>Welcome to Masar Training and Development Platform</h1>
        <p class="hero-subtitle">Empower yourself with quality education and professional development</p>
        
        <?php if (!isLoggedIn()): ?>
            <div class="hero-actions">
                <a href="register.php" class="btn btn-primary btn-large">Get Started</a>
                <a href="login.php" class="btn btn-secondary btn-large">Login</a>
            </div>
        <?php else: ?>
            <div class="hero-actions">
                <a href="courses.php" class="btn btn-primary btn-large">Browse Courses</a>
                <a href="dashboard.php" class="btn btn-secondary btn-large">My Dashboard</a>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="stats-section">
        <div class="stat-card">
            <h3><?php echo $totalCourses; ?></h3>
            <p>Active Courses</p>
        </div>
        <div class="stat-card">
            <h3><?php echo $totalStudents; ?></h3>
            <p>Registered Students</p>
        </div>
        <div class="stat-card">
            <h3>24/7</h3>
            <p>Access Anytime</p>
        </div>
    </div>
    
    <div class="features-section">
        <h2>Why Choose Masar Training?</h2>
        <div class="features-grid">
            <div class="feature-card">
                <h3>Expert Instructors</h3>
                <p>Learn from industry professionals with years of experience</p>
            </div>
            <div class="feature-card">
                <h3>Flexible Learning</h3>
                <p>Study at your own pace with our comprehensive course materials</p>
            </div>
            <div class="feature-card">
                <h3>Track Progress</h3>
                <p>Monitor your learning journey with detailed statistics and analytics</p>
            </div>
            <div class="feature-card">
                <h3>Certification</h3>
                <p>Earn certificates upon course completion</p>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

