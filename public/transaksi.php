<?php
// ===============================================================
// SellExa – Riwayat Transaksi / Pesanan User
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
    header('Location: '.$BASE.'/login.php?next='.urlencode($BASE.'/transaksi.php'));
    exit;
}
$userId = (int)$user['id'];

// pakai CSS profil dulu
$EXTRA_CSS = ['style-profil.css'];

// header umum
require_once __DIR__ . '/../includes/header.php';

// tab: bayar, proses, dikirim, tiba, ulasan, semua
$tab = $_GET['tab'] ?? 'semua';

// mapping tab ke status orders
$statusFilter = null;
switch ($tab) {
    case 'bayar':
        $statusFilter = ['menunggu_pembayaran'];
        break;
    case 'proses':
        $statusFilter = ['diproses'];
        break;
    case 'dikirim':
        $statusFilter = ['dikirim'];
        break;
    case 'tiba':
        $statusFilter = ['selesai'];
        break;
    case 'ulasan':
        $statusFilter = ['selesai'];
        break;
    default:
        $tab = 'semua';
        $statusFilter = null;
        break;
}

// ambil pesanan user
$sql = "
    SELECT 
        o.id,
        o.grand_total,
        o.status,
        o.created_at,
        o.total_items,
        o.payment_method
    FROM orders o
    WHERE o.user_id = ?
";
$params = [$userId];

if ($statusFilter !== null && !empty($statusFilter)) {
    $in = implode(',', array_fill(0, count($statusFilter), '?'));
    $sql .= " AND o.status IN ($in)";
    foreach ($statusFilter as $st) {
        $params[] = $st;
    }
}

// urutkan dari terbaru
$sql .= " ORDER BY o.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// helper badge status
function status_badge_class(string $status): string {
    switch ($status) {
        case 'menunggu_pembayaran': return 'badge-menunggu_pembayaran';
        case 'diproses':            return 'badge-diproses';
        case 'dikirim':             return 'badge-dikirim';
        case 'selesai':             return 'badge-selesai';
        case 'dibatalkan':          return 'badge-dibatalkan';
        default:                    return '';
    }
}

// untuk inisial di header
$fullName = trim((string)($user['full_name'] ?? 'Pengguna'));
$initial  = mb_strtoupper(mb_substr($fullName !== '' ? $fullName : 'S', 0, 1));
?>
<div class="profile-page transaksi-page">

  <!-- Header kecil -->
  <section class="profile-hero">
    <div class="profile-hero-inner">
      <div class="profile-avatar">
        <span><?= e($initial) ?></span>
      </div>
      <div class="profile-main-info">
        <div class="profile-name"><?= e($fullName ?: 'Pengguna SellExa') ?></div>
        <div class="profile-email"><?= e($user['email'] ?? '') ?></div>
      </div>
    </div>
  </section>

  <!-- TAB STATUS -->
  <section class="profile-section">
    <div class="section-header">
      <h2>Transaksi Saya</h2>
      <a href="<?= e($BASE) ?>/profil.php" class="section-link">Kembali ke Profil</a>
    </div>

    <div class="transaksi-tabs">
      <?php
        $tabs = [
          'semua'  => 'Semua',
          'bayar'  => 'Bayar',
          'proses' => 'Diproses',
          'dikirim'=> 'Dikirim',
          'tiba'   => 'Sudah Tiba',
          'ulasan' => 'Ulasan',
        ];
      ?>
      <?php foreach ($tabs as $key => $label): ?>
        <a href="<?= e($BASE) ?>/transaksi.php?tab=<?= e($key) ?>"
           class="transaksi-tab <?= $tab === $key ? 'active' : '' ?>">
          <?= e($label) ?>
        </a>
      <?php endforeach; ?>
    </div>

    <!-- pesan sukses misalnya dari konfirmasi pesanan -->
    <?php if (!empty($_GET['msg'])): ?>
      <div class="flash-info">
        <?= e($_GET['msg']) ?>
      </div>
    <?php endif; ?>
  </section>

  <!-- LIST PESANAN -->
  <section class="profile-section">
    <?php if (empty($orders)): ?>
      <p>Belum ada pesanan di kategori ini.</p>
    <?php else: ?>
      <div class="transaksi-list">
        <?php foreach ($orders as $o): 
          $badgeClass = status_badge_class($o['status']);
          $statusText = ucwords(str_replace('_', ' ', $o['status']));
        ?>
          <div class="transaksi-card">
            <div class="transaksi-card-header">
              <div class="transaksi-card-title">
                <span class="transaksi-order-id">#<?= (int)$o['id'] ?></span>
                <span class="badge-status <?= e($badgeClass) ?>">
                  <?= e($statusText) ?>
                </span>
              </div>
              <div class="transaksi-card-date">
                <?= e($o['created_at']) ?>
              </div>
            </div>

            <div class="transaksi-card-body">
              <div class="transaksi-row">
                <span>Total Barang</span>
                <span><?= (int)$o['total_items'] ?> pcs</span>
              </div>
              <div class="transaksi-row">
                <span>Total Tagihan</span>
                <span>Rp<?= number_format((float)$o['grand_total'], 0, ',', '.') ?></span>
              </div>
              <div class="transaksi-row">
                <span>Metode Pembayaran</span>
                <span><?= e(strtoupper($o['payment_method'] ?? '-')) ?></span>
              </div>
            </div>

            <div class="transaksi-card-footer">
              <?php if ($o['status'] === 'dikirim'): ?>
                <form method="post" action="<?= e($BASE) ?>/konfirmasi_pesanan.php" class="transaksi-actions">
                  <?php if (function_exists('csrf_input')) { csrf_input(); } ?>
                  <input type="hidden" name="order_id" value="<?= (int)$o['id'] ?>">
                  <button type="submit" class="btn-small btn-primary">
                    Konfirmasi pesanan sudah diterima
                  </button>
                </form>
              <?php elseif ($o['status'] === 'selesai'): ?>
                <span class="transaksi-note">
                  Pesanan sudah selesai. Terima kasih sudah berbelanja di SellExa 💜
                </span>
              <?php else: ?>
                <span class="transaksi-note">
                  Terima kasih sudah berbelanja di SellExa 💜
                </span>
              <?php endif; ?>
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
