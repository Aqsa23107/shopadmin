<?php
/**
 * Orders API Endpoint
 * Handles status updates and item details for orders in shop_db
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';

$pdo = Database::getConnection();
$action = $_REQUEST['action'] ?? '';

switch ($action) {
    case 'update_status':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            json_response(['success' => false, 'message' => 'Method not allowed'], 405);
        }

        $orderId = (int)($_POST['order_id'] ?? 0);
        $status = trim($_POST['status'] ?? '');

        $validStatuses = ['Pending', 'Shipped', 'Delivered', 'Cancelled'];
        if (!in_array($status, $validStatuses, true)) {
            json_response(['success' => false, 'message' => 'Invalid order status selected.'], 400);
        }

        try {
            $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE order_id = ?");
            $stmt->execute([$status, $orderId]);
            json_response(['success' => true, 'message' => "Order #$orderId status updated to $status."]);
        } catch (Exception $e) {
            json_response(['success' => false, 'message' => 'Database error: ' . $e->getMessage()], 500);
        }
        break;

    case 'get_details':
        $orderId = (int)($_GET['order_id'] ?? 0);

        // Fetch order + customer info
        $orderStmt = $pdo->prepare("SELECT 
            o.*, 
            c.first_name, 
            c.last_name, 
            c.email, 
            c.phone, 
            c.city, 
            c.country 
        FROM orders o 
        JOIN customers c ON o.customer_id = c.customer_id 
        WHERE o.order_id = ?");
        $orderStmt->execute([$orderId]);
        $order = $orderStmt->fetch();

        if (!$order) {
            json_response(['success' => false, 'message' => 'Order not found.'], 404);
        }

        // Fetch line items
        $itemsStmt = $pdo->prepare("SELECT 
            oi.*, 
            p.product_name, 
            p.price as current_product_price 
        FROM order_items oi 
        JOIN products p ON oi.product_id = p.product_id 
        WHERE oi.order_id = ?");
        $itemsStmt->execute([$orderId]);
        $items = $itemsStmt->fetchAll();

        json_response([
            'success' => true,
            'order' => $order,
            'items' => $items
        ]);
        break;

    default:
        json_response(['success' => false, 'message' => 'Invalid action.'], 400);
}
