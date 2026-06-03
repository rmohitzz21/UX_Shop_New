-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 02, 2026 at 12:51 PM
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
-- Database: `uxmerchandise`
--

-- --------------------------------------------------------

--
-- Table structure for table `addresses`
--

CREATE TABLE `addresses` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `address_line1` varchar(255) NOT NULL,
  `address_line2` varchar(255) DEFAULT NULL,
  `city` varchar(100) NOT NULL,
  `state` varchar(100) NOT NULL,
  `zip_code` varchar(20) NOT NULL,
  `country` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `is_default` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `addresses`
--

INSERT INTO `addresses` (`id`, `user_id`, `first_name`, `last_name`, `address_line1`, `address_line2`, `city`, `state`, `zip_code`, `country`, `phone`, `is_default`, `created_at`, `updated_at`) VALUES
(7, 50, 'Mohit', 'Rana', 'sakm, osoam, moamos, omsmoam,momaoma', 'aaa', 'Ahmedabad', 'Gujarat', '380001', 'India', '918238014262', 1, '2026-04-14 04:53:03', '2026-04-14 04:53:03');

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `name` varchar(160) DEFAULT 'Admin',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bundles`
--

CREATE TABLE `bundles` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `whats_included` text DEFAULT NULL,
  `file_specification` text DEFAULT NULL,
  `category` varchar(100) DEFAULT 'Bundles',
  `tags` varchar(500) DEFAULT NULL,
  `included_items` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `old_price` decimal(10,2) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `badge_text` varchar(80) DEFAULT 'Best Seller',
  `additional_images` text DEFAULT NULL,
  `rating` decimal(3,2) DEFAULT 4.70,
  `stock` int(11) NOT NULL DEFAULT 999,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `sales_count` int(11) NOT NULL DEFAULT 0,
  `view_count` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `digital_file_path` varchar(500) DEFAULT NULL,
  `download_limit` tinyint(3) UNSIGNED NOT NULL DEFAULT 5,
  `download_expiry_days` smallint(5) UNSIGNED NOT NULL DEFAULT 30
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bundles`
--

INSERT INTO `bundles` (`id`, `name`, `slug`, `description`, `whats_included`, `file_specification`, `category`, `tags`, `included_items`, `price`, `old_price`, `image`, `badge_text`, `additional_images`, `rating`, `stock`, `is_active`, `is_featured`, `sales_count`, `view_count`, `created_at`, `updated_at`, `digital_file_path`, `download_limit`, `download_expiry_days`) VALUES
(2, 'UX Interview Prep Bundle', 'ux-interview-prep-bundle', 'Research prompts, whiteboard exercises, presentation templates, and interview scorecards.', 'Interview question bank\nWhiteboard challenge templates\nPresentation deck\nSelf-assessment scorecard', NULL, 'Interview Prep', 'interview-prep,career,whiteboard', '[\"Interview question bank\",\"Whiteboard challenge templates\",\"Presentation deck\",\"Self-assessment scorecard\"]', 899.00, 1999.00, 'img/poster2.webp', 'Best Seller', NULL, 4.70, 999, 1, 1, 3, 0, '2026-05-22 05:56:58', '2026-06-01 12:44:03', NULL, 5, 30),
(8, 'asnsajn', 'asnsajn', 'njajsnaj', 'smasask\r\naksakms\r\nakmsam\'\r\nskansa\'kans\r\nnasnak', 'ansajns\r\nnajsna\'nans\r\nnans]n', 'UI', 'design', '[{\"label\":\"smasask\"},{\"label\":\"aksakms\"},{\"label\":\"akmsam\'\"},{\"label\":\"skansa\'kans\"},{\"label\":\"nasnak\"}]', 120.00, 100.00, 'img/products/42e447d5878d2612084bb0d03d1e2d7c.png', 'Most Popular', '[\"img/products/58cba21c139022e76fb1dd7cb88170d1.webp\",\"img/products/2fb5089022ff43b9ca5b96307122ff12.webp\"]', 4.00, 10, 1, 1, 1, 0, '2026-06-01 12:01:42', '2026-06-01 12:02:21', NULL, 5, 30),
(9, 'QA BUNDLE CRUD TEST 1780397142562 UPDATED', 'qa-bundle-crud-test-1780397142562-updated', 'Updated QA bundle verified through admin dashboard CRUD testing.', 'Updated QA Guide PDF\r\nUpdated QA Canva Template\r\nUpdated QA Figma Resource\r\nBonus ZIP', 'PDF\r\nZIP\r\nCanva template link\r\nFigma resource link', 'QA Updated Pack', 'qa, temporary, bundle-crud', '[{\"label\":\"Updated QA Guide PDF\"},{\"label\":\"Updated QA Canva Template\"},{\"label\":\"Updated QA Figma Resource\"},{\"label\":\"Bonus ZIP\"}]', 888.25, 1200.00, 'img/poster.webp', 'QA Updated', NULL, 4.70, 30, 1, 0, 0, 0, '2026-06-02 10:45:44', '2026-06-02 10:48:35', NULL, 5, 30);

-- --------------------------------------------------------

--
-- Table structure for table `bundle_items`
--

