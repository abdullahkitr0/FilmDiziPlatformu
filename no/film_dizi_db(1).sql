-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 29, 2025 at 12:51 PM
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
-- Database: `film_dizi_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_logs`
--

CREATE TABLE `admin_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(50) NOT NULL,
  `details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`details`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bulk_content_logs`
--

CREATE TABLE `bulk_content_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `total_rows` int(11) NOT NULL DEFAULT 0,
  `processed_rows` int(11) NOT NULL DEFAULT 0,
  `success_count` int(11) NOT NULL DEFAULT 0,
  `error_count` int(11) NOT NULL DEFAULT 0,
  `status` enum('pending','processing','completed','failed','partial') NOT NULL DEFAULT 'pending',
  `error_log` text DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `slug` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `slug`, `description`, `created_at`, `updated_at`) VALUES
(1, 'Adventure', 'adventure', NULL, '2025-01-29 10:46:20', '2025-01-29 10:46:20'),
(2, 'Drama', 'drama', NULL, '2025-01-29 10:46:20', '2025-01-29 10:46:20'),
(3, 'Fantasy', 'fantasy', NULL, '2025-01-29 10:46:20', '2025-01-29 10:46:20'),
(4, 'Crime', 'crime', NULL, '2025-01-29 10:55:31', '2025-01-29 10:55:31'),
(5, 'Thriller', 'thriller', NULL, '2025-01-29 10:55:31', '2025-01-29 10:55:31'),
(6, 'Comedy', 'comedy', NULL, '2025-01-29 10:56:23', '2025-01-29 10:56:23');

-- --------------------------------------------------------

--
-- Table structure for table `comments`
--

CREATE TABLE `comments` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `content_id` int(11) NOT NULL,
  `comment` text NOT NULL,
  `rating` int(11) DEFAULT NULL CHECK (`rating` >= 1 and `rating` <= 10),
  `is_approved` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `contents`
--

