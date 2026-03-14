Y<?php
require_once 'config/auth.php';
require_once 'config/database.php';

requireRole('admin');

$pageTitle = 'Permissions Management';
include 'includes/header.php';

$conn = getDBConnection();
$message = '';
$messageType = '';

// Handle permission actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['grant_permission'])) {
        $user_id = intval($_POST['user_id']);
        $permission_type = trim($_POST['permission_type']);
        $resource = trim($_POST['resource'] ?? '');
        $granted_by = getCurrentUserId();
        
        if (!empty($permission_type)) {
            $stmt = $conn->prepare("INSERT INTO permissions (user_id, permission_type, resource, granted_by) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("issi", $user_id, $permission_type, $resource, $granted_by);
            
            if ($stmt->execute()) {
                $message = 'Permission granted successfully!';
                $messageType = 'success';
            } else {
                $message = 'Failed to grant permission.';
                $messageType = 'error';
            }
            $stmt->close();
        }
    } elseif (isset($_POST['revoke_permission'])) {
        $permission_id = intval($_POST['permission_id']);
        
        $stmt = $conn->prepare("DELETE FROM permissions WHERE id = ?");
        $stmt->bind_param("i", $permission_id);
        
        if ($stmt->execute()) {
            $message = 'Permission revoked successfully!';
            $messageType = 'success';
        } else {
            $message = 'Failed to revoke permission.';
            $messageType = 'error';
        }
        $stmt->close();
    } elseif (isset($_POST['toggle_permission'])) {
        $permission_id = intval($_POST['permission_id']);
        $granted = intval($_POST['granted']);
        $new_granted = $granted ? 0 : 1;
        
        $stmt = $conn->prepare("UPDATE permissions SET granted = ? WHERE id = ?");
        $stmt->bind_param("ii", $new_granted, $permission_id);
        
        if ($stmt->execute()) {
            $message = 'Permission updated successfully!';
            $messageType = 'success';
        } else {
            $message = 'Failed to update permission.';
            $messageType = 'error';
        }
        $stmt->close();
    }
}

// Get all users
$usersQuery = "SELECT id, username, email, full_name, role FROM users ORDER BY full_name";
$usersResult = $conn->query($usersQuery);
$users = [];
while ($row = $usersResult->fetch_assoc()) {
    $users[] = $row;
}

// Get all permissions
$permissionsQuery = "SELECT p.*, u.username, u.full_name, g.full_name as granted_by_name 
                     FROM permissions p 
                     JOIN users u ON p.user_id = u.id 
                     LEFT JOIN users g ON p.granted_by = g.id 
                     ORDER BY p.granted_at DESC";
$permissionsResult = $conn->query($permissionsQuery);

$conn->close();
?>

<div class="container">
    <h1>Permissions Management</h1>
    
    <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    
    <div class="permissions-section">
        <div class="permissions-grid">
            <div class="permissions-card">
                <h2>Grant New Permission</h2>
                <form method="POST" action="permissions.php">
                    <div class="form-group">
                        <label for="user_id">User</label>
                        <select id="user_id" name="user_id" required>
                            <option value="">Select a user...</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?php echo $user['id']; ?>">
                                    <?php echo htmlspecialchars($user['full_name'] . ' (' . $user['username'] . ') - ' . ucfirst($user['role'])); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="permission_type">Permission Type</label>
                        <select id="permission_type" name="permission_type" required>
                            <option value="">Select permission type...</option>
                            <option value="create_course">Create Course</option>
                            <option value="edit_course">Edit Course</option>
                            <option value="delete_course">Delete Course</option>
                            <option value="manage_users">Manage Users</option>
                            <option value="view_reports">View Reports</option>
                            <option value="manage_permissions">Manage Permissions</option>
                            <option value="access_admin">Access Admin Panel</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="resource">Resource (Optional)</label>
                        <input type="text" id="resource" name="resource" placeholder="e.g., course_id, user_id, etc.">
                    </div>
                    
                    <button type="submit" name="grant_permission" class="btn btn-primary">Grant Permission</button>
                </form>
            </div>
            
            <div class="permissions-card">
                <h2>Current Permissions</h2>
                <div class="permissions-list">
                    <?php if ($permissionsResult && $permissionsResult->num_rows > 0): ?>
                        <?php while ($permission = $permissionsResult->fetch_assoc()): ?>
                            <div class="permission-item">
                                <div class="permission-info">
                                    <strong><?php echo htmlspecialchars($permission['full_name']); ?></strong>
                                    <span class="permission-type"><?php echo htmlspecialchars(str_replace('_', ' ', $permission['permission_type'])); ?></span>
                                    <?php if ($permission['resource']): ?>
                                        <span class="permission-resource">Resource: <?php echo htmlspecialchars($permission['resource']); ?></span>
                                    <?php endif; ?>
                                    <small>Granted by: <?php echo htmlspecialchars($permission['granted_by_name'] ?? 'System'); ?> on <?php echo date('M d, Y', strtotime($permission['granted_at'])); ?></small>
                                </div>
                                <div class="permission-actions">
                                    <form method="POST" action="permissions.php" style="display: inline;">
                                        <input type="hidden" name="permission_id" value="<?php echo $permission['id']; ?>">
                                        <input type="hidden" name="granted" value="<?php echo $permission['granted']; ?>">
                                        <button type="submit" name="toggle_permission" class="btn btn-sm btn-<?php echo $permission['granted'] ? 'warning' : 'success'; ?>">
                                            <?php echo $permission['granted'] ? 'Disable' : 'Enable'; ?>
                                        </button>
                                    </form>
                                    <form method="POST" action="permissions.php" style="display: inline;">
                                        <input type="hidden" name="permission_id" value="<?php echo $permission['id']; ?>">
                                        <button type="submit" name="revoke_permission" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to revoke this permission?');">Revoke</button>
                                    </form>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p>No permissions have been granted yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

