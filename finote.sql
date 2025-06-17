-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jun 15, 2025 at 04:25 PM
-- Server version: 8.0.30
-- PHP Version: 8.1.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `finote`
--

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `type` enum('income','expense') NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `user_id`, `name`, `type`, `created_at`) VALUES
(1, NULL, 'Gaji', 'income', '2025-06-10 16:19:36'),
(2, NULL, 'Investasi', 'income', '2025-06-10 16:19:36'),
(3, NULL, 'Bonus', 'income', '2025-06-10 16:19:36'),
(4, NULL, 'Pendapatan Orang Tua', 'income', '2025-06-10 16:19:36'),
(5, NULL, 'Makanan', 'expense', '2025-06-10 16:19:36'),
(6, NULL, 'Transportasi', 'expense', '2025-06-10 16:19:36'),
(7, NULL, 'Tagihan Listrik', 'expense', '2025-06-10 16:19:36'),
(8, NULL, 'Jajan', 'expense', '2025-06-10 16:19:36'),
(9, NULL, 'Hiburan', 'expense', '2025-06-10 16:19:36');

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `category_id` int UNSIGNED NOT NULL,
  `type` enum('income','expense') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `transaction_date` date NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`id`, `user_id`, `category_id`, `type`, `amount`, `description`, `transaction_date`, `created_at`) VALUES
(1, 1, 1, 'income', '1500000.00', 'gaji bulan juni', '2025-06-10', '2025-06-10 16:24:59'),
(2, 1, 8, 'expense', '15000.00', 'beli seblak', '2025-06-10', '2025-06-10 16:25:35'),
(3, 1, 9, 'expense', '20000.00', 'nonton', '2025-06-11', '2025-06-11 00:59:02'),
(4, 1, 6, 'expense', '1000000.00', 'servis motor', '2025-06-11', '2025-06-11 01:00:23'),
(5, 1, 4, 'income', '1500000.00', 'gaji bulan juni orang tua', '2025-06-11', '2025-06-11 01:01:54'),
(6, 4, 4, 'income', '1500000.00', 'gaji orang tua', '2025-06-11', '2025-06-11 01:51:41'),
(7, 4, 5, 'expense', '20000.00', 'beli soto', '2025-06-11', '2025-06-11 01:52:19');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int UNSIGNED NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `email`, `created_at`) VALUES
(1, 'ady', '$2y$10$2wj1l5iX3cfTZk6HbVjFlufHPUHszJuZT6pRtcxjeQZH4sVUkIN7W', 'ady@egmail.com', '2025-06-10 02:24:13'),
(2, 'salsa', '$2y$10$kMZueoHO1VVUOdCgjGPc6.XmYbAOcc5PL8NOp.HTlZzBn1cXHR.D2', 'salsa@gmail.com', '2025-06-10 02:25:58'),
(3, 'abyan', '$2y$10$2J9QEiPnIOTmqJNIx5FMRuqbczUiA48LDmw/cAFCoWp8BYTJb3l9q', 'admin@example.com', '2025-06-10 14:15:58'),
(4, 'joni', '$2y$10$xUO6CfTXKBzaS6VI.v7vlObNoAeWWWxp/88OKV0qepfP5QdjY7z02', 'joni@gmail.com', '2025-06-11 01:50:55');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `categories_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `transactions_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
