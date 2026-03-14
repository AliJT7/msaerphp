<?php
require_once 'config/auth.php';
require_once 'config/database.php';
require_once 'config/language.php';

$error = '';
$success = '';

// If already logged in, redirect to dashboard
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // تنظيف المدخلات
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $full_name = trim($_POST['full_name'] ?? '');

    // التحقق من المدخلات
    require_once 'config/language.php';
    if (empty($username) || empty($email) || empty($password) || empty($full_name)) {
        $error = t('fill_all_fields');
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = t('invalid_email');
    } elseif (strlen($password) < 6) {
        $error = t('password_too_short');
    } elseif ($password !== $confirm_password) {
        $error = t('passwords_not_match');
    } else {

        // الاتصال بقاعدة البيانات
        $conn = getDBConnection();

        // التحقق من وجود المستخدم
        $checkStmt = $conn->prepare(
            "SELECT id FROM users WHERE username = ? OR email = ?"
        );
        $checkStmt->bind_param("ss", $username, $email);
        $checkStmt->execute();
        $result = $checkStmt->get_result();

        if ($result->num_rows > 0) {
            require_once 'config/language.php';
            $error = t('username_exists');
            $checkStmt->close();

        } else {

            $checkStmt->close();

            // تشفير كلمة المرور
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // إدخال المستخدم
            $insertStmt = $conn->prepare(
                "INSERT INTO users (username, email, password, full_name, role)
                 VALUES (?, ?, ?, ?, 'student')"
            );
            $insertStmt->bind_param(
                "ssss",
                $username,
                $email,
                $hashed_password,
                $full_name
            );

            if ($insertStmt->execute()) {
                require_once 'config/language.php';
                $user_id = $conn->insert_id;

                // إنشاء سجل الإحصائيات
                $statsStmt = $conn->prepare(
                    "INSERT INTO user_statistics (user_id) VALUES (?)"
                );
                $statsStmt->bind_param("i", $user_id);
                $statsStmt->execute();
                $statsStmt->close();

                $success = t('registration_success') . ' <a href="login.php">' . t('login_here') . '</a>.';

            } else {
                require_once 'config/language.php';
                $error = t('registration_failed');
            }

            $insertStmt->close();
        }

        // 🔐 إغلاق الاتصال مرة واحدة فقط
        $conn->close();
    }
}

$pageTitle = 'Register';
include 'includes/header.php';
?>

<div class="container">
    <div class="auth-container">
        <div class="auth-image-side">
            <img src="assets/images/auth-image.jpg" alt="Masar Training Platform" class="auth-image" onerror="this.style.display='none'">
            <div class="auth-image-overlay">
                <h2><?php require_once 'config/language.php'; echo t('join_us'); ?></h2>
                <p><?php echo t('start_journey'); ?></p>
            </div>
        </div>
        <div class="auth-card">
            <h2><?php require_once 'config/language.php'; echo t('register_title'); ?></h2>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="register.php">
                <div class="form-group">
                    <label for="full_name"><?php require_once 'config/language.php'; echo t('full_name'); ?></label>
                    <input type="text" id="full_name" name="full_name" required>
                </div>

                <div class="form-group">
                    <label for="username"><?php echo t('username'); ?></label>
                    <input type="text" id="username" name="username" required>
                </div>

                <div class="form-group">
                    <label for="email"><?php echo t('email'); ?></label>
                    <input type="email" id="email" name="email" required>
                </div>

                <div class="form-group">
                    <label for="password"><?php echo t('password'); ?></label>
                    <input type="password" id="password" name="password" required minlength="6">
                </div>

                <div class="form-group">
                    <label for="confirm_password"><?php echo t('confirm_password'); ?></label>
                    <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
                </div>

                <button type="submit" class="btn btn-primary"><?php echo t('register_button'); ?></button>
            </form>

            <p class="auth-link">
                <?php echo t('have_account'); ?> <a href="login.php"><?php echo t('login_here'); ?></a>
            </p>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
