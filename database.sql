-- database.sql - Database schema cho SoundCloud Downloader API

-- Tạo database
CREATE DATABASE IF NOT EXISTS `soundcloud_api` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `soundcloud_api`;

-- Bảng users - Lưu thông tin người dùng
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` varchar(50) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng api_keys - Lưu API keys
CREATE TABLE IF NOT EXISTS `api_keys` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `key_value` varchar(100) NOT NULL,
  `user_id` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `request_limit` int(11) NOT NULL DEFAULT 10,
  `request_count` int(11) NOT NULL DEFAULT 0,
  `limit_type` enum('daily','monthly') NOT NULL DEFAULT 'daily',
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `order_id` varchar(50) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_used` datetime DEFAULT NULL,
  `daily_reset_at` date DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `key_value` (`key_value`),
  KEY `user_id` (`user_id`),
  KEY `email` (`email`),
  CONSTRAINT `fk_api_keys_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng orders - Lưu đơn hàng
CREATE TABLE IF NOT EXISTS `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` varchar(50) NOT NULL,
  `user_id` varchar(50) NOT NULL,
  `api_key` varchar(100) NOT NULL,
  `plan` varchar(50) NOT NULL,
  `plan_name` varchar(100) NOT NULL,
  `price` int(11) NOT NULL,
  `request_limit` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `status` enum('pending','completed','cancelled') NOT NULL DEFAULT 'pending',
  `transaction_id` varchar(100) DEFAULT NULL,
  `transaction_data` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `paid_at` datetime DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_id` (`order_id`),
  KEY `user_id` (`user_id`),
  KEY `status` (`status`),
  CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng request_logs - Log các request API (optional, để tracking)
CREATE TABLE IF NOT EXISTS `request_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `api_key` varchar(100) NOT NULL,
  `action` varchar(50) NOT NULL,
  `query` varchar(255) DEFAULT NULL,
  `track_id` varchar(50) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `response_status` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `api_key` (`api_key`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Indexes để tối ưu performance
CREATE INDEX idx_api_keys_status ON api_keys(status);
CREATE INDEX idx_api_keys_limit_type ON api_keys(limit_type);
CREATE INDEX idx_orders_status ON orders(status);
CREATE INDEX idx_request_logs_api_key_date ON request_logs(api_key, created_at);

-- Insert admin user mặc định
INSERT INTO `users` (`user_id`, `email`, `password`, `name`) 
VALUES ('ADMIN-001', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator')
ON DUPLICATE KEY UPDATE email=email;
