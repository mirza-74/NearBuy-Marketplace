<?php
// ===============================================================
// NearBuy – Dashboard Toko (Seller Overview)
// ===============================================================
declare(strict_types=1);

// BASE: naik satu level dari /public/seller ke /public
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));   // /NearBuy-marketplace/public/seller
$BASE      = rtrim(dirname($scriptDir), '/');                                  // /NearBuy-marketplace/public

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/settings.php';

// hanya seller yang boleh masuk ke dashboard ini
$user = $_SESSION['user'] ?? null;
if (!$user || ($user['role'] ?? '') !== 'seller') {
    header('Location: ' . $BASE . '/login.php');
    exit;
}

$userId = (int)$user['id'];

// beritahu header kalau halaman ini pakai CSS khusus seller
$EXTRA_CSS = ['seller/style-admin-dashboard.css'];

// header umum (includes/header.php) – sudah baca $BASE di atas
require_once __DIR__ . '/../../includes/header.php';

// helper label status
if (!function_exists('order_status_label')) {
    function order_status_label(string $status): string {
        switch ($status) {
            case 'menunggu_pembayaran': return 'Menunggu Pembayaran';
            case 'diproses':            return 'Diproses';
            case 'dikirim':             return 'Dikirim';
            case 'selesai':             return 'Selesai';
            case 'dibatalkan':          return 'Dibatalkan';
            default:                    return $status;
        }
    }
}

// helper gambar sederhana
if (!function_exists('seller_image_url')) {
    function seller_image_url(?string $path, string $BASE): string {
        if (!$path) {
            return 'https://via.placeholder.com/360x240?text=Produk+NearBuy';
        }
        if (preg_match('~^https?://~i', $path)) {
            return $path;
        }
        $p = ltrim($path, '/');
        if (str_starts_with($p, 'products/')) {
            return $BASE . '/uploads/' . $p;
        }
        if (str_starts_with($p, 'uploads/')) {
            return $BASE . '/' . $p;
        }
        if (str_starts_with($p, 'public/uploads/')) {
            return $BASE . '/' . substr($p, 7);
        }
        return $BASE . '/uploads/' . $p;
    }
}

// ================== Info toko seller ==================
$shop      = null;
$shopId    = null;
$hasLatLng = false;

