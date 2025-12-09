-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 09, 2025 at 10:14 AM
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
-- Database: `nearbuy`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_notifications`
--

CREATE TABLE `admin_notifications` (
  `id` int(11) NOT NULL,
  `shop_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `status` varchar(20) DEFAULT 'unread',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(1, '/NearBuy/public/assets/banner-nearbuy.jpg', NULL, 1, 0, '2025-11-14 08:51:04');

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
(0, 7, 'active', '2025-12-09 06:49:09', NULL),
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
(0, 0, 5, NULL, 1, 15000.00, '2025-12-09 06:49:09', NULL),
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
(0, 4, NULL, 1, 15000.00, 0.00, 1500.00, 0.00, 16500.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'menunggu_pembayaran', '2025-12-09 07:07:09', NULL),
(1, 2, 3, 1, 160000.00, 15000.00, 1500.00, 0.00, 176500.00, 'bca_va', '', 'Miru', '09887654', '', 'Bangka Belitung', '', '', 'diproses', '2025-11-13 20:24:38', '2025-11-13 20:30:57'),
(2, 2, 4, 1, 160000.00, 15000.00, 1500.00, 0.00, 176500.00, 'bca_va', '', 'Miru', '09887654', '', 'Bangka Belitung', '', '', 'selesai', '2025-11-14 08:32:44', '2025-11-28 09:40:39'),
(3, 3, 5, 1, 160000.00, 15000.00, 1500.00, 0.00, 176500.00, 'alfamart', '', 'Mirza', '087478476', '', 'Pangkalpinang', '', '', 'selesai', '2025-11-14 09:11:04', '2025-11-28 09:40:32'),
(4, 3, 6, 1, 25000.00, 15000.00, 1500.00, 0.00, 41500.00, 'bca_va', '', 'Mirza', '087478476', '', 'Pangkalpinang', '', '', 'dibatalkan', '2025-11-14 09:17:43', '2025-11-27 10:11:43'),
(5, 4, NULL, 1, 15000.00, 0.00, 1500.00, 0.00, 16500.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'menunggu_pembayaran', '2025-12-09 07:24:34', NULL),
(6, 4, NULL, 1, 15000.00, 0.00, 1500.00, 0.00, 16500.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'selesai', '2025-12-09 07:28:14', '2025-12-09 08:20:25'),
(7, 4, NULL, 1, 15000.00, 0.00, 1500.00, 0.00, 16500.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'menunggu_pembayaran', '2025-12-09 07:39:34', NULL),
(8, 4, NULL, 1, 15000.00, 0.00, 1500.00, 0.00, 16500.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'selesai', '2025-12-09 07:39:59', '2025-12-09 08:13:54'),
(9, 4, NULL, 1, 15000.00, 0.00, 1500.00, 0.00, 16500.00, 'cod', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'selesai', '2025-12-09 07:56:37', '2025-12-09 08:10:44'),
(10, 4, NULL, 1, 12000.00, 0.00, 1500.00, 0.00, 13500.00, 'cod', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'menunggu_pembayaran', '2025-12-09 08:08:23', NULL),
(11, 6, NULL, 1, 15000.00, 0.00, 1500.00, 0.00, 16500.00, 'cod', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'menunggu_pembayaran', '2025-12-09 08:21:55', NULL),
(12, 4, NULL, 1, 12000.00, 0.00, 1500.00, 0.00, 13500.00, 'cod', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'menunggu_pembayaran', '2025-12-09 08:23:00', NULL),
(13, 4, NULL, 1, 12000.00, 0.00, 1500.00, 0.00, 13500.00, 'cod', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'menunggu_pembayaran', '2025-12-09 08:23:58', NULL),
(14, 3, NULL, 1, 16000.00, 0.00, 1500.00, 0.00, 17500.00, 'cod', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'menunggu_pembayaran', '2025-12-09 09:09:03', NULL);

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
(0, 0, 5, NULL, 'Baju Polos', 1, 15000.00, 15000.00, 1500.00, 1500.00, '2025-12-09 07:07:09'),
(1, 1, 1, NULL, 'Lollipop - SUSS16 Tumbler Straw Cup 750ml (Insulated Bottle | Stainless Steel Tumbler / SUS 316)', 1, 160000.00, 160000.00, 1500.00, 1500.00, '2025-11-13 20:24:39'),
(2, 2, 1, NULL, 'Lollipop - SUSS16 Tumbler Straw Cup 750ml (Insulated Bottle | Stainless Steel Tumbler / SUS 316)', 1, 160000.00, 160000.00, 1500.00, 1500.00, '2025-11-14 08:32:44'),
(3, 3, 1, NULL, 'Lollipop - SUSS16 Tumbler Straw Cup 750ml (Insulated Bottle | Stainless Steel Tumbler / SUS 316)', 1, 160000.00, 160000.00, 1500.00, 1500.00, '2025-11-14 09:11:04'),
(4, 4, 2, NULL, 'Baju', 1, 25000.00, 25000.00, 1500.00, 1500.00, '2025-11-14 09:17:43'),
(5, 5, 5, NULL, 'Baju Polos', 1, 15000.00, 15000.00, 1500.00, 1500.00, '2025-12-09 07:24:34'),
(6, 6, 5, NULL, 'Baju Polos', 1, 15000.00, 15000.00, 1500.00, 1500.00, '2025-12-09 07:28:14'),
(7, 7, 5, NULL, 'Baju Polos', 1, 15000.00, 15000.00, 1500.00, 1500.00, '2025-12-09 07:39:34'),
(8, 8, 5, NULL, 'Baju Polos', 1, 15000.00, 15000.00, 1500.00, 1500.00, '2025-12-09 07:39:59'),
(9, 9, 5, NULL, 'Baju Polos', 1, 15000.00, 15000.00, 1500.00, 1500.00, '2025-12-09 07:56:37'),
(10, 10, 4, NULL, 'Galon', 1, 12000.00, 12000.00, 1500.00, 1500.00, '2025-12-09 08:08:23'),
(11, 11, 5, NULL, 'Baju Polos', 1, 15000.00, 15000.00, 1500.00, 1500.00, '2025-12-09 08:21:55'),
(12, 12, 4, NULL, 'Galon', 1, 12000.00, 12000.00, 1500.00, 1500.00, '2025-12-09 08:23:00'),
(13, 13, 4, NULL, 'Galon', 1, 12000.00, 12000.00, 1500.00, 1500.00, '2025-12-09 08:23:58'),
(14, 14, 6, NULL, 'Beras', 1, 16000.00, 16000.00, 1500.00, 1500.00, '2025-12-09 09:09:03');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `shop_id` bigint(20) UNSIGNED DEFAULT NULL,
  `seller_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(220) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(12,2) NOT NULL,
  `compare_price` decimal(12,2) DEFAULT NULL,
  `discount` int(11) DEFAULT 0,
  `stock` int(11) NOT NULL,
  `sku` varchar(80) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `popularity` int(11) NOT NULL DEFAULT 0,
  `is_promo` tinyint(1) NOT NULL DEFAULT 0,
  `main_image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `shop_id`, `seller_id`, `title`, `slug`, `description`, `price`, `compare_price`, `discount`, `stock`, `sku`, `is_active`, `popularity`, `is_promo`, `main_image`, `created_at`, `updated_at`) VALUES
