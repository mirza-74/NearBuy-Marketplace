<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/db.php';

$BASE = '/NearBuy-Marketplace/public';

$error   = '';
$success = false;

if (!function_exists('h')) {
    function h(?string $s): string {
        return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
    }
}

// Wajib login sebagai user biasa dulu
$user = $_SESSION['user'] ?? null;
if (!$user || empty($user['id'])) {
    header('Location: ' . $BASE . '/login.php');
    exit;
}

$userId = (int)$user['id'];

// Cek apakah user sudah punya toko
try {
    $checkShop = $pdo->prepare("SELECT id FROM shops WHERE user_id = ? LIMIT 1");
    $checkShop->execute([$userId]);
    $existingShop = $checkShop->fetch(PDO::FETCH_ASSOC);

    if ($existingShop) {
        // Kalau sudah punya toko, langsung arahkan ke halaman toko
        header('Location: ' . $BASE . '/seller/toko.php');
        exit;
    }
} catch (Throwable $e) {
    // kalau error, biarkan user tetap bisa lihat form (tapi nanti bisa tampilkan pesan kalau mau)
}

// nilai lama untuk refill form
$old = [
    'name'        => '',
    'address'     => '',
    'latitude'    => '',
    'longitude'   => '',
    'description' => '',
];

// PROSES SUBMIT FORM
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // CSRF jika tersedia
    if (function_exists('csrf_verify')) {
        $token = $_POST['csrf'] ?? '';
        if (!csrf_verify($token)) {
            $error = 'Sesi formulir sudah habis. Silakan muat ulang halaman dan coba lagi.';
        }
    }

    foreach ($old as $k => $_) {
        $old[$k] = trim((string)($_POST[$k] ?? ''));
    }

    if ($error === '') {

        // Validasi dasar
        if ($old['name'] === '' || $old['address'] === '' || $old['latitude'] === '' || $old['longitude'] === '') {
            $error = 'Nama toko, alamat, dan koordinat lokasi wajib diisi.';
        } elseif (!is_numeric($old['latitude']) || !is_numeric($old['longitude'])) {
            $error = 'Latitude dan longitude harus berupa angka (format desimal).';
        } else {
            try {
                if (!isset($pdo) || !($pdo instanceof PDO)) {
                    throw new RuntimeException('Koneksi database tidak tersedia.');
                }

                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

                $pdo->beginTransaction();

                // Insert ke tabel shops
                $stmt = $pdo->prepare("
                    INSERT INTO shops
                        (user_id, name, address, latitude, longitude, description, is_active)
                    VALUES
                        (?, ?, ?, ?, ?, ?, 0)
                ");

                $stmt->execute([
                    $userId,
                    $old['name'],
                    $old['address'],
                    (float)$old['latitude'],
                    (float)$old['longitude'],
                    $old['description'] ?: null,
                ]);

                $pdo->commit();
                $success = true;

                // kosongkan form
                foreach ($old as $k => $_) {
                    $old[$k] = '';
                }

            } catch (Throwable $e) {
                if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $error = 'Terjadi kesalahan saat menyimpan data toko. Silakan coba lagi.';
                if (defined('DEBUG') && DEBUG) {
                    $error .= ' [DEBUG: ' . $e->getMessage() . ']';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi Toko | NearBuy</title>
    <!-- Pakai style-auth yang sama dengan login/registrasi user -->
    <link rel="stylesheet" href="<?= h($BASE) ?>/style-auth.css">
    <!-- Kalau kamu punya CSS tambahan khusus seller, bisa tambahkan di sini -->
    <!-- <link rel="stylesheet" href="<?= h($BASE) ?>/seller/style-toko.css"> -->
</head>
<body>

<div class="container">

    <!-- LEFT PANEL -->
    <div class="left-panel">
        <img src="<?= h($BASE) ?>/assets/logo_nearbuy.png" alt="NearBuy" class="logo">
        <p class="slogan">
            “Buka toko di NearBuy dan jangkau pembeli terdekat di sekitar domisili kamu.”
        </p>
    </div>

    <!-- RIGHT PANEL -->
    <div class="right-panel">
        <div class="form-container">

            <h2>Registrasi Toko NearBuy</h2>

            <?php if ($error): ?>
                <div class="alert alert-error" role="alert">
                    <?= h($error) ?>
                </div>
            <?php endif; ?>

            <?php if (!$success): ?>
            <form method="POST" autocomplete="off" novalidate>
                <?php if (function_exists('csrf_token')): ?>
                    <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
                <?php endif; ?>

                <!-- Info pemilik (readonly) -->
                <label>Pemilik Akun</label>
                <input type="text" value="<?= h($user['full_name'] ?? '') ?>" readonly>

                <label>Email Akun</label>
                <input type="email" value="<?= h($user['email'] ?? '') ?>" readonly>

                <!-- Data toko -->
                <label for="name">Nama Toko *</label>
                <input id="name" type="text" name="name"
                       value="<?= h($old['name']) ?>" placeholder="Contoh: Warung Harian Makmur" required>

                <label for="address">Alamat Lengkap Toko *</label>
                <textarea id="address" name="address"
                          placeholder="Contoh: Jl. Mawar No. 10, RT 02 / RW 05, Pangkalpinang"
                          style="min-height:70px; resize:vertical;"><?= h($old['address']) ?></textarea>

                <div class="two-columns">
                    <div class="input-box">
                        <label for="latitude">Latitude *</label>
                        <input id="latitude" type="text" name="latitude"
                               placeholder="-2.1291000"
                               value="<?= h($old['latitude']) ?>" required>
                    </div>
                    <div class="input-box">
                        <label for="longitude">Longitude *</label>
                        <input id="longitude" type="text" name="longitude"
                               placeholder="106.1090000"
                               value="<?= h($old['longitude']) ?>" required>
                    </div>
                </div>

                <small style="display:block;margin:-6px 0 12px;font-size:12px;color:#6b7280;">
                    *Kamu bisa mengambil titik koordinat dari Google Maps lalu menyalin latitude & longitude ke sini.
                </small>

                <label for="description">Deskripsi Toko (opsional)</label>
                <textarea id="description" name="description"
                          placeholder="Contoh: Menyediakan gas elpiji, air galon, dan kebutuhan harian area Pangkalpinang.">
                    <?= h($old['description']) ?>
                </textarea>

                <small style="display:block;margin-top:4px;font-size:12px;color:#6b7280;">
                    Dengan mendaftarkan toko, kamu menyatakan telah membaca dan menyetujui
                    <a href="<?= h($BASE) ?>/seller/peraturan_toko.php" style="color:#1d75ff;">Peraturan Toko NearBuy</a>.
                </small>

                <button type="submit">Daftarkan Toko</button>
            </form>
            <?php endif; ?>

            <div class="register-text">
                <a href="<?= h($BASE) ?>/seller/toko.php">Kembali ke halaman Toko Saya</a>
            </div>

        </div>
    </div>

</div>

<?php if ($success): ?>
<div class="modal-mask" role="dialog" aria-modal="true">
    <div class="modal-card">
        <h3>Toko Berhasil Didaftarkan 🎉</h3>
        <p>
            Data toko kamu sudah tersimpan dan akan diperiksa oleh admin NearBuy.
            Setelah disetujui, toko kamu akan aktif dan muncul di pencarian pembeli terdekat.
        </p>
        <div class="modal-actions">
            <a href="<?= h($BASE) ?>/seller/toko.php" class="btn btn-primary">Lihat Toko Saya</a>
            <a href="<?= h($BASE) ?>/index.php" class="btn btn-secondary">Kembali ke Beranda</a>
        </div>
    </div>
</div>
<?php endif; ?>

</body>
</html>
