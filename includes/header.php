<?php
// ===============================================================
// SellExa – Header (Admin & User Role Based Navigation)
// ===============================================================
declare(strict_types=1);

// pastikan session aktif
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ambil user dari session (kalau ada)
$user = $_SESSION['user'] ?? null;

// default role = guest
$role = 'guest';

// hanya gunakan role jika benar-benar valid
if ($user && is_array($user)) {
    if (isset($user['role']) && in_array($user['role'], ['pengguna','admin'], true)) {
        $role = $user['role'];
    }
}

// OPTIONAL: beberapa halaman boleh paksa tampil sebagai guest
// misal di index.php: $FORCE_GUEST_HEADER = true;
if (isset($FORCE_GUEST_HEADER) && $FORCE_GUEST_HEADER === true) {
    $role = 'guest';
}

// BASE DETECTION
if (!isset($BASE) || !$BASE) {
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    if (preg_match('~^(.*/public)~', $scriptDir, $m)) {
        $BASE = $m[1];
    } else {
        $BASE = '/Marketplace_SellExa/public';
    }
}
$BASE = '/' . ltrim($BASE, '/');
$BASE = rtrim($BASE, '/');

// Escape helper
if (!function_exists('e')) {
    function e(string $s): string {
        return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
    }
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SellExa Marketplace</title>

  <link rel="stylesheet" href="<?= e($BASE) ?>/style.css">

  <?php
  if (!empty($EXTRA_CSS) && is_array($EXTRA_CSS)) {
      foreach ($EXTRA_CSS as $css) {
          echo '<link rel="stylesheet" href="'.e($BASE).'/'.ltrim($css, '/').'">'."\n";
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
      <img src="<?= e($BASE) ?>/assets/logo-sellexa.png" class="logo-img" alt="Logo SellExa">
      <span class="logo-text" style="font-weight:700;">SellExa</span>
    </div>

    <!-- Hamburger Mobile -->
    <button class="nav-toggle" aria-label="Toggle menu" aria-expanded="false" aria-controls="primary-nav">
      <span class="bar"></span><span class="bar"></span><span class="bar"></span>
    </button>

    <!-- NAV ROLE-BASED -->
    <nav class="nav-links" id="primary-nav">

      <?php if ($role === 'guest'): ?>

          <a href="<?= e($BASE) ?>/index.php">Home</a>
          <a href="<?= e($BASE) ?>/login.php">Daftar/Login</a>
          <a href="<?= e($BASE) ?>/tentang.php">Tentang Kami</a>
          <a href="<?= e($BASE) ?>/kontak.php">Kontak</a>

      <?php elseif ($role === 'pengguna'): ?>

          <a href="<?= e($BASE) ?>/index.php">Home</a>
          <a href="<?= e($BASE) ?>/wishlist.php">❤️ Wishlist</a>
          <a href="<?= e($BASE) ?>/keranjang.php">🛒 Keranjang</a>
          <a href="<?= e($BASE) ?>/profil.php">👤 Profil</a>
          <a href="<?= e($BASE) ?>/logout.php" class="logout">Logout</a>

      <?php elseif ($role === 'admin'): ?>

          <!-- ADMIN HEADER -->
          <a href="<?= e($BASE) ?>/admin/index.php">📊 Dashboard</a>
          <a href="<?= e($BASE) ?>/admin/produk.php">📦 Kelola Produk</a>
          <a href="<?= e($BASE) ?>/admin/pembeli.php">👥 Kelola Pembeli</a>
          <a href="<?= e($BASE) ?>/admin/pesanan.php">🧾 Kelola Pesanan</a>
          <a href="<?= e($BASE) ?>/admin/promo.php">🏷 Kelola Promo</a>
          <a href="<?= e($BASE) ?>/logout.php" class="logout">Logout</a>

      <?php endif; ?>

    </nav>

  </div>
</header>

<main class="content">
