<?php
// ===============================================================
// SellExa – Reporting Transaksi (Admin)
// ===============================================================
declare(strict_types=1);

$BASE = '/Marketplace_SellExa/public';

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/settings.php';

// cek admin
$user = $_SESSION['user'] ?? null;
if (!$user || ($user['role'] ?? '') !== 'admin') {
    header('Location: ' . $BASE . '/login.php');
    exit;
}

// CSS admin
$EXTRA_CSS = ['admin/style-admin-dashboard.css'];

// header
require_once __DIR__ . '/../../includes/header.php';

// ==========================
// Filter Input
// ==========================
$status = $_GET['status'] ?? 'all';
$dari   = $_GET['dari'] ?? '';
$sampai = $_GET['sampai'] ?? '';

$where = [];
$params = [];

// filter status
if ($status !== 'all') {
    $where[] = "o.status = ?";
    $params[] = $status;
}

// filter tanggal
if ($dari !== '') {
    $where[] = "DATE(o.created_at) >= ?";
    $params[] = $dari;
}
if ($sampai !== '') {
    $where[] = "DATE(o.created_at) <= ?";
    $params[] = $sampai;
}

$sqlWhere = '';
if (!empty($where)) {
    $sqlWhere = "WHERE " . implode(" AND ", $where);
}

// ==========================
// Ambil summary
// ==========================
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) AS total_order,
        SUM(grand_total) AS total_pendapatan,
        SUM(total_ongkir + total_pajak_admin) AS total_admin_fee,
        SUM(total_items) AS total_produk
    FROM orders o
    $sqlWhere
");
$stmt->execute($params);
$summary = $stmt->fetch(PDO::FETCH_ASSOC);

// ==========================
// Ambil list transaksi
// ==========================
$stmt2 = $pdo->prepare("
    SELECT 
        o.id, o.grand_total, o.status, o.created_at,
        u.full_name, u.email
    FROM orders o
    JOIN users u ON u.id = o.user_id
    $sqlWhere
    ORDER BY o.created_at DESC
");
$stmt2->execute($params);
$orders = $stmt2->fetchAll(PDO::FETCH_ASSOC);

// daftar status
$statuses = [
    'all' => 'Semua',
    'menunggu_pembayaran' => 'Menunggu Pembayaran',
    'diproses' => 'Diproses',
    'dikirim' => 'Dikirim',
    'selesai' => 'Selesai',
    'dibatalkan' => 'Dibatalkan'
];
?>

<div class="admin-shell">

<header class="admin-page-header">
    <div>
      <h1 class="admin-page-title">Reporting Transaksi</h1>
      <p class="admin-page-subtitle">Laporan lengkap seluruh pesanan di SellExa</p>
    </div>
</header>

<!-- ======================== -->
<!--      FILTER AREA        -->
<!-- ======================== -->
<section class="admin-card">
    <h2>Filter Laporan</h2>

    <form method="get" class="admin-filter-form">
        <label>Status:</label>
        <select name="status">
            <?php foreach ($statuses as $key => $label): ?>
            <option value="<?= $key ?>" <?= $key === $status ? 'selected' : '' ?>>
                <?= $label ?>
            </option>
            <?php endforeach; ?>
        </select>

        <label>Dari Tanggal:</label>
        <input type="date" name="dari" value="<?= htmlspecialchars($dari) ?>">

        <label>Sampai Tanggal:</label>
        <input type="date" name="sampai" value="<?= htmlspecialchars($sampai) ?>">

        <button type="submit">Terapkan</button>
    </form>
</section>

<!-- ======================== -->
<!--      SUMMARY AREA        -->
<!-- ======================== -->
<section class="admin-grid">
    <div class="admin-card">
        <div class="admin-card-label">Total Pesanan</div>
        <div class="admin-card-value"><?= (int)($summary['total_order'] ?? 0) ?></div>
    </div>

    <div class="admin-card">
        <div class="admin-card-label">Total Pendapatan</div>
        <div class="admin-card-value">
            Rp<?= number_format((float)($summary['total_pendapatan'] ?? 0), 0, ',', '.') ?>
        </div>
    </div>

    <div class="admin-card">
        <div class="admin-card-label">Pendapatan Admin</div>
        <div class="admin-card-value">
            Rp<?= number_format((float)($summary['total_admin_fee'] ?? 0), 0, ',', '.') ?>
        </div>
    </div>

    <div class="admin-card">
        <div class="admin-card-label">Produk Terjual</div>
        <div class="admin-card-value">
            <?= (int)($summary['total_produk'] ?? 0) ?> pcs
        </div>
    </div>
</section>

<!-- ======================== -->
<!--      TABLE ORDERS        -->
<!-- ======================== -->
<section class="admin-card">
  <h2>Daftar Transaksi</h2>

  <?php if (empty($orders)): ?>
      <p>Tidak ada transaksi ditemukan.</p>
  <?php else: ?>
  <table class="admin-table">
    <thead>
      <tr>
        <th>ID</th>
        <th>Pembeli</th>
        <th>Total</th>
        <th>Status</th>
        <th>Tanggal</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($orders as $o): ?>
      <tr>
        <td>#<?= (int)$o['id'] ?></td>
        <td><?= htmlspecialchars($o['full_name'] ?: $o['email']) ?></td>
        <td>Rp<?= number_format((float)$o['grand_total'], 0, ',', '.') ?></td>
        <td><?= htmlspecialchars($o['status']) ?></td>
        <td><?= htmlspecialchars($o['created_at']) ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</section>

</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
</main>
</body>
</html>
