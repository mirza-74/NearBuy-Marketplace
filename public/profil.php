<?php
// ===============================================================
// SellExa – Halaman Profil / Akun
// ===============================================================
declare(strict_types=1);

$BASE = '/Marketplace_SellExa/public';

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/settings.php';

// pastikan user login
$user = $_SESSION['user'] ?? null;
$role = $user['role'] ?? 'guest';
if (!$user || !in_array($role, ['pengguna','admin'], true)) {
    header('Location: '.$BASE.'/login.php?next='.urlencode($BASE.'/profil.php'));
    exit;
}
$userId = (int)$user['id'];

// CSS khusus profil
$EXTRA_CSS = ['style-profil.css'];

// header
require_once __DIR__ . '/../includes/header.php';

// ---------- Helper gambar ----------
if (!function_exists('upload_image_url')) {
    function upload_image_url(?string $path, string $BASE): string {
        if (!$path) return 'https://via.placeholder.com/200x200?text=No+Image';
        if (preg_match('~^https?://~i', $path)) return $path;
        $p = ltrim($path, '/');
        if (str_starts_with($p, 'products/')) {
            return rtrim($BASE, '/').'/uploads/'.$p;
        }
        if (str_starts_with($p, 'uploads/')) {
            return rtrim($BASE, '/').'/'.$p;
        }
        if (str_starts_with($p, 'public/uploads/')) {
            return rtrim($BASE, '/').'/'.substr($p, 7);
        }
        return rtrim($BASE, '/').'/uploads/'.$p;
    }
}

// ---------- Data ringkasan kecil ----------
$points = (int)($user['points'] ?? 0);

// jumlah voucher yang pernah diklaim user
$stmt = $pdo->prepare("SELECT COUNT(*) FROM voucher_claims WHERE user_id = :uid");
$stmt->execute([':uid' => $userId]);
$voucherCount = (int)$stmt->fetchColumn();

// jumlah item di keranjang aktif
$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(ci.qty),0) 
    FROM carts c 
    JOIN cart_items ci ON ci.cart_id = c.id
    WHERE c.user_id = :uid AND c.status = 'active'
");
$stmt->execute([':uid' => $userId]);
$cartQty = (int)$stmt->fetchColumn();

// ---------- Cek apakah user sudah punya toko ----------
$hasStore  = false;
$storeName = '';