CREATE TABLE `bundle_items` (
  `id` int(11) NOT NULL,
  `bundle_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bundle_items`
--

INSERT INTO `bundle_items` (`id`, `bundle_id`, `product_id`, `quantity`, `created_at`) VALUES
(1, 8, 1, 1, '2026-06-01 12:01:42'),
(2, 9, 62, 1, '2026-06-02 10:47:40');

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `item_type` enum('product','bundle') NOT NULL DEFAULT 'product',
  `bundle_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `size` varchar(20) DEFAULT NULL,
  `available_type` varchar(20) DEFAULT 'physical',
  `selected_format` enum('digital','physical') NOT NULL DEFAULT 'physical',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `_item_key` varchar(120) GENERATED ALWAYS AS (concat(`item_type`,':',coalesce(`product_id`,0),':',coalesce(`bundle_id`,0),':',`selected_format`,':',coalesce(`size`,''))) STORED
) ;

--
-- Dumping data for table `cart`
--

INSERT INTO `cart` (`id`, `user_id`, `item_type`, `bundle_id`, `product_id`, `quantity`, `size`, `available_type`, `selected_format`, `created_at`) VALUES
(213, 70, 'product', NULL, 46, 1, NULL, 'digital', 'digital', '2026-05-23 08:45:07'),
(223, 71, 'product', NULL, 68, 1, NULL, 'digital', 'digital', '2026-05-29 11:40:24');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(120) NOT NULL,
  `slug` varchar(140) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(255) DEFAULT NULL,
  `accent` varchar(40) DEFAULT 'purple',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `slug`, `description`, `icon`, `accent`, `is_active`, `sort_order`, `created_at`, `updated_at`) VALUES
(1, 'Templates', 'templates', 'Get hired with ready-to-use templates', 'img/products/adc08eab4cc1e46399c6727b8fbe372e.png', 'green', 1, 1, '2026-05-22 05:56:58', '2026-06-01 11:51:50'),
(2, 'UI Kits', 'ui-kits', 'Build stunning interfaces faster', 'img/products/d712de394414a8387277515b6692cab1.webp', 'pink', 1, 2, '2026-05-22 05:56:58', '2026-06-01 11:53:48'),
(3, 'Mockups', 'mockups', 'Present your work professionally', '', 'purple', 0, 3, '2026-05-22 05:56:58', '2026-05-28 08:48:35'),
(4, 'UX Resources', 'ux-resources', 'Master UX research and strategy', '', 'orange', 0, 4, '2026-05-22 05:56:58', '2026-05-28 08:48:40'),
(5, 'Workbooks', 'workbooks', 'Practice systems for UX learners', '', 'green', 0, 5, '2026-05-22 05:56:58', '2026-05-28 08:49:00'),
(6, 'Bundles', 'bundles', 'Curated packs for faster outcomes', '', 'purple', 0, 6, '2026-05-22 05:56:58', '2026-05-28 08:49:03');

-- --------------------------------------------------------

--
-- Table structure for table `contact_messages`
--

CREATE TABLE `contact_messages` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `message` text NOT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `archived` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `contact_messages`
--

INSERT INTO `contact_messages` (`id`, `name`, `email`, `phone`, `subject`, `message`, `ip`, `created_at`, `is_read`, `archived`) VALUES
(1, 'Test', 'test2@gmail.com', '7621048017', 'test', 'testing api for contact', '::1', '2026-02-19 14:04:19', 0, 0),
(2, 'Mohit', 'rmohit@gmail.com', '7621048017', 'Hello', 'Hello Mam&#039;m..', '::1', '2026-02-20 16:41:07', 0, 0),
(3, 'Mohit Rana', 'rmohittt21@gmail.com', '08238014262', 'Hello', 'sasmsmansamsnasnansanmsnan', '::1', '2026-04-14 12:11:31', 0, 0),
(4, 'Mohit Rana', 'rmohittt21@gmail.com', '08238014262', 'tewst', '&lt;?php\n// includes/helpers.php\n\nfunction sendResponse($status, $message, $data = null, $code = 200) {\n    http_response_code($code);\n    header(&#039;Content-Type: application/json&#039;);\n\n    $response = [\n        &quot;status&quot;  =&gt; $status,\n        &quot;message&quot; =&gt; $message\n    ];\n\n    if ($data !== null) {\n        $response[&quot;data&quot;] = $data;\n    }\n\n    echo json_encode($response);\n    exit;\n}\n\nfunction requireUserAuth() {\n    if (empty($_SESSION[&#039;user_id&#039;])) {\n        sendResponse(&quot;error&quot;, &quot;Unauthorized: Login required&quot;, null, 401);\n    }\n}\n\nfunction requireAdmin() {\n    if (empty($_SESSION[&#039;admin_id&#039;])) {\n        sendResponse(&quot;error&quot;, &quot;Unauthorized: Admin access required&quot;, null, 401);\n    }\n}\n\nfunction requireAuth() {\n    requireUserAuth();\n}\n\nfunction validateCsrf() {\n    if (empty($_SESSION[&#039;csrf_token&#039;])) {\n        sendResponse(&quot;error&quot;, &quot;Session expired&quot;, null, 403);\n    }\n\n    $token = $_SERVER[&#039;HTTP_X_CSRF_TOKEN&#039;] ?? &#039;&#039;;\n\n    if (empty($token)) {\n        $body = json_decode(file_get_contents(&#039;php:', '::1', '2026-04-14 14:54:00', 0, 0),
(5, 'Mohit Rana', 'rmohittt21@gmail.com', '08238014262', 'HELLO', 'THIS MAIL IS FOR TESTING', '::1', '2026-04-14 14:59:35', 0, 0),
(6, 'Mohit Rana', 'rmohittt21@gmail.com', '08238014262', 'Tetsting', 'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA', '::1', '2026-04-14 15:00:28', 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `digital_downloads`
--

CREATE TABLE `digital_downloads` (
  `id` int(10) UNSIGNED NOT NULL,
  `order_id` int(11) NOT NULL,
  `order_item_id` int(11) NOT NULL,
  `resource_id` int(11) NOT NULL DEFAULT 0 COMMENT '0 = legacy single-file row; >0 = digital_resources.id',
  `user_id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `bundle_id` int(11) DEFAULT NULL,
  `token` char(64) NOT NULL,
  `download_count` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `download_limit` tinyint(3) UNSIGNED NOT NULL DEFAULT 5,
  `expires_at` datetime NOT NULL,
  `file_path` varchar(500) NOT NULL DEFAULT '',
  `item_name` varchar(255) NOT NULL DEFAULT '',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `digital_resources`
--

CREATE TABLE `digital_resources` (
  `id` int(10) UNSIGNED NOT NULL,
  `product_id` int(11) DEFAULT NULL COMMENT 'Set for product resources; NULL for bundles',
  `bundle_id` int(11) DEFAULT NULL COMMENT 'Set for bundle resources; NULL for products',
  `title` varchar(255) NOT NULL,
  `resource_type` varchar(40) NOT NULL COMMENT 'Allowed: file | zip | pdf | canva | figma | external_link | instructions',
  `delivery_mode` varchar(30) NOT NULL COMMENT 'Allowed: download | open_link | instructions',
  `storage_provider` varchar(30) NOT NULL DEFAULT 'local' COMMENT 'Allowed: local | r2 | s3 | external',
  `storage_key` varchar(700) DEFAULT NULL COMMENT 'Private: cloud object key or local file path ÔÇö never exposed in public APIs',
  `external_url` text DEFAULT NULL COMMENT 'Canva / Figma / approved HTTPS URL ÔÇö served only after auth & ownership check',
  `instructions` text DEFAULT NULL,
  `download_limit` tinyint(3) UNSIGNED NOT NULL DEFAULT 5,
  `expiry_days` smallint(5) UNSIGNED NOT NULL DEFAULT 30,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ;

-- --------------------------------------------------------

--
-- Table structure for table `featured_items`
--

CREATE TABLE `featured_items` (
  `id` int(11) NOT NULL,
  `item_type` enum('product','bundle') NOT NULL,
  `item_id` int(11) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `freebies`
--

CREATE TABLE `freebies` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `category` varchar(100) DEFAULT 'General',
  `image` varchar(500) DEFAULT '',
  `file_url` varchar(500) DEFAULT '',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `download_count` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `freebies`
--

INSERT INTO `freebies` (`id`, `name`, `slug`, `description`, `category`, `image`, `file_url`, `is_active`, `is_featured`, `sort_order`, `download_count`, `created_at`, `updated_at`) VALUES
(1, 'asmam', 'asmam', 'z xax a  x', 'mdakmd', 'img/products/8e73868e88f23fafb8af3c6c8961aac6.webp', 'http://localhost/Shop/UX_SHOP/UX_Shop_New/freebies.php', 1, 0, 1, 2, '2026-05-27 17:06:24', '2026-05-28 06:33:19');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_movements`
--

CREATE TABLE `inventory_movements` (
  `id` int(11) NOT NULL,
  `item_type` enum('product','bundle') NOT NULL DEFAULT 'product',
  `item_id` int(11) NOT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `change_qty` int(11) NOT NULL,
  `stock_before` int(11) NOT NULL DEFAULT 0,
  `stock_after` int(11) NOT NULL DEFAULT 0,
  `reason` varchar(160) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory_movements`
--

INSERT INTO `inventory_movements` (`id`, `item_type`, `item_id`, `admin_id`, `change_qty`, `stock_before`, `stock_after`, `reason`, `created_at`) VALUES
(1, 'bundle', 8, 2, 10, 0, 10, 'Admin bundle create', '2026-06-01 12:01:42'),
(2, 'product', 69, 2, 7, 0, 7, 'Admin product create', '2026-06-02 10:42:15'),
(3, 'product', 69, 2, 2, 7, 9, 'Admin product update', '2026-06-02 10:42:59'),
(4, 'bundle', 9, 2, 25, 0, 25, 'Admin bundle create', '2026-06-02 10:45:44'),
(5, 'bundle', 9, 2, 5, 25, 30, 'Admin bundle update', '2026-06-02 10:46:11');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_reservations`
--

CREATE TABLE `inventory_reservations` (
  `id` int(10) UNSIGNED NOT NULL,
  `order_id` int(11) NOT NULL,
  `order_item_id` int(11) NOT NULL,
  `item_type` enum('product','bundle') NOT NULL DEFAULT 'product',
  `item_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `expires_at` datetime NOT NULL COMMENT 'Auto-release threshold for abandoned awaiting_payment orders',
  `released_at` datetime DEFAULT NULL COMMENT 'Set when voided: payment failed, order cancelled, or expired',
  `consumed_at` datetime DEFAULT NULL COMMENT 'Set when stock is permanently decremented on payment confirmation',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `order_number` varchar(50) NOT NULL,
  `user_id` int(11) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `shipping` decimal(10,2) DEFAULT 50.00,
  `tax` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `payment_id` varchar(120) DEFAULT NULL,
  `razorpay_order_id` varchar(120) DEFAULT NULL,
  `payment_verified_at` datetime DEFAULT NULL,
  `paid_at` datetime DEFAULT NULL,
  `confirmation_email_sent_at` datetime DEFAULT NULL,
  `payment_currency` varchar(10) DEFAULT 'INR',
  `status` varchar(32) NOT NULL DEFAULT 'pending',
  `payment_status` enum('pending','paid','failed','refunded') NOT NULL DEFAULT 'pending',
  `status_updated_at` timestamp NULL DEFAULT NULL,
  `shipping_address` text DEFAULT NULL,
  `customer_note` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `order_number`, `user_id`, `total`, `subtotal`, `shipping`, `tax`, `payment_method`, `payment_id`, `razorpay_order_id`, `payment_verified_at`, `paid_at`, `confirmation_email_sent_at`, `payment_currency`, `status`, `payment_status`, `status_updated_at`, `shipping_address`, `customer_note`, `created_at`) VALUES
(104, 'UXP-20260528-9F2598', 71, 588.82, 499.00, 0.00, 89.82, 'cod', NULL, NULL, NULL, NULL, NULL, 'INR', 'cancelled', 'pending', '2026-05-28 12:32:40', '{\"firstName\":\"Mohit\",\"lastName\":\"Rana\",\"email\":\"rmohittt21@gmail.com\",\"phone\":\"+918238014262\",\"address\":\"\",\"city\":\"\",\"state\":\"\",\"zip\":\"\",\"country\":\"\"}', NULL, '2026-05-28 11:34:47'),
(105, 'UXP-20260529-E483D8', 71, 638.82, 499.00, 50.00, 89.82, 'cod', NULL, NULL, NULL, NULL, NULL, 'INR', 'pending', 'pending', '2026-05-29 05:57:40', '{\"firstName\":\"Mohit\",\"lastName\":\"Rana\",\"email\":\"rmohittt21@gmail.com\",\"phone\":\"+918238014262\",\"address\":\"sakm, osoam, moamos, omsmoam,momaoma\",\"city\":\"Ahmedabad\",\"state\":\"Gujarat\",\"zip\":\"380001\",\"country\":\"IN\"}', NULL, '2026-05-29 05:57:40'),
(106, 'UXP-20260601-4E35BA', 74, 1177.64, 998.00, 0.00, 179.64, 'cod', NULL, NULL, NULL, NULL, NULL, 'INR', 'pending', 'pending', '2026-06-01 10:02:02', '{\"firstName\":\"Mohit\",\"lastName\":\"Rana\",\"email\":\"rmohittt21@gmail.com\",\"phone\":\"+918238014262\",\"address\":\"\",\"city\":\"\",\"state\":\"\",\"zip\":\"\",\"country\":\"\"}', NULL, '2026-06-01 10:02:02'),
(107, 'UXP-20260601-693B59', 74, 1060.82, 899.00, 0.00, 161.82, 'cod', NULL, NULL, NULL, NULL, NULL, 'INR', 'pending', 'pending', '2026-06-01 10:18:58', '{\"firstName\":\"Mohit\",\"lastName\":\"Rana\",\"email\":\"rmohittt21@gmail.com\",\"phone\":\"+918238014262\",\"address\":\"\",\"city\":\"\",\"state\":\"\",\"zip\":\"\",\"country\":\"\"}', NULL, '2026-06-01 10:18:58'),
(108, 'UXP-20260601-8BC961', 74, 470.82, 399.00, 0.00, 71.82, 'razorpay', NULL, NULL, NULL, '2026-06-01 17:14:53', NULL, 'INR', 'paid', 'paid', '2026-06-01 11:44:53', '{\"firstName\":\"Mohit\",\"lastName\":\"Rana\",\"email\":\"rmohittt21@gmail.com\",\"phone\":\"+918238014262\",\"address\":\"\",\"city\":\"\",\"state\":\"\",\"zip\":\"\",\"country\":\"\"}', NULL, '2026-06-01 11:42:58'),
(109, 'UXP-20260601-2F3E1C', 74, 470.82, 399.00, 0.00, 71.82, 'cod', NULL, NULL, NULL, '2026-06-01 17:15:01', NULL, 'INR', 'paid', 'paid', '2026-06-01 11:45:01', '{\"firstName\":\"Mohit\",\"lastName\":\"Rana\",\"email\":\"rmohittt21@gmail.com\",\"phone\":\"+918238014262\",\"address\":\"\",\"city\":\"\",\"state\":\"\",\"zip\":\"\",\"country\":\"\"}', NULL, '2026-06-01 11:43:02'),
(110, 'UXP-20260601-CC2B14', 74, 588.82, 499.00, 0.00, 89.82, 'cod', NULL, NULL, NULL, NULL, NULL, 'INR', 'pending', 'pending', '2026-06-01 12:16:29', '{\"firstName\":\"Hello\",\"lastName\":\"Pacific\",\"email\":\"hello@uxpacific.com\",\"phone\":\"7863041880\",\"address\":\"\",\"city\":\"\",\"state\":\"\",\"zip\":\"\",\"country\":\"\"}', NULL, '2026-06-01 12:16:29'),
(111, 'UXP-20260602-FAF585', 74, 520.82, 399.00, 50.00, 71.82, 'cod', NULL, NULL, NULL, NULL, NULL, 'INR', 'pending', 'pending', '2026-06-02 08:45:07', '{\"firstName\":\"Hello\",\"lastName\":\"Pacific\",\"email\":\"hello@uxpacific.com\",\"phone\":\"7863041880\",\"address\":\"1006/8, mangal bhuvan\",\"city\":\"huu\",\"state\":\"mncvbm\",\"zip\":\"380001\",\"country\":\"IN\"}', NULL, '2026-06-02 08:45:07');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `bundle_id` int(11) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `size` varchar(20) DEFAULT NULL,
  `product_name` varchar(255) DEFAULT NULL COMMENT 'Snapshot of product name at time of order',
  `product_image` varchar(500) DEFAULT NULL COMMENT 'Snapshot of product image path at time of order',
  `item_type` enum('product','bundle') NOT NULL DEFAULT 'product',
  `selected_format` enum('digital','physical') NOT NULL DEFAULT 'physical'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `bundle_id`, `quantity`, `price`, `size`, `product_name`, `product_image`, `item_type`, `selected_format`) VALUES
(85, 86, 40, NULL, 1, 150.00, '', 'Stickers', 'img/products/89667db0590c3b12b27816c854c2b5a1.webp', 'product', 'physical'),
(88, 89, 41, NULL, 1, 250.00, 'L', 'Tshirt -02', 'img/products/8413b299d888950e82c636e2aaa5f750.webp', 'product', 'physical'),
(97, 96, 39, NULL, 1, 450.00, '', 'Booklet Ux Audit', 'img/products/7111ca902a561a11a457a453eb03b4c0.webp', 'product', 'physical'),
(99, 99, 39, NULL, 1, 450.00, '', 'Booklet Ux Audit', 'img/products/7111ca902a561a11a457a453eb03b4c0.webp', 'product', 'physical'),
(101, 101, 41, NULL, 1, 250.00, 'L', 'Tshirt -02', 'img/products/615b7bfc0173287ba4ad2c2622ab6099.webp', 'product', 'physical'),
(103, 103, 45, NULL, 1, 399.00, 'One Size', 'UXPacific UI Template', 'img/poster1.webp', 'product', 'physical'),
(104, 103, 60, NULL, 2, 349.00, 'One Size', 'Portfolio Layout System', 'img/shopnew.png', 'product', 'physical'),
(105, 104, 63, NULL, 1, 499.00, 'One Size', 'UX Research Workbook', 'img/poster2.webp', 'product', 'digital'),
(106, 105, 63, NULL, 1, 499.00, 'One Size', 'UX Research Workbook', 'img/poster2.webp', 'product', 'digital'),
(107, 106, 63, NULL, 2, 499.00, 'One Size', 'UX Research Workbook', 'img/poster2.webp', 'product', 'digital'),
(108, 107, 68, NULL, 1, 899.00, '', 'UX Interview Prep Bundle', 'img/poster2.webp', 'product', 'digital'),
(109, 108, 62, NULL, 1, 399.00, '', 'UXPacific UI Template', 'img/poster1.webp', 'product', 'digital'),
(110, 109, 62, NULL, 1, 399.00, '', 'UXPacific UI Template', 'img/poster1.webp', 'product', 'digital'),
(111, 110, 63, NULL, 1, 499.00, '', 'UX Research Workbook', 'img/poster2.webp', 'product', 'digital'),
(112, 111, 62, NULL, 1, 399.00, 'One Size', 'UXPacific UI Template', 'img/poster1.webp', 'product', 'digital');

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `token_hash` varchar(255) NOT NULL COMMENT 'SHA-256 hash of the raw token',
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL COMMENT 'Set when token is consumed',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) DEFAULT NULL,
  `sku` varchar(80) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `tags` varchar(500) DEFAULT NULL,
  `available_type` enum('physical','digital','both') NOT NULL DEFAULT 'physical',
  `product_type` enum('physical','digital') NOT NULL DEFAULT 'physical',
  `price` decimal(10,2) NOT NULL,
  `commercial_price` decimal(10,2) DEFAULT NULL,
  `old_price` decimal(10,2) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `stock` int(11) DEFAULT 0,
  `rating` decimal(3,1) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1,
  `is_featured` tinyint(1) DEFAULT 0,
  `sales_count` int(11) NOT NULL DEFAULT 0,
  `view_count` int(11) NOT NULL DEFAULT 0,
  `related_products` text DEFAULT NULL,
  `whats_included` text DEFAULT NULL,
  `file_specification` text DEFAULT NULL,
  `high_resolution` varchar(80) DEFAULT 'Yes',
  `compatible_software` varchar(255) DEFAULT NULL,
  `software_version` varchar(120) DEFAULT NULL,
  `files_included` varchar(255) DEFAULT NULL,
  `grid_columns` varchar(80) DEFAULT NULL,
  `layout_type` varchar(120) DEFAULT NULL,
  `license_type` varchar(80) DEFAULT 'Premium',
  `additional_images` text DEFAULT NULL,
  `digital_file_path` varchar(500) DEFAULT NULL,
  `download_limit` tinyint(3) UNSIGNED NOT NULL DEFAULT 5,
  `download_expiry_days` smallint(5) UNSIGNED NOT NULL DEFAULT 30
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `slug`, `sku`, `description`, `category`, `tags`, `available_type`, `product_type`, `price`, `commercial_price`, `old_price`, `image`, `stock`, `rating`, `created_at`, `updated_at`, `is_active`, `is_featured`, `sales_count`, `view_count`, `related_products`, `whats_included`, `file_specification`, `high_resolution`, `compatible_software`, `software_version`, `files_included`, `grid_columns`, `layout_type`, `license_type`, `additional_images`, `digital_file_path`, `download_limit`, `download_expiry_days`) VALUES
(62, 'UXPacific UI Template', 'uxpacific-ui-template', NULL, 'Premium Figma landing page and dashboard templates with responsive auto layout.', 'Templates', 'figma,landing,dashboard,template', 'digital', 'physical', 399.00, 558.60, 899.00, 'img/poster1.webp', 998, 4.8, '2026-05-28 11:33:22', '2026-06-02 08:45:07', 1, 1, 3, 1, NULL, '- Editable source files\n- Usage guide\n- Bonus checklist', '- Figma / PDF files\n- 1440px layout references\n- Personal and commercial license options', 'Yes', NULL, NULL, NULL, NULL, NULL, 'Premium', '[\"img\\/poster1.webp\",\"img\\/poster.webp\",\"img\\/poster2.webp\"]', NULL, 5, 30),
(63, 'UX Research Workbook', 'ux-research-workbook', '', 'A practical workbook for UX learners and product designers.', 'Workbooks', 'ux,research,workbook,pdf', 'digital', 'physical', 499.00, 698.60, 999.00, 'img/poster2.webp', 998, 4.7, '2026-05-28 11:33:22', '2026-06-01 12:16:29', 1, 1, 5, 6, NULL, '- Editable source files\r\n- Usage guide\r\n- Bonus checklist', '- Figma / PDF files\r\n- 1440px layout references\r\n- Personal and commercial license options', 'Yes', NULL, NULL, NULL, NULL, NULL, 'Premium', '[\"img/products/43993d8004e05c4b8c8ce7e3771145dc.webp\"]', NULL, 5, 30),
(68, 'UX Interview Prep Bundle', NULL, NULL, 'Research prompts, whiteboard exercises, presentation templates, and interview scorecards.', 'Interview Prep', NULL, 'digital', 'physical', 899.00, NULL, NULL, 'img/poster2.webp', 999, 4.8, '2026-05-29 11:40:24', '2026-06-01 10:18:58', 1, 0, 1, 0, NULL, NULL, NULL, 'Yes', NULL, NULL, NULL, NULL, NULL, 'Premium', NULL, NULL, 5, 30);

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `bundle_id` int(11) DEFAULT NULL,
  `rating` tinyint(4) NOT NULL,
  `comment` text DEFAULT NULL,
  `is_approved` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`id`, `user_id`, `product_id`, `bundle_id`, `rating`, `comment`, `is_approved`, `created_at`) VALUES