CREATE TABLE `contents` (
  `id` int(11) NOT NULL,
  `imdb_id` varchar(20) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `original_title` varchar(255) DEFAULT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `type` enum('movie','series') NOT NULL,
  `release_year` year(4) DEFAULT NULL,
  `imdb_rating` decimal(3,1) DEFAULT NULL,
  `runtime` varchar(50) DEFAULT NULL,
  `language` varchar(100) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `director` varchar(255) DEFAULT NULL,
  `writer` text DEFAULT NULL,
  `awards` text DEFAULT NULL,
  `cast` text DEFAULT NULL,
  `poster_url` varchar(255) DEFAULT NULL,
  `trailer_url` varchar(255) DEFAULT NULL,
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` text DEFAULT NULL,
  `meta_keywords` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `contents`
--

INSERT INTO `contents` (`id`, `imdb_id`, `title`, `original_title`, `slug`, `description`, `type`, `release_year`, `imdb_rating`, `runtime`, `language`, `country`, `director`, `writer`, `awards`, `cast`, `poster_url`, `trailer_url`, `meta_title`, `meta_description`, `meta_keywords`, `created_at`, `updated_at`) VALUES
(1, 'tt0167260', 'The Lord of the Rings: The Return of the King', 'The Lord of the Rings: The Return of the King', 'the-lord-of-the-rings-the-return-of-the-king', 'Gandalf and Aragorn lead the World of Men against Sauron\'s army to draw his gaze from Frodo and Sam as they approach Mount Doom with the One Ring.', 'movie', '2003', 9.0, '201 min', 'English, Quenya, Old English, Sindarin', 'New Zealand, United States', 'Peter Jackson', 'J.R.R. Tolkien, Fran Walsh, Philippa Boyens', 'Won 11 Oscars. 215 wins & 124 nominations total', NULL, 'https://m.media-amazon.com/images/M/MV5BMTZkMjBjNWMtZGI5OC00MGU0LTk4ZTItODg2NWM3NTVmNWQ4XkEyXkFqcGc@._V1_SX300.jpg', NULL, NULL, NULL, NULL, '2025-01-29 10:46:20', '2025-01-29 10:46:20'),
(2, 'tt0903747', 'Breaking Bad', 'Breaking Bad', 'breaking-bad', 'A chemistry teacher diagnosed with inoperable lung cancer turns to manufacturing and selling methamphetamine with a former student to secure his family\'s future.', 'series', '2008', 9.5, '49 min', 'English, Spanish', 'United States', 'N/A', 'Vince Gilligan', 'Won 16 Primetime Emmys. 169 wins & 269 nominations total', NULL, 'https://m.media-amazon.com/images/M/MV5BMzU5ZGYzNmQtMTdhYy00OGRiLTg0NmQtYjVjNzliZTg1ZGE4XkEyXkFqcGc@._V1_SX300.jpg', NULL, NULL, NULL, NULL, '2025-01-29 10:55:31', '2025-01-29 10:55:31'),
(3, 'tt0386676', 'The Office', 'The Office', 'the-office', 'A mockumentary on a group of typical office workers, where the workday consists of ego clashes, inappropriate behavior, tedium and romance.', 'series', '2005', 9.0, '22 min', 'English, Spanish, German, French', 'United States', 'N/A', 'Greg Daniels, Ricky Gervais, Stephen Merchant', 'Won 5 Primetime Emmys. 59 wins & 211 nominations total', NULL, 'https://m.media-amazon.com/images/M/MV5BZjQwYzBlYzUtZjhhOS00ZDQ0LWE0NzAtYTk4MjgzZTNkZWEzXkEyXkFqcGc@._V1_SX300.jpg', NULL, NULL, NULL, NULL, '2025-01-29 10:56:23', '2025-01-29 10:56:23'),
(4, 'tt0120737', 'The Lord of the Rings: The Fellowship of the Ring', 'The Lord of the Rings: The Fellowship of the Ring', 'the-lord-of-the-rings-the-fellowship-of-the-ring', 'A meek Hobbit from the Shire and eight companions set out on a journey to destroy the powerful One Ring and save Middle-earth from the Dark Lord Sauron.', 'movie', '2001', 8.9, '178 min', 'English, Sindarin', 'New Zealand, United States, United Kingdom', 'Peter Jackson', 'J.R.R. Tolkien, Fran Walsh, Philippa Boyens', 'Won 4 Oscars. 125 wins & 126 nominations total', NULL, 'https://m.media-amazon.com/images/M/MV5BNzIxMDQ2YTctNDY4MC00ZTRhLTk4ODQtMTVlOWY4NTdiYmMwXkEyXkFqcGc@._V1_SX300.jpg', NULL, NULL, NULL, NULL, '2025-01-29 10:58:40', '2025-01-29 10:58:40'),
(5, 'tt0068646', 'The Godfather', 'The Godfather', 'the-godfather', 'The aging patriarch of an organized crime dynasty transfers control of his clandestine empire to his reluctant son.', 'movie', '1972', 9.2, '175 min', 'English, Italian, Latin', 'United States', 'Francis Ford Coppola', 'Mario Puzo, Francis Ford Coppola', 'Won 3 Oscars. 31 wins & 31 nominations total', NULL, 'https://m.media-amazon.com/images/M/MV5BNGEwYjgwOGQtYjg5ZS00Njc1LTk2ZGEtM2QwZWQ2NjdhZTE5XkEyXkFqcGc@._V1_SX300.jpg', NULL, NULL, NULL, NULL, '2025-01-29 10:58:47', '2025-01-29 10:58:47');

-- --------------------------------------------------------

--
-- Table structure for table `content_actors`
--

CREATE TABLE `content_actors` (
  `id` int(11) NOT NULL,
  `content_id` int(11) NOT NULL,
  `actor_name` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `content_actors`
--

INSERT INTO `content_actors` (`id`, `content_id`, `actor_name`, `created_at`) VALUES
(1, 1, 'Elijah Wood', '2025-01-29 10:46:21'),
(2, 1, 'Viggo Mortensen', '2025-01-29 10:46:21'),
(3, 1, 'Ian McKellen', '2025-01-29 10:46:21'),
(4, 2, 'Bryan Cranston', '2025-01-29 10:55:31'),
(5, 2, 'Aaron Paul', '2025-01-29 10:55:31'),
(6, 2, 'Anna Gunn', '2025-01-29 10:55:31'),
(7, 3, 'Steve Carell', '2025-01-29 10:56:23'),
(8, 3, 'Jenna Fischer', '2025-01-29 10:56:23'),
(9, 3, 'John Krasinski', '2025-01-29 10:56:23'),
(10, 4, 'Elijah Wood', '2025-01-29 10:58:40'),
(11, 4, 'Ian McKellen', '2025-01-29 10:58:40'),
(12, 4, 'Orlando Bloom', '2025-01-29 10:58:40'),
(13, 5, 'Marlon Brando', '2025-01-29 10:58:47'),
(14, 5, 'Al Pacino', '2025-01-29 10:58:47'),
(15, 5, 'James Caan', '2025-01-29 10:58:47');

-- --------------------------------------------------------

--
-- Table structure for table `content_categories`
--

CREATE TABLE `content_categories` (
  `content_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `content_categories`
--

INSERT INTO `content_categories` (`content_id`, `category_id`, `created_at`) VALUES
(1, 1, '2025-01-29 10:46:20'),
(1, 2, '2025-01-29 10:46:20'),
(1, 3, '2025-01-29 10:46:20'),
(2, 2, '2025-01-29 10:55:31'),
(2, 4, '2025-01-29 10:55:31'),
(2, 5, '2025-01-29 10:55:31'),
(3, 6, '2025-01-29 10:56:23'),
(4, 1, '2025-01-29 10:58:40'),
(4, 2, '2025-01-29 10:58:40'),
(4, 3, '2025-01-29 10:58:40'),
(5, 2, '2025-01-29 10:58:47'),
(5, 4, '2025-01-29 10:58:47');

-- --------------------------------------------------------

--
-- Table structure for table `content_platform_relations`
--

CREATE TABLE `content_platform_relations` (
  `id` int(11) NOT NULL,
  `content_id` int(11) NOT NULL,
  `platform_id` int(11) NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `content_views`
--

CREATE TABLE `content_views` (
  `id` int(11) NOT NULL,
  `content_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `visitor_ip` varchar(45) DEFAULT NULL,
  `viewed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `content_views`
--

INSERT INTO `content_views` (`id`, `content_id`, `user_id`, `visitor_ip`, `viewed_at`) VALUES
(1, 1, NULL, '127.0.0.1', '2025-01-29 10:55:47'),
(2, 2, NULL, '127.0.0.1', '2025-01-29 10:55:57'),
(3, 2, NULL, '127.0.0.1', '2025-01-29 10:56:26'),
(4, 3, NULL, '127.0.0.1', '2025-01-29 10:56:31'),
(5, 5, NULL, '127.0.0.1', '2025-01-29 10:59:12'),
(6, 5, NULL, '127.0.0.1', '2025-01-29 10:59:16'),
(7, 5, NULL, '127.0.0.1', '2025-01-29 10:59:22');

-- --------------------------------------------------------

--
-- Table structure for table `media_library`
--

CREATE TABLE `media_library` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_type` varchar(50) NOT NULL,
  `file_size` int(11) NOT NULL,
  `mime_type` varchar(100) NOT NULL,
  `dimensions` varchar(20) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `used` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `platforms`
--

CREATE TABLE `platforms` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `website` varchar(255) NOT NULL,
  `logo_url` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `role_permissions`
--

CREATE TABLE `role_permissions` (
  `id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  `permission_name` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `role_permissions`
--

INSERT INTO `role_permissions` (`id`, `role_id`, `permission_name`, `created_at`) VALUES
(1, 1, 'manage_users', '2025-01-29 11:36:47'),
(2, 1, 'manage_roles', '2025-01-29 11:36:47'),
(3, 1, 'manage_content', '2025-01-29 11:36:47'),
(4, 1, 'manage_settings', '2025-01-29 11:36:47'),
(5, 2, 'moderate_comments', '2025-01-29 11:36:47');

-- --------------------------------------------------------

--
-- Table structure for table `site_settings`
--

CREATE TABLE `site_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_description` text DEFAULT NULL,
  `is_public` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `site_settings`
--

INSERT INTO `site_settings` (`id`, `setting_key`, `setting_value`, `setting_description`, `is_public`, `created_at`, `updated_at`, `updated_by`) VALUES
(1, 'site_name', 'Film & Dizi Platformu', 'Site adı', 1, '2025-01-29 09:40:49', '2025-01-29 09:40:49', NULL),
(2, 'site_description', 'Film ve dizi önerileri, incelemeler ve daha fazlası', 'Site açıklaması', 1, '2025-01-29 09:40:49', '2025-01-29 09:40:49', NULL),
(3, 'user_registration', '1', 'Kullanıcı kaydı açık/kapalı', 0, '2025-01-29 09:40:49', '2025-01-29 09:40:49', NULL),
(4, 'maintenance_mode', '0', 'Bakım modu açık/kapalı', 0, '2025-01-29 09:40:49', '2025-01-29 09:40:49', NULL),
(5, 'default_lang', 'tr', 'Varsayılan dil', 1, '2025-01-29 09:40:49', '2025-01-29 09:40:49', NULL),
(6, 'items_per_page', '12', 'Sayfa başına gösterilecek içerik sayısı', 0, '2025-01-29 09:40:49', '2025-01-29 09:40:49', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `is_admin` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `avatar_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `is_admin`, `is_active`, `avatar_url`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'admin@admin.com', '$2y$10$JYl9ec42Jf5sX5sRBG/EEuNW8PAUiqNtZOiDNqKrr7I6Cp4Yn7MrC', 1, 1, NULL, '2025-01-29 09:44:54', '2025-01-29 09:48:58'),
(3, 'admin1', 'admin1@example.com', '$2y$10$vk9O/oABBob02ccvxu0K3.ZBNCWxonqdI9o.KItSF3UwMgxXuLoTm', 1, 1, NULL, '2025-01-29 10:43:53', '2025-01-29 10:43:53'),
(4, 'test', 'test@test.com', '$2y$10$tC1ldkPiFSIyAbTCLQ6tA.R8a5uIkawseozNJWLMWYsez.5KrM5he', 1, 1, NULL, '2025-01-29 11:40:28', '2025-01-29 11:40:28');

-- --------------------------------------------------------

--
-- Table structure for table `user_activities`
--

CREATE TABLE `user_activities` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `activity_type` varchar(50) NOT NULL,
  `activity_details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`activity_details`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_activities`
--

INSERT INTO `user_activities` (`id`, `user_id`, `activity_type`, `activity_details`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, NULL, 'user_registered', '{\"username\":\"admin\",\"email\":\"admin@admin.com\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 09:44:54'),
(2, NULL, 'login_success', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:05:26'),
(3, 3, 'login_success', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 11:37:36');

-- --------------------------------------------------------

--
-- Table structure for table `user_bans`
--

CREATE TABLE `user_bans` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `banned_by` int(11) NOT NULL,
  `reason` text NOT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `removed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `removed_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_roles`
--

CREATE TABLE `user_roles` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_roles`
--

INSERT INTO `user_roles` (`id`, `name`, `description`, `created_at`) VALUES
(1, 'admin', 'Tam yetkili yönetici', '2025-01-29 09:40:49'),
(2, 'moderator', 'İçerik moderatörü', '2025-01-29 09:40:49'),
(3, 'user', 'Normal kullanıcı', '2025-01-29 09:40:49');

-- --------------------------------------------------------

--
-- Table structure for table `user_role_relations`
--

CREATE TABLE `user_role_relations` (
  `user_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_role_relations`
--

INSERT INTO `user_role_relations` (`user_id`, `role_id`, `created_at`) VALUES
(1, 1, '2025-01-29 09:44:54');

-- --------------------------------------------------------

--
-- Table structure for table `visitor_stats`
--

CREATE TABLE `visitor_stats` (
  `id` int(11) NOT NULL,
  `page_url` varchar(255) NOT NULL,
  `visitor_ip` varchar(45) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `visited_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `visitor_stats`
--

INSERT INTO `visitor_stats` (`id`, `page_url`, `visitor_ip`, `user_id`, `user_agent`, `visited_at`) VALUES
(1, '/4/', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 09:41:42'),
(2, '/4/', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 09:41:45'),
(3, '/4/', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 09:43:45'),
(4, '/4/', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 09:43:52'),
(5, '/4/admin/', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 09:43:59'),
(6, '/4/login.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 09:43:59'),
(7, '/4/', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 09:44:02'),
(8, '/4/index.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 09:44:04'),
(9, '/4/profile.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 09:44:08'),
(10, '/4/login.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 09:44:08'),
(11, '/4/', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 09:44:10'),
(12, '/4/top-rated.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 09:44:24'),
(13, '/4/login.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 09:44:32'),
(14, '/4/register.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 09:44:39'),
(15, '/4/register.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 09:44:54'),
(16, '/4/index.php', '127.0.0.1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 09:44:54'),
(17, '/4/index.php', '127.0.0.1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 09:48:21'),
(18, '/4/', '127.0.0.1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 09:48:25'),
(19, '/4/search.php', '127.0.0.1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 09:48:28'),
(20, '/4/', '127.0.0.1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 09:48:30'),
(21, '/4/search.php', '127.0.0.1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 09:48:32'),
(22, '/4/', '127.0.0.1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 09:48:33'),
(23, '/4/latest.php', '127.0.0.1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 09:48:34'),
(24, '/4/latest.php', '127.0.0.1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 09:48:35'),
(25, '/4/', '127.0.0.1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 09:48:36'),
(26, '/4/top-rated.php', '127.0.0.1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 09:48:37'),
(27, '/4/', '127.0.0.1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 09:48:38'),
(28, '/4/', '127.0.0.1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 09:49:00'),
(29, '/4/', '127.0.0.1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 09:49:02'),
(30, '/4/admin/', '127.0.0.1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 09:50:12'),
(31, '/4/login.php', '127.0.0.1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 09:50:12'),
(32, '/4/index.php', '127.0.0.1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 09:50:12'),
(33, '/4/', '127.0.0.1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 09:52:11'),
(34, '/4/search.php', '127.0.0.1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 09:52:15'),
(35, '/4/', '127.0.0.1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 09:52:19'),
(36, '/4/profile.php', '127.0.0.1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 09:52:20'),
(37, '/4/watchlist.php', '127.0.0.1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 09:52:24'),
(38, '/4/error_test.php', '127.0.0.1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 09:54:17'),
(39, '/4/watchlist.php', '127.0.0.1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 09:56:13'),
(40, '/4/watchlist.php', '127.0.0.1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 09:56:14'),
(41, '/4/watchlist.php', '127.0.0.1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 09:56:14'),
(42, '/4/login.php', '127.0.0.1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 09:56:30'),
(43, '/4/', '127.0.0.1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 09:56:30'),
(44, '/4/watchlist.php', '127.0.0.1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 09:56:51'),
(45, '/4/', '127.0.0.1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 09:57:51'),
(46, '/4/', '127.0.0.1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 09:57:52'),
(47, '/4/', '127.0.0.1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 09:57:55'),
(48, '/4/admin/', '127.0.0.1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 09:57:59'),
(49, '/4/login.php', '127.0.0.1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 09:57:59'),
(50, '/4/index.php', '127.0.0.1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 09:57:59'),
(51, '/4/watchlist.php', '127.0.0.1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 09:58:22'),
(52, '/4/search.php', '127.0.0.1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 09:58:40'),
(53, '/4/watchlist.php', '127.0.0.1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 09:58:42'),
(54, '/4/', '127.0.0.1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 09:58:43'),
(55, '/4/', '127.0.0.1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:03:59'),
(56, '/4/profile.php', '127.0.0.1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:04:01'),
(57, '/4/watchlist.php', '127.0.0.1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:04:09'),
(58, '/4/', '127.0.0.1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:04:13'),
(59, '/4/latest.php', '127.0.0.1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:04:15'),
(60, '/4/', '127.0.0.1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:04:17'),
(61, '/4/top-rated.php', '127.0.0.1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:04:18'),
(62, '/4/search.php', '127.0.0.1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:04:22'),
(63, '/4/', '127.0.0.1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:04:25'),
(64, '/4/', '127.0.0.1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:04:30'),
(65, '/4/admin/', '127.0.0.1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:04:34'),
(66, '/4/login.php', '127.0.0.1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:04:34'),
(67, '/4/index.php', '127.0.0.1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:04:34'),
(68, '/4/index.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:05:08'),
(69, '/4/login.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:05:09'),
(70, '/4/login.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:05:26'),
(71, '/4/index.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:05:26'),
(72, '/4/admin/', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:05:28'),
(73, '/4/admin/seo-settings.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:15:22'),
(74, '/4/admin/', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:15:24'),
(75, '/4/admin/seo-settings.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:15:26'),
(76, '/4/admin/', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:15:29'),
(77, '/4/admin/', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:16:47'),
(78, '/4/admin/contents.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:16:52'),
(79, '/4/admin/categories.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:16:57'),
(80, '/4/admin/', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:16:59'),
(81, '/4/admin/users.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:17:01'),
(82, '/4/admin/user-roles.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:17:02'),
(83, '/4/admin/user-activities.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:17:04'),
(84, '/4/admin/users.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:17:17'),
(85, '/4/admin/user-form.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:17:23'),
(86, '/4/admin/users.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:17:26'),
(87, '/4/admin/seo-settings.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:17:28'),
(88, '/4/admin/users.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:17:31'),
(89, '/4/admin/settings.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:17:32'),
(90, '/4/', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:18:56'),
(91, '/4/', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:19:06'),
(92, '/4/error_test.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:20:00'),
(93, '/4/admin/index.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:22:55'),
(94, '/4/admin/contents.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:22:57'),
(95, '/4/admin/categories.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:22:59'),
(96, '/4/admin/platforms.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:23:02'),
(97, '/4/admin/categories.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:23:03'),
(98, '/4/admin/platforms.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:23:05'),
(99, '/4/admin/categories.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:23:16'),
(100, '/4/admin/index.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:23:17'),
(101, '/4/admin/bulk-content.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:23:19'),
(102, '/4/admin/index.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:23:32'),
(103, '/4/admin/add-content-omdb.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:23:34'),
(104, '/4/admin/index.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:23:35'),
(105, '/4/admin/media-library.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:23:36'),
(106, '/4/admin/index.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:23:46'),
(107, '/4/admin/users.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:23:48'),
(108, '/4/admin/user-roles.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:23:51'),
(109, '/4/admin/users.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:23:54'),
(110, '/4/admin/user-roles.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:23:55'),
(111, '/4/admin/users.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:23:56'),
(112, '/4/admin/user-activities.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:24:06'),
(113, '/4/admin/user-bans.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:24:12'),
(114, '/4/admin/user-activities.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:24:52'),
(115, '/4/admin/seo-settings.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:24:55'),
(116, '/4/admin/user-activities.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:25:04'),
(117, '/4/admin/statistics.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:25:06'),
(118, '/4/admin/admin-logs.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:25:13'),
(119, '/4/admin/settings.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:25:15'),
(120, '/4/admin/platforms.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:26:10'),
(121, '/4/admin/platforms.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:27:24'),
(122, '/4/admin/platforms.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:27:26'),
(123, '/4/admin/settings.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:29:53'),
(124, '/4/admin/settings.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:29:54'),
(125, '/4/admin/settings.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:29:55'),
(126, '/4/admin/seo-settings.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:29:57'),
(127, '/4/admin/platforms.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:30:19'),
(128, '/4/admin/seo-settings.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:30:24'),
(129, '/4/admin/bulk-content.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:30:28'),
(130, '/4/admin/seo-settings.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:30:30'),
(131, '/4/admin/bulk-content.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:30:36'),
(132, '/4/admin/seo-settings.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:30:41'),
(133, '/4/admin/media-library.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:30:45'),
(134, '/4/admin/seo-settings.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:30:52'),
(135, '/4/admin/media-library.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:30:53'),
(136, '/4/admin/seo-settings.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:30:54'),
(137, '/4/admin/user-bans.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:30:55'),
(138, '/4/admin/user-activities.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:30:58'),
(139, '/4/admin/user-roles.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:31:06'),
(140, '/4/admin/user-activities.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:31:09'),
(141, '/4/admin/user-bans.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:31:11'),
(142, '/4/', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:32:57'),
(143, '/4/', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:33:01'),
(144, '/4/', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:33:02'),
(150, '/4/', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:46:07'),
(151, '/4/admin/settings.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:46:09'),
(152, '/4/admin/contents.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:46:13'),
(153, '/4/admin/platforms.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:46:15'),
(154, '/4/admin/add-content-omdb.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:46:19'),
(155, '/4/admin/add-content-omdb.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:46:20'),
(156, '/4/', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:46:22'),
(157, '/4/', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:46:34'),
(158, '/4/admin/settings.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:48:32'),
(159, '/4/admin/settings.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:48:34'),
(160, '/4/admin/settings.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:50:11'),
(161, '/4/admin/seo-settings.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:50:19'),
(162, '/4/admin/statistics.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:50:47'),
(163, '/4/admin/admin-logs.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:50:52'),
(164, '/4/admin/users.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:50:54'),
(165, '/4/admin/user-roles.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:50:56'),
(166, '/4/admin/user-activities.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:50:58'),
(167, '/4/admin/user-bans.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:51:00'),
(168, '/4/admin/media-library.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:51:01'),
(169, '/4/admin/user-bans.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:51:03'),
(170, '/4/admin/media-library.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:51:04'),
(171, '/4/admin/user-bans.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:51:05'),
(172, '/4/admin/users.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:51:06'),
(173, '/4/admin/seo-settings.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:51:21'),
(174, '/4/admin/statistics.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:51:43'),
(175, '/4/admin/admin-logs.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:51:45'),
(176, '/4/admin/users.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:51:46'),
(177, '/4/admin/user-roles.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:51:48'),
(178, '/4/admin/user-activities.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:51:50'),
(179, '/4/admin/user-activities.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:54:13'),
(180, '/4/admin/add-content-omdb.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:55:30'),
(181, '/4/admin/add-content-omdb.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:55:31'),
(182, '/4/', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:55:37'),
(183, '/4/category.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:55:46'),
(184, '/4/content.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:55:47'),
(185, '/4/content.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:55:57'),
(186, '/4/admin/user-bans.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:56:12'),
(187, '/4/admin/add-content-omdb.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:56:15'),
(188, '/4/admin/add-content-omdb.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:56:20'),
(189, '/4/admin/add-content-omdb.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:56:22'),
(190, '/4/admin/add-content-omdb.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:56:23'),
(191, '/4/content.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:56:26'),
(192, '/4/', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:56:28'),
(193, '/4/content.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:56:31'),
(194, '/4/', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:56:33'),
(195, '/4/admin/', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:57:54'),
(196, '/4/admin/user-roles.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:57:59'),
(197, '/4/admin/users.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:58:03'),
(198, '/4/admin/user-roles.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:58:19'),
(199, '/4/admin/media-library.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:58:32'),
(200, '/4/admin/user-roles.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:58:32'),
(201, '/4/admin/user-activities.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:58:37'),
(202, '/4/admin/add-content-omdb.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:58:40'),
(203, '/4/admin/add-content-omdb.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:58:46'),
(204, '/4/', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:58:50'),
(205, '/4/latest.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:58:59'),
(206, '/4/watchlist.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:59:01'),
(207, '/4/latest.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:59:10'),
(208, '/4/content.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:59:12'),
(209, '/4/watchlist.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:59:14'),
(210, '/4/content.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:59:16'),
(211, '/4/watchlist.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:59:17'),
(212, '/4/content.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:59:22'),
(213, '/4/watchlist.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:59:26'),
(214, '/4/', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:59:27'),
(215, '/4/watchlist.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:59:28'),
(216, '/4/search.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:59:29'),
(217, '/4/', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:59:32'),
(218, '/4/admin/user-activities.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:59:43'),
(219, '/4/admin/user-roles.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:59:44'),
(220, '/4/admin/users.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:59:46'),
(221, '/4/admin/user-roles.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 10:59:50'),
(222, '/4/admin/user-roles.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 11:00:01'),
(223, '/4/admin/user-roles.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 11:00:01'),
(224, '/4/admin/user-roles.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 11:00:03'),
(225, '/4/admin/user-activities.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 11:00:07'),
(226, '/4/admin/user-roles.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 11:00:12'),
(227, '/4/admin/user-bans.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 11:00:13'),
(228, '/4/admin/user-bans.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 11:00:25'),
(229, '/4/admin/user-bans.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 11:00:25'),
(230, '/4/admin/user-bans.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 11:00:27'),
(231, '/4/admin/users.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 11:00:29'),
(232, '/4/admin/user-bans.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 11:00:32'),
(233, '/4/admin/user-activities.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 11:00:37'),
(234, '/4/admin/user-bans.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 11:00:38'),
(235, '/4/admin/user-roles.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 11:00:39'),
(236, '/4/admin/users.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 11:00:41'),
(237, '/4/admin/user-form.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 11:00:42'),
(238, '/4/admin/users.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 11:00:46'),
(239, '/4/admin/user-form.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 11:00:48'),
(240, '/4/admin/users.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 11:00:50'),
(241, '/4/admin/user-form.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 11:00:51'),
(242, '/4/admin/users.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 11:00:52'),
(243, '/4/admin/user-form.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 11:00:52'),
(244, '/4/admin/users.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 11:00:53'),
(245, '/4/admin/users.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 11:01:42'),
(246, '/4/admin/media-library.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 11:01:43'),
(247, '/4/admin/users.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 11:01:44'),
(248, '/4/admin/user-activities.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 11:01:47'),
(249, '/4/admin/users.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 11:02:15'),
(250, '/4/', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 11:35:40'),
(251, '/4/', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 11:35:50'),
(252, '/4/login.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 11:37:24'),
(253, '/4/', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 11:37:25'),
(254, '/4/register.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 11:37:26'),
(255, '/4/', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 11:37:27'),
(256, '/4/login.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 11:37:28'),
(257, '/4/login.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 11:37:36'),
(258, '/4/index.php', '127.0.0.1', 3, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 11:37:36'),
(259, '/4/admin/', '127.0.0.1', 3, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 11:37:39'),
(260, '/4/admin/content-form.php', '127.0.0.1', 3, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 11:37:42'),
(261, '/4/admin/media-library.php', '127.0.0.1', 3, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 11:38:11'),
(262, '/4/admin/content-form.php', '127.0.0.1', 3, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 11:38:11'),
(263, '/4/admin/user-roles.php', '127.0.0.1', 3, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 11:38:17'),
(264, '/4/admin/user-roles.php', '127.0.0.1', 3, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 11:38:58'),
(265, '/4/admin/user-roles.php', '127.0.0.1', 3, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 11:40:31'),
(266, '/4/admin/user-roles.php', '127.0.0.1', 3, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 11:40:32'),
(267, '/4/admin/user-roles.php', '127.0.0.1', 3, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 11:40:32'),
(268, '/4/admin/user-roles.php', '127.0.0.1', 3, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 11:43:03'),
(269, '/4/admin/get_role_users.php', '127.0.0.1', 3, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 11:43:05'),
(270, '/4/admin/user-roles.php', '127.0.0.1', 3, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 11:43:12'),
(271, '/4/admin/get_role_users.php', '127.0.0.1', 3, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 11:44:17'),
(272, '/4/admin/user-roles.php', '127.0.0.1', 3, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 11:46:15'),
(273, '/4/admin/user-roles.php', '127.0.0.1', 3, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 11:46:16'),
(274, '/4/admin/user-roles.php', '127.0.0.1', 3, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 11:46:16'),
(275, '/4/admin/user-roles.php', '127.0.0.1', 3, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 11:46:16'),
(276, '/4/admin/api/get_role_users.php', '127.0.0.1', 3, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 11:46:17'),
(277, '/4/admin/user-roles.php', '127.0.0.1', 3, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 11:46:23'),
(278, '/4/admin/user-roles.php', '127.0.0.1', 3, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 11:46:41'),
(279, '/4/admin/api/get_role_users.php', '127.0.0.1', 3, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 11:46:42'),
(280, '/4/admin/user-roles.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 11:48:23'),
(281, '/4/login.php', '127.0.0.1', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 11:48:23'),
(282, '/4/admin/user-roles.php', '127.0.0.1', 3, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 11:49:59'),
(283, '/4/admin/api/get_role_users.php', '127.0.0.1', 3, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 11:50:00'),
(284, '/4/admin/api/get_role_users.php', '127.0.0.1', 3, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-01-29 11:50:14');

-- --------------------------------------------------------

--
-- Table structure for table `watchlist`
--

CREATE TABLE `watchlist` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `content_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_logs`
--
ALTER TABLE `admin_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `bulk_content_logs`
--
ALTER TABLE `bulk_content_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `content_id` (`content_id`);

--
-- Indexes for table `contents`
--
ALTER TABLE `contents`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD UNIQUE KEY `imdb_id` (`imdb_id`);

--
-- Indexes for table `content_actors`
--
ALTER TABLE `content_actors`
  ADD PRIMARY KEY (`id`),
  ADD KEY `content_id` (`content_id`);

--
-- Indexes for table `content_categories`
--
ALTER TABLE `content_categories`
  ADD PRIMARY KEY (`content_id`,`category_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `content_platform_relations`
--
ALTER TABLE `content_platform_relations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `content_platform_unique` (`content_id`,`platform_id`),
  ADD KEY `platform_id` (`platform_id`);

--
-- Indexes for table `content_views`
--
ALTER TABLE `content_views`
  ADD PRIMARY KEY (`id`),
  ADD KEY `content_id` (`content_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `media_library`
--
ALTER TABLE `media_library`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `platforms`
--
ALTER TABLE `platforms`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_role_permission` (`role_id`,`permission_name`);

--
-- Indexes for table `site_settings`
--
ALTER TABLE `site_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`),
  ADD KEY `updated_by` (`updated_by`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_activities`
--
ALTER TABLE `user_activities`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `user_bans`
--
ALTER TABLE `user_bans`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `banned_by` (`banned_by`),
  ADD KEY `removed_by` (`removed_by`);

--
-- Indexes for table `user_roles`
--
ALTER TABLE `user_roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `user_role_relations`
--
ALTER TABLE `user_role_relations`
  ADD PRIMARY KEY (`user_id`,`role_id`),
  ADD KEY `role_id` (`role_id`);

--
-- Indexes for table `visitor_stats`
--
ALTER TABLE `visitor_stats`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `watchlist`
--
ALTER TABLE `watchlist`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_content` (`user_id`,`content_id`),
  ADD KEY `content_id` (`content_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_logs`
--
ALTER TABLE `admin_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `bulk_content_logs`
--
ALTER TABLE `bulk_content_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `comments`
--
ALTER TABLE `comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `contents`
--
ALTER TABLE `contents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `content_actors`
--
ALTER TABLE `content_actors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `content_platform_relations`
--
ALTER TABLE `content_platform_relations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `content_views`
--
ALTER TABLE `content_views`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `media_library`
--
ALTER TABLE `media_library`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `platforms`
--
ALTER TABLE `platforms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `role_permissions`
--
ALTER TABLE `role_permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `site_settings`
--
ALTER TABLE `site_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `user_activities`
--
ALTER TABLE `user_activities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `user_bans`
--
ALTER TABLE `user_bans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_roles`
--
ALTER TABLE `user_roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `visitor_stats`
--
ALTER TABLE `visitor_stats`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=285;

--
-- AUTO_INCREMENT for table `watchlist`
--
ALTER TABLE `watchlist`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin_logs`
--
ALTER TABLE `admin_logs`
  ADD CONSTRAINT `admin_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `bulk_content_logs`
--
ALTER TABLE `bulk_content_logs`
  ADD CONSTRAINT `bulk_content_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `comments`
--
ALTER TABLE `comments`
  ADD CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comments_ibfk_2` FOREIGN KEY (`content_id`) REFERENCES `contents` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `content_actors`
--
ALTER TABLE `content_actors`
  ADD CONSTRAINT `content_actors_ibfk_1` FOREIGN KEY (`content_id`) REFERENCES `contents` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `content_categories`
--
ALTER TABLE `content_categories`
  ADD CONSTRAINT `content_categories_ibfk_1` FOREIGN KEY (`content_id`) REFERENCES `contents` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `content_categories_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `content_platform_relations`
--
ALTER TABLE `content_platform_relations`
  ADD CONSTRAINT `content_platform_relations_ibfk_1` FOREIGN KEY (`content_id`) REFERENCES `contents` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `content_platform_relations_ibfk_2` FOREIGN KEY (`platform_id`) REFERENCES `platforms` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `content_views`
--
ALTER TABLE `content_views`
  ADD CONSTRAINT `content_views_ibfk_1` FOREIGN KEY (`content_id`) REFERENCES `contents` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `content_views_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `media_library`
--
ALTER TABLE `media_library`
  ADD CONSTRAINT `media_library_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD CONSTRAINT `password_resets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD CONSTRAINT `role_permissions_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `user_roles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `site_settings`
--
ALTER TABLE `site_settings`
  ADD CONSTRAINT `site_settings_ibfk_1` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `user_activities`
--
ALTER TABLE `user_activities`
  ADD CONSTRAINT `user_activities_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `user_bans`
--
ALTER TABLE `user_bans`
  ADD CONSTRAINT `user_bans_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_bans_ibfk_2` FOREIGN KEY (`banned_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_bans_ibfk_3` FOREIGN KEY (`removed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `user_role_relations`
--
ALTER TABLE `user_role_relations`
  ADD CONSTRAINT `user_role_relations_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_role_relations_ibfk_2` FOREIGN KEY (`role_id`) REFERENCES `user_roles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `visitor_stats`
--
ALTER TABLE `visitor_stats`
  ADD CONSTRAINT `visitor_stats_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `watchlist`
--
ALTER TABLE `watchlist`
  ADD CONSTRAINT `watchlist_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `watchlist_ibfk_2` FOREIGN KEY (`content_id`) REFERENCES `contents` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
