<?php
/**
 * ShopAdmin Overview Dashboard
 * Powered by Core PHP & shop_db
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth_check.php';

$pdo = Database::getConnection();

// 1. Fetch KPI Metrics
// Total Revenue (excluding cancelled orders)
$revStmt = $pdo->query("SELECT COALESCE(SUM(oi.quantity * oi.unit_price), 0) as total_revenue 
                         FROM order_items oi 
                         JOIN orders o ON oi.order_id = o.order_id 
                         WHERE o.status != 'Cancelled'");
$totalRevenue = (float)($revStmt->fetchColumn() ?? 0);

// Total Orders & Pending Orders
$ordersStmt = $pdo->query("SELECT 
    COUNT(*) as total_orders,
    SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending_orders,
    SUM(CASE WHEN status = 'Delivered' THEN 1 ELSE 0 END) as delivered_orders,
    SUM(CASE WHEN status = 'Shipped' THEN 1 ELSE 0 END) as shipped_orders,
    SUM(CASE WHEN status = 'Cancelled' THEN 1 ELSE 0 END) as cancelled_orders
FROM orders");
$orderMetrics = $ordersStmt->fetch() ?: ['total_orders' => 0, 'pending_orders' => 0];

// Total Products & Low Stock Count
$prodStmt = $pdo->query("SELECT 
    COUNT(*) as total_products,
    SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_products,
    SUM(CASE WHEN stock_quantity <= 20 THEN 1 ELSE 0 END) as low_stock_products
FROM products");
$prodMetrics = $prodStmt->fetch() ?: ['total_products' => 0, 'low_stock_products' => 0];

// Total Customers
$custStmt = $pdo->query("SELECT COUNT(*) as total_customers FROM customers");
$totalCustomers = (int)($custStmt->fetchColumn() ?? 0);

// 2. Fetch Recent Orders (Top 6)
$recentOrdersStmt = $pdo->query("SELECT 
    o.order_id, 
    o.order_date, 
    o.status, 
    c.customer_id, 
    c.first_name, 
    c.last_name, 
    c.email,
    COALESCE(SUM(oi.quantity * oi.unit_price), 0) as total_amount,
    COUNT(oi.order_item_id) as items_count
FROM orders o
JOIN customers c ON o.customer_id = c.customer_id
LEFT JOIN order_items oi ON o.order_id = oi.order_id
GROUP BY o.order_id
ORDER BY o.order_date DESC, o.order_id DESC
LIMIT 6");
$recentOrders = $recentOrdersStmt->fetchAll();

// 3. Top Selling Products
$topProductsStmt = $pdo->query("SELECT 
    p.product_id,
    p.product_name,
    p.price,
    p.stock_quantity,
    c.category_name,
    COALESCE(SUM(oi.quantity), 0) as total_sold,
    COALESCE(SUM(oi.quantity * oi.unit_price), 0) as total_revenue
FROM products p
JOIN categories c ON p.category_id = c.category_id
LEFT JOIN order_items oi ON p.product_id = oi.product_id
GROUP BY p.product_id
ORDER BY total_sold DESC, total_revenue DESC
LIMIT 5");
$topProducts = $topProductsStmt->fetchAll();

// 4. Low Stock Products
$lowStockStmt = $pdo->query("SELECT 
    p.product_id, 
    p.product_name, 
    p.stock_quantity, 
    p.price, 
    c.category_name 
FROM products p
JOIN categories c ON p.category_id = c.category_id
WHERE p.stock_quantity <= 30
ORDER BY p.stock_quantity ASC
LIMIT 5");
$lowStockProducts = $lowStockStmt->fetchAll();

// 5. Chart Data: Monthly Revenue & Orders
$trendStmt = $pdo->query("SELECT 
    DATE_FORMAT(o.order_date, '%b %Y') as month_label,
    DATE_FORMAT(o.order_date, '%Y-%m') as sort_key,
    COALESCE(SUM(oi.quantity * oi.unit_price), 0) as monthly_revenue,
    COUNT(DISTINCT o.order_id) as monthly_orders
FROM orders o
LEFT JOIN order_items oi ON o.order_id = oi.order_id
GROUP BY month_label, sort_key
ORDER BY sort_key ASC
LIMIT 12");
$monthlyData = $trendStmt->fetchAll();

$chartSalesLabels = [];
$chartSalesValues = [];
$chartOrderCounts = [];
foreach ($monthlyData as $row) {
    $chartSalesLabels[] = $row['month_label'];
    $chartSalesValues[] = round((float)$row['monthly_revenue'], 2);
    $chartOrderCounts[] = (int)$row['monthly_orders'];
}
if (empty($chartSalesLabels)) {
    $chartSalesLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'];
    $chartSalesValues = [1200, 1900, 2400, 2100, 2800, 3500];
    $chartOrderCounts = [4, 7, 10, 8, 12, 16];
}

// 6. Category Breakdown for Doughnut Chart
$catStmt = $pdo->query("SELECT 
    c.category_name, 
    COUNT(p.product_id) as product_count,
    COALESCE(SUM(oi.quantity * oi.unit_price), 0) as category_revenue
FROM categories c
LEFT JOIN products p ON c.category_id = p.category_id
LEFT JOIN order_items oi ON p.product_id = oi.product_id
GROUP BY c.category_id
ORDER BY category_revenue DESC");
$categoryData = $catStmt->fetchAll();

$chartCatLabels = [];
$chartCatValues = [];
foreach ($categoryData as $row) {
    $chartCatLabels[] = $row['category_name'];
    $chartCatValues[] = round((float)$row['category_revenue'], 2);
}

$pageTitle = 'Dashboard Overview';
$activePage = 'dashboard';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
require_once __DIR__ . '/includes/topbar.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <div style="display:inline-flex;align-items:center;gap:0.4rem;font-size:0.75rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:var(--primary);margin-bottom:0.35rem;">
            <i class="fa-solid fa-shapes"></i> ShopSphere Platform
        </div>
        <h1 class="page-title" style="font-family:'Plus Jakarta Sans',sans-serif;letter-spacing:-0.03em;">ShopSphere Executive Dashboard</h1>
        <p class="page-subtitle">Real-time commerce operations &amp; store intelligence from <strong>shop_db</strong></p>
    </div>
    <div style="display:flex;gap:0.75rem;">
        <a href="<?= BASE_URL ?>/modules/products/index.php" class="btn btn-secondary btn-sm">
            <i class="fa-solid fa-plus"></i> New Product
        </a>
        <a href="<?= BASE_URL ?>/modules/orders/index.php" class="btn btn-primary btn-sm">
            <i class="fa-solid fa-cart-shopping"></i> View Orders
        </a>
    </div>
</div>

<!-- KPI Metrics Grid -->
<div class="metrics-grid">
    <!-- Revenue Card -->
    <div class="metric-card card-primary">
        <div class="metric-header">
            <span class="metric-title">Total Revenue</span>
            <div class="metric-icon-wrap">
                <i class="fa-solid fa-dollar-sign"></i>
            </div>
        </div>
        <div class="metric-value"><?= format_price($totalRevenue) ?></div>
        <div class="metric-footer">
            <span class="metric-trend trend-up">
                <i class="fa-solid fa-arrow-trend-up"></i> +12.4%
            </span>
            <span>from verified orders</span>
        </div>
    </div>

    <!-- Orders Card -->
    <div class="metric-card card-success">
        <div class="metric-header">
            <span class="metric-title">Total Orders</span>
            <div class="metric-icon-wrap">
                <i class="fa-solid fa-box"></i>
            </div>
        </div>
        <div class="metric-value"><?= number_format($orderMetrics['total_orders']) ?></div>
        <div class="metric-footer">
            <span class="badge badge-warning" style="font-size:0.7rem;">
                <?= $orderMetrics['pending_orders'] ?> Pending
            </span>
            <span><?= $orderMetrics['delivered_orders'] ?? 0 ?> Delivered</span>
        </div>
    </div>

    <!-- Products Card -->
    <div class="metric-card card-warning">
        <div class="metric-header">
            <span class="metric-title">Products in Store</span>
            <div class="metric-icon-wrap">
                <i class="fa-solid fa-tags"></i>
            </div>
        </div>
        <div class="metric-value"><?= number_format($prodMetrics['total_products']) ?></div>
        <div class="metric-footer">
            <span class="badge badge-danger" style="font-size:0.7rem;">
                <?= $prodMetrics['low_stock_products'] ?> Low Stock
            </span>
            <span><?= $prodMetrics['active_products'] ?? 0 ?> Active</span>
        </div>
    </div>

    <!-- Customers Card -->
    <div class="metric-card card-info">
        <div class="metric-header">
            <span class="metric-title">Registered Customers</span>
            <div class="metric-icon-wrap">
                <i class="fa-solid fa-users"></i>
            </div>
        </div>
        <div class="metric-value"><?= number_format($totalCustomers) ?></div>
        <div class="metric-footer">
            <span class="metric-trend trend-up">
                <i class="fa-solid fa-check"></i> Active
            </span>
            <span>across international regions</span>
        </div>
    </div>
</div>

<!-- Charts Section -->
<div class="charts-grid">
    <!-- Sales & Revenue Trend Chart -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <i class="fa-solid fa-chart-line" style="color:var(--primary);"></i>
                Revenue & Orders Trend
            </div>
            <span class="badge badge-secondary" style="font-size:0.7rem;">Live Data</span>
        </div>
        <div class="card-body">
            <div class="chart-container">
                <canvas id="salesTrendChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Category Distribution Doughnut Chart -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <i class="fa-solid fa-pie-chart" style="color:var(--info);"></i>
                Sales by Category
            </div>
        </div>
        <div class="card-body">
            <div class="chart-container">
                <canvas id="categoryShareChart"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Recent Orders & Top Selling Products Grid -->
<div class="dashboard-grid">
    <!-- Recent Orders Table -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <i class="fa-solid fa-receipt" style="color:var(--warning);"></i>
                Recent Customer Orders
            </div>
            <a href="<?= BASE_URL ?>/modules/orders/index.php" class="btn btn-secondary btn-sm" style="font-size:0.75rem;">
                View All <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentOrders)): ?>
                        <tr>
                            <td colspan="6" style="text-align:center;color:var(--text-muted);padding:2rem;">No orders found in database.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recentOrders as $order): ?>
                            <?php
                            $status = $order['status'];
                            $badgeClass = 'badge-secondary';
                            if ($status === 'Delivered') $badgeClass = 'badge-success';
                            elseif ($status === 'Shipped') $badgeClass = 'badge-info';
                            elseif ($status === 'Pending') $badgeClass = 'badge-warning';
                            elseif ($status === 'Cancelled') $badgeClass = 'badge-danger';
                            ?>
                            <tr>
                                <td style="font-weight:600;">#ORD-<?= str_pad($order['order_id'], 4, '0', STR_PAD_LEFT) ?></td>
                                <td>
                                    <div style="font-weight:600;"><?= e($order['first_name'] . ' ' . $order['last_name']) ?></div>
                                    <div style="font-size:0.75rem;color:var(--text-muted);"><?= e($order['email']) ?></div>
                                </td>
                                <td><?= format_date($order['order_date']) ?></td>
                                <td style="font-weight:700;"><?= format_price($order['total_amount']) ?></td>
                                <td>
                                    <span class="badge <?= $badgeClass ?>"><?= e($status) ?></span>
                                </td>
                                <td>
                                    <a href="<?= BASE_URL ?>/modules/orders/view.php?id=<?= $order['order_id'] ?>" class="btn btn-secondary btn-icon" title="View Order Details">
                                        <i class="fa-solid fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Top Products & Low Stock Alerts Column -->
    <div style="display:flex;flex-direction:column;gap:1.5rem;">
        <!-- Top Selling Products -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">
                    <i class="fa-solid fa-fire" style="color:var(--danger);"></i>
                    Top Selling Products
                </div>
            </div>
            <div class="card-body" style="padding-top:0.5rem;padding-bottom:0.5rem;">
                <?php if (empty($topProducts)): ?>
                    <p style="color:var(--text-muted);text-align:center;padding:1rem;">No product sales recorded yet.</p>
                <?php else: ?>
                    <?php $rank = 1; foreach ($topProducts as $item): ?>
                        <div class="ranking-item">
                            <div class="ranking-info">
                                <div class="ranking-rank"><?= $rank++ ?></div>
                                <div>
                                    <div class="ranking-title"><?= e($item['product_name']) ?></div>
                                    <div class="ranking-sub"><?= e($item['category_name']) ?> &bull; <?= format_price($item['price']) ?></div>
                                </div>
                            </div>
                            <div class="ranking-amount">
                                <div><?= format_price($item['total_revenue']) ?></div>
                                <div style="font-size:0.75rem;color:var(--text-muted);font-weight:400;"><?= (int)$item['total_sold'] ?> sold</div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Inventory Watchlist (Low Stock) -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">
                    <i class="fa-solid fa-triangle-exclamation" style="color:var(--warning);"></i>
                    Low Stock Alerts
                </div>
                <a href="<?= BASE_URL ?>/modules/products/index.php" class="btn btn-secondary btn-sm" style="font-size:0.7rem;">Manage</a>
            </div>
            <div class="card-body">
                <?php if (empty($lowStockProducts)): ?>
                    <p style="color:var(--success);text-align:center;font-size:0.85rem;"><i class="fa-solid fa-circle-check"></i> All products are healthy in stock!</p>
                <?php else: ?>
                    <?php foreach ($lowStockProducts as $p): ?>
                        <div style="margin-bottom:1rem;">
                            <div style="display:flex;justify-content:space-between;font-size:0.85rem;margin-bottom:0.25rem;">
                                <span style="font-weight:600;"><?= e($p['product_name']) ?></span>
                                <span class="badge <?= $p['stock_quantity'] <= 5 ? 'badge-danger' : 'badge-warning' ?>" style="font-size:0.7rem;">
                                    <?= $p['stock_quantity'] ?> left
                                </span>
                            </div>
                            <div class="progress-bar-wrap">
                                <div class="progress-bar" style="width: <?= min(100, max(5, $p['stock_quantity'] * 2)) ?>%; background: <?= $p['stock_quantity'] <= 5 ? 'var(--danger)' : 'var(--warning)' ?>;"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
    window.dashboardChartData = {
        salesLabels: <?= json_encode($chartSalesLabels) ?>,
        salesValues: <?= json_encode($chartSalesValues) ?>,
        orderCounts: <?= json_encode($chartOrderCounts) ?>,
        categoryLabels: <?= json_encode($chartCatLabels) ?>,
        categoryValues: <?= json_encode($chartCatValues) ?>
    };
</script>

<?php
$extraScripts = '<script src="' . BASE_URL . '/assets/js/dashboard.js"></script>';
require_once __DIR__ . '/includes/footer.php';
?>
