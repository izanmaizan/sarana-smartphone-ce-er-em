-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jul 06, 2025 at 03:35 AM
-- Server version: 8.0.30
-- PHP Version: 8.3.16

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `sarana_smartphone_crm`
--

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`, `created_at`) VALUES
(1, 'Smartphone', 'Handphone pintar', '2025-07-04 02:54:33'),
(2, 'Aksesoris', 'Aksesoris smartphone', '2025-07-04 02:54:33'),
(3, 'Sparepart', 'Suku cadang smartphone', '2025-07-04 02:54:33'),
(4, 'Flagship', 'Smartphone kelas premium', '2025-07-05 04:08:54'),
(5, 'Mid-Range', 'Smartphone menengah dengan performa optimal', '2025-07-05 04:08:54'),
(6, 'Budget', 'Smartphone terjangkau untuk kebutuhan dasar', '2025-07-05 04:08:54'),
(7, 'Gaming', 'Smartphone khusus gaming dengan performa tinggi', '2025-07-05 04:08:54');

-- --------------------------------------------------------

--
-- Table structure for table `chats`
--

CREATE TABLE `chats` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `message` text NOT NULL,
  `sender_type` enum('customer','admin') NOT NULL,
  `status` enum('unread','read') DEFAULT 'unread',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `chats`
--

INSERT INTO `chats` (`id`, `user_id`, `message`, `sender_type`, `status`, `created_at`) VALUES
(1, 2, 'halo', 'customer', 'read', '2025-07-05 05:05:02'),
(2, 2, 'Halo! Ada yang bisa saya bantu?', 'admin', 'read', '2025-07-05 05:06:13'),
(3, 2, 'test', 'customer', 'read', '2025-07-06 02:00:44'),
(4, 2, 'Mohon tunggu sebentar, saya cek dulu informasinya.', 'admin', 'read', '2025-07-06 02:01:13'),
(5, 2, 'Mohon tunggu sebentar, saya cek dulu informasinya.', 'admin', 'read', '2025-07-06 02:03:59'),
(6, 2, 'Mohon tunggu sebentar, saya cek dulu informasinya.', 'admin', 'read', '2025-07-06 02:04:09'),
(7, 2, 'Mohon tunggu sebentar, saya cek dulu informasinya.', 'admin', 'read', '2025-07-06 02:31:40'),
(8, 2, 'halo', 'customer', 'read', '2025-07-06 02:31:58'),
(9, 2, 'saya', 'customer', 'read', '2025-07-06 02:32:11');

-- --------------------------------------------------------

--
-- Table structure for table `discounts`
--

CREATE TABLE `discounts` (
  `id` int NOT NULL,
  `product_id` int DEFAULT NULL,
  `percentage` decimal(5,2) NOT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `usage_limit` int DEFAULT NULL,
  `used_count` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `discounts`
--

INSERT INTO `discounts` (`id`, `product_id`, `percentage`, `start_date`, `end_date`, `status`, `usage_limit`, `used_count`, `created_at`) VALUES
(1, 1, 10.00, '2025-07-01', '2025-07-31', 'active', NULL, 0, '2025-07-04 02:54:34'),
(2, 2, 15.00, '2025-07-01', '2025-07-31', 'active', NULL, 0, '2025-07-04 02:54:34'),
(3, 11, 2.00, '2025-07-05', '2025-07-06', 'active', NULL, 0, '2025-07-05 05:01:10');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `total_amount` decimal(12,2) NOT NULL,
  `status` enum('pending','confirmed','shipped','delivered','cancelled') DEFAULT 'pending',
  `payment_status` enum('pending','paid','failed') DEFAULT 'pending',
  `tracking_number` varchar(50) DEFAULT NULL,
  `order_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `total_amount`, `status`, `payment_status`, `tracking_number`, `order_date`) VALUES
(1, 2, 91000000.00, 'delivered', 'pending', NULL, '2025-07-06 03:08:14');

