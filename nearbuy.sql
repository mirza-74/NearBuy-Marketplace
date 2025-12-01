

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

--
-- Database: `nearbuy`
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
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `shops`
--

INSERT INTO `shops` (`id`, `user_id`, `name`, `address`, `latitude`, `longitude`, `description`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 3, 'Toko Contoh NearBuy', 'Pangkalpinang', -2.1291000, 106.1090000, 'Toko contoh kebutuhan harian di sekitar Pangkalpinang', 1, '2025-12-01 17:46:36', NULL);

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
  `nama` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','pengguna','seller') NOT NULL DEFAULT 'pengguna',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `nama`, `email`, `password`, `role`, `created_at`) VALUES
(1, 'Administrator', 'admin@nearbuy.com', 'admin123', 'admin', '2025-12-01 17:10:04');

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
(1, 2, 1, '2025-11-28 11:30:00');

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
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

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
