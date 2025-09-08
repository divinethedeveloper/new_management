-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Sep 08, 2025 at 04:50 AM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `store_management`
--

-- --------------------------------------------------------

--
-- Table structure for table `Items`
--

CREATE TABLE `Items` (
  `ItemCode` varchar(10) NOT NULL,
  `ItemDescription` varchar(100) NOT NULL,
  `Unit` varchar(20) NOT NULL,
  `ReorderLevel` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `Items`
--

INSERT INTO `Items` (`ItemCode`, `ItemDescription`, `Unit`, `ReorderLevel`) VALUES
('ITM-001', 'Office Chair', 'Each', 50),
('ITM-002', 'Printer Ink', 'Box', 100),
('ITM-003', 'Notebook', 'Pack', 150),
('ITM-004', 'Desk Lamp', 'Each', 20),
('ITM-005', 'A4 Paper Reams', 'Ream', 200),
('ITM-006', 'Ballpoint Pens', 'Box', 80),
('ITM-007', 'Staplers', 'Unit', 30),
('ITM-008', 'File Folders', 'Pack', 100),
('ITM-009', 'Whiteboard Markers', 'Set', 50),
('ITM-666', 'Test Item', '50', 5);

-- --------------------------------------------------------

--
-- Table structure for table `LoginLogs`
--

CREATE TABLE `LoginLogs` (
  `id` int(11) NOT NULL,
  `username_or_email` varchar(255) NOT NULL,
  `status` enum('success','failure') NOT NULL,
  `login_time` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `LoginLogs`
--

INSERT INTO `LoginLogs` (`id`, `username_or_email`, `status`, `login_time`) VALUES
(1, 'admin@admin.com', 'success', '2025-09-08 02:30:42'),
(2, 'admin@admin.com', 'failure', '2025-09-08 02:32:14'),
(3, 'manager', 'success', '2025-09-08 02:41:44'),
(4, 'admin', 'failure', '2025-09-08 02:47:37'),
(5, 'admin', 'success', '2025-09-08 02:48:02');

-- --------------------------------------------------------

--
-- Table structure for table `StockAlerts`
--

CREATE TABLE `StockAlerts` (
  `AlertID` int(11) NOT NULL,
  `ItemCode` varchar(10) DEFAULT NULL,
  `AlertStatus` enum('OK','Watch','Low') NOT NULL,
  `AlertDate` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `StockAlerts`
--

INSERT INTO `StockAlerts` (`AlertID`, `ItemCode`, `AlertStatus`, `AlertDate`) VALUES
(1, 'ITM-001', 'OK', '2025-09-03'),
(2, 'ITM-002', 'Watch', '2025-09-03'),
(3, 'ITM-003', 'Low', '2025-09-03'),
(4, 'ITM-004', 'OK', '2025-09-03'),
(5, 'ITM-005', 'OK', '2025-09-05'),
(6, 'ITM-006', 'Watch', '2025-09-05'),
(7, 'ITM-007', 'Low', '2025-09-05'),
(8, 'ITM-008', 'OK', '2025-09-05'),
(9, 'ITM-009', 'Watch', '2025-09-05'),
(10, 'ITM-008', 'OK', '2025-09-05'),
(11, 'ITM-666', 'Watch', '2025-09-05'),
(12, 'ITM-001', 'OK', '2025-09-05');

-- --------------------------------------------------------

--
-- Table structure for table `StockBalance`
--

CREATE TABLE `StockBalance` (
  `ItemCode` varchar(10) NOT NULL,
  `OpeningStock` int(11) NOT NULL,
  `StockOnHand` int(11) NOT NULL,
  `LastUpdated` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `StockBalance`
--

INSERT INTO `StockBalance` (`ItemCode`, `OpeningStock`, `StockOnHand`, `LastUpdated`) VALUES
('ITM-001', 100, 200, '2025-09-05'),
('ITM-002', 200, 150, '2025-09-03'),
('ITM-003', 500, 100, '2025-09-03'),
('ITM-004', 50, 50, '2025-09-03'),
('ITM-005', 300, 350, '2025-09-05'),
('ITM-006', 150, 120, '2025-09-05'),
('ITM-007', 60, 25, '2025-09-05'),
('ITM-008', 200, 300, '2025-09-05'),
('ITM-009', 100, 90, '2025-09-05'),
('ITM-666', 5, 5, '2025-09-05');

-- --------------------------------------------------------

--
-- Table structure for table `StockTransactions`
--

CREATE TABLE `StockTransactions` (
  `TransactionID` int(11) NOT NULL,
  `ItemCode` varchar(10) DEFAULT NULL,
  `TransactionType` enum('Receipt','Issue') NOT NULL,
  `Quantity` int(11) NOT NULL,
  `TransactionDate` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `StockTransactions`
--

INSERT INTO `StockTransactions` (`TransactionID`, `ItemCode`, `TransactionType`, `Quantity`, `TransactionDate`) VALUES
(1, 'ITM-001', 'Receipt', 100, '2025-08-01'),
(2, 'ITM-001', 'Issue', 30, '2025-08-15'),
(3, 'ITM-002', 'Issue', 150, '2025-08-20'),
(4, 'ITM-002', 'Receipt', 100, '2025-08-25'),
(5, 'ITM-003', 'Receipt', 200, '2025-08-10'),
(6, 'ITM-003', 'Issue', 600, '2025-08-28'),
(7, 'ITM-004', 'Receipt', 50, '2025-08-05'),
(8, 'ITM-005', 'Receipt', 150, '2025-08-02'),
(9, 'ITM-005', 'Issue', 100, '2025-08-20'),
(10, 'ITM-006', 'Receipt', 50, '2025-08-05'),
(11, 'ITM-006', 'Issue', 80, '2025-08-25'),
(12, 'ITM-007', 'Receipt', 20, '2025-08-10'),
(13, 'ITM-007', 'Issue', 55, '2025-08-30'),
(14, 'ITM-008', 'Receipt', 100, '2025-08-15'),
(16, 'ITM-009', 'Receipt', 60, '2025-08-03'),
(17, 'ITM-009', 'Issue', 70, '2025-08-28'),
(18, 'ITM-001', 'Receipt', 30, '2025-09-05');

-- --------------------------------------------------------

--
-- Table structure for table `Users`
--

CREATE TABLE `Users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('viewer','store_manager','system_manager') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `Users`
--

INSERT INTO `Users` (`id`, `username`, `password`, `role`, `created_at`) VALUES
(1, 'viewer', '', 'viewer', '2025-09-07 21:29:37'),
(2, 'manager', 'password', 'store_manager', '2025-09-07 21:29:37'),
(3, 'admin', 'admin', 'system_manager', '2025-09-07 21:29:37');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `Items`
--
ALTER TABLE `Items`
  ADD PRIMARY KEY (`ItemCode`);

--
-- Indexes for table `LoginLogs`
--
ALTER TABLE `LoginLogs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `StockAlerts`
--
ALTER TABLE `StockAlerts`
  ADD PRIMARY KEY (`AlertID`),
  ADD KEY `ItemCode` (`ItemCode`);

--
-- Indexes for table `StockBalance`
--
ALTER TABLE `StockBalance`
  ADD PRIMARY KEY (`ItemCode`);

--
-- Indexes for table `StockTransactions`
--
ALTER TABLE `StockTransactions`
  ADD PRIMARY KEY (`TransactionID`),
  ADD KEY `ItemCode` (`ItemCode`);

--
-- Indexes for table `Users`
--
ALTER TABLE `Users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `LoginLogs`
--
ALTER TABLE `LoginLogs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `StockAlerts`
--
ALTER TABLE `StockAlerts`
  MODIFY `AlertID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `StockTransactions`
--
ALTER TABLE `StockTransactions`
  MODIFY `TransactionID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `Users`
--
ALTER TABLE `Users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `StockAlerts`
--
ALTER TABLE `StockAlerts`
  ADD CONSTRAINT `stockalerts_ibfk_1` FOREIGN KEY (`ItemCode`) REFERENCES `Items` (`ItemCode`);

--
-- Constraints for table `StockBalance`
--
ALTER TABLE `StockBalance`
  ADD CONSTRAINT `stockbalance_ibfk_1` FOREIGN KEY (`ItemCode`) REFERENCES `Items` (`ItemCode`);

--
-- Constraints for table `StockTransactions`
--
ALTER TABLE `StockTransactions`
  ADD CONSTRAINT `stocktransactions_ibfk_1` FOREIGN KEY (`ItemCode`) REFERENCES `Items` (`ItemCode`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