try {
    $stmtShop = $pdo->prepare("
        SELECT id, name, address, latitude, longitude
        FROM shops
        WHERE user_id = ?
        LIMIT 1
    ");
    $stmtShop->execute([$userId]);
    $shop = $stmtShop->fetch(PDO::FETCH_ASSOC) ?: null;

    if ($shop) {
        $shopId    = (int)$shop['id'];
        $hasLatLng = !is_null($shop['latitude']) && !is_null($shop['longitude']);
    }
} catch (Throwable $e) {
    $shop   = null;
    $shopId = null;
}

// ================== Angka ringkas khusus seller ==================
$totalProduk       = 0;
$totalPesanan      = 0;
$totalOmzetBruto   = 0.0;
$totalQtyTerjual   = 0;

if ($shopId) {
    try {
        // jumlah produk milik toko ini
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE shop_id = ?");
        $stmt->execute([$shopId]);
        $totalProduk = (int)$stmt->fetchColumn();
    } catch (Throwable $e) {
        $totalProduk = 0;
    }

    try {
        // pesanan yang berisi produk dari toko ini
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(DISTINCT o.id) AS total_orders,
                COALESCE(SUM(oi.subtotal),0) AS total_omzet,
                COALESCE(SUM(oi.qty),0) AS total_qty
            FROM orders o
            JOIN order_items oi ON oi.order_id = o.id
            JOIN products p     ON p.id = oi.product_id
            WHERE p.shop_id = ?
        ");
        $stmt->execute([$shopId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        $totalPesanan    = (int)($row['total_orders'] ?? 0);
        $totalOmzetBruto = (float)($row['total_omzet'] ?? 0);
        $totalQtyTerjual = (int)($row['total_qty'] ?? 0);
    } catch (Throwable $e) {
        $totalPesanan    = 0;
        $totalOmzetBruto = 0.0;
        $totalQtyTerjual = 0;
    }
}

// ================== 10 pesanan terbaru toko ini ==================
$orders = [];
if ($shopId) {
    $stmt = $pdo->prepare("
        SELECT 
            o.id,
            o.grand_total,
            o.status,
            o.created_at,
            u.full_name,
            u.email
        FROM orders o
        JOIN order_items oi ON oi.order_id = o.id
        JOIN products p     ON p.id = oi.product_id
        JOIN users u        ON u.id = o.user_id
        WHERE p.shop_id = ?
        GROUP BY o.id, o.grand_total, o.status, o.created_at, u.full_name, u.email
        ORDER BY o.created_at DESC
        LIMIT 10
    ");
    $stmt->execute([$shopId]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ================== Preview produk toko di beranda ==================
$produkPreview = [];
if ($shopId) {
    $stmt = $pdo->prepare("
        SELECT id, title, slug, price, compare_price, main_image, stock, created_at
        FROM products
        WHERE shop_id = ?
        ORDER BY created_at DESC
        LIMIT 6
    ");
    $stmt->execute([$shopId]);
    $produkPreview = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// daftar status valid (ENUM di tabel orders)
$statuses = [
    'menunggu_pembayaran',
    'diproses',
    'dikirim',
    'selesai',
    'dibatalkan'
];
?>

<div class="admin-shell">

  <header class="admin-page-header">
    <div>
      <h1 class="admin-page-title">Dashboard Toko</h1>
      <p class="admin-page-subtitle">
        Halo, <?= e($user['full_name'] ?? 'Seller') ?> 👋
        <?php if ($shop && $shop['name']): ?>
          – <?= e($shop['name']) ?>
        <?php endif; ?>
      </p>
    </div>
  </header>

  <!-- Ringkasan utama -->
  <section class="admin-grid">
    <div class="admin-card">
      <div class="admin-card-label">Produk Aktif</div>
      <div class="admin-card-value"><?= (int)$totalProduk ?></div>
      <div class="admin-card-desc">Jumlah produk yang kamu jual di NearBuy.</div>
    </div>

    <div class="admin-card">
      <div class="admin-card-label">Pesanan Masuk</div>
      <div class="admin-card-value"><?= (int)$totalPesanan ?></div>
      <div class="admin-card-desc">Total pesanan yang melibatkan produk tokomu.</div>
    </div>

    <div class="admin-card">
      <div class="admin-card-label">Omzet Bruto</div>
      <div class="admin-card-value">
        Rp<?= number_format($totalOmzetBruto, 0, ',', '.') ?>
      </div>
      <div class="admin-card-desc">Akumulasi nilai produk yang terjual.</div>
    </div>

    <div class="admin-card">
      <div class="admin-card-label">Status Lokasi Toko</div>
      <div class="admin-card-value">
        <?= $hasLatLng ? '✅ Sudah di-set' : '⚠ Belum di-set' ?>
      </div>
      <div class="admin-card-desc">
        Lokasi dipakai untuk rekomendasi di sekitar pengguna.
      </div>
    </div>
  </section>

  <!-- Info / pengingat profil toko -->
  <section class="admin-section" id="profil-toko">
    <h2 class="admin-section-title">Profil & Lokasi Toko</h2>
    <?php if (!$shop): ?>
      <p class="admin-section-text">
        Kamu belum memiliki profil toko. Silakan isi data toko dan alamat
        di halaman <b>Pengaturan Toko</b> agar produkmu muncul sebagai seller lokal di NearBuy.
      </p>
    <?php else: ?>
      <p class="admin-section-text">
        Nama toko: <b><?= e($shop['name'] ?? 'Toko NearBuy') ?></b><br>
        Alamat: <?= e($shop['address'] ?: 'Belum diisi') ?><br>
        Lokasi: <?= $hasLatLng ? 'Sudah tersimpan di peta' : 'Belum diatur di peta' ?>.
      </p>
    <?php endif; ?>
  </section>

  <!-- Pesanan terbaru -->
  <section class="admin-card">
    <h2>Pesanan Terbaru</h2>
    <?php if (empty($orders)): ?>
      <p>Belum ada pesanan untuk tokomu.</p>
    <?php else: ?>
      <table class="admin-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Pembeli</th>
            <th>Total</th>
            <th>Status</th>
            <th>Tanggal</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($orders as $o): ?>
            <tr>
              <td>#<?= (int)$o['id'] ?></td>
              <td><?= e($o['full_name'] ?: $o['email']) ?></td>
              <td>Rp<?= number_format((float)$o['grand_total'], 0, ',', '.') ?></td>
              <td><?= e(order_status_label($o['status'])) ?></td>
              <td><?= e($o['created_at']) ?></td>
              <td>
                <form method="post"
                      action="<?= e($BASE) ?>/seller/update_order_status.php"
                      class="inline-form">
                  <?php if (function_exists('csrf_input')) { csrf_input(); } ?>
                  <input type="hidden" name="order_id" value="<?= (int)$o['id'] ?>">

                  <select name="status">
                    <?php foreach ($statuses as $st): ?>
                      <option value="<?= e($st) ?>"
                        <?= $st === $o['status'] ? 'selected' : '' ?>>
                        <?= e(order_status_label($st)) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>

                  <button type="submit">Update</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </section>

  <!-- Quick actions untuk seller -->
  <section class="admin-section">
    <h2 class="admin-section-title">Aksi Cepat Toko</h2>
    <div class="admin-quick-links">
      <a class="admin-quick-link" href="<?= e($BASE) ?>/seller/produk.php">Kelola Produk</a>
      <a class="admin-quick-link" href="<?= e($BASE) ?>/seller/produk_varian.php">Kelola Varian Produk</a>
      <a class="admin-quick-link" href="<?= e($BASE) ?>/seller/pesanan.php">Kelola Pesanan</a>
      <a class="admin-quick-link" href="<?= e($BASE) ?>/seller/promo.php">Kelola Promo Harga</a>
      <a class="admin-quick-link" href="<?= e($BASE) ?>/seller/banners.php">Kelola Banner Toko</a>
      <a class="admin-quick-link" href="<?= e($BASE) ?>/seller/reporting_transaksi.php">Laporan Transaksi Toko</a>
      <!-- kalau nanti ada halaman khusus pengaturan toko -->
      <!-- <a class="admin-quick-link" href="<?= e($BASE) ?>/seller/pengaturan_toko.php">Pengaturan Toko & Lokasi</a> -->
    </div>
  </section>

  <!-- Preview produk akan tampil ke user -->
  <section class="admin-section">
    <h2 class="admin-section-title">Preview Produk di Beranda Pengguna</h2>
    <p class="admin-section-text">
      Berikut contoh bagaimana produkmu bisa muncul di halaman utama NearBuy
      ketika lokasi pengguna dekat dengan tokomu.
    </p>

    <div class="produk-grid">
      <?php if (empty($produkPreview)): ?>
        <p style="color:#64748b;">Belum ada produk yang bisa ditampilkan. Tambahkan produk terlebih dahulu.</p>
      <?php else: ?>
        <?php foreach ($produkPreview as $p): ?>
          <?php
            $img      = seller_image_url($p['main_image'] ?? null, $BASE);
            $hasPromo = (!is_null($p['compare_price']) && (float)$p['compare_price'] > (float)$p['price']);
          ?>
          <div class="produk-card">
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
            <p class="subtext">Stok: <?= (int)$p['stock'] ?></p>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </section>

</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
</main>
</body>
</html>