(1, 71, 63, NULL, 5, 'This is Very Help ful', 1, '2026-05-29 05:58:16');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role` enum('customer','admin') DEFAULT 'customer',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_blocked` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `email`, `password_hash`, `first_name`, `last_name`, `phone`, `role`, `created_at`, `updated_at`, `is_blocked`) VALUES
(2, 'admin@uxpacific.com', '$2y$10$jH6GQIJil5BK/vzwPCBJKOe7gGuIn6PfjTbcjpHnV/uvZmNsczEgi', 'Admin', 'User', NULL, 'admin', '2026-02-09 15:33:31', '2026-05-13 11:54:11', 0),
(9, 'Hello@uxpacific.com', '$2y$10$jH6GQIJil5BK/vzwPCBJKOe7gGuIn6PfjTbcjpHnV/uvZmNsczEgi', 'Admin', 'User', NULL, 'admin', '2026-02-11 12:33:58', '2026-05-13 11:54:20', 0),
(31, 'pacific@gmail.com', '$2y$10$3Y6F7GHmHGzDDoVEd0ni9ePJap9QYIzb5mgKDNLOYycpAEFfpsSDK', 'ux', 'pacific', '8238014262', 'customer', '2026-02-18 05:41:41', '2026-02-18 05:41:41', 0),
(32, 'test@gmail.com', '$2y$10$Me1a4MyAahhFAI0xuhTpG.TaSOvYnZdX5Dhd3sEmLBRSSX2YhzzOa', 'test', '', '7621048017', 'customer', '2026-02-18 11:21:38', '2026-02-18 11:21:38', 0),
(33, 'zulaprajapati26@gmail.com', '$2y$10$3jFzBx7MoW728UEdBYsYoO9zFR5UrT7PHdEGk9sHZZPcFA6G1Hw0G', 'zula', '', '7863041880', 'customer', '2026-02-19 06:06:15', '2026-02-19 06:06:15', 0),
(48, 'aradhya.arya13@gmail.com', '$2y$10$YS/prUpFHnB00/aE0N.KSuBqhJaUt2uH2PmUKCw.wI0SOI7O3XarC', 'ardhya', 'satghare', '7621048017', 'customer', '2026-02-27 06:01:05', '2026-02-27 06:01:05', 0),
(49, 'admin00@gmail.com', '$2y$10$vprWhjZnHurukoTJ6QnPGuI5wx0m0ECRqG4woJhm6If8WxOxtToAe', 'admin', '', '7621048017', 'customer', '2026-03-19 08:41:56', '2026-03-19 08:41:56', 0),
(50, 'user@gmail.com', '$2y$10$XpkJGMIH4psioqrCvfiKD.P9edDzAH1.RQ/lRVICcgK3CSRx7wqjK', 'user', '', '7621048017', 'customer', '2026-03-25 05:56:49', '2026-03-25 05:56:49', 0),
(51, 'devang@gmail.com', '$2y$10$uETfrzYwUHB/gfmbX7Ei7ODa2AkxBQIZCAv/SYoRAMEKE//NhkTA2', 'devang', 'P', '7862041878', 'customer', '2026-03-25 09:19:00', '2026-03-25 09:19:00', 0),
(55, 'qa_1776166621@test.local', '$2y$10$IKlqN1Zd6yFJfF1iwoYsQONpKbFrlaN8bMNPjjVOzmVeFcIPrQt9e', 'QA', 'Tester', '', 'customer', '2026-04-14 06:07:01', '2026-04-14 06:07:01', 0),
(56, 'qa2_1776166641@test.local', '$2y$10$4Xi.rTd/UhUCUSUniM.7kOc5D9KX2gjPm8Xfxq4/p4Fzs9vBfs1DW', 'QA', 'Tester', '', 'customer', '2026-04-14 06:07:21', '2026-04-14 06:07:21', 0),
(57, 'qa3_1776166668@test.local', '$2y$10$LW7Se9OkkY597KLYlUv8AehMrXoI.RJN0mvqsbNkvrKC26loS8.Aa', 'QA', 'Tester', '', 'customer', '2026-04-14 06:07:47', '2026-04-14 06:07:47', 0),
(58, 'qa5_1776166774@test.local', '$2y$10$mjMXcILFbpJY5jeSJ5teNeJvD56svSvrfmuSz7jyEy0nub0sxHTre', 'QA', 'User', '', 'customer', '2026-04-14 06:09:34', '2026-04-14 06:09:34', 0),
(66, 'ts_1776147967@test.uxpacific.local', '$2y$10$NZRny5Zq87pQiZBm968PIeUtHX7XHPeWfqq2TO5BrVCLqXckxyQS6', 'Test', 'Suite', '+91 9999999999', 'customer', '2026-04-14 06:26:07', '2026-04-14 06:26:07', 0),
(68, 'rmohit21264@gmail.com', '$2y$10$aQDXPN/v8rEYYimUbcYFKO5hdFZodkU4pJysaVB6dTusX6RsoRWFW', 'zula', '', '7863041880', 'customer', '2026-04-14 06:29:32', '2026-04-14 06:29:32', 0),
(69, 'social@uxpacific.com', '$2y$10$Wzz5s8j8VhaL9fYN1inZZOi.V0VFOvX7N2/Tpod7Dt3y8gYUiRTBC', 'Yugan', '', '7621049017', 'customer', '2026-04-14 09:26:44', '2026-04-14 09:26:44', 0),
(70, 'abc@gmail.com', '$2y$10$LssXnUQ4p2RD3ohHElpngeW/P.wZf1yjKxQ5P3YEe5JMJnnBbInxS', 'abc', '', '7863041880', 'customer', '2026-05-23 08:44:40', '2026-05-23 08:44:40', 0),
(71, 'rm2003@gmail.com', '$2y$10$0LZI10fLxovrgfbVzfdpLOYrlytqt0Bb/ax9Ap5D7XJiKAkMBkTd.', 'Mohit', '', '', 'customer', '2026-05-27 08:54:25', '2026-05-27 08:54:25', 0),
(72, 'testuser@example.com', '$2y$10$z6peaInDmwhG6taUCuJmPe4vNipUajSdZyThLkTwVArTi17rC79Ya', 'Test', 'User', '1234567890', 'customer', '2026-05-29 12:05:45', '2026-05-29 12:05:45', 0),
(73, 'dhruv@gmail.com', '$2y$10$A2XFQthHsiCoDxJQXUJAMe28CRSDBzilaC6LGr8B6Sk4DgnOpVt8i', 'Dhruv', 'Shah', '', 'customer', '2026-05-29 13:04:52', '2026-05-29 13:04:52', 0),
(74, 'zulauiux@gmail.com', '$2y$10$07V7AOYlg6mzYgLQSLvCB.moEN7xhJArDtPD9Mhd.wryLyQ/KfX.i', 'zula', '', '', 'customer', '2026-06-01 10:01:10', '2026-06-01 10:01:10', 0);

