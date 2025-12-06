<?php
// ===============================================================
// NearBuy – Halaman Paket Seller
// ===============================================================
declare(strict_types=1);

// BASE otomatis
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$BASE = preg_replace('~/seller$~', '', rtrim($scriptDir, '/'));

// Include
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/db.php';

$user = $_SESSION['user'] ?? null;
if (!$user || ($user['role'] ?? '') !== 'seller') {
    header('Location: ' . $BASE . '/login.php');
    exit;
}

$sellerId = (int)$user['id'];

// Helper
if (!function_exists('e')) {
    function e(string $s): string {
        return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
    }
}

// =============================================
// Ambil toko seller
// =============================================
$stmt = $pdo->prepare("
    SELECT id, name, is_active, package_code, package_status, 
           package_started_at, package_expired_at
    FROM shops
    WHERE user_id = ?
    LIMIT 1
");
$stmt->execute([$sellerId]);
$shop = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$shop) {
    header("Location: {$BASE}/seller/toko.php");
    exit;
}

$shopId         = (int)$shop['id'];
$shopActive     = (int)$shop['is_active'] === 1;
$currentPkg     = $shop['package_code'] ?? null;
$currentStatus  = $shop['package_status'] ?? null;
$currentExpiry  = $shop['package_expired_at'] ?? null;

// =============================================
// DATA PAKET
// =============================================
$PACKAGES = [
    'A' => [
        'name' => 'Paket A',
        'price' => 25000,
        'limit' => 20,
        'duration_days' => 30
    ],
    'B' => [
        'name' => 'Paket B',
        'price' => 45000,
        'limit' => 50,
        'duration_days' => 30
    ],
];

// =============================================
// Handle pembelian paket
// =============================================
$action = $_POST['action'] ?? '';

if ($action === 'buy_package') {
    $code = $_POST['package_code'] ?? '';
    if (!isset($PACKAGES[$code])) {
        $_SESSION['flash'] = "Paket tidak valid.";
        header("Location: paket.php");
        exit;
    }

    if (!$shopActive) {
        $_SESSION['flash'] = "Toko belum disetujui admin. Paket tidak bisa diaktifkan.";
        header("Location: paket.php");
        exit;
    }

    $days = $PACKAGES[$code]['duration_days'];
    $start = date('Y-m-d H:i:s');
    $end   = date('Y-m-d H:i:s', strtotime("+{$days} days"));

    $stmt = $pdo->prepare("
        UPDATE shops
        SET package_code = ?, 
            package_status = 'active',
            package_started_at = ?,
            package_expired_at = ?
        WHERE id = ?
    ");

    $stmt->execute([$code, $start, $end, $shopId]);

    $_SESSION['flash'] = "Paket {$PACKAGES[$code]['name']} berhasil diaktifkan!";
    header("Location: paket.php");
    exit;
}

$EXTRA_CSS = ['seller/style-paket.css'];
require_once __DIR__ . '/../../includes/header.php';
?>

<style>
.paket-wrap{max-width:1000px;margin:20px auto;padding:16px}
.paket-title{font-size:1.8rem;font-weight:700;margin-bottom:10px}
.flash{background:#ecfeff;color:#164e63;padding:10px;border-radius:10px;margin-bottom:12px}
.pkg-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:20px;margin-top:20px}
.pkg-card{border:1px solid #dbe1ea;border-radius:14px;padding:20px;background:#fff;box-shadow:0 3px 6px rgba(0,0,0,0.06)}
.pkg-name{font-size:1.4rem;font-weight:700;margin-bottom:4px}
.pkg-price{color:#1e40af;font-size:1.2rem;font-weight:700;margin-bottom:10px}
.pkg-limit{font-size:.95rem;color:#475569;margin-bottom:10px}
.btn{display:inline-block;padding:10px 14px;border-radius:10px;text-decoration:none;text-align:center}
.btn-primary{background:#1e40af;color:white;font-weight:700}
.btn-disabled{background:#cbd5e1;color:#64748b;cursor:not-allowed}
.status-box{background:#f1f5f9;border:1px solid #dbe1ea;padding:12px;border-radius:12px;margin-top:10px;font-size:.95rem}
</style>

<div class="paket-wrap">

    <h1 class="paket-title">Pilih Paket Toko</h1>

    <?php if (!empty($_SESSION['flash'])): ?>
    <div class="flash">
        <?= e($_SESSION['flash']); unset($_SESSION['flash']); ?>
    </div>
    <?php endif; ?>

    <!-- STATUS PAKET SEKARANG -->
    <div class="status-box">
        <strong>Toko:</strong> <?= e($shop['name']) ?><br>
        <strong>Status Toko:</strong> 
        <?= $shopActive ? "<span style='color:#15803d;font-weight:700'>Aktif</span>" : "<span style='color:#dc2626;font-weight:700'>Menunggu Admin</span>" ?><br>

        <strong>Paket Saat Ini:</strong>
        <?php if ($currentPkg && isset($PACKAGES[$currentPkg])): ?>
            <?= e($PACKAGES[$currentPkg]['name']) ?>  
            (<?= e($currentStatus) ?>)
        <?php else: ?>
            <span style="color:#475569">Belum ada paket</span>
        <?php endif; ?>

        <?php if ($currentExpiry): ?>
            <br><strong>Berlaku hingga:</strong> <?= date('d M Y', strtotime($currentExpiry)) ?>
        <?php endif; ?>
    </div>

    <div class="pkg-grid">
        <?php foreach ($PACKAGES as $code => $pkg): ?>
        <div class="pkg-card">
            <div class="pkg-name"><?= e($pkg['name']) ?></div>
            <div class="pkg-price">Rp<?= number_format($pkg['price'], 0, ',', '.') ?></div>
            <div class="pkg-limit">
                • Limit Produk: <b><?= $pkg['limit'] ?> produk</b><br>
                • Durasi: <?= $pkg['duration_days'] ?> hari
            </div>

            <?php if (!$shopActive): ?>
                <button class="btn btn-disabled" disabled>Toko belum aktif</button>

            <?php elseif ($currentPkg === $code && $currentStatus === 'active'): ?>
                <button class="btn btn-disabled" disabled>Sudah aktif</button>

            <?php else: ?>
            <form method="post">
                <input type="hidden" name="action" value="buy_package">
                <input type="hidden" name="package_code" value="<?= e($code) ?>">
                <button class="btn btn-primary" type="submit">
                    Aktifkan Paket
                </button>
            </form>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>

</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
</main>
</body>
</html>
