<?php
/**
 * ShopAdmin System Diagnostic & Setup Utility
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

$message = '';
$status = 'info';

if (isset($_POST['run_setup'])) {
    try {
        $pdo = Database::getConnection();
        $sql = file_get_contents(__DIR__ . '/database.sql');
        $pdo->exec($sql);
        $message = "Database 'shop_db' schema and initial data verified successfully!";
        $status = "success";
    } catch (Exception $e) {
        $message = "Setup Error: " . $e->getMessage();
        $status = "danger";
    }
}

// Check current status
$tables = [];
$dbConnected = false;
$errorMsg = '';

try {
    $pdo = Database::getConnection();
    $dbConnected = true;
    $tblStmt = $pdo->query("SHOW TABLES");
    $tables = $tblStmt->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    $errorMsg = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Setup & Diagnostics | <?= APP_NAME ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/dashboard.css">
</head>
<body style="padding:2rem 1rem;max-width:800px;margin:0 auto;background:var(--bg-primary);">
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <i class="fa-solid fa-screwdriver-wrench" style="color:var(--primary);"></i>
                ShopSphere Admin Setup & Diagnostics
            </div>
            <a href="<?= BASE_URL ?>/index.php" class="btn btn-secondary btn-sm">Go to Dashboard</a>
        </div>
        <div class="card-body">
            <?php if (!empty($message)): ?>
                <div class="toast toast-<?= $status ?>" style="position:static;margin-bottom:1.5rem;animation:none;">
                    <div><?= e($message) ?></div>
                </div>
            <?php endif; ?>

            <div style="margin-bottom:1.5rem;">
                <h3 style="font-size:1.1rem;margin-bottom:0.5rem;">Database Connectivity</h3>
                <?php if ($dbConnected): ?>
                    <div class="badge badge-success" style="font-size:0.85rem;padding:0.4rem 0.8rem;">
                        <i class="fa-solid fa-circle-check"></i> Connected to MySQL (shop_db)
                    </div>
                <?php else: ?>
                    <div class="badge badge-danger" style="font-size:0.85rem;padding:0.4rem 0.8rem;">
                        <i class="fa-solid fa-triangle-exclamation"></i> Connection Failed: <?= e($errorMsg) ?>
                    </div>
                <?php endif; ?>
            </div>

            <div style="margin-bottom:1.5rem;">
                <h3 style="font-size:1.1rem;margin-bottom:0.5rem;">Detected Tables</h3>
                <?php if (!empty($tables)): ?>
                    <div style="display:flex;gap:0.5rem;flex-wrap:wrap;">
                        <?php foreach ($tables as $t): ?>
                            <span class="badge badge-secondary" style="font-size:0.8rem;padding:0.35rem 0.65rem;">
                                <i class="fa-solid fa-table"></i> <?= e($t) ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p style="color:var(--text-muted);">No tables detected.</p>
                <?php endif; ?>
            </div>

            <div style="border-top:1px solid var(--border-color);padding-top:1.5rem;">
                <h3 style="font-size:1.1rem;margin-bottom:0.5rem;">Default Admin Credentials</h3>
                <div style="background:var(--bg-input);padding:1rem;border-radius:var(--radius-sm);border:1px solid var(--border-color);margin-bottom:1.5rem;">
                    <div>Username: <strong>admin</strong></div>
                    <div>Password: <strong>admin123</strong></div>
                    <div>Email: <strong>admin@shop.com</strong></div>
                </div>

                <form method="POST" action="">
                    <button type="submit" name="run_setup" class="btn btn-primary">
                        <i class="fa-solid fa-rotate"></i> Re-verify Schema & Seed Data
                    </button>
                    <a href="<?= BASE_URL ?>/login.php" class="btn btn-secondary">
                        <i class="fa-solid fa-arrow-right-to-bracket"></i> Login Page
                    </a>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
