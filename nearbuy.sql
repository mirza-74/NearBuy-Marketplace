-- ============================================================
-- DATABASE: nearbuy
-- ============================================================

CREATE DATABASE IF NOT EXISTS `nearbuy`;
USE `nearbuy`;

-- ============================================================
-- 1. Tabel Users
-- ============================================================

CREATE TABLE IF NOT EXISTS `users` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `nama` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('admin','pengguna','seller') NOT NULL DEFAULT 'pengguna',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
);

-- ============================================================
-- 2. Tabel Products (opsional)
-- ============================================================

CREATE TABLE IF NOT EXISTS `products` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `seller_id` INT NOT NULL,
  `nama_produk` VARCHAR(255) NOT NULL,
  `deskripsi` TEXT,
  `harga` INT NOT NULL,
  `stok` INT NOT NULL,
  `gambar` VARCHAR(255),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`seller_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

-- ============================================================
-- 3. Tabel Transactions (opsional)
-- ============================================================

CREATE TABLE IF NOT EXISTS `transactions` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `buyer_id` INT NOT NULL,
  `product_id` INT NOT NULL,
  `jumlah` INT NOT NULL,
  `total_harga` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`buyer_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
);

-- ============================================================
-- 4. Tambahkan user admin default (opsional)
-- ============================================================

INSERT INTO `users` (`nama`, `email`, `password`, `role`)
VALUES ('Administrator', 'admin@nearbuy.com', 'admin123', 'admin');
