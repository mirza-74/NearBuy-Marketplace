<?php
// ===============================================================
// SellExa – Kelola Pesanan (Admin)
// ===============================================================
declare(strict_types=1);

$BASE = '/Marketplace_SellExa/public';

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/settings.php';

// pastikan hanya admin yang boleh masuk
$user = $_SESSION['user'] ?? null;
if (!$user || ($user['role'] ?? '') !== 'admin') {
    header('Location: ' . $BASE . '/login.php');
    exit;
}

// CSS khusus admin (kalau mau, bisa pakai file yang sama dengan dashboard)
$EXTRA_CSS = ['admin/style-admin-dashboard.css'];

// header umum
require_once __DIR__ . '/../../includes/header.php';

// ---------------- FILTER STATUS ----------------
$allowedStatus = [
    ''                    => 'Semua Status',
    'menunggu_pembayaran' => 'Menunggu Pembayaran',
    'diproses'            => 'Diproses',
    'dikirim'             => 'Dikirim',
    'selesai'             => 'Selesai',
    'dibatalkan'          => 'Dibatalkan',
];

$status = $_GET['status'] ?? '';
if (!array_key_exists($status, $allowedStatus)) {
    $status = '';
}

// ambil daftar pesanan
$sql = "
    SELECT o.*, u.full_name, u.email
    FROM orders o
    JOIN users u ON u.id = o.user_id
";
$params = [];

if ($status !== '') {
    $sql .= " WHERE o.status = :status";
    $params[':status'] = $status;
}

$sql .= " ORDER BY o.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// helper label status
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

// helper e() kalau belum ada
if (!function_exists('e')) {
    function e(string $str): string {
        return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
    }
}
?>
<div class="admin-shell">

  <header class="admin-page-header">
    <div>
      <h1 class="admin-page-title">Kelola Pesanan</h1>
      <p class="admin-page-subtitle">
        Lihat dan atur status pesanan dari semua pengguna.
      </p>
    </div>
  </header>

  <!-- Filter Status -->
  <section class="admin-card" style="margin-bottom:16px;">
    <form method="get" class="admin-filter-form">
      <label>
        Status:
        <select name="status" onchange="this.form.submit()">
          <?php foreach ($allowedStatus as $key => $label): ?>
            <option value="<?= e($key) ?>" <?= $key === $status ? 'selected' : '' ?>>
              <?= e($label) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </label>
      <?php if ($status !== ''): ?>
        <a href="<?= e($BASE) ?>/admin/pesanan.php" class="admin-btn-small">Reset</a>
      <?php endif; ?>
    </form>
  </section>

  <!-- Tabel Pesanan -->
  <section class="admin-card">
    <h2>Daftar Pesanan</h2>

    <?php if (empty($orders)): ?>
      <p>Belum ada pesanan untuk filter ini.</p>
    <?php else: ?>
      <table class="admin-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Pembeli</th>
            <th>Total Barang</th>
            <th>Ongkir</th>
            <th>Pajak Admin</th>
            <th>Grand Total</th>
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
              <td>Rp<?= number_format((float)$o['total_barang'], 0, ',', '.') ?></td>
              <td>Rp<?= number_format((float)$o['total_ongkir'], 0, ',', '.') ?></td>
              <td>Rp<?= number_format((float)$o['total_pajak_admin'], 0, ',', '.') ?></td>
              <td><strong>Rp<?= number_format((float)$o['grand_total'], 0, ',', '.') ?></strong></td>
              <td><?= e(order_status_label($o['status'])) ?></td>
              <td><?= e($o['created_at']) ?></td>
              <td>
                <form method="post"
                      action="<?= e($BASE) ?>/admin/update_order_status.php"
                      class="inline-form">
                  <?php if (function_exists('csrf_input')) { csrf_input(); } ?>
                  <input type="hidden" name="order_id" value="<?= (int)$o['id'] ?>">

                  <select name="status">
                    <?php foreach ($allowedStatus as $key => $label): ?>
                      <?php if ($key === '') continue; ?>
                      <option value="<?= e($key) ?>"
                        <?= $key === $o['status'] ? 'selected' : '' ?>>
                        <?= e($label) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>

                  <button type="submit" class="admin-btn-small">Update</button>
                </form>
              </td>
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
