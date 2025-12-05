<?php
// ===============================================================
// NearBuy – Header (auto BASE based nav + auto CSS toko)
// ===============================================================
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// BASE
if (empty($BASE)) {
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    $BASE = rtrim($scriptDir, '/');
    if ($BASE === '' || $BASE === '/') {
        $BASE = '/NearBuy-marketplace/public';
    }
}

// helper escape
if (!function_exists('e')) {
    function e(string $s): string {
        return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
    }
}

// ambil user dan role
$user = $_SESSION['user'] ?? null;
$role = 'guest';

if ($user && is_array($user)) {
    if (isset($user['role']) && in_array($user['role'], ['pengguna', 'admin', 'seller'], true)) {
        $role = $user['role'];
    }
}

// paksa guest jika perlu
if (!empty($FORCE_GUEST_HEADER)) {
    $role = 'guest';
}

// =======================================
// 🔥 Deteksi halaman toko.php
// =======================================
$currentFile = basename($_SERVER['SCRIPT_NAME'] ?? '');
$isTokoPage = ($currentFile === 'toko.php');

// Jika halaman toko.php, tambahkan CSS toko secara otomatis
if ($isTokoPage) {
    $EXTRA_CSS[] = "seller/style-toko.css";
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>NearBuy Marketplace</title>

  <!-- CSS global -->
  <link rel="stylesheet" href="<?= e($BASE) ?>/style.css">

  <!-- CSS tambahan (produk.php, toko.php, seller, dll) -->
  <?php
  if (!empty($EXTRA_CSS) && is_array($EXTRA_CSS)) {
      foreach ($EXTRA_CSS as $css) {
          echo '<link rel="stylesheet" href="' . e($BASE) . '/' . ltrim($css, '/') . '">' . "\n";
      }
  }
  ?>

  <script src="<?= e($BASE) ?>/assets/nav.js" defer></script>
</head>

<body>

<header class="main-header" style="background:#1e2a47;">
  <div class="header-container">

    <!-- Logo -->
    <div class="logo">
      <img src="<?= e($BASE) ?>/assets/logo-sellexa.png" class="logo-img" alt="Logo NearBuy">
      <span class="logo-text" style="font-weight:700;">NearBuy</span>
    </div>

    <!-- Hamburger Mobile -->
    <button class="nav-toggle" aria-label="Toggle menu" aria-expanded="false" aria-controls="primary-nav">
      <span class="bar"></span><span class="bar"></span><span class="bar"></span>
    </button>

    <!-- NAV ROLE BASED -->
    <nav class="nav-links" id="primary-nav">

      <?php if ($role === 'guest'): ?>

        <a href="<?= e($BASE) ?>/index.php">Home</a>
        <a href="<?= e($BASE) ?>/login.php">Daftar / Login</a>
        <a href="<?= e($BASE) ?>/tentang.php">Tentang Kami</a>
        <a href="<?= e($BASE) ?>/kontak.php">Kontak</a>

      <?php elseif ($role === 'pengguna'): ?>

        <a href="<?= e($BASE) ?>/index.php">Home</a>
        <a href="<?= e($BASE) ?>/set_lokasi.php">📍 Set Lokasi</a>
        <a href="<?= e($BASE) ?>/seller/toko.php">🏪 Buka Toko</a>
        <a href="<?= e($BASE) ?>/keranjang.php">🛒 Keranjang</a>
        <a href="<?= e($BASE) ?>/profil.php">👤 Profil</a>
        <a href="<?= e($BASE) ?>/logout.php" class="logout">Logout</a>

      <?php elseif ($role === 'seller'): ?>

        <a href="<?= e($BASE) ?>/seller/index.php">📊 Dashboard Toko</a>
        <a href="<?= e($BASE) ?>/seller/produk.php">📦 Kelola Produk</a>
        <a href="<?= e($BASE) ?>/seller/pesanan.php">🧾 Kelola Pesanan</a>
        <a href="<?= e($BASE) ?>/seller/promo.php">🏷 Kelola Promo</a>
        <a href="<?= e($BASE) ?>/seller/lokasi.php">📍 Lokasi Toko</a>
        <a href="<?= e($BASE) ?>/logout.php" class="logout">Logout</a>

      <?php elseif ($role === 'admin'): ?>

        <a href="<?= e($BASE) ?>/admin/index.php">📊 Admin Dashboard</a>
        <a href="<?= e($BASE) ?>/admin/pembeli.php">👥 Kelola Pengguna</a>
        <a href="<?= e($BASE) ?>/admin/reporting_transaksi.php">📈 Laporan</a>
        <a href="<?= e($BASE) ?>/logout.php" class="logout">Logout</a>

      <?php endif; ?>

    </nav>

  </div>
</header>

<main class="content">
