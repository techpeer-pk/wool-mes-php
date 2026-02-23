-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 15, 2025 at 02:01 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `wool_production_mes`
--

-- --------------------------------------------------------

--
-- Table structure for table `alerts`
--

CREATE TABLE `alerts` (
  `alert_id` int(11) NOT NULL,
  `batch_id` int(11) NOT NULL,
  `alert_type` enum('Delay','Weight Loss','Quality Issue','Other') NOT NULL,
  `severity` enum('Low','Medium','High','Critical') DEFAULT 'Medium',
  `message` text NOT NULL,
  `is_resolved` tinyint(1) DEFAULT 0,
  `resolved_at` datetime DEFAULT NULL,
  `resolved_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `batches`
--

CREATE TABLE `batches` (
  `batch_id` int(11) NOT NULL,
  `batch_number` varchar(20) NOT NULL,
  `initial_weight` decimal(10,2) NOT NULL,
  `current_weight` decimal(10,2) NOT NULL,
  `current_stage_id` int(11) NOT NULL,
  `status` enum('In Progress','Completed','On Hold','Cancelled') DEFAULT 'In Progress',
  `start_date` date NOT NULL,
  `expected_completion_date` date DEFAULT NULL,
  `actual_completion_date` date DEFAULT NULL,
  `source_supplier` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `batches`
--

INSERT INTO `batches` (`batch_id`, `batch_number`, `initial_weight`, `current_weight`, `current_stage_id`, `status`, `start_date`, `expected_completion_date`, `actual_completion_date`, `source_supplier`, `notes`, `created_at`, `updated_at`, `created_by`) VALUES
(1, 'WB-2024-001', 1000.00, 998.26, 3, 'In Progress', '2024-12-01', '2024-12-24', NULL, 'Green Valley Farm', NULL, '2025-12-15 06:45:33', '2025-12-15 09:09:18', 1),
(2, 'WB-2025-001', 100000.00, 98888.90, 3, 'In Progress', '2025-12-15', '2026-01-14', NULL, 'Test Vendor', 'This is test entry to check demo Batch by: AutoBot', '2025-12-15 07:50:08', '2025-12-15 12:02:36', 1),
(3, 'WB-2025-002', 2500.00, 2500.00, 1, 'In Progress', '2025-12-15', '2026-01-07', NULL, 'Test Vendor', 'Test', '2025-12-15 07:57:28', '2025-12-15 07:57:28', 1),
(4, 'WB-2025-003', 1799.00, 1799.00, 1, 'In Progress', '2025-12-15', '2026-01-07', NULL, 'Classic Suppliers ', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Morbi rhoncus vitae nisi ut volutpat. Quisque vitae porttitor sem. Proin et faucibus leo. Nunc accumsan fermentum nunc sed congue. Vivamus eu consectetur nibh.', '2025-12-15 12:32:08', '2025-12-15 12:33:28', 2),
(5, 'WB-2025-004', 3599.00, 3599.00, 1, 'In Progress', '2025-12-15', '2026-01-02', NULL, 'Zahoor Traders (Pvt.) Ltd.', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Morbi rhoncus vitae nisi ut volutpat. Quisque vitae porttitor sem. Proin et faucibus leo. Nunc accumsan fermentum nunc sed congue. Vivamus eu consectetur nibh.', '2025-12-15 12:35:26', '2025-12-15 12:35:26', 2),
(6, 'WB-2025-005', 500.00, 450.00, 10, 'Completed', '2025-12-15', '2025-12-22', '2025-12-15', 'Cotton N Cotten', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Morbi rhoncus vitae nisi ut volutpat. Quisque vitae porttitor sem. Proin et faucibus leo. Nunc accumsan fermentum nunc sed congue. Vivamus eu consectetur nibh.', '2025-12-15 12:36:10', '2025-12-15 12:57:36', 2);

-- --------------------------------------------------------

--
-- Table structure for table `batch_stage_history`
--

CREATE TABLE `batch_stage_history` (
  `history_id` int(11) NOT NULL,
  `batch_id` int(11) NOT NULL,
  `stage_id` int(11) NOT NULL,
  `vendor_id` int(11) DEFAULT NULL,
  `weight_in` decimal(10,2) DEFAULT NULL,
  `weight_out` decimal(10,2) DEFAULT NULL,
  `weight_loss` decimal(10,2) DEFAULT NULL,
  `start_date` datetime NOT NULL,
  `end_date` datetime DEFAULT NULL,
  `duration_hours` int(11) DEFAULT NULL,
  `status` enum('Pending','In Progress','Completed','Failed') DEFAULT 'In Progress',
  `notes` text DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `batch_stage_history`
--

INSERT INTO `batch_stage_history` (`history_id`, `batch_id`, `stage_id`, `vendor_id`, `weight_in`, `weight_out`, `weight_loss`, `start_date`, `end_date`, `duration_hours`, `status`, `notes`, `updated_by`, `created_at`) VALUES
(1, 1, 1, 1, 1000.00, 999.00, 1.00, '2024-12-01 08:00:00', '2025-12-15 12:53:43', 9101, 'Completed', 'Test Note', 1, '2025-12-15 06:45:33'),
(2, 2, 1, 1, 100000.00, 30000.00, 70000.00, '2025-12-15 12:50:08', '2025-12-15 12:50:57', 0, 'Completed', 'Test', 1, '2025-12-15 07:50:08'),
(3, 2, 2, 1, 30000.00, 28888.90, 1111.10, '2025-12-15 12:50:57', '2025-12-15 14:12:01', 1, 'Completed', '', 1, '2025-12-15 07:50:57'),
(4, 1, 2, 2, 999.00, 998.26, 0.74, '2025-12-15 12:53:43', '2025-12-15 14:09:18', 1, 'Completed', 'Next Stage: Scouring/Washing (Expected duration: 1 days)', 1, '2025-12-15 07:53:43'),
(5, 3, 1, 1, 2500.00, NULL, NULL, '2025-12-15 12:57:28', NULL, NULL, 'In Progress', NULL, 1, '2025-12-15 07:57:28'),
(6, 1, 3, 11, 998.26, NULL, NULL, '2025-12-15 14:09:18', NULL, NULL, 'In Progress', NULL, 1, '2025-12-15 09:09:18'),
(7, 2, 3, 11, 28888.90, NULL, NULL, '2025-12-15 14:12:01', NULL, NULL, 'In Progress', NULL, 1, '2025-12-15 09:12:01'),
(8, 4, 1, 1, 1799.00, NULL, NULL, '2025-12-15 17:32:08', NULL, NULL, 'In Progress', NULL, 2, '2025-12-15 12:32:08'),
(9, 5, 1, 1, 3599.00, NULL, NULL, '2025-12-15 17:35:26', NULL, NULL, 'In Progress', NULL, 2, '2025-12-15 12:35:26'),
(10, 6, 1, 1, 500.00, 495.26, 4.74, '2025-12-15 17:36:10', '2025-12-15 17:37:44', 0, 'Completed', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Morbi rhoncus vitae nisi ut volutpat. Quisque vitae porttitor sem. Proin et faucibus leo. Nunc accumsan fermentum nunc sed congue. Vivamus eu consectetur nibh.', 2, '2025-12-15 12:36:10'),
(11, 6, 2, 2, 495.26, 494.26, 1.00, '2025-12-15 17:37:44', '2025-12-15 17:38:18', 0, 'Completed', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Morbi rhoncus vitae nisi ut volutpat. Quisque vitae porttitor sem. Proin et faucibus leo. Nunc accumsan fermentum nunc sed congue. Vivamus eu consectetur nibh.', 2, '2025-12-15 12:37:44'),
(12, 6, 3, 11, 494.26, 491.76, 2.50, '2025-12-15 17:38:18', '2025-12-15 17:39:24', 0, 'Completed', 'Next Stage: Carding', 2, '2025-12-15 12:38:18'),
(13, 6, 4, 8, 491.76, 491.76, 0.00, '2025-12-15 17:39:24', '2025-12-15 17:42:10', 0, 'Completed', 'Dyeing - Expected duration: 2 days', 1, '2025-12-15 12:39:24'),
(14, 6, 5, 5, 491.76, 491.59, 0.17, '2025-12-15 17:42:10', '2025-12-15 17:43:10', 0, 'Completed', 'Spinning - Expected duration: 3 days', 1, '2025-12-15 12:42:10'),
(15, 6, 6, 6, 491.59, 491.59, 0.00, '2025-12-15 17:43:10', '2025-12-15 17:44:59', 0, 'Completed', 'Weaving/Knitting - Expected duration: 4 days', 1, '2025-12-15 12:43:10'),
(16, 6, 7, 7, 491.59, 491.59, 0.00, '2025-12-15 17:44:59', '2025-12-15 17:50:57', 0, 'Completed', 'NA', 2, '2025-12-15 12:44:59'),
(17, 6, 8, 8, 491.59, 491.59, 0.00, '2025-12-15 17:50:57', '2025-12-15 17:53:45', 0, 'Completed', 'NA', 2, '2025-12-15 12:50:57'),
(18, 6, 9, 12, 491.59, 450.00, 41.59, '2025-12-15 17:53:45', '2025-12-15 17:56:22', 0, 'Completed', 'Final after 450 Kg.', 2, '2025-12-15 12:53:45'),
(19, 6, 10, 10, 450.00, 450.00, 0.00, '2025-12-15 17:56:22', '2025-12-15 17:57:36', 0, 'Completed', '', 1, '2025-12-15 12:56:22');

-- --------------------------------------------------------

--
-- Table structure for table `production_stages`
--

CREATE TABLE `production_stages` (
  `stage_id` int(11) NOT NULL,
  `stage_number` int(11) NOT NULL,
  `stage_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `avg_duration_days` int(11) DEFAULT NULL,
  `avg_weight_loss_percent` decimal(5,2) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `production_stages`
--

INSERT INTO `production_stages` (`stage_id`, `stage_number`, `stage_name`, `description`, `avg_duration_days`, `avg_weight_loss_percent`, `is_active`, `created_at`) VALUES
(1, 1, 'Raw Wool Receipt', 'Initial receiving and weighing of raw wool', 1, 0.00, 1, '2025-12-15 06:45:33'),
(2, 2, 'Sorting & Grading', 'Sorting by quality and color', 2, 5.00, 1, '2025-12-15 06:45:33'),
(3, 3, 'Scouring/Washing', 'Deep cleaning to remove dirt and grease', 1, 30.00, 1, '2025-12-15 06:45:33'),
(4, 4, 'Carding', 'Aligning wool fibers', 1, 2.00, 1, '2025-12-15 06:45:33'),
(5, 5, 'Dyeing', 'Coloring the wool', 2, 3.00, 1, '2025-12-15 06:45:33'),
(6, 6, 'Spinning', 'Converting to yarn', 3, 5.00, 1, '2025-12-15 06:45:33'),
(7, 7, 'Weaving/Knitting', 'Creating fabric', 4, 2.00, 1, '2025-12-15 06:45:33'),
(8, 8, 'Finishing', 'Final fabric treatment', 2, 1.00, 1, '2025-12-15 06:45:33'),
(9, 9, 'Cutting & Sewing', 'Garment production', 5, 8.00, 1, '2025-12-15 06:45:33'),
(10, 10, 'QC & Packaging', 'Quality check and final packaging', 2, 1.00, 1, '2025-12-15 06:45:33');

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `setting_id` int(11) NOT NULL,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`setting_id`, `setting_key`, `setting_value`, `description`, `updated_at`) VALUES
(1, 'company_name', 'Wool Production MES', 'Company name displayed in system', '2025-12-15 12:25:38'),
(2, 'timezone', 'Asia/Karachi', 'System timezone', '2025-12-15 06:45:33'),
(3, 'date_format', 'Y-m-d', 'Date display format', '2025-12-15 06:45:33'),
(4, 'alert_delay_days', '2', 'Days delay before triggering alert', '2025-12-15 06:45:33');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `role` enum('Admin','Supervisor','Viewer','Vendor') DEFAULT 'Viewer',
  `vendor_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password_hash`, `full_name`, `email`, `role`, `vendor_id`, `is_active`, `last_login`, `created_at`) VALUES
