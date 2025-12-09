<?php
// ===============================================================
// NearBuy – Detail Produk (dengan jarak & tombol Google Maps)
// ===============================================================
declare(strict_types=1);

// BASE otomatis sesuai folder project (tanpa hardcode)
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$BASE = rtrim($scriptDir, '/');

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/settings.php';

// CSS khusus halaman detail produk
$EXTRA_CSS = ['style-detail-produk.css'];

// ---------- User ----------
$user    = $_SESSION['user'] ?? null;
$role    = $user['role'] ?? 'guest';
$isGuest = ($role === 'guest');
$userId  = (int)($user['id'] ?? 0);

// header umum
require_once __DIR__ . '/../includes/header.php';

// ---------- Helper dasar ----------
if (!function_exists('e')) {
    function e($s): string {
        return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('is_https_url')) {
    function is_https_url(string $p): bool {
        return (bool)preg_match('~^https?://~i', $p);
    }
}
if (!function_exists('join_url')) {
    function join_url(...$segments): string {
        $s = implode('/', array_map(fn($x) => trim((string)$x, '/'), $segments));
        return '/' . $s;
    }
}
if (!function_exists('upload_image_url')) {
    function upload_image_url(?string $path, string $BASE): string {
        if (!$path) return 'https://via.placeholder.com/560x320?text=No+Image';
        if (is_https_url($path)) return $path;
        $p = ltrim($path, '/');
        if (str_starts_with($p, 'products/'))       return join_url($BASE, 'uploads', $p);
        if (str_starts_with($p, 'uploads/'))        return join_url($BASE, $p);
        if (str_starts_with($p, 'public/uploads/')) return join_url($BASE, substr($p, 7));
        return join_url($BASE, 'uploads', $p);
    }
}

// ---------- Helper Haversine & format ----------
if (!function_exists('haversine_km')) {
    function haversine_km(float $lat1, float $lon1, float $lat2, float $lon2): float {
        $R = 6371.0; // radius bumi km
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

// ---------- Ambil slug ----------
$slug = trim((string)($_GET['slug'] ?? ''));
if ($slug === '') {
    header('Location: ' . $BASE . '/index.php');
    exit;
}

// ---------- Ambil produk (dengan info toko jika ada) ----------
$stmt = $pdo->prepare("
    SELECT 
      p.id,
      p.slug,
      p.title,
      p.price,
      p.compare_price,
      p.main_image,
      p.description,
      p.stock,
      p.is_active,
      p.created_at,
      (SELECT COUNT(1) 
         FROM product_variants v 
        WHERE v.product_id = p.id 
          AND v.is_active = 1) AS variant_count,
      s.id AS shop_id,
      s.name AS shop_name,
      s.latitude AS shop_lat,
      s.longitude AS shop_lng,
      s.address AS shop_address
    FROM products p
    LEFT JOIN shops s ON s.id = p.shop_id
    WHERE p.slug = ?
    LIMIT 1
");
$stmt->execute([$slug]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product || !(int)$product['is_active']) {
    ?>
    <div class="detail-page">
      <div class="detail-not-found">
        <h1>Produk tidak ditemukan</h1>
        <p>Produk yang kamu cari mungkin sudah tidak tersedia.</p>
        <a href="<?= e($BASE) ?>/index.php" class="btn-back-home">Kembali ke beranda</a>
      </div>
    </div>
    <?php
    require_once __DIR__ . '/../includes/footer.php';
    ?>
    </main>
    </body>
    </html>
    <?php
    exit;
}

$productId = (int)$product['id'];

// ---------- Gallery tambahan (jika ada tabel product_images) ----------
$images = [];
try {
    $stmtImg = $pdo->prepare("
        SELECT image_path 
        FROM product_images 
        WHERE product_id = ? 
        ORDER BY sort_order ASC, id ASC
    ");
    $stmtImg->execute([$productId]);
    $rowsImg = $stmtImg->fetchAll(PDO::FETCH_COLUMN, 0);
    foreach ($rowsImg as $rowPath) {
        $images[] = upload_image_url($rowPath, $BASE);
    }
} catch (Throwable $e) {
    // jika tabel tidak ada, abaikan
}

$mainImg = upload_image_url($product['main_image'] ?? null, $BASE);
if (empty($images)) {
    $images[] = $mainImg;
} else {
    if (!in_array($mainImg, $images, true)) {
        array_unshift($images, $mainImg);
    }
}

// ---------- Cek wishlist untuk user ini ----------
$isWish = false;
if (!$isGuest && $userId > 0) {
    try {
        $stmtWish = $pdo->prepare("
            SELECT 1 
            FROM wishlists 
            WHERE user_id = ? AND product_id = ? 
            LIMIT 1
        ");
        $stmtWish->execute([$userId, $productId]);
        $isWish = (bool)$stmtWish->fetchColumn();
    } catch (Throwable $e) {
        $isWish = false;
    }
}

// ---------- Back URL (dipakai di form wishlist) ----------
$currentUrl = $BASE . '/detail_produk.php?slug=' . urlencode($slug);

// ---------- Lokasi user (prioritas session, lalu DB user) ----------
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

// ---------- Hitung jarak ke toko (jika tersedia) + URL Google Maps ----------
$distanceKm    = null;
$distanceLabel = '';
$mapsUrl       = '';
$shopLat       = isset($product['shop_lat']) ? $product['shop_lat'] : null;
$shopLng       = isset($product['shop_lng']) ? $product['shop_lng'] : null;
$shopName      = $product['shop_name'] ?? null;

$hasShopCoords = (is_numeric($shopLat) && is_numeric($shopLng));

if ($hasShopCoords) {
    $shopLatF = (float)$shopLat;
    $shopLngF = (float)$shopLng;

    // Jika lokasi user tersedia → buat rute
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
        // User belum set lokasi → buka pin toko saja
        $mapsUrl = sprintf(
            'https://www.google.com/maps/search/?api=1&query=%F,%F',
            $shopLatF,
            $shopLngF
        );
    }
}

// ---------- Hitung promo ----------
$hasPromo  = (!is_null($product['compare_price']) && (float)$product['compare_price'] > (float)$product['price']);
$price     = (float)$product['price'];
$compare   = (float)($product['compare_price'] ?? 0);
$stock     = (int)$product['stock'];
$badgeText = $hasPromo ? 'Promo spesial' : 'Produk';

// ---------- Logika ULASAN ----------
$currentUser    = $_SESSION['user'] ?? null;
$currentUserId  = (int)($currentUser['id'] ?? 0);
$canReview       = false;
$alreadyReviewed = false;
$reviewOrderId   = null;
$reviews         = [];

try {
    // cek apakah user pernah beli produk ini dan ordernya selesai
    if ($currentUserId > 0) {
        $stmtBought = $pdo->prepare("
            SELECT oi.order_id, o.status
            FROM order_items oi
            JOIN orders o ON o.id = oi.order_id
            WHERE o.user_id = ? 
              AND oi.product_id = ?
            ORDER BY o.created_at DESC
            LIMIT 1
        ");
        $stmtBought->execute([$currentUserId, $productId]);
        $rowBought = $stmtBought->fetch(PDO::FETCH_ASSOC);

        if ($rowBought && $rowBought['status'] === 'selesai') {
            $reviewOrderId = (int)$rowBought['order_id'];
            $canReview = true;

            // cek apakah sudah pernah mengulas
            $stmtCheck = $pdo->prepare("
                SELECT id 
                FROM product_reviews
                WHERE user_id = ? AND product_id = ? AND order_id = ?
                LIMIT 1
            ");
            $stmtCheck->execute([$currentUserId, $productId, $reviewOrderId]);
            if ($stmtCheck->fetch()) {
                $alreadyReviewed = true;
                $canReview = false;
            }
        }
    }

    // ambil semua ulasan produk ini
    $stmtRev = $pdo->prepare("
      SELECT r.rating, r.comment, r.created_at, u.full_name
      FROM product_reviews r
      JOIN users u ON u.id = r.user_id
      WHERE r.product_id = ?
      ORDER BY r.created_at DESC
    ");
    $stmtRev->execute([$productId]);
    $reviews = $stmtRev->fetchAll(PDO::FETCH_ASSOC);

} catch (Throwable $e) {
    // kalau tabel product_reviews belum ada, bagian ulasan akan kosong
    $canReview       = false;
    $alreadyReviewed = false;
    $reviewOrderId   = null;
    $reviews         = [];
}

// ---------- Ambil kategori utama (bukan "semua") untuk "Mungkin Kamu Suka" ----------
$relatedProducts      = [];
$relatedCategoryName  = '';

try {
    $stmtCat = $pdo->prepare("
        SELECT c.id, c.slug, c.name
        FROM product_categories pc
        JOIN categories c ON c.id = pc.category_id
        WHERE pc.product_id = ?
          AND c.is_active = 1
          AND LOWER(c.slug) <> 'semua'
        ORDER BY c.sort_order, c.name
        LIMIT 1
    ");
    $stmtCat->execute([$productId]);
    $catRow = $stmtCat->fetch(PDO::FETCH_ASSOC);

    if ($catRow) {
        $catId = (int)$catRow['id'];
        $relatedCategoryName = (string)$catRow['name'];

        $stmtRel = $pdo->prepare("
            SELECT 
              p.id,
              p.slug,
              p.title,
              p.price,
              p.compare_price,
              p.main_image
            FROM products p
            JOIN product_categories pc ON pc.product_id = p.id
            WHERE pc.category_id = ?
              AND p.is_active = 1
              AND p.stock > 0
              AND p.id <> ?
            ORDER BY COALESCE(p.updated_at, p.created_at) DESC
            LIMIT 6
        ");
        $stmtRel->execute([$catId, $productId]);
        $relatedProducts = $stmtRel->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Throwable $e) {
    $relatedProducts = [];
}

?>
<div class="detail-page">

  <?php if (!empty($_SESSION['flash'])): ?>
    <div class="nb-flash">
      <?php
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);

        if (is_array($flash)) {
            echo e($flash['message'] ?? 'Terjadi kesalahan');
        } else {
            echo e((string)$flash);
        }
      ?>
    </div>
  <?php endif; ?>

  <!-- Breadcrumb sederhana -->
  <nav class="detail-breadcrumb">
    <a href="<?= e($BASE) ?>/index.php">Beranda</a>
    <span>/</span>
    <span><?= e($product['title']) ?></span>
  </nav>

  <div class="detail-layout">

    <!-- Kiri: Foto Produk -->
    <div class="detail-left">
      <div class="detail-photo-wrap">
        <img src="<?= e($mainImg) ?>" alt="<?= e($product['title']) ?>" class="detail-photo-main" loading="lazy">

        <?php if (!$isGuest): ?>
          <form method="post"
                action="<?= e($BASE) ?>/wishlist_toggle.php"
                class="detail-wish-form">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="product_id" value="<?= (int)$productId ?>">
            <input type="hidden" name="back" value="<?= e($currentUrl) ?>">

            <button type="submit"
                    class="btn-wish <?= $isWish ? 'active' : '' ?>"
                    aria-label="Wishlist produk">
              <?= $isWish ? '♥' : '♡' ?>
            </button>
          </form>
        <?php endif; ?>
      </div>

      <?php if (count($images) > 1): ?>
        <div class="detail-thumbs">
          <?php foreach ($images as $imgUrl): ?>
            <div class="detail-thumb">
              <img src="<?= e($imgUrl) ?>" alt="Foto produk tambahan" loading="lazy">
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <!-- Kanan: Info Produk -->
    <div class="detail-right">
      <div class="detail-header">
        <span class="detail-badge"><?= e($badgeText) ?></span>
        <h1 class="detail-title"><?= e($product['title']) ?></h1>
      </div>

      <div class="detail-price-block">
        <?php if ($hasPromo): ?>
          <div class="detail-price-main">
            <span class="detail-price-now">Rp<?= number_format($price, 0, ',', '.') ?></span>
            <span class="detail-price-old">Rp<?= number_format($compare, 0, ',', '.') ?></span>
          </div>
          <div class="detail-price-info">
            <?php 
              $discPercent = ($compare > 0) ? round(100 - ($price / $compare * 100)) : 0;
            ?>
            <span class="detail-price-disc">-<?= (int)$discPercent ?>%</span>
          </div>
        <?php else: ?>
          <div class="detail-price-main">
            <span class="detail-price-now">Rp<?= number_format($price, 0, ',', '.') ?></span>
          </div>
        <?php endif; ?>
      </div>

      <div class="detail-meta">
        <div class="detail-meta-item">
          <span class="label">Stok</span>
          <span class="value <?= $stock > 0 ? 'in-stock' : 'out-stock' ?>">
            <?= $stock > 0 ? 'Tersedia (' . $stock . ' pcs)' : 'Habis' ?>
          </span>
        </div>
        <?php if ((int)$product['variant_count'] > 0): ?>
          <div class="detail-meta-item">
            <span class="label">Varian</span>
            <span class="value"><?= (int)$product['variant_count'] ?> opsi</span>
          </div>
        <?php endif; ?>
        <div class="detail-meta-item">
          <span class="label">Kode Produk</span>
          <span class="value">#<?= (int)$productId ?></span>
        </div>

        <!-- Nama toko -->
        <?php if (!empty($shopName)): ?>
          <div class="detail-meta-item">
            <span class="label">Toko</span>
            <span class="value">
              <?= e($shopName) ?>
              <?php if (!empty($product['shop_address'])): ?>
                · <?= e($product['shop_address']) ?>
              <?php endif; ?>
            </span>
          </div>
        <?php endif; ?>

        <!-- Jarak + Link Google Maps -->
        <?php if ($hasShopCoords): ?>
          <div class="detail-meta-item">
            <span class="label">Jarak</span>
            <span class="value">
              <?php if ($distanceLabel !== ''): ?>
                <?= e($distanceLabel) ?>
              <?php else: ?>
                Lokasi Anda belum diatur.
              <?php endif; ?>
            </span>
          </div>
          <?php if ($mapsUrl !== ''): ?>
            <div class="detail-meta-item">
              <span class="label">Lokasi</span>
              <span class="value">
                <a href="<?= e($mapsUrl) ?>" target="_blank" rel="noopener noreferrer" class="btn-maps">
                  Lihat di Google Maps
                </a>
              </span>
            </div>
          <?php endif; ?>
        <?php else: ?>
          <div class="detail-meta-item">
            <span class="label">Lokasi</span>
            <span class="value">
              Lokasi toko belum diatur.
            </span>
          </div>
        <?php endif; ?>

      </div>

      <?php if ($stock > 0): ?>
        <div class="detail-buy-box">
          <!-- Hanya satu form: Pesan Sekarang -->
          <form method="post" action="<?= e($BASE) ?>/pesan.php" class="detail-form">
            <?php if (function_exists('csrf_input')) { csrf_input(); } ?>
            <input type="hidden" name="product_id" value="<?= (int)$productId ?>">

            <div class="detail-qty-row">
              <label for="qty">Jumlah</label>
              <input id="qty" type="number" name="qty" min="1" max="<?= $stock ?>" value="1">
            </div>

            <div class="detail-buttons">
              <button type="submit" class="btn-secondary">
                Pesan Sekarang
              </button>
            </div>
          </form>
        </div>
      <?php else: ?>
        <div class="detail-buy-box">
          <p class="out-stock-text">Stok habis, produk tidak dapat dipesan saat ini.</p>
        </div>
      <?php endif; ?>

      <div class="detail-desc">
        <h2>Deskripsi Produk</h2>
        <p>
          <?= nl2br(e($product['description'] ?? 'Belum ada deskripsi produk.')) ?>
        </p>
      </div>
    </div>

  </div>

  <!-- ========== Ulasan Pembeli ========== -->
  <section class="product-review-section">
    <h2>Ulasan Pembeli</h2>

    <?php if ($currentUserId === 0): ?>
      <p class="review-info">Login untuk menulis ulasan.</p>

    <?php elseif ($alreadyReviewed): ?>
      <p class="review-info">Kamu sudah menulis ulasan untuk produk ini. Terima kasih.</p>

    <?php elseif ($canReview): ?>
      <form method="post" action="<?= e($BASE) ?>/submit_review.php" class="review-form">
        <?php if (function_exists('csrf_input')) { csrf_input(); } ?>
        <input type="hidden" name="product_id" value="<?= (int)$productId ?>">
        <input type="hidden" name="order_id"   value="<?= (int)$reviewOrderId ?>">
        <input type="hidden" name="slug"       value="<?= e($product['slug']) ?>">

        <label for="rating">Rating</label>
        <div class="rating-input">
          <?php for ($i = 5; $i >= 1; $i--): ?>
            <label>
              <input type="radio" name="rating" value="<?= $i ?>" <?= $i === 5 ? 'checked' : '' ?>>
              <?= $i ?> ⭐
            </label>
          <?php endfor; ?>
        </div>

        <label for="comment">Ulasan</label>
        <textarea name="comment" rows="3" placeholder="Ceritakan pengalamanmu menggunakan produk ini"></textarea>

        <button type="submit" class="btn-primary">Kirim Ulasan</button>
      </form>

    <?php else: ?>
      <p class="review-info">
        Kamu bisa menulis ulasan setelah pesanan untuk produk ini berstatus selesai.
      </p>
    <?php endif; ?>

    <?php if (!empty($reviews)): ?>
      <div class="review-list">
        <?php foreach ($reviews as $r): ?>
          <div class="review-item">
            <div class="review-head">
              <span class="review-name">
                <?= e($r['full_name'] ?: 'Pembeli') ?>
              </span>
              <span class="review-rating">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                  <?= $i <= (int)$r['rating'] ? '⭐' : '☆' ?>
                <?php endfor; ?>
              </span>
            </div>
            <?php if (!empty($r['comment'])): ?>
              <p class="review-comment"><?= nl2br(e($r['comment'])) ?></p>
            <?php endif; ?>
            <div class="review-date"><?= e($r['created_at']) ?></div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <p class="review-empty">Belum ada ulasan untuk produk ini.</p>
    <?php endif; ?>
  </section>

  <!-- ========== Mungkin Kamu Suka ========== -->
  <?php if (!empty($relatedProducts)): ?>
    <section class="detail-related">
      <div class="detail-related-head">
        <h2>Mungkin Kamu Suka</h2>
        <?php if ($relatedCategoryName !== ''): ?>
          <span class="detail-related-sub">Produk lain di kategori <?= e($relatedCategoryName) ?></span>
        <?php endif; ?>
      </div>

      <div class="detail-related-grid">
        <?php foreach ($relatedProducts as $rp): 
          $rImg = upload_image_url($rp['main_image'] ?? null, $BASE);
          $rHasPromo = (!is_null($rp['compare_price']) && (float)$rp['compare_price'] > (float)$rp['price']);
          $rDetailUrl = $BASE . '/detail_produk.php?slug=' . urlencode($rp['slug']);
        ?>
          <a href="<?= e($rDetailUrl) ?>" class="detail-rel-card">
            <div class="detail-rel-img-wrap">
              <img src="<?= e($rImg) ?>" alt="<?= e($rp['title']) ?>" loading="lazy">
              <?php if ($rHasPromo): ?>
                <span class="detail-rel-badge">Promo</span>
              <?php endif; ?>
            </div>
            <div class="detail-rel-body">
              <h3 class="detail-rel-title"><?= e($rp['title']) ?></h3>
              <div class="detail-rel-price">
                <?php if ($rHasPromo): ?>
                  <span class="old">Rp<?= number_format((float)$rp['compare_price'], 0, ',', '.') ?></span>
                <?php endif; ?>
                <span class="now">Rp<?= number_format((float)$rp['price'], 0, ',', '.') ?></span>
              </div>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endif; ?>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
</main>
</body>
</html>