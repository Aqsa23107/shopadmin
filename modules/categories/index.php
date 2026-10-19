<?php
/**
 * Categories Management Module
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth_check.php';

$pdo = Database::getConnection();

// Fetch categories with product counts
$sql = "SELECT c.*, COUNT(p.product_id) as total_products, COALESCE(SUM(p.stock_quantity), 0) as total_units
        FROM categories c
        LEFT JOIN products p ON c.category_id = p.category_id
        GROUP BY c.category_id
        ORDER BY c.category_name ASC";
$categories = $pdo->query($sql)->fetchAll();

$pageTitle = 'Category Management';
$activePage = 'categories';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
require_once __DIR__ . '/../../includes/topbar.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Category Management</h1>
        <p class="page-subtitle">Organize your store products into intuitive catalog departments</p>
    </div>
    <button type="button" class="btn btn-primary btn-sm" onclick="openAddCategoryModal()">
        <i class="fa-solid fa-plus"></i> Add New Category
    </button>
</div>

<!-- Categories Grid / Table Card -->
<div class="card">
    <div class="card-header">
        <div class="card-title">
            <i class="fa-solid fa-layer-group" style="color:var(--info);"></i>
            All Store Categories (<?= count($categories) ?>)
        </div>
    </div>
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width:70px;">ID</th>
                    <th>Category Name</th>
                    <th>Description</th>
                    <th>Assigned Products</th>
                    <th>Total Inventory Units</th>
                    <th style="text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($categories)): ?>
                    <tr>
                        <td colspan="6" style="text-align:center;color:var(--text-muted);padding:3rem;">
                            No categories found in store.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($categories as $cat): ?>
                        <tr>
                            <td style="color:var(--text-muted);font-weight:600;">#<?= $cat['category_id'] ?></td>
                            <td>
                                <strong style="font-size:0.95rem;color:var(--text-primary);"><?= e($cat['category_name']) ?></strong>
                            </td>
                            <td style="color:var(--text-secondary);max-width:300px;">
                                <?= e($cat['description'] ?: 'No description.') ?>
                            </td>
                            <td>
                                <a href="<?= BASE_URL ?>/modules/products/index.php?category=<?= $cat['category_id'] ?>" class="badge badge-primary" style="background:rgba(99,102,241,0.12);color:var(--primary);border:1px solid rgba(99,102,241,0.2);">
                                    <i class="fa-solid fa-box"></i> <?= $cat['total_products'] ?> Products
                                </a>
                            </td>
                            <td style="font-weight:600;">
                                <?= number_format($cat['total_units']) ?> units
                            </td>
                            <td style="text-align:right;">
                                <button type="button" class="btn btn-secondary btn-icon" onclick="openEditCategoryModal(<?= $cat['category_id'] ?>)" title="Edit Category">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </button>
                                <button type="button" class="btn btn-danger btn-icon" onclick="confirmDeleteCategory(<?= $cat['category_id'] ?>, '<?= e(addslashes($cat['category_name'])) ?>', <?= (int)$cat['total_products'] ?>)" title="Delete Category">
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

<!-- Category Add / Edit Modal -->
<div class="modal-overlay" id="categoryModal">
    <div class="modal-content" style="max-width:480px;">
        <div class="modal-header">
            <h3 class="modal-title" id="catModalTitle" style="font-size:1.1rem;font-weight:700;">Add Category</h3>
            <button type="button" data-modal-close style="background:none;border:none;color:var(--text-muted);cursor:pointer;font-size:1.2rem;">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form id="categoryForm" onsubmit="saveCategory(event)">
            <div class="modal-body">
                <input type="hidden" name="category_id" id="cat_id" value="0">

                <div class="form-group">
                    <label class="form-label" for="cat_name">Category Name *</label>
                    <input type="text" name="category_name" id="cat_name" class="form-control" required placeholder="e.g. Smart Home Devices">
                </div>

                <div class="form-group">
                    <label class="form-label" for="cat_desc">Description</label>
                    <textarea name="description" id="cat_desc" class="form-control" rows="3" placeholder="Category purpose or notes..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-primary" id="saveCatBtn">
                    <i class="fa-solid fa-floppy-disk"></i> Save Category
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal-overlay" id="deleteCatModal">
    <div class="modal-content" style="max-width:420px;">
        <div class="modal-header">
            <h3 class="modal-title" style="color:var(--danger);"><i class="fa-solid fa-triangle-exclamation"></i> Delete Category</h3>
            <button type="button" data-modal-close style="background:none;border:none;color:var(--text-muted);cursor:pointer;font-size:1.2rem;">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="modal-body">
            <p>Are you sure you want to delete category <strong id="deleteCatName"></strong>?</p>
            <div id="deleteCatWarning" style="margin-top:0.75rem;display:none;" class="badge badge-danger">
                Cannot delete: this category currently has linked products.
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
            <button type="button" class="btn btn-danger" id="confirmDeleteCatBtn" onclick="executeDeleteCategory()">
                <i class="fa-solid fa-trash-can"></i> Confirm Delete
            </button>
        </div>
    </div>
</div>

<script>
let activeCatDeleteId = null;

function openAddCategoryModal() {
    document.getElementById('categoryForm').reset();
    document.getElementById('cat_id').value = '0';
    document.getElementById('catModalTitle').textContent = 'Add New Category';
    openModal('categoryModal');
}

async function openEditCategoryModal(id) {
    try {
        const res = await fetch(`<?= BASE_URL ?>/api/categories.php?action=get&id=${id}`);
        const data = await res.json();
        if (data.success && data.category) {
            document.getElementById('cat_id').value = data.category.category_id;
            document.getElementById('cat_name').value = data.category.category_name;
            document.getElementById('cat_desc').value = data.category.description || '';
            document.getElementById('catModalTitle').textContent = 'Edit Category #' + data.category.category_id;
            openModal('categoryModal');
        } else {
            showToast(data.message || 'Error loading category', 'danger');
        }
    } catch (e) {
        showToast('Network error loading category', 'danger');
    }
}

async function saveCategory(e) {
    e.preventDefault();
    const btn = document.getElementById('saveCatBtn');
    btn.disabled = true;

    const formData = new FormData(document.getElementById('categoryForm'));
    formData.append('action', 'save');

    try {
        const res = await fetch(`<?= BASE_URL ?>/api/categories.php`, {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        if (data.success) {
            showToast(data.message, 'success');
            closeModal('categoryModal');
            setTimeout(() => window.location.reload(), 500);
        } else {
            showToast(data.message || 'Save failed', 'danger');
        }
    } catch (err) {
        showToast('Network error saving category', 'danger');
    } finally {
        btn.disabled = false;
    }
}

function confirmDeleteCategory(id, name, prodCount) {
    activeCatDeleteId = id;
    document.getElementById('deleteCatName').textContent = name;
    const warning = document.getElementById('deleteCatWarning');
    const deleteBtn = document.getElementById('confirmDeleteCatBtn');

    if (prodCount > 0) {
        warning.style.display = 'inline-block';
        deleteBtn.disabled = true;
    } else {
        warning.style.display = 'none';
        deleteBtn.disabled = false;
    }
    openModal('deleteCatModal');
}

async function executeDeleteCategory() {
    if (!activeCatDeleteId) return;
    const btn = document.getElementById('confirmDeleteCatBtn');
    btn.disabled = true;

    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('category_id', activeCatDeleteId);

    try {
        const res = await fetch(`<?= BASE_URL ?>/api/categories.php`, {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        if (data.success) {
            showToast(data.message, 'success');
            closeModal('deleteCatModal');
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
