<?php
require_once 'config/auth.php';
require_once 'config/database.php';

requireLogin();

$pageTitle = 'Dashboard';
include 'includes/header.php';

$conn = getDBConnection();
$user_id = getCurrentUserId();

// Get user information
$userStmt = $conn->prepare("SELECT username, email, full_name, role, created_at FROM users WHERE id = ?");
$userStmt->bind_param("i", $user_id);
$userStmt->execute();
$user = $userStmt->get_result()->fetch_assoc();
$userStmt->close();

// Get user statistics
$statsStmt = $conn->prepare("SELECT * FROM user_statistics WHERE user_id = ?");
$statsStmt->bind_param("i", $user_id);
$statsStmt->execute();
$stats = $statsStmt->get_result()->fetch_assoc();
$statsStmt->close();

// If no stats exist, create them
if (!$stats) {
    $createStatsStmt = $conn->prepare("INSERT INTO user_statistics (user_id) VALUES (?)");
    $createStatsStmt->bind_param("i", $user_id);
    $createStatsStmt->execute();
    $createStatsStmt->close();
    
    // Re-fetch stats
    $statsStmt = $conn->prepare("SELECT * FROM user_statistics WHERE user_id = ?");
    $statsStmt->bind_param("i", $user_id);
    $statsStmt->execute();
    $stats = $statsStmt->get_result()->fetch_assoc();
    $statsStmt->close();
}

// Get enrolled courses
$enrollmentsQuery = "SELECT c.*, e.status, e.progress_percentage, e.enrollment_date 
                     FROM enrollments e 
                     JOIN courses c ON e.course_id = c.id 
                     WHERE e.user_id = ? 
                     ORDER BY e.enrollment_date DESC 
                     LIMIT 5";
$enrollmentsStmt = $conn->prepare($enrollmentsQuery);
$enrollmentsStmt->bind_param("i", $user_id);
$enrollmentsStmt->execute();
$enrollments = $enrollmentsStmt->get_result();

// Calculate additional stats
$totalEnrolled = $stats['total_courses_enrolled'] ?? 0;
$totalCompleted = $stats['total_courses_completed'] ?? 0;
$totalHours = $stats['total_learning_hours'] ?? 0;
$completionRate = $totalEnrolled > 0 ? round(($totalCompleted / $totalEnrolled) * 100, 1) : 0;

$conn->close();
?>

<div class="container">
    <div class="dashboard-header">
        <h1>Welcome, <?php echo htmlspecialchars($user['full_name']); ?>!</h1>
        <p class="user-role">Role: <span class="badge badge-<?php echo $user['role'] === 'admin' ? 'danger' : ($user['role'] === 'instructor' ? 'warning' : 'info'); ?>"><?php echo ucfirst($user['role']); ?></span></p>
    </div>
    
    <div class="stats-grid">
        <div class="stat-card-large">
            <div class="stat-icon">📚</div>
            <div class="stat-content">
                <h3><?php echo $totalEnrolled; ?></h3>
                <p>Courses Enrolled</p>
            </div>
        </div>
        
        <div class="stat-card-large">
            <div class="stat-icon">✅</div>
            <div class="stat-content">
                <h3><?php echo $totalCompleted; ?></h3>
                <p>Courses Completed</p>
            </div>
        </div>
        
        <div class="stat-card-large">
            <div class="stat-icon">⏱️</div>
            <div class="stat-content">
                <h3><?php echo number_format($totalHours, 1); ?></h3>
                <p>Learning Hours</p>
            </div>
        </div>
        
        <div class="stat-card-large">
            <div class="stat-icon">📊</div>
            <div class="stat-content">
                <h3><?php echo $completionRate; ?>%</h3>
                <p>Completion Rate</p>
            </div>
        </div>
    </div>
    
    <div class="dashboard-section">
        <h2>My Recent Enrollments</h2>
        <div class="enrollments-list">
            <?php if ($enrollments->num_rows > 0): ?>
                <?php while ($enrollment = $enrollments->fetch_assoc()): ?>
                    <div class="enrollment-item">
                        <div class="enrollment-info">
                            <h4><?php echo htmlspecialchars($enrollment['title']); ?></h4>
                            <p class="enrollment-meta">
                                Enrolled: <?php echo date('M d, Y', strtotime($enrollment['enrollment_date'])); ?> | 
                                Status: <span class="badge badge-<?php echo $enrollment['status'] === 'completed' ? 'success' : 'info'; ?>"><?php echo ucfirst(str_replace('_', ' ', $enrollment['status'])); ?></span>
                            </p>
                        </div>
                        <div class="enrollment-progress">
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo $enrollment['progress_percentage']; ?>%"></div>
                            </div>
                            <small><?php echo $enrollment['progress_percentage']; ?>% Complete</small>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>You haven't enrolled in any courses yet. <a href="courses.php">Browse courses</a> to get started!</p>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="dashboard-section">
        <h2>Account Information</h2>
        <div class="info-card">
            <div class="info-row">
                <strong>Username:</strong> <?php echo htmlspecialchars($user['username']); ?>
            </div>
            <div class="info-row">
                <strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?>
            </div>
            <div class="info-row">
                <strong>Member Since:</strong> <?php echo date('F d, Y', strtotime($user['created_at'])); ?>
            </div>
            <?php if ($stats['last_login']): ?>
                <div class="info-row">
                    <strong>Last Login:</strong> <?php echo date('F d, Y H:i', strtotime($stats['last_login'])); ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