(1, 'admin', '$2y$10$k5OVus/8EDQ1CKh3Pkf5j.4e52r.fbXLIB9kg99/HuvJnRj6/B4Vy', 'System Administrator', 'admin@woolmes.com', 'Admin', NULL, 1, '2025-12-15 17:28:43', '2025-12-15 06:45:33'),
(2, 'supervisor', '$2y$10$lQvRvGWPfUXxtV6XjHqJke94ROSu6MA4nyWl7Uic4gZG7gBOBwLtu', 'Production Supervisor', 'supervisor@woolmes.com', 'Supervisor', NULL, 1, '2025-12-15 17:29:32', '2025-12-15 06:45:33'),
(3, 'kaleem', '$2y$10$jh1vauLDQX49lIBGAZ/yHutCmLE4DxtfxwqDg8Pq9U34t4XM4kfMS', 'Kaleem Ullah', 'kaleem@example.com', 'Viewer', NULL, 1, '2025-12-15 14:52:47', '2025-12-15 08:44:33'),
(5, 'vendor1', '$2y$10$Ihmld2HrYSiNsoiFb37qSOe1N18aKo6JQSVtnaIv.JVUFPebXY.iS', 'vendor1', 'vendor1@example.com', 'Vendor', 1, 1, '2025-12-15 17:03:55', '2025-12-15 11:30:34');

