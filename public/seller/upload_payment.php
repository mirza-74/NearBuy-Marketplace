<?php
// ===============================================================
// NearBuy – Halaman Unggah Bukti Pembayaran
// File: /seller/upload_payment.php
// ===============================================================
$successMessage = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_FILES['bukti_bayar'])) {
    
    $file = $_FILES['bukti_bayar'];
    $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    // 1. Validasi File
    $allowedExt = ['jpg', 'jpeg', 'png'];
    $maxFileSize = 2 * 1024 * 1024; // 2 MB

    if (!in_array($fileExt, $allowedExt)) {
        $successMessage = 'Gagal: Hanya format JPG atau PNG yang diperbolehkan.';
    } elseif ($file['size'] > $maxFileSize) {
        $successMessage = 'Gagal: Ukuran file melebihi batas 2MB.';
    } elseif ($file['error'] !== UPLOAD_ERR_OK) {
        $successMessage = 'Gagal: Terjadi error saat mengunggah file.';
    } else {
        // --- Proses Upload File dan Update DB ---
        
        // 2. Definisikan folder tujuan upload
        // Asumsi struktur: /public/uploads/payment_proofs/
        $uploadDir = __DIR__ . '/../../uploads/payment_proofs/'; 
        
        if (!is_dir($uploadDir)) {
            // Buat folder jika belum ada (pastikan folder /public bisa ditulis)
            mkdir($uploadDir, 0777, true); 
        }
        
        // 3. Buat nama file unik: shopID_timestamp.ext
        $fileName = $shop['id'] . '_' . time() . '.' . $fileExt;
        $filePathServer = $uploadDir . $fileName; 
        
        // Path Relatif untuk disimpan di DB (Path ini yang diakses Admin)
        $relativeFilePath = $BASE . '/uploads/payment_proofs/' . $fileName; 

        // 4. Pindahkan file
        if (move_uploaded_file($file['tmp_name'], $filePathServer)) {
            try {
                // 5. Update Database: Menggunakan kolom 'last_payment_proof' dan 'package_code'
                $stmt = $pdo->prepare("
                    UPDATE shops SET 
                        subscription_status = 'pending_payment',
                        last_payment_proof = ?,       /* MENGGUNAKAN NAMA KOLOM DARI DB ANDA */
                        package_code = ?,             /* MENGGUNAKAN NAMA KOLOM DARI DB ANDA */
                        updated_at = NOW()
                    WHERE id = ?
                ");
                
                $stmt->execute([
                    $relativeFilePath,      
                    $package['name'],       // Kita simpan nama paket ke kolom package_code
                    $shop['id']
                ]);

                // Berhasil: hapus session dan tampilkan pesan sukses
                unset($_SESSION['subscription_plan']);
                unset($_SESSION['subscription_shop_id']);
                $successMessage = 'Bukti pembayaran berhasil diunggah. Kami akan memprosesnya dalam 1x24 jam. Anda akan diarahkan kembali ke dashboard toko.';
                header("Refresh: 5; url=" . $BASE . "/seller/toko.php");

            } catch (Throwable $e) {
                 // Error DB
                 $successMessage = 'Gagal menyimpan data pembayaran ke database. ' . $e->getMessage();
                 // Hapus file yang sudah terlanjur diupload jika error DB
                 if (file_exists($filePathServer)) { unlink($filePathServer); }
            }
        } else {
            // Error move file (izin folder)
            $successMessage = 'Gagal memindahkan file ke server. Periksa izin folder (/uploads/payment_proofs/).';
        }
    }
}
require_once __DIR__ . '/../../includes/header.php';

?>

<link rel="stylesheet" href="style_seller.css"> 

<div class="nb-shell">
    <section class="nb-card nb-main-card" style="max-width: 600px; margin: auto;"> 
        <h1 class="nb-title">Pembayaran Langganan</h1>
        
        <?php if ($successMessage): ?>
            <p class="nb-success-msg">
                <?= $successMessage ?>
            </p>
        <?php else: ?>

            <p class="nb-sub">
                Paket Pilihan: 
                <b><?= e($package['name']) ?></b> 
                (Rp<?= number_format($package['price'], 0, ',', '.') ?> / bulan)
            </p>

            <p class="nb-sub">
                Silakan transfer ke rekening berikut dan unggah bukti pembayarannya:
            </p>

            <div class="nb-bank-box">
                <p class="bank-label">Bank Tujuan:</p> 
                <p class="bank-detail">
                    Bank ABC: 123456789
                </p>
                <p class="bank-footer">(a.n. NearBuy)</p>
            </div>
            
            <form method="post" enctype="multipart/form-data">
                <div class="nb-field file-input-wrapper">
                    <label class="nb-label">Unggah Bukti Pembayaran (JPG/PNG)</label>
                    
                    <input type="file" id="bukti_bayar" name="bukti_bayar" required accept="image/jpeg, image/png">
                    
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
    document.getElementById('bukti_bayar').addEventListener('change', function() {
        const fileNameSpan = document.getElementById('file-name');
        if (this.files.length > 0) {
            fileNameSpan.textContent = 'File terpilih: ' + this.files[0].name;
        } else {
            fileNameSpan.textContent = 'Tidak ada file dipilih.';
        }
    });
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>