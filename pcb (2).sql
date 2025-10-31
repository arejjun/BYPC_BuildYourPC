-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 20, 2025 at 10:54 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `pcb`
--

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `shop_id` int(11) NOT NULL,
  `delivery_address` text DEFAULT NULL,
  `status` enum('pending','confirmed','shipped','delivered','cancelled') DEFAULT 'pending',
  `total_amount` decimal(10,2) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`order_id`, `customer_id`, `shop_id`, `delivery_address`, `status`, `total_amount`, `created_at`) VALUES
(4, 10, 7, 'Kottayam, Pincode: 686001, Phone: 08921018256', 'pending', 442421.00, '2025-09-18 19:12:11'),
(5, 12, 6, 'Kottayam, Pincode: 686001, Phone: 08921018256', 'delivered', 900.00, '2025-09-18 19:24:11'),
(6, 4, 8, 'Kottayam, Pincode: 686001, Phone: 08921018256', 'pending', 2299.00, '2025-09-19 16:52:48');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `order_item_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `product_id` int(11) NOT NULL,
  `shop_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `brand` varchar(100) DEFAULT NULL,
  `category` enum('CPU','GPU','Motherboard','RAM','Storage','PSU','Cabinet','Cooling','Monitor','Peripherals') NOT NULL,
  `stock` int(11) DEFAULT 0,
  `availability` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`product_id`, `shop_id`, `name`, `description`, `price`, `brand`, `category`, `stock`, `availability`, `created_at`) VALUES
(24, 7, 'gsync', 'rerewrer', 442421.00, 'eee', 'CPU', 232, 1, '2025-09-18 19:10:48'),
(25, 6, 'rtx', 'jnjk', 900.00, 'jn', 'CPU', 87, 1, '2025-09-18 19:23:36'),
(26, 6, 'rtx', 'jnjk', 900.00, 'jn', 'CPU', 87, 1, '2025-09-18 19:24:39'),
(27, 8, 'new', 'qwertyu', 1232.00, 'kk', 'CPU', 9, 1, '2025-09-19 08:48:27'),
(28, 8, 'new', 'qwertyu', 1232.00, 'kk', 'CPU', 9, 1, '2025-09-19 09:03:45'),
(29, 8, 'rog strix', 'nn', 52000.00, 'Asus', 'GPU', 121, 1, '2025-09-19 09:04:42'),
(30, 8, 'ZEBRONICS Iceberg Gaming Chassis, mATX/Mini ITX, Tempered Glass, 5 Fans Mid tower Cabinet with USB 2', 'Choose the ZEB-Iceberg chassis designed to elevate your gaming experience to the next level. Featuring front and side wraparound tempered glass panels, it offers a stunning showcase for your hardware. With 120mm multi-color LED ring fans and 5 installed, the ZEB-Iceberg ensures mesmerizing visuals and optimal cooling performance. Its heavy-duty structure guarantees durability and stability, while the optimized airflow design enhances cooling efficiency. The bottom PSU placement maximizes space and cable management while including 1 x USB 3.0 and 2 x USB ports ensures convenient connectivity. Compatible with mATX/Mini ITX motherboards, and topped with a magnetic dust filter, the ZEB-Iceberg sets a new standard for gaming chassis.\r\n', 2299.00, 'Zebronics', 'Cabinet', 12, 1, '2025-09-19 09:56:34');

-- --------------------------------------------------------

--
-- Table structure for table `product_images`
--

