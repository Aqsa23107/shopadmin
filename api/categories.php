<?php
/**
 * Categories API Endpoint
 * Handles CRUD for Product Categories in shop_db
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';

$pdo = Database::getConnection();
$action = $_REQUEST['action'] ?? '';

switch ($action) {
    case 'get':
        $id = (int)($_GET['id'] ?? 0);
        $stmt = $pdo->prepare("SELECT * FROM categories WHERE category_id = ?");
        $stmt->execute([$id]);
        $cat = $stmt->fetch();
        if ($cat) {
            json_response(['success' => true, 'category' => $cat]);
        } else {
            json_response(['success' => false, 'message' => 'Category not found.'], 404);
        }
        break;

    case 'save':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            json_response(['success' => false, 'message' => 'Method not allowed'], 405);
        }

        $id = (int)($_POST['category_id'] ?? 0);
        $name = trim($_POST['category_name'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if (empty($name)) {
            json_response(['success' => false, 'message' => 'Category name is required.'], 400);
        }

        try {
            if ($id > 0) {
                $stmt = $pdo->prepare("UPDATE categories SET category_name = ?, description = ? WHERE category_id = ?");
                $stmt->execute([$name, $description, $id]);
                json_response(['success' => true, 'message' => 'Category updated successfully!']);
            } else {
                $stmt = $pdo->prepare("INSERT INTO categories (category_name, description) VALUES (?, ?)");
                $stmt->execute([$name, $description]);
                json_response(['success' => true, 'message' => 'Category created successfully!']);
            }
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                json_response(['success' => false, 'message' => 'A category with this name already exists.'], 400);
            }
            json_response(['success' => false, 'message' => 'Database error: ' . $e->getMessage()], 500);
        }
        break;

    case 'delete':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            json_response(['success' => false, 'message' => 'Method not allowed'], 405);
        }
        $id = (int)($_POST['category_id'] ?? 0);

        try {
            // Check if there are linked products
            $check = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
            $check->execute([$id]);
            if ($check->fetchColumn() > 0) {
                json_response([
                    'success' => false, 
                    'message' => 'Cannot delete this category because it contains active products. Reassign the products first.'
                ], 400);
            }

            $del = $pdo->prepare("DELETE FROM categories WHERE category_id = ?");
            $del->execute([$id]);
            json_response(['success' => true, 'message' => 'Category deleted successfully!']);
        } catch (Exception $e) {
            json_response(['success' => false, 'message' => 'Could not delete category: ' . $e->getMessage()], 500);
        }
        break;

    default:
        json_response(['success' => false, 'message' => 'Invalid action.'], 400);
}
