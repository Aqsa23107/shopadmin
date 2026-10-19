<?php
/**
 * Logout Handler
 */

require_once __DIR__ . '/config/config.php';

$_SESSION = [];

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();

// Restart session for flash message
session_start();
set_flash('info', 'You have been successfully logged out.');
redirect('login.php');
