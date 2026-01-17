-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: May 21, 2025 at 08:17 PM
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
-- Database: `Cineplex21`
--

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `id` int(11) NOT NULL,
  `showtime_id` int(11) NOT NULL,
  `user_id` varchar(100) NOT NULL,
  `booking_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `total_amount` decimal(10,2) NOT NULL,
  `status` enum('pending','confirmed','cancelled') NOT NULL DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`id`, `showtime_id`, `user_id`, `booking_date`, `total_amount`, `status`) VALUES
(16, 148, '9', '2025-05-04 16:12:15', 165000.00, 'pending'),
(17, 82, '7', '2025-05-04 17:00:05', 165000.00, 'pending'),
(18, 95, '7', '2025-05-05 01:14:24', 100000.00, 'pending'),
(19, 80, '7', '2025-05-21 15:47:16', 150000.00, 'pending');

-- --------------------------------------------------------

--
-- Table structure for table `booking_seats`
--

CREATE TABLE `booking_seats` (
  `id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `seat_number` varchar(5) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `booking_seats`
--

INSERT INTO `booking_seats` (`id`, `booking_id`, `seat_number`) VALUES
(48, 16, 'D7'),
(49, 16, 'G8'),
(50, 16, 'K9'),
(51, 17, 'J10'),
(52, 17, 'K10'),
(53, 17, 'K11'),
(54, 18, 'K17'),
(55, 18, 'K18'),
(56, 19, 'D6'),
(57, 19, 'D7'),
(58, 19, 'D8');

-- --------------------------------------------------------

--
-- Table structure for table `movies`
--

