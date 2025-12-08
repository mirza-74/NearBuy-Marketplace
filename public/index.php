<?php
// ===============================================================
// NearBuy – Beranda
// ===============================================================
declare(strict_types=1);

// BASE: otomatis ambil folder /public yang sedang dipakai
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$BASE = rtrim($scriptDir, '/');

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/settings.php';
require_once __DIR__ . '/../includes/header.php';

// data user bisa kosong jika guest
$user    = $_SESSION['user'] ?? null;
$role    = $user['role'] ?? 'guest';
$userId  = (int)($user['id'] ?? 0);
$isGuest = ($role === 'guest' || $userId <= 0);

// poin user jika nanti dipakai untuk fitur lain
$userPoints = $isGuest ? 0 : (int)($user['points'] ?? 0);

// lokasi user dari session jika sudah pernah di set di halaman lain
$userLat = isset($_SESSION['user_lat']) ? (float)$_SESSION['user_lat'] : null;
$userLng = isset($_SESSION['user_lng']) ? (float)$_SESSION['user_lng'] : null;

// ===================== HELPER Gambar dan URL =====================
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
    if (!$path) {
      return 'https://via.placeholder.com/560x320?text=No+Image';
    }
    if (is_https_url($path)) {
      return $path;
    }
    $p = ltrim($path, '/');
    if (str_starts_with($p, 'products/')) {
      return join_url($BASE, 'uploads', $p);
    }
    if (str_starts_with($p, 'uploads/')) {
      return join_url($BASE, $p);
    }
    if (str_starts_with($p, 'public/uploads/')) {
      return join_url($BASE, substr($p, 7));
    }
    return join_url($BASE, 'uploads', $p);
  }
}

