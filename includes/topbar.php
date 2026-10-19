<?php
/**
 * Global Topbar Header Component
 */
$adminUser = $_SESSION['admin_user'] ?? ['full_name' => 'Administrator', 'role' => 'Super Admin', 'email' => 'admin@shop.com'];
?>
<header class="topbar">
    <div class="topbar-left">
        <button type="button" class="sidebar-toggle-btn" id="sidebarToggleBtn" aria-label="Toggle Sidebar">
            <i class="fa-solid fa-bars-staggered"></i>
        </button>
        <div class="search-box">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" id="globalSearchInput" placeholder="Quick search..." onkeyup="if(event.key === 'Enter'){ window.location.href='<?= BASE_URL ?>/modules/products/index.php?search=' + encodeURIComponent(this.value); }">
        </div>
    </div>

    <div class="topbar-right">
        <!-- DB Status Badge -->
        <div class="badge badge-success" style="font-size:0.75rem;padding:0.35rem 0.75rem;cursor:default;" title="Connected to shop_db on localhost">
            <i class="fa-solid fa-database"></i> shop_db
        </div>

        <!-- Theme Toggle -->
        <button type="button" class="icon-btn" id="themeToggleBtn" title="Toggle Theme" aria-label="Toggle Theme">
            <i class="fa-solid fa-sun"></i>
        </button>

        <!-- Quick View Store Link -->
        <a href="<?= BASE_URL ?>/modules/products/index.php" class="icon-btn" title="Manage Inventory">
            <i class="fa-solid fa-cubes"></i>
        </a>

        <!-- Profile Dropdown -->
        <div class="profile-dropdown-container">
            <div class="profile-trigger" id="profileDropdownTrigger">
                <div class="admin-avatar" style="width:30px;height:30px;font-size:0.8rem;">
                    <?= strtoupper(substr($adminUser['full_name'] ?? 'A', 0, 1)) ?>
                </div>
                <span style="font-weight:600;font-size:0.85rem;"><?= e(explode(' ', $adminUser['full_name'] ?? 'Admin')[0]) ?></span>
                <i class="fa-solid fa-chevron-down" style="font-size:0.7rem;color:var(--text-muted);"></i>
            </div>

            <div class="dropdown-menu" id="profileDropdownMenu">
                <div class="dropdown-header">
                    <div style="font-weight:600;font-size:0.85rem;"><?= e($adminUser['full_name'] ?? 'Administrator') ?></div>
                    <div style="font-size:0.75rem;color:var(--text-muted);"><?= e($adminUser['email'] ?? 'admin@shop.com') ?></div>
                </div>
                <a href="<?= BASE_URL ?>/profile.php" class="dropdown-item">
                    <i class="fa-solid fa-user-pen"></i>
                    <span>Profile Settings</span>
                </a>
                <a href="<?= BASE_URL ?>/modules/orders/index.php" class="dropdown-item">
                    <i class="fa-solid fa-clipboard-list"></i>
                    <span>Recent Orders</span>
                </a>
                <div style="height:1px;background:var(--border-color);margin:0.25rem 0;"></div>
                <a href="<?= BASE_URL ?>/logout.php" class="dropdown-item text-danger">
                    <i class="fa-solid fa-arrow-right-from-bracket"></i>
                    <span>Sign Out</span>
                </a>
            </div>
        </div>
    </div>
</header>
<main class="content-body">
<?php
// Display flash message if set
$flash = get_flash();
if ($flash):
?>
    <div class="toast toast-<?= e($flash['type']) ?>" style="position:static;margin-bottom:1.5rem;animation:none;">
        <i class="fa-solid <?= $flash['type'] === 'success' ? 'fa-check-circle' : 'fa-circle-exclamation' ?>"></i>
        <div style="flex:1;"><?= e($flash['message']) ?></div>
    </div>
<?php endif; ?>
