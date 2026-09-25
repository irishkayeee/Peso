-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 25, 2026 at 06:37 AM
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
-- Database: `peso_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `budgets`
--

CREATE TABLE `budgets` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `category` varchar(30) NOT NULL DEFAULT '',
  `period_type` enum('day','week','month','custom') NOT NULL DEFAULT 'month',
  `period_start` date NOT NULL,
  `period_end` date NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `budgets`
--

INSERT INTO `budgets` (`id`, `user_id`, `category`, `period_type`, `period_start`, `period_end`, `amount`, `created_at`) VALUES
(3, 4, 'Food', 'month', '2026-09-01', '2026-09-30', 2000.00, '2026-09-14 09:08:54'),
(5, 4, '', 'month', '2026-09-01', '2026-09-30', 5000.00, '2026-09-14 09:32:56'),
(7, 4, 'Shopping', 'month', '2026-09-01', '2026-09-30', 500.00, '2026-09-14 09:39:50'),
(12, 4, '', 'week', '2026-09-14', '2026-09-20', 100.00, '2026-09-14 12:48:22'),
(26, 4, '', 'day', '2026-09-16', '2026-09-16', 1000.00, '2026-09-16 13:21:54'),
(27, 4, 'Food', 'day', '2026-09-16', '2026-09-16', 1000.00, '2026-09-16 13:22:04');

-- --------------------------------------------------------

--
-- Table structure for table `expenses`
--

CREATE TABLE `expenses` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `category` varchar(30) NOT NULL,
  `payment_method` varchar(30) NOT NULL DEFAULT 'Cash',
  `description` varchar(255) DEFAULT NULL,
  `expense_date` date NOT NULL,
  `period_type` enum('day','week','month','custom') NOT NULL DEFAULT 'month',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `expenses`
--

INSERT INTO `expenses` (`id`, `user_id`, `amount`, `category`, `payment_method`, `description`, `expense_date`, `period_type`, `created_at`) VALUES
(4, 4, 100.00, 'Food', 'Cash', 'lunch', '2026-09-14', 'month', '2026-09-14 09:31:32'),
(5, 4, 200.00, 'Education', 'Cash', 'paper', '2026-09-14', 'month', '2026-09-14 09:32:19'),
(51, 4, 300.00, 'Food', 'Cash', 'jabe', '2026-09-16', 'month', '2026-09-16 13:28:49'),
(52, 4, 200.00, 'Food', 'Cash', '', '2026-09-16', 'month', '2026-09-16 13:41:54'),
(53, 4, 300.00, 'Food', 'Cash', '', '2026-09-16', 'month', '2026-09-16 13:42:15'),
(603, 4, 300.00, 'Food', 'Cash', '', '2026-09-17', 'month', '2026-09-17 08:41:14');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(10) UNSIGNED NOT NULL,
  `email` varchar(150) NOT NULL,
  `otp_code` varchar(6) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `notify_budget_alerts` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `email`, `password`, `profile_picture`, `created_at`, `notify_budget_alerts`) VALUES
(4, 'Irish Kaye Cuenca', 'cuenca.irishkaye.d@gmail.com', '$2y$10$TmCgRVRVjwnFf9EiiAIJ..anS7yEMO6Pbd1LB3uDucH7y625SFRiu', NULL, '2026-09-14 09:08:10', 1),
(5, 'Sidebar Test', 'sidebar_test_1789377424832@example.com', '$2y$10$yOgO8Vv7uEb/9o2SCZMYEu7FhY1qt/MtaMQ.WBWtGiM9Eh2V2lWE2', NULL, '2026-09-14 09:17:09', 1),
(7, 'Sidebar Test', 'sidebar_test_1789377665025@example.com', '$2y$10$NqCGHworoDZj7D.mwIoEDeJ14RvqsBi2g7.Dt3cGuewpzGt8srDnu', NULL, '2026-09-14 09:21:05', 1),
(8, 'Sidebar Test', 'sidebar_test_1789377708327@example.com', '$2y$10$/ofWaVzu0IhpQt.89q6Ryu0TjbI7jU/SSMlWkKRC/sGTRwQi96/Y2', NULL, '2026-09-14 09:21:48', 1),
(9, 'Sidebar Test', 'sidebar_test_1789377767826@example.com', '$2y$10$BGUlCyKjPxXpOp9u8zLBUemk1bjx6xSk/7bjXZg/6Sm.Qznf.ueS6', NULL, '2026-09-14 09:22:48', 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `budgets`
--
ALTER TABLE `budgets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_user_cat_period` (`user_id`,`category`,`period_start`,`period_end`);

--
-- Indexes for table `expenses`
--
ALTER TABLE `expenses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_date` (`user_id`,`expense_date`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_email` (`email`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email_unique` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `budgets`
--
ALTER TABLE `budgets`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `expenses`
--
ALTER TABLE `expenses`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=604;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `budgets`
--
ALTER TABLE `budgets`
  ADD CONSTRAINT `fk_budgets_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `expenses`
--
ALTER TABLE `expenses`
  ADD CONSTRAINT `fk_expenses_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
