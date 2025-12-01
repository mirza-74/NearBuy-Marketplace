<?php
// ===============================================================
// SellExa – Index (Home)  (compatible with MariaDB 10.4 – no ANY_VALUE)
// ===============================================================
declare(strict_types=1);

$BASE = '/Marketplace_SellExa/public';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/settings.php';
require_once __DIR__ . '/../includes/header.php';

$user    = $_SESSION['user'] ?? null;
$role    = $user['role'] ?? 'guest';
$isGuest = ($role === 'guest');
$userId  = (int)($user['id'] ?? 0);
$userPoints = (int)($user['points'] ?? 0);

// daftar produk yang sudah ada di wishlist user (untuk ikon hati aktif)
$wishlistProductIds = [];
if (!$isGuest && $userId > 0) {
    $stmtWish = $pdo->prepare("SELECT product_id FROM wishlists WHERE user_id = ?");
    $stmtWish->execute([$userId]);
    $wishlistProductIds = array_map('intval', $stmtWish->fetchAll(PDO::FETCH_COLUMN, 0));
}

// ambil flash dari session (klaim voucher, dll)
$flash = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);

// flash khusus wishlist
$flashWishlist = $_SESSION['flash_wishlist'] ?? '';
unset($_SESSION['flash_wishlist']);

/* ------------------- Helper Gambar & URL ------------------- */
if (!function_exists('is_https_url')) {
  function is_https_url(string $p): bool { return (bool)preg_match('~^https?://~i', $p); }
}
if (!function_exists('join_url')) {
  function join_url(...$segments): string {
    $s = implode('/', array_map(fn($x) => trim((string)$x, '/'), $segments));
    return '/'.$s;
  }
}
if (!function_exists('upload_image_url')) {
  function upload_image_url(?string $path, string $BASE): string {
    if (!$path) return 'https://via.placeholder.com/560x320?text=No+Image';
    if (is_https_url($path)) return $path;
    $p = ltrim($path, '/');
    if (str_starts_with($p, 'products/'))      return join_url($BASE, 'uploads', $p);
    if (str_starts_with($p, 'uploads/'))       return join_url($BASE, $p);
    if (str_starts_with($p, 'public/uploads/'))return join_url($BASE, substr($p, 7));
    return join_url($BASE, 'uploads', $p);
  }
}

/* ------------------- DATA UMUM ------------------- */

/* Banner utama dari site_settings */
$homeBannerPath = (function() use ($pdo, $BASE) {
    $stmt = $pdo->prepare("
        SELECT value 
        FROM site_settings 
        WHERE `key` = 'home_banner' 
        LIMIT 1
    ");
    $stmt->execute();
    $v = $stmt->fetchColumn();
    // fallback kalau belum diisi
    return $v ?: ($BASE . '/assets/poster-sellexa.jpg');
})();

/* Banner iklan untuk sidebar dari tabel banners */
$adBanners = $pdo->query("
    SELECT id, image_path, link_url
    FROM banners
    WHERE is_active = 1
    ORDER BY id DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);


/* Kategori aktif */
$cats = $pdo->query("
  SELECT id, name, slug
  FROM categories 
  WHERE is_active=1 
  ORDER BY sort_order, name
")->fetchAll(PDO::FETCH_ASSOC);

/* Promo Hari Ini */
$promo = $pdo->query("
  SELECT id, slug, title, price, compare_price, main_image
  FROM products
  WHERE is_active=1
    AND stock > 0
    AND compare_price IS NOT NULL
    AND compare_price > price
  ORDER BY COALESCE(updated_at, created_at) DESC
  LIMIT 6
")->fetchAll(PDO::FETCH_ASSOC);

/* ------------------- Rekomendasi (view v_user_recommendations) ------------------- */
$rekom = [];
if ($role === 'pengguna' && $userId > 0) {
  $sqlRekom = "
    SELECT product_id AS id, slug, title, price, compare_price, main_image, created_at, popularity
    FROM v_user_recommendations
    WHERE user_id = ?
    ORDER BY popularity DESC, created_at DESC
    LIMIT 6
  ";
  $stmt = $pdo->prepare($sqlRekom);
  $stmt->execute([$userId]);
  $rekom = $stmt->fetchAll(PDO::FETCH_ASSOC);

  if (empty($rekom)) {
    $stmt = $pdo->query("
      SELECT id, slug, title, price, compare_price, main_image
      FROM products
      WHERE is_active=1 AND stock>0
      ORDER BY popularity DESC, created_at DESC
      LIMIT 6
    ");
    $rekom = $stmt->fetchAll(PDO::FETCH_ASSOC);
  }
}

/* ------------------- FILTER KATEGORI & SEARCH (Produk Tersedia) ------------------- */
$activeCatSlug = isset($_GET['k']) ? trim((string)$_GET['k']) : '';
$searchQRaw    = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
$searchQ       = mb_substr($searchQRaw, 0, 80); // batasin panjang

$where   = ["p.is_active = 1", "p.stock > 0"];
$params  = [];

/* Filter kategori (join hanya jika ada k selain 'semua') */
$joinCat = '';
if ($activeCatSlug !== '' && strtolower($activeCatSlug) !== 'semua') {
  $joinCat = " JOIN product_categories pc ON pc.product_id = p.id
               JOIN categories c ON c.id = pc.category_id ";
  $where[] = "c.slug = ?";
  $params[] = $activeCatSlug;
}

/* Filter search */
if ($searchQ !== '') {
  $where[] = "(p.title LIKE ? OR p.description LIKE ?)";
  $params[] = "%{$searchQ}%";
  $params[] = "%{$searchQ}%";
}

/* ------------------- Pagination (2-langkah, tanpa ANY_VALUE) ------------------- */
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 12;
$offset  = ($page - 1) * $perPage;

/* Hitung total produk unik */
$countSql = "
  SELECT COUNT(DISTINCT p.id) AS total
  FROM products p
  {$joinCat}
  WHERE ".implode(' AND ', $where);
$stmtCnt = $pdo->prepare($countSql);
$stmtCnt->execute($params);
$total = (int)$stmtCnt->fetchColumn();
$totalPages = max(1, (int)ceil($total / $perPage));

$perPage  = (int)$perPage;
$offset   = (int)$offset;

$idSql = "
  SELECT DISTINCT p.id
  FROM products p
  {$joinCat}
  WHERE ".implode(' AND ', $where)."
  ORDER BY p.created_at DESC
  LIMIT {$perPage} OFFSET {$offset}
";

$stmtIds = $pdo->prepare($idSql);
$stmtIds->execute($params);   // cuma kirim parameter untuk filter (kategori & search)
$idRows = $stmtIds->fetchAll(PDO::FETCH_COLUMN, 0);

/* Langkah 2: ambil detail produk + hitung variant_count, dan jaga urutan */
$produk = [];
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
      (SELECT COUNT(1) FROM product_variants v 
        WHERE v.product_id = p.id AND v.is_active=1) AS variant_count,
      p.created_at
    FROM products p
    WHERE p.id IN ($inPlaceholders)
    ORDER BY FIELD(p.id, $inPlaceholders)
  ";
  $stmtDet = $pdo->prepare($detailSql);
  $bindVals = array_merge($idRows, $idRows);
  foreach ($bindVals as $i => $val) {
    $stmtDet->bindValue($i+1, (int)$val, PDO::PARAM_INT);
  }
  $stmtDet->execute();
  $produk = $stmtDet->fetchAll(PDO::FETCH_ASSOC);
}

/* ------------------- Voucher untuk sidebar (pakai tabel vouchers & voucher_claims) ------------------- */
$vouchersSidebar = [];
if ($userId > 0) {
  $stmtV = $pdo->prepare("
    SELECT 
      v.*,
      vc.id AS claimed_id
    FROM vouchers v
    LEFT JOIN voucher_claims vc
      ON vc.voucher_id = v.id AND vc.user_id = ?
    WHERE v.is_active = 1
    ORDER BY v.min_points ASC, v.created_at DESC
  ");
  $stmtV->execute([$userId]);
  $vouchersSidebar = $stmtV->fetchAll(PDO::FETCH_ASSOC);
}
?>

<link rel="stylesheet" href="<?= e($BASE) ?>/style.css">

<div class="page-wrap">
  <div class="content">

    <!-- Banner Section -->
    <section class="banner">
        <?php if ($homeBannerPath): ?>
            <img src="<?= e($homeBannerPath) ?>" alt="Banner Utama" class="banner-img" loading="lazy">
        <?php endif; ?>
    </section>

    <!-- Kategori -->
    <section class="kategori">
      <h2>Kategori</h2>
      <div class="kategori-container">
        <a class="kategori-btn <?= ($activeCatSlug === '' || strtolower($activeCatSlug) === 'semua') ? 'active' : '' ?>"
           href="<?= e($BASE) ?>/index.php">Semua</a>

        <?php foreach ($cats as $c): ?>
          <?php if (strtolower($c['slug']) === 'semua') continue; ?>
          <a class="kategori-btn <?= ($activeCatSlug === $c['slug']) ? 'active' : '' ?>"
             href="<?= e($BASE) ?>/index.php?k=<?= e($c['slug']) ?>">
            <?= e($c['name']) ?>
          </a>
        <?php endforeach; ?>
      </div>
    </section>

    <!-- Pencarian -->
    <section class="search">
      <form method="get" action="<?= e($BASE) ?>/index.php">
        <?php if ($activeCatSlug): ?>
          <input type="hidden" name="k" value="<?= e($activeCatSlug) ?>">
        <?php endif; ?>
        <input type="text" name="q" value="<?= e($searchQ) ?>" maxlength="80" placeholder="Apa yang sedang kamu cari?" required>
        <button type="submit">Cari</button>
      </form>
    </section>

    <!-- Flash dari session (klaim voucher, dll) -->
    <?php if ($flash !== ''): ?>
      <div class="flash-info">
        <?= e($flash) ?>
      </div>
    <?php endif; ?>

    <!-- Flash wishlist -->
    <?php if ($flashWishlist !== ''): ?>
      <div class="flash-wishlist">
        <?= e($flashWishlist) ?>
        <a href="<?= e($BASE) ?>/wishlist.php" class="flash-link">Cek Wishlist</a>
      </div>
    <?php endif; ?>

    <!-- Flash dari query string lama (optional) -->
    <?php if (!empty($_GET['msg'])): ?>
      <div style="margin:10px 0; padding:10px 12px; border:1px solid #bbf7d0; background:#ecfdf5; color:#065f46; border-radius:10px;">
        <?= e($_GET['msg']) ?>
      </div>
    <?php endif; ?>
    <?php if (!empty($_GET['error'])): ?>
      <div style="margin:10px 0; padding:10px 12px; border:1px solid #fecaca; background:#fff1f2; color:#991b1b; border-radius:10px;">
        <?= e($_GET['error']) ?>
      </div>
    <?php endif; ?>

    <!-- Promo Hari Ini -->
    <?php if (!empty($promo)): ?>
    <section class="produk-section">
      <h2>Promo Hari Ini</h2>
      <div class="produk-grid">
        <?php foreach ($promo as $p): 
          $img = upload_image_url($p['main_image'] ?? null, $BASE);
          $hasPromo = (!is_null($p['compare_price']) && (float)$p['compare_price'] > (float)$p['price']);
          $detailUrl = $BASE.'/detail_produk.php?slug='.urlencode($p['slug']);
        ?>
          <a class="produk-card" href="<?= e($detailUrl) ?>">
            <div class="img-wrap">
              <img src="<?= e($img) ?>" alt="<?= e($p['title']) ?>" loading="lazy">
              <?php if ($hasPromo): ?>
                <span class="badge-discount">Promo</span>
              <?php endif; ?>
            </div>
            <h3 class="judul"><?= e($p['title']) ?></h3>
            <p class="harga">
              <?php if ($hasPromo): ?>
                <del>Rp<?= number_format((float)$p['compare_price'], 0, ',', '.') ?></del><br>
              <?php endif; ?>
              <b>Rp<?= number_format((float)$p['price'], 0, ',', '.') ?></b>
            </p>
          </a>
        <?php endforeach; ?>
      </div>
    </section>
    <?php endif; ?>

    <!-- Rekomendasi -->
    <?php if ($role === 'pengguna' && !empty($rekom)): ?>
    <section class="produk-section">
      <h2>Rekomendasi Untukmu</h2>
      <div class="produk-grid">
        <?php foreach ($rekom as $r):
          $img = upload_image_url($r['main_image'] ?? null, $BASE);
          $hasPromo = (!is_null($r['compare_price']) && (float)$r['compare_price'] > (float)$r['price']);
          $detailUrl = $BASE.'/detail_produk.php?slug='.urlencode($r['slug']);
        ?>
          <a class="produk-card" href="<?= e($detailUrl) ?>">
            <div class="img-wrap">
              <img src="<?= e($img) ?>" alt="<?= e($r['title']) ?>" loading="lazy">
              <?php if ($hasPromo): ?><span class="badge-discount">Promo</span><?php endif; ?>
            </div>
            <h3 class="judul"><?= e($r['title']) ?></h3>
            <p class="harga">
              <?php if ($hasPromo): ?><del>Rp<?= number_format((float)$r['compare_price'], 0, ',', '.') ?></del><br><?php endif; ?>
              <b>Rp<?= number_format((float)$r['price'], 0, ',', '.') ?></b>
            </p>
          </a>
        <?php endforeach; ?>
      </div>
    </section>
    <?php endif; ?>

    <!-- Produk terbaru -->
    <section class="produk-section">
      <h2>
        Produk Tersedia
        <?php if (!empty($activeCatSlug) && strtolower($activeCatSlug) !== 'semua'): ?> — <?= e($activeCatSlug) ?><?php endif; ?>
        <?php if (!empty($searchQ)): ?> (cari: “<?= e($searchQ) ?>”)<?php endif; ?>
      </h2>
      <div class="produk-grid">
        <?php if (!empty($produk)): ?>
          <?php foreach ($produk as $p):
              $img = upload_image_url($p['main_image'] ?? null, $BASE);
              $hasPromo  = (!is_null($p['compare_price']) && (float)$p['compare_price'] > (float)$p['price']);
              $detailUrl = $BASE.'/detail_produk.php?slug='.urlencode($p['slug']);

              // apakah produk ini sudah ada di wishlist user?
              $isWish = in_array((int)$p['id'], $wishlistProductIds, true);
              // url untuk balik lagi ke halaman ini setelah toggle
              $backUrl = $_SERVER['REQUEST_URI'] ?? ($BASE . '/index.php');
          ?>
            <div class="produk-card">

              <!-- FORM WISHLIST (ikon hati di pojok gambar) -->
              <form method="post"
                    action="<?= e($BASE) ?>/wishlist_toggle.php"
                    class="wish-toggle-form">
                <!-- CSRF TOKEN WAJIB NAMA-NYA "csrf" -->
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                
                <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
                <input type="hidden" name="back" value="<?= e($backUrl) ?>">
                <button type="submit"
                        class="btn-wish <?= $isWish ? 'active' : '' ?>"
                        aria-label="Tambah ke wishlist">
                  <?= $isWish ? '♥' : '♡' ?>
                </button>
              </form>

              <!-- LINK DETAIL + INFO PRODUK -->
              <a href="<?= e($detailUrl) ?>" class="produk-link">
                <div class="img-wrap">
                  <img src="<?= e($img) ?>" alt="<?= e($p['title']) ?>" loading="lazy">
                  <?php if ($hasPromo): ?><span class="badge-discount">Promo</span><?php endif; ?>
                </div>
                <h3 class="judul"><?= e($p['title']) ?></h3>
                <p class="harga">
                  <?php if ($hasPromo): ?>
                    <del>Rp<?= number_format((float)$p['compare_price'], 0, ',', '.') ?></del><br>
                  <?php endif; ?>
                  <b>Rp<?= number_format((float)$p['price'], 0, ',', '.') ?></b>
                </p>
              </a>

              <?php if ($isGuest): ?>
                <a class="btn-disabled"
                  href="<?= e($BASE) ?>/login.php"
                  aria-label="Login untuk menambahkan ke keranjang">
                  Login untuk menambahkan
                </a>
              <?php else: ?>
                <!-- Tambah ke keranjang -->
                <form method="post"
                      action="<?= e($BASE) ?>/add_to_cart.php"
                      class="add-form"
                      style="display:inline-block;">
                  <?php if (function_exists('csrf_input')) { csrf_input(); } ?>
                  <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
                  <input type="hidden" name="qty" value="1">
                  <button type="submit"
                          class="btn-add"
                          aria-label="Tambah <?= e($p['title']) ?> ke keranjang">
                    + Keranjang
                  </button>
                </form>

                <!-- Pesan langsung -->
                <form method="post"
                      action="<?= e($BASE) ?>/buy_now.php"
                      class="buy-form"
                      style="display:inline-block;">
                  <?php if (function_exists('csrf_input')) { csrf_input(); } ?>
                  <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
                  <input type="hidden" name="qty" value="1">
                  <button type="submit"
                          class="btn-buy"
                          aria-label="Pesan sekarang <?= e($p['title']) ?>">
                    Pesan
                  </button>
                </form>
              <?php endif; ?>

            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div style="color:#64748b;">Produk tidak ditemukan untuk filter ini.</div>
        <?php endif; ?>
      </div>

      <!-- Pagination -->
      <?php if ($totalPages > 1): ?>
      <nav class="pagination" style="margin-top:12px; display:flex; gap:10px; align-items:center;">
        <?php
          $qs = [];
          if (!empty($activeCatSlug)) $qs['k'] = $activeCatSlug;
          if (!empty($searchQ))      $qs['q'] = $searchQ;
        ?>
        <?php if ($page > 1): $qs['page'] = $page - 1; ?>
          <a href="<?= e($BASE) ?>/index.php?<?= http_build_query($qs) ?>" style="padding:8px 12px; border:1px solid #e5e7eb; border-radius:8px; text-decoration:none;">« Prev</a>
        <?php endif; ?>
        <span>Halaman <?= (int)$page ?> / <?= (int)$totalPages ?></span>
        <?php if ($page < $totalPages): $qs['page'] = $page + 1; ?>
          <a href="<?= e($BASE) ?>/index.php?<?= http_build_query($qs) ?>" style="padding:8px 12px; border:1px solid #e5e7eb; border-radius:8px; text-decoration:none;">Next »</a>
        <?php endif; ?>
      </nav>
      <?php endif; ?>
    </section>

  </div>

  <!-- Sidebar -->
  <aside class="sidebar">
    <div class="banner-side card">
      <?php if (empty($adBanners)): ?>
          <img src="<?= e($BASE) ?>/assets/banner-side.jpg" alt="Iklan" class="side-img" loading="lazy">
      <?php else: ?>
          <?php foreach ($adBanners as $b): ?>
              <?php
                  // image_path di tabel banners sudah berisi URL lengkap
                  $bimg  = $b['image_path'] ?? '';
                  $blink = $b['link_url'] ?: '#';
              ?>
              <?php if ($bimg !== ''): ?>
                  <a href="<?= e($blink) ?>" style="display:block;margin-bottom:8px;">
                      <img src="<?= e($bimg) ?>" alt="Iklan" class="side-img" loading="lazy">
                  </a>
              <?php endif; ?>
          <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <div class="voucher-side card">
      <h4>Voucher</h4>

      <?php if ($isGuest): ?>
        <p>Login untuk klaim voucher.</p>
        <a class="btn-claim" href="<?= e($BASE) ?>/login.php">Masuk</a>
      <?php else: ?>
        <p class="voucher-poin">Poin kamu: <b><?= (int)$userPoints ?></b></p>

        <?php if (empty($vouchersSidebar)): ?>
          <p style="font-size:0.85rem;color:#6b7280;">Belum ada voucher yang tersedia.</p>
        <?php else: ?>
          <?php foreach ($vouchersSidebar as $v): ?>
            <?php
              $claimed   = !empty($v['claimed_id']);
              $needPoint = (int)$v['min_points'];
              $canClaim  = !$claimed && $userPoints >= $needPoint;

              // label diskon
              $diskonLabel = '';
              if ($v['discount_type'] === 'percent') {
                  $diskonLabel = 'Diskon ' . (int)$v['discount_value'] . '%';
              } elseif ($v['discount_type'] === 'nominal') {
                  $diskonLabel = 'Potongan Rp' . number_format((int)$v['discount_value'], 0, ',', '.');
              } else {
                  $diskonLabel = 'Gratis Ongkir';
              }

              $minTrxText = ((int)$v['min_transaction'] > 0)
                  ? 'Min. belanja Rp' . number_format((int)$v['min_transaction'], 0, ',', '.')
                  : 'Tanpa minimum belanja';
            ?>
            <div class="voucher-card">
              <div class="voucher-header">
                <div class="voucher-title"><?= e($diskonLabel) ?></div>
                <div class="voucher-points"><?= (int)$v['min_points'] ?> pts</div>
              </div>
              <div class="voucher-body">
                <div class="voucher-name"><?= e($v['title']) ?></div>
                <div class="voucher-desc">
                  <?= e($minTrxText) ?> • Kode: <b><?= e($v['code']) ?></b>
                </div>
              </div>
              <div class="voucher-footer">
                <?php if ($claimed): ?>
                  <button class="btn-voucher disabled" disabled>Sudah diklaim</button>
                <?php elseif (!$canClaim): ?>
                  <button class="btn-voucher disabled" disabled>Poin belum cukup</button>
                <?php else: ?>
                  <form method="post" action="<?= e($BASE) ?>/claim_voucher.php">
                    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="voucher_id" value="<?= (int)$v['id'] ?>">
                    <button type="submit" class="btn-voucher">Tambahkan Poin</button>
                  </form>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </aside>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
</main>
</body>
</html>