--
-- Triggers `orders`
--
DELIMITER $$
CREATE TRIGGER `restore_stock_on_cancel` AFTER UPDATE ON `orders` FOR EACH ROW BEGIN
    IF OLD.status != 'cancelled' AND NEW.status = 'cancelled' THEN
        UPDATE products p
        JOIN order_items oi ON p.id = oi.product_id
        SET p.stock = p.stock + oi.quantity
        WHERE oi.order_id = NEW.id;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int NOT NULL,
  `order_id` int DEFAULT NULL,
  `product_id` int DEFAULT NULL,
  `quantity` int NOT NULL,
  `price` decimal(12,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `price`) VALUES
(1, 1, 4, 1, 18000000.00),
(2, 1, 5, 4, 16000000.00),
(3, 1, 6, 1, 9000000.00);

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int NOT NULL,
  `category_id` int DEFAULT NULL,
  `unit_id` int DEFAULT NULL,
  `name` varchar(200) NOT NULL,
  `description` text,
  `price` decimal(12,2) NOT NULL,
  `stock` int DEFAULT '0',
  `weight` decimal(8,2) DEFAULT '0.00',
  `dimensions` varchar(50) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `category_id`, `unit_id`, `name`, `description`, `price`, `stock`, `weight`, `dimensions`, `image`, `status`, `created_at`) VALUES
(1, 1, 1, 'iPhone 15 Pro', 'iPhone terbaru dengan teknologi A17 Pro', 15000000.00, 10, 0.00, NULL, '1751769415_6869e147a2592.jpg', 'active', '2025-07-04 02:54:33'),
(2, 1, 1, 'Samsung Galaxy S24', 'Flagship Samsung dengan AI terdepan', 12000000.00, 15, 0.00, NULL, '1751769440_6869e160764e2.jpg', 'active', '2025-07-04 02:54:33'),
(3, 2, 2, 'Case iPhone 15', 'Case pelindung premium untuk iPhone 15', 250000.00, 50, 0.00, NULL, '1751769468_6869e17c1f3c2.jpg', 'active', '2025-07-04 02:54:33'),
(4, 1, 1, 'iPhone 15 Pro Max', 'iPhone terbaru dengan chip A17 Pro dan kamera 48MP', 18000000.00, 7, 221.00, NULL, '1751769195_6869e06b07b15.jpg', 'active', '2025-07-05 04:08:54'),
(5, 1, 1, 'Samsung Galaxy S24 Ultra', 'Flagship Samsung dengan S Pen dan kamera 200MP', 16000000.00, 8, 232.00, NULL, '1751769222_6869e08687a66.jpg', 'active', '2025-07-05 04:08:54'),
(6, 2, 1, 'Google Pixel 8', 'Smartphone Google dengan AI photography terbaik', 9000000.00, 14, 187.00, NULL, '1751769255_6869e0a7f2a11.jpg', 'active', '2025-07-05 04:08:54'),
(7, 2, 1, 'OnePlus 12', 'Smartphone flagship killer dengan fast charging', 8500000.00, 15, 220.00, NULL, '1751769285_6869e0c52af8c.jpg', 'active', '2025-07-05 04:08:54'),
(8, 3, 1, 'Xiaomi Redmi Note 13', 'Smartphone budget dengan performa mumpuni', 2500000.00, 25, 188.00, NULL, '1751769389_6869e12dad6fe.png', 'active', '2025-07-05 04:08:54'),
(11, 1, 1, 'Xiaomi 14 Ultra', 'Smartphone flagship Xiaomi dengan kamera Leica dan Snapdragon 8 Gen 3', 1499000.00, 18, 0.00, NULL, '1751691395_6868b083cae3f.png', 'active', '2025-07-05 04:56:35');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `product_id` int DEFAULT NULL,
  `rating` int DEFAULT NULL,
  `comment` text,
  `admin_reply` text,
  `admin_reply_date` timestamp NULL DEFAULT NULL,
  `replied_by` int DEFAULT NULL,
  `status` enum('approved','pending','rejected') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`id`, `user_id`, `product_id`, `rating`, `comment`, `admin_reply`, `admin_reply_date`, `replied_by`, `status`, `created_at`) VALUES
