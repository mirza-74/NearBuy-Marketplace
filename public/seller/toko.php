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

// helper jarak (dipakai untuk pesanan terbaru)
if (!function_exists('haversine_km')) {
    function haversine_km(float $lat1, float $lon1, float $lat2, float $lon2): float {
        $R = 6371.0; // km
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $R * $c;
    }
}
if (!function_exists('format_distance_label')) {
    function format_distance_label(?float $km): string {
        if ($km === null) return '';
        if ($km >= 1) {
            return round($km, 1) . ' km';
        }
        $m = (int)round($km * 1000);
        return $m . ' m';
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
// Handler POST: tombol "Pesanan selesai"
// ===============================================================
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method === 'POST' && ($_POST['action'] ?? '') === 'complete_order') {
    $orderId = (int)($_POST['order_id'] ?? 0);

    $flash = [
        'type'    => 'error',
        'message' => 'Pesanan tidak ditemukan atau tidak bisa diubah.'
    ];

    // cek CSRF
    $csrf_ok = true;
    if (function_exists('csrf_verify')) {
        if (!csrf_verify()) {
            $csrf_ok = false;
            $flash['message'] = 'Sesi formulir sudah tidak valid. Coba lagi.';
        }
    }

    if ($csrf_ok && $orderId > 0) {
        try {
            // cek apakah order benar milik toko seller ini
            $stmtC = $pdo->prepare("
              SELECT o.id, o.status
              FROM orders o
              JOIN order_items oi ON oi.order_id = o.id
              JOIN products p ON p.id = oi.product_id
              JOIN shops s ON s.id = p.shop_id
              WHERE o.id = ? AND s.user_id = ?
              LIMIT 1
            ");
            $stmtC->execute([$orderId, $userId]);
            $row = $stmtC->fetch(PDO::FETCH_ASSOC);

            if ($row) {
                if ($row['status'] !== 'selesai') {
                    $stmtU = $pdo->prepare("
                      UPDATE orders 
                      SET status = 'selesai', updated_at = NOW()
                      WHERE id = ?
                    ");
                    $stmtU->execute([$orderId]);

                    $flash['type']    = 'success';
                    $flash['message'] = 'Pesanan #' . $orderId . ' ditandai sebagai selesai.';
                } else {
                    $flash['type']    = 'info';
                    $flash['message'] = 'Pesanan #' . $orderId . ' sudah berstatus selesai.';
                }
            }
        } catch (Throwable $e) {
            $flash['type']    = 'error';
            $flash['message'] = 'Gagal mengubah status pesanan: ' . $e->getMessage();
        }
    }

    $_SESSION['flash'] = $flash;
    header('Location: ' . $BASE_SELLER . '/toko.php');
    exit;
}

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

<?php
// ====== FLASH DI LUAR .nb-shell (supaya nggak ikut flex layout) ======
$flash = $_SESSION['flash'] ?? null;
if (is_array($flash) && !empty($flash['message'])): ?>
  <div class="nb-flash">
    <?= e($flash['message']) ?>
  </div>
<?php
endif;
unset($_SESSION['flash']);
?>

<div class="nb-shell">

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
      $pkgStatus = $shop['subscription_status'] ?? null;
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

      // ================= Pesanan terbaru untuk toko ini =================
      $recentOrders = [];
      try {
          $stmtO = $pdo->prepare("
            SELECT 
              o.id AS order_id,
              o.created_at,
              o.payment_method,
              o.grand_total,
              o.status,
              u.full_name AS buyer_name,
              u.latitude AS buyer_lat,
              u.longitude AS buyer_lng,
              oi.qty,
              oi.title AS product_title
            FROM orders o
            JOIN order_items oi ON oi.order_id = o.id
            JOIN products p ON p.id = oi.product_id
            JOIN users u ON u.id = o.user_id
            WHERE p.shop_id = ?
            ORDER BY o.created_at DESC
            LIMIT 5
          ");
          $stmtO->execute([(int)$shop['id']]);
          $recentOrders = $stmtO->fetchAll(PDO::FETCH_ASSOC);
      } catch (Throwable $e) {
          $recentOrders = [];
      }
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

    <?php if (!empty($recentOrders)): ?>
      <section class="nb-card nb-main-card" style="margin-top:24px;">
        <h2 class="nb-title">Pesanan Terbaru</h2>
        <p class="nb-sub nb-sub-small">
          Pesanan yang masuk ke toko kamu. Cek jarak dan metode pembayaran, lalu siapkan pesanan.
        </p>

        <div class="nb-orders-list">
          <?php foreach ($recentOrders as $o): ?>
            <?php
              $distanceText = '';
              if ($o['buyer_lat'] !== null && $o['buyer_lng'] !== null &&
                  $shop['latitude'] !== null && $shop['longitude'] !== null) {

                  $km = haversine_km(
                      (float)$o['buyer_lat'],
                      (float)$o['buyer_lng'],
                      (float)$shop['latitude'],
                      (float)$shop['longitude']
                  );
                  $distanceText = format_distance_label($km) . ' dari pembeli';
              }

              $method = strtolower((string)($o['payment_method'] ?? 'cod'));
              $methodLabel = ($method === 'qris') ? 'QRIS' : 'Cash / COD';

              $status = (string)($o['status'] ?? '');
            ?>
            <div class="nb-order-row" style="display:flex;justify-content:space-between;gap:16px;padding:10px 0;border-bottom:1px solid #eee;">
              <div>
                <div class="nb-order-id" style="font-weight:600;">
                  Order #<?= e($o['order_id']) ?> · <?= e($o['product_title']) ?> (<?= (int)$o['qty'] ?> pcs)
                </div>
                <div class="nb-order-meta" style="font-size:13px;color:#555;">
                  Pembeli: <?= e($o['buyer_name'] ?? 'Pembeli') ?>
                  <?php if ($distanceText): ?>
                    · <?= e($distanceText) ?>
                  <?php endif; ?>
                  <?php if ($status): ?>
                    · Status: <?= e($status) ?>
                  <?php endif; ?>
                </div>
              </div>
              <div style="text-align:right;font-size:13px;">
                <div class="nb-order-amount" style="font-weight:600;">
                  Rp<?= number_format((float)$o['grand_total'], 0, ',', '.') ?>
                </div>
                <div class="nb-order-method">
                  Metode: <?= e($methodLabel) ?>
                </div>
                <div class="nb-order-action" style="margin-top:4px;">
                  <div style="color:#2563eb;margin-bottom:4px;">
                    Siapkan pesanan
                  </div>

                  <?php if ($status !== 'selesai'): ?>
                    <form method="post" action="<?= e($BASE_SELLER) ?>/toko.php" style="display:inline;">
                      <?php if (function_exists('csrf_input')) { csrf_input(); } ?>
                      <input type="hidden" name="action" value="complete_order">
                      <input type="hidden" name="order_id" value="<?= (int)$o['order_id'] ?>">
                      <button type="submit"
                              style="padding:4px 10px;border-radius:999px;border:none;cursor:pointer;background:#16a34a;color:#fff;font-size:12px;">
                        Pesanan selesai
                      </button>
                    </form>
                  <?php else: ?>
                    <span style="font-size:12px;color:#16a34a;font-weight:600;">Sudah selesai</span>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </section>
    <?php endif; ?>

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
