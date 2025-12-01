-- PASTIKAN SEDANG MEMILIH DATABASE `nearbuy`
USE `nearbuy`;

-- 1. Tambah nilai 'seller' pada kolom role di tabel users
ALTER TABLE `users`
  MODIFY `role` enum('admin','pengguna','seller') NOT NULL DEFAULT 'pengguna';

-- 2. Buat tabel shops untuk menyimpan lokasi penjual
CREATE TABLE `shops` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(150) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `latitude` decimal(10,7) NOT NULL,
  `longitude` decimal(10,7) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_shops_user` (`user_id`),
  CONSTRAINT `fk_shops_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 3. Tambah kolom shop_id di tabel products dan buat relasi ke shops
ALTER TABLE `products`
  ADD COLUMN `shop_id` bigint(20) UNSIGNED DEFAULT NULL AFTER `id`,
  ADD KEY `fk_products_shop` (`shop_id`);

ALTER TABLE `products`
  ADD CONSTRAINT `fk_products_shop`
    FOREIGN KEY (`shop_id`) REFERENCES `shops`(`id`) ON DELETE SET NULL;

-- 4. Jadikan salah satu user sebagai seller
--   Di dump kamu, id 1 admin, id 2 Miru, id 3 Mirza
--   Misal kita jadikan Mirza sebagai seller
UPDATE `users`
SET `role` = 'seller'
WHERE `id` = 3;

-- 5. Tambah satu contoh toko untuk seller tadi
INSERT INTO `shops` (
  `user_id`,
  `name`,
  `address`,
  `latitude`,
  `longitude`,
  `description`,
  `is_active`
) VALUES (
  3,
  'Toko Contoh NearBuy',
  'Pangkalpinang',
  -2.1291000,
  106.1090000,
  'Toko contoh kebutuhan harian di sekitar Pangkalpinang',
  1
);

-- Ambil id shop yang baru dibuat
-- Di banyak kasus ini akan bernilai 1
-- Kalau ragu bisa cek di phpMyAdmin, tabel shops
-- Asumsikan di sini id nya 1, lalu kita hubungkan semua produk ke toko ini
UPDATE `products`
SET `shop_id` = 1
WHERE `id` IN (1,2);

-- 6. Opsional, ubah banner supaya tidak lagi menunjuk ke folder Marketplace_SellExa
UPDATE `site_settings`
SET `value` = '/NearBuy/public/assets/banner-nearbuy.jpg'
WHERE `key` = 'home_banner';
