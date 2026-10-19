<?php
/**
 * Products Management Module
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth_check.php';

$pdo = Database::getConnection();

// Fetch categories for filtering and dropdowns
$categories = $pdo->query("SELECT * FROM categories ORDER BY category_name ASC")->fetchAll();

// Handle Search and Filter Query
$search = trim($_GET['search'] ?? '');
$filterCategory = (int)($_GET['category'] ?? 0);
$filterStatus = $_GET['status'] ?? '';
$filterStock = $_GET['stock'] ?? '';

$sql = "SELECT p.*, c.category_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.category_id 
        WHERE 1=1";
$params = [];

if ($search !== '') {
    $sql .= " AND (p.product_name LIKE ? OR p.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($filterCategory > 0) {
    $sql .= " AND p.category_id = ?";
    $params[] = $filterCategory;
}

if ($filterStatus === 'active') {
    $sql .= " AND p.is_active = 1";
} elseif ($filterStatus === 'inactive') {
    $sql .= " AND p.is_active = 0";
}

if ($filterStock === 'low') {
    $sql .= " AND p.stock_quantity <= 20 AND p.stock_quantity > 0";
} elseif ($filterStock === 'out') {
    $sql .= " AND p.stock_quantity = 0";
}

$sql .= " ORDER BY p.product_id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Check for CSV export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=products_' . date('Y-m-d') . '.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Product Name', 'Category', 'Price', 'Stock', 'Status', 'Description']);
    foreach ($products as $p) {
        fputcsv($output, [
            $p['product_id'],
            $p['product_name'],
            $p['category_name'],
            $p['price'],
            $p['stock_quantity'],
            $p['is_active'] ? 'Active' : 'Inactive',
            $p['description']
        ]);
    }
    fclose($output);
    exit;
}

$pageTitle = 'Products Catalog';
$activePage = 'products';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
require_once __DIR__ . '/../../includes/topbar.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Products Management</h1>
        <p class="page-subtitle">Manage items, pricing, inventory stock, and catalog categories</p>
    </div>
    <div style="display:flex;gap:0.75rem;">
        <a href="?<?= http_build_query(array_merge($_GET, ['export' => 'csv'])) ?>" class="btn btn-secondary btn-sm">
            <i class="fa-solid fa-file-csv"></i> Export CSV
        </a>
        <button type="button" class="btn btn-primary btn-sm" onclick="openAddProductModal()">
            <i class="fa-solid fa-plus"></i> Add New Product
        </button>
    </div>
</div>

<!-- Filters Bar -->
<div class="card" style="margin-bottom:1.5rem;">
    <div class="card-body" style="padding:1rem 1.5rem;">
        <form method="GET" action="" style="display:flex;gap:1rem;flex-wrap:wrap;align-items:center;">
            <div style="flex:1;min-width:200px;">
                <input type="text" name="search" class="form-control" placeholder="Search product name or description..." value="<?= e($search) ?>">
            </div>
            <div style="min-width:180px;">
                <select name="category" class="form-control" onchange="this.form.submit()">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['category_id'] ?>" <?= $filterCategory === (int)$cat['category_id'] ? 'selected' : '' ?>>
                            <?= e($cat['category_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="min-width:140px;">
                <select name="status" class="form-control" onchange="this.form.submit()">
                    <option value="">All Status</option>
                    <option value="active" <?= $filterStatus === 'active' ? 'selected' : '' ?>>Active Only</option>
                    <option value="inactive" <?= $filterStatus === 'inactive' ? 'selected' : '' ?>>Inactive Only</option>
                </select>
            </div>
            <div style="min-width:140px;">
                <select name="stock" class="form-control" onchange="this.form.submit()">
                    <option value="">All Stock</option>
                    <option value="low" <?= $filterStock === 'low' ? 'selected' : '' ?>>Low Stock (<= 20)</option>
                    <option value="out" <?= $filterStock === 'out' ? 'selected' : '' ?>>Out of Stock (0)</option>
                </select>
            </div>
            <button type="submit" class="btn btn-secondary" style="padding:0.65rem 1rem;">
                <i class="fa-solid fa-filter"></i> Filter
            </button>
            <?php if (!empty($search) || !empty($filterCategory) || !empty($filterStatus) || !empty($filterStock)): ?>
                <a href="index.php" class="btn btn-secondary" style="padding:0.65rem 1rem;" title="Clear Filters">
                    <i class="fa-solid fa-rotate-left"></i>
                </a>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Products Table Card -->
<div class="card">
    <div class="card-header">
        <div class="card-title">
            <i class="fa-solid fa-boxes-stacked" style="color:var(--primary);"></i>
            Catalog Products (<?= count($products) ?> items)
        </div>
    </div>
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width:60px;">ID</th>
                    <th>Product</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Status</th>
                    <th style="text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($products)): ?>
                    <tr>
                        <td colspan="7" style="text-align:center;color:var(--text-muted);padding:3rem;">
                            <i class="fa-solid fa-box-open" style="font-size:2rem;margin-bottom:0.5rem;display:block;"></i>
                            No products match your current filters.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($products as $p): ?>
                        <tr>
                            <td style="color:var(--text-muted);font-weight:600;">#<?= $p['product_id'] ?></td>
                            <td>
                                <div style="font-weight:600;"><?= e($p['product_name']) ?></div>
                                <div style="font-size:0.75rem;color:var(--text-muted);max-width:320px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                    <?= e($p['description'] ?: 'No description provided.') ?>
                                </div>
                            </td>
                            <td>
                                <span class="badge badge-secondary"><?= e($p['category_name'] ?: 'Uncategorized') ?></span>
                            </td>
                            <td style="font-weight:700;color:var(--text-primary);"><?= format_price($p['price']) ?></td>
                            <td>
                                <?php if ($p['stock_quantity'] <= 0): ?>
                                    <span class="badge badge-danger">Out of Stock (0)</span>
                                <?php elseif ($p['stock_quantity'] <= 20): ?>
                                    <span class="badge badge-warning"><?= $p['stock_quantity'] ?> left</span>
                                <?php else: ?>
                                    <span class="badge badge-success"><?= $p['stock_quantity'] ?> in stock</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button type="button" 
                                    class="badge <?= $p['is_active'] ? 'badge-success' : 'badge-secondary' ?>" 
                                    style="border:none;cursor:pointer;" 
                                    onclick="toggleProductStatus(<?= $p['product_id'] ?>)" 
                                    title="Click to toggle status">
                                    <i class="fa-solid <?= $p['is_active'] ? 'fa-check' : 'fa-ban' ?>"></i> 
                                    <?= $p['is_active'] ? 'Active' : 'Disabled' ?>
                                </button>
                            </td>
                            <td style="text-align:right;">
                                <button type="button" class="btn btn-secondary btn-icon" onclick="openEditProductModal(<?= $p['product_id'] ?>)" title="Edit Product">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </button>
                                <button type="button" class="btn btn-danger btn-icon" onclick="confirmDeleteProduct(<?= $p['product_id'] ?>, '<?= e(addslashes($p['product_name'])) ?>')" title="Delete Product">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Product Add / Edit Modal -->
<div class="modal-overlay" id="productModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title" id="productModalTitle" style="font-size:1.1rem;font-weight:700;">Add Product</h3>
            <button type="button" data-modal-close style="background:none;border:none;color:var(--text-muted);cursor:pointer;font-size:1.2rem;">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form id="productForm" onsubmit="saveProduct(event)">
            <div class="modal-body">
                <input type="hidden" name="product_id" id="prod_id" value="0">

                <div class="form-group">
                    <label class="form-label" for="prod_name">Product Name *</label>
                    <input type="text" name="product_name" id="prod_name" class="form-control" required placeholder="e.g. Wireless Noise-Cancelling Headphones">
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                    <div class="form-group">
                        <label class="form-label" for="prod_category">Category *</label>
                        <select name="category_id" id="prod_category" class="form-control" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['category_id'] ?>"><?= e($cat['category_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="prod_price">Price ($) *</label>
                        <input type="number" step="0.01" min="0" name="price" id="prod_price" class="form-control" required placeholder="29.99">
                    </div>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                    <div class="form-group">
                        <label class="form-label" for="prod_stock">Initial Stock *</label>
                        <input type="number" min="0" name="stock_quantity" id="prod_stock" class="form-control" required value="50">
                    </div>

                    <div class="form-group" style="display:flex;flex-direction:column;justify-content:center;">
                        <label class="form-label">Visibility</label>
                        <label style="display:flex;align-items:center;gap:0.5rem;cursor:pointer;margin-top:0.35rem;">
                            <input type="checkbox" name="is_active" id="prod_active" value="1" checked style="width:18px;height:18px;accent-color:var(--primary);">
                            <span>Active in Store</span>
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="prod_desc">Description</label>
                    <textarea name="description" id="prod_desc" class="form-control" rows="3" placeholder="Brief product summary..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-primary" id="saveProdBtn">
                    <i class="fa-solid fa-floppy-disk"></i> Save Product
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal-overlay" id="deleteModal">
    <div class="modal-content" style="max-width:420px;">
        <div class="modal-header">
            <h3 class="modal-title" style="color:var(--danger);"><i class="fa-solid fa-triangle-exclamation"></i> Confirm Delete</h3>
            <button type="button" data-modal-close style="background:none;border:none;color:var(--text-muted);cursor:pointer;font-size:1.2rem;">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="modal-body">
            <p>Are you sure you want to delete <strong id="deleteTargetName">this product</strong>?</p>
            <p style="font-size:0.8rem;color:var(--text-muted);margin-top:0.5rem;">If this product has associated order history, it will be deactivated to maintain sales integrity.</p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
            <button type="button" class="btn btn-danger" id="confirmDeleteBtn" onclick="executeDeleteProduct()">
                <i class="fa-solid fa-trash-can"></i> Delete
            </button>
        </div>
    </div>
</div>

<script>
let activeDeleteId = null;

function openAddProductModal() {
    document.getElementById('productForm').reset();
    document.getElementById('prod_id').value = '0';
    document.getElementById('prod_active').checked = true;
    document.getElementById('productModalTitle').textContent = 'Add New Product';
    openModal('productModal');
}

async function openEditProductModal(id) {
    try {
        const res = await fetch(`<?= BASE_URL ?>/api/products.php?action=get&id=${id}`);
        const data = await res.json();
        if (data.success && data.product) {
            const p = data.product;
            document.getElementById('prod_id').value = p.product_id;
            document.getElementById('prod_name').value = p.product_name;
            document.getElementById('prod_category').value = p.category_id;
            document.getElementById('prod_price').value = p.price;
            document.getElementById('prod_stock').value = p.stock_quantity;
            document.getElementById('prod_active').checked = p.is_active == 1;
            document.getElementById('prod_desc').value = p.description || '';
            document.getElementById('productModalTitle').textContent = 'Edit Product #' + p.product_id;
            openModal('productModal');
        } else {
            showToast(data.message || 'Error loading product', 'danger');
        }
    } catch (err) {
        showToast('Network error loading product', 'danger');
    }
}

async function saveProduct(e) {
    e.preventDefault();
    const btn = document.getElementById('saveProdBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving...';

    const formData = new FormData(document.getElementById('productForm'));
    formData.append('action', 'save');

    try {
        const res = await fetch(`<?= BASE_URL ?>/api/products.php`, {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        if (data.success) {
            showToast(data.message, 'success');
            closeModal('productModal');
            setTimeout(() => window.location.reload(), 600);
        } else {
            showToast(data.message || 'Save failed', 'danger');
        }
    } catch (err) {
        showToast('Network error saving product', 'danger');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Save Product';
    }
}

async function toggleProductStatus(id) {
    const formData = new FormData();
    formData.append('action', 'toggle_status');
    formData.append('product_id', id);

    try {
        const res = await fetch(`<?= BASE_URL ?>/api/products.php`, {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        if (data.success) {
            showToast(data.message, 'success');
            setTimeout(() => window.location.reload(), 400);
        }
    } catch (err) {
        showToast('Failed to toggle status', 'danger');
    }
}

function confirmDeleteProduct(id, name) {
    activeDeleteId = id;
    document.getElementById('deleteTargetName').textContent = name;
    openModal('deleteModal');
}

async function executeDeleteProduct() {
    if (!activeDeleteId) return;
    const btn = document.getElementById('confirmDeleteBtn');
    btn.disabled = true;

    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('product_id', activeDeleteId);

    try {
        const res = await fetch(`<?= BASE_URL ?>/api/products.php`, {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        if (data.success) {
            showToast(data.message, 'success');
            closeModal('deleteModal');
            setTimeout(() => window.location.reload(), 500);
        } else {
            showToast(data.message || 'Delete failed', 'danger');
        }
    } catch (err) {
        showToast('Network error', 'danger');
    } finally {
        btn.disabled = false;
    }
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
