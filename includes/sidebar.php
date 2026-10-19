<?php
/**
 * Global Sidebar Navigation Component
 */
$activePage = $activePage ?? 'dashboard';
$adminUser = $_SESSION['admin_user'] ?? ['full_name' => 'Administrator', 'role' => 'Super Admin', 'username' => 'admin'];
?>
<aside class="sidebar">
    <div class="sidebar-header">
        <div class="brand-icon" style="background:linear-gradient(135deg, #6366f1 0%, #a855f7 50%, #ec4899 100%);box-shadow:0 4px 14px rgba(168,85,247,0.35);">
            <i class="fa-solid fa-shapes"></i>
        </div>
        <div class="brand-text">
            ShopSphere <span class="brand-badge">Admin</span>
        </div>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section-title">Main Menu</div>
        <a href="<?= BASE_URL ?>/index.php" class="nav-link <?= $activePage === 'dashboard' ? 'active' : '' ?>">
            <i class="fa-solid fa-chart-pie"></i>
            <span>Dashboard</span>
        </a>

        <div class="nav-section-title">Catalog & Store</div>
        <a href="<?= BASE_URL ?>/modules/products/index.php" class="nav-link <?= $activePage === 'products' ? 'active' : '' ?>">
            <i class="fa-solid fa-boxes-stacked"></i>
            <span>Products</span>
        </a>

        <a href="<?= BASE_URL ?>/modules/categories/index.php" class="nav-link <?= $activePage === 'categories' ? 'active' : '' ?>">
            <i class="fa-solid fa-layer-group"></i>
            <span>Categories</span>
        </a>

        <a href="<?= BASE_URL ?>/modules/orders/index.php" class="nav-link <?= $activePage === 'orders' ? 'active' : '' ?>">
            <i class="fa-solid fa-bag-shopping"></i>
            <span>Orders</span>
        </a>

        <a href="<?= BASE_URL ?>/modules/customers/index.php" class="nav-link <?= $activePage === 'customers' ? 'active' : '' ?>">
            <i class="fa-solid fa-users"></i>
            <span>Customers</span>
        </a>

        <div class="nav-section-title">Analytics & Tools</div>
        <a href="<?= BASE_URL ?>/modules/reports/index.php" class="nav-link <?= $activePage === 'reports' ? 'active' : '' ?>">
            <i class="fa-solid fa-chart-column"></i>
            <span>Reports & Sales</span>
        </a>

        <a href="<?= BASE_URL ?>/profile.php" class="nav-link <?= $activePage === 'profile' ? 'active' : '' ?>">
            <i class="fa-solid fa-user-gear"></i>
            <span>Admin Settings</span>
        </a>
    </nav>

    <div class="sidebar-footer">
        <div class="admin-card">
            <div class="admin-avatar">
                <?= strtoupper(substr($adminUser['full_name'] ?? 'A', 0, 1)) ?>
            </div>
            <div class="admin-info">
                <div class="admin-name"><?= e($adminUser['full_name'] ?? 'Admin') ?></div>
                <div class="admin-role"><?= e($adminUser['role'] ?? 'Super Admin') ?></div>
            </div>
            <a href="<?= BASE_URL ?>/logout.php" title="Sign Out" class="icon-btn" style="width:30px;height:30px;font-size:0.8rem;border:none;">
                <i class="fa-solid fa-arrow-right-from-bracket"></i>
            </a>
        </div>
    </div>
</aside>
<div class="main-wrapper">
