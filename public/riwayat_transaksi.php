<?php
// ===============================================================
// SellExa – Riwayat Transaksi (Customer)
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
    header('Location: '.$BASE.'/login.php?next='.urlencode($BASE.'/riwayat_transaksi.php'));
    exit;
}
$userId = (int)$user['id'];

// pakai CSS profil (atau ganti kalau kamu punya style khusus)
$EXTRA_CSS = ['style-profil.css'];

// header umum
require_once __DIR__ . '/../includes/header.php';

// ambil semua pesanan user
$stmt = $pdo->prepare("
    SELECT 
        o.id,
        o.grand_total,
        o.status,
        o.created_at,
        o.total_items,
        o.payment_method
    FROM orders o
    WHERE o.user_id = :uid
    ORDER BY o.created_at DESC
");
$stmt->execute([':uid' => $userId]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// hitung summary kecil (total pesanan & total belanja)
$totalPesanan = count($orders);
$totalBelanja = 0.0;
foreach ($orders as $o) {
    $totalBelanja += (float)$o['grand_total'];
}

// helper badge status
function rt_status_badge_class(string $status): string {
    switch ($status) {
        case 'menunggu_pembayaran': return 'badge-menunggu_pembayaran';
        case 'diproses':            return 'badge-diproses';
        case 'dikirim':             return 'badge-dikirim';
        case 'selesai':             return 'badge-selesai';
        case 'dibatalkan':          return 'badge-dibatalkan';
        default:                    return '';
    }
}
?>
<style>
/* ====== Riwayat Transaksi (basic) – kalau sudah ada di CSS, boleh hapus bagian ini ====== */
.riwayat-page {
  max-width: 960px;
  margin: 0 auto 40px;
}
.riwayat-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  gap: 12px;
}
.riwayat-summary {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  font-size: 13px;
  color: #444;
}
.riwayat-summary-item {
  padding: 6px 10px;
  border-radius: 999px;
  background: #f3f4f6;
}
.riwayat-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
  margin-top: 12px;
}
.riwayat-card {
  border-radius: 10px;
  border: 1px solid #e5e7eb;
  background: #fff;
  padding: 10px 12px;
}
.riwayat-card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 4px;
}
.riwayat-card-title {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 14px;
  font-weight: 600;
}
.riwayat-card-date {
  font-size: 12px;
  color: #6b7280;
}
.riwayat-card-body {
  font-size: 13px;
  margin-top: 4px;
}
.riwayat-row {
  display: flex;
  justify-content: space-between;
  padding: 2px 0;
}
.badge-status {
  padding: 2px 8px;
  border-radius: 999px;
  font-size: 11px;
  font-weight: 500;
}
.badge-menunggu_pembayaran { background:#fef3c7;color:#92400e; }
.badge-diproses            { background:#dbeafe;color:#1d4ed8; }
.badge-dikirim             { background:#e0f2fe;color:#0369a1; }
.badge-selesai             { background:#dcfce7;color:#166534; }
.badge-dibatalkan          { background:#fee2e2;color:#b91c1c; }
.riwayat-empty {
  padding: 16px;
  border-radius: 10px;
  border: 1px dashed #e5e7eb;
  background: #f9fafb;
  font-size: 14px;
  color: #4b5563;
}
</style>

<div class="profile-page riwayat-page">

  <!-- Header kecil -->
  <section class="profile-hero">
    <div class="profile-hero-inner">
      <div class="profile-avatar">
        <?php
          $fullName = trim((string)($user['full_name'] ?? 'Pengguna'));
          $initial  = mb_strtoupper(mb_substr($fullName !== '' ? $fullName : 'S', 0, 1));
        ?>
        <span><?= e($initial) ?></span>
      </div>
      <div class="profile-main-info">
        <div class="profile-name"><?= e($fullName ?: 'Pengguna SellExa') ?></div>
        <div class="profile-email"><?= e($user['email'] ?? '') ?></div>
        <a href="<?= e($BASE) ?>/profil.php" class="profile-edit-link">Kembali ke Profil</a>
      </div>
    </div>
  </section>

  <!-- Judul + Summary -->
  <section class="profile-section">
    <div class="riwayat-header">
      <h2>Riwayat Transaksi</h2>
      <div class="riwayat-summary">
        <div class="riwayat-summary-item">
          Total Pesanan: <b><?= (int)$totalPesanan ?></b>
        </div>
        <div class="riwayat-summary-item">
          Total Belanja: <b>Rp<?= number_format($totalBelanja, 0, ',', '.') ?></b>
        </div>
      </div>
    </div>
  </section>

  <!-- List Riwayat -->
  <section class="profile-section">
    <?php if (empty($orders)): ?>
      <div class="riwayat-empty">
        Belum ada transaksi yang tercatat. Yuk mulai belanja di SellExa 💜
      </div>
    <?php else: ?>
      <div class="riwayat-list">
        <?php foreach ($orders as $o): 
          $badgeClass = rt_status_badge_class($o['status']);
          $statusText = ucwords(str_replace('_', ' ', $o['status']));
        ?>
          <div class="riwayat-card">
            <div class="riwayat-card-header">
              <div class="riwayat-card-title">
                <span>#<?= (int)$o['id'] ?></span>
                <span class="badge-status <?= e($badgeClass) ?>"><?= e($statusText) ?></span>
              </div>
              <div class="riwayat-card-date">
                <?= e($o['created_at']) ?>
              </div>
            </div>
            <div class="riwayat-card-body">
              <div class="riwayat-row">
                <span>Total Barang</span>
                <span><?= (int)$o['total_items'] ?> pcs</span>
              </div>
              <div class="riwayat-row">
                <span>Total Tagihan</span>
                <span>Rp<?= number_format((float)$o['grand_total'], 0, ',', '.') ?></span>
              </div>
              <div class="riwayat-row">
                <span>Metode Pembayaran</span>
                <span><?= e(strtoupper($o['payment_method'] ?? '-')) ?></span>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
</main>
</body>
</html>
