-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 13, 2026 at 04:31 PM
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
-- Database: `soulbud_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(10) UNSIGNED NOT NULL,
  `username` varchar(80) NOT NULL,
  `email` varchar(191) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `email`, `password`, `full_name`, `created_at`) VALUES
(3, 'Admin', 'jomarieambos3@gmail.com', '$2y$12$FvTZLcj5JMLbPGZyh2pSDO/1G1vuerjrhDQv7oxy85yy6TuxKrRti', 'Ambos Jomarie', '2026-05-13 05:55:54');

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `id` int(10) UNSIGNED NOT NULL,
  `booking_ref` varchar(20) NOT NULL,
  `client_id` int(10) UNSIGNED NOT NULL,
  `event_type` varchar(100) NOT NULL,
  `event_location` text NOT NULL,
  `preferred_date` date NOT NULL,
  `preferred_time` time NOT NULL,
  `service_type` enum('Photography','Videography','Both') NOT NULL,
  `additional_notes` text DEFAULT NULL,
  `status` enum('Waiting for Email Verification','Pending','Approved','Confirmed','Completed','Cancelled','Rejected','Rescheduled') NOT NULL DEFAULT 'Waiting for Email Verification',
  `admin_notes` text DEFAULT NULL,
  `rescheduled_date` date DEFAULT NULL,
  `rescheduled_time` time DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`id`, `booking_ref`, `client_id`, `event_type`, `event_location`, `preferred_date`, `preferred_time`, `service_type`, `additional_notes`, `status`, `admin_notes`, `rescheduled_date`, `rescheduled_time`, `created_at`, `updated_at`) VALUES
(5, 'SB-067BCD07', 2, 'Corporate Event', 'Villareal, Bayawan, Negros Oriental, Negros Island Region, Philippines', '2026-05-16', '04:48:00', 'Both', 'sss', 'Confirmed', '', NULL, NULL, '2026-05-13 08:48:15', '2026-05-13 08:54:11');

-- --------------------------------------------------------

--
-- Table structure for table `calendar_blocks`
--

CREATE TABLE `calendar_blocks` (
  `id` int(10) UNSIGNED NOT NULL,
  `block_date` date NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `chat_messages`
--

CREATE TABLE `chat_messages` (
  `id` int(11) NOT NULL,
  `session_id` varchar(64) NOT NULL,
  `sender` enum('client','admin') NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `clients`
--

CREATE TABLE `clients` (
  `id` int(10) UNSIGNED NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `email` varchar(191) NOT NULL,
  `contact_number` varchar(30) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `clients`
--

INSERT INTO `clients` (`id`, `full_name`, `email`, `contact_number`, `created_at`) VALUES
(2, 'Moreno Micho', 'morenomichoj@gmail.com', '09550171169', '2026-05-13 06:02:13');

-- --------------------------------------------------------

--
-- Table structure for table `email_verifications`
--

CREATE TABLE `email_verifications` (
  `id` int(10) UNSIGNED NOT NULL,
  `booking_id` int(10) UNSIGNED NOT NULL,
  `token` varchar(255) NOT NULL,
  `is_used` tinyint(1) DEFAULT 0,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `email_verifications`
--

INSERT INTO `email_verifications` (`id`, `booking_id`, `token`, `is_used`, `expires_at`, `created_at`) VALUES
(5, 5, '30696c6722aadc6a0beb77d3390b15e0f8f3517748ca8e2777421158f10e8c5b', 1, '2026-05-14 10:48:15', '2026-05-13 08:48:15');

-- --------------------------------------------------------

--
-- Table structure for table `notification_logs`
--

CREATE TABLE `notification_logs` (
  `id` int(10) UNSIGNED NOT NULL,
  `booking_id` int(10) UNSIGNED DEFAULT NULL,
  `type` varchar(80) NOT NULL,
  `recipient` varchar(191) NOT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('Sent','Failed') DEFAULT 'Sent'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notification_logs`
--