CREATE TABLE `movies` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `release_date` date NOT NULL,
  `rating` enum('G','PG','PG-13','R') NOT NULL,
  `poster_path` varchar(255) DEFAULT NULL,
  `description` text NOT NULL,
  `status` enum('now-showing','coming-soon') NOT NULL,
  `genre` varchar(100) NOT NULL,
  `duration` int(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `movies`
--

INSERT INTO `movies` (`id`, `title`, `release_date`, `rating`, `poster_path`, `description`, `status`, `genre`, `duration`, `created_at`) VALUES
(41, 'Inside Out 2', '2025-05-04', 'PG', 'movie_681721de4402c.jpg', 'Riley navigates teenage life with new emotions, including Anxiety.', 'now-showing', 'Animation', 100, '2025-05-04 08:14:22'),
(42, 'Deadpool & Wolverine', '2025-05-04', 'R', 'movie_681722289b331.jpg', 'Deadpool and Wolverine team up for a chaotic, action-packed mission.', 'now-showing', 'Action', 127, '2025-05-04 08:15:36'),
(43, 'Moana 2', '2025-05-04', 'PG', 'movie_681724f30a666.jpg', 'Moana sets sail once again for a magical new journey.', 'now-showing', 'Animation, Adventure', 95, '2025-05-04 08:27:31'),
(44, 'Despicable me 4', '2025-05-04', 'PG', 'movie_6817259d9acf9.jpg', 'Gru and the Minions face a new enemy in another hilarious mission.', 'now-showing', 'Animation, Comedy', 95, '2025-05-04 08:30:21'),
(45, 'Wicked', '2025-05-04', 'PG-13', 'movie_681725d11f2cf.jpg', 'A musical prequel that tells the untold story of the witches of Oz.', 'now-showing', 'Musical, Fantasy', 145, '2025-05-04 08:31:13'),
(46, 'Mufasa: The Lion King', '2025-05-04', 'PG', 'movie_681726f9b1904.jpg', 'A prequel exploring the origins of Mufasa.', 'now-showing', 'Animation, Drama', 118, '2025-05-04 08:36:09'),
(47, 'Dune: Part Two', '2025-05-04', 'PG-13', 'movie_681727358cddd.jpg', 'Paul Atreides continues his rise among the Fremen and seeks revenge.', 'now-showing', 'Sci-Fi, Action', 166, '2025-05-04 08:37:09'),
(48, 'Kung Fu Panda 4', '2025-05-04', 'PG', 'movie_68172782dd0b3.jpg', 'Po returns to train a new warrior and protect his legacy.', 'now-showing', 'Animation, Action', 94, '2025-05-04 08:38:26');

-- --------------------------------------------------------

--
-- Table structure for table `showtimes`
--

CREATE TABLE `showtimes` (
  `id` int(11) NOT NULL,
  `movie_id` int(11) NOT NULL,
  `theater_id` int(11) NOT NULL,
  `showdate` date NOT NULL,
  `showtime` time NOT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 50000.00,
  `available_seats` int(11) NOT NULL DEFAULT 96,
  `status` enum('active','full','cancelled') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `showtimes`
--

INSERT INTO `showtimes` (`id`, `movie_id`, `theater_id`, `showdate`, `showtime`, `price`, `available_seats`, `status`, `created_at`) VALUES
(80, 41, 1, '2025-05-25', '10:00:00', 50000.00, 93, 'active', '2025-05-04 15:55:40'),
(81, 41, 1, '2025-05-05', '13:30:00', 50000.00, 96, 'active', '2025-05-04 15:55:40'),
(82, 41, 1, '2025-05-05', '17:00:00', 55000.00, 93, 'active', '2025-05-04 15:55:40'),
(83, 41, 1, '2025-05-05', '20:30:00', 55000.00, 96, 'active', '2025-05-04 15:55:40'),
(84, 41, 2, '2025-05-05', '11:15:00', 50000.00, 96, 'active', '2025-05-04 15:55:40'),
(85, 41, 2, '2025-05-05', '14:45:00', 50000.00, 96, 'active', '2025-05-04 15:55:40'),
(86, 41, 2, '2025-05-05', '18:15:00', 55000.00, 96, 'active', '2025-05-04 15:55:40'),
(87, 41, 3, '2025-05-05', '12:30:00', 50000.00, 96, 'active', '2025-05-04 15:55:40'),
(88, 41, 3, '2025-05-05', '16:00:00', 50000.00, 96, 'active', '2025-05-04 15:55:40'),
(89, 41, 3, '2025-05-05', '19:30:00', 55000.00, 96, 'active', '2025-05-04 15:55:40'),
(90, 41, 4, '2025-05-05', '11:00:00', 50000.00, 96, 'active', '2025-05-04 15:55:40'),
(91, 41, 4, '2025-05-05', '15:30:00', 50000.00, 96, 'active', '2025-05-04 15:55:40'),
(92, 41, 5, '2025-05-05', '13:00:00', 50000.00, 96, 'active', '2025-05-04 15:55:40'),
(93, 41, 5, '2025-05-05', '17:30:00', 55000.00, 96, 'active', '2025-05-04 15:55:40'),
(94, 42, 6, '2025-05-05', '10:30:00', 50000.00, 96, 'active', '2025-05-04 15:55:40'),
(95, 42, 6, '2025-05-05', '14:00:00', 50000.00, 94, 'active', '2025-05-04 15:55:40'),
(96, 42, 6, '2025-05-05', '17:30:00', 55000.00, 96, 'active', '2025-05-04 15:55:40'),
(97, 42, 7, '2025-05-05', '11:45:00', 50000.00, 96, 'active', '2025-05-04 15:55:40'),
(98, 42, 7, '2025-05-05', '15:15:00', 50000.00, 96, 'active', '2025-05-04 15:55:40'),
(99, 42, 7, '2025-05-05', '18:45:00', 55000.00, 96, 'active', '2025-05-04 15:55:40'),
(100, 42, 8, '2025-05-05', '12:00:00', 50000.00, 96, 'active', '2025-05-04 15:55:40'),
(101, 42, 8, '2025-05-05', '16:30:00', 50000.00, 96, 'active', '2025-05-04 15:55:40'),
(102, 42, 8, '2025-05-05', '20:00:00', 55000.00, 96, 'active', '2025-05-04 15:55:40'),
(103, 42, 9, '2025-05-05', '11:30:00', 50000.00, 96, 'active', '2025-05-04 15:55:40'),
(104, 42, 9, '2025-05-05', '16:00:00', 50000.00, 96, 'active', '2025-05-04 15:55:40'),
(105, 42, 10, '2025-05-05', '13:30:00', 50000.00, 96, 'active', '2025-05-04 15:55:40'),
(106, 42, 10, '2025-05-05', '18:00:00', 55000.00, 96, 'active', '2025-05-04 15:55:40'),
(107, 43, 11, '2025-05-06', '10:15:00', 50000.00, 96, 'active', '2025-05-04 15:55:40'),
(108, 43, 11, '2025-05-06', '13:45:00', 50000.00, 96, 'active', '2025-05-04 15:55:40'),
(109, 43, 11, '2025-05-06', '17:15:00', 55000.00, 96, 'active', '2025-05-04 15:55:40'),
(110, 43, 12, '2025-05-06', '11:30:00', 50000.00, 96, 'active', '2025-05-04 15:55:40'),
(111, 43, 12, '2025-05-06', '15:00:00', 50000.00, 96, 'active', '2025-05-04 15:55:40'),
(112, 43, 12, '2025-05-06', '18:30:00', 55000.00, 96, 'active', '2025-05-04 15:55:40'),
(113, 43, 13, '2025-05-06', '12:15:00', 50000.00, 96, 'active', '2025-05-04 15:55:40'),
(114, 43, 13, '2025-05-06', '15:45:00', 50000.00, 96, 'active', '2025-05-04 15:55:40'),
(115, 43, 13, '2025-05-06', '19:15:00', 55000.00, 96, 'active', '2025-05-04 15:55:40'),
(116, 43, 14, '2025-05-06', '11:00:00', 50000.00, 96, 'active', '2025-05-04 15:55:40'),
(117, 43, 14, '2025-05-06', '15:30:00', 50000.00, 96, 'active', '2025-05-04 15:55:40'),
(118, 43, 15, '2025-05-06', '13:15:00', 50000.00, 96, 'active', '2025-05-04 15:55:40'),
(119, 43, 15, '2025-05-06', '17:45:00', 55000.00, 96, 'active', '2025-05-04 15:55:40'),
(120, 44, 16, '2025-05-06', '10:45:00', 50000.00, 96, 'active', '2025-05-04 15:55:40'),
(121, 44, 16, '2025-05-06', '14:15:00', 50000.00, 96, 'active', '2025-05-04 15:55:40'),
(122, 44, 16, '2025-05-06', '17:45:00', 55000.00, 96, 'active', '2025-05-04 15:55:40'),
(123, 44, 17, '2025-05-06', '11:15:00', 50000.00, 96, 'active', '2025-05-04 15:55:40'),
(124, 44, 17, '2025-05-06', '15:45:00', 50000.00, 96, 'active', '2025-05-04 15:55:40'),
(125, 44, 17, '2025-05-06', '19:15:00', 55000.00, 96, 'active', '2025-05-04 15:55:40'),
(126, 44, 18, '2025-05-06', '12:45:00', 50000.00, 96, 'active', '2025-05-04 15:55:40'),
(127, 44, 18, '2025-05-06', '16:15:00', 50000.00, 96, 'active', '2025-05-04 15:55:40'),
(128, 44, 18, '2025-05-06', '19:45:00', 55000.00, 96, 'active', '2025-05-04 15:55:40'),
(129, 44, 19, '2025-05-06', '11:45:00', 50000.00, 96, 'active', '2025-05-04 15:55:40'),
(130, 44, 19, '2025-05-06', '16:15:00', 50000.00, 96, 'active', '2025-05-04 15:55:40'),
(131, 44, 20, '2025-05-06', '13:45:00', 50000.00, 96, 'active', '2025-05-04 15:55:40'),
(132, 44, 20, '2025-05-06', '18:15:00', 55000.00, 96, 'active', '2025-05-04 15:55:40'),
(133, 45, 1, '2025-05-07', '10:15:00', 50000.00, 96, 'active', '2025-05-04 15:55:40'),
(134, 45, 1, '2025-05-07', '13:45:00', 50000.00, 96, 'active', '2025-05-04 15:55:40'),
(135, 45, 1, '2025-05-07', '17:15:00', 55000.00, 96, 'active', '2025-05-04 15:55:40'),
(136, 45, 2, '2025-05-07', '11:30:00', 50000.00, 96, 'active', '2025-05-04 15:55:40'),
(137, 45, 2, '2025-05-07', '15:00:00', 50000.00, 96, 'active', '2025-05-04 15:55:40'),
(138, 45, 2, '2025-05-07', '18:30:00', 55000.00, 96, 'active', '2025-05-04 15:55:40'),
(139, 45, 3, '2025-05-07', '12:15:00', 50000.00, 96, 'active', '2025-05-04 15:55:40'),
(140, 45, 3, '2025-05-07', '15:45:00', 50000.00, 96, 'active', '2025-05-04 15:55:40'),
(141, 45, 3, '2025-05-07', '19:15:00', 55000.00, 96, 'active', '2025-05-04 15:55:40'),
(142, 45, 4, '2025-05-07', '11:00:00', 50000.00, 96, 'active', '2025-05-04 15:55:40'),
(143, 45, 4, '2025-05-07', '15:30:00', 50000.00, 96, 'active', '2025-05-04 15:55:40'),
(144, 45, 5, '2025-05-07', '13:15:00', 50000.00, 96, 'active', '2025-05-04 15:55:40'),
(145, 45, 5, '2025-05-07', '17:45:00', 55000.00, 96, 'active', '2025-05-04 15:55:40'),
(146, 46, 6, '2025-05-07', '10:45:00', 50000.00, 96, 'active', '2025-05-04 15:55:40'),
(147, 46, 6, '2025-05-07', '14:15:00', 50000.00, 96, 'active', '2025-05-04 15:55:40'),
(148, 46, 6, '2025-05-07', '17:45:00', 55000.00, 93, 'active', '2025-05-04 15:55:40'),
(149, 46, 7, '2025-05-07', '11:15:00', 50000.00, 96, 'active', '2025-05-04 15:55:40'),
(150, 46, 7, '2025-05-07', '15:45:00', 50000.00, 96, 'active', '2025-05-04 15:55:40'),
(151, 46, 7, '2025-05-07', '19:15:00', 55000.00, 96, 'active', '2025-05-04 15:55:40'),
(152, 46, 8, '2025-05-07', '12:45:00', 50000.00, 96, 'active', '2025-05-04 15:55:40'),
(153, 46, 8, '2025-05-07', '16:15:00', 50000.00, 96, 'active', '2025-05-04 15:55:40'),
(154, 46, 8, '2025-05-07', '19:45:00', 55000.00, 96, 'active', '2025-05-04 15:55:40'),
(155, 46, 9, '2025-05-07', '11:45:00', 50000.00, 96, 'active', '2025-05-04 15:55:40'),
(156, 46, 9, '2025-05-07', '16:15:00', 50000.00, 96, 'active', '2025-05-04 15:55:40'),
(157, 46, 10, '2025-05-07', '13:45:00', 50000.00, 96, 'active', '2025-05-04 15:55:40'),
(158, 46, 10, '2025-05-07', '18:15:00', 55000.00, 96, 'active', '2025-05-04 15:55:40'),
(159, 47, 11, '2025-05-08', '10:30:00', 50000.00, 96, 'active', '2025-05-04 15:55:40'),
(160, 47, 11, '2025-05-08', '14:00:00', 50000.00, 96, 'active', '2025-05-04 15:55:40'),
(161, 47, 11, '2025-05-08', '17:30:00', 55000.00, 96, 'active', '2025-05-04 15:55:40'),
(162, 47, 12, '2025-05-08', '11:45:00', 50000.00, 96, 'active', '2025-05-04 15:55:40'),
(163, 47, 12, '2025-05-08', '15:15:00', 50000.00, 96, 'active', '2025-05-04 15:55:40'),
(164, 47, 12, '2025-05-08', '18:45:00', 55000.00, 96, 'active', '2025-05-04 15:55:40'),
(165, 47, 13, '2025-05-08', '12:00:00', 50000.00, 96, 'active', '2025-05-04 15:55:40'),
(166, 47, 13, '2025-05-08', '16:30:00', 50000.00, 96, 'active', '2025-05-04 15:55:40'),
(167, 47, 13, '2025-05-08', '20:00:00', 55000.00, 96, 'active', '2025-05-04 15:55:40'),
(168, 47, 14, '2025-05-08', '11:30:00', 50000.00, 96, 'active', '2025-05-04 15:55:40'),
(169, 47, 14, '2025-05-08', '16:00:00', 50000.00, 96, 'active', '2025-05-04 15:55:40'),
(170, 47, 15, '2025-05-08', '13:30:00', 50000.00, 96, 'active', '2025-05-04 15:55:40'),
(171, 47, 15, '2025-05-08', '18:00:00', 55000.00, 96, 'active', '2025-05-04 15:55:40'),
(172, 48, 16, '2025-05-08', '10:00:00', 50000.00, 96, 'active', '2025-05-04 15:55:40'),
(173, 48, 16, '2025-05-08', '13:30:00', 50000.00, 96, 'active', '2025-05-04 15:55:40'),
(174, 48, 16, '2025-05-08', '17:00:00', 55000.00, 96, 'active', '2025-05-04 15:55:40'),
(175, 48, 16, '2025-05-08', '20:30:00', 55000.00, 96, 'active', '2025-05-04 15:55:40'),
(176, 48, 17, '2025-05-08', '11:15:00', 50000.00, 96, 'active', '2025-05-04 15:55:40'),
(177, 48, 17, '2025-05-08', '14:45:00', 50000.00, 96, 'active', '2025-05-04 15:55:40'),
(178, 48, 17, '2025-05-08', '18:15:00', 55000.00, 96, 'active', '2025-05-04 15:55:40'),
(179, 48, 18, '2025-05-08', '12:30:00', 50000.00, 96, 'active', '2025-05-04 15:55:40'),
(180, 48, 18, '2025-05-08', '16:00:00', 50000.00, 96, 'active', '2025-05-04 15:55:40'),
(181, 48, 18, '2025-05-08', '19:30:00', 55000.00, 96, 'active', '2025-05-04 15:55:40'),
(182, 48, 19, '2025-05-08', '11:00:00', 50000.00, 96, 'active', '2025-05-04 15:55:40'),
(183, 48, 19, '2025-05-08', '15:30:00', 50000.00, 96, 'active', '2025-05-04 15:55:40'),
(184, 48, 20, '2025-05-08', '13:00:00', 50000.00, 96, 'active', '2025-05-04 15:55:40'),
(185, 48, 20, '2025-05-08', '17:30:00', 55000.00, 96, 'active', '2025-05-04 15:55:40'),
(186, 41, 1, '2025-05-09', '11:00:00', 55000.00, 96, 'active', '2025-05-04 15:55:40'),
(187, 41, 1, '2025-05-09', '15:30:00', 55000.00, 96, 'active', '2025-05-04 15:55:40'),
(188, 41, 1, '2025-05-09', '20:00:00', 60000.00, 96, 'active', '2025-05-04 15:55:40'),
(189, 42, 6, '2025-05-09', '12:30:00', 55000.00, 96, 'active', '2025-05-04 15:55:40'),
(190, 42, 6, '2025-05-09', '17:00:00', 55000.00, 96, 'active', '2025-05-04 15:55:40'),
(191, 42, 6, '2025-05-09', '21:30:00', 60000.00, 96, 'active', '2025-05-04 15:55:40'),
(192, 43, 11, '2025-05-09', '13:00:00', 55000.00, 96, 'active', '2025-05-04 15:55:40'),
(193, 43, 11, '2025-05-09', '17:30:00', 55000.00, 96, 'active', '2025-05-04 15:55:40'),
(194, 43, 11, '2025-05-09', '22:00:00', 60000.00, 96, 'active', '2025-05-04 15:55:40'),
(195, 44, 16, '2025-05-09', '14:00:00', 55000.00, 96, 'active', '2025-05-04 15:55:40'),
(196, 44, 16, '2025-05-09', '18:30:00', 55000.00, 96, 'active', '2025-05-04 15:55:40'),
(197, 44, 16, '2025-05-09', '22:30:00', 60000.00, 96, 'active', '2025-05-04 15:55:40'),
(198, 45, 5, '2025-05-10', '10:00:00', 60000.00, 96, 'active', '2025-05-04 15:55:40'),
(199, 45, 5, '2025-05-10', '14:30:00', 60000.00, 96, 'active', '2025-05-04 15:55:40'),
(200, 45, 5, '2025-05-10', '19:00:00', 65000.00, 96, 'active', '2025-05-04 15:55:40'),
(201, 46, 10, '2025-05-10', '11:30:00', 60000.00, 96, 'active', '2025-05-04 15:55:40'),
(202, 46, 10, '2025-05-10', '16:00:00', 60000.00, 96, 'active', '2025-05-04 15:55:40'),
(203, 46, 10, '2025-05-10', '20:30:00', 65000.00, 96, 'active', '2025-05-04 15:55:40'),
(204, 47, 15, '2025-05-10', '12:00:00', 60000.00, 96, 'active', '2025-05-04 15:55:40'),
(205, 47, 15, '2025-05-10', '16:30:00', 60000.00, 96, 'active', '2025-05-04 15:55:40'),
(206, 47, 15, '2025-05-10', '21:00:00', 65000.00, 96, 'active', '2025-05-04 15:55:40'),
(207, 48, 20, '2025-05-10', '13:00:00', 60000.00, 96, 'active', '2025-05-04 15:55:40'),
(208, 48, 20, '2025-05-10', '17:30:00', 60000.00, 96, 'active', '2025-05-04 15:55:40'),
(209, 48, 20, '2025-05-10', '22:00:00', 65000.00, 96, 'active', '2025-05-04 15:55:40'),
(210, 41, 3, '2025-05-11', '10:30:00', 60000.00, 96, 'active', '2025-05-04 15:55:40'),
(211, 42, 7, '2025-05-11', '11:00:00', 60000.00, 96, 'active', '2025-05-04 15:55:40'),
(212, 43, 12, '2025-05-11', '12:30:00', 60000.00, 96, 'active', '2025-05-04 15:55:40'),
(213, 44, 17, '2025-05-11', '13:00:00', 60000.00, 96, 'active', '2025-05-04 15:55:40'),
(214, 45, 4, '2025-05-11', '14:30:00', 60000.00, 96, 'active', '2025-05-04 15:55:40'),
(215, 46, 8, '2025-05-11', '15:00:00', 60000.00, 96, 'active', '2025-05-04 15:55:40'),
(216, 47, 13, '2025-05-11', '16:30:00', 60000.00, 96, 'active', '2025-05-04 15:55:40'),
(217, 48, 18, '2025-05-11', '17:00:00', 60000.00, 96, 'active', '2025-05-04 15:55:40'),
(218, 41, 3, '2025-05-11', '19:00:00', 65000.00, 96, 'active', '2025-05-04 15:55:40'),
(219, 43, 12, '2025-05-11', '19:30:00', 65000.00, 96, 'active', '2025-05-04 15:55:40'),
(220, 45, 4, '2025-05-11', '20:00:00', 65000.00, 96, 'active', '2025-05-04 15:55:40'),
(221, 47, 13, '2025-05-11', '20:30:00', 65000.00, 96, 'active', '2025-05-04 15:55:40');

-- --------------------------------------------------------

--
-- Table structure for table `theaters`
--

CREATE TABLE `theaters` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `location` varchar(255) NOT NULL,
  `city` varchar(50) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `special_tag` varchar(20) DEFAULT NULL,
  `facilities` longtext DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `total_seats` int(11) NOT NULL DEFAULT 96,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `theaters`
--

INSERT INTO `theaters` (`id`, `name`, `location`, `city`, `address`, `phone`, `special_tag`, `facilities`, `active`, `total_seats`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Cinema XXI Grand Indonesia', 'Grand Indonesia Mall Lt. 8', 'Jakarta', 'Jl. M.H. Thamrin No.1', '02123456789', 'IMAX', 'Premium seats, Dolby Atmos, 4K Projection, Food court, VIP lounge', 1, 200, 'active', '2025-04-29 19:27:46', '2025-04-29 19:27:46'),
(2, 'CGV Plaza Senayan', 'Plaza Senayan Lt. 5', 'Jakarta', 'Jl. Asia Afrika No.8', '02123456788', 'Regular', 'Standard seats, Dolby Digital, Food stand', 1, 150, 'active', '2025-04-29 19:27:46', '2025-04-29 19:27:46'),
(3, 'Cinepolis Kota Kasablanka', 'Kota Kasablanka Lt. 3', 'Jakarta', 'Jl. Casablanca Raya Kav. 88', '02123456787', '4DX', 'Motion seats, Environmental effects, Dolby Atmos, Premium food', 1, 120, 'active', '2025-04-29 19:27:46', '2025-04-29 19:27:46'),
(4, 'Cinema XXI Pondok Indah Mall', 'Pondok Indah Mall Lt. 2', 'Jakarta', 'Jl. Metro Pondok Indah', '02123456786', 'Regular', 'Standard seats, Snack bar, Digital projection', 1, 180, 'active', '2025-04-29 19:27:46', '2025-04-29 19:27:46'),
(5, 'CGV Central Park', 'Central Park Mall Lt. 3', 'Jakarta', 'Jl. Letjen S. Parman Kav. 28', '02123456785', 'SCREENX', '270-degree screen, Recliner seats, Premium sound, VIP service', 1, 160, 'active', '2025-04-29 19:27:46', '2025-04-29 19:27:46'),
(6, 'Cinema XXI Paris Van Java', 'Paris Van Java Mall Lt. 2', 'Bandung', 'Jl. Sukajadi No. 137-139', '02223456784', 'Regular', 'Standard seats, Concession stand', 1, 140, 'active', '2025-04-29 19:27:46', '2025-04-29 19:27:46'),
(7, 'CGV Cihampelas Walk', 'Cihampelas Walk Lt. 3', 'Bandung', 'Jl. Cihampelas No. 160', '02223456783', 'DOLBY', 'Dolby Atmos, Recliner seats, Premium concessions', 1, 130, 'active', '2025-04-29 19:27:46', '2025-04-29 19:27:46'),
(8, 'Cinema XXI Trans Studio Mall', 'Trans Studio Mall Lt. 4', 'Bandung', 'Jl. Gatot Subroto No. 289', '02223456782', 'IMAX', 'IMAX projection, Premium sound, Deluxe seats', 1, 200, 'active', '2025-04-29 19:27:46', '2025-04-29 19:27:46'),
(9, 'Cinepolis BEC Mall', 'Bandung Electronic Center Lt. 3A', 'Bandung', 'Jl. Purnawarman No. 13-15', '02223456781', 'Regular', 'Standard seats, Digital sound, Snack stand', 1, 120, 'active', '2025-04-29 19:27:46', '2025-04-29 19:27:46'),
(10, 'CGV Festival Citylink', 'Bandung', 'Bandung', 'Jl. Peta No. 241', '02223456780', 'Premium', 'Recliner seats, Dolby sound, Premium food', 1, 100, 'active', '2025-04-29 19:27:46', '2025-05-03 17:08:39'),
(11, 'Cinema XXI Tunjungan Plaza', 'Tunjungan Plaza Lt. 5', 'Surabaya', 'Jl. Basuki Rahmat No. 8-12', '03123456789', 'IMAX', 'IMAX screen, Premium sound, Luxury seating', 1, 180, 'active', '2025-04-29 19:27:46', '2025-04-29 19:27:46'),
(12, 'CGV Grand City', 'Grand City Mall Lt. 3', 'Surabaya', 'Jl. Walikota Mustajab No. 1', '03123456788', 'Gold Class', 'Recliner seats, Waiter service, Premium experience', 1, 100, 'active', '2025-04-29 19:27:46', '2025-04-29 19:27:46'),
(13, 'Cinema XXI Pakuwon Mall', 'Pakuwon Mall Lt. 3', 'Surabaya', 'Jl. Puncak Indah Lontar No. 2', '03123456787', 'Regular', 'Standard seats, Digital projection, Concession stand', 1, 150, 'active', '2025-04-29 19:27:46', '2025-04-29 19:27:46'),
(14, 'Cinepolis Royal Plaza', 'Royal Plaza Lt. 4', 'Surabaya', 'Jl. Ahmad Yani No. 16-18', '03123456786', '4DX', 'Motion seats, Environmental effects, Premium experience', 1, 120, 'active', '2025-04-29 19:27:46', '2025-04-29 19:27:46'),
(15, 'CGV Marvel City', 'Marvel City Mall Lt. 2', 'Surabaya', 'Jl. Ngagel No. 123', '03123456785', 'Regular', 'Standard seats, Digital sound, Snack bar', 1, 140, 'active', '2025-04-29 19:27:46', '2025-04-29 19:27:46'),
(16, 'Cinema XXI Beachwalk', 'Beachwalk Shopping Center Lt. 3', 'Bali', 'Jl. Pantai Kuta', '03613456789', 'Regular', 'Standard seats, Digital projection, Concession stand', 1, 130, 'active', '2025-04-29 19:27:46', '2025-04-29 19:27:46'),
(17, 'Cinepolis Bali Galeria', 'Bali Galeria Mall Lt. 4', 'Bali', 'Jl. Bypass Ngurah Rai', '03613456788', 'IMAX', 'IMAX screen, Premium sound, Luxury seating', 1, 180, 'active', '2025-04-29 19:27:46', '2025-04-29 19:27:46'),
(18, 'CGV Level 21', 'Level 21 Mall Lt. 5', 'Bali', 'Jl. Teuku Umar No. 1', '03613456787', 'Regular', 'Standard seats, Digital sound, Snack bar', 1, 140, 'active', '2025-04-29 19:27:46', '2025-04-29 19:27:46'),
(19, 'Cinema XXI Park23', 'Park23 Entertainment Center Lt. 2', 'Bali', 'Jl. Kediri, Tuban', '03613456786', 'DOLBY', 'Dolby Atmos, Premium seats, Enhanced concessions', 1, 150, 'active', '2025-04-29 19:27:46', '2025-04-29 19:27:46'),
(20, 'CGV Lippo Mall Kuta', 'Lippo Mall Kuta Lt. 3', 'Bali', 'Jl. Kartika Plaza', '03613456785', 'Gold Class', 'Luxury recliners, Waiter service, Premium experience', 1, 100, 'active', '2025-04-29 19:27:46', '2025-04-29 19:27:46');

-- --------------------------------------------------------

--
-- Table structure for table `tickets`
--

CREATE TABLE `tickets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `movie_id` int(11) NOT NULL,
  `showtime_id` int(11) NOT NULL,
  `cinema_id` int(11) NOT NULL,
  `seat_numbers` varchar(255) NOT NULL,
  `quantity` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `booking_date` int(11) NOT NULL,
  `booking_code` int(11) NOT NULL,
  `status` text NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `name` varchar(100) NOT NULL,
  `firstname` varchar(50) NOT NULL DEFAULT '...',
  `lastname` varchar(50) NOT NULL DEFAULT '...',
  `birthdate` date DEFAULT '0000-00-00',
  `gender` text NOT NULL DEFAULT '...',
  `phone_number` varchar(20) DEFAULT NULL,
  `address` varchar(255) NOT NULL DEFAULT '...',
  `role` enum('admin','customer') NOT NULL DEFAULT 'customer',
  `loyalty_points` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `name`, `firstname`, `lastname`, `birthdate`, `gender`, `phone_number`, `address`, `role`, `loyalty_points`, `created_at`, `updated_at`) VALUES
(6, 'Admin1', 'Admin123@gmail.com', '$2y$10$QU1/R2k4uetF5YZjiBo9neO65QkW2fzUO6y1vl5lSOvElOakwCWdq', 'muhammad nabil', '', '', '0000-00-00', '', '0', '0', 'admin', 0, '2025-04-28 11:45:30', '2025-04-28 11:45:30'),
(7, 'Cust1', 'Cust1@gmail.com', '$2y$10$QQlp0KpybZL3dHeGU/ejaeul8XzVeXnqcH2QN966ujqiTqLPWyrDK', 'indraprasta', 'Nabil', 'Indrapasta', '2006-01-21', 'male', '81295221412', 'Kranggan', 'customer', 0, '2025-04-28 12:31:40', '2025-05-04 09:47:02'),
(9, 'Nietz', 'niet@gmail.com', '$2y$10$Vtsxz1dMwzKUe1ghOBPzRuP3DCqbSHzTFXf3wGwELGQnz98kwMNh2', 'Nietzsche', 'Nietz', 'Jagok', '2006-05-01', 'male', '89500119966', 'Jl. Jababeka, Cikarang', 'customer', 0, '2025-05-04 16:10:59', '2025-05-04 16:13:33');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `showtime_id` (`showtime_id`);

--
-- Indexes for table `booking_seats`
--
ALTER TABLE `booking_seats`
  ADD PRIMARY KEY (`id`),
  ADD KEY `booking_id` (`booking_id`);

--
-- Indexes for table `movies`
--
ALTER TABLE `movies`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `showtimes`
--
ALTER TABLE `showtimes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `movie_id` (`movie_id`),
  ADD KEY `theater_id` (`theater_id`);

--
-- Indexes for table `theaters`
--
ALTER TABLE `theaters`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tickets`
--
ALTER TABLE `tickets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `movie_id` (`movie_id`),
  ADD KEY `showtime_id` (`showtime_id`),
  ADD KEY `tickets_ibfk_1` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `booking_seats`
--
ALTER TABLE `booking_seats`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=59;

--
-- AUTO_INCREMENT for table `movies`
--
ALTER TABLE `movies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT for table `showtimes`
--
ALTER TABLE `showtimes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=222;

--
-- AUTO_INCREMENT for table `theaters`
--
ALTER TABLE `theaters`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `tickets`
--
ALTER TABLE `tickets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`showtime_id`) REFERENCES `showtimes` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `booking_seats`
--
ALTER TABLE `booking_seats`
  ADD CONSTRAINT `booking_seats_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `showtimes`
--
ALTER TABLE `showtimes`
  ADD CONSTRAINT `showtimes_ibfk_1` FOREIGN KEY (`movie_id`) REFERENCES `movies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `showtimes_ibfk_2` FOREIGN KEY (`theater_id`) REFERENCES `theaters` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tickets`
--
ALTER TABLE `tickets`
  ADD CONSTRAINT `tickets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tickets_ibfk_2` FOREIGN KEY (`movie_id`) REFERENCES `movies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tickets_ibfk_3` FOREIGN KEY (`showtime_id`) REFERENCES `showtimes` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
