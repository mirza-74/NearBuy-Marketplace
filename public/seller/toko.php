<?php
// ===============================================================
// NearBuy – Halaman Toko Saya
// Jika sudah punya toko tampilkan ringkasan toko
// Jika belum punya toko tampilkan ajakan registrasi
// ===============================================================
declare(strict_types=1);

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/db.php';

// deteksi BASE otomatis dari folder public
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$BASE = rtrim($scriptDir, '/');
if ($BASE === '' || $BASE === '/') {
    $BASE = '/NearBuy-marketplace/public';
}

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

// ambil toko milik user
$shop = null;
$hasShop = false;

try {
    $stmt = $pdo->prepare("
        SELECT id, shop_id, name, address, latitude, longitude, is_active, created_at
        FROM shops
        WHERE user_id = ?
        LIMIT 1
    ");
    $stmt->execute([$userId]);
    $shop = $stmt->fetch(PDO::FETCH_ASSOC);
    $hasShop = $shop ? true : false;
} catch (Throwable $e) {
    $shop = null;
    $hasShop = false;
}

// kasih tahu header kalau halaman ini pakai CSS khusus toko
$EXTRA_CSS = ['seller/style-toko.css'];

// muat header umum
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="toko-shell">

  <?php if ($hasShop): ?>
    <!-- Jika sudah punya toko -->
    <section class="toko-card toko-card-main">
      <div class="toko-headline">
        <h1>Toko Saya</h1>
        <p>Kelola toko NearBuy milik kamu dari satu halaman ini.</p>
      </div>

      <div class="toko-info-grid">
        <div class="toko-info-block">
          <div class="label">Nama Toko</div>
          <div class="value"><?= e($shop['name'] ?? 'Toko Tanpa Nama') ?></div>
        </div>

        <div class="toko-info-block">
          <div class="label">ID Toko</div>
          <div class="value mono">
            <?= e($shop['shop_id'] ?? ('NB-' . (int)$shop['id'])) ?>
          </div>
        </div>

        <div class="toko-info-block">
          <div class="label">Status</div>
          <div class="value">
            <?php if (!empty($shop['is_active'])): ?>
              <span class="badge badge-on">Aktif</span>
            <?php else: ?>
              <span class="badge badge-off">Menunggu persetujuan admin</span>
            <?php endif; ?>
          </div>
        </div>

        <div class="toko-info-block full">
          <div class="label">Alamat Toko</div>
          <div class="value">
            <?= e($shop['address'] ?: 'Alamat belum diisi') ?>
          </div>
        </div>

        <div class="toko-info-block">
          <div class="label">Latitude</div>
          <div class="value mono">
            <?= $shop['latitude'] !== null ? e((string)$shop['latitude']) : 'Belum diatur' ?>
          </div>
        </div>

        <div class="toko-info-block">
          <div class="label">Longitude</div>
          <div class="value mono">
            <?= $shop['longitude'] !== null ? e((string)$shop['longitude']) : 'Belum diatur' ?>
          </div>
        </div>
      </div>

      <div class="toko-actions">
        <a class="btn primary" href="<?= e($BASE) ?>/seller/index.php">
          Masuk Dashboard Toko
        </a>
        <a class="btn" href="<?= e($BASE) ?>/seller/produk.php">
          Kelola Produk
        </a>
        <a class="btn" href="<?= e($BASE) ?>/seller/lokasi.php">
          Atur Lokasi Toko
        </a>
      </div>
    </section>

    <!-- Preview produk toko di bawah -->
    <section class="toko-card">
      <h2>Preview Produk di NearBuy</h2>
      <p class="toko-subtext">
        Ini contoh tampilan produk kamu di halaman pembeli.
      </p>

      <?php
      // ambil beberapa produk milik toko ini untuk preview
      $preview = [];
      try {
          $stmtP = $pdo->prepare("
              SELECT id, title, price, compare_price, main_image, slug
              FROM products
              WHERE shop_id = ?
              ORDER BY created_at DESC
              LIMIT 8
          ");
          $stmtP->execute([$shop['id']]);
          $preview = $stmtP->fetchAll(PDO::FETCH_ASSOC);
      } catch (Throwable $e) {
          $preview = [];
      }
      ?>

      <?php if ($preview): ?>
        <div class="toko-product-grid">
          <?php foreach ($preview as $p): ?>
            <?php
              $img = $p['main_image']
                     ? $BASE . '/uploads/' . ltrim($p['main_image'], '/')
                     : 'https://via.placeholder.com/120?text=No+Img';
              $hasPromo = !is_null($p['compare_price']) && (float)$p['compare_price'] > (float)$p['price'];
            ?>
            <div class="toko-product-card">
              <img src="<?= e($img) ?>" alt="<?= e($p['title']) ?>" class="toko-product-img">
              <div class="toko-product-title"><?= e($p['title']) ?></div>
              <div class="toko-product-price">
                <?php if ($hasPromo): ?>
                  <span class="old">Rp<?= number_format((float)$p['compare_price'], 0, ',', '.') ?></span>
                <?php endif; ?>
                <span class="new">Rp<?= number_format((float)$p['price'], 0, ',', '.') ?></span>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <p class="toko-subtext">
          Belum ada produk. Tambahkan produk di menu Kelola Produk.
        </p>
      <?php endif; ?>
    </section>

  <?php else: ?>
    <!-- Jika belum punya toko -->
    <section class="toko-card toko-empty">
      <h1>Buka Toko di NearBuy</h1>
      <p class="toko-lead">
        Kamu belum punya toko di NearBuy.
        Buka toko agar produk kamu bisa ditemukan pembeli di sekitar lokasi kamu.
      </p>

      <ol class="toko-steps">
        <li>Baca dulu peraturan dan panduan buka toko.</li>
        <li>Daftarkan toko dengan ID toko, nama toko, dan kata sandi khusus.</li>
        <li>Tunggu persetujuan admin. Setelah disetujui toko kamu akan aktif.</li>
      </ol>

      <div class="toko-actions">
        <a class="btn" href="<?= e($BASE) ?>/seller/peraturan_toko.php">
          Baca peraturan buka toko
        </a>
        <a class="btn primary" href="<?= e($BASE) ?>/seller/register_toko.php">
          Buka toko sekarang
        </a>
      </div>

      <p class="toko-note">
        ID toko berfungsi seperti email toko kamu. ID ini akan dipakai saat login ke dashboard seller.
      </p>
    </section>
  <?php endif; ?>

</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
</main>
</body>
</html>
