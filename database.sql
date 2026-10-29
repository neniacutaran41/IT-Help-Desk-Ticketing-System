-- ========================================================
-- IT Help Desk Ticketing System Database Schema & Seed Data
-- Compatible with Hostinger Shared Hosting & Localhost (XAMPP)
-- ========================================================

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `ticket_history`;
DROP TABLE IF EXISTS `ticket_replies`;
DROP TABLE IF EXISTS `tickets`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `users`;
SET FOREIGN_KEY_CHECKS = 1;

-- --------------------------------------------------------
-- Table structure for `users`
-- --------------------------------------------------------
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('admin', 'it_staff', 'employee') NOT NULL DEFAULT 'employee',
  `department` VARCHAR(100) DEFAULT NULL,
  `phone` VARCHAR(30) DEFAULT NULL,
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `categories`
-- --------------------------------------------------------
CREATE TABLE `categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `description` VARCHAR(255) DEFAULT NULL,
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `tickets`
-- --------------------------------------------------------
CREATE TABLE `tickets` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `ticket_number` VARCHAR(30) NOT NULL UNIQUE,
  `user_id` INT NOT NULL,
  `category_id` INT DEFAULT NULL,
  `assigned_to` INT DEFAULT NULL,
  `title` VARCHAR(200) NOT NULL,
  `description` TEXT NOT NULL,
  `priority` ENUM('low', 'medium', 'high', 'urgent') NOT NULL DEFAULT 'medium',
  `status` ENUM('open', 'assigned', 'in_progress', 'resolved', 'closed') NOT NULL DEFAULT 'open',
  `attachment` VARCHAR(255) DEFAULT NULL,
  `attachment_original_name` VARCHAR(255) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `resolved_at` DATETIME DEFAULT NULL,
  `closed_at` DATETIME DEFAULT NULL,
  CONSTRAINT `fk_tickets_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tickets_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_tickets_assigned` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `ticket_replies`
-- --------------------------------------------------------
CREATE TABLE `ticket_replies` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `ticket_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `message` TEXT NOT NULL,
  `is_internal` TINYINT(1) NOT NULL DEFAULT 0,
  `attachment` VARCHAR(255) DEFAULT NULL,
  `attachment_original_name` VARCHAR(255) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_replies_ticket` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_replies_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `ticket_history`
-- --------------------------------------------------------
CREATE TABLE `ticket_history` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `ticket_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `action` VARCHAR(100) NOT NULL,
  `old_value` VARCHAR(150) DEFAULT NULL,
  `new_value` VARCHAR(150) DEFAULT NULL,
  `comment` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_history_ticket` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_history_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Sample Data: Users
-- Passwords:
-- admin@helpdesk.com   -> admin123
-- support@helpdesk.com -> staff123
-- sarah.tech@helpdesk.com -> staff123
-- employee@helpdesk.com -> user123
-- jane.smith@company.com -> user123
-- --------------------------------------------------------
INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `department`, `phone`, `status`, `created_at`) VALUES
(1, 'System Administrator', 'admin@helpdesk.com', '$2y$10$sbWu4buqOnW2GdapDtSut.FE1U9XjteRHjwoyo6Ws3ZCjZBlK/sl.', 'admin', 'IT Infrastructure', '+1 555-0100', 'active', NOW()),
(2, 'Alex Rivera', 'support@helpdesk.com', '$2y$10$77LCFc.yXtNUrHt.yqb/UOWBawBrYcoFtT3EV1j651zbCk4KKU1g2', 'it_staff', 'Technical Support', '+1 555-0101', 'active', NOW()),
(3, 'Sarah Connor', 'sarah.tech@helpdesk.com', '$2y$10$77LCFc.yXtNUrHt.yqb/UOWBawBrYcoFtT3EV1j651zbCk4KKU1g2', 'it_staff', 'Network Operations', '+1 555-0102', 'active', NOW()),
(4, 'John Doe', 'employee@helpdesk.com', '$2y$10$n1AwZVH622fkWJH1qAN57./GIrlpd4ZKq0nX2F1d2LOnxAUPwurLm', 'employee', 'Marketing', '+1 555-0201', 'active', NOW()),
(5, 'Jane Smith', 'jane.smith@company.com', '$2y$10$n1AwZVH622fkWJH1qAN57./GIrlpd4ZKq0nX2F1d2LOnxAUPwurLm', 'employee', 'Human Resources', '+1 555-0202', 'active', NOW());

-- --------------------------------------------------------
-- Sample Data: Categories
-- --------------------------------------------------------
INSERT INTO `categories` (`id`, `name`, `description`, `status`) VALUES
(1, 'Hardware & Peripherals', 'Issues with desktop computers, laptops, monitors, keyboards, or mice', 'active'),
(2, 'Software & Applications', 'Issues with business applications, OS, Microsoft Office, or ERP tools', 'active'),
(3, 'Network & Internet', 'Problems with Wi-Fi, Ethernet, VPN connection, or internet latency', 'active'),
(4, 'Email & Accounts', 'Password resets, email delivery issues, new user access, or 2FA problems', 'active'),
(5, 'Printing & Scanning', 'Office printer offline, paper jams, print queue errors, or scanner issues', 'active'),
(6, 'Other Technical Issues', 'General technical requests and inquiries not categorized above', 'active');

-- --------------------------------------------------------
-- Sample Data: Tickets
-- --------------------------------------------------------
INSERT INTO `tickets` (`id`, `ticket_number`, `user_id`, `category_id`, `assigned_to`, `title`, `description`, `priority`, `status`, `created_at`, `updated_at`, `resolved_at`, `closed_at`) VALUES
(1, 'TCK-2026-0001', 4, 3, 2, 'Cannot connect to company VPN from home', 'I have been trying to log in to the GlobalProtect VPN since this morning, but it keeps giving me a "Connection Timeout - Server Unreachable" error. I need this to access the shared drive.', 'high', 'in_progress', DATE_SUB(NOW(), INTERVAL 2 DAY), NOW(), NULL, NULL),
(2, 'TCK-2026-0002', 5, 4, 1, 'Password reset needed for HR Portal', 'My HR portal account got locked after 3 incorrect attempts. Please help unlock and reset the temporary password for my corporate ID.', 'medium', 'resolved', DATE_SUB(NOW(), INTERVAL 3 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY), NULL),
(3, 'TCK-2026-0003', 4, 1, NULL, 'Second monitor flickering and turning black', 'My secondary Dell monitor keeps going pitch black every 15 minutes. Swapped HDMI cable but problem persists. May be a loose port or graphics driver issue.', 'medium', 'open', DATE_SUB(NOW(), INTERVAL 4 HOUR), NOW(), NULL, NULL),
(4, 'TCK-2026-0004', 5, 5, 3, 'Floor 3 Network Printer offline', 'The HP LaserJet printer on the 3rd floor marketing wing displays error 79. Service error. Multiple colleagues are unable to print quarterly reports.', 'urgent', 'assigned', DATE_SUB(NOW(), INTERVAL 1 DAY), NOW(), NULL, NULL),
(5, 'TCK-2026-0005', 4, 2, 2, 'Adobe Acrobat Pro license expired', 'My Adobe Creative Cloud license says subscription ended. Need it renewed for client presentation design materials.', 'low', 'closed', DATE_SUB(NOW(), INTERVAL 5 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY));

-- --------------------------------------------------------
-- Sample Data: Ticket Replies & Notes
-- --------------------------------------------------------
INSERT INTO `ticket_replies` (`id`, `ticket_id`, `user_id`, `message`, `is_internal`, `created_at`) VALUES
(1, 1, 2, 'Hello John, could you please verify if your home router DNS is reachable? We have dispatched an update to your gateway profile.', 0, DATE_SUB(NOW(), INTERVAL 36 HOUR)),
(2, 1, 4, 'Thanks Alex! I restarted the router, but still get the timeout. Attached screenshot of the exact error dialog.', 0, DATE_SUB(NOW(), INTERVAL 30 HOUR)),
(3, 1, 2, 'Internal note: VPN gateway 2 had a failover event yesterday. Checking RADIUS auth logs for user john.doe.', 1, DATE_SUB(NOW(), INTERVAL 28 HOUR)),
(4, 2, 1, 'Your password has been reset. A temporary link has been sent to your registered backup email.', 0, DATE_SUB(NOW(), INTERVAL 25 HOUR)),
(5, 2, 5, 'Thank you! Successfully logged in and updated my credentials.', 0, DATE_SUB(NOW(), INTERVAL 24 HOUR));

-- --------------------------------------------------------
-- Sample Data: Ticket History
-- --------------------------------------------------------
INSERT INTO `ticket_history` (`id`, `ticket_id`, `user_id`, `action`, `old_value`, `new_value`, `comment`, `created_at`) VALUES
(1, 1, 4, 'ticket_created', NULL, 'open', 'Ticket submitted by John Doe', DATE_SUB(NOW(), INTERVAL 2 DAY)),
(2, 1, 1, 'assigned', 'Unassigned', 'Alex Rivera', 'Assigned by Administrator', DATE_SUB(NOW(), INTERVAL 40 HOUR)),
(3, 1, 2, 'status_changed', 'assigned', 'in_progress', 'Staff began investigation', DATE_SUB(NOW(), INTERVAL 38 HOUR)),
(4, 2, 5, 'ticket_created', NULL, 'open', 'Ticket submitted by Jane Smith', DATE_SUB(NOW(), INTERVAL 3 DAY)),
(5, 2, 1, 'assigned', 'Unassigned', 'System Administrator', 'Assigned by Administrator', DATE_SUB(NOW(), INTERVAL 26 HOUR)),
(6, 2, 1, 'status_changed', 'assigned', 'resolved', 'Account unlocked and verified', DATE_SUB(NOW(), INTERVAL 25 HOUR)),
(7, 3, 4, 'ticket_created', NULL, 'open', 'Ticket submitted by John Doe', DATE_SUB(NOW(), INTERVAL 4 HOUR)),
(8, 4, 5, 'ticket_created', NULL, 'open', 'Ticket submitted by Jane Smith', DATE_SUB(NOW(), INTERVAL 1 DAY)),
(9, 4, 1, 'assigned', 'Unassigned', 'Sarah Connor', 'Assigned to Network Operations', DATE_SUB(NOW(), INTERVAL 20 HOUR));
