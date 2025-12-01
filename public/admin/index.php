<?php
// ===============================================================
// SellExa – Dashboard Admin (basic overview)
// ===============================================================
declare(strict_types=1);

// base URL untuk link
$BASE = '/Marketplace_SellExa/public';

// include dari ROOT project (naik 2 level dari /public/admin)
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/settings.php';

// pastikan hanya admin yang boleh masuk
$user = $_SESSION['user'] ?? null;
if (!$user || ($user['role'] ?? '') !== 'admin') {
    header('Location: ' . $BASE . '/login.php');
    exit;
}

// kasih tahu header kalau halaman ini pakai CSS khusus admin
$EXTRA_CSS = ['admin/style-admin-dashboard.css'];

// header umum (yang di /includes/header.php)
require_once __DIR__ . '/../../includes/header.php';

// helper label status (biar lebih rapi)
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

// ================== ambil beberapa angka ringkas ==================
$totalProduk          = 0;
$totalPengguna        = 0;
$totalPesanan         = 0;
$totalPendapatanAdmin = 0.0;

try {
    $totalProduk = (int)$pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
} catch (Throwable $e) {
    $totalProduk = 0;
}

try {
    $stmtUser = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'pengguna'");
    $totalPengguna = (int)$stmtUser->fetchColumn();
} catch (Throwable $e) {
    $totalPengguna = 0;
}

try {
    $totalPesanan = (int)$pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();

    // pakai total_pajak_admin sebagai pendapatan admin (bisa kamu ganti sesuai desain)
    $totalPendapatanAdmin = (float)$pdo
        ->query("SELECT COALESCE(SUM(total_pajak_admin),0) FROM orders")
        ->fetchColumn();
} catch (Throwable $e) {
    $totalPesanan         = 0;
    $totalPendapatanAdmin = 0.0;
}

// Ambil 10 pesanan terbaru
$stmt = $pdo->query("
    SELECT o.id, o.grand_total, o.status, o.created_at,
           u.full_name, u.email
    FROM orders o
    JOIN users u ON u.id = o.user_id
    ORDER BY o.created_at DESC
    LIMIT 10
");
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// daftar status valid (sesuai ENUM di tabel orders)
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
      <h1 class="admin-page-title">Dashboard Admin</h1>
      <p class="admin-page-subtitle">
        Selamat datang, <?= e($user['full_name'] ?? 'Admin') ?> 👋
      </p>
    </div>
  </header>

  <section class="admin-card">
    <h2>Pesanan Terbaru</h2>
    <?php if (empty($orders)): ?>
      <p>Belum ada pesanan.</p>
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
              <td><?= htmlspecialchars($o['full_name'] ?: $o['email']) ?></td>
              <td>Rp<?= number_format((float)$o['grand_total'],0,',','.') ?></td>
              <td><?= htmlspecialchars(order_status_label($o['status'])) ?></td>
              <td><?= htmlspecialchars($o['created_at']) ?></td>
              <td>
                <form method="post"
                      action="<?= htmlspecialchars($BASE) ?>/admin/update_order_status.php"
                      class="inline-form">
                  <?php if (function_exists('csrf_input')) { csrf_input(); } ?>
                  <input type="hidden" name="order_id" value="<?= (int)$o['id'] ?>">

                  <select name="status">
                    <?php foreach ($statuses as $st): ?>
                      <option value="<?= htmlspecialchars($st) ?>"
                        <?= $st === $o['status'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars(order_status_label($st)) ?>
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

  <!-- Ringkasan Kartu -->
  <section class="admin-grid">
    <div class="admin-card">
      <div class="admin-card-label">Total Produk</div>
      <div class="admin-card-value"><?= (int)$totalProduk ?></div>
      <div class="admin-card-desc">Semua produk yang aktif di SellExa.</div>
    </div>

    <div class="admin-card">
      <div class="admin-card-label">Total Pembeli</div>
      <div class="admin-card-value"><?= (int)$totalPengguna ?></div>
      <div class="admin-card-desc">Akun pengguna dengan role pembeli.</div>
    </div>

    <div class="admin-card">
      <div class="admin-card-label">Total Pesanan</div>
      <div class="admin-card-value"><?= (int)$totalPesanan ?></div>
      <div class="admin-card-desc">Akumulasi pesanan yang tercatat (tabel <code>orders</code>).</div>
    </div>

    <div class="admin-card">
      <div class="admin-card-label">Pendapatan Admin</div>
      <div class="admin-card-value">
        Rp<?= number_format($totalPendapatanAdmin, 0, ',', '.') ?>
      </div>
      <div class="admin-card-desc">Dari pajak admin per pesanan (kolom <code>total_pajak_admin</code>).</div>
    </div>
  </section>

  <section class="admin-section">
    <h2 class="admin-section-title">Ringkasan Singkat</h2>
    <p class="admin-section-text">
      Halaman ini nantinya bisa dikembangkan menjadi dashboard lengkap
      grafik performa, transaksi harian, mutasi pendapatan admin dari pajak & ongkir, dan status pesanan.
    </p>
  </section>

  <section class="admin-section">
    <h2 class="admin-section-title">Navigasi Cepat</h2>
    <div class="admin-quick-links">
      <a class="admin-quick-link" href="<?= e($BASE) ?>/admin/produk.php">Kelola Produk</a>
      <a class="admin-quick-link" href="<?= e($BASE) ?>/admin/produk_varian.php">Kelola Varian Produk</a>
      <a class="admin-quick-link" href="<?= e($BASE) ?>/admin/voucher.php">Kelola Promo / Voucher</a>
      <a class="admin-quick-link" href="<?= e($BASE) ?>/admin/banners.php">Kelola Banner</a>
      <a class="admin-quick-link" href="<?= e($BASE) ?>/admin/pesanan.php">Kelola Semua Pesanan</a>
      <a class="admin-quick-link" href="<?= e($BASE) ?>/admin/reporting_transaksi.php">Transaksi Saya</a>
    </div>
  </section>

</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
</main>
</body>
</html>
