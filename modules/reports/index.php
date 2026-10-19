<?php
/**
 * Reports & Sales Analytics Module
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth_check.php';

$pdo = Database::getConnection();

// Overall Revenue & AOV
$kpiStmt = $pdo->query("SELECT 
    COUNT(DISTINCT o.order_id) as total_orders,
    COALESCE(SUM(oi.quantity * oi.unit_price), 0) as gross_revenue,
    COALESCE(SUM(oi.quantity), 0) as total_items_sold,
    COALESCE(AVG(order_totals.total), 0) as avg_order_value
FROM orders o
LEFT JOIN (
    SELECT order_id, SUM(quantity * unit_price) as total 
    FROM order_items 
    GROUP BY order_id
) as order_totals ON o.order_id = order_totals.order_id
LEFT JOIN order_items oi ON o.order_id = oi.order_id");
$kpi = $kpiStmt->fetch();

// Monthly Breakdown
$monthlyStmt = $pdo->query("SELECT 
    DATE_FORMAT(o.order_date, '%Y-%m') as ym,
    DATE_FORMAT(o.order_date, '%M %Y') as month_title,
    COUNT(DISTINCT o.order_id) as order_count,
    COALESCE(SUM(oi.quantity * oi.unit_price), 0) as month_sales,
    COALESCE(SUM(oi.quantity), 0) as units_sold
FROM orders o
LEFT JOIN order_items oi ON o.order_id = oi.order_id
GROUP BY ym, month_title
ORDER BY ym DESC");
$monthlyReports = $monthlyStmt->fetchAll();

// Category Performance
$catPerfStmt = $pdo->query("SELECT 
    c.category_name,
    COUNT(DISTINCT p.product_id) as products_count,
    COALESCE(SUM(oi.quantity), 0) as units_sold,
    COALESCE(SUM(oi.quantity * oi.unit_price), 0) as total_revenue
FROM categories c
LEFT JOIN products p ON c.category_id = p.category_id
LEFT JOIN order_items oi ON p.product_id = oi.product_id
GROUP BY c.category_id
ORDER BY total_revenue DESC");
$categoryReports = $catPerfStmt->fetchAll();

$pageTitle = 'Sales & Financial Reports';
$activePage = 'reports';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
require_once __DIR__ . '/../../includes/topbar.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Sales & Financial Analytics</h1>
        <p class="page-subtitle">Historical performance and category metrics from <strong>shop_db</strong></p>
    </div>
    <button type="button" class="btn btn-secondary btn-sm" onclick="window.print()">
        <i class="fa-solid fa-print"></i> Print Statement
    </button>
</div>

<!-- KPI Summary Cards -->
<div class="metrics-grid">
    <div class="metric-card card-primary">
        <div class="metric-header">
            <span class="metric-title">Gross Revenue</span>
            <div class="metric-icon-wrap"><i class="fa-solid fa-sack-dollar"></i></div>
        </div>
        <div class="metric-value"><?= format_price($kpi['gross_revenue']) ?></div>
        <div class="metric-footer">Lifetime sales volume</div>
    </div>

    <div class="metric-card card-success">
        <div class="metric-header">
            <span class="metric-title">Avg Order Value (AOV)</span>
            <div class="metric-icon-wrap"><i class="fa-solid fa-scale-balanced"></i></div>
        </div>
        <div class="metric-value"><?= format_price($kpi['avg_order_value']) ?></div>
        <div class="metric-footer">Per completed transaction</div>
    </div>

    <div class="metric-card card-info">
        <div class="metric-header">
            <span class="metric-title">Units Sold</span>
            <div class="metric-icon-wrap"><i class="fa-solid fa-cart-flatbed"></i></div>
        </div>
        <div class="metric-value"><?= number_format($kpi['total_items_sold']) ?></div>
        <div class="metric-footer">Across all product lines</div>
    </div>

    <div class="metric-card card-warning">
        <div class="metric-header">
            <span class="metric-title">Total Orders</span>
            <div class="metric-icon-wrap"><i class="fa-solid fa-receipt"></i></div>
        </div>
        <div class="metric-value"><?= number_format($kpi['total_orders']) ?></div>
        <div class="metric-footer">Processed transactions</div>
    </div>
</div>

<!-- Monthly Performance Table -->
<div class="card" style="margin-bottom:2rem;">
    <div class="card-header">
        <div class="card-title">
            <i class="fa-solid fa-calendar-days" style="color:var(--primary);"></i>
            Monthly Revenue Performance Breakdown
        </div>
    </div>
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Month</th>
                    <th style="text-align:center;">Orders</th>
                    <th style="text-align:center;">Units Sold</th>
                    <th style="text-align:right;">Monthly Revenue</th>
                    <th style="text-align:right;">Avg Basket Size</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($monthlyReports)): ?>
                    <tr><td colspan="5" style="text-align:center;color:var(--text-muted);padding:2rem;">No monthly sales data recorded.</td></tr>
                <?php else: ?>
                    <?php foreach ($monthlyReports as $row): ?>
                        <?php $avgPerOrder = $row['order_count'] > 0 ? ($row['month_sales'] / $row['order_count']) : 0; ?>
                        <tr>
                            <td><strong><?= e($row['month_title']) ?></strong></td>
                            <td style="text-align:center;"><span class="badge badge-secondary"><?= $row['order_count'] ?></span></td>
                            <td style="text-align:center;"><?= number_format($row['units_sold']) ?></td>
                            <td style="text-align:right;font-weight:700;color:var(--text-primary);"><?= format_price($row['month_sales']) ?></td>
                            <td style="text-align:right;color:var(--text-secondary);"><?= format_price($avgPerOrder) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Category Performance Table -->
<div class="card">
    <div class="card-header">
        <div class="card-title">
            <i class="fa-solid fa-chart-pie" style="color:var(--success);"></i>
            Revenue Share by Department / Category
        </div>
    </div>
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Category</th>
                    <th style="text-align:center;">Catalog Products</th>
                    <th style="text-align:center;">Units Sold</th>
                    <th style="text-align:right;">Generated Revenue</th>
                    <th style="text-align:right;">Revenue Share %</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $totalGross = (float)$kpi['gross_revenue'] ?: 1;
                foreach ($categoryReports as $cat): 
                    $share = round(($cat['total_revenue'] / $totalGross) * 100, 1);
                ?>
                    <tr>
                        <td><strong><?= e($cat['category_name']) ?></strong></td>
                        <td style="text-align:center;"><?= $cat['products_count'] ?></td>
                        <td style="text-align:center;"><?= number_format($cat['units_sold']) ?></td>
                        <td style="text-align:right;font-weight:700;color:var(--primary);"><?= format_price($cat['total_revenue']) ?></td>
                        <td style="text-align:right;">
                            <span class="badge badge-primary" style="background:rgba(99,102,241,0.12);color:var(--primary);">
                                <?= $share ?>%
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
