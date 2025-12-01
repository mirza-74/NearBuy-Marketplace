<?php
// ===============================================================
// SellExa – Halaman Sukses Checkout
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
    header('Location: '.$BASE.'/login.php');
    exit;
}
$userId = (int)$user['id'];

// css pakai file checkout juga
$EXTRA_CSS = ['style-checkout.css'];

// ambil id pesanan dari query string
$orderId = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

// cari order milik user ini
$order = null;
if ($orderId > 0) {
    $stmt = $pdo->prepare("
        SELECT o.*, u.full_name, u.email
        FROM orders o
        JOIN users u ON u.id = o.user_id
        WHERE o.id = :id AND o.user_id = :uid
        LIMIT 1
    ");
    $stmt->execute([
        ':id'  => $orderId,
        ':uid' => $userId,
    ]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
}

// header
require_once __DIR__ . '/../includes/header.php';
?>
<div class="checkout-page checkout-success-page">

  <?php if (!$order): ?>

    <div class="co-card co-success-card">
      <h1 class="co-success-title">Pesanan tidak ditemukan</h1>
      <p class="co-success-text">
        Kami tidak menemukan data pesanan yang dimaksud. 
        Mungkin link sudah kadaluarsa atau pesanan bukan milik akun ini.
      </p>
      <div class="co-success-actions">
        <a href="<?= e($BASE) ?>/" class="btn-primary">Kembali ke Beranda</a>
        <a href="<?= e($BASE) ?>/transaksi.php" class="btn-ghost">Lihat Semua Transaksi</a>
      </div>
    </div>

  <?php else: ?>

    <div class="co-card co-success-card">
      <div class="co-success-icon">✅</div>
      <h1 class="co-success-title">Pesanan kamu sedang diproses</h1>
      <p class="co-success-text">
        Terima kasih sudah berbelanja di <strong>SellExa</strong> 💜<br>
        Pesanan kamu sudah kami terima dan sedang diproses oleh sistem.
      </p>

      <div class="co-success-summary">
        <div class="co-success-row">
          <span>No. Pesanan</span>
          <span>#<?= (int)$order['id'] ?></span>
        </div>
        <div class="co-success-row">
          <span>Total Tagihan</span>
          <span>Rp<?= number_format((float)$order['grand_total'],0,',','.') ?></span>
        </div>
        <div class="co-success-row">
          <span>Status</span>
          <span class="badge-status badge-<?= e($order['status']) ?>">
            <?= ucwords(str_replace('_',' ', $order['status'])) ?>
          </span>
        </div>
        <div class="co-success-row">
          <span>Tanggal</span>
          <span><?= e($order['created_at']) ?></span>
        </div>
        <div class="co-success-row">
          <span>Metode Pembayaran</span>
          <span><?= e(strtoupper($order['payment_method'] ?? '-')) ?></span>
        </div>
      </div>

      <div class="co-success-actions">
        <a href="<?= e($BASE) ?>/transaksi.php?order_id=<?= (int)$order['id'] ?>" class="btn-primary">
          Lihat Detail Pesanan
        </a>
        <a href="<?= e($BASE) ?>/" class="btn-ghost">
          Kembali Belanja
        </a>
      </div>
    </div>

  <?php endif; ?>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
</main>
</body>
</html>
