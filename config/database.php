<?php
/**
 * Database Connection for ShopAdmin
 * Uses PDO for secure, prepared SQL execution.
 */

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'shop_db');
define('DB_CHARSET', 'utf8mb4');

class Database {
    private static ?PDO $instance = null;

    public static function getConnection(): PDO {
        if (self::$instance === null) {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            try {
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
                self::ensureTables();
            } catch (PDOException $e) {
                // If shop_db doesn't exist, provide a helpful error message
                die("<div style='font-family:sans-serif;padding:30px;background:#fee2e2;color:#991b1b;border:1px solid #f87171;border-radius:8px;max-width:600px;margin:50px auto;'>
                    <h2 style='margin-top:0;'>Database Connection Error</h2>
                    <p>Could not connect to database <strong>" . htmlspecialchars(DB_NAME) . "</strong> on <strong>" . htmlspecialchars(DB_HOST) . "</strong>.</p>
                    <p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>
                    <p>Please make sure MySQL is running in XAMPP or your local server environment.</p>
                </div>");
            }
        }
        return self::$instance;
    }

    /**
     * Ensures admins table exists and seeds the default admin user
     */
    private static function ensureTables(): void {
        try {
            $pdo = self::$instance;
            
            // Check / create admins table
            $pdo->exec("CREATE TABLE IF NOT EXISTS `admins` (
                `admin_id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `username` VARCHAR(50) NOT NULL UNIQUE,
                `password` VARCHAR(255) NOT NULL,
                `full_name` VARCHAR(100) NOT NULL,
                `email` VARCHAR(100) NOT NULL UNIQUE,
                `role` VARCHAR(30) NOT NULL DEFAULT 'Super Admin',
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

            // Seed default admin only if admins table is empty
            $countStmt = $pdo->query("SELECT COUNT(*) FROM `admins`");
            if ($countStmt->fetchColumn() == 0) {
                $hash = password_hash('admin123', PASSWORD_BCRYPT);
                $ins = $pdo->prepare("INSERT INTO `admins` (username, password, full_name, email, role) VALUES (?, ?, ?, ?, ?)");
                $ins->execute(['admin', $hash, 'Shop Administrator', 'admin@shop.com', 'Super Admin']);
            }
        } catch (Exception $e) {
            // Non-critical, continue
        }
    }
}