(3, 3, 6, 'Baju', 'baju', 'Baju Open Custom', 10000.00, NULL, 0, 10, NULL, 1, 0, 0, NULL, '2025-12-08 12:18:57', '2025-12-08 12:27:20'),
(4, 3, 6, 'Galon', 'galon', '15 liter', 12000.00, NULL, 0, 17, NULL, 1, 0, 0, NULL, '2025-12-08 12:46:31', '2025-12-09 08:23:58'),
(5, 2, 4, 'Baju Polos', 'baju-polos', '', 15000.00, NULL, 0, 43, NULL, 1, 0, 0, 'products/product_20251208_140334_103680.jpg', '2025-12-08 13:03:34', '2025-12-09 08:21:55'),
(6, 4, 8, 'Beras', 'beras', 'Harga tertera adalah harga per 1 kilogram', 16000.00, NULL, 0, 9, NULL, 1, 0, 0, NULL, '2025-12-09 08:58:28', '2025-12-09 09:09:03');

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
(2, 10),
(3, 1),
(3, 10),
(4, 1),
(4, 11);

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
-- Table structure for table `shops`
--

CREATE TABLE `shops` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(150) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `latitude` decimal(10,7) NOT NULL,
  `longitude` decimal(10,7) NOT NULL,
  `description` text DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `package_code` varchar(20) DEFAULT NULL,
  `last_payment_proof` varchar(255) DEFAULT NULL,
  `package_status` enum('none','waiting_payment','active','expired') NOT NULL DEFAULT 'none',
  `package_started_at` datetime DEFAULT NULL,
  `package_expires_at` datetime DEFAULT NULL,
  `product_limit` int(11) DEFAULT 0,
  `subscription_status` enum('none','waiting_payment','active','expired') NOT NULL DEFAULT 'none',
  `has_qris` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `shops`
