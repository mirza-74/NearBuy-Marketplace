<?php
// ===============================================================
// NearBuy – Halaman Toko Saya (Dashboard Seller di sisi user)
// ===============================================================
declare(strict_types=1);

// BASE: otomatis, buang "/seller" di ujung
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$BASE = preg_replace('~/seller$~', '', rtrim($scriptDir, '/'));

// BASE khusus folder seller
$BASE_SELLER = $BASE . '/seller';

// includes
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/db.php';

// helper escape
if (!function_exists('e')) {
    function e($s): string {
        return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
    }
}

// wajib login dulu (role apapun)
$user = $_SESSION['user'] ?? null;
if (!$user || empty($user['id'])) {
    header('Location: ' . $BASE . '/login.php');
    exit;
}

$userId = (int)$user['id'];

// ===============================================================
// Ambil toko milik user (1 user 1 toko)
// ===============================================================
$shop    = null;
$hasShop = false;

try {
    $stmt = $pdo->prepare("SELECT * FROM shops WHERE user_id = ? LIMIT 1");
    $stmt->execute([$userId]);
    $shop    = $stmt->fetch(PDO::FETCH_ASSOC);
    $hasShop = $shop ? true : false;
} catch (Throwable $e) {
    $shop    = null;
    $hasShop = false;
}

// CSS khusus toko/dashboard seller (opsional, header juga auto-add kalau file ini toko.php)
if (!isset($EXTRA_CSS) || !is_array($EXTRA_CSS)) {
    $EXTRA_CSS = [];
}
$EXTRA_CSS[] = 'seller/style-toko.css';

// muat header umum (navbar dll)
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="nb-shell">

  <?php if (!empty($_SESSION['flash'])): ?>
    <div class="nb-flash">
      <?php
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);

        if (is_array($flash)) {
            echo e($flash['message'] ?? 'Terjadi kesalahan');
        } else {
            echo e($flash);
        }
      ?>
    </div>
  <?php endif; ?>

  <?php if ($hasShop): ?>
    <?php
      // status toko (dari admin)
      $isActive = (int)($shop['is_active'] ?? 0);
      if ($isActive === 1) {
          $statusText  = 'Aktif';
          $statusClass = 'on';
      } elseif ($isActive === 2) {
          $statusText  = 'Ditolak';
          $statusClass = 'inactive';
      } else {
          $statusText  = 'Menunggu persetujuan admin';
          $statusClass = 'inactive';
      }

      // status paket / langganan
      $pkgCode   = $shop['package_code']   ?? null;
      $pkgStatus = $shop['package_status'] ?? null;
      $limitProd = (int)($shop['product_limit'] ?? 0);

      $pkgText  = 'Belum memilih paket';
      $pkgClass = 'pkg-none';

      if (!empty($pkgCode)) {
          if ($pkgStatus === 'active') {
              $pkgText  = 'Paket ' . $pkgCode . ' (Aktif, limit ' . $limitProd . ' produk)';
              $pkgClass = 'pkg-active';
          } elseif ($pkgStatus === 'waiting_payment') {
              $pkgText  = 'Paket ' . $pkgCode . ' – menunggu pembayaran';
              $pkgClass = 'pkg-wait';
          } elseif ($pkgStatus === 'expired') {
              $pkgText  = 'Paket ' . $pkgCode . ' – kadaluarsa';
              $pkgClass = 'pkg-expired';
          }
      }

      // boleh kelola produk hanya jika toko aktif + paket aktif
      $canManageProducts = ($isActive === 1 && $pkgStatus === 'active');
      $pkgExpiresAt      = $shop['package_expires_at'] ?? null;
    ?>

    <!-- ================================================== -->
    <!-- USER SUDAH PUNYA TOKO  -> DASHBOARD TOKO           -->
    <!-- ================================================== -->

    <!-- Kartu utama kiri -->
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
        <div class="nb-label">Status Toko</div>
        <div class="nb-value">
          <span class="nb-pill <?= e($statusClass) ?>"><?= e($statusText) ?></span>
        </div>
      </div>

      <div class="nb-field">
        <div class="nb-label">Paket Toko</div>
        <div class="nb-value">
          <span class="nb-pill <?= e($pkgClass) ?>"><?= e($pkgText) ?></span>
        </div>
      </div>

      <?php if ($pkgStatus === 'active' && !empty($pkgExpiresAt)): ?>
        <div class="nb-field">
          <div class="nb-label">Masa Aktif Paket</div>
          <div class="nb-value">
            Berlaku sampai: <?= e($pkgExpiresAt) ?>
          </div>
        </div>
      <?php endif; ?>

      <div class="nb-field">
        <div class="nb-label">Alamat Toko</div>
        <div class="nb-value">
          <?= e($shop['address'] ?: 'Alamat toko belum diisi') ?>
        </div>
      </div>

      <div class="nb-field">
        <div class="nb-label">Koordinat Lokasi</div>
        <div class="nb-value">
          Lat: <?= e((string)($shop['latitude']  ?? '')) ?> ·
          Lng: <?= e((string)($shop['longitude'] ?? '')) ?>
        </div>
      </div>

      <div class="nb-actions nb-actions-top">
        <?php if ($isActive !== 1): ?>

          <button class="nb-btn" type="button" disabled title="Toko belum aktif">
            Kelola Produk (menunggu persetujuan admin)
          </button>
          <p class="nb-sub nb-sub-small" style="margin-top:8px;">
            Toko kamu masih menunggu persetujuan admin. Setelah aktif, kamu bisa memilih paket dan menambah produk.
          </p>

        <?php elseif ($pkgStatus !== 'active'): ?>

          <!-- Toko aktif tapi paket belum aktif -->
          <a class="nb-btn nb-btn-primary" href="<?= e($BASE_SELLER) ?>/paket.php">
            Pilih / Bayar Paket
          </a>

          <a class="nb-btn nb-btn-secondary" href="<?= e($BASE) ?>/set_lokasi.php">
            Atur Lokasi Toko
          </a>

          <p class="nb-sub nb-sub-small" style="margin-top:8px;">
            Kamu belum bisa menambah produk sampai paket aktif.
          </p>

        <?php else: ?>

          <!-- Toko aktif & paket aktif: full akses -->
          <a class="nb-btn nb-btn-primary" href="<?= e($BASE_SELLER) ?>/produk.php">
            Kelola Produk
          </a>
          <a class="nb-btn nb-btn-secondary" href="<?= e($BASE_SELLER) ?>/lokasi.php">
            Atur Lokasi Toko
          </a>

        <?php endif; ?>
      </div>
    </section>

    <!-- Kartu samping kanan – preview produk -->
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
          Belum ada produk.<br>
          <?php if ($canManageProducts): ?>
            Tambahkan produk di menu <b>Kelola Produk</b>.
          <?php else: ?>
            Kamu perlu menunggu toko aktif dan paket aktif sebelum bisa menambah produk.
          <?php endif; ?>
        </p>
      <?php endif; ?>
    </aside>

  <?php else: ?>
    <!-- ================================================== -->
    <!-- USER BELUM PUNYA TOKO -->
    <!-- ================================================== -->

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
        <a class="nb-btn nb-btn-secondary" href="<?= e($BASE_SELLER) ?>/peraturan_toko.php">
          Baca Peraturan Buka Toko
        </a>
        <a class="nb-btn nb-btn-primary" href="<?= e($BASE_SELLER) ?>/register_toko.php">
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
</main>
</body>
</html>
