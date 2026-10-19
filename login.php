<?php
/**
 * ShopAdmin Authentication Page
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

// If already logged in, redirect to dashboard
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    redirect('index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ? OR email = ?");
        $stmt->execute([$username, $username]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password'])) {
            // Authentication successful
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id'] = $admin['admin_id'];
            $_SESSION['admin_user'] = [
                'id' => $admin['admin_id'],
                'username' => $admin['username'],
                'full_name' => $admin['full_name'],
                'email' => $admin['email'],
                'role' => $admin['role']
            ];

            // Update last login
            try {
                $upd = $pdo->prepare("UPDATE admins SET last_login = NOW() WHERE admin_id = ?");
                $upd->execute([$admin['admin_id']]);
            } catch (Exception $e) {}

            set_flash('success', 'Welcome back, ' . htmlspecialchars($admin['full_name']) . '!');
            redirect('index.php');
        } else {
            $error = 'Invalid username or password. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In | <?= APP_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/dashboard.css">
</head>
<body class="login-body">
    <div class="login-glow"></div>
    <div class="login-glow-2"></div>

    <div class="login-card">
        <div class="login-brand">
            <div class="brand-icon" style="background:linear-gradient(135deg, #6366f1 0%, #a855f7 50%, #ec4899 100%);box-shadow:0 8px 24px rgba(168,85,247,0.35);">
                <i class="fa-solid fa-shapes"></i>
            </div>
            <h1 style="font-family:'Plus Jakarta Sans',sans-serif;letter-spacing:-0.03em;font-weight:800;">ShopSphere <span style="background:linear-gradient(135deg,#818cf8,#ec4899);-webkit-background-clip:text;-webkit-text-fill-color:transparent;">Admin</span></h1>
            <p>Access the unified <strong>shop_db</strong> commerce operations portal</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="toast toast-danger" style="position:static;margin-bottom:1.25rem;animation:none;">
                <i class="fa-solid fa-circle-exclamation"></i>
                <div><?= e($error) ?></div>
            </div>
        <?php endif; ?>

        <!-- Demo Credentials Helper -->
        <div class="demo-account-box">
            <div class="demo-info">
                <div><i class="fa-solid fa-shield-halved" style="margin-right:4px;color:var(--primary);"></i> Admin: <strong>admin</strong> / <strong>admin123</strong></div>
            </div>
            <button type="button" class="btn btn-secondary btn-sm" onclick="fillDemo()" style="padding:0.25rem 0.6rem;font-size:0.75rem;">
                Auto Fill
            </button>
        </div>

        <form action="<?= BASE_URL ?>/login.php" method="POST">
            <div class="form-group">
                <label class="form-label" for="username">Username or Email</label>
                <div style="position:relative;">
                    <i class="fa-solid fa-user" style="position:absolute;left:0.85rem;top:50%;transform:translateY(-50%);color:var(--text-muted);"></i>
                    <input type="text" name="username" id="username" class="form-control" style="padding-left:2.4rem;" placeholder="e.g. admin" required autofocus>
                </div>
            </div>

            <div class="form-group">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:0.45rem;">
                    <label class="form-label" for="password" style="margin-bottom:0;">Password</label>
                </div>
                <div style="position:relative;">
                    <i class="fa-solid fa-lock" style="position:absolute;left:0.85rem;top:50%;transform:translateY(-50%);color:var(--text-muted);"></i>
                    <input type="password" name="password" id="password" class="form-control" style="padding-left:2.4rem;padding-right:2.4rem;" placeholder="••••••••" required>
                    <button type="button" onclick="togglePassword()" style="position:absolute;right:0.85rem;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--text-muted);cursor:pointer;">
                        <i class="fa-solid fa-eye" id="pwToggleIcon"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn btn-primary" style="width:100%;padding:0.75rem;margin-top:0.75rem;">
                <i class="fa-solid fa-arrow-right-to-bracket"></i> Sign In to ShopSphere Admin
            </button>
        </form>

        <div style="margin-top:1.5rem;text-align:center;font-size:0.75rem;color:var(--text-muted);">
            Connected to <strong>MySQL localhost:3306 (shop_db)</strong>
        </div>
    </div>

    <script>
        function fillDemo() {
            document.getElementById('username').value = 'admin';
            document.getElementById('password').value = 'admin123';
        }

        function togglePassword() {
            const pwInput = document.getElementById('password');
            const icon = document.getElementById('pwToggleIcon');
            if (pwInput.type === 'password') {
                pwInput.type = 'text';
                icon.className = 'fa-solid fa-eye-slash';
            } else {
                pwInput.type = 'password';
                icon.className = 'fa-solid fa-eye';
            }
        }
    </script>
</body>
</html>
