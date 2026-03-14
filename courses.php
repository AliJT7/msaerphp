<?php
require_once 'config/auth.php';
require_once 'config/database.php';

requireLogin();

$pageTitle = 'Courses';
include 'includes/header.php';

$conn = getDBConnection();
$user_id = getCurrentUserId();

// Get all active courses
$coursesQuery = "SELECT c.*, u.full_name as instructor_name 
                 FROM courses c 
                 LEFT JOIN users u ON c.instructor_id = u.id 
                 WHERE c.status = 'active' 
                 ORDER BY c.created_at DESC";
$coursesResult = $conn->query($coursesQuery);

// Get user's enrollments
$enrollmentsQuery = "SELECT course_id, status, progress_percentage FROM enrollments WHERE user_id = ?";
$enrollmentsStmt = $conn->prepare($enrollmentsQuery);
$enrollmentsStmt->bind_param("i", $user_id);
$enrollmentsStmt->execute();
$enrollmentsResult = $enrollmentsStmt->get_result();
$enrollments = [];
while ($row = $enrollmentsResult->fetch_assoc()) {
    $enrollments[$row['course_id']] = $row;
}
$enrollmentsStmt->close();

// Handle enrollment
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enroll'])) {
    $course_id = intval($_POST['course_id']);
    
    // Check if already enrolled
    if (!isset($enrollments[$course_id])) {
        $enrollStmt = $conn->prepare("INSERT INTO enrollments (user_id, course_id, status) VALUES (?, ?, 'enrolled')");
        $enrollStmt->bind_param("ii", $user_id, $course_id);
        
        if ($enrollStmt->execute()) {
            // Update user statistics
            $updateStatsStmt = $conn->prepare("UPDATE user_statistics SET total_courses_enrolled = total_courses_enrolled + 1 WHERE user_id = ?");
            $updateStatsStmt->bind_param("i", $user_id);
            $updateStatsStmt->execute();
            $updateStatsStmt->close();
            
            $message = 'Successfully enrolled in the course!';
            $messageType = 'success';
            // Refresh enrollments
            header('Location: courses.php');
            exit();
        } else {
            $message = 'Failed to enroll. Please try again.';
            $messageType = 'error';
        }
        $enrollStmt->close();
    } else {
        $message = 'You are already enrolled in this course.';
        $messageType = 'error';
    }
}

$conn->close();
?>

<div class="container">
    <h1>Available Courses</h1>
    
    <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    
    <div class="courses-grid">
        <?php if ($coursesResult && $coursesResult->num_rows > 0): ?>
            <?php while ($course = $coursesResult->fetch_assoc()): ?>
                <div class="course-card">
                    <div class="course-header">
                        <h3><?php echo htmlspecialchars($course['title']); ?></h3>
                        <span class="course-category"><?php echo htmlspecialchars($course['category']); ?></span>
                    </div>
                    
                    <p class="course-description"><?php echo htmlspecialchars($course['description']); ?></p>
                    
                    <div class="course-info">
                        <div class="info-item">
                            <strong>Instructor:</strong> <?php echo htmlspecialchars($course['instructor_name'] ?? 'TBA'); ?>
                        </div>
                        <div class="info-item">
                            <strong>Duration:</strong> <?php echo $course['duration_hours']; ?> hours
                        </div>
                        <div class="info-item">
                            <strong>Price:</strong> $<?php echo number_format($course['price'], 2); ?>
                        </div>
                    </div>
                    
                    <?php if (isset($enrollments[$course['id']])): ?>
                        <div class="course-status">
                            <span class="badge badge-<?php echo $enrollments[$course['id']]['status'] === 'completed' ? 'success' : 'info'; ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $enrollments[$course['id']]['status'])); ?>
                            </span>
                            <?php if ($enrollments[$course['id']]['status'] !== 'completed'): ?>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?php echo $enrollments[$course['id']]['progress_percentage']; ?>%"></div>
                                </div>
                                <small>Progress: <?php echo $enrollments[$course['id']]['progress_percentage']; ?>%</small>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <form method="POST" action="courses.php" style="margin-top: 15px;">
                            <input type="hidden" name="course_id" value="<?php echo $course['id']; ?>">
                            <button type="submit" name="enroll" class="btn btn-primary btn-block">Enroll Now</button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No courses available at the moment.</p>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

