-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 04, 2025 at 03:57 PM
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
-- Database: `research_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `password`) VALUES
(1, 'admin', 'admin');

-- --------------------------------------------------------

--
-- Table structure for table `researchassignments`
--

CREATE TABLE `researchassignments` (
  `assignment_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `due_date` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `researchassignments`
--

INSERT INTO `researchassignments` (`assignment_id`, `title`, `description`, `created_by`, `due_date`, `created_at`, `updated_at`) VALUES
(1, 'Ai Tech', 'First Assignment', 1, '2025-05-31 09:18:00', '2025-05-27 04:18:27', '2025-05-27 04:22:03'),
(2, 'Assignment 2', 'dfas', 1, '2025-05-28 10:16:00', '2025-05-27 05:16:04', '2025-05-27 05:16:04');

-- --------------------------------------------------------

--
-- Table structure for table `submissions`
--

CREATE TABLE `submissions` (
  `submission_id` int(11) NOT NULL,
  `assignment_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `supervisor_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `abstract` text NOT NULL,
  `keywords` varchar(255) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `domain` varchar(100) DEFAULT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_type` enum('pdf','doc','docx') NOT NULL,
  `submission_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','under_review','accepted','rejected','needs_improvement','accepted_and_published') DEFAULT 'pending',
  `feedback` text DEFAULT NULL,
  `feedback_file` varchar(255) DEFAULT NULL,
  `rating` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `submissions`
--

INSERT INTO `submissions` (`submission_id`, `assignment_id`, `student_id`, `supervisor_id`, `title`, `abstract`, `keywords`, `category`, `domain`, `file_path`, `file_type`, `submission_date`, `status`, `feedback`, `feedback_file`, `rating`) VALUES
(1, 1, 2, 1, 'sd', 'sfd', 'ai', 'fd', 'dsa', 'uploads/1748323142_Alien Invasion Recovery Group I MY FINAL (1).pdf', 'pdf', '2025-05-27 05:19:02', 'accepted_and_published', 'YOu need soematea', 'feedbacks/1748324721_SRS File (Online Serach Portal).docx', NULL),
(2, 2, 2, 1, 'ds', 'ds', 'dsds', 'ds', 'd', 'uploads/1748322989_Alien Invasion Recovery Group I MY FINAL (1).pdf', 'pdf', '2025-05-27 05:16:29', 'accepted_and_published', 'fds', '', NULL),
(3, 1, 2, 1, 'New Email', 'New Email', 'New Email', 'New Email', 'New Email', 'uploads/1748324987_Phase I.docx', 'docx', '2025-05-27 05:49:47', 'needs_improvement', 'kjadslke', 'feedbacks/1748332233_Chapter 2&4.pdf', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `submissionversions`
--

CREATE TABLE `submissionversions` (
  `version_id` int(11) NOT NULL,
  `submission_id` int(11) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `uploaded_at` datetime DEFAULT current_timestamp(),
  `uploaded_by` int(11) NOT NULL,
  `file_type` varchar(50) DEFAULT NULL,
  `remarks` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `submissionversions`
--

INSERT INTO `submissionversions` (`version_id`, `submission_id`, `file_path`, `uploaded_at`, `uploaded_by`, `file_type`, `remarks`) VALUES
(1, 1, 'uploads/1748320927_Spring 2025_CS619_10942 (3).docx', '2025-05-27 10:18:12', 2, 'docx', 'New'),
(2, 1, 'uploads/1748323092_Chapter 2&4.pdf', '2025-05-27 10:19:02', 2, 'pdf', 'ds');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `age` int(11) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `user_type` varchar(50) DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `full_name` varchar(100) NOT NULL,
  `affiliation` varchar(150) NOT NULL,
  `expertise` text NOT NULL,
  `publications` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `email`, `phone`, `age`, `bio`, `user_type`, `profile_picture`, `status`, `full_name`, `affiliation`, `expertise`, `publications`) VALUES
(1, 'faculty12', '$2y$10$I9jjw4myLjaLInrQxC2fvemwOOMQmKOESysxH8jb30iOLODJnfwRi', 'saifx280@gmail.com', '3176526827', 23, 'ds', 'Faculty Member', 'images/1748319257_cs201.png', 'approved', '', '', '', 0),
(2, 'student12', '$2y$10$LuMxm/iLUgNhaCEu8XS8du3qv.Izh7BW5GVe/vhJKiI0gj0gZEbiO', 'nasiryt.827@gmail.com', '31765268278', 23, 'ds', 'Student', 'images/1748319947_addpet.png', 'approved', '', '', '', 0),
(3, 'haider', '$2y$10$H3Fsu0wM8Auc2DbXCmRFMuACfVDi.vsw3.LDUCk8xlhPqsPLj3YvK', 'dummy@gmail.com', '4512', 33, 'fdsafd', 'Faculty Member', 'images/1754314278_2.png', 'approved', 'Haider ALI', 'VU', 'Data Science, Cybersecurity', 3);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `researchassignments`
--
ALTER TABLE `researchassignments`
  ADD PRIMARY KEY (`assignment_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `submissions`
--
ALTER TABLE `submissions`
  ADD PRIMARY KEY (`submission_id`),
  ADD KEY `assignment_id` (`assignment_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `supervisor_id` (`supervisor_id`);

--
-- Indexes for table `submissionversions`
--
ALTER TABLE `submissionversions`
  ADD PRIMARY KEY (`version_id`),
  ADD KEY `submission_id` (`submission_id`),
  ADD KEY `uploaded_by` (`uploaded_by`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `researchassignments`
--
ALTER TABLE `researchassignments`
  MODIFY `assignment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `submissions`
--
ALTER TABLE `submissions`
  MODIFY `submission_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `submissionversions`
--
ALTER TABLE `submissionversions`
  MODIFY `version_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `researchassignments`
--
ALTER TABLE `researchassignments`
  ADD CONSTRAINT `researchassignments_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `submissions`
--
ALTER TABLE `submissions`
  ADD CONSTRAINT `submissions_ibfk_1` FOREIGN KEY (`assignment_id`) REFERENCES `researchassignments` (`assignment_id`),
  ADD CONSTRAINT `submissions_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `submissions_ibfk_3` FOREIGN KEY (`supervisor_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `submissionversions`
--
ALTER TABLE `submissionversions`
  ADD CONSTRAINT `submissionversions_ibfk_1` FOREIGN KEY (`submission_id`) REFERENCES `submissions` (`submission_id`),
  ADD CONSTRAINT `submissionversions_ibfk_2` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
