<?php
// ===============================================================
// NearBuy – Halaman Pilih Paket Langganan
// File: /seller/pilih_paket.php
// ===============================================================
declare(strict_types=1);

// Includes Wajib
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/db.php';

// BASE: otomatis, buang "/seller" di ujung
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$BASE = preg_replace('~/seller$~', '', rtrim($scriptDir, '/'));

// helper escape
if (!function_exists('e')) {
    function e(string $s): string {
        return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
    }
}

// ===============================================================
// 1. VALIDASI LOGIN & AMBIL DATA TOKO
// ===============================================================

// wajib login dulu
$user = $_SESSION['user'] ?? null;
if (!$user || empty($user['id'])) {
    header('Location: ' . $BASE . '/login.php');
    exit;
}

$userId = (int)$user['id'];

// Ambil data toko milik user (1 user 1 toko)
$shop    = null;
$hasShop = false;

try {
    $stmt = $pdo->prepare("
        SELECT 
          id, 
          name, 
          address, 
          latitude, 
          longitude, 
          is_active, 
          subscription_status, 
          product_limit, 
          created_at
        FROM shops
        WHERE user_id = ?
        LIMIT 1
    ");
    $stmt->execute([$userId]);
    $shop    = $stmt->fetch(PDO::FETCH_ASSOC);
    $hasShop = $shop ? true : false;
} catch (Throwable $e) {
    // Biarkan $shop null jika error
}

// ===============================================================
// 2. VALIDASI KONDISI TOKO (REDIRECTS)
// ===============================================================

// Redirect jika user belum punya toko
if (!$hasShop) {
    header('Location: ' . $BASE . '/seller/toko.php');
    exit;
}

// Cek status toko (harus aktif) dan status langganan
$isActiveShop = !empty($shop['is_active']);
$isSubscribed = ($shop['subscription_status'] ?? 'free') === 'active';
$isPendingPayment = ($shop['subscription_status'] ?? '') === 'pending_payment';

if (!$isActiveShop || $isSubscribed || $isPendingPayment) {
    // Redirect jika:
    // a. Toko belum aktif (menunggu persetujuan)
    // b. Sudah aktif berlangganan
    // c. Sedang menunggu verifikasi pembayaran
    header('Location: ' . $BASE . '/seller/toko.php');
    exit;
}

require_once __DIR__ . '/../../includes/header.php';



// ===============================================================
// 3. LOGIKA PAKET & POST
// ===============================================================

// Data Paket
$packages = [
    1 => ['name' => 'Paket Starter', 'price' => 20000, 'limit' => 15, 'desc' => 'Untuk Seller Baru'],
    2 => ['name' => 'Paket Premium', 'price' => 50000, 'limit' => 9999, 'desc' => 'Produk Tanpa Batas'],
];

// Proses Pemilihan Paket
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['package_id'] ?? 0)) {
    $packageId = (int)($_POST['package_id']);
    
    if (isset($packages[$packageId])) {
        // Simpan pilihan paket dan ID toko ke session
        $_SESSION['subscription_plan'] = $packages[$packageId];
        $_SESSION['subscription_shop_id'] = $shop['id']; 

        // Redirect ke halaman upload bukti pembayaran
        header('Location: ' . $BASE . '/seller/upload_payment.php');
        exit;
    }
}
?>
<link rel="stylesheet" href="style_seller.css">
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
                    Rp<?= number_format($p['price'], 0, ',', '.') ?> <span style="font-size: 14px; color: #6b7280;">/ bulan</span>
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