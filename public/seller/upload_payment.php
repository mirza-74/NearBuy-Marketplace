<?php
// ===============================================================
// NearBuy – Halaman Unggah Bukti Pembayaran (FINAL FIXED VERSION)
// File: /public/seller/upload_payment.php
// ===============================================================
declare(strict_types=1);

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/db.php';

// BASE otomatis: buang "/seller" kalau ada di ujung
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$BASE = preg_replace('~/seller$~', '', rtrim($scriptDir, '/'));

// helper escape
if (!function_exists('e')) {
    function e(string $s): string {
        return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
    }
}

// ===============================================================
// 1. VALIDASI LOGIN & AMBIL TOKO
// ===============================================================
$user = $_SESSION['user'] ?? null;
if (!$user || empty($user['id'])) {
    header("Location: $BASE/login.php");
    exit;
}

$userId = (int)$user['id'];

$stmt = $pdo->prepare("SELECT id FROM shops WHERE user_id = ? LIMIT 1");
$stmt->execute([$userId]);
$shop = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$shop) {
    // user belum punya toko
    header("Location: $BASE/seller/toko.php");
    exit;
}

// ===============================================================
// 2. VALIDASI DATA PAKET DI SESSION
// ===============================================================
$package           = $_SESSION['subscription_plan']    ?? null;
$shopIdToSubscribe = $_SESSION['subscription_shop_id'] ?? null;

if (
    !$package ||
    !$shop ||
    (int)$shop['id'] !== (int)$shopIdToSubscribe
) {
    // data sesi tidak valid → kembali ke pilih paket
    header("Location: $BASE/seller/paket.php");
    exit;
}

// ===============================================================
// 3. PROSES UPLOAD BUKTI PEMBAYARAN
// ===============================================================
$successMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_FILES['bukti_bayar'])) {

    $file = $_FILES['bukti_bayar'];
    $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    $allowedExt  = ['jpg', 'jpeg', 'png'];
    $maxFileSize = 2 * 1024 * 1024; // 2MB

    if (!in_array($ext, $allowedExt, true)) {
        $successMessage = 'Format file harus JPG atau PNG.';
    } elseif ($file['size'] > $maxFileSize) {
        $successMessage = 'Ukuran file maksimal 2MB.';
    } elseif ($file['error'] !== UPLOAD_ERR_OK) {
        $successMessage = 'Terjadi error saat upload file.';
    } else {
        // folder upload: /public/uploads/payment_proofs
        $uploadDir = __DIR__ . '/../../uploads/payment_proofs/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // nama file unik: shopId_timestamp.ext
        $fileName       = $shop['id'] . '_' . time() . '.' . $ext;
        $filePathServer = $uploadDir . $fileName;
        // path relatif yang bisa diakses browser
        $relativeFilePath = $BASE . '/uploads/payment_proofs/' . $fileName;

        if (move_uploaded_file($file['tmp_name'], $filePathServer)) {
            try {
                // UPDATE shops sesuai struktur tabel kamu
                $stmt = $pdo->prepare("
                    UPDATE shops SET
                        package_status      = 'waiting_payment',
                        subscription_status = 'waiting_payment',
                        last_payment_proof  = :proof,
                        package_code        = :pkg,
                        product_limit       = :limit,
                        updated_at          = NOW()
                    WHERE id = :id
                ");

                $stmt->execute([
                    ':proof' => $relativeFilePath,
                    ':pkg'   => $package['name'],   // "Paket Starter" / "Paket Premium"
                    ':limit' => (int)$package['limit'],
                    ':id'    => $shop['id'],
                ]);

                // Hapus data paket di session (supaya tidak dobel submit)
                unset($_SESSION['subscription_plan'], $_SESSION['subscription_shop_id']);

                $successMessage =
                    'Bukti pembayaran berhasil diunggah! Admin akan memverifikasi dalam 1x24 jam.<br>' .
                    'Kamu akan diarahkan ke halaman Toko Saya.';

                // Redirect pelan2 balik ke toko
                header("Refresh: 5; url=$BASE/seller/toko.php");

            } catch (Throwable $e) {
                // kalau gagal simpan ke DB, hapus file yang sudah terupload
                if (file_exists($filePathServer)) {
                    unlink($filePathServer);
                }
                $successMessage = 'Gagal menyimpan data ke database.<br>Error: ' . e($e->getMessage());
            }
        } else {
            $successMessage = 'Gagal memindahkan file ke server. Periksa izin folder uploads/payment_proofs.';
        }
    }
}

// ===============================================================
// TAMPILAN HTML
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
        Paket dipilih:
        <b><?= e($package['name']) ?></b> —
        Rp<?= number_format((float)$package['price'], 0, ',', '.') ?>/bulan
      </p>

      <p class="nb-sub">
        Silakan transfer ke rekening berikut dan unggah bukti pembayarannya:
      </p>

      <div class="nb-bank-box">
        <p class="bank-label">Bank Tujuan:</p>
        <p class="bank-detail">Bank ABC: 123456789</p>
        <p class="bank-footer">(a.n. NearBuy)</p>
      </div>

      <form method="post" enctype="multipart/form-data">
        <div class="nb-field file-input-wrapper">
          <label class="nb-label">Unggah Bukti Pembayaran (JPG/PNG)</label>

          <!-- input asli (disembunyikan oleh CSS) -->
          <input
            type="file"
            id="bukti_bayar"
            name="bukti_bayar"
            required
            accept="image/jpeg,image/png"
          >

          <!-- tombol custom yang kelihatan -->
          <label for="bukti_bayar" class="file-input-custom">
            Pilih File
          </label>

          <span id="file-name">Tidak ada file dipilih.</span>
        </div>

        <button type="submit" class="nb-btn nb-btn-primary nb-action-full">
          Konfirmasi Pembayaran
        </button>
      </form>

    <?php endif; ?>

  </section>
</div>

<script>
  const fileInput  = document.getElementById('bukti_bayar');
  const fileNameEl = document.getElementById('file-name');

  if (fileInput && fileNameEl) {
    fileInput.addEventListener('change', function () {
      if (this.files && this.files.length > 0) {
        fileNameEl.textContent = 'File terpilih: ' + this.files[0].name;
      } else {
        fileNameEl.textContent = 'Tidak ada file dipilih.';
      }
    });
  }
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
