<?php
// ===============================================================
// NearBuy – Halaman Toko Saya (Dashboard Seller)
// ===============================================================
declare(strict_types=1);

// BASE: otomatis, buang "/seller" di ujung
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$BASE = preg_replace('~/seller$~', '', rtrim($scriptDir, '/'));

// includes
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/db.php';

// helper escape
if (!function_exists('e')) {
  function e(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
  }
}

// wajib login dulu
$user = $_SESSION['user'] ?? null;
if (!$user || empty($user['id'])) {
  header('Location: ' . $BASE . '/login.php');
  exit;
}

$userId = (int)$user['id'];

// ambil toko milik user (1 user 1 toko)
$shop  = null;
$hasShop = false;

try {
  $stmt = $pdo->prepare("
    SELECT 
     id, 
     name, 
     address, 
     latitude, 
     longitude, 
     is_active, 
     subscription_status, 
     product_limit, 
     created_at
    FROM shops
    WHERE user_id = ?
    LIMIT 1
  ");
  $stmt->execute([$userId]);
  $shop  = $stmt->fetch(PDO::FETCH_ASSOC);
  $hasShop = $shop ? true : false;
} catch (Throwable $e) {
  $shop  = null;
  $hasShop = false;
}

// muat header umum
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="nb-shell">

 <?php if ($hasShop): ?>
  <?php 
  $isActiveShop = !empty($shop['is_active']); 
  $isSubscribed = ($shop['subscription_status'] ?? 'free') === 'active'; 
  $isPendingPayment = ($shop['subscription_status'] ?? '') === 'pending_payment'; 
  ?>

          <section class="nb-card nb-main-card">
   <h1 class="nb-title">Toko Saya</h1>
   <p class="nb-sub">
    Kelola toko NearBuy milik kamu dari satu halaman ini. Pastikan alamat dan lokasi sudah benar
    agar pembeli di sekitar kamu mudah menemukan toko.
   </p>

   <div class="nb-field">
    <div class="nb-label">Nama Toko</div>
    <div class="nb-value"><?= e($shop['name'] ?? 'Toko Tanpa Nama') ?></div>
   </div>

   <div class="nb-field">
    <div class="nb-label">ID Toko</div>
    <div class="nb-value nb-pill">
     NB-<?= e((string)$shop['id']) ?>
    </div>
   </div>

   <div class="nb-field">
    <div class="nb-label">Status Toko (Admin)</div>
    <div class="nb-value">
     <?php if ($isActiveShop): ?>
      <span class="nb-pill">Aktif</span>
     <?php else: ?>
      <span class="nb-pill inactive">Menunggu persetujuan admin</span>
     <?php endif; ?>
    </div>
   </div>

         <div class="nb-field">
    <div class="nb-label">Status Langganan</div>
    <div class="nb-value">
      <?php if ($isSubscribed): ?>
        <span class="nb-pill active">Aktif (Batas: <?= (int)$shop['product_limit'] ?> produk)</span>
      <?php elseif ($isPendingPayment): ?>
        <span class="nb-pill pending">Menunggu Verifikasi Pembayaran</span>
      <?php else: ?>
        <span class="nb-pill expired">Belum Berlangganan</span>
      <?php endif; ?>
    </div>
   </div>
         <div class="nb-field">
    <div class="nb-label">Alamat Toko</div>
    <div class="nb-value">
     <?= e($shop['address'] ?: 'Alamat toko belum diisi') ?>
    </div>
   </div>

   <div class="nb-field">
    <div class="nb-label">Koordinat Lokasi</div>
    <div class="nb-value">
     Lat: <?= e((string)$shop['latitude']) ?> · Lng: <?= e((string)$shop['longitude']) ?>
    </div>
   </div>

   <div class="nb-actions nb-actions-top">
    <?php if ($isActiveShop && $isSubscribed): ?>
          <a class="nb-btn nb-btn-primary" href="<?= e($BASE) ?>/seller/produk.php">
      Kelola Produk
     </a>
    <?php elseif (!$isActiveShop): ?>
          <button class="nb-btn" type="button" disabled title="Toko belum aktif">
      Kelola Produk (menunggu persetujuan)
     </button>
    <?php else: ?>
          <a class="nb-btn nb-btn-primary" href="<?= e($BASE) ?>/seller/pilih_paket.php">
      Pilih Paket Langganan
     </a>
    <?php endif; ?>

    <a class="nb-btn nb-btn-secondary" href="<?= e($BASE) ?>/seller/edit_toko.php">
     Atur Lokasi Toko
    </a>
   </div>
  </section>
        <aside class="nb-card nb-side-card">
   <h2 class="nb-side-title">Preview Produk</h2>
   <p class="nb-sub nb-sub-small">
    Contoh tampilan produk kamu di halaman pembeli NearBuy.
   </p>

   <?php
   $preview = [];
   try {
     $stmtP = $pdo->prepare("
       SELECT id, title, price, compare_price, main_image
       FROM products
       WHERE shop_id = ?
       ORDER BY created_at DESC
       LIMIT 6
     ");
     $stmtP->execute([$shop['id']]);
     $preview = $stmtP->fetchAll(PDO::FETCH_ASSOC);
   } catch (Throwable $e) {
     $preview = [];
   }
   ?>

   <?php if ($preview): ?>
    <div class="nb-products-grid">
     <?php foreach ($preview as $p): ?>
      <?php
       if (!empty($p['main_image'])) {
         if (preg_match('~^https?://~i', $p['main_image'])) {
           $img = $p['main_image'];
         } else {
           $img = $BASE . '/uploads/' . ltrim($p['main_image'], '/');
         }
       } else {
         $img = 'https://via.placeholder.com/160x120?text=No+Image';
       }

       $hasPromo = !is_null($p['compare_price']) && (float)$p['compare_price'] > (float)$p['price'];
      ?>
      <div class="nb-prod-card">
       <div class="nb-prod-img-wrap">
        <img src="<?= e($img) ?>" alt="<?= e($p['title']) ?>">
       </div>
       <div class="nb-prod-body">
        <div class="nb-prod-title"><?= e($p['title']) ?></div>
        <?php if ($hasPromo): ?>
         <div class="nb-prod-compare">
          Rp<?= number_format((float)$p['compare_price'], 0, ',', '.') ?>
         </div>
        <?php endif; ?>
        <div class="nb-prod-price">
         Rp<?= number_format((float)$p['price'], 0, ',', '.') ?>
        </div>
       </div>
      </div>
     <?php endforeach; ?>
    </div>
   <?php else: ?>
    <p class="nb-empty">
     Belum ada produk. Tambahkan produk di menu <b>Kelola Produk</b>.
    </p>
   <?php endif; ?>
  </aside>

 <?php else: ?>
          <section class="nb-card nb-main-card">
   <h1 class="nb-title">Buka Toko di NearBuy</h1>
   <p class="nb-sub">
    Kamu belum memiliki toko. Daftarkan toko sekarang dan jangkau pembeli terdekat di sekitar domisili kamu.
   </p>

   <p class="nb-sub nb-sub-small">
    Dengan toko NearBuy, produk kamu akan muncul di rekomendasi pembeli yang lokasinya paling dekat dengan toko kamu.
   </p>

   <div class="nb-field">
    <div class="nb-label">Langkah buka toko:</div>
    <ol class="nb-step-list">
     <li>Baca peraturan dan panduan buka toko.</li>
     <li>Isi data toko, alamat, dan lokasi di peta.</li>
     <li>Tunggu persetujuan admin sebelum toko aktif.</li>
    </ol>
   </div>

   <div class="nb-actions nb-actions-top">
    <a class="nb-btn nb-btn-secondary" href="<?= e($BASE) ?>/seller/peraturan_toko.php">
     Baca Peraturan Buka Toko
    </a>
    <a class="nb-btn nb-btn-primary" href="<?= e($BASE) ?>/seller/register_toko.php">
     Buka Toko Sekarang
    </a>
   </div>

   <p class="nb-sub nb-sub-small" style="margin-top: 10px;">
    ID Toko berfungsi seperti email toko kamu dan akan dipakai saat login ke dashboard seller.
   </p>
  </section>

    <aside class="nb-card nb-side-card">
   <h2 class="nb-side-title">Kenapa buka toko di NearBuy?</h2>
   <p class="nb-sub nb-sub-small">
    • Jangkau pelanggan di sekitar lokasi toko kamu secara otomatis.<br>
    • Pengelolaan produk dan pesanan dari satu dashboard seller.<br>
    • Cocok untuk warung, minimarket, dan usaha rumahan.
   </p>

   <div class="nb-actions">
    <a class="nb-btn" href="<?= e($BASE) ?>/profil.php">
     Lengkapi Profil Pembeli
    </a>
   </div>
  </aside>

 <?php endif; ?>

</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>