--

INSERT INTO `shops` (`id`, `user_id`, `name`, `address`, `latitude`, `longitude`, `description`, `logo`, `is_active`, `created_at`, `updated_at`, `package_code`, `last_payment_proof`, `package_status`, `package_started_at`, `package_expires_at`, `product_limit`, `subscription_status`, `has_qris`) VALUES
(1, 3, 'Toko Contoh NearBuy', 'Pangkalpinang', -2.1291000, 106.1090000, 'Toko contoh kebutuhan harian di sekitar Pangkalpinang', NULL, 1, '2025-12-01 17:46:36', NULL, NULL, NULL, 'none', NULL, NULL, 0, 'none', 0),
(2, 4, 'Warung Rama', 'Pangkalpinang', -2.1291000, 106.1090000, 'Menyediakan Pulsa, Token Listrik, Isi Ulang Paket Internet dan kebutuhan smartphone Anda', NULL, 1, '2025-12-06 07:14:34', '2025-12-08 12:19:36', 'Paket Starter', '/NearBuy-Marketplace/public/uploads/payment_proofs/2_1765191092.png', 'active', '2025-12-08 19:19:36', '2026-01-07 19:19:36', 15, 'active', 0),
(3, 6, 'Mirru\'s Store', 'Jl Brig Hasan Basri Kec Amuntai Tengan Kab. HSU', -999.9999999, -999.9999999, 'Jual atau Jasa Top Up Game', NULL, 1, '2025-12-06 19:11:33', '2025-12-08 13:01:06', 'Paket Premium', '/NearBuy-Marketplace/public/uploads/payment_proofs/3_1765193269.png', 'active', '2025-12-08 18:39:05', '2026-01-07 18:39:05', 9999, 'active', 0),
(4, 8, 'Warung Serba Ada', 'Jl Palembang', -3.0472684, 104.7491455, NULL, NULL, 1, '2025-12-09 08:37:46', '2025-12-09 08:48:46', 'Paket Premium', '/NearBuy-Marketplace/public/uploads/payment_proofs/4_1765269548.png', 'waiting_payment', NULL, NULL, 99999, 'active', 0);

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
('home_banner', '/NearBuy/public/assets/banner-nearbuy.jpg'),
('home_banner', '/NearBuy/public/assets/banner-nearbuy.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` int(11) NOT NULL,
  `buyer_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `jumlah` int(11) NOT NULL,
  `total_harga` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `full_name` varchar(120) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `gender` enum('male','female','other') DEFAULT NULL,
  `birth_date` date DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `city` varchar(120) DEFAULT NULL,
  `province` varchar(120) DEFAULT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('admin','pengguna','seller') NOT NULL DEFAULT 'pengguna',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `points` int(11) NOT NULL DEFAULT 0,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `email`, `phone`, `gender`, `birth_date`, `address`, `city`, `province`, `postal_code`, `password_hash`, `role`, `is_active`, `created_at`, `updated_at`, `points`, `latitude`, `longitude`) VALUES
(1, 'Administrator', 'admin@nearbuy.com', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'admin123', 'admin', 1, '2025-12-01 17:10:04', NULL, 0, NULL, NULL),
(2, 'Mirza', 'mirza123@gmail.com', '0834567878', 'female', NULL, NULL, 'Pangkalpinang', NULL, NULL, '$2y$10$lRS6jJL8t/ItRYrbkZf0Vufgfk68BXsw1oTz9DixxQr3c8lSME5r6', 'pengguna', 1, '2025-12-03 14:43:33', NULL, 0, NULL, NULL),
(3, 'Miru', 'miru@gmail.com', '085369410097', NULL, NULL, NULL, '-', NULL, NULL, '$2y$10$TDhdRJdG6I6XcSoXm5Q2CedXhGlS9gzajQ9bsyPsmJikB1jadv19a', 'pengguna', 1, '2025-12-05 16:04:46', NULL, 0, NULL, NULL),
(4, 'Ramama', 'Ramama@gmail.com', '085369410097', 'male', '2002-05-22', 'Jl. Melati', 'gabek', 'Bangka Belitung', '45677', '$2y$10$MhhrQAQXYkF7xOHjRslDpeACFHnKVxH1XoEYBSmx324F24Dqc1Jqm', 'seller', 1, '2025-12-05 16:58:22', '2025-12-08 12:47:48', 0, -2.0912294, 106.1111927),
(5, 'Admin', 'Admin123@gmail.com', '08879874982', 'female', NULL, 'Jl Bukit Tani', 'Pangkalpinang', 'Bangka Belitung', '45677', '$2y$10$iKnrRirrT66NFteWXOpMrOON.Kr.iWNCEd030C1t/CPVhYxGMUm6S', 'admin', 1, '2025-12-06 17:00:57', NULL, 0, NULL, NULL),
(6, 'Okta', 'okta123@gmail.com', '085369410097', 'male', '2004-10-31', '-', '-', '-', '45677', '$2y$10$9qT7OmtTpKYYuN0TkrBdpe2R3e6BAU5.ICRogGrJFvz7L9TEG7cvm', 'seller', 1, '2025-12-06 19:07:23', '2025-12-08 12:45:33', 0, -2.1001927, 106.1298180),
(7, 'Miru123', 'Miru123@gmail.com', '08907956465', 'male', '2010-04-07', 'Jl. Batik Tikal', 'Pangkalpinang', 'Bangka Belitung', '45677', '$2y$10$Oewc33Kdy3lSzcK3NdzyC.6e1S3EFsaBfzaaoo6MC0t04GI0OvBMu', 'pengguna', 1, '2025-12-08 13:06:41', '2025-12-09 06:47:55', 0, -2.0701156, 106.0757863),
(8, 'Mmm', 'mmm123@gmail.com', '089868758', 'male', '2009-03-16', 'Jl Mawar', 'Pangkalpinang', 'Bangka Belitung', '22422', '$2y$10$2I8ZyUp/oYRjhLFSh9f2nOGXfQcDZcqioQG4gz/aZoaB1LCSc8lsy', 'seller', 1, '2025-12-09 08:27:36', '2025-12-09 08:38:16', 0, -2.7256690, 104.7054310);

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
(3, 11),
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
  `id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `discount_type` enum('percent','fixed') NOT NULL,
  `discount_value` decimal(10,2) NOT NULL,
  `min_purchase` decimal(10,2) DEFAULT 0.00,
  `max_discount` decimal(10,2) DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `voucher_claims`
--

CREATE TABLE `voucher_claims` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `voucher_id` int(11) NOT NULL,
  `claimed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('claimed','used','expired') DEFAULT 'claimed'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_user_recommendations`
-- (See below for the actual view)
--
CREATE TABLE `v_user_recommendations` (
`user_id` int(11)
,`product_id` int(11)
,`slug` varchar(220)
,`title` varchar(255)
,`price` decimal(12,2)
,`compare_price` decimal(12,2)
,`main_image` varchar(255)
,`stock` int(11)
,`popularity` int(11)
,`created_at` timestamp
,`category_name` varchar(120)
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

--
-- Dumping data for table `wishlists`
--

INSERT INTO `wishlists` (`id`, `user_id`, `product_id`, `created_at`) VALUES
(1, 2, 1, '2025-11-28 11:30:00'),
(1, 2, 1, '2025-11-28 11:30:00');

-- --------------------------------------------------------

--
-- Structure for view `v_user_recommendations`
--
DROP TABLE IF EXISTS `v_user_recommendations`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_user_recommendations`  AS SELECT `u`.`id` AS `user_id`, `p`.`id` AS `product_id`, `p`.`slug` AS `slug`, `p`.`title` AS `title`, `p`.`price` AS `price`, `p`.`compare_price` AS `compare_price`, `p`.`main_image` AS `main_image`, `p`.`stock` AS `stock`, `p`.`popularity` AS `popularity`, `p`.`created_at` AS `created_at`, `c`.`name` AS `category_name` FROM ((((`users` `u` join `user_preferences` `up` on(`up`.`user_id` = `u`.`id`)) join `product_categories` `pc` on(`pc`.`category_id` = `up`.`category_id`)) join `products` `p` on(`p`.`id` = `pc`.`product_id`)) join `categories` `c` on(`c`.`id` = `up`.`category_id`)) WHERE `p`.`is_active` = 11 ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_notifications`
--
ALTER TABLE `admin_notifications`
  ADD PRIMARY KEY (`id`);

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
  ADD KEY `seller_id` (`seller_id`);

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
-- Indexes for table `shops`
--
ALTER TABLE `shops`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `buyer_id` (`buyer_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_notifications`
--
ALTER TABLE `admin_notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `shops`
--
ALTER TABLE `shops`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`seller_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`buyer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `transactions_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
