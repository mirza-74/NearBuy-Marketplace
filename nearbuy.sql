SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

SET NAMES utf8mb4;

CREATE TABLE admin_notifications (
  id INT(11) NOT NULL,
  shop_id INT(11) NOT NULL,
  message TEXT NOT NULL,
  status VARCHAR(20) DEFAULT 'unread',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE categories (
  id BIGINT(20) UNSIGNED NOT NULL,
  name VARCHAR(120) NOT NULL,
  slug VARCHAR(140) NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  sort_order INT(11) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO categories (id, name, slug, is_active, sort_order, created_at, updated_at) VALUES
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

CREATE TABLE orders (
  id BIGINT(20) UNSIGNED NOT NULL,
  user_id BIGINT(20) UNSIGNED NOT NULL,
  cart_id BIGINT(20) UNSIGNED DEFAULT NULL,
  total_items INT(11) NOT NULL DEFAULT 0,
  total_barang DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  total_ongkir DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  total_pajak_admin DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  total_diskon DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  grand_total DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  payment_method VARCHAR(50) DEFAULT NULL,
  note TEXT DEFAULT NULL,
  ship_name VARCHAR(120) DEFAULT NULL,
  ship_phone VARCHAR(30) DEFAULT NULL,
  ship_address VARCHAR(255) DEFAULT NULL,
  ship_city VARCHAR(120) DEFAULT NULL,
  ship_province VARCHAR(120) DEFAULT NULL,
  ship_postal_code VARCHAR(20) DEFAULT NULL,
  status ENUM('menunggu_pembayaran','diproses','dikirim','selesai','dibatalkan') NOT NULL DEFAULT 'menunggu_pembayaran',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO orders (id, user_id, cart_id, total_items, total_barang, total_ongkir, total_pajak_admin, total_diskon, grand_total, payment_method, note, ship_name, ship_phone, ship_address, ship_city, ship_province, ship_postal_code, status, created_at, updated_at) VALUES
(0, 4, NULL, 1, '15000.00', '0.00', '1500.00', '0.00', '16500.00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'menunggu_pembayaran', '2025-12-09 07:07:09', NULL),
(1, 2, 3, 1, '160000.00', '15000.00', '1500.00', '0.00', '176500.00', 'bca_va', '', 'Miru', '09887654', '', 'Bangka Belitung', '', '', 'diproses', '2025-11-13 20:24:38', '2025-11-13 20:30:57'),
(2, 2, 4, 1, '160000.00', '15000.00', '1500.00', '0.00', '176500.00', 'bca_va', '', 'Miru', '09887654', '', 'Bangka Belitung', '', '', 'selesai', '2025-11-14 08:32:44', '2025-11-28 09:40:39'),
(3, 3, 5, 1, '160000.00', '15000.00', '1500.00', '0.00', '176500.00', 'alfamart', '', 'Mirza', '087478476', '', 'Pangkalpinang', '', '', 'selesai', '2025-11-14 09:11:04', '2025-11-28 09:40:32'),
(4, 3, 6, 1, '25000.00', '15000.00', '1500.00', '0.00', '41500.00', 'bca_va', '', 'Mirza', '087478476', '', 'Pangkalpinang', '', '', 'dibatalkan', '2025-11-14 09:17:43', '2025-11-27 10:11:43'),
(5, 4, NULL, 1, '15000.00', '0.00', '1500.00', '0.00', '16500.00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'menunggu_pembayaran', '2025-12-09 07:24:34', NULL),
(6, 4, NULL, 1, '15000.00', '0.00', '1500.00', '0.00', '16500.00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'selesai', '2025-12-09 07:28:14', '2025-12-09 08:20:25'),
(7, 4, NULL, 1, '15000.00', '0.00', '1500.00', '0.00', '16500.00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'menunggu_pembayaran', '2025-12-09 07:39:34', NULL),
(8, 4, NULL, 1, '15000.00', '0.00', '1500.00', '0.00', '16500.00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'selesai', '2025-12-09 07:39:59', '2025-12-09 08:13:54'),
(9, 4, NULL, 1, '15000.00', '0.00', '1500.00', '0.00', '16500.00', 'cod', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'selesai', '2025-12-09 07:56:37', '2025-12-09 08:10:44'),
(10, 4, NULL, 1, '12000.00', '0.00', '1500.00', '0.00', '13500.00', 'cod', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'menunggu_pembayaran', '2025-12-09 08:08:23', NULL),
(11, 6, NULL, 1, '15000.00', '0.00', '1500.00', '0.00', '16500.00', 'cod', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'menunggu_pembayaran', '2025-12-09 08:21:55', NULL),
(12, 4, NULL, 1, '12000.00', '0.00', '1500.00', '0.00', '13500.00', 'cod', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'menunggu_pembayaran', '2025-12-09 08:23:00', NULL),
(13, 4, NULL, 1, '12000.00', '0.00', '1500.00', '0.00', '13500.00', 'cod', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'menunggu_pembayaran', '2025-12-09 08:23:58', NULL),
(14, 3, NULL, 1, '16000.00', '0.00', '1500.00', '0.00', '17500.00', 'cod', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'menunggu_pembayaran', '2025-12-09 09:09:03', NULL),
(15, 9, NULL, 1, '12000.00', '0.00', '1500.00', '0.00', '13500.00', 'cod', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'selesai', '2025-12-09 09:40:27', '2025-12-09 09:41:44');

CREATE TABLE order_items (
  id BIGINT(20) UNSIGNED NOT NULL,
  order_id BIGINT(20) UNSIGNED NOT NULL,
  product_id BIGINT(20) UNSIGNED NOT NULL,
  variant_id BIGINT(20) UNSIGNED DEFAULT NULL,
  title VARCHAR(255) NOT NULL,
  qty INT(11) NOT NULL DEFAULT 1,
  price DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  subtotal DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  admin_fee_per_item DECIMAL(14,2) NOT NULL DEFAULT 1500.00,
  admin_fee_total DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO order_items (id, order_id, product_id, variant_id, title, qty, price, subtotal, admin_fee_per_item, admin_fee_total, created_at) VALUES
(0, 0, 5, NULL, 'Baju Polos', 1, '15000.00', '15000.00', '1500.00', '1500.00', '2025-12-09 07:07:09'),
(1, 1, 1, NULL, 'Lollipop - SUSS16 Tumbler Straw Cup 750ml (Insulated Bottle | Stainless Steel Tumbler / SUS 316)', 1, '160000.00', '160000.00', '1500.00', '1500.00', '2025-11-13 20:24:39'),
(2, 2, 1, NULL, 'Lollipop - SUSS16 Tumbler Straw Cup 750ml (Insulated Bottle | Stainless Steel Tumbler / SUS 316)', 1, '160000.00', '160000.00', '1500.00', '1500.00', '2025-11-14 08:32:44'),
(3, 3, 1, NULL, 'Lollipop - SUSS16 Tumbler Straw Cup 750ml (Insulated Bottle | Stainless Steel Tumbler / SUS 316)', 1, '160000.00', '160000.00', '1500.00', '1500.00', '2025-11-14 09:11:04'),
(4, 4, 2, NULL, 'Baju', 1, '25000.00', '25000.00', '1500.00', '1500.00', '2025-11-14 09:17:43'),
(5, 5, 5, NULL, 'Baju Polos', 1, '15000.00', '15000.00', '1500.00', '1500.00', '2025-12-09 07:24:34'),
(6, 6, 5, NULL, 'Baju Polos', 1, '15000.00', '15000.00', '1500.00', '1500.00', '2025-12-09 07:28:14'),
(7, 7, 5, NULL, 'Baju Polos', 1, '15000.00', '15000.00', '1500.00', '1500.00', '2025-12-09 07:39:34'),
(8, 8, 5, NULL, 'Baju Polos', 1, '15000.00', '15000.00', '1500.00', '1500.00', '2025-12-09 07:39:59'),
(9, 9, 5, NULL, 'Baju Polos', 1, '15000.00', '15000.00', '1500.00', '1500.00', '2025-12-09 07:56:37'),
(10, 10, 4, NULL, 'Galon', 1, '12000.00', '12000.00', '1500.00', '1500.00', '2025-12-09 08:08:23'),
(11, 11, 5, NULL, 'Baju Polos', 1, '15000.00', '15000.00', '1500.00', '1500.00', '2025-12-09 08:21:55'),
(12, 12, 4, NULL, 'Galon', 1, '12000.00', '12000.00', '1500.00', '1500.00', '2025-12-09 08:23:00'),
(13, 13, 4, NULL, 'Galon', 1, '12000.00', '12000.00', '1500.00', '1500.00', '2025-12-09 08:23:58'),
(14, 14, 6, NULL, 'Beras', 1, '16000.00', '16000.00', '1500.00', '1500.00', '2025-12-09 09:09:03'),
(15, 15, 7, NULL, 'crofle', 1, '12000.00', '12000.00', '1500.00', '1500.00', '2025-12-09 09:40:27');

CREATE TABLE products (
  id INT(11) NOT NULL,
  shop_id BIGINT(20) UNSIGNED DEFAULT NULL,
  seller_id INT(11) NOT NULL,
  title VARCHAR(255) NOT NULL,
  slug VARCHAR(220) NOT NULL,
  description TEXT DEFAULT NULL,
  price DECIMAL(12,2) NOT NULL,
  compare_price DECIMAL(12,2) DEFAULT NULL,
  discount INT(11) DEFAULT 0,
  stock INT(11) NOT NULL,
  sku VARCHAR(80) DEFAULT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  popularity INT(11) NOT NULL DEFAULT 0,
  is_promo TINYINT(1) NOT NULL DEFAULT 0,
  main_image VARCHAR(255) DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO products (id, shop_id, seller_id, title, slug, description, price, compare_price, discount, stock, sku, is_active, popularity, is_promo, main_image, created_at, updated_at) VALUES
(7, 5, 9, 'crofle', 'crofle', 'crofle enak gurih renyah', '12000.00', NULL, 0, 9, NULL, 1, 0, 0, 'products/product_20251209_103719_ba3820.jpg', '2025-12-09 09:37:19', '2025-12-09 09:40:27'),
(8, 5, 9, 'Galon aqua', 'galon-aqua', 'galon premium dari pegungan asli', '10000.00', NULL, 0, 20, NULL, 1, 0, 0, 'products/product_20251209_153432_c8fc25.jpg', '2025-12-09 14:34:32', '2025-12-09 15:00:31'),
(9, 5, 9, 'mie instan', 'mie-instan', 'mie indomie goreng', '3000.00', NULL, 0, 100, NULL, 1, 0, 0, 'products/product_20251209_154730_c29c77.jpg', '2025-12-09 14:47:30', '2025-12-09 15:00:33'),
(10, 5, 9, 'kecap', 'kecap', 'kecap manis sedap', '50000.00', NULL, 0, 40, NULL, 1, 0, 0, 'products/product_20251209_154826_69237e.png', '2025-12-09 14:48:26', '2025-12-09 15:00:34'),
(11, 5, 9, 'telur', 'telur', 'telur ayam', '2000.00', NULL, 0, 50, NULL, 1, 0, 0, 'products/product_20251209_155919_d57fbd.jpg', '2025-12-09 14:59:19', '2025-12-09 15:00:35'),
(12, 5, 9, 'beras sphp', 'beras-sphp', 'beras sphp 5 kg', '65000.00', NULL, 0, 50, NULL, 1, 0, 0, 'products/product_20251209_160014_3cbfac.jpg', '2025-12-09 15:00:14', '2025-12-09 15:00:36'),
(13, 5, 9, 'swistsal', 'swistsal', 'switsal sabun', '25000.00', NULL, 0, 50, NULL, 0, 0, 0, 'products/product_20251209_161223_093029.jpg', '2025-12-09 15:12:23', NULL),
(14, 5, 9, 'rice cooker', 'rice-cooker', 'riceooker nasi', '150000.00', NULL, 0, 50, NULL, 0, 0, 0, 'products/product_20251209_161405_c88d70.jpg', '2025-12-09 15:14:05', NULL),
(15, 5, 9, 'sepatu running', 'sepatu-running', 'sepatu lari joging', '3000000.00', NULL, 0, 30, NULL, 0, 0, 0, 'products/product_20251209_161709_c30f59.jpg', '2025-12-09 15:17:09', NULL),
(16, 5, 9, 'tas sekolah lucu', 'tas-sekolah-lucu', 'tas sekolah perempuan', '250000.00', NULL, 0, 50, NULL, 0, 0, 0, 'products/product_20251209_161951_ff33e6.jpg', '2025-12-09 15:19:51', NULL),
(17, 5, 9, 'kemonceng', 'kemonceng', 'kemonceng aesthetic untuk bersihin debu', '15000.00', NULL, 0, 40, NULL, 0, 0, 0, 'products/product_20251209_162142_4f89bd.jpg', '2025-12-09 15:21:42', NULL);

CREATE TABLE product_categories (
  product_id BIGINT(20) UNSIGNED NOT NULL,
  category_id BIGINT(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO product_categories (product_id, category_id) VALUES
(1, 1),
(1, 11),
(2, 1),
(2, 10),
(3, 1),
(3, 10),
(4, 1),
(4, 11),
(7, 7),
(8, 7),
(9, 7),
(10, 7),
(11, 7),
(13, 4),
(14, 5),
(15, 8),
(16, 4),
(16, 12),
(17, 11);

CREATE TABLE product_images (
  id BIGINT(20) UNSIGNED NOT NULL,
  product_id BIGINT(20) UNSIGNED NOT NULL,
  variant_id BIGINT(20) UNSIGNED DEFAULT NULL,
  image_path VARCHAR(255) NOT NULL,
  sort_order INT(11) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE product_reviews (
  id INT(10) UNSIGNED NOT NULL,
  user_id INT(10) UNSIGNED NOT NULL,
  product_id INT(10) UNSIGNED NOT NULL,
  order_id INT(10) UNSIGNED NOT NULL,
  rating TINYINT(4) NOT NULL,
  comment TEXT DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE product_variants (
  id BIGINT(20) UNSIGNED NOT NULL,
  product_id BIGINT(20) UNSIGNED NOT NULL,
  name VARCHAR(120) NOT NULL,
  sku VARCHAR(80) DEFAULT NULL,
  price DECIMAL(12,2) DEFAULT NULL,
  compare_price DECIMAL(12,2) DEFAULT NULL,
  stock INT(11) NOT NULL DEFAULT 0,
  image VARCHAR(255) DEFAULT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE shops (
  id BIGINT(20) UNSIGNED NOT NULL,
  user_id BIGINT(20) UNSIGNED NOT NULL,
  name VARCHAR(150) NOT NULL,
  address VARCHAR(255) DEFAULT NULL,
  latitude DECIMAL(10,7) NOT NULL,
  longitude DECIMAL(10,7) NOT NULL,
  description TEXT DEFAULT NULL,
  logo VARCHAR(255) DEFAULT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  package_code VARCHAR(20) DEFAULT NULL,
  last_payment_proof VARCHAR(255) DEFAULT NULL,
  package_status ENUM('none','waiting_payment','active','expired') NOT NULL DEFAULT 'none',
  package_started_at DATETIME DEFAULT NULL,
  package_expires_at DATETIME DEFAULT NULL,
  product_limit INT(11) DEFAULT 0,
  subscription_status ENUM('none','waiting_payment','active','expired') NOT NULL DEFAULT 'none',
  has_qris TINYINT(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO shops (id, user_id, name, address, latitude, longitude, description, logo, is_active, created_at, updated_at, package_code, last_payment_proof, package_status, package_started_at, package_expires_at, product_limit, subscription_status, has_qris) VALUES
(1, 3, 'Toko Contoh NearBuy', 'Pangkalpinang', '-2.1291000', '106.1090000', 'Toko contoh kebutuhan harian di sekitar Pangkalpinang', NULL, 1, '2025-12-01 17:46:36', '2025-12-09 09:27:12', 'Paket Starter', '/NearBuy-Marketplace/public/uploads/payment_proofs/1_1765272106.jpg', 'waiting_payment', NULL, NULL, 15, 'active', 0),
(2, 4, 'Warung Rama', 'Pangkalpinang', '-2.1291000', '106.1090000', 'Menyediakan Pulsa, Token Listrik, Isi Ulang Paket Internet dan kebutuhan smartphone Anda', NULL, 1, '2025-12-06 07:14:34', '2025-12-08 12:19:36', 'Paket Starter', '/NearBuy-Marketplace/public/uploads/payment_proofs/2_1765191092.png', 'active', '2025-12-08 19:19:36', '2026-01-07 19:19:36', 15, 'active', 0),
(3, 6, 'Mirru\'s Store', 'Jl Brig Hasan Basri Kec Amuntai Tengan Kab. HSU', '-999.9999999', '-999.9999999', 'Jual atau Jasa Top Up Game', NULL, 1, '2025-12-06 19:11:33', '2025-12-08 13:01:06', 'Paket Premium', '/NearBuy-Marketplace/public/uploads/payment_proofs/3_1765193269.png', 'active', '2025-12-08 18:39:05', '2026-01-07 18:39:05', 9999, 'active', 0),
(4, 8, 'Warung Serba Ada', 'Jl Palembang', '-3.0472684', '104.7491455', NULL, NULL, 1, '2025-12-09 08:37:46', '2025-12-09 08:48:46', 'Paket Premium', '/NearBuy-Marketplace/public/uploads/payment_proofs/4_1765269548.png', 'waiting_payment', NULL, NULL, 99999, 'active', 0),
(5, 9, 'warung swalayan', 'jalan gang melati', '-2.4162757', '106.0839844', 'toko swalayan', NULL, 1, '2025-12-09 09:33:03', '2025-12-09 09:35:23', 'Paket Starter', '/NearBuy-Marketplace/public/uploads/payment_proofs/5_1765272856.jpg', 'waiting_payment', NULL, NULL, 15, 'active', 0);

CREATE TABLE site_settings (
  key VARCHAR(64) NOT NULL,
  value TEXT DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO site_settings (key, value) VALUES
('home_banner', '/NearBuy/public/assets/banner-nearbuy.jpg'),
('home_banner', '/NearBuy/public/assets/banner-nearbuy.jpg');

CREATE TABLE transactions (
  id INT(11) NOT NULL,
  buyer_id INT(11) NOT NULL,
  product_id INT(11) NOT NULL,
  jumlah INT(11) NOT NULL,
  total_harga INT(11) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE users (
  id INT(11) NOT NULL,
  full_name VARCHAR(120) NOT NULL,
  email VARCHAR(100) NOT NULL,
  phone VARCHAR(30) DEFAULT NULL,
  gender ENUM('male','female','other') DEFAULT NULL,
  birth_date DATE DEFAULT NULL,
  address VARCHAR(255) DEFAULT NULL,
  city VARCHAR(120) DEFAULT NULL,
  province VARCHAR(120) DEFAULT NULL,
  postal_code VARCHAR(20) DEFAULT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin','pengguna','seller') NOT NULL DEFAULT 'pengguna',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  points INT(11) NOT NULL DEFAULT 0,
  latitude DECIMAL(10,7) DEFAULT NULL,
  longitude DECIMAL(10,7) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO users (id, full_name, email, phone, gender, birth_date, address, city, province, postal_code, password_hash, role, is_active, created_at, updated_at, points, latitude, longitude) VALUES
(1, 'Administrator', 'admin@nearbuy.com', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'admin123', 'admin', 1, '2025-12-01 17:10:04', NULL, 0, NULL, NULL),
(5, 'Admin', 'Admin123@gmail.com', '08879874982', 'female', NULL, 'Jl Bukit Tani', 'Pangkalpinang', 'Bangka Belitung', '45677', '$2y$10$iKnrRirrT66NFteWXOpMrOON.Kr.iWNCEd030C1t/CPVhYxGMUm6S', 'admin', 1, '2025-12-06 17:00:57', NULL, 0, NULL, NULL),
(9, 'Rojan Jiyan Arifi', 'rojanjiyan16@gmail.com', '083847799206', 'male', '2025-12-09', 'jalan gang melati', 'koba', 'bangka belitung', '33681', '$2y$10$I3GxTGyXZfvnCioVFrtnXO1lKFnoCHqqIMNBRKRE5owYpokfDawQW', 'seller', 1, '2025-12-09 09:30:01', '2025-12-09 09:42:08', 0, '-2.0700405', '106.0773752');

CREATE TABLE user_preferences (
  user_id BIGINT(20) UNSIGNED NOT NULL,
  category_id BIGINT(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO user_preferences (user_id, category_id) VALUES
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

CREATE TABLE v_user_recommendations (
  user_id INT(11),
  product_id INT(11),
  slug VARCHAR(220),
  title VARCHAR(255),
  price DECIMAL(12,2),
  compare_price DECIMAL(12,2),
  main_image VARCHAR(255),
  stock INT(11),
  popularity INT(11),
  created_at TIMESTAMP,
  category_name VARCHAR(120)
);

DROP TABLE IF EXISTS v_user_recommendations;

CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW v_user_recommendations AS
SELECT u.id AS user_id,
       p.id AS product_id,
       p.slug AS slug,
       p.title AS title,
       p.price AS price,
       p.compare_price AS compare_price,
       p.main_image AS main_image,
       p.stock AS stock,
       p.popularity AS popularity,
       p.created_at AS created_at,
       c.name AS category_name
FROM users u
JOIN user_preferences up ON up.user_id = u.id
JOIN product_categories pc ON pc.category_id = up.category_id
JOIN products p ON p.id = pc.product_id
JOIN categories c ON c.id = up.category_id
WHERE p.is_active = 1111;

ALTER TABLE admin_notifications
  ADD PRIMARY KEY (id);

ALTER TABLE categories
  ADD PRIMARY KEY (id),
  ADD UNIQUE KEY uq_categories_slug (slug);

ALTER TABLE orders
  ADD PRIMARY KEY (id),
  ADD KEY fk_orders_user (user_id),
  ADD KEY fk_orders_cart (cart_id);

ALTER TABLE order_items
  ADD PRIMARY KEY (id),
  ADD KEY fk_oi_order (order_id),
  ADD KEY fk_oi_product (product_id);

ALTER TABLE products
  ADD PRIMARY KEY (id),
  ADD KEY seller_id (seller_id);

ALTER TABLE product_categories
  ADD PRIMARY KEY (product_id,category_id),
  ADD KEY fk_pc_category (category_id);

ALTER TABLE product_images
  ADD PRIMARY KEY (id),
  ADD KEY idx_pi_product (product_id),
  ADD KEY idx_pi_variant (variant_id);

ALTER TABLE product_reviews
  ADD PRIMARY KEY (id),
  ADD KEY idx_product (product_id),
  ADD KEY idx_user (user_id),
  ADD KEY idx_order (order_id);

ALTER TABLE shops
  ADD PRIMARY KEY (id);

ALTER TABLE transactions
  ADD PRIMARY KEY (id),
  ADD KEY buyer_id (buyer_id),
  ADD KEY product_id (product_id);

ALTER TABLE users
  ADD PRIMARY KEY (id),
  ADD UNIQUE KEY email (email);

ALTER TABLE admin_notifications
  MODIFY id INT(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE products
  MODIFY id INT(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

ALTER TABLE shops
  MODIFY id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

ALTER TABLE transactions
  MODIFY id INT(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE users
  MODIFY id INT(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

ALTER TABLE products
  ADD CONSTRAINT products_ibfk_1 FOREIGN KEY (seller_id) REFERENCES users (id) ON DELETE CASCADE;

ALTER TABLE transactions
  ADD CONSTRAINT transactions_ibfk_1 FOREIGN KEY (buyer_id) REFERENCES users (id) ON DELETE CASCADE,
  ADD CONSTRAINT transactions_ibfk_2 FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE;

COMMIT;
