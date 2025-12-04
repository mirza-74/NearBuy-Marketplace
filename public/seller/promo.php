<?php
// ===============================================================
// SellExa – Kelola Promo (hub untuk voucher & banner)
// ===============================================================
declare(strict_types=1);

$BASE = '/Marketplace_SellExa/public';

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/settings.php';

// pastikan hanya admin yang boleh akses
$user = $_SESSION['user'] ?? null;
if (!$user || ($user['role'] ?? '') !== 'admin') {
    header('Location: ' . $BASE . '/login.php');
    exit;
}

// pakai css dashboard admin biar tampilan sama
$EXTRA_CSS = ['admin/style-admin-dashboard.css'];

require_once __DIR__ . '/../../includes/header.php';
?>
<div class="admin-shell">

  <header class="admin-page-header">
    <div>
      <h1 class="admin-page-title">Kelola Promo</h1>
      <p class="admin-page-subtitle">
        Atur voucher, banner, dan promosi lain di SellExa.
      </p>
    </div>
  </header>

  <section class="admin-grid">
    <!-- Kartu ke Kelola Voucher -->
    <div class="admin-card">
      <div class="admin-card-label">Voucher & Poin</div>
      <div class="admin-card-desc">
        Buat, aktif/nonaktifkan, dan kelola kode voucher yang bisa ditukar dengan poin oleh pengguna.
      </div>
      <a href="<?= e($BASE) ?>/admin/voucher.php" class="admin-quick-link" style="margin-top:12px;display:inline-block;">
        Buka Kelola Voucher
      </a>
    </div>

    <!-- Kartu ke Kelola Banner -->
    <div class="admin-card">
      <div class="admin-card-label">Banner & Iklan</div>
      <div class="admin-card-desc">
        Atur banner utama dan banner iklan yang tampil di halaman beranda pengguna.
      </div>
      <a href="<?= e($BASE) ?>/admin/banners.php" class="admin-quick-link" style="margin-top:12px;display:inline-block;">
        Buka Kelola Banner
      </a>
    </div>

    <!-- Slot pengembangan berikutnya -->
    <div class="admin-card">
      <div class="admin-card-label">Program Promo Khusus</div>
      <div class="admin-card-desc">
        (Opsional) Nanti bisa diisi flash sale, bundling produk, atau campaign musiman sesuai tugas dosen.
      </div>
    </div>
  </section>

</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
</main>
</body>
</html>
