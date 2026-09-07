-- Database Schema for Pub & Bar Booking System
-- Database Name: pub_booking_db

CREATE DATABASE IF NOT EXISTS `pub_booking_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `pub_booking_db`;

-- 1. Users Table
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `phone` VARCHAR(20) NULL,
    `role` ENUM('user', 'admin') DEFAULT 'user',
    `points` INT DEFAULT 50,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Bars Table
CREATE TABLE IF NOT EXISTS `bars` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(150) NOT NULL,
    `city` VARCHAR(100) DEFAULT 'เมืองสงขลา',
    `address` VARCHAR(255) NOT NULL,
    `description` TEXT,
    `image` VARCHAR(255),
    `open_time` TIME DEFAULT '17:00:00',
    `cutoff_time` TIME DEFAULT '20:00:00',
    `close_time` TIME DEFAULT '02:00:00',
    `promptpay_number` VARCHAR(30) DEFAULT '0812345678',
    `promptpay_name` VARCHAR(100) DEFAULT 'Songkhla Nightlife Co., Ltd.',
    `phone` VARCHAR(20) DEFAULT '074-123456',
    `line_id` VARCHAR(50) DEFAULT '@nightlife_sk',
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Tables Table
CREATE TABLE IF NOT EXISTS `tables` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `bar_id` INT NOT NULL,
    `table_number` VARCHAR(20) NOT NULL,
    `zone` VARCHAR(50) NOT NULL,
    `capacity` INT DEFAULT 4,
    `status` ENUM('available', 'reserved', 'maintenance') DEFAULT 'available',
    `pos_x` INT DEFAULT 0,
    `pos_y` INT DEFAULT 0,
    FOREIGN KEY (`bar_id`) REFERENCES `bars`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Promotions Table
CREATE TABLE IF NOT EXISTS `promotions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `bar_id` INT NULL,
    `title` VARCHAR(200) NOT NULL,
    `description` TEXT NOT NULL,
    `price` DECIMAL(10,2) NOT NULL,
    `points_reward` INT DEFAULT 30,
    `image` VARCHAR(255),
    `badge` VARCHAR(50) DEFAULT 'HOT DEAL',
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Bookings Table
CREATE TABLE IF NOT EXISTS `bookings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `booking_code` VARCHAR(30) NOT NULL UNIQUE,
    `user_id` INT NOT NULL,
    `bar_id` INT NOT NULL,
    `table_id` INT NOT NULL,
    `promotion_id` INT NULL,
    `booking_date` DATE NOT NULL,
    `arrival_time` TIME NOT NULL,
    `guests_count` INT DEFAULT 2,
    `total_amount` DECIMAL(10,2) DEFAULT 0.00,
    `points_earned` INT DEFAULT 0,
    `payment_method` VARCHAR(50) DEFAULT 'PromptPay',
    `payment_slip` VARCHAR(255) NULL,
    `status` ENUM('pending_payment', 'confirmed', 'checked_in', 'cancelled', 'no_show') DEFAULT 'pending_payment',
    `notes` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`bar_id`) REFERENCES `bars`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`table_id`) REFERENCES `tables`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Points History
CREATE TABLE IF NOT EXISTS `points_history` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `booking_id` INT NULL,
    `points_change` INT NOT NULL,
    `description` VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Contact Messages (Threads)
CREATE TABLE IF NOT EXISTS `contact_messages` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NULL,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(150) NOT NULL,
    `phone` VARCHAR(30) NULL,
    `subject` VARCHAR(200) NOT NULL,
    `message` TEXT NOT NULL,
    `admin_reply` TEXT NULL,
    `status` ENUM('unread', 'replied') DEFAULT 'unread',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Contact Replies (Two-way Chat Messages)
CREATE TABLE IF NOT EXISTS `contact_replies` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `message_id` INT NOT NULL,
    `sender_role` ENUM('user', 'admin') NOT NULL,
    `sender_name` VARCHAR(100) NOT NULL,
    `message` TEXT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (`message_id`),
    FOREIGN KEY (`message_id`) REFERENCES `contact_messages`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- SEED DATA (ข้อมูลเริ่มต้น)
-- =============================================

-- Seed Users: Admin & Customer with password '1234'
INSERT INTO `users` (`id`, `name`, `email`, `password`, `phone`, `role`, `points`) VALUES
(1, 'แอดมินระบบ SKRU', '674295003@parichat.skru.ac.th', '$2y$10$T85v056vIe484.c.hCcmXOu6Z3lQ0vW6v5Jz4l7Q/L3dZ8G/4z0b.', '089-999-9999', 'admin', 999),
(2, 'สมชาย สายปาร์ตี้', 'user@gmail.com', '$2y$10$T85v056vIe484.c.hCcmXOu6Z3lQ0vW6v5Jz4l7Q/L3dZ8G/4z0b.', '081-234-5678', 'user', 150)
ON DUPLICATE KEY UPDATE `email` = VALUES(`email`);

-- Seed Bars in Songkhla
INSERT INTO `bars` (`id`, `name`, `city`, `address`, `description`, `image`, `open_time`, `cutoff_time`, `close_time`, `promptpay_number`, `promptpay_name`, `phone`, `line_id`) VALUES
(1, 'Nockout Pub & Music Bar', 'เมืองสงขลา', 'ถ.ไทรบุรี ต.บ่อยาง อ.เมืองสงขลา จ.สงขลา', 'ผับสุดมันส์ใจกลางเมืองสงขลา ดนตรีสดคุณภาพ แสงสีเสียงจัดเต็ม อาหารและเครื่องดื่มครบครัน', 'assets/images/bars/nockout.jpg', '17:00:00', '20:00:00', '02:00:00', '0812345678', 'บจก. น็อคเอาท์ สงขลา', '074-321999', '@nockout_sk'),
(2, 'Fullmoon Club Songkhla', 'เมืองสงขลา', 'ถ.ชลาทัศน์ ต.บ่อยาง อ.เมืองสงขลา (เลียบหาดชลาทัศน์)', 'คลับพรีเมียมริมทะเลสงขลา ปาร์ตี้สุดเอ็กซ์คลูซีฟ DJ ระดับประเทศ และโซน VIP สุดหรู', 'assets/images/bars/fullmoon.jpg', '17:30:00', '20:00:00', '02:00:00', '0887654321', 'บจก. ฟูลมูน คลับ', '074-888777', '@fullmoon_sk')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- Seed Promotions
INSERT INTO `promotions` (`id`, `bar_id`, `title`, `description`, `price`, `points_reward`, `image`, `badge`, `is_active`) VALUES
(1, NULL, 'โปรเบียร์ช้าง 3 ขวด + น้ำแข็งถังใหญ่', 'เบียร์ช้างคลาสสิก 3 ขวด เย็นเจี๊ยบชื่นใจ พร้อมน้ำแข็งถังใหญ่ 1 ถัง เติมความสดชื่นตลอดคืน', 199.00, 20, 'assets/images/promotions/chang.jpg', 'PROMO 199.-', 1),
(2, NULL, 'โปรแสงโสม 1 ลิตร + มิกเซอร์ 4 ขวด + น้ำแข็งถังใหญ่', 'เหล้าแสงโสม 1 ลิตร เสิร์ฟพร้อมมิกเซอร์โค้กหรือสไปรท์ 4 ขวด และน้ำแข็งถังใหญ่ 1 ถัง สุดคุ้มสำหรับแก๊งเพื่อน', 599.00, 60, 'assets/images/promotions/sangsom.jpg', 'SUPER VALUE 599.-', 1)
ON DUPLICATE KEY UPDATE `title` = VALUES(`title`);

-- Seed Tables for Nockout Pub (Bar ID 1)
INSERT INTO `tables` (`bar_id`, `table_number`, `zone`, `capacity`, `status`, `pos_x`, `pos_y`) VALUES
(1, 'VIP-01', 'VIP โซนพิเศษ', 6, 'available', 1, 1),
(1, 'VIP-02', 'VIP โซนพิเศษ', 6, 'available', 2, 1),
(1, 'VIP-03', 'VIP โซนพิเศษ', 8, 'available', 3, 1),
(1, 'S-01', 'หน้าเวที (Stage Front)', 4, 'available', 1, 2),
(1, 'S-02', 'หน้าเวที (Stage Front)', 4, 'available', 2, 2),
(1, 'S-03', 'หน้าเวที (Stage Front)', 4, 'available', 3, 2),
(1, 'S-04', 'หน้าเวที (Stage Front)', 4, 'available', 4, 2),
(1, 'A-01', 'โซนในร้าน (Indoor Air)', 4, 'available', 1, 3),
(1, 'A-02', 'โซนในร้าน (Indoor Air)', 4, 'available', 2, 3),
(1, 'A-03', 'โซนในร้าน (Indoor Air)', 4, 'available', 3, 3),
(1, 'A-04', 'โซนในร้าน (Indoor Air)', 4, 'available', 4, 3),
(1, 'OUT-01', 'ระเบียงชิล (Outdoor)', 4, 'available', 1, 4),
(1, 'OUT-02', 'ระเบียงชิล (Outdoor)', 4, 'available', 2, 4),
(1, 'OUT-03', 'ระเบียงชิล (Outdoor)', 4, 'available', 3, 4);

-- Seed Tables for Fullmoon Club (Bar ID 2)
INSERT INTO `tables` (`bar_id`, `table_number`, `zone`, `capacity`, `status`, `pos_x`, `pos_y`) VALUES
(2, 'FM-VIP1', 'VIP Lounge', 8, 'available', 1, 1),
(2, 'FM-VIP2', 'VIP Lounge', 8, 'available', 2, 1),
(2, 'FM-VIP3', 'VIP Lounge', 10, 'available', 3, 1),
(2, 'FM-DJ1', 'หน้าดีเจ (DJ Dance Floor)', 4, 'available', 1, 2),
(2, 'FM-DJ2', 'หน้าดีเจ (DJ Dance Floor)', 4, 'available', 2, 2),
(2, 'FM-DJ3', 'หน้าดีเจ (DJ Dance Floor)', 4, 'available', 3, 2),
(2, 'FM-C1', 'โซนกลาง (Club Center)', 4, 'available', 1, 3),
(2, 'FM-C2', 'โซนกลาง (Club Center)', 4, 'available', 2, 3),
(2, 'FM-C3', 'โซนกลาง (Club Center)', 4, 'available', 3, 3),
(2, 'FM-BAR1', 'หน้าบาร์ (Cocktail Bar)', 2, 'available', 1, 4),
(2, 'FM-BAR2', 'หน้าบาร์ (Cocktail Bar)', 2, 'available', 2, 4);
