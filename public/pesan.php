<?php
// ===============================================================
// NearBuy – Pesan Sekarang (buat pesanan 1 produk)
// ===============================================================
declare(strict_types=1);

$BASE = '/NearBuy-marketplace/public';

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/settings.php';

error_reporting(E_ALL);
ini_set('display_errors', '1');

// ---------- Helper dasar ----------
if (!function_exists('e')) {
    function e($s): string {
        return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
    }
}

// helper jarak (sama dengan detail_produk)
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
            return round($km, 1) . ' km dari lokasi Anda';
        }
        $m = (int)round($km * 1000);
        return $m . ' m dari lokasi Anda';
    }
}

// ---------- User ----------
$user   = $_SESSION['user'] ?? null;
$userId = (int)($user['id'] ?? 0);

// CSS khusus halaman (samakan dengan detail_produk)
$EXTRA_CSS = ['style-detail-produk.css'];

// ---------- Variabel default untuk tampilan ----------
$method         = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$errors         = [];
$orderCreated   = false;
$orderId        = null;
$slug           = '';
$title          = '';
$qty            = (int)($_POST['qty'] ?? 1);
$grand_total    = 0.0;

$shopName       = '';
$shopAddress    = '';
$distanceLabel  = '';
$mapsUrl        = '';
$hasShopCoords  = false;
$shopHasQris    = false;
$paymentMethod  = null; // 'qris' atau 'cod'