try {
    // sesuaikan nama tabel/kolom kalau nanti kamu pakai nama lain
    $stmtStore = $pdo->prepare("
        SELECT id, name 
        FROM stores 
        WHERE user_id = :uid 
        LIMIT 1
    ");
    $stmtStore->execute([':uid' => $userId]);
    $rowStore = $stmtStore->fetch(PDO::FETCH_ASSOC);

    if ($rowStore) {
        $hasStore  = true;
        $storeName = (string)$rowStore['name'];
    }
} catch (Throwable $e) {
    // kalau tabel stores belum ada, jangan bikin error
    $hasStore  = false;
    $storeName = '';
}

// ---------- Rekomendasi produk ----------
$rekom = [];
$stmt = $pdo->prepare("
    SELECT DISTINCT 
      r.product_id AS id,
      r.slug,
      r.title,
      r.price,
      r.compare_price,
      r.main_image
    FROM v_user_recommendations r
    WHERE r.user_id = :uid
      AND r.stock > 0
    ORDER BY r.popularity DESC, r.created_at DESC
    LIMIT 8
");
$stmt->execute([':uid' => $userId]);
$rekom = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($rekom)) {
    $stmt = $pdo->query("
        SELECT id, slug, title, price, compare_price, main_image
        FROM products
        WHERE is_active=1 AND stock>0
        ORDER BY popularity DESC, created_at DESC
        LIMIT 8
    ");
    $rekom = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// helper nama inisial
$fullName = trim((string)($user['full_name'] ?? 'Pengguna'));
$initial  = mb_strtoupper(mb_substr($fullName !== '' ? $fullName : 'S', 0, 1));
$email    = (string)($user['email'] ?? '');
?>
<div class="profile-page">

  <!-- KARTU ATAS PROFIL -->
  <section class="profile-hero">
    <div class="profile-hero-inner">
      <div class="profile-avatar">
        <span><?= e($initial) ?></span>
      </div>
      <div class="profile-main-info">
        <div class="profile-name"><?= e($fullName ?: 'Pengguna SellExa') ?></div>
        <?php if ($email): ?>
          <div class="profile-email"><?= e($email) ?></div>
        <?php endif; ?>
        <a href="<?= e($BASE) ?>/profil-edit.php" class="profile-edit-link">Edit profil</a>
      </div>
    </div>

    <div class="profile-stat-row">
      <div class="profile-stat-item">
        <div class="stat-label">Poin</div>
        <div class="stat-value"><?= number_format($points, 0, ',', '.') ?></div>
      </div>
      <div class="profile-stat-item">
        <div class="stat-label">Voucher</div>
        <div class="stat-value"><?= $voucherCount ?></div>
      </div>
      <div class="profile-stat-item">
        <div class="stat-label">Keranjang</div>
        <div class="stat-value"><?= $cartQty ?></div>
      </div>
    </div>
  </section>

  <!-- MENU TRANSAKSI -->
  <section class="profile-section">
    <div class="section-header">
      <h2>Transaksi</h2>
      <a href="<?= e($BASE) ?>/transaksi.php" class="section-link">Lihat semua</a>
    </div>
    <div class="profile-menu-grid">
      <a href="<?= e($BASE) ?>/transaksi.php?tab=bayar" class="profile-menu-item">
        <div class="menu-icon circle wait">Rp</div>
        <div class="menu-text">Bayar</div>
      </a>
      <a href="<?= e($BASE) ?>/transaksi.php?tab=proses" class="profile-menu-item">
        <div class="menu-icon circle process">⏳</div>
        <div class="menu-text">Diproses</div>
      </a>
      <a href="<?= e($BASE) ?>/transaksi.php?tab=dikirim" class="profile-menu-item">
        <div class="menu-icon circle ship">🚚</div>
        <div class="menu-text">Dikirim</div>
      </a>
      <a href="<?= e($BASE) ?>/transaksi.php?tab=tiba" class="profile-menu-item">
        <div class="menu-icon circle done">📦</div>
        <div class="menu-text">Sudah Tiba</div>
      </a>
      <a href="<?= e($BASE) ?>/transaksi.php?tab=ulasan" class="profile-menu-item">
        <div class="menu-icon circle review">⭐</div>
        <div class="menu-text">Ulasan</div>
      </a>
    </div>
  </section>

  <!-- MENU LAINNYA -->
  <section class="profile-section">
    <h2>Menu Lainnya</h2>
    <div class="profile-menu-grid more">

      <!-- BUKA TOKO / TOKO SAYA -->
      <a href="<?= e($BASE) ?>/buka_toko.php" class="profile-menu-item">
        <div class="menu-icon square"><?= $hasStore ? '🏪' : '🏬' ?></div>
        <div class="menu-text">
          <?= $hasStore ? 'Toko Saya' : 'Buka Toko' ?>
        </div>
        <?php if ($hasStore && $storeName !== ''): ?>
          <div class="menu-subtext"><?= e($storeName) ?></div>
        <?php endif; ?>
      </a>

      <a href="<?= e($BASE) ?>/alamat.php" class="profile-menu-item">
        <div class="menu-icon square">📍</div>
        <div class="menu-text">Alamat</div>
      </a>
      <a href="<?= e($BASE) ?>/riwayat_transaksi.php" class="profile-menu-item">
        <div class="menu-icon square">🛍️</div>
        <div class="menu-text">Beli Lagi</div>
      </a>
      <a href="<?= e($BASE) ?>/wishlist.php" class="profile-menu-item">
        <div class="menu-icon square">❤️</div>
        <div class="menu-text">Wishlist</div>
      </a>
    </div>
  </section>

  <!-- REKOMENDASI PRODUK -->
  <?php if (!empty($rekom)): ?>
  <section class="profile-section">
    <h2>Rekomendasi Untukmu</h2>
    <div class="rekom-grid">
      <?php foreach ($rekom as $p):
        $img = upload_image_url($p['main_image'] ?? null, $BASE);
        $hasPromo = (!is_null($p['compare_price']) && (float)$p['compare_price'] > (float)$p['price']);
        $detailUrl = $BASE.'/detail_produk.php?slug='.urlencode($p['slug']);
      ?>
      <div class="rekom-card">
        <a href="<?= e($detailUrl) ?>" class="rekom-link">
          <img src="<?= e($img) ?>" alt="<?= e($p['title']) ?>" class="rekom-img" loading="lazy">
          <h3 class="rekom-title"><?= e($p['title']) ?></h3>
          <div class="rekom-price">
            <span class="rekom-price-current">
              Rp<?= number_format((float)$p['price'], 0, ',', '.') ?>
            </span>
            <?php if ($hasPromo): ?>
              <span class="rekom-price-compare">
                Rp<?= number_format((float)$p['compare_price'], 0, ',', '.') ?>
              </span>
            <?php endif; ?>
          </div>
        </a>
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
