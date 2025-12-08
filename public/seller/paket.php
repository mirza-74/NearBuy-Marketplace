<?php
// ===============================================================
// NearBuy – Halaman Pilih Paket Langganan
// File: /seller/paket.php
// ===============================================================
declare(strict_types=1);

// Includes
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/db.php';

$BASE = '/NearBuy-Marketplace/public';
$BASE_SELLER = $BASE . '/seller';

// helper escape
if (!function_exists('e')) {
    function e(string $s): string {
        return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
    }
}

// ===============================================================
// 1. VALIDASI LOGIN & AMBIL DATA TOKO
// ===============================================================

$user = $_SESSION['user'] ?? null;
if (!$user || empty($user['id'])) {
    header('Location: ' . $BASE . '/login.php');
    exit;
}

$userId = (int)$user['id'];

$shop    = null;
$hasShop = false;

try {
    // ambil semua kolom supaya aman
    $stmt = $pdo->prepare("SELECT * FROM shops WHERE user_id = ? LIMIT 1");
    $stmt->execute([$userId]);
    $shop    = $stmt->fetch(PDO::FETCH_ASSOC);
    $hasShop = $shop ? true : false;
} catch (Throwable $e) {
    $shop    = null;
    $hasShop = false;
}

// ===============================================================
// 2. VALIDASI KONDISI TOKO (REDIRECTS)
// ===============================================================

// kalau belum punya toko, balik ke halaman toko
if (!$hasShop) {
    header('Location: ' . $BASE_SELLER . '/toko.php');
    exit;
}

$isActiveShop = (int)($shop['is_active'] ?? 0) === 1;

// konsisten dengan toko.php -> pakai package_status
$pkgStatus = $shop['package_status'] ?? null;

// jika toko belum aktif, atau paket sudah aktif / sedang menunggu pembayaran,
// tidak boleh pilih paket lagi -> balik ke toko
if (
    !$isActiveShop ||
    $pkgStatus === 'active' ||
    $pkgStatus === 'waiting_payment'
) {
    header('Location: ' . $BASE_SELLER . '/toko.php');
    exit;
}

// ===============================================================
// 3. LOGIKA PAKET & POST
// ===============================================================

$packages = [
    1 => ['name' => 'Paket Starter',  'price' => 20000, 'limit' => 15,   'desc' => 'Untuk Seller Baru'],
    2 => ['name' => 'Paket Premium',  'price' => 50000, 'limit' => 9999, 'desc' => 'Produk Tanpa Batas'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['package_id'])) {
    $packageId = (int)$_POST['package_id'];

    if (isset($packages[$packageId])) {
        // simpan info paket + id toko ke session untuk halaman upload_payment.php
        $_SESSION['subscription_plan']    = $packages[$packageId] + ['id' => $packageId];
        $_SESSION['subscription_shop_id'] = (int)$shop['id'];

        header('Location: ' . $BASE_SELLER . '/upload_payment.php');
        exit;
    }
}

// CSS extra kalau mau melalui header
if (!isset($EXTRA_CSS) || !is_array($EXTRA_CSS)) {
    $EXTRA_CSS = [];
}
$EXTRA_CSS[] = 'seller/style_seller.css';

require_once __DIR__ . '/../../includes/header.php';
?>
<div class="nb-shell">
    <section class="nb-card nb-main-card" style="max-width: 600px; margin: auto;">
        <h1 class="nb-title">Pilih Paket Langganan</h1>
        <p class="nb-sub">
            Anda belum berlangganan. Silakan pilih paket untuk dapat mengelola produk Anda.
        </p>

        <div class="nb-packages-grid" style="display: flex; gap: 20px; margin-top: 20px;">
            <?php foreach ($packages as $id => $p): ?>
                <div class="package-card" style="flex: 1; border: 1px solid #e5e7eb; border-radius: 12px; padding: 20px;">
                    <h3 style="color: #1e3a8a;"><?= e($p['name']) ?></h3>
                    <p style="font-size: 24px; font-weight: 700;">
                        Rp<?= number_format($p['price'], 0, ',', '.') ?>
                        <span style="font-size: 14px; color: #6b7280;">/ bulan</span>
                    </p>
                    <ul style="list-style: disc; margin-left: 20px; padding: 0;">
                        <li>Batas <?= $p['limit'] === 9999 ? 'Tanpa Batas' : (int)$p['limit'] ?> Produk</li>
                        <li>Akses Kelola Produk</li>
                    </ul>
                    <form method="post" style="margin-top: 15px;">
                        <input type="hidden" name="package_id" value="<?= $id ?>">
                        <button type="submit" class="nb-btn nb-btn-primary" style="width: 100%;">Pilih Paket</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