CREATE TABLE `product_images` (
  `image_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_images`
--

INSERT INTO `product_images` (`image_id`, `product_id`, `image_path`) VALUES
(43, 24, '1758202848_68cc0be0b2b93.jpg'),
(44, 24, '1758202848_68cc0be0b38e0.jpg'),
(45, 25, '1758203616_68cc0ee03ff3c.jpeg'),
(46, 25, '1758203616_68cc0ee041bb9.jpg'),
(47, 25, '1758203616_68cc0ee042956.jpg'),
(48, 26, '1758203679_68cc0f1f48996.jpeg'),
(49, 26, '1758203679_68cc0f1f49c72.jpg'),
(50, 26, '1758203679_68cc0f1f4ac84.jpg'),
(51, 27, '1758251907_68cccb83db111.jpg'),
(52, 27, '1758251907_68cccb83db92a.jpg'),
(53, 27, '1758251907_68cccb83dc00c.jpg'),
(54, 28, '1758252825_68cccf195ecbe.jpg'),
(55, 28, '1758252825_68cccf195f2cc.jpg'),
(56, 28, '1758252825_68cccf195f7fc.jpg'),
(57, 29, '1758252882_68cccf5204061.jpg'),
(58, 30, '1758255994_68ccdb7a8522c.png'),
(59, 30, '1758255994_68ccdb7a8633c.png');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `review_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `rating` int(11) DEFAULT NULL CHECK (`rating` between 1 and 5),
  `comment` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `review_images`
--

CREATE TABLE `review_images` (
  `review_image_id` int(11) NOT NULL,
  `review_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `search_history`
--

CREATE TABLE `search_history` (
  `search_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `search_term` varchar(100) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `shop_id` int(11) DEFAULT NULL,
  `searched_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `shops`
--

CREATE TABLE `shops` (
  `shop_id` int(11) NOT NULL,
  `owner_id` int(11) NOT NULL,
  `shop_name` varchar(100) NOT NULL,
  `phone_number` varchar(15) NOT NULL,
  `address_line1` varchar(255) NOT NULL,
  `address_line2` varchar(255) DEFAULT NULL,
  `district` varchar(50) NOT NULL,
  `status` enum('pending','approved','rejected','banned') NOT NULL DEFAULT 'pending',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `shops`
--

INSERT INTO `shops` (`shop_id`, `owner_id`, `shop_name`, `phone_number`, `address_line1`, `address_line2`, `district`, `status`, `created_at`) VALUES
(6, 12, 'wq', '3131232333', 'esfefefefe', 'gggrgrggrg', 'idukki', 'approved', '2025-09-18 18:49:44'),
(7, 14, 'leo', '121212121', 'sccascasc', 'cdcd', 'thrissur', 'approved', '2025-09-18 19:02:08'),
(8, 16, 'rr', '555555555', 'gyvgyvgyv', '', 'idukki', 'approved', '2025-09-19 08:47:27'),
(9, 17, 'Space PC ', '78945123', 'Kottayam', 'ktm', 'kottayam', 'pending', '2025-09-19 10:18:22'),
(10, 18, 'Arjun P Anil', '08921018256', 'Kottayam', '', 'kottayam', 'pending', '2025-09-19 16:24:51');

-- --------------------------------------------------------

--
-- Table structure for table `suggested_shops`
--

CREATE TABLE `suggested_shops` (
  `suggestion_id` int(11) NOT NULL,
  `shop_name` varchar(150) NOT NULL,
  `address_line1` varchar(255) NOT NULL,
  `address_line2` varchar(255) DEFAULT NULL,
  `district` varchar(100) NOT NULL,
  `pincode` varchar(10) NOT NULL,
  `phone_number` varchar(15) NOT NULL,
  `email` varchar(100) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone_number` varchar(15) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','shop_owner','customer') NOT NULL,
  `user_status` enum('active','banned') NOT NULL DEFAULT 'active',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `name`, `email`, `phone_number`, `password`, `role`, `user_status`, `created_at`) VALUES
(1, 'abc', 'abc@gmail.com', '1111111111', '$2y$10$w1v5ZzgVdixW2BuFQt/4auzfLgoc2AnS7BrKIMWMtR9E4ZZPYFQEm', 'customer', 'active', '2025-09-16 17:02:18'),
(2, 'owner', 'owner@gmail.com', '2222222222', '$2y$10$ld6C.QF/TWHaEszES6SxGOLc3fFsOiYwOFHyyPD2ldDBdqZpovrNS', 'shop_owner', 'active', '2025-09-16 17:22:19'),
(4, 'aradmin', 'aradmin@gmail.com', '8921018256', '$2y$10$41rpWopJssV7gROhiN.WHeGgUGrmWSTlIsq6Sx2RB0cnfgZMqBXG6', 'admin', 'active', '2025-09-16 17:51:47'),
(5, 'kr pc ', 'kr@gmail.com', '1234567899', '$2y$10$QjAI3Ot0G7nYTXRVQDcD5udPtLSsI2H48RBm0eBDredpXPR.JGWr2', 'shop_owner', 'active', '2025-09-16 18:22:43'),
(6, 'are', 'are@gmail.com', '1234567890', '$2y$10$LRsF5CloaxC1F5wta.GedunypDAJMoBtR5zc7.W8F91L8hpER9Zdi', 'customer', 'active', '2025-09-17 12:22:43'),
(7, 'ww', 'ww@gmail.com', '1234567891', '$2y$10$FsMsa5RBYs7UmHuunDQ29eIWIkFb76fLGsNb9MqAfXb/zjebHGMc.', 'customer', 'active', '2025-09-17 12:29:53'),
(8, 'ww shop', 'wwshop@gmail.com', '1234567892', '$2y$10$WNQNagLTnzqzta3eroC.r.foFvj2WZd4rsAae6udoS4x2HRQqoTFm', 'shop_owner', 'active', '2025-09-17 12:41:38'),
(9, 'qq', 'qq@gmail.com', '0000000000', '$2y$10$oMf6NngOSfqjr0OWAw9f.eZtRAN.4CveO1tTYE73PFgxdP5J5NnNG', 'shop_owner', 'active', '2025-09-17 18:17:44'),
(10, 'Arjun P Anil', 'arjunpanil30@gmail.com', '0892101825', '$2y$10$oMSw0zKfmwEs.xD.btlrOeKIrsyxcAq7UHga5igXIw1E3m.e6UU/K', 'customer', 'active', '2025-09-18 08:33:05'),
(12, 'wwe', 'wwe@gmail.com', '1515151551', '$2y$10$bFiuGY06guChq0TShzAFGOfU4p.WfHUZwoOgRRY5lHNvdaR70s1a6', 'shop_owner', 'active', '2025-09-18 09:38:44'),
(13, 'abcde', 'abcde@gmail.com', '1234567122', '$2y$10$i.qU5RSC8EzhJZW2XW9kp.EGJyEJALJDiolxJrf5wxiJe6Klt18uC', 'shop_owner', 'active', '2025-09-18 09:41:41'),
(14, 'leo', 'leo@gmail.com', '1234569877', '$2y$10$jdOzKN9omylnMhmWfrkopeasjzafNIWoko25bqxeKYQ0.Rn/z9NCK', 'shop_owner', 'active', '2025-09-18 19:01:37'),
(15, 'GeekBoz PCs', 'geekboz@gmail.com', '1236547898', '$2y$10$asLLLRxrk1ipXTOJ6pZomOvSV/nfEiDx574rGWT5z1pLkKj.0GbGi', 'shop_owner', 'active', '2025-09-18 23:01:42'),
(16, 'rr', 'rr@gmail.com', '1236547894', '$2y$10$Q825onmgj916lAlvU0THMOLUrM25SSKzAjXTXyCf9WD0jMgJ74t6y', 'shop_owner', 'active', '2025-09-19 08:46:56'),
(17, 'Arun', 'arun@gmail.com', '1234567898', '$2y$10$/fJRtgGUIwMEL95hWl1n2elX/rHK3MFf.OcGk.qanDemeQ0sHcPD2', 'shop_owner', 'active', '2025-09-19 10:17:39'),
(18, 'pp', 'pp@gmail.com', '1236547895', '$2y$10$fBJC9VgSHXcW53WXYxed0usN5yFqeSzLdecVm.5fgwVkweBb/Bwnu', 'shop_owner', 'active', '2025-09-19 16:24:26');

-- --------------------------------------------------------

--
-- Table structure for table `user_favorites`
--

CREATE TABLE `user_favorites` (
  `favorite_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `shop_id` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `shop_id` (`shop_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`order_item_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`product_id`),
  ADD KEY `shop_id` (`shop_id`);

--
-- Indexes for table `product_images`
--
ALTER TABLE `product_images`
  ADD PRIMARY KEY (`image_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`review_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `review_images`
--
ALTER TABLE `review_images`
  ADD PRIMARY KEY (`review_image_id`),
  ADD KEY `review_id` (`review_id`);

--
-- Indexes for table `search_history`
--
ALTER TABLE `search_history`
  ADD PRIMARY KEY (`search_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `shop_id` (`shop_id`);

--
-- Indexes for table `shops`
--
ALTER TABLE `shops`
  ADD PRIMARY KEY (`shop_id`),
  ADD KEY `owner_id` (`owner_id`);

--
-- Indexes for table `suggested_shops`
--
ALTER TABLE `suggested_shops`
  ADD PRIMARY KEY (`suggestion_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `phone_number` (`phone_number`);

--
-- Indexes for table `user_favorites`
--
ALTER TABLE `user_favorites`
  ADD PRIMARY KEY (`favorite_id`),
  ADD UNIQUE KEY `uniq_user_product` (`user_id`,`product_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `shop_id` (`shop_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `order_item_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `product_images`
--
ALTER TABLE `product_images`
  MODIFY `image_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=60;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `review_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `review_images`
--
ALTER TABLE `review_images`
  MODIFY `review_image_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `search_history`
--
ALTER TABLE `search_history`
  MODIFY `search_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `shops`
--
ALTER TABLE `shops`
  MODIFY `shop_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `suggested_shops`
--
ALTER TABLE `suggested_shops`
  MODIFY `suggestion_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `user_favorites`
--
ALTER TABLE `user_favorites`
  MODIFY `favorite_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`shop_id`) ON DELETE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`shop_id`) ON DELETE CASCADE;

--
-- Constraints for table `product_images`
--
ALTER TABLE `product_images`
  ADD CONSTRAINT `product_images_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `review_images`
--
ALTER TABLE `review_images`
  ADD CONSTRAINT `review_images_ibfk_1` FOREIGN KEY (`review_id`) REFERENCES `reviews` (`review_id`) ON DELETE CASCADE;

--
-- Constraints for table `search_history`
--
ALTER TABLE `search_history`
  ADD CONSTRAINT `search_history_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `search_history_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `search_history_ibfk_3` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`shop_id`) ON DELETE SET NULL;

--
-- Constraints for table `shops`
--
ALTER TABLE `shops`
  ADD CONSTRAINT `shops_ibfk_1` FOREIGN KEY (`owner_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `user_favorites`
--
ALTER TABLE `user_favorites`
  ADD CONSTRAINT `user_favorites_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_favorites_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_favorites_ibfk_3` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`shop_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
