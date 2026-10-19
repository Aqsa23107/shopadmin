<?php
/**
 * Detailed Order Invoice View
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth_check.php';

$pdo = Database::getConnection();
$orderId = (int)($_GET['id'] ?? 0);

if ($orderId <= 0) {
    set_flash('danger', 'Invalid order ID specified.');
    redirect('modules/orders/index.php');
}

// Fetch order & customer info
$stmt = $pdo->prepare("SELECT 
    o.*, 
    c.first_name, 
    c.last_name, 
    c.email, 
    c.phone, 
    c.city, 
    c.country, 
    c.created_at as customer_since
FROM orders o
JOIN customers c ON o.customer_id = c.customer_id
WHERE o.order_id = ?");
$stmt->execute([$orderId]);
$order = $stmt->fetch();

if (!$order) {
    set_flash('danger', 'Order not found in database.');
    redirect('modules/orders/index.php');
}

// Fetch order items with products
$itemsStmt = $pdo->prepare("SELECT 
    oi.*, 
    p.product_name, 
    p.category_id, 
    c.category_name 
FROM order_items oi
JOIN products p ON oi.product_id = p.product_id
LEFT JOIN categories c ON p.category_id = c.category_id
WHERE oi.order_id = ?");
$itemsStmt->execute([$orderId]);
$items = $itemsStmt->fetchAll();

$subtotal = 0;
foreach ($items as $item) {
    $subtotal += ($item['quantity'] * $item['unit_price']);
}

$pageTitle = 'Order #ORD-' . str_pad($order['order_id'], 4, '0', STR_PAD_LEFT);
$activePage = 'orders';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
require_once __DIR__ . '/../../includes/topbar.php';
?>

<div class="page-header">
    <div>
        <a href="index.php" style="color:var(--text-muted);font-size:0.85rem;display:inline-flex;align-items:center;gap:0.35rem;margin-bottom:0.5rem;">
            <i class="fa-solid fa-arrow-left"></i> Back to Orders
        </a>
        <h1 class="page-title">Order #ORD-<?= str_pad($order['order_id'], 4, '0', STR_PAD_LEFT) ?></h1>
        <p class="page-subtitle">Placed on <?= format_date($order['order_date'], 'F d, Y') ?></p>
    </div>
    <div style="display:flex;gap:0.75rem;align-items:center;">
        <button type="button" class="btn btn-secondary btn-sm" onclick="window.print()">
            <i class="fa-solid fa-print"></i> Print Invoice
        </button>
    </div>
</div>

<!-- Order Summary Cards Grid -->
<div class="invoice-card">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:1.5rem;">
        <div>
            <div style="font-size:0.75rem;text-transform:uppercase;color:var(--text-muted);font-weight:600;letter-spacing:0.05em;">Customer Information</div>
            <h2 style="font-size:1.35rem;margin:0.25rem 0;"><?= e($order['first_name'] . ' ' . $order['last_name']) ?></h2>
            <div style="color:var(--text-secondary);font-size:0.9rem;">
                <div><i class="fa-solid fa-envelope" style="width:20px;color:var(--text-muted);"></i> <?= e($order['email']) ?></div>
                <div><i class="fa-solid fa-phone" style="width:20px;color:var(--text-muted);"></i> <?= e($order['phone'] ?: 'No phone provided') ?></div>
                <div><i class="fa-solid fa-location-dot" style="width:20px;color:var(--text-muted);"></i> <?= e($order['city'] ?: '-') ?>, <?= e($order['country']) ?></div>
            </div>
        </div>

        <div style="background:var(--bg-input);border:1px solid var(--border-color);border-radius:var(--radius-sm);padding:1.25rem;min-width:260px;">
            <div style="font-size:0.75rem;text-transform:uppercase;color:var(--text-muted);font-weight:600;margin-bottom:0.5rem;">Fulfillment Status</div>
            <div style="display:flex;align-items:center;gap:0.75rem;margin-bottom:0.75rem;">
                <select id="detailOrderStatus" class="form-control" onchange="changeDetailStatus(this.value)">
                    <option value="Pending" <?= $order['status'] === 'Pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="Shipped" <?= $order['status'] === 'Shipped' ? 'selected' : '' ?>>Shipped</option>
                    <option value="Delivered" <?= $order['status'] === 'Delivered' ? 'selected' : '' ?>>Delivered</option>
                    <option value="Cancelled" <?= $order['status'] === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                </select>
            </div>
            <div style="font-size:0.75rem;color:var(--text-muted);">
                Customer ID: <strong>#CUST-<?= $order['customer_id'] ?></strong>
            </div>
        </div>
    </div>
</div>

<!-- Purchased Line Items -->
<div class="card" style="margin-bottom:2rem;">
    <div class="card-header">
        <div class="card-title">
            <i class="fa-solid fa-list-check" style="color:var(--primary);"></i>
            Purchased Line Items
        </div>
    </div>
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width:60px;">Item</th>
                    <th>Product</th>
                    <th>Category</th>
                    <th style="text-align:right;">Unit Price</th>
                    <th style="text-align:center;">Qty</th>
                    <th style="text-align:right;">Line Total</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($items)): ?>
                    <tr>
                        <td colspan="6" style="text-align:center;color:var(--text-muted);padding:2rem;">No items recorded for this order.</td>
                    </tr>
                <?php else: ?>
                    <?php $itemNum = 1; foreach ($items as $item): ?>
                        <?php $lineTotal = $item['quantity'] * $item['unit_price']; ?>
                        <tr>
                            <td style="color:var(--text-muted);font-weight:600;">#<?= $itemNum++ ?></td>
                            <td>
                                <strong style="color:var(--text-primary);"><?= e($item['product_name']) ?></strong>
                            </td>
                            <td>
                                <span class="badge badge-secondary"><?= e($item['category_name'] ?: 'General') ?></span>
                            </td>
                            <td style="text-align:right;"><?= format_price($item['unit_price']) ?></td>
                            <td style="text-align:center;font-weight:600;"><?= $item['quantity'] ?></td>
                            <td style="text-align:right;font-weight:700;color:var(--text-primary);"><?= format_price($lineTotal) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Totals Summary Section -->
    <div style="padding:1.5rem;border-top:1px solid var(--border-color);display:flex;justify-content:flex-end;">
        <div style="width:100%;max-width:320px;">
            <div style="display:flex;justify-content:space-between;padding:0.35rem 0;color:var(--text-secondary);">
                <span>Subtotal:</span>
                <span><?= format_price($subtotal) ?></span>
            </div>
            <div style="display:flex;justify-content:space-between;padding:0.35rem 0;color:var(--text-secondary);">
                <span>Shipping & Handling:</span>
                <span style="color:var(--success);">Free Shipping</span>
            </div>
            <div style="height:1px;background:var(--border-color);margin:0.5rem 0;"></div>
            <div style="display:flex;justify-content:space-between;padding:0.5rem 0;font-size:1.25rem;font-weight:800;color:var(--text-primary);">
                <span>Grand Total:</span>
                <span style="color:var(--primary);"><?= format_price($subtotal) ?></span>
            </div>
        </div>
    </div>
</div>

<script>
async function changeDetailStatus(newStatus) {
    const formData = new FormData();
    formData.append('action', 'update_status');
    formData.append('order_id', <?= $order['order_id'] ?>);
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