// ---------- Proses utama (tanpa output HTML dulu) ----------
if ($method !== 'POST') {
    $errors[] = 'Metode tidak diizinkan. Silakan akses fitur ini dari halaman detail produk.';
} else {

    // Cek login
    if (!$user || $userId <= 0) {
        $_SESSION['flash'] = [
            'type'    => 'error',
            'message' => 'Silakan login terlebih dahulu untuk melakukan pemesanan.'
        ];
        header('Location: ' . $BASE . '/login.php');
        exit;
    }

    // Cek CSRF
    $csrf_ok = true;
    if (function_exists('csrf_verify')) {
        if (!csrf_verify()) {
            $csrf_ok = false;
            $errors[] = 'Sesi formulir sudah tidak valid. Silakan kembali ke halaman produk dan coba lagi.';
        }
    }

    // Ambil product_id
    $productId = (int)($_POST['product_id'] ?? 0);
    if ($qty < 1) {
        $qty = 1;
    }

    if ($csrf_ok && $productId <= 0) {
        $errors[] = 'Produk tidak valid.';
    }

    if ($csrf_ok && $productId > 0 && empty($errors)) {

        // Ambil produk (termasuk shop_id)
        $stmt = $pdo->prepare("
            SELECT 
              p.id,
              p.slug,
              p.title,
              p.price,
              p.stock,
              p.is_active,
              p.shop_id
            FROM products p
            WHERE p.id = ?
            LIMIT 1
        ");
        $stmt->execute([$productId]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$product || !(int)$product['is_active']) {
            $errors[] = 'Produk tidak tersedia atau sudah tidak aktif.';
        } else {
            $slug   = (string)$product['slug'];
            $title  = (string)$product['title'];
            $stock  = (int)$product['stock'];
            $price  = (float)$product['price'];
            $shopId = (int)($product['shop_id'] ?? 0);

            if ($stock <= 0) {
                $errors[] = 'Stok produk habis.';
            } elseif ($qty > $stock) {
                $errors[] = 'Stok tidak mencukupi. Stok tersedia: ' . $stock . ' pcs.';
            } else {
                // ---------- Ambil data toko ----------
                $shopLat = null;
                $shopLng = null;

                if ($shopId > 0) {
                    try {
                        $stmtShop = $pdo->prepare("
                            SELECT id, name, address, latitude, longitude
                            FROM shops
                            WHERE id = ?
                            LIMIT 1
                        ");
                        $stmtShop->execute([$shopId]);
                        $shopRow = $stmtShop->fetch(PDO::FETCH_ASSOC);

                        if ($shopRow) {
                            $shopName    = (string)($shopRow['name'] ?? '');
                            $shopAddress = (string)($shopRow['address'] ?? '');
                            $shopLat     = $shopRow['latitude'];
                            $shopLng     = $shopRow['longitude'];
                        }
                    } catch (Throwable $e) {
                        // abaikan, biar tetep jalan
                    }

                    // Cek QRIS dari DB (kolom optional shops.has_qris)
                    try {
                        $stmtQris = $pdo->prepare("
                            SELECT has_qris 
                            FROM shops 
                            WHERE id = ? 
                            LIMIT 1
                        ");
                        $stmtQris->execute([$shopId]);
                        $rowQ = $stmtQris->fetch(PDO::FETCH_ASSOC);
                        if ($rowQ && isset($rowQ['has_qris'])) {
                            $shopHasQris = ((int)$rowQ['has_qris'] === 1);
                        }
                    } catch (Throwable $e) {
                        // kalau kolom belum ada → QRIS dianggap tidak tersedia
                        $shopHasQris = false;
                    }
                }

                // tentukan metode pembayaran yang dicatat di orders
                // kalau toko punya QRIS → pakai 'qris', kalau tidak → 'cod'
                $paymentMethod = $shopHasQris ? 'qris' : 'cod';

                // ---------- Lokasi user ----------
                $userLat = isset($_SESSION['user_lat']) ? (float)$_SESSION['user_lat'] : null;
                $userLng = isset($_SESSION['user_lng']) ? (float)$_SESSION['user_lng'] : null;

                if (($userLat === null || $userLng === null) && $userId > 0) {
                    try {
                        $stmtUL = $pdo->prepare("SELECT latitude, longitude FROM users WHERE id = ? LIMIT 1");
                        $stmtUL->execute([$userId]);
                        $uLoc = $stmtUL->fetch(PDO::FETCH_ASSOC);
                        if ($uLoc && $uLoc['latitude'] !== null && $uLoc['longitude'] !== null) {
                            $userLat = (float)$uLoc['latitude'];
                            $userLng = (float)$uLoc['longitude'];
                        }
                    } catch (Throwable $e) {
                        // ignore
                    }
                }

                // ---------- Hitung jarak & URL Maps ----------
                $hasShopCoords = (is_numeric($shopLat) && is_numeric($shopLng));
                if ($hasShopCoords) {
                    $shopLatF = (float)$shopLat;
                    $shopLngF = (float)$shopLng;

                    if ($userLat !== null && $userLng !== null) {
                        $distanceKm    = haversine_km((float)$userLat, (float)$userLng, $shopLatF, $shopLngF);
                        $distanceLabel = format_distance_label($distanceKm);

                        $mapsUrl = sprintf(
                            'https://www.google.com/maps/dir/%F,%F/%F,%F/',
                            $userLat,
                            $userLng,
                            $shopLatF,
                            $shopLngF
                        );
                    } else {
                        $mapsUrl = sprintf(
                            'https://www.google.com/maps/search/?api=1&query=%F,%F',
                            $shopLatF,
                            $shopLngF
                        );
                    }
                }

                // ========== Proses buat pesanan ==========
                try {
                    $pdo->beginTransaction();

                    $total_items        = $qty;
                    $total_barang       = $price * $qty;
                    $admin_fee_per_item = 1500.00;
                    $total_pajak_admin  = $admin_fee_per_item * $qty;
                    $total_ongkir       = 0.00;
                    $total_diskon       = 0.00;
                    $grand_total        = $total_barang + $total_ongkir + $total_pajak_admin - $total_diskon;

                    // ID baru untuk orders (MAX(id)+1)
                    $stmtNextOrderId = $pdo->query("SELECT IFNULL(MAX(id), 0) + 1 AS next_id FROM orders");
                    $orderId = (int)$stmtNextOrderId->fetchColumn();

                    $stmtOrder = $pdo->prepare("
                        INSERT INTO orders (
                            id,
                            user_id,
                            total_items,
                            total_barang,
                            total_ongkir,
                            total_pajak_admin,
                            total_diskon,
                            grand_total,
                            payment_method
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmtOrder->execute([
                        $orderId,
                        $userId,
                        $total_items,
                        $total_barang,
                        $total_ongkir,
                        $total_pajak_admin,
                        $total_diskon,
                        $grand_total,
                        $paymentMethod
                    ]);

                    // ID baru untuk order_items (MAX(id)+1)
                    $stmtNextItemId = $pdo->query("SELECT IFNULL(MAX(id), 0) + 1 AS next_id FROM order_items");
                    $orderItemId = (int)$stmtNextItemId->fetchColumn();

                    $subtotal        = $price * $qty;
                    $admin_fee_total = $admin_fee_per_item * $qty;

                    $stmtItem = $pdo->prepare("
                        INSERT INTO order_items (
                            id,
                            order_id,
                            product_id,
                            variant_id,
                            title,
                            qty,
                            price,
                            subtotal,
                            admin_fee_per_item,
                            admin_fee_total
                        ) VALUES (?, ?, ?, NULL, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmtItem->execute([
                        $orderItemId,
                        $orderId,
                        $productId,
                        $title,
                        $qty,
                        $price,
                        $subtotal,
                        $admin_fee_per_item,
                        $admin_fee_total
                    ]);

                    // Kurangi stok produk
                    $stmtStock = $pdo->prepare("
                        UPDATE products 
                        SET stock = stock - ? 
                        WHERE id = ? AND stock >= ?
                    ");
                    $stmtStock->execute([$qty, $productId, $qty]);

                    $pdo->commit();
                    $orderCreated = true;

                } catch (Throwable $e) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    $errors[] = 'Terjadi kesalahan saat membuat pesanan: ' . $e->getMessage();
                }
            }
        }
    }
}

// ---------- Muat header (navbar, dll) ----------
require_once __DIR__ . '/../includes/header.php';
?>

<div class="detail-page">

  <!-- Breadcrumb -->
  <nav class="detail-breadcrumb">
    <a href="<?= e($BASE) ?>/index.php">Beranda</a>
    <span>/</span>
    <span>Pesan Sekarang</span>
  </nav>

  <div class="detail-layout">

    <!-- Kartu kiri: Ringkasan pesanan -->
    <div class="detail-right">
      <div class="detail-header">
        <span class="detail-badge">Pesanan</span>
        <h1 class="detail-title">Pesan Sekarang</h1>
      </div>

      <?php if (!empty($errors)): ?>
        <div class="nb-flash" style="margin-bottom:16px;">
          <strong>Terjadi kesalahan:</strong>
          <ul>
            <?php foreach ($errors as $err): ?>
              <li><?= e($err) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php elseif ($orderCreated && $orderId !== null): ?>
        <div class="nb-flash" style="margin-bottom:16px;">
          <p>Pesanan kamu sudah dibuat dan diteruskan ke penjual.</p>
          <p><strong>No. Order:</strong> #<?= (int)$orderId ?></p>
          <?php if ($title !== ''): ?>
            <p><strong>Produk:</strong> <?= e($title) ?> (<?= (int)$qty ?> pcs)</p>
          <?php endif; ?>
          <p><strong>Total:</strong> Rp<?= number_format($grand_total ?? 0, 0, ',', '.') ?></p>
          <?php if ($paymentMethod): ?>
            <p><strong>Metode di pesanan ini:</strong>
              <?= $paymentMethod === 'qris' ? 'QRIS' : 'Cash / COD' ?>
            </p>
          <?php endif; ?>
        </div>
      <?php else: ?>
        <div class="nb-flash" style="margin-bottom:16px;">
          Terjadi sesuatu yang tidak terduga. Silakan coba lagi dari halaman produk.
        </div>
      <?php endif; ?>

      <div class="detail-desc">
        <h2>Aksi Selanjutnya</h2>
        <p>
          Penjual akan menerima informasi pesanan ini di dashboard toko mereka.
          Silakan tunggu konfirmasi / pesanan disiapkan.
        </p>
        <p style="margin-top:10px;">
          <?php if ($slug !== ''): ?>
            <a href="<?= e($BASE) ?>/detail_produk.php?slug=<?= urlencode($slug) ?>" class="btn-secondary">
              &larr; Kembali ke detail produk
            </a>
            &nbsp;
          <?php endif; ?>
          <a href="<?= e($BASE) ?>/index.php" class="btn-primary">
            Kembali ke beranda
          </a>
        </p>
      </div>
    </div>

    <!-- Kartu kanan: Lokasi & metode pembayaran -->
    <aside class="nb-card nb-side-card" style="margin-left:24px;align-self:flex-start;max-width:360px;">
      <h2 class="nb-side-title">Lokasi & Pembayaran</h2>

      <!-- Lokasi & jarak -->
      <div class="nb-field">
        <div class="nb-label">Toko</div>
        <div class="nb-value">
          <?= $shopName !== '' ? e($shopName) : 'Toko belum terhubung' ?>
          <?php if ($shopAddress !== ''): ?>
            <br><small><?= e($shopAddress) ?></small>
          <?php endif; ?>
        </div>
      </div>

      <div class="nb-field">
        <div class="nb-label">Jarak</div>
        <div class="nb-value">
          <?php if ($hasShopCoords && $distanceLabel !== ''): ?>
            <?= e($distanceLabel) ?>
          <?php elseif ($hasShopCoords): ?>
            Lokasi Anda belum diatur.
          <?php else: ?>
            Lokasi toko belum diatur.
          <?php endif; ?>
        </div>
      </div>

      <?php if ($mapsUrl !== ''): ?>
        <div class="nb-field">
          <div class="nb-label">Google Maps</div>
          <div class="nb-value">
            <a href="<?= e($mapsUrl) ?>" target="_blank" rel="noopener noreferrer" class="btn-maps">
              Lihat di Google Maps
            </a>
          </div>
        </div>
      <?php endif; ?>

      <hr style="margin:16px 0;border:none;border-top:1px solid #eee;">

      <!-- Metode pembayaran (informasi saja) -->
      <div class="nb-field">
        <div class="nb-label">Metode Pembayaran</div>
        <div class="nb-value">
          <div style="display:flex;flex-direction:column;gap:8px;">
            <!-- QRIS -->
            <label style="display:flex;align-items:center;gap:8px;">
              <input type="radio" name="payment_method_view" value="qris"
                     <?= $shopHasQris ? ($paymentMethod === 'qris' ? 'checked' : '') : 'disabled' ?>>
              <span>
                QRIS
                <?php if (!$shopHasQris): ?>
                  <small style="color:#888;display:block;">QRIS tidak tersedia untuk toko ini.</small>
                <?php else: ?>
                  <small style="color:#888;display:block;">Metode non-tunai dari penjual.</small>
                <?php endif; ?>
              </span>
            </label>

            <!-- CASH / COD -->
            <label style="display:flex;align-items:center;gap:8px;">
              <input type="radio" name="payment_method_view" value="cod"
                     <?= $paymentMethod === 'cod' ? 'checked' : '' ?>>
              <span>
                Cash / COD
                <small style="color:#888;display:block;">Bayar langsung saat barang diterima.</small>
              </span>
            </label>
          </div>
        </div>
      </div>

      <p class="nb-sub nb-sub-small" style="margin-top:10px;">
        Metode di atas dicatat pada pesanan, dan bisa ditampilkan di dashboard seller
        untuk membantu mereka menyiapkan pesanan.
      </p>
    </aside>

  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
</main>
</body>
</html>