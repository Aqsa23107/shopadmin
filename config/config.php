<?php
/**
 * Application Configuration & Helper Functions
 * ShopAdmin Core PHP Dashboard
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set default timezone
date_default_timezone_set('UTC');

// Dynamic Base URL Detection
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$requestUri = $_SERVER['REQUEST_URI'] ?? '';
$scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');

if (str_contains($scriptName, '/shopadmin/')) {
    $subPath = substr($scriptName, 0, strpos($scriptName, '/shopadmin/') + strlen('/shopadmin'));
    $baseUrl = $protocol . $host . $subPath;
} elseif (str_starts_with($requestUri, '/shopadmin')) {
    $baseUrl = $protocol . $host . '/shopadmin';
} else {
    // Served directly from document root (e.g. php -S localhost:8080)
    $baseUrl = $protocol . $host;
}
define('BASE_URL', rtrim($baseUrl, '/'));
define('APP_NAME', 'ShopSphere Admin');
define('APP_VERSION', '1.0.0');

// Currency symbol
define('CURRENCY_SYMBOL', '$');

/**
 * Sanitize output string
 */
function e(?string $string): string {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Format currency
 */
function format_price($amount): string {
    return CURRENCY_SYMBOL . number_format((float)$amount, 2);
}

/**
 * Format date
 */
function format_date(?string $date, string $format = 'M d, Y'): string {
    if (!$date) return '-';
    $timestamp = strtotime($date);
    return $timestamp ? date($format, $timestamp) : '-';
}

/**
 * Redirect helper
 */
function redirect(string $path): void {
    if (!str_starts_with($path, 'http://') && !str_starts_with($path, 'https://')) {
        $path = BASE_URL . '/' . ltrim($path, '/');
    }
    header("Location: " . $path);
    exit;
}

/**
 * Flash message helpers
 */
function set_flash(string $type, string $message): void {
    $_SESSION['flash'] = [
        'type' => $type, // 'success', 'danger', 'warning', 'info'
        'message' => $message
    ];
}

function get_flash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Send JSON response
 */
function json_response(array $data, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}
