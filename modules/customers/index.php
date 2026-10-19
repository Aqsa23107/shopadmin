<?php
/**
 * Customer Directory Module
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth_check.php';

$pdo = Database::getConnection();

$search = trim($_GET['search'] ?? '');

$sql = "SELECT 
    c.*, 
    COUNT(DISTINCT o.order_id) as total_orders,
    COALESCE(SUM(oi.quantity * oi.unit_price), 0) as total_spent
FROM customers c
LEFT JOIN orders o ON c.customer_id = o.customer_id
LEFT JOIN order_items oi ON o.order_id = oi.order_id
WHERE 1=1";

$params = [];
if (!empty($search)) {
    $sql .= " AND (c.first_name LIKE ? OR c.last_name LIKE ? OR c.email LIKE ? OR c.city LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql .= " GROUP BY c.customer_id ORDER BY total_spent DESC, c.customer_id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$customers = $stmt->fetchAll();

$pageTitle = 'Customer Directory';
$activePage = 'customers';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
require_once __DIR__ . '/../../includes/topbar.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Customer Directory</h1>
        <p class="page-subtitle">View profiles, contact information, lifetime spending, and purchase history</p>
    </div>
    <button type="button" class="btn btn-primary btn-sm" onclick="openAddCustomerModal()">
        <i class="fa-solid fa-user-plus"></i> Add Customer
    </button>
</div>

<!-- Search Card -->
<div class="card" style="margin-bottom:1.5rem;">
    <div class="card-body" style="padding:1rem 1.5rem;">
        <form method="GET" action="" style="display:flex;gap:1rem;align-items:center;">
            <div style="flex:1;">
                <input type="text" name="search" class="form-control" placeholder="Search by customer name, email, or city..." value="<?= e($search) ?>">
            </div>
            <button type="submit" class="btn btn-secondary">
                <i class="fa-solid fa-magnifying-glass"></i> Search
            </button>
            <?php if (!empty($search)): ?>
                <a href="index.php" class="btn btn-secondary" title="Clear Search">
                    <i class="fa-solid fa-rotate-left"></i>
                </a>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Customers Table -->
<div class="card">
    <div class="card-header">
        <div class="card-title">
            <i class="fa-solid fa-users" style="color:var(--info);"></i>
            Registered Customers (<?= count($customers) ?>)
        </div>
    </div>
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width:70px;">ID</th>
                    <th>Customer Name</th>
                    <th>Contact Info</th>
                    <th>Location</th>
                    <th>Joined</th>
                    <th style="text-align:center;">Orders</th>
                    <th style="text-align:right;">Lifetime Spent</th>
                    <th style="text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($customers)): ?>
                    <tr>
                        <td colspan="8" style="text-align:center;color:var(--text-muted);padding:3rem;">
                            No customers found.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($customers as $c): ?>
                        <tr>
                            <td style="color:var(--text-muted);font-weight:600;">#<?= $c['customer_id'] ?></td>
                            <td>
                                <div style="display:flex;align-items:center;gap:0.75rem;">
                                    <div class="admin-avatar" style="width:32px;height:32px;font-size:0.8rem;background:linear-gradient(135deg, #10b981, #06b6d4);">
                                        <?= strtoupper(substr($c['first_name'], 0, 1)) ?>
                                    </div>
                                    <strong style="color:var(--text-primary);"><?= e($c['first_name'] . ' ' . $c['last_name']) ?></strong>
                                </div>
                            </td>
                            <td>
                                <div><i class="fa-solid fa-envelope" style="font-size:0.75rem;color:var(--text-muted);margin-right:4px;"></i> <?= e($c['email']) ?></div>
                                <div style="font-size:0.8rem;color:var(--text-muted);"><i class="fa-solid fa-phone" style="font-size:0.75rem;margin-right:4px;"></i> <?= e($c['phone'] ?: '-') ?></div>
                            </td>
                            <td>
                                <div><?= e($c['city'] ?: '-') ?></div>
                                <div style="font-size:0.75rem;color:var(--text-muted);"><?= e($c['country']) ?></div>
                            </td>
                            <td><?= format_date($c['created_at']) ?></td>
                            <td style="text-align:center;">
                                <a href="<?= BASE_URL ?>/modules/orders/index.php?search=<?= urlencode($c['email']) ?>" class="badge badge-info" title="View customer orders">
                                    <?= $c['total_orders'] ?> orders
                                </a>
                            </td>
                            <td style="text-align:right;font-weight:700;color:var(--text-primary);">
                                <?= format_price($c['total_spent']) ?>
                            </td>
                            <td style="text-align:right;">
                                <button type="button" class="btn btn-secondary btn-icon" onclick="openEditCustomerModal(<?= $c['customer_id'] ?>)" title="Edit Customer">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </button>
                                <a href="<?= BASE_URL ?>/modules/orders/index.php?search=<?= urlencode($c['email']) ?>" class="btn btn-secondary btn-icon" title="View Customer Orders">
                                    <i class="fa-solid fa-cart-shopping"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Customer Add / Edit Modal -->
<div class="modal-overlay" id="customerModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title" id="custModalTitle" style="font-size:1.1rem;font-weight:700;">Add Customer</h3>
            <button type="button" data-modal-close style="background:none;border:none;color:var(--text-muted);cursor:pointer;font-size:1.2rem;">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form id="customerForm" onsubmit="saveCustomer(event)">
            <div class="modal-body">
                <input type="hidden" name="customer_id" id="cust_id" value="0">

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                    <div class="form-group">
                        <label class="form-label" for="cust_fn">First Name *</label>
                        <input type="text" name="first_name" id="cust_fn" class="form-control" required placeholder="Aisha">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="cust_ln">Last Name *</label>
                        <input type="text" name="last_name" id="cust_ln" class="form-control" required placeholder="Rahman">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="cust_email">Email Address *</label>
                    <input type="email" name="email" id="cust_email" class="form-control" required placeholder="aisha@example.com">
                </div>

                <div class="form-group">
                    <label class="form-label" for="cust_phone">Phone Number</label>
                    <input type="text" name="phone" id="cust_phone" class="form-control" placeholder="+6012-3456789">
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                    <div class="form-group">
                        <label class="form-label" for="cust_city">City</label>
                        <input type="text" name="city" id="cust_city" class="form-control" placeholder="Kuala Lumpur">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="cust_country">Country</label>
                        <input type="text" name="country" id="cust_country" class="form-control" value="Malaysia" placeholder="Malaysia">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-primary" id="saveCustBtn">
                    <i class="fa-solid fa-floppy-disk"></i> Save Customer
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddCustomerModal() {
    document.getElementById('customerForm').reset();
    document.getElementById('cust_id').value = '0';
    document.getElementById('cust_country').value = 'Malaysia';
    document.getElementById('custModalTitle').textContent = 'Add New Customer';
    openModal('customerModal');
}

async function openEditCustomerModal(id) {
    try {
        const res = await fetch(`<?= BASE_URL ?>/api/customers.php?action=get&id=${id}`);
        const data = await res.json();
        if (data.success && data.customer) {
            const c = data.customer;
            document.getElementById('cust_id').value = c.customer_id;
            document.getElementById('cust_fn').value = c.first_name;
            document.getElementById('cust_ln').value = c.last_name;
            document.getElementById('cust_email').value = c.email;
            document.getElementById('cust_phone').value = c.phone || '';
            document.getElementById('cust_city').value = c.city || '';
            document.getElementById('cust_country').value = c.country || 'Malaysia';
            document.getElementById('custModalTitle').textContent = 'Edit Customer #' + c.customer_id;
            openModal('customerModal');
        } else {
            showToast(data.message || 'Error loading customer', 'danger');
        }
    } catch (err) {
        showToast('Network error loading customer', 'danger');
    }
}

async function saveCustomer(e) {
    e.preventDefault();
    const btn = document.getElementById('saveCustBtn');
    btn.disabled = true;

    const formData = new FormData(document.getElementById('customerForm'));
    formData.append('action', 'save');

    try {
        const res = await fetch(`<?= BASE_URL ?>/api/customers.php`, {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        if (data.success) {
            showToast(data.message, 'success');
            closeModal('customerModal');
            setTimeout(() => window.location.reload(), 500);
        } else {
            showToast(data.message || 'Save failed', 'danger');
        }
    } catch (err) {
        showToast('Network error saving customer', 'danger');
    } finally {
        btn.disabled = false;
    }
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
