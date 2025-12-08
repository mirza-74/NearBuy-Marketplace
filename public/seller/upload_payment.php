<?php
// ===============================================================
// NearBuy – Halaman Unggah Bukti Pembayaran (FINAL FIXED VERSION)
// ===============================================================
declare(strict_types=1);

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/db.php';

// BASE otomatis
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$BASE = preg_replace('~/seller$~', '', rtrim($scriptDir, '/'));

// escape
if (!function_exists('e')) {
    function e(string $s): string {
        return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
    }
}

// ===============================================================
// 1. VALIDASI LOGIN
// ===============================================================
$user = $_SESSION['user'] ?? null;
if (!$user || empty($user['id'])) {
    header("Location: $BASE/login.php");
    exit;
}

$userId = (int)$user['id'];

// AMBIL DATA TOKO
$stmt = $pdo->prepare("SELECT id FROM shops WHERE user_id = ? LIMIT 1");
$stmt->execute([$userId]);
$shop = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$shop) {
    header("Location: $BASE/seller/toko.php");
    exit;
}

// ===============================================================
// 2. VALIDASI DATA PAKET DI SESSION
// ===============================================================
$package = $_SESSION['subscription_plan'] ?? null;
$shopIdToSubscribe = $_SESSION['subscription_shop_id'] ?? null;

if (!$package || (int)$shop['id'] !== (int)$shopIdToSubscribe) {
    header("Location: $BASE/seller/paket.php");
    exit;
}

// ===============================================================
// 3. PROSES UPLOAD
// ===============================================================
$successMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_FILES['bukti_bayar'])) {

    $file = $_FILES['bukti_bayar'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowedExt = ['jpg', 'jpeg', 'png'];

    if (!in_array($ext, $allowedExt, true)) {
        $successMessage = "Format file harus JPG/PNG.";
    } elseif ($file['size'] > 2 * 1024 * 1024) {
        $successMessage = "Ukuran file maksimal 2MB.";
    } elseif ($file['error'] !== UPLOAD_ERR_OK) {
        $successMessage = "Terjadi error saat upload file.";
    } else {

        // folder
        $uploadDir = __DIR__ . '/../../uploads/payment_proofs/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

        $fileName = $shop['id'] . '_' . time() . ".$ext";
        $filePathServer = $uploadDir . $fileName;
        $relativeFilePath = $BASE . "/uploads/payment_proofs/" . $fileName;

        if (move_uploaded_file($file['tmp_name'], $filePathServer)) {

            try {
                // ===============================================================
                // UPDATE DATABASE SESUAI STRUKTUR KAMU (package_status)
                // ===============================================================
                $stmt = $pdo->prepare("
                    UPDATE shops SET 
                        package_status      = 'waiting_payment',
                        last_payment_proof  = :proof,
                        package_code        = :pkg,
                        product_limit       = :limit,
                        updated_at          = NOW()
                    WHERE id = :id
                ");

                $stmt->execute([
                    ':proof' => $relativeFilePath,
                    ':pkg'   => $package['name'],
                    ':limit' => (int)$package['limit'],
                    ':id'    => $shop['id']
                ]);

                // hapus session
                unset($_SESSION['subscription_plan'], $_SESSION['subscription_shop_id']);

                $successMessage =
                    "Bukti pembayaran berhasil diunggah! Admin akan memverifikasi.<br>
                     Kamu akan diarahkan ke halaman toko dalam 5 detik.";

                header("Refresh: 5; url=$BASE/seller/toko.php");

            } catch (Throwable $e) {

                if (file_exists($filePathServer)) unlink($filePathServer);

                $successMessage = "Gagal menyimpan data ke database.<br>Error: " . $e->getMessage();
            }

        } else {
            $successMessage = "Gagal memindahkan file ke server.";
        }
    }
}

// ===============================================================
require_once __DIR__ . '/../../includes/header.php';
?>

<link rel="stylesheet" href="style_seller.css">

<div class="nb-shell">
    <section class="nb-card nb-main-card" style="max-width:600px;margin:auto;">

    <h1 class="nb-title">Pembayaran Langganan</h1>

    <?php if ($successMessage): ?>

        <p class="nb-success-msg"><?= $successMessage ?></p>

    <?php else: ?>

        <p class="nb-sub">
            Paket dipilih: <b><?= e($package['name']) ?></b> — 
            Rp<?= number_format($package['price'], 0, ',', '.') ?>/bulan
        </p>

        <div class="nb-bank-box">
            <p class="bank-label">Bank Tujuan:</p>
            <p class="bank-detail">Bank ABC: 123456789</p>
            <p class="bank-footer">(a.n NearBuy)</p>
        </div>

        <form method="post" enctype="multipart/form-data">

            <label class="nb-label">Unggah Bukti Pembayaran</label>
            <input type="file" name="bukti_bayar" required accept="image/jpeg,image/png">

            <button type="submit" class="nb-btn nb-btn-primary nb-action-full">
                Konfirmasi Pembayaran
            </button>

        </form>

    <?php endif; ?>

    </section>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
