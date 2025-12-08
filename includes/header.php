<?php
// ===============================================================
// NearBuy – Header (auto BASE + auto CSS toko + label toko dinamis)
// ===============================================================
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/*
 |---------------------------------------------------------------
 | BASE URL
 |---------------------------------------------------------------
 | - Kalau file pemanggil sudah set $BASE, kita pakai itu.
 | - Kalau belum, kita hitung dari SCRIPT_NAME dan cari segmen "/public".
 | - Fallback terakhir: '/NearBuy-Marketplace/public'
 */
if (empty($BASE)) {
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')); // contoh: /NearBuy-Marketplace/public/seller

    $posPublic = stripos($scriptDir, '/public');
    if ($posPublic !== false) {
        // ambil sampai "/public"
        $BASE = substr($scriptDir, 0, $posPublic + 7); // 7 = strlen("/public")
    } else {
        // fallback kalau struktur folder berubah
        $BASE = '/NearBuy-Marketplace/public';
    }
}

// untuk kenyamanan halaman2 seller / admin
$BASE_SELLER = $BASE . '/seller';
$BASE_ADMIN  = $BASE . '/admin';

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

// paksa guest jika perlu (misal di landing khusus)
if (!empty($FORCE_GUEST_HEADER)) {
    $role = 'guest';
}

// =======================================
// 🔥 Deteksi halaman toko.php
// =======================================
$currentFile = basename($_SERVER['SCRIPT_NAME'] ?? '');
$isTokoPage  = ($currentFile === 'toko.php');

// Jika halaman toko.php, tambahkan CSS toko secara otomatis
if ($isTokoPage) {
    if (!isset($EXTRA_CSS) || !is_array($EXTRA_CSS)) {
        $EXTRA_CSS = [];
    }
    $EXTRA_CSS[] = 'seller/style-toko.css';
}

// =======================================
// 🔍 Label menu Toko (Buka Toko / Toko Saya)
// =======================================
$shopMenuLabel = '🏪 Buka Toko';

// untuk user login non-admin, cek apakah sudah punya toko
if ($user && in_array($role, ['pengguna', 'seller'], true)) {
    require_once __DIR__ . '/db.php';

    if (isset($pdo) && $pdo instanceof PDO) {
        try {
            $stmtShop = $pdo->prepare("SELECT is_active FROM shops WHERE user_id = ? LIMIT 1");
            $stmtShop->execute([(int)$user['id']]);
            $rowShop = $stmtShop->fetch(PDO::FETCH_ASSOC);

            if ($rowShop) {
                $status = (int)$rowShop['is_active'];
                if ($status === 1) {
                    $shopMenuLabel = '🏪 Toko Saya';
                } elseif ($status === 0) {
                    $shopMenuLabel = '🏪 Toko (menunggu admin)';
                } elseif ($status === 2) {
                    $shopMenuLabel = '🏪 Toko (ditolak)';
                }
            }
        } catch (Throwable $e) {
            // kalau gagal query, biarkan label default saja
        }
    }
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
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

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
      <img src="<?= e($BASE) ?>/assets/logo_nearbuyheader.png" class="logo-img" alt="Logo NearBuy">
      <span class="logo-text" style="font-weight:500;">“Menghubungkan pelanggan dengan produk terdekat."</span>
    </div>

    <!-- Hamburger Mobile -->
    <button class="nav-toggle" aria-label="Toggle menu" aria-expanded="false" aria-controls="primary-nav">
      <i class="fa-solid fa-bars-staggered"></i>
    </button>

    <!-- NAV ROLE BASED -->
    <nav class="nav-links" id="primary-nav">

      <?php if ($role === 'guest'): ?>

        <a href="<?= e($BASE) ?>/index.php">
          <i class="fa-solid fa-house"></i> <span>Home</span>
        </a>

        <a href="<?= e($BASE) ?>/tentang.php">
          <i class="fa-solid fa-circle-info"></i> <span>Tentang</span>
        </a>

        <a href="<?= e($BASE) ?>/kontak.php">
          <i class="fa-solid fa-headset"></i> <span>Kontak</span>
        </a>

        <a href="<?= e($BASE) ?>/register.php">
          <i class="fa-solid fa-user-plus"></i> <span>Daftar</span>
        </a>

        <a href="<?= e($BASE) ?>/login.php" class="login-icon">
          <i class="fa-solid fa-right-to-bracket"></i> <span>Login</span>
        </a>

      <?php elseif ($role === 'pengguna' || $role === 'seller'): ?>

        <a href="<?= e($BASE) ?>/index.php">Home</a>
        <a href="<?= e($BASE) ?>/set_lokasi.php">📍 Set Lokasi</a>

        <!-- label toko dinamis, tapi tujuannya selalu ke /seller/toko.php -->
        <a href="<?= e($BASE_SELLER) ?>/toko.php">
          <?= e($shopMenuLabel) ?>
        </a>

        <a href="<?= e($BASE) ?>/keranjang.php">🛒 Keranjang</a>
        <a href="<?= e($BASE) ?>/profil.php">👤 Profil</a>
        <a href="<?= e($BASE) ?>/logout.php" class="logout">Logout</a>

      <?php elseif ($role === 'admin'): ?>

        <a href="<?= e($BASE_ADMIN) ?>/index.php">📊 Admin Dashboard</a>
        <a href="<?= e($BASE_ADMIN) ?>/pembeli.php">👥 Kelola Pengguna</a>
        <a href="<?= e($BASE_ADMIN) ?>/reporting_transaksi.php">📈 Laporan</a>
        <a href="<?= e($BASE) ?>/logout.php" class="logout">Logout</a>

      <?php endif; ?>

    </nav>

  </div>
</header>

<main class="content">