-- --------------------------------------------------------

--
-- Table structure for table `user_tokens`
--

CREATE TABLE `user_tokens` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `refresh_token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_tokens`
--

INSERT INTO `user_tokens` (`id`, `user_id`, `refresh_token`, `expires_at`, `created_at`) VALUES
(2, 32, 'c640c89c73a25bb16d945447bf942bbc682c7e82c0d61f1db9695a9a8a663684', '2026-02-25 12:21:48', '2026-02-18 16:51:48'),
(3, 32, '240815abffc71db552a8b4165c37b79dd6e6cbd17f4e167a7dca325ca9f8e20a', '2026-02-25 13:07:01', '2026-02-18 17:37:01'),
(4, 33, '4a7a634780b6db216e48fa09b2367d3ba9bbb01e7b7daa7d9912808a246a47b8', '2026-02-26 07:06:40', '2026-02-19 11:36:40'),
(15, 31, '9db8838dee74d44ad1b25d8f4719d4e1961b3bc6c67db5173188e1e5217ad8e6', '2026-03-03 11:24:48', '2026-02-24 15:54:48'),
(16, 48, '6216bf4f168ec9ae2087bf48b059ac4d0459d058150a12262914b31817f976ee', '2026-03-06 07:01:27', '2026-02-27 11:31:27'),
(17, 32, 'f3f6fc7266681b5bac8db4b9f5831033aeefaf95084a5a3fed5ebaddcead3aa1', '2026-03-13 06:46:39', '2026-03-06 11:16:39'),
(18, 32, 'ec1783aed6b11ed54cf11d393e883af010f63da7a7648f8bc3ab42b8c3e11ea8', '2026-03-20 07:29:10', '2026-03-13 11:59:10'),
(19, 49, 'e9e60d6761963a7b04684cf9f2a0c60db123e7716696b59ac6c046644c4a6a09', '2026-03-26 09:42:09', '2026-03-19 14:12:09'),
(32, 57, 'da8447d5440c3c95b2939750a290140a771bd43e53a462224e2fc69aa8b1c8e3', '2026-04-21 08:07:48', '2026-04-14 11:37:48'),
(33, 57, 'bd02a2cab47db0e7afcd3f80acffaa0da3f2e2cfa5a8acbc095c9479417a12a2', '2026-04-21 08:08:05', '2026-04-14 11:38:05'),
(34, 57, '334d21100ff7cdf15027a9356b9f542eea2708241ccdd04f7f2f49340327ad48', '2026-04-21 08:09:01', '2026-04-14 11:39:01'),
(35, 58, 'bfe0be3edfd57093beee2210e0ac5c959ddde8bcf169c35d6bf6953822eb732c', '2026-04-21 08:09:35', '2026-04-14 11:39:35'),
(36, 57, 'a4134e3a179d542ce50c7e7464f107b99accad0710f2c247f8948d295152592c', '2026-04-21 08:11:23', '2026-04-14 11:41:23'),
(42, 69, 'd56f7e63b0af3e87a383431603fe8ea8f200d24414faac5e9fc8eb840929cfb0', '2026-04-21 11:27:04', '2026-04-14 14:57:04');

-- --------------------------------------------------------

--
-- Table structure for table `wishlist`
--

CREATE TABLE `wishlist` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `bundle_id` int(11) DEFAULT NULL,
  `item_type` enum('product','bundle') NOT NULL DEFAULT 'product',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `addresses`
--
ALTER TABLE `addresses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `bundles`
--
ALTER TABLE `bundles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_bundle_active_featured` (`is_active`,`is_featured`),
  ADD KEY `idx_bundle_category` (`category`);

--
-- Indexes for table `bundle_items`
--
ALTER TABLE `bundle_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_bundle_product` (`bundle_id`,`product_id`),
  ADD KEY `idx_bundle_items_product` (`product_id`);

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_cart_user_item` (`user_id`,`_item_key`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `idx_cart_user_type` (`user_id`,`item_type`),
  ADD KEY `idx_cart_bundle_id` (`bundle_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `digital_downloads`
--
ALTER TABLE `digital_downloads`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_token` (`token`),
  ADD UNIQUE KEY `uq_order_item_resource` (`order_item_id`,`resource_id`),
  ADD KEY `idx_order_id` (`order_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_dd_resource` (`resource_id`);

--
-- Indexes for table `digital_resources`
--
ALTER TABLE `digital_resources`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_dr_product` (`product_id`,`is_active`,`sort_order`),
  ADD KEY `idx_dr_bundle` (`bundle_id`,`is_active`,`sort_order`);

--
-- Indexes for table `featured_items`
--
ALTER TABLE `featured_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_featured_item` (`item_type`,`item_id`);

--
-- Indexes for table `freebies`
--
ALTER TABLE `freebies`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `inventory_movements`
--
ALTER TABLE `inventory_movements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_inventory_item` (`item_type`,`item_id`,`created_at`),
  ADD KEY `idx_inventory_admin` (`admin_id`);

--
-- Indexes for table `inventory_reservations`
--
ALTER TABLE `inventory_reservations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_reservation_order` (`order_id`),
  ADD KEY `idx_reservation_item` (`item_type`,`item_id`),
  ADD KEY `idx_reservation_expires` (`expires_at`,`released_at`,`consumed_at`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_number` (`order_number`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_orders_user_status` (`user_id`,`status`),
  ADD KEY `idx_orders_razorpay` (`razorpay_order_id`),
  ADD KEY `idx_orders_payment_status` (`payment_status`),
  ADD KEY `idx_orders_paid_at` (`paid_at`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_token_hash` (`token_hash`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_products_active` (`is_active`),
  ADD KEY `idx_products_featured` (`is_featured`),
  ADD KEY `idx_products_category` (`category`,`is_active`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_reviews_product` (`product_id`),
  ADD KEY `idx_reviews_bundle` (`bundle_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_tokens`
--
ALTER TABLE `user_tokens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_wishlist_item` (`user_id`,`item_type`,`product_id`,`bundle_id`),
  ADD KEY `idx_wishlist_user` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `addresses`
--
ALTER TABLE `addresses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bundles`
--
ALTER TABLE `bundles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `bundle_items`
--
ALTER TABLE `bundle_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17324;

--
-- AUTO_INCREMENT for table `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `digital_downloads`
--
ALTER TABLE `digital_downloads`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `digital_resources`
--
ALTER TABLE `digital_resources`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `featured_items`
--
ALTER TABLE `featured_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `freebies`
--
ALTER TABLE `freebies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `inventory_movements`
--
ALTER TABLE `inventory_movements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `inventory_reservations`
--
ALTER TABLE `inventory_reservations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=112;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=113;

--
-- AUTO_INCREMENT for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=70;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=75;

--
-- AUTO_INCREMENT for table `user_tokens`
--
ALTER TABLE `user_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `wishlist`
--
ALTER TABLE `wishlist`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `addresses`
--
ALTER TABLE `addresses`
  ADD CONSTRAINT `addresses_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_ibfk_3` FOREIGN KEY (`bundle_id`) REFERENCES `bundles` (`id`) ON DELETE CASCADE;

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
-- Constraints for table `user_tokens`
--
ALTER TABLE `user_tokens`
  ADD CONSTRAINT `user_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
