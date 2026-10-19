<?php
/**
 * Authentication Gatekeeper
 * Ensures current user is logged in as Admin.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    // If it's an AJAX / API request
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized. Please log in.']);
        exit;
    }
    
    // Web request: redirect to login
    $loginUrl = defined('BASE_URL') ? BASE_URL . '/login.php' : 'login.php';
    header("Location: " . $loginUrl);
    exit;
}
