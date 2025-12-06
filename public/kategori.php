<?php
// ===============================================================
// NearBuy – Daftar Produk per Kategori (kategori.php)
// Pastikan file ini ada di folder /public
// ===============================================================
declare(strict_types=1);

// Pastikan Anda memiliki file-file ini di folder ../includes/
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/settings.php';
require_once __DIR__ . '/../includes/header.php';

// BASE: otomatis ambil folder /public yang sedang dipakai
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$BASE = rtrim($scriptDir, '/');

// Data user dan lokasi
$user    = $_SESSION['user'] ?? null;
$role    = $user['role'] ?? 'guest';
$userId  = (int)($user['id'] ?? 0);
$isGuest = ($role === 'guest' || $userId <= 0);
$userLat = isset($_SESSION['user_lat']) ? (float)$_SESSION['user_lat'] : null;
$userLng = isset($_SESSION['user_lng']) ? (float)$_SESSION['user_lng'] : null;

// ===================== HELPER Gambar dan URL =====================
// Pastikan fungsi helper ini ada di sini atau di file include.
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
// =======================================================================================


// 1. Ambil Slug Kategori dari URL
$categorySlug = isset($_GET['slug']) ? trim((string)$_GET['slug']) : '';

// 2. Ambil detail Kategori
$category = null;
$categoryName = "Kategori Tidak Ditemukan";
$categoryId = 0;

if ($categorySlug !== '') {
    try {
        $stmtCat = $pdo->prepare("
            SELECT id, name 
            FROM categories 
            WHERE slug = ? 
            LIMIT 1
        ");
        $stmtCat->execute([$categorySlug]);
        $category = $stmtCat->fetch(PDO::FETCH_ASSOC);

        if ($category) {
            $categoryName = $category['name'];
            $categoryId = (int)$category['id'];
        }
    } catch (Throwable $e) {
        // Error DB
    }
}


// 3. Persiapan Filter Produk
$where  = ["p.is_active = 1", "p.stock > 0", "u.role = 'seller'"];
$params = [];

// Tambahkan filter KATEGORI
if ($categoryId > 0) {
    // JOIN ke product_categories untuk memfilter
    $where[] = "pc.category_id = ?";
    $params[] = $categoryId;
} else {
    // Kategori tidak valid, tidak perlu query produk
    $totalPages = 1;
    $produk = [];
    $total = 0;
    $categoryName = "Kategori Tidak Ditemukan";
    goto render_page;
}


// 4. Proses Pagination dan Ambil Produk
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 12;
$offset  = ($page - 1) * $perPage;

$total      = 0;
$totalPages = 1;
$produk     = [];

try {
    // Hitung total produk
    $countSql = "
        SELECT COUNT(DISTINCT p.id) AS total
        FROM products p
        JOIN shops s ON s.id = p.shop_id
        JOIN users u ON u.id = s.user_id
        JOIN product_categories pc ON pc.product_id = p.id
        WHERE " . implode(' AND ', $where);

    $stmtCnt = $pdo->prepare($countSql);
    $stmtCnt->execute($params);
    $total = (int)$stmtCnt->fetchColumn();
    $totalPages = max(1, (int)ceil($total / $perPage));

    // Ambil ID produk
    $idSql = "
        SELECT DISTINCT p.id
        FROM products p
        JOIN shops s ON s.id = p.shop_id
        JOIN users u ON u.id = s.user_id
        JOIN product_categories pc ON pc.product_id = p.id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY p.created_at DESC
        LIMIT {$perPage} OFFSET {$offset}
    ";
    $stmtIds = $pdo->prepare($idSql);
    $stmtIds->execute($params);
    $idRows = $stmtIds->fetchAll(PDO::FETCH_COLUMN, 0);

    // Ambil detail produk
    if (!empty($idRows)) {
        $inPlaceholders = implode(',', array_fill(0, count($idRows), '?'));
        $detailSql = "
            SELECT 
                p.id, p.slug, p.title, p.price, p.compare_price, p.main_image,
                s.name AS shop_name, p.created_at
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

// ===================== AMBIL SEMUA KATEGORI UNTUK SIDEBAR =====================
$categories = [];
try {
    $stmtAllCat = $pdo->prepare("
        SELECT id, name, slug 
        FROM categories 
        ORDER BY name ASC
    ");
    $stmtAllCat->execute();
    $categories = $stmtAllCat->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $categories = [];
}

render_page:
?>

<div class="page-wrap">
  <div class="content">

    <section class="produk-section">
      <h1>Produk Kategori: <?= e($categoryName) ?></h1>
      
      <?php if ($categoryId === 0): ?>
        <p style="color:#ef4444; font-size:1.1rem; font-weight:bold;">
            ❌ Kategori yang Anda cari tidak ditemukan.
        </p>
      <?php endif; ?>

      <div class="produk-grid" style="margin-top: 15px;">
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
          <div style="color:#64748b;">Tidak ada produk yang tersedia di kategori **<?= e($categoryName) ?>**.</div>
        <?php endif; ?>
      </div>

      <?php if ($totalPages > 1): ?>
        <nav class="pagination" style="margin-top:12px; display:flex; gap:10px; align-items:center;">
          <?php $qs = ['slug' => $categorySlug]; ?>
          <?php if ($page > 1): $qs['page'] = $page - 1; ?>
            <a
              href="<?= e($BASE) ?>/kategori.php?<?= http_build_query($qs) ?>"
              style="padding:8px 12px; border:1px solid #e5e7eb; border-radius:8px; text-decoration:none;">
              « Prev
            </a>
          <?php endif; ?>
          <span>Halaman <?= (int)$page ?> dari <?= (int)$totalPages ?></span>
          <?php if ($page < $totalPages): $qs['page'] = $page + 1; ?>
            <a
              href="<?= e($BASE) ?>/kategori.php?<?= http_build_query($qs) ?>"
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
              style="text-decoration: none; color: <?= $categoryId === 0 ? '#0d9488' : '#1f2937' ?>; display: block; padding: 4px 0; font-weight: bold;">
              Semua Produk
            </a>
          </li>
          <?php foreach ($categories as $cat): 
             $isActive = ($categorySlug === $cat['slug']);
          ?>
            <li style="margin-bottom: 4px;">
              <a 
                href="<?= e($BASE) ?>/kategori.php?slug=<?= urlencode($cat['slug']) ?>" 
                style="text-decoration: none; display: block; padding: 4px 0; font-weight: <?= $isActive ? 'bold' : 'normal' ?>; color: <?= $isActive ? '#0d9488' : '#4b5563' ?>;">
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
      </p>
    </div>
  </aside>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
</main>
</body>
</html>