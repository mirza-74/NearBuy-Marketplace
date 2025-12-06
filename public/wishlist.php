<?php
// ===============================================================
//  – Wishlist Sederhana (list produk yang disimpan user)
// ===============================================================
declare(strict_types=1);

$BASE = '/NearBuy-Marketplace/public';

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/settings.php';

// --- CEK USER LOGIN (tanpa require_user) ---
$user = $_SESSION['user'] ?? null;
if (!$user || empty($user['id'])) {
    $_SESSION['flash'] = 'Silakan login untuk melihat wishlist.';
    header('Location: ' . $BASE . '/login.php');
    exit;
}

$userId      = (int)$user['id'];
$userPoints  = (int)($user['points'] ?? 0);

// ambil flash
$flash = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);

// helper
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

// ambil wishlist user
$sqlWish = "
    SELECT 
      w.id         AS wishlist_id,
      w.created_at AS wish_time,
      p.id         AS product_id,
      p.slug,
      p.title,
      p.price,
      p.compare_price,
      p.main_image,
      p.stock
    FROM wishlists w
    JOIN products p ON p.id = w.product_id
    WHERE w.user_id = ?
    ORDER BY w.created_at DESC
";
$stmt = $pdo->prepare($sqlWish);
$stmt->execute([$userId]);
$wishlistItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
$totalWish     = count($wishlistItems);

// opsional: rekomendasi di bawah wishlist
$rekom = [];
try {
    $sqlRekom = "
        SELECT product_id AS id, slug, title, price, compare_price, main_image, created_at, popularity
        FROM v_user_recommendations
        WHERE user_id = ?
        ORDER BY popularity DESC, created_at DESC
        LIMIT 8
    ";
    $st = $pdo->prepare($sqlRekom);
    $st->execute([$userId]);
    $rekom = $st->fetchAll(PDO::FETCH_ASSOC);

    if (empty($rekom)) {
        $st = $pdo->query("
            SELECT id, slug, title, price, compare_price, main_image
            FROM products
            WHERE is_active = 1 AND stock > 0
            ORDER BY popularity DESC, created_at DESC
            LIMIT 8
        ");
        $rekom = $st->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Throwable $e) {
    $rekom = [];
}

require_once __DIR__ . '/../includes/header.php';
?>
<link rel="stylesheet" href="<?= e($BASE) ?>/style-wishlist.css">

<div class="wishlist-page">

  <header class="wishlist-header">
    <h1>Wishlist</h1>
    <span class="wishlist-count"><?= (int)$totalWish ?> barang</span>
  </header>

  <?php if ($flash !== ''): ?>
    <div class="wishlist-flash"><?= e($flash) ?></div>
  <?php endif; ?>

  <!-- DAFTAR PRODUK WISHLIST -->
  <section class="wishlist-items">
    <?php if ($totalWish === 0): ?>
      <div class="wishlist-empty">
        <div class="empty-icon">♡</div>
        <h3>Belum ada barang di wishlist</h3>
        <p>Tambahkan produk favoritmu dengan menekan ikon hati di halaman produk.</p>
        <a href="<?= e($BASE) ?>/index.php" class="btn-primary">Mulai Belanja</a>
      </div>
    <?php else: ?>
      <div class="wishlist-grid">
        <?php foreach ($wishlistItems as $item):
          $img      = upload_image_url($item['main_image'] ?? null, $BASE);
          $detail   = $BASE . '/detail_produk.php?slug=' . urlencode($item['slug']);
          $hasPromo = (!is_null($item['compare_price']) && (float)$item['compare_price'] > (float)$item['price']);
        ?>
          <div class="wish-card">
            <a href="<?= e($detail) ?>" class="wish-img-wrap">
              <img src="<?= e($img) ?>" alt="<?= e($item['title']) ?>" loading="lazy">
              <?php if ($hasPromo): ?><span class="badge-promo">Promo</span><?php endif; ?>
            </a>

            <div class="wish-body">
              <h3 class="wish-title"><?= e($item['title']) ?></h3>
              <div class="wish-price">
                <?php if ($hasPromo): ?>
                  <span class="old">Rp<?= number_format((float)$item['compare_price'], 0, ',', '.') ?></span>
                <?php endif; ?>
                <span class="new">Rp<?= number_format((float)$item['price'], 0, ',', '.') ?></span>
              </div>
            </div>

            <div class="wish-actions">
              <!-- Tambah ke keranjang -->
              <form method="post" action="<?= e($BASE) ?>/add_to_cart.php" class="wish-form">
                <?php if (function_exists('csrf_input')) { csrf_input(); } ?>
                <input type="hidden" name="product_id" value="<?= (int)$item['product_id'] ?>">
                <input type="hidden" name="qty" value="1">
                <button type="submit" class="btn-cart">+ Keranjang</button>
              </form>

              <!-- Hapus dari wishlist -->
              <form method="post" action="<?= e($BASE) ?>/wishlist_toggle.php" class="wish-form">
                <?php if (function_exists('csrf_input')) { csrf_input(); } ?>
                <input type="hidden" name="product_id" value="<?= (int)$item['product_id'] ?>">
                <input type="hidden" name="back" value="<?= e($BASE) ?>/wishlist.php">
                <button type="submit" class="btn-remove">Hapus</button>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>

  <!-- Rekomendasi -->
  <?php if (!empty($rekom)): ?>
    <section class="wishlist-rekom">
      <h2>Rekomendasi Untuk Anda</h2>
      <div class="wishlist-grid">
        <?php foreach ($rekom as $p):
          $img      = upload_image_url($p['main_image'] ?? null, $BASE);
          $hasPromo = (!is_null($p['compare_price']) && (float)$p['compare_price'] > (float)$p['price']);
          $detail   = $BASE . '/detail_produk.php?slug=' . urlencode($p['slug']);
        ?>
          <div class="wish-card">
            <a href="<?= e($detail) ?>" class="wish-img-wrap">
              <img src="<?= e($img) ?>" alt="<?= e($p['title']) ?>" loading="lazy">
              <?php if ($hasPromo): ?><span class="badge-promo">Promo</span><?php endif; ?>
            </a>
            <div class="wish-body">
              <h3 class="wish-title"><?= e($p['title']) ?></h3>
              <div class="wish-price">
                <?php if ($hasPromo): ?>
                  <span class="old">Rp<?= number_format((float)$p['compare_price'], 0, ',', '.') ?></span>
                <?php endif; ?>
                <span class="new">Rp<?= number_format((float)$p['price'], 0, ',', '.') ?></span>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endif; ?>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
</main>
</body>
</html>
