<?php
/**
 * Orders Management Module
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth_check.php';

$pdo = Database::getConnection();

$statusFilter = $_GET['status'] ?? 'all';
$search = trim($_GET['search'] ?? '');

// Base query
$sql = "SELECT 
    o.order_id,
    o.order_date,
    o.status,
    c.customer_id,
    c.first_name,
    c.last_name,
    c.email,
    c.city,
    c.country,
    COUNT(oi.order_item_id) as items_count,
    COALESCE(SUM(oi.quantity * oi.unit_price), 0) as total_amount
FROM orders o
JOIN customers c ON o.customer_id = c.customer_id
LEFT JOIN order_items oi ON o.order_id = oi.order_id
WHERE 1=1";

$params = [];

if ($statusFilter !== 'all' && !empty($statusFilter)) {
    $sql .= " AND o.status = ?";
    $params[] = $statusFilter;
}

if (!empty($search)) {
    $sql .= " AND (c.first_name LIKE ? OR c.last_name LIKE ? OR c.email LIKE ? OR o.order_id = ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = (int)$search;
}

$sql .= " GROUP BY o.order_id ORDER BY o.order_date DESC, o.order_id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

// Counts per status for tabs
$countsStmt = $pdo->query("SELECT 
    COUNT(*) as all_count,
    SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending_count,
    SUM(CASE WHEN status = 'Shipped' THEN 1 ELSE 0 END) as shipped_count,
    SUM(CASE WHEN status = 'Delivered' THEN 1 ELSE 0 END) as delivered_count,
    SUM(CASE WHEN status = 'Cancelled' THEN 1 ELSE 0 END) as cancelled_count
FROM orders");
$counts = $countsStmt->fetch() ?: ['all_count' => 0, 'pending_count' => 0, 'shipped_count' => 0, 'delivered_count' => 0, 'cancelled_count' => 0];

$pageTitle = 'Orders Management';
$activePage = 'orders';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
require_once __DIR__ . '/../../includes/topbar.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Orders Management</h1>
        <p class="page-subtitle">Track, fulfill, update status and review line items for shop customers</p>
    </div>
</div>

<!-- Status Navigation Tabs -->
<div style="display:flex;gap:0.5rem;margin-bottom:1.5rem;flex-wrap:wrap;">
    <a href="?status=all" class="btn <?= $statusFilter === 'all' ? 'btn-primary' : 'btn-secondary' ?> btn-sm">
        All Orders (<?= $counts['all_count'] ?>)
    </a>
    <a href="?status=Pending" class="btn <?= $statusFilter === 'Pending' ? 'btn-primary' : 'btn-secondary' ?> btn-sm">
        Pending (<?= $counts['pending_count'] ?>)
    </a>
    <a href="?status=Shipped" class="btn <?= $statusFilter === 'Shipped' ? 'btn-primary' : 'btn-secondary' ?> btn-sm">
        Shipped (<?= $counts['shipped_count'] ?>)
    </a>
    <a href="?status=Delivered" class="btn <?= $statusFilter === 'Delivered' ? 'btn-primary' : 'btn-secondary' ?> btn-sm">
        Delivered (<?= $counts['delivered_count'] ?>)
    </a>
    <a href="?status=Cancelled" class="btn <?= $statusFilter === 'Cancelled' ? 'btn-primary' : 'btn-secondary' ?> btn-sm">
        Cancelled (<?= $counts['cancelled_count'] ?>)
    </a>
</div>

<!-- Search Bar -->
<div class="card" style="margin-bottom:1.5rem;">
    <div class="card-body" style="padding:1rem 1.5rem;">
        <form method="GET" action="" style="display:flex;gap:1rem;align-items:center;">
            <input type="hidden" name="status" value="<?= e($statusFilter) ?>">
            <div style="flex:1;">
                <input type="text" name="search" class="form-control" placeholder="Search by customer name, email, or order ID #..." value="<?= e($search) ?>">
            </div>
            <button type="submit" class="btn btn-secondary">
                <i class="fa-solid fa-magnifying-glass"></i> Search
            </button>
            <?php if (!empty($search)): ?>
                <a href="?status=<?= e($statusFilter) ?>" class="btn btn-secondary" title="Clear search">
                    <i class="fa-solid fa-xmark"></i>
                </a>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Orders Table -->
<div class="card">
    <div class="card-header">
        <div class="card-title">
            <i class="fa-solid fa-bag-shopping" style="color:var(--primary);"></i>
            Orders List (<?= count($orders) ?>)
        </div>
    </div>
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Order</th>
                    <th>Customer</th>
                    <th>Location</th>
                    <th>Date</th>
                    <th>Items</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th style="text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($orders)): ?>
                    <tr>
                        <td colspan="8" style="text-align:center;color:var(--text-muted);padding:3rem;">
                            No orders found matching the criteria.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($orders as $o): ?>
                        <?php
                        $badgeClass = 'badge-secondary';
                        if ($o['status'] === 'Delivered') $badgeClass = 'badge-success';
                        elseif ($o['status'] === 'Shipped') $badgeClass = 'badge-info';
                        elseif ($o['status'] === 'Pending') $badgeClass = 'badge-warning';
                        elseif ($o['status'] === 'Cancelled') $badgeClass = 'badge-danger';
                        ?>
                        <tr>
                            <td style="font-weight:700;">
                                <a href="view.php?id=<?= $o['order_id'] ?>" style="color:var(--primary);">
                                    #ORD-<?= str_pad($o['order_id'], 4, '0', STR_PAD_LEFT) ?>
                                </a>
                            </td>
                            <td>
                                <div style="font-weight:600;"><?= e($o['first_name'] . ' ' . $o['last_name']) ?></div>
                                <div style="font-size:0.75rem;color:var(--text-muted);"><?= e($o['email']) ?></div>
                            </td>
                            <td style="font-size:0.85rem;color:var(--text-secondary);">
                                <?= e($o['city'] ?: '-') ?>, <?= e($o['country']) ?>
                            </td>
                            <td><?= format_date($o['order_date']) ?></td>
                            <td>
                                <span class="badge badge-secondary"><?= $o['items_count'] ?> items</span>
                            </td>
                            <td style="font-weight:700;font-size:0.95rem;"><?= format_price($o['total_amount']) ?></td>
                            <td>
                                <select class="form-control" style="padding:0.25rem 0.5rem;font-size:0.8rem;width:125px;border-radius:6px;" onchange="updateOrderStatus(<?= $o['order_id'] ?>, this.value)">
                                    <option value="Pending" <?= $o['status'] === 'Pending' ? 'selected' : '' ?>>Pending</option>
                                    <option value="Shipped" <?= $o['status'] === 'Shipped' ? 'selected' : '' ?>>Shipped</option>
                                    <option value="Delivered" <?= $o['status'] === 'Delivered' ? 'selected' : '' ?>>Delivered</option>
                                    <option value="Cancelled" <?= $o['status'] === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                </select>
                            </td>
                            <td style="text-align:right;">
                                <a href="view.php?id=<?= $o['order_id'] ?>" class="btn btn-secondary btn-icon" title="View Full Order Invoice">
                                    <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
async function updateOrderStatus(orderId, newStatus) {
    const formData = new FormData();
    formData.append('action', 'update_status');
    formData.append('order_id', orderId);
    formData.append('status', newStatus);

    try {
        const res = await fetch(`<?= BASE_URL ?>/api/orders.php`, {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        if (data.success) {
            showToast(data.message, 'success');
        } else {
            showToast(data.message || 'Status update failed', 'danger');
        }
    } catch (err) {
        showToast('Network error updating status', 'danger');
    }
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