(1, 2, 1, 5, 'Produk rekomended', 'terimakasih', '2025-07-06 03:32:43', 1, 'approved', '2025-07-06 03:23:12');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `created_at`, `updated_at`) VALUES
(1, 'site_name', 'Sarana Smartphone', '2025-07-06 03:17:37', '2025-07-06 03:17:37'),
(2, 'site_description', 'Toko Smartphone Terpercaya', '2025-07-06 03:17:37', '2025-07-06 03:17:37'),
(3, 'contact_email', 'info@saranasmart.com', '2025-07-06 03:17:37', '2025-07-06 03:17:37'),
(4, 'contact_phone', '021-1234567', '2025-07-06 03:17:37', '2025-07-06 03:17:37'),
(5, 'address', 'Jalan Dokter Soetomo No. 78, Kota Padang, Sumatra Barat 25126, Padang, Indonesia 25126', '2025-07-06 03:17:37', '2025-07-06 03:32:16');

-- --------------------------------------------------------

--
-- Table structure for table `stock_in`
--

CREATE TABLE `stock_in` (
  `id` int NOT NULL,
  `product_id` int DEFAULT NULL,
  `quantity` int NOT NULL,
  `date` date DEFAULT NULL,
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `stock_in`
--

INSERT INTO `stock_in` (`id`, `product_id`, `quantity`, `date`, `notes`, `created_at`) VALUES
(1, 11, 2, '2025-07-05', 'Quick add stock', '2025-07-05 04:57:47'),
(2, 11, 2, '2025-07-05', 'Quick add stock', '2025-07-05 04:57:50'),
(3, 11, 2, '2025-07-05', 'Quick add stock', '2025-07-05 04:57:52'),
(4, 11, 2, '2025-07-05', 'Quick add stock', '2025-07-05 04:57:55'),
(5, 7, 5, '2025-07-06', '', '2025-07-06 02:40:36');

-- --------------------------------------------------------

--
-- Table structure for table `units`
--

CREATE TABLE `units` (
  `id` int NOT NULL,
  `name` varchar(50) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `units`
--

INSERT INTO `units` (`id`, `name`, `created_at`) VALUES
(1, 'Unit', '2025-07-04 02:54:33'),
(2, 'Pcs', '2025-07-04 02:54:33'),
(3, 'Set', '2025-07-04 02:54:33'),
(4, 'Box', '2025-07-05 04:08:54'),
(5, 'Pack', '2025-07-05 04:08:54'),
(6, 'Bundle', '2025-07-05 04:08:54');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text,
  `birth_date` date DEFAULT NULL,
  `gender` enum('male','female') DEFAULT NULL,
  `last_login` timestamp NULL DEFAULT NULL,
  `role` enum('admin','customer') DEFAULT 'customer',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `phone`, `address`, `birth_date`, `gender`, `last_login`, `role`, `created_at`) VALUES
(1, 'Admin', 'admin@sarana.com', '0192023a7bbd73250516f069df18b500', NULL, NULL, NULL, NULL, NULL, 'admin', '2025-07-04 02:54:33'),
(2, 'Customer Demo', 'customer@demo.com', '62cc2d8b4bf2d8728120d052163a77df', NULL, NULL, NULL, NULL, NULL, 'customer', '2025-07-04 02:54:33');

--
-- Triggers `users`
--
DELIMITER $$
CREATE TRIGGER `log_user_login` AFTER UPDATE ON `users` FOR EACH ROW BEGIN
    IF OLD.last_login != NEW.last_login THEN
        INSERT INTO activity_logs (user_id, action, details, created_at)
        VALUES (NEW.id, 'user_login', CONCAT('User logged in from IP: ', @user_ip), NOW());
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_customer_analytics`
-- (See below for the actual view)
--
CREATE TABLE `v_customer_analytics` (
`completed_orders` bigint
,`created_at` timestamp
,`customer_type` varchar(7)
,`email` varchar(100)
,`id` int
,`last_order_date` timestamp
,`name` varchar(100)
,`total_orders` bigint
,`total_reviews` bigint
,`total_spent` decimal(34,2)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_product_analytics`
-- (See below for the actual view)
--
CREATE TABLE `v_product_analytics` (
`avg_rating` decimal(14,4)
,`category_name` varchar(100)
,`id` int
,`name` varchar(200)
,`price` decimal(12,2)
,`review_count` bigint
,`status` enum('active','inactive')
,`stock` int
,`total_revenue` decimal(44,2)
,`total_sold` decimal(32,0)
);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `chats`
--
ALTER TABLE `chats`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_status` (`user_id`,`status`),
  ADD KEY `idx_sender_status` (`sender_type`,`status`);

--
-- Indexes for table `discounts`
--
ALTER TABLE `discounts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status_date` (`status`,`order_date`),
  ADD KEY `idx_user_date` (`user_id`,`order_date`),
  ADD KEY `idx_tracking` (`tracking_number`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `unit_id` (`unit_id`),
  ADD KEY `idx_status_stock` (`status`,`stock`),
  ADD KEY `idx_category_status` (`category_id`,`status`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_product_status` (`product_id`,`status`),
  ADD KEY `idx_status_created` (`status`,`created_at`),
  ADD KEY `replied_by` (`replied_by`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `stock_in`
--
ALTER TABLE `stock_in`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `units`
--
ALTER TABLE `units`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `chats`
--
ALTER TABLE `chats`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `discounts`
--
ALTER TABLE `discounts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `stock_in`
--
ALTER TABLE `stock_in`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `units`
--
ALTER TABLE `units`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

-- --------------------------------------------------------

--
-- Structure for view `v_customer_analytics`
--
DROP TABLE IF EXISTS `v_customer_analytics`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_customer_analytics`  AS SELECT `u`.`id` AS `id`, `u`.`name` AS `name`, `u`.`email` AS `email`, `u`.`created_at` AS `created_at`, count(distinct `o`.`id`) AS `total_orders`, coalesce(sum((case when (`o`.`status` <> 'cancelled') then `o`.`total_amount` end)),0) AS `total_spent`, count(distinct (case when (`o`.`status` = 'delivered') then `o`.`id` end)) AS `completed_orders`, count(distinct `r`.`id`) AS `total_reviews`, max(`o`.`order_date`) AS `last_order_date`, (case when (coalesce(sum((case when (`o`.`status` <> 'cancelled') then `o`.`total_amount` end)),0) >= 10000000) then 'VIP' when (count(distinct `o`.`id`) >= 5) then 'Loyal' when (`u`.`created_at` >= (now() - interval 7 day)) then 'New' else 'Regular' end) AS `customer_type` FROM ((`users` `u` left join `orders` `o` on((`u`.`id` = `o`.`user_id`))) left join `reviews` `r` on((`u`.`id` = `r`.`user_id`))) WHERE (`u`.`role` = 'customer') GROUP BY `u`.`id` ;

-- --------------------------------------------------------

--
-- Structure for view `v_product_analytics`
--
DROP TABLE IF EXISTS `v_product_analytics`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_product_analytics`  AS SELECT `p`.`id` AS `id`, `p`.`name` AS `name`, `p`.`price` AS `price`, `p`.`stock` AS `stock`, `p`.`status` AS `status`, `c`.`name` AS `category_name`, coalesce(sum(`oi`.`quantity`),0) AS `total_sold`, coalesce(avg(`r`.`rating`),0) AS `avg_rating`, count(distinct `r`.`id`) AS `review_count`, coalesce(sum((`oi`.`quantity` * `oi`.`price`)),0) AS `total_revenue` FROM ((((`products` `p` left join `categories` `c` on((`p`.`category_id` = `c`.`id`))) left join `order_items` `oi` on((`p`.`id` = `oi`.`product_id`))) left join `orders` `o` on(((`oi`.`order_id` = `o`.`id`) and (`o`.`status` = 'delivered')))) left join `reviews` `r` on(((`p`.`id` = `r`.`product_id`) and (`r`.`status` = 'approved')))) GROUP BY `p`.`id` ;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `chats`
--
ALTER TABLE `chats`
  ADD CONSTRAINT `chats_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `discounts`
--
ALTER TABLE `discounts`
  ADD CONSTRAINT `discounts_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`),
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`),
  ADD CONSTRAINT `products_ibfk_2` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`);

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  ADD CONSTRAINT `reviews_ibfk_3` FOREIGN KEY (`replied_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `stock_in`
--
ALTER TABLE `stock_in`
  ADD CONSTRAINT `stock_in_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