INSERT INTO `notification_logs` (`id`, `booking_id`, `type`, `recipient`, `subject`, `sent_at`, `status`) VALUES
(7, NULL, 'verification', 'morenomichoj@gmail.com', '📧 Verify Your Booking — SOULBUD.CO', '2026-05-13 08:37:02', 'Sent'),
(8, NULL, 'verification', 'morenomichoj@gmail.com', '📧 Verify Your Booking — SOULBUD.CO', '2026-05-13 08:48:20', 'Sent'),
(9, NULL, 'confirmation', 'morenomichoj@gmail.com', '✅ Booking Confirmed — SOULBUD.CO', '2026-05-13 08:53:16', 'Sent'),
(10, 5, 'confirmation', 'morenomichoj@gmail.com', '✅ Booking Confirmed — SOULBUD.CO', '2026-05-13 08:53:28', 'Sent'),
(11, 5, 'payment', 'morenomichoj@gmail.com', '💳 Payment Confirmed — SOULBUD.CO', '2026-05-13 08:54:22', 'Sent'),
(12, NULL, 'payment', 'morenomichoj@gmail.com', '💳 Payment Confirmed — SOULBUD.CO', '2026-05-13 13:48:57', 'Sent');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(10) UNSIGNED NOT NULL,
  `booking_id` int(10) UNSIGNED NOT NULL,
  `payment_method` enum('GCash','Bank Transfer','Cash') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `proof_filename` varchar(255) DEFAULT NULL,
  `status` enum('Pending','Confirmed','Rejected') DEFAULT 'Pending',
  `confirmed_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `booking_id`, `payment_method`, `amount`, `proof_filename`, `status`, `confirmed_at`, `created_at`) VALUES
(3, 5, 'GCash', 900.00, 'SB-067BCD07_1778662450.png', 'Confirmed', '2026-05-13 10:54:18', '2026-05-13 08:54:10');

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_bookings_full`
-- (See below for the actual view)
--
CREATE TABLE `v_bookings_full` (
`id` int(10) unsigned
,`booking_ref` varchar(20)
,`full_name` varchar(150)
,`email` varchar(191)
,`contact_number` varchar(30)
,`event_type` varchar(100)
,`event_location` text
,`preferred_date` date
,`preferred_time` time
,`service_type` enum('Photography','Videography','Both')
,`status` enum('Waiting for Email Verification','Pending','Approved','Confirmed','Completed','Cancelled','Rejected','Rescheduled')
,`admin_notes` text
,`rescheduled_date` date
,`rescheduled_time` time
,`created_at` timestamp
,`updated_at` timestamp
,`payment_method` enum('GCash','Bank Transfer','Cash')
,`amount` decimal(10,2)
,`payment_status` enum('Pending','Confirmed','Rejected')
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_monthly_report`
-- (See below for the actual view)
--
CREATE TABLE `v_monthly_report` (
`month` varchar(7)
,`total_bookings` bigint(21)
,`confirmed` decimal(23,0)
,`completed` decimal(23,0)
,`cancelled` decimal(23,0)
,`paid` decimal(23,0)
,`total_revenue` decimal(32,2)
);

-- --------------------------------------------------------

--
-- Structure for view `v_bookings_full`
--
DROP TABLE IF EXISTS `v_bookings_full`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_bookings_full`  AS SELECT `b`.`id` AS `id`, `b`.`booking_ref` AS `booking_ref`, `c`.`full_name` AS `full_name`, `c`.`email` AS `email`, `c`.`contact_number` AS `contact_number`, `b`.`event_type` AS `event_type`, `b`.`event_location` AS `event_location`, `b`.`preferred_date` AS `preferred_date`, `b`.`preferred_time` AS `preferred_time`, `b`.`service_type` AS `service_type`, `b`.`status` AS `status`, `b`.`admin_notes` AS `admin_notes`, `b`.`rescheduled_date` AS `rescheduled_date`, `b`.`rescheduled_time` AS `rescheduled_time`, `b`.`created_at` AS `created_at`, `b`.`updated_at` AS `updated_at`, `p`.`payment_method` AS `payment_method`, `p`.`amount` AS `amount`, `p`.`status` AS `payment_status` FROM ((`bookings` `b` join `clients` `c` on(`c`.`id` = `b`.`client_id`)) left join `payments` `p` on(`p`.`booking_id` = `b`.`id`)) ;

-- --------------------------------------------------------

--
-- Structure for view `v_monthly_report`
--
DROP TABLE IF EXISTS `v_monthly_report`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_monthly_report`  AS SELECT date_format(`b`.`preferred_date`,'%Y-%m') AS `month`, count(0) AS `total_bookings`, sum(`b`.`status` = 'Confirmed') AS `confirmed`, sum(`b`.`status` = 'Completed') AS `completed`, sum(`b`.`status` = 'Cancelled') AS `cancelled`, sum(`p`.`status` = 'Confirmed') AS `paid`, ifnull(sum(`p`.`amount`),0) AS `total_revenue` FROM (`bookings` `b` left join `payments` `p` on(`p`.`booking_id` = `b`.`id`)) GROUP BY date_format(`b`.`preferred_date`,'%Y-%m') ORDER BY date_format(`b`.`preferred_date`,'%Y-%m') DESC ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `booking_ref` (`booking_ref`),
  ADD KEY `client_id` (`client_id`);

--
-- Indexes for table `calendar_blocks`
--
ALTER TABLE `calendar_blocks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_block_date` (`block_date`);

--
-- Indexes for table `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_session` (`session_id`),
  ADD KEY `idx_read` (`is_read`);

--
-- Indexes for table `clients`
--
ALTER TABLE `clients`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_client_email` (`email`);

--
-- Indexes for table `email_verifications`
--
ALTER TABLE `email_verifications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `booking_id` (`booking_id`);

--
-- Indexes for table `notification_logs`
--
ALTER TABLE `notification_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `booking_id` (`booking_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `booking_id` (`booking_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `calendar_blocks`
--
ALTER TABLE `calendar_blocks`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `chat_messages`
--
ALTER TABLE `chat_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=70;

--
-- AUTO_INCREMENT for table `clients`
--
ALTER TABLE `clients`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `email_verifications`
--
ALTER TABLE `email_verifications`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `notification_logs`
--
ALTER TABLE `notification_logs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `email_verifications`
--
ALTER TABLE `email_verifications`
  ADD CONSTRAINT `email_verifications_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notification_logs`
--
ALTER TABLE `notification_logs`
  ADD CONSTRAINT `notification_logs_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
