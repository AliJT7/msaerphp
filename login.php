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

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = t('fill_all_fields');
    } else {
        $conn = getDBConnection();
        $stmt = $conn->prepare("
            SELECT id, username, email, password, full_name, role 
            FROM users 
            WHERE username = ? OR email = ?
        ");
        $stmt->bind_param("ss", $username, $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id']   = $user['id'];
                $_SESSION['username']  = $user['username'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['full_name'] = $user['full_name'];

                $updateStmt = $conn->prepare("
                    INSERT INTO user_statistics (user_id, last_login)
                    VALUES (?, NOW())
                    ON DUPLICATE KEY UPDATE last_login = NOW()
                ");
                $updateStmt->bind_param("i", $user['id']);
                $updateStmt->execute();
                $updateStmt->close();

                $stmt->close();
                $conn->close();

                header('Location: dashboard.php');
                exit();
            } else {
                $error = t('invalid_credentials');
            }
        } else {
            $error = t('invalid_credentials');
        }

        $stmt->close();
        $conn->close();
    }
}

$pageTitle = 'Login';
include 'includes/header.php';
?>

<div class="container">
    <div class="auth-container">

        <!-- LEFT SIDE -->
        <div class="auth-image-side">
            <div class="auth-image-overlay">
                <h2><?php echo t('welcome_back'); ?></h2>
                <p><?php echo t('continue_learning'); ?></p>
            </div>
        </div>

        <!-- RIGHT SIDE -->
        <div class="auth-card">

            <!-- LOGO -->
            <div class="login-logo">
                <img src="assets/images/logo.png" alt="Masar Training Logo">
            </div>

            <h2><?php echo t('login_title'); ?></h2>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="login.php">
                <div class="form-group">
                    <label for="username"><?php echo t('username_or_email'); ?></label>
                    <input type="text" id="username" name="username" required autofocus>
                </div>

                <div class="form-group">
                    <label for="password"><?php echo t('password'); ?></label>
                    <input type="password" id="password" name="password" required>
                </div>

                <button type="submit" class="btn btn-primary">
                    <?php echo t('login_button'); ?>
                </button>
            </form>

            <p class="auth-link">
                <?php echo t('no_account'); ?>
                <a href="register.php"><?php echo t('register_here'); ?></a>
            </p>

        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
