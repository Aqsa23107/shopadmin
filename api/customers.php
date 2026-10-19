<?php
/**
 * Customers API Endpoint
 * Handles CRUD and retrieval for Customers in shop_db
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';

$pdo = Database::getConnection();
$action = $_REQUEST['action'] ?? '';

switch ($action) {
    case 'get':
        $id = (int)($_GET['id'] ?? 0);
        $stmt = $pdo->prepare("SELECT * FROM customers WHERE customer_id = ?");
        $stmt->execute([$id]);
        $cust = $stmt->fetch();
        if ($cust) {
            json_response(['success' => true, 'customer' => $cust]);
        } else {
            json_response(['success' => false, 'message' => 'Customer not found.'], 404);
        }
        break;

    case 'save':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            json_response(['success' => false, 'message' => 'Method not allowed'], 405);
        }

        $id = (int)($_POST['customer_id'] ?? 0);
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $country = trim($_POST['country'] ?? 'Malaysia');

        if (empty($firstName) || empty($lastName) || empty($email)) {
            json_response(['success' => false, 'message' => 'First name, last name, and email are required.'], 400);
        }

        try {
            if ($id > 0) {
                $stmt = $pdo->prepare("UPDATE customers SET first_name = ?, last_name = ?, email = ?, phone = ?, city = ?, country = ? WHERE customer_id = ?");
                $stmt->execute([$firstName, $lastName, $email, $phone, $city, $country, $id]);
                json_response(['success' => true, 'message' => 'Customer updated successfully!']);
            } else {
                $stmt = $pdo->prepare("INSERT INTO customers (first_name, last_name, email, phone, city, country, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
                $stmt->execute([$firstName, $lastName, $email, $phone, $city, $country]);
                json_response(['success' => true, 'message' => 'Customer added successfully!']);
            }
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                json_response(['success' => false, 'message' => 'A customer with this email address already exists.'], 400);
            }
            json_response(['success' => false, 'message' => 'Database error: ' . $e->getMessage()], 500);
        }
        break;

    default:
        json_response(['success' => false, 'message' => 'Invalid action.'], 400);
}