// ===================== DAFTAR KATEGORI (Sidebar) =====================
$categories = [];
try {
    $stmtCat = $pdo->prepare("
        SELECT id, name, slug 
        FROM categories 
        ORDER BY name ASC
    ");
    $stmtCat->execute();
    $categories = $stmtCat->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $categories = [];
}

// ===================== BANNER UTAMA =====================
$homeBannerPath = (function() use ($pdo, $BASE) {
    try {
        $stmt = $pdo->prepare("
            SELECT value 
            FROM site_settings 
            WHERE `key` = 'home_banner' 
            LIMIT 1
        ");
        $stmt->execute();
        $v = $stmt->fetchColumn();
        return $v ?: ($BASE . '/assets/banner-nearbuy.jpg');
    } catch (Throwable $e) {
        // jika tabel site_settings belum ada, pakai banner default
        return $BASE . '/assets/banner-nearbuy.jpg';
    }
})();

// ===================== FILTER DASAR PRODUK =====================
// search sederhana untuk nama produk
$searchQRaw = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
$searchQ    = mb_substr($searchQRaw, 0, 80);

// HANYA: produk aktif, stok > 0, pemilik ber-role seller, toko aktif
$whereParts = [
    "p.is_active = 1",
    "p.stock > 0",
    "u.role = 'seller'",
    "s.is_active = 1"
];
$params = [];

// filter search jika ada
if ($searchQ !== '') {
    $whereParts[] = "(p.title LIKE ? OR p.description LIKE ?)";
    $params[] = "%{$searchQ}%";
    $params[] = "%{$searchQ}%";
}

$whereSql = implode(' AND ', $whereParts);

// ===================== REKOMENDASI: PRODUK TERDEKAT =====================
$rekom = [];

try {
    if ($userLat !== null && $userLng !== null) {
        $radiusKm = 5;

        $sqlRekom = "
            SELECT 
                p.id,
                p.slug,
                p.title,
                p.price,
                p.compare_price,
                p.main_image,
                s.name AS shop_name,
                (
                    6371 * ACOS(
                        COS(RADIANS(:user_lat)) 
                        * COS(RADIANS(s.latitude)) 
                        * COS(RADIANS(s.longitude) - RADIANS(:user_lng)) 
                        + SIN(RADIANS(:user_lat)) 
                        * SIN(RADIANS(s.latitude))
                    )
                ) AS distance_km
            FROM products p
            JOIN shops s ON s.id = p.shop_id
            JOIN users u ON u.id = s.user_id
            LEFT JOIN product_categories pc ON pc.product_id = p.id
            LEFT JOIN categories c ON c.id = pc.category_id
            WHERE {$whereSql}
            HAVING distance_km <= :radius
            ORDER BY distance_km ASC, p.created_at DESC
            LIMIT 8
        ";

        $stmtRekom = $pdo->prepare($sqlRekom);
        $stmtRekom->bindValue(':user_lat', $userLat);
        $stmtRekom->bindValue(':user_lng', $userLng);
        $stmtRekom->bindValue(':radius', $radiusKm);

        // bind param ? dari $params (search)
        $idx = 1;
        foreach ($params as $pVal) {
            $stmtRekom->bindValue($idx, $pVal);
            $idx++;
        }

        $stmtRekom->execute();
        $rekom = $stmtRekom->fetchAll(PDO::FETCH_ASSOC);
    }

    // fallback jika tidak ada lokasi user atau tidak ada hasil
    if (empty($rekom)) {
        $sqlFallback = "
            SELECT 
                p.id,
                p.slug,
                p.title,
                p.price,
                p.compare_price,
                p.main_image,
                s.name AS shop_name
            FROM products p
            JOIN shops s ON s.id = p.shop_id
            JOIN users u ON u.id = s.user_id
            LEFT JOIN product_categories pc ON pc.product_id = p.id
            LEFT JOIN categories c ON c.id = pc.category_id
            WHERE {$whereSql}
            ORDER BY p.created_at DESC
            LIMIT 8
        ";
        $stmtFb = $pdo->prepare($sqlFallback);
        $stmtFb->execute($params);
        $rekom = $stmtFb->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Throwable $e) {
    $rekom = [];
}

// ===================== PRODUK LAINNYA UNTUK LIST BAWAH =====================
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 12;
$offset  = ($page - 1) * $perPage;

$total      = 0;
$totalPages = 1;
$produk     = [];

try {
    // hitung total
    $countSql = "
        SELECT COUNT(DISTINCT p.id) AS total
        FROM products p
        JOIN shops s ON s.id = p.shop_id
        JOIN users u ON u.id = s.user_id
        LEFT JOIN product_categories pc ON pc.product_id = p.id
        LEFT JOIN categories c ON c.id = pc.category_id
        WHERE {$whereSql}
    ";

    $stmtCnt = $pdo->prepare($countSql);
    $stmtCnt->execute($params);
    $total = (int)$stmtCnt->fetchColumn();
    $totalPages = max(1, (int)ceil($total / $perPage));

    $perPage = (int)$perPage;
    $offset  = (int)$offset;

    // ambil id produk
    $idSql = "
        SELECT DISTINCT p.id
        FROM products p
        JOIN shops s ON s.id = p.shop_id
        JOIN users u ON u.id = s.user_id
        LEFT JOIN product_categories pc ON pc.product_id = p.id
        LEFT JOIN categories c ON c.id = pc.category_id
        WHERE {$whereSql}
        ORDER BY p.created_at DESC
        LIMIT {$perPage} OFFSET {$offset}
    ";
    $stmtIds = $pdo->prepare($idSql);
    $stmtIds->execute($params);
    $idRows = $stmtIds->fetchAll(PDO::FETCH_COLUMN, 0);

    if (!empty($idRows)) {
        $inPlaceholders = implode(',', array_fill(0, count($idRows), '?'));
        $detailSql = "
            SELECT 
                p.id,
                p.slug,
                p.title,
                p.price,
                p.compare_price,
                p.main_image,
                s.name AS shop_name,
                p.created_at
            FROM products p
            JOIN shops s ON s.id = p.shop_id
            WHERE p.id IN ($inPlaceholders)
            ORDER BY FIELD(p.id, $inPlaceholders)
        ";
        $stmtDet = $pdo->prepare($detailSql);
        $bindVals = array_merge($idRows, $idRows);
        foreach ($bindVals as $i => $val) {
            $stmtDet->bindValue($i + 1, (int)$val, PDO::PARAM_INT);
        }
        $stmtDet->execute();
        $produk = $stmtDet->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Throwable $e) {
    $produk = [];
}
?>

<div class="page-wrap">
  <div class="content">

    <section class="banner">
      <?php if ($homeBannerPath): ?>
        <img src="<?= e($homeBannerPath) ?>" alt="NearBuy" class="banner-img" loading="lazy">
      <?php endif; ?>
    </section>

    <section class="search">
      <form method="get" action="<?= e($BASE) ?>/index.php">
        <input
          type="text"
          name="q"
          value="<?= e($searchQ) ?>"
          maxlength="80"
          placeholder="Cari kebutuhan harian di sekitarmu">
        <button type="submit">
          <i class="fa fa-search"></i>
        </button>
      </form>
    </section>
    

    <section class="produk-section">
      <h2>Rekomendasi di Sekitarmu</h2>
      <?php if ($userLat === null || $userLng === null): ?>
        <p style="font-size:0.9rem;color:#64748b;margin-bottom:10px;">
          Aktifkan lokasi di halaman Set Lokasi agar rekomendasi benar benar sesuai titik kamu.
        </p>
      <?php endif; ?>

      <div class="produk-grid">
        <?php if (!empty($rekom)): ?>
          <?php foreach ($rekom as $r):
            $img = upload_image_url($r['main_image'] ?? null, $BASE);
            $hasPromo = (!is_null($r['compare_price']) && (float)$r['compare_price'] > (float)$r['price']);
            $detailUrl = $BASE . '/detail_produk.php?slug=' . urlencode($r['slug']);
          ?>
            <a class="produk-card" href="<?= e($detailUrl) ?>">
              <div class="img-wrap">
                <img src="<?= e($img) ?>" alt="<?= e($r['title']) ?>" loading="lazy">
                <?php if ($hasPromo): ?>
                  <span class="badge-discount">Promo</span>
                <?php endif; ?>
              </div>
              <h3 class="judul"><?= e($r['title']) ?></h3>
              <p class="subtext">Toko: <?= e($r['shop_name'] ?? 'NearBuy Seller') ?></p>
              <p class="harga">
                <?php if ($hasPromo): ?>
                  <del>Rp<?= number_format((float)$r['compare_price'], 0, ',', '.') ?></del><br>
                <?php endif; ?>
                <b>Rp<?= number_format((float)$r['price'], 0, ',', '.') ?></b>
              </p>
            </a>
          <?php endforeach; ?>
        <?php else: ?>
          <div style="color:#64748b;">Belum ada produk yang bisa direkomendasikan.</div>
        <?php endif; ?>
      </div>
    </section>

    <section class="produk-section">
      <h2>
        Semua Kebutuhan Harian
        <?php if (!empty($searchQ)): ?>
          untuk “<?= e($searchQ) ?>”
        <?php endif; ?>
      </h2>

      <div class="produk-grid">
        <?php if (!empty($produk)): ?>
          <?php foreach ($produk as $p):
            $img = upload_image_url($p['main_image'] ?? null, $BASE);
            $hasPromo = (!is_null($p['compare_price']) && (float)$p['compare_price'] > (float)$p['price']);
            $detailUrl = $BASE . '/detail_produk.php?slug=' . urlencode($p['slug']);
          ?>
            <a class="produk-card" href="<?= e($detailUrl) ?>">
              <div class="img-wrap">
                <img src="<?= e($img) ?>" alt="<?= e($p['title']) ?>" loading="lazy">
                <?php if ($hasPromo): ?>
                  <span class="badge-discount">Promo</span>
                <?php endif; ?>
              </div>
              <h3 class="judul"><?= e($p['title']) ?></h3>
              <p class="subtext">Toko: <?= e($p['shop_name'] ?? 'NearBuy Seller') ?></p>
              <p class="harga">
                <?php if ($hasPromo): ?>
                  <del>Rp<?= number_format((float)$p['compare_price'], 0, ',', '.') ?></del><br>
                <?php endif; ?>
                <b>Rp<?= number_format((float)$p['price'], 0, ',', '.') ?></b>
              </p>
            </a>
          <?php endforeach; ?>
        <?php else: ?>
          <div style="color:#64748b;">Produk tidak ditemukan.</div>
        <?php endif; ?>
      </div>

      <?php if ($totalPages > 1): ?>
        <nav class="pagination" style="margin-top:12px; display:flex; gap:10px; align-items:center;">
          <?php
            $qs = [];
            if (!empty($searchQ)) {
              $qs['q'] = $searchQ;
            }
          ?>
          <?php if ($page > 1): $qs['page'] = $page - 1; ?>
            <a
              href="<?= e($BASE) ?>/index.php?<?= http_build_query($qs) ?>"
              style="padding:8px 12px; border:1px solid #e5e7eb; border-radius:8px; text-decoration:none;">
              « Prev
            </a>
          <?php endif; ?>
          <span>Halaman <?= (int)$page ?> dari <?= (int)$totalPages ?></span>
          <?php if ($page < $totalPages): $qs['page'] = $page + 1; ?>
            <a
              href="<?= e($BASE) ?>/index.php?<?= http_build_query($qs) ?>"
              style="padding:8px 12px; border:1px solid #e5e7eb; border-radius:8px; text-decoration:none;">
              Next »
            </a>
          <?php endif; ?>
        </nav>
      <?php endif; ?>
    </section>

  </div>

  <aside class="sidebar">
    <div class="card" style="padding:12px; margin-bottom: 20px;">
      <h4>Kategori Produk</h4>
      <?php if (!empty($categories)): ?>
        <ul style="list-style: none; padding: 0; margin: 8px 0 0 0;">
          <li style="margin-bottom: 4px;">
            <a 
              href="<?= e($BASE) ?>/index.php" 
              style="text-decoration: none; color: #1f2937; display: block; padding: 4px 0; font-weight: bold;">
              Semua Produk
            </a>
          </li>

          <?php foreach ($categories as $cat): ?>
            <li style="margin-bottom: 4px;">
              <a 
                href="<?= e($BASE) ?>/kategori.php?slug=<?= urlencode($cat['slug']) ?>" 
                style="text-decoration: none; color: #4b5563; display: block; padding: 4px 0;">
                <?= e($cat['name']) ?>
              </a>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php else: ?>
        <p style="font-size:0.85rem;color:#6b7280;">Belum ada kategori yang ditambahkan.</p>
      <?php endif; ?>
    </div>
    <div class="card" style="padding:12px;">
      <h4>NearBuy</h4>
      <p style="font-size:0.85rem;color:#6b7280;">
        Temukan kebutuhan harian dari penjual terdekat di sekitarmu.
        Semua produk dijual oleh seller lokal. Admin hanya mengelola platform.
      </p>
    </div>
  </aside>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
</main>
</body>
</html>