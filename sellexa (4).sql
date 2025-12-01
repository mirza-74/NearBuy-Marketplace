-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 28, 2025 at 01:28 PM
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
-- Database: `sellexa`
--

-- --------------------------------------------------------

--
-- Table structure for table `banners`
--

CREATE TABLE `banners` (
  `id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `link_url` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `banners`
--

INSERT INTO `banners` (`id`, `image_path`, `link_url`, `is_active`, `sort_order`, `created_at`) VALUES
(1, '/Marketplace_SellExa/public/uploads/banners/banner_20251114_095104_58e986e4.jpeg', NULL, 1, 0, '2025-11-14 08:51:04'),
(2, '/Marketplace_SellExa/public/uploads/banners/banner_20251128_103934_c7211070.png', NULL, 0, 0, '2025-11-28 09:39:34');

-- --------------------------------------------------------

--
-- Table structure for table `carts`
--

CREATE TABLE `carts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `status` enum('active','ordered','abandoned','cancelled') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `carts`
--

INSERT INTO `carts` (`id`, `user_id`, `status`, `created_at`, `updated_at`) VALUES
(2, 2, 'ordered', '2025-11-13 08:14:12', '2025-11-13 09:27:03'),
(3, 2, 'ordered', '2025-11-13 09:57:37', '2025-11-13 20:24:39'),
(4, 2, 'ordered', '2025-11-14 08:30:43', '2025-11-14 08:32:44'),
(5, 3, 'ordered', '2025-11-14 09:10:34', '2025-11-14 09:11:04'),
(6, 3, 'ordered', '2025-11-14 09:17:14', '2025-11-14 09:17:43'),
(7, 3, 'active', '2025-11-27 18:14:35', NULL),
(8, 2, 'active', '2025-11-28 11:17:49', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `cart_items`
--

CREATE TABLE `cart_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `cart_id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `variant_id` bigint(20) UNSIGNED DEFAULT NULL,
  `qty` int(11) NOT NULL DEFAULT 1,
  `price` decimal(12,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cart_items`
--

INSERT INTO `cart_items` (`id`, `cart_id`, `product_id`, `variant_id`, `qty`, `price`, `created_at`, `updated_at`) VALUES
(1, 2, 1, NULL, 2, 160000.00, '2025-11-13 08:14:13', '2025-11-13 09:26:34'),
(3, 3, 1, NULL, 1, 160000.00, '2025-11-13 18:24:46', '2025-11-13 20:16:37'),
(4, 4, 1, NULL, 1, 160000.00, '2025-11-14 08:30:43', '2025-11-14 08:32:16'),
(5, 5, 1, NULL, 1, 160000.00, '2025-11-14 09:10:34', '2025-11-14 09:10:49'),
(6, 6, 2, NULL, 1, 25000.00, '2025-11-14 09:17:14', NULL),
(7, 7, 1, NULL, 3, 160000.00, '2025-11-27 18:14:35', '2025-11-28 09:26:34');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(120) NOT NULL,
  `slug` varchar(140) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `slug`, `is_active`, `sort_order`, `created_at`, `updated_at`) VALUES
(1, 'Semua', 'semua', 1, 0, '2025-10-24 09:40:53', NULL),
(2, 'Promo', 'promo', 1, 1, '2025-10-24 09:40:53', NULL),
(3, 'Aksesoris', 'aksesoris', 1, 2, '2025-10-24 09:40:53', NULL),
(4, 'Bayi & Anak', 'bayi-dan-anak', 1, 3, '2025-10-24 09:40:53', NULL),
(5, 'Elektronik', 'elektronik', 1, 4, '2025-10-24 09:40:53', NULL),
(6, 'Kesehatan & Kecantikan', 'kesehatan-kecantikan', 1, 5, '2025-10-24 09:40:53', NULL),
(7, 'Makanan & Minuman', 'makanan-minuman', 1, 6, '2025-10-24 09:40:53', NULL),
(8, 'Olahraga', 'olahraga', 1, 7, '2025-10-24 09:40:53', NULL),
(9, 'Otomotif', 'otomotif', 1, 8, '2025-10-24 09:40:53', NULL),
(10, 'Pakaian/Fashion', 'pakaian-fashion', 1, 9, '2025-10-24 09:40:53', NULL),
(11, 'Peralatan Rumah Tangga', 'peralatan-rumah-tangga', 1, 10, '2025-10-24 09:40:53', NULL),
(12, 'Peralatan Sekolah', 'peralatan-sekolah', 1, 11, '2025-10-24 09:40:53', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `cart_id` bigint(20) UNSIGNED DEFAULT NULL,
  `total_items` int(11) NOT NULL DEFAULT 0,
  `total_barang` decimal(14,2) NOT NULL DEFAULT 0.00,
  `total_ongkir` decimal(14,2) NOT NULL DEFAULT 0.00,
  `total_pajak_admin` decimal(14,2) NOT NULL DEFAULT 0.00,
  `total_diskon` decimal(14,2) NOT NULL DEFAULT 0.00,
  `grand_total` decimal(14,2) NOT NULL DEFAULT 0.00,
  `payment_method` varchar(50) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `ship_name` varchar(120) DEFAULT NULL,
  `ship_phone` varchar(30) DEFAULT NULL,
  `ship_address` varchar(255) DEFAULT NULL,
  `ship_city` varchar(120) DEFAULT NULL,
  `ship_province` varchar(120) DEFAULT NULL,
  `ship_postal_code` varchar(20) DEFAULT NULL,
  `status` enum('menunggu_pembayaran','diproses','dikirim','selesai','dibatalkan') NOT NULL DEFAULT 'menunggu_pembayaran',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `cart_id`, `total_items`, `total_barang`, `total_ongkir`, `total_pajak_admin`, `total_diskon`, `grand_total`, `payment_method`, `note`, `ship_name`, `ship_phone`, `ship_address`, `ship_city`, `ship_province`, `ship_postal_code`, `status`, `created_at`, `updated_at`) VALUES
(1, 2, 3, 1, 160000.00, 15000.00, 1500.00, 0.00, 176500.00, 'bca_va', '', 'Miru', '09887654', '', 'Bangka Belitung', '', '', 'diproses', '2025-11-13 20:24:38', '2025-11-13 20:30:57'),
(2, 2, 4, 1, 160000.00, 15000.00, 1500.00, 0.00, 176500.00, 'bca_va', '', 'Miru', '09887654', '', 'Bangka Belitung', '', '', 'selesai', '2025-11-14 08:32:44', '2025-11-28 09:40:39'),
(3, 3, 5, 1, 160000.00, 15000.00, 1500.00, 0.00, 176500.00, 'alfamart', '', 'Mirza', '087478476', '', 'Pangkalpinang', '', '', 'selesai', '2025-11-14 09:11:04', '2025-11-28 09:40:32'),
(4, 3, 6, 1, 25000.00, 15000.00, 1500.00, 0.00, 41500.00, 'bca_va', '', 'Mirza', '087478476', '', 'Pangkalpinang', '', '', 'dibatalkan', '2025-11-14 09:17:43', '2025-11-27 10:11:43');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `order_id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `variant_id` bigint(20) UNSIGNED DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `qty` int(11) NOT NULL DEFAULT 1,
  `price` decimal(14,2) NOT NULL DEFAULT 0.00,
  `subtotal` decimal(14,2) NOT NULL DEFAULT 0.00,
  `admin_fee_per_item` decimal(14,2) NOT NULL DEFAULT 1500.00,
  `admin_fee_total` decimal(14,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `variant_id`, `title`, `qty`, `price`, `subtotal`, `admin_fee_per_item`, `admin_fee_total`, `created_at`) VALUES
(1, 1, 1, NULL, 'Lollipop - SUSS16 Tumbler Straw Cup 750ml (Insulated Bottle | Stainless Steel Tumbler / SUS 316)', 1, 160000.00, 160000.00, 1500.00, 1500.00, '2025-11-13 20:24:39'),
(2, 2, 1, NULL, 'Lollipop - SUSS16 Tumbler Straw Cup 750ml (Insulated Bottle | Stainless Steel Tumbler / SUS 316)', 1, 160000.00, 160000.00, 1500.00, 1500.00, '2025-11-14 08:32:44'),
(3, 3, 1, NULL, 'Lollipop - SUSS16 Tumbler Straw Cup 750ml (Insulated Bottle | Stainless Steel Tumbler / SUS 316)', 1, 160000.00, 160000.00, 1500.00, 1500.00, '2025-11-14 09:11:04'),
(4, 4, 2, NULL, 'Baju', 1, 25000.00, 25000.00, 1500.00, 1500.00, '2025-11-14 09:17:43');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(200) NOT NULL,
  `slug` varchar(220) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `compare_price` decimal(12,2) DEFAULT NULL,
  `discount` int(11) DEFAULT 0,
  `stock` int(11) NOT NULL DEFAULT 0,
  `sku` varchar(80) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `main_image` varchar(255) DEFAULT NULL,
  `popularity` int(11) NOT NULL DEFAULT 0,
  `is_promo` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `title`, `slug`, `description`, `price`, `compare_price`, `discount`, `stock`, `sku`, `is_active`, `main_image`, `popularity`, `is_promo`, `created_at`, `updated_at`) VALUES
(1, 'Lollipop - SUSS16 Tumbler Straw Cup 750ml (Insulated Bottle | Stainless Steel Tumbler / SUS 316)', 'lollipop-suss16-tumbler-straw-cup-750ml-insulated-bottle-stainless-steel-tumbler-sus-316', 'Tumblr 750ml\r\n\r\n(Insulated Bottle | Stainless Steel Tumblr)\r\n\r\nTahan Es batu 24 Jam\r\n\r\n- spesifikasi\r\n\r\nmaterial dalam: stainless steel sus 316\r\n\r\nvolume: 750ml\r\n\r\ndengan tali\r\n\r\nFOOD GRADE', 160000.00, NULL, 0, 17, NULL, 1, 'products/product_20251107_051149_ce58ea.jpg', 14, 0, '2025-11-07 04:11:49', '2025-11-28 11:17:49'),
(2, 'Baju', 'baju', 'Baju Polos Bangka', 25000.00, NULL, 0, 49, NULL, 1, 'products/product_20251114_101414_c1b886.jpg', 1, 0, '2025-11-14 09:14:14', '2025-11-14 09:17:43');

-- --------------------------------------------------------

--
-- Table structure for table `product_categories`
--

CREATE TABLE `product_categories` (
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `category_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_categories`
--

INSERT INTO `product_categories` (`product_id`, `category_id`) VALUES
(1, 1),
(1, 11),
(2, 1),
(2, 10);

-- --------------------------------------------------------

--
-- Table structure for table `product_images`
--

CREATE TABLE `product_images` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `variant_id` bigint(20) UNSIGNED DEFAULT NULL,
  `image_path` varchar(255) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_reviews`
--

CREATE TABLE `product_reviews` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `order_id` int(10) UNSIGNED NOT NULL,
  `rating` tinyint(4) NOT NULL,
  `comment` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_variants`
--

CREATE TABLE `product_variants` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(120) NOT NULL,
  `sku` varchar(80) DEFAULT NULL,
  `price` decimal(12,2) DEFAULT NULL,
  `compare_price` decimal(12,2) DEFAULT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `image` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `site_settings`
--

CREATE TABLE `site_settings` (
  `key` varchar(64) NOT NULL,
  `value` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `site_settings`
--

INSERT INTO `site_settings` (`key`, `value`) VALUES
('home_banner', '/Marketplace_SellExa/public/uploads/banners/banner_20251114_094828_d986c1ca.jpeg');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `email` varchar(190) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(120) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `gender` enum('male','female','other') DEFAULT NULL,
  `birth_date` date DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `city` varchar(120) DEFAULT NULL,
  `province` varchar(120) DEFAULT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `role` enum('admin','pengguna') NOT NULL DEFAULT 'pengguna',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `points` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `email`, `password_hash`, `full_name`, `phone`, `gender`, `birth_date`, `address`, `city`, `province`, `postal_code`, `role`, `is_active`, `created_at`, `updated_at`, `points`) VALUES
(1, 'Admin123@gmail.com', '$2y$10$WoZRq/hKOiNtuKfDN1CFVe2CysC2VBRh0CQdNzNQJ4.pzVY/PxRYG', 'Admin123', '09876543456789', 'female', NULL, NULL, 'Pangkalpinang', NULL, NULL, 'admin', 1, '2025-10-30 07:59:18', NULL, 0),
(2, 'miru123@gmail.com', '$2y$10$wnuC.uF/BJDOyzxHuJwDKe3WCj6vN/WXwFiO.2S1RB6IW9GpT7c2K', 'Miru', '09887654', 'female', NULL, NULL, 'Bangka Belitung', NULL, NULL, 'pengguna', 1, '2025-11-12 04:42:02', NULL, 0),
(3, 'mirza123@gmail.com', '$2y$10$enSs8tEk3JcotCqdwBcoIuXoAo04WHnQSLUk1CzwBxfBj0d9GChvW', 'Mirza', '087478476', 'female', NULL, NULL, 'Pangkalpinang', NULL, NULL, 'pengguna', 1, '2025-11-14 09:09:58', NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `user_preferences`
--

CREATE TABLE `user_preferences` (
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `category_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_preferences`
--

INSERT INTO `user_preferences` (`user_id`, `category_id`) VALUES
(2, 1),
(2, 11),
(3, 1),
(3, 10),
(3, 11);

-- --------------------------------------------------------

--
-- Table structure for table `vouchers`
--

CREATE TABLE `vouchers` (
  `id` int(10) UNSIGNED NOT NULL,
  `code` varchar(32) NOT NULL,
  `title` varchar(120) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `min_points` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `discount_type` enum('percent','nominal','free_shipping') DEFAULT 'percent',
  `discount_value` int(11) DEFAULT 0,
  `min_transaction` int(11) DEFAULT 0,
  `max_discount` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `voucher_claims`
--

CREATE TABLE `voucher_claims` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `voucher_id` int(10) UNSIGNED NOT NULL,
  `claimed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_user_recommendations`
-- (See below for the actual view)
--
CREATE TABLE `v_user_recommendations` (
`user_id` bigint(20) unsigned
,`product_id` bigint(20) unsigned
,`title` varchar(200)
,`slug` varchar(220)
,`price` decimal(12,2)
,`compare_price` decimal(12,2)
,`stock` int(11)
,`main_image` varchar(255)
,`popularity` int(11)
,`created_at` timestamp
,`matched_category` varchar(120)
,`matched_category_id` bigint(20) unsigned
);

-- --------------------------------------------------------

--
-- Table structure for table `wishlists`
--

CREATE TABLE `wishlists` (
  `id` int(11) UNSIGNED NOT NULL,
  `user_id` int(11) UNSIGNED NOT NULL,
  `product_id` int(11) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure for view `v_user_recommendations`
--
DROP TABLE IF EXISTS `v_user_recommendations`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_user_recommendations`  AS SELECT `u`.`id` AS `user_id`, `p`.`id` AS `product_id`, `p`.`title` AS `title`, `p`.`slug` AS `slug`, `p`.`price` AS `price`, `p`.`compare_price` AS `compare_price`, `p`.`stock` AS `stock`, `p`.`main_image` AS `main_image`, `p`.`popularity` AS `popularity`, `p`.`created_at` AS `created_at`, `c`.`name` AS `matched_category`, `c`.`id` AS `matched_category_id` FROM ((((`users` `u` join `user_preferences` `up` on(`up`.`user_id` = `u`.`id`)) join `product_categories` `pc` on(`pc`.`category_id` = `up`.`category_id`)) join `products` `p` on(`p`.`id` = `pc`.`product_id` and `p`.`is_active` = 1 and `p`.`stock` > 0)) join `categories` `c` on(`c`.`id` = `up`.`category_id`)) ORDER BY `u`.`id` ASC, `p`.`popularity` DESC, `p`.`created_at` DESC ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `banners`
--
ALTER TABLE `banners`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `carts`
--
ALTER TABLE `carts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_carts_user` (`user_id`);

--
-- Indexes for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_cart_product_variant` (`cart_id`,`product_id`,`variant_id`),
  ADD KEY `idx_ci_cart` (`cart_id`),
  ADD KEY `idx_ci_product` (`product_id`),
  ADD KEY `fk_ci_variant` (`variant_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_categories_slug` (`slug`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_orders_user` (`user_id`),
  ADD KEY `fk_orders_cart` (`cart_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_oi_order` (`order_id`),
  ADD KEY `fk_oi_product` (`product_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_products_slug` (`slug`),
  ADD KEY `idx_products_active_stock` (`is_active`,`stock`),
  ADD KEY `idx_products_popularity` (`popularity`);

--
-- Indexes for table `product_categories`
--
ALTER TABLE `product_categories`
  ADD PRIMARY KEY (`product_id`,`category_id`),
  ADD KEY `fk_pc_category` (`category_id`);

--
-- Indexes for table `product_images`
--
ALTER TABLE `product_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pi_product` (`product_id`),
  ADD KEY `idx_pi_variant` (`variant_id`);

--
-- Indexes for table `product_reviews`
--
ALTER TABLE `product_reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_product` (`product_id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_order` (`order_id`);

--
-- Indexes for table `product_variants`
--
ALTER TABLE `product_variants`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_variant_name_per_product` (`product_id`,`name`),
  ADD KEY `idx_pv_sku` (`sku`),
  ADD KEY `idx_pv_active_stock` (`is_active`,`stock`);

--
-- Indexes for table `site_settings`
--
ALTER TABLE `site_settings`
  ADD PRIMARY KEY (`key`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_users_email` (`email`);

--
-- Indexes for table `user_preferences`
--
ALTER TABLE `user_preferences`
  ADD PRIMARY KEY (`user_id`,`category_id`),
  ADD KEY `fk_up_category` (`category_id`);

--
-- Indexes for table `vouchers`
--
ALTER TABLE `vouchers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD KEY `idx_v_active_minpoints` (`is_active`,`min_points`);

--
-- Indexes for table `voucher_claims`
--
ALTER TABLE `voucher_claims`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_user_voucher` (`user_id`,`voucher_id`),
  ADD KEY `fk_vc_voucher` (`voucher_id`),
  ADD KEY `idx_vc_user` (`user_id`);

--
-- Indexes for table `wishlists`
--
ALTER TABLE `wishlists`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_user_product` (`user_id`,`product_id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_product` (`product_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `banners`
--
ALTER TABLE `banners`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `carts`
--
ALTER TABLE `carts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `cart_items`
--
ALTER TABLE `cart_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `product_images`
--
ALTER TABLE `product_images`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `product_reviews`
--
ALTER TABLE `product_reviews`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `product_variants`
--
ALTER TABLE `product_variants`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `vouchers`
--
ALTER TABLE `vouchers`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `voucher_claims`
--
ALTER TABLE `voucher_claims`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wishlists`
--
ALTER TABLE `wishlists`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `carts`
--
ALTER TABLE `carts`
  ADD CONSTRAINT `fk_carts_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD CONSTRAINT `fk_ci_cart` FOREIGN KEY (`cart_id`) REFERENCES `carts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ci_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  ADD CONSTRAINT `fk_ci_variant` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_orders_cart` FOREIGN KEY (`cart_id`) REFERENCES `carts` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `fk_oi_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_oi_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `product_categories`
--
ALTER TABLE `product_categories`
  ADD CONSTRAINT `fk_pc_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_pc_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_images`
--
ALTER TABLE `product_images`
  ADD CONSTRAINT `fk_pi_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_pi_variant` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_variants`
--
ALTER TABLE `product_variants`
  ADD CONSTRAINT `fk_pv_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_preferences`
--
ALTER TABLE `user_preferences`
  ADD CONSTRAINT `fk_up_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_up_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `voucher_claims`
--
ALTER TABLE `voucher_claims`
  ADD CONSTRAINT `fk_vc_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_vc_voucher` FOREIGN KEY (`voucher_id`) REFERENCES `vouchers` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
