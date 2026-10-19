-- ======================================================
-- Database: shop_db
-- ShopAdmin Dashboard Database Schema & Seed Data
-- ======================================================

CREATE DATABASE IF NOT EXISTS `shop_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `shop_db`;

-- --------------------------------------------------------
-- Table structure for table `admins`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `admins` (
  `admin_id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL UNIQUE,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL UNIQUE,
  `role` varchar(30) NOT NULL DEFAULT 'Super Admin',
  `last_login` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`admin_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed default admin account (admin / admin123)
INSERT INTO `admins` (`admin_id`, `username`, `password`, `full_name`, `email`, `role`, `created_at`) 
VALUES (1, 'admin', '$2y$10$kJwcGkk01tDNuSO9qK.zB.P9bGKqomWrR4gVNxMOVK6UDQSTWmZq2', 'Shop Administrator', 'admin@shop.com', 'Super Admin', NOW())
ON DUPLICATE KEY UPDATE `full_name` = VALUES(`full_name`);

-- --------------------------------------------------------
-- Table structure for table `categories`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `categories` (
  `category_id` int(11) NOT NULL AUTO_INCREMENT,
  `category_name` varchar(50) NOT NULL UNIQUE,
  `description` text DEFAULT NULL,
  PRIMARY KEY (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table structure for table `products`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `products` (
  `product_id` int(11) NOT NULL AUTO_INCREMENT,
  `product_name` varchar(100) NOT NULL,
  `category_id` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock_quantity` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `description` text DEFAULT NULL,
  PRIMARY KEY (`product_id`),
  KEY `category_id` (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table structure for table `customers`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `customers` (
  `customer_id` int(11) NOT NULL AUTO_INCREMENT,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL UNIQUE,
  `phone` varchar(20) DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL,
  `country` varchar(50) NOT NULL DEFAULT 'Malaysia',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`customer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table structure for table `orders`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `orders` (
  `order_id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `order_date` date NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'Pending',
  PRIMARY KEY (`order_id`),
  KEY `customer_id` (`customer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table structure for table `order_items`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `order_items` (
  `order_item_id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `unit_price` decimal(10,2) NOT NULL,
  PRIMARY KEY (`order_item_id`),
  KEY `order_id` (`order_id`),
  KEY `product_id` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
