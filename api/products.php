<?php
/**
 * Products API Endpoint
 * Handles CRUD for Products in shop_db
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';

$pdo = Database::getConnection();
$action = $_REQUEST['action'] ?? '';

switch ($action) {
    case 'get':
        $id = (int)($_GET['id'] ?? 0);
        $stmt = $pdo->prepare("SELECT * FROM products WHERE product_id = ?");
        $stmt->execute([$id]);
        $product = $stmt->fetch();
        if ($product) {
            json_response(['success' => true, 'product' => $product]);
        } else {
            json_response(['success' => false, 'message' => 'Product not found.'], 404);
        }
        break;

    case 'save':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            json_response(['success' => false, 'message' => 'Method not allowed'], 405);
        }

        $id = (int)($_POST['product_id'] ?? 0);
        $name = trim($_POST['product_name'] ?? '');
        $categoryId = (int)($_POST['category_id'] ?? 0);
        $price = (float)($_POST['price'] ?? 0);
        $stock = (int)($_POST['stock_quantity'] ?? 0);
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        $description = trim($_POST['description'] ?? '');

        if (empty($name) || $categoryId <= 0 || $price < 0) {
            json_response(['success' => false, 'message' => 'Please fill in all required fields properly.'], 400);
        }

        try {
            if ($id > 0) {
                // Update
                $stmt = $pdo->prepare("UPDATE products SET 
                    product_name = ?, 
                    category_id = ?, 
                    price = ?, 
                    stock_quantity = ?, 
                    is_active = ?, 
                    description = ? 
                    WHERE product_id = ?");
                $stmt->execute([$name, $categoryId, $price, $stock, $isActive, $description, $id]);
                json_response(['success' => true, 'message' => 'Product updated successfully!']);
            } else {
                // Create
                $stmt = $pdo->prepare("INSERT INTO products 
                    (product_name, category_id, price, stock_quantity, is_active, description) 
                    VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$name, $categoryId, $price, $stock, $isActive, $description]);
                json_response(['success' => true, 'message' => 'Product created successfully!']);
            }
        } catch (Exception $e) {
            json_response(['success' => false, 'message' => 'Database error: ' . $e->getMessage()], 500);
        }
        break;

    case 'toggle_status':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            json_response(['success' => false, 'message' => 'Method not allowed'], 405);
        }
        $id = (int)($_POST['product_id'] ?? 0);
        $stmt = $pdo->prepare("UPDATE products SET is_active = IF(is_active = 1, 0, 1) WHERE product_id = ?");
        $stmt->execute([$id]);
        json_response(['success' => true, 'message' => 'Status updated!']);
        break;

    case 'delete':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            json_response(['success' => false, 'message' => 'Method not allowed'], 405);
        }
        $id = (int)($_POST['product_id'] ?? 0);

        try {
            // Check if product is referenced in order_items
            $check = $pdo->prepare("SELECT COUNT(*) FROM order_items WHERE product_id = ?");
            $check->execute([$id]);
            if ($check->fetchColumn() > 0) {
                // Soft disable or prevent delete
                $soft = $pdo->prepare("UPDATE products SET is_active = 0 WHERE product_id = ?");
                $soft->execute([$id]);
                json_response([
                    'success' => true, 
                    'message' => 'Product has existing orders. It has been deactivated instead of permanently deleted.'
                ]);
            } else {
                $del = $pdo->prepare("DELETE FROM products WHERE product_id = ?");
                $del->execute([$id]);
                json_response(['success' => true, 'message' => 'Product deleted successfully!']);
            }
        } catch (Exception $e) {
            json_response(['success' => false, 'message' => 'Could not delete product: ' . $e->getMessage()], 500);
        }
        break;

    default:
        json_response(['success' => false, 'message' => 'Invalid action.'], 400);
}