-- --------------------------------------------------------

--
-- Table structure for table `vendors`
--

CREATE TABLE `vendors` (
  `vendor_id` int(11) NOT NULL,
  `vendor_name` varchar(100) NOT NULL,
  `vendor_type` enum('Internal','External') DEFAULT 'External',
  `contact_person` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `specialization` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `vendors`
--

INSERT INTO `vendors` (`vendor_id`, `vendor_name`, `vendor_type`, `contact_person`, `phone`, `email`, `address`, `specialization`, `is_active`, `created_at`) VALUES
(1, 'Warehouse A', 'Internal', 'Ahmed Khan', '021-1234567', NULL, NULL, 'Storage', 1, '2025-12-15 06:45:33'),
(2, 'Sorting Department', 'Internal', 'Sara Ali', '021-2345678', NULL, NULL, 'Sorting & Grading', 1, '2025-12-15 06:45:33'),
(3, 'CleanWool Facility', 'External', 'Hassan Raza', '021-3456789', NULL, NULL, 'Washing', 1, '2025-12-15 06:45:33'),
(4, 'Card Master Co.', 'External', 'Fatima Sheikh', '021-4567890', NULL, NULL, 'Carding', 1, '2025-12-15 06:45:33'),
(5, 'ColorTech Dyehouse', 'External', 'Bilal Ahmed', '021-5678901', NULL, NULL, 'Dyeing', 1, '2025-12-15 06:45:33'),
(6, 'Premium Spinning Mill', 'External', 'Ayesha Khan', '021-6789012', NULL, NULL, 'Spinning', 1, '2025-12-15 06:45:33'),
(7, 'Textile Weavers Ltd', 'External', 'Omar Farooq', '021-7890123', NULL, NULL, 'Weaving', 1, '2025-12-15 06:45:33'),
(8, 'Finish Pro', 'External', 'Zainab Ali', '021-8901234', NULL, NULL, 'Finishing', 1, '2025-12-15 06:45:33'),
(9, 'Fashion Garments Inc', 'External', 'Imran Malik', '021-9012345', NULL, NULL, 'Garment Making', 1, '2025-12-15 06:45:33'),
(10, 'QC Department', 'Internal', 'Nadia Hussain', '021-0123456', NULL, NULL, 'Quality Control', 1, '2025-12-15 06:45:33'),
(11, 'Scouring/Washing', 'Internal', 'Aslam Malik', '0309-0404293', '', '', 'Scouring/Washing', 1, '2025-12-15 09:08:21'),
(12, 'Cutting & Sewing', 'Internal', 'Cutting & Sewing', '', '', 'Internal', 'Cutting & Sewing', 1, '2025-12-15 12:53:11');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `alerts`
--
ALTER TABLE `alerts`
  ADD PRIMARY KEY (`alert_id`),
  ADD KEY `resolved_by` (`resolved_by`),
  ADD KEY `idx_alerts_batch` (`batch_id`);

--
-- Indexes for table `batches`
--
ALTER TABLE `batches`
  ADD PRIMARY KEY (`batch_id`),
  ADD UNIQUE KEY `batch_number` (`batch_number`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_batch_status` (`status`),
  ADD KEY `idx_batch_stage` (`current_stage_id`);

--
-- Indexes for table `batch_stage_history`
--
ALTER TABLE `batch_stage_history`
  ADD PRIMARY KEY (`history_id`),
  ADD KEY `stage_id` (`stage_id`),
  ADD KEY `vendor_id` (`vendor_id`),
  ADD KEY `updated_by` (`updated_by`),
  ADD KEY `idx_history_batch` (`batch_id`);

--
-- Indexes for table `production_stages`
--
ALTER TABLE `production_stages`
  ADD PRIMARY KEY (`stage_id`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`setting_id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `vendor_id` (`vendor_id`);

--
-- Indexes for table `vendors`
--
ALTER TABLE `vendors`
  ADD PRIMARY KEY (`vendor_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `alerts`
--
ALTER TABLE `alerts`
  MODIFY `alert_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `batches`
--
ALTER TABLE `batches`
  MODIFY `batch_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `batch_stage_history`
--
ALTER TABLE `batch_stage_history`
  MODIFY `history_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `production_stages`
--
ALTER TABLE `production_stages`
  MODIFY `stage_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `setting_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `vendors`
--
ALTER TABLE `vendors`
  MODIFY `vendor_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `alerts`
--
ALTER TABLE `alerts`
  ADD CONSTRAINT `alerts_ibfk_1` FOREIGN KEY (`batch_id`) REFERENCES `batches` (`batch_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `alerts_ibfk_2` FOREIGN KEY (`resolved_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `batches`
--
ALTER TABLE `batches`
  ADD CONSTRAINT `batches_ibfk_1` FOREIGN KEY (`current_stage_id`) REFERENCES `production_stages` (`stage_id`),
  ADD CONSTRAINT `batches_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `batch_stage_history`
--
ALTER TABLE `batch_stage_history`
  ADD CONSTRAINT `batch_stage_history_ibfk_1` FOREIGN KEY (`batch_id`) REFERENCES `batches` (`batch_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `batch_stage_history_ibfk_2` FOREIGN KEY (`stage_id`) REFERENCES `production_stages` (`stage_id`),
  ADD CONSTRAINT `batch_stage_history_ibfk_3` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`vendor_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `batch_stage_history_ibfk_4` FOREIGN KEY (`updated_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`vendor_id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
