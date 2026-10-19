<?php
/**
 * Admin Profile & Security Settings
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth_check.php';

$pdo = Database::getConnection();
$adminId = $_SESSION['admin_id'] ?? 1;

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $username = trim($_POST['username'] ?? '');
        $fullName = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');

        if (empty($username) || empty($fullName) || empty($email)) {
            $error = 'Username, full name, and email are all required.';
        } elseif (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username)) {
            $error = 'Username must be 3-30 characters and contain only letters, numbers, or underscores.';
        } else {
            try {
                // Check if username already taken by another account
                $check = $pdo->prepare("SELECT admin_id FROM admins WHERE username = ? AND admin_id != ?");
                $check->execute([$username, $adminId]);
                if ($check->fetch()) {
                    $error = 'This username is already taken by another account.';
                } else {
                    $stmt = $pdo->prepare("UPDATE admins SET username = ?, full_name = ?, email = ? WHERE admin_id = ?");
                    $stmt->execute([$username, $fullName, $email, $adminId]);
                    $_SESSION['admin_user']['username'] = $username;
                    $_SESSION['admin_user']['full_name'] = $fullName;
                    $_SESSION['admin_user']['email'] = $email;
                    $success = 'Account profile and username updated successfully!';
                }
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    $error = 'The username or email is already in use.';
                } else {
                    $error = 'Database error: ' . $e->getMessage();
                }
            }
        }
    } elseif ($action === 'change_password') {
        $currentPass = $_POST['current_password'] ?? '';
        $newPass = $_POST['new_password'] ?? '';
        $confirmPass = $_POST['confirm_password'] ?? '';

        if (empty($currentPass) || empty($newPass)) {
            $error = 'Please fill out all password fields.';
        } elseif ($newPass !== $confirmPass) {
            $error = 'New passwords do not match.';
        } elseif (strlen($newPass) < 6) {
            $error = 'Password must be at least 6 characters.';
        } else {
            // Verify current password
            $stmt = $pdo->prepare("SELECT password FROM admins WHERE admin_id = ?");
            $stmt->execute([$adminId]);
            $hash = $stmt->fetchColumn();

            if ($hash && password_verify($currentPass, $hash)) {
                $newHash = password_hash($newPass, PASSWORD_BCRYPT);
                $upd = $pdo->prepare("UPDATE admins SET password = ? WHERE admin_id = ?");
                $upd->execute([$newHash, $adminId]);
                $success = 'Password successfully changed!';
            } else {
                $error = 'Current password is incorrect.';
            }
        }
    }
}

// Fetch current admin info
$stmt = $pdo->prepare("SELECT * FROM admins WHERE admin_id = ?");
$stmt->execute([$adminId]);
$admin = $stmt->fetch();

$pageTitle = 'Admin Profile Settings';
$activePage = 'profile';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
require_once __DIR__ . '/includes/topbar.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Admin Profile & Settings</h1>
        <p class="page-subtitle">Manage administrator credentials, display name, and security preferences</p>
    </div>
</div>

<?php if (!empty($error)): ?>
    <div class="toast toast-danger" style="position:static;margin-bottom:1.5rem;animation:none;">
        <i class="fa-solid fa-circle-exclamation"></i>
        <div><?= e($error) ?></div>
    </div>
<?php endif; ?>

<?php if (!empty($success)): ?>
    <div class="toast toast-success" style="position:static;margin-bottom:1.5rem;animation:none;">
        <i class="fa-solid fa-circle-check"></i>
        <div><?= e($success) ?></div>
    </div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:2rem;align-items:start;">
    <!-- Profile Info Card -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <i class="fa-solid fa-user-gear" style="color:var(--primary);"></i>
                Account Details
            </div>
            <span class="badge badge-primary"><?= e($admin['role'] ?? 'Super Admin') ?></span>
        </div>
        <div class="card-body">
            <form method="POST" action="">
                <input type="hidden" name="action" value="update_profile">

                <div class="form-group">
                    <label class="form-label" for="username">Admin Username *</label>
                    <input type="text" name="username" id="username" class="form-control" value="<?= e($admin['username']) ?>" required pattern="^[a-zA-Z0-9_]{3,30}$" title="3-30 letters, numbers, or underscores">
                    <small style="color:var(--text-muted);font-size:0.75rem;">Used to log into the dashboard (letters, numbers, underscore).</small>
                </div>

                <div class="form-group">
                    <label class="form-label" for="full_name">Full Display Name *</label>
                    <input type="text" name="full_name" id="full_name" class="form-control" value="<?= e($admin['full_name']) ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="email">Email Address *</label>
                    <input type="email" name="email" id="email" class="form-control" value="<?= e($admin['email']) ?>" required>
                </div>

                <div class="form-group" style="font-size:0.8rem;color:var(--text-muted);">
                    <div>Member Since: <strong><?= format_date($admin['created_at']) ?></strong></div>
                    <div>Last Login: <strong><?= $admin['last_login'] ? format_date($admin['last_login'], 'M d, Y H:i:s') : 'Current Session' ?></strong></div>
                </div>

                <button type="submit" class="btn btn-primary" style="margin-top:0.5rem;">
                    <i class="fa-solid fa-floppy-disk"></i> Save Profile Details
                </button>
            </form>
        </div>
    </div>

    <!-- Security & Password Card -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <i class="fa-solid fa-shield-halved" style="color:var(--warning);"></i>
                Change Password
            </div>
        </div>
        <div class="card-body">
            <form method="POST" action="">
                <input type="hidden" name="action" value="change_password">

                <div class="form-group">
                    <label class="form-label" for="current_password">Current Password *</label>
                    <input type="password" name="current_password" id="current_password" class="form-control" required placeholder="Enter current password">
                </div>

                <div class="form-group">
                    <label class="form-label" for="new_password">New Password *</label>
                    <input type="password" name="new_password" id="new_password" class="form-control" required minlength="6" placeholder="Min. 6 characters">
                </div>

                <div class="form-group">
                    <label class="form-label" for="confirm_password">Confirm New Password *</label>
                    <input type="password" name="confirm_password" id="confirm_password" class="form-control" required minlength="6" placeholder="Re-enter new password">
                </div>

                <button type="submit" class="btn btn-secondary" style="margin-top:0.5rem;">
                    <i class="fa-solid fa-key"></i> Update Password
                </button>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
