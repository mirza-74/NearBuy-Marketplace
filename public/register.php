<?php
declare(strict_types=1);

// ========================
// CONFIG & ERROR HANDLING
// ========================
const DEBUG = false; // set true kalau mau lihat error detail
if (DEBUG) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
}

// ========================
// INCLUDES
// ========================
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';

// Base URL (sesuaikan dengan folder kamu)
$BASE = '/NearBuy-Marketplace/public';

// ========================
// STATE
// ========================
$error   = '';
$success = false;

$old = [
    'full_name'   => '',
    'email'       => '',
    'phone'       => '',
    'gender'      => '',
    'birth_date'  => '',
    'address'     => '',
    'city'        => '',
    'province'    => '',
    'postal_code' => '',
];

// ========================
// FORM SUBMISSION
// ========================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // CSRF check kalau fungsi tersedia
    if (function_exists('csrf_verify')) {
        $token = $_POST['csrf'] ?? '';
        if (!csrf_verify($token)) {
            $error = 'Sesi formulir sudah habis. Silakan muat ulang halaman dan coba lagi.';
        }
    }

    // Ambil input
    foreach ($old as $key => $_) {
        $old[$key] = trim((string)($_POST[$key] ?? ''));
    }

    $password = trim((string)($_POST['password'] ?? ''));
    $confirm  = trim((string)($_POST['confirm'] ?? ''));

    if ($error === '') {

        // ---------- VALIDASI ----------
        if (
            $old['full_name'] === '' ||
            $old['email'] === '' ||
            $password === '' ||
            $confirm === ''
        ) {
            $error = 'Nama lengkap, email, dan password wajib diisi.';
        } elseif (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
            $error = 'Format email tidak valid.';
        } elseif (strlen($password) < 6) {
            $error = 'Password minimal 6 karakter.';
        } elseif ($password !== $confirm) {
            $error = 'Konfirmasi password tidak sama.';
        } elseif ($old['city'] === '') {
            // domisili minimal ada kota
            $error = 'Harap isi kota domisili agar rekomendasi produk bisa disesuaikan dengan lokasi kamu.';
        } else {
            // ---------- SIMPAN KE DATABASE ----------
            try {
                if (!isset($pdo) || !($pdo instanceof PDO)) {
                    throw new RuntimeException('Koneksi database tidak tersedia.');
                }

                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

                // Cek apakah email sudah terdaftar
                $check = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
                $check->execute([$old['email']]);

                if ($check->fetch()) {
                    $error = 'Email sudah terdaftar. Silakan gunakan email lain atau login.';
                } else {

                    $pdo->beginTransaction();

                    $hash = password_hash($password, PASSWORD_DEFAULT);

                    /**
                     * Tabel users kamu (dari SQL dump):
                     *  - full_name (NOT NULL)
                     *  - email (NOT NULL, unique)
                     *  - phone (NULL)
                     *  - gender (enum, NULL)
                     *  - birth_date (date, NULL)
                     *  - address, city, province, postal_code (NULL)
                     *  - password_hash (NOT NULL)
                     *  - role (enum, default 'pengguna')
                     *  - is_active (default 1)
                     *  - created_at (default current_timestamp)
                     *  - updated_at, points, latitude, longitude (punya default / NULL)
                     *
                     * Jadi cukup insert field-field di bawah, sisanya pakai default.
                     */

                    $insert = $pdo->prepare("
                        INSERT INTO users 
                            (full_name, email, phone, gender, birth_date, address, city, province, postal_code, password_hash)
                        VALUES 
                            (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");

                    $birthDate = $old['birth_date'] !== '' ? $old['birth_date'] : null;

                    $insert->execute([
                        $old['full_name'],
                        $old['email'],
                        $old['phone'] ?: null,
                        $old['gender'] ?: null,
                        $birthDate,
                        $old['address'] ?: null,
                        $old['city'] ?: null,
                        $old['province'] ?: null,
                        $old['postal_code'] ?: null,
                        $hash
                    ]);

                    $pdo->commit();
                    $success = true;

                    // reset form
                    foreach ($old as $k => $_) {
                        $old[$k] = '';
                    }
                }

            } catch (Throwable $e) {
                if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $error = 'Terjadi kesalahan saat menyimpan data. Silakan coba lagi.';
                if (DEBUG) {
                    $error .= ' [DEBUG: ' . $e->getMessage() . ']';
                }
            }
        }
    }
}

// HTML Escape helper
function h(?string $s): string {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <title>Registrasi | NearBuy</title>

    <link rel="stylesheet" href="<?= h($BASE) ?>/style-auth.css?v=5">
</head>
<body>

<div class="container">

    <!-- LEFT PANEL -->
    <div class="left-panel">
        <img src="<?= h($BASE) ?>/assets/logo_nearbuy.png" alt="NearBuy" class="logo">
        <p class="slogan">“Menghubungkan pelanggan dengan produk terdekat di domisili kamu.”</p>
    </div>

    <!-- RIGHT PANEL -->
    <div class="right-panel">
        <div class="form-container">

            <h2>Registrasi Akun NearBuy</h2>

            <?php if ($error): ?>
                <div class="alert alert-error" role="alert">
                    <?= h($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" autocomplete="off" novalidate>
                <?php if (function_exists('csrf_token')): ?>
                    <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
                <?php endif; ?>

                <label for="full_name">Nama Lengkap *</label>
                <input id="full_name" type="text" name="full_name"
                       value="<?= h($old['full_name']) ?>" required>

                <label for="email">Email *</label>
                <input id="email" type="email" name="email"
                       value="<?= h($old['email']) ?>" required>

                <label for="phone">Nomor Telepon</label>
                <input id="phone" type="text" name="phone"
                       value="<?= h($old['phone']) ?>">

                <label for="password">Password *</label>
                <input id="password" type="password" name="password" required>

                <label for="confirm">Konfirmasi Password *</label>
                <input id="confirm" type="password" name="confirm" required>

                <div class="two-columns">
                    <div class="input-box">
                        <label for="gender">Jenis Kelamin</label>
                        <select id="gender" name="gender">
                            <option value="" <?= $old['gender']==='' ? 'selected' : '' ?>>- Pilih -</option>
                            <option value="male"   <?= $old['gender']==='male' ? 'selected' : '' ?>>Laki-laki</option>
                            <option value="female" <?= $old['gender']==='female' ? 'selected' : '' ?>>Perempuan</option>
                            <option value="other"  <?= $old['gender']==='other' ? 'selected' : '' ?>>Lainnya</option>
                        </select>
                    </div>
                    <div class="input-box">
                        <label for="birth_date">Tanggal Lahir</label>
                        <input id="birth_date" type="date" name="birth_date"
                               value="<?= h($old['birth_date']) ?>">
                    </div>
                </div>

                <label for="address">Alamat Lengkap (Domisili)</label>
                <textarea id="address" name="address"
                          placeholder="Contoh: Jl. Mawar No. 10, Kel. X, Kec. Y"><?= h($old['address']) ?></textarea>

                <label for="city">Kota Domisili *</label>
                <input id="city" type="text" name="city"
                       placeholder="Contoh: Pangkalpinang"
                       value="<?= h($old['city']) ?>" required>

                <label for="province">Provinsi</label>
                <input id="province" type="text" name="province"
                       placeholder="Contoh: Bangka Belitung"
                       value="<?= h($old['province']) ?>">

                <label for="postal_code">Kode Pos</label>
                <input id="postal_code" type="text" name="postal_code"
                       value="<?= h($old['postal_code']) ?>">

                <button type="submit">Daftar</button>
            </form>

            <div class="register-text">
                Sudah punya akun?
                <a href="<?= h($BASE) ?>/login.php">Masuk di sini</a>
            </div>

        </div>
    </div>
</div>

<!-- MODAL SUCCESS -->
<?php if ($success): ?>
<div class="modal-mask" role="dialog" aria-modal="true">
    <div class="modal-card">
        <h3>Akun Berhasil Dibuat 🎉</h3>
        <p>Selamat datang di NearBuy! Domisili kamu sudah tersimpan sehingga rekomendasi produk bisa menyesuaikan lokasi kamu.</p>
        <div class="modal-actions">
            <a href="<?= h($BASE) ?>/login.php" class="btn btn-primary">Login</a>
            <a href="<?= h($BASE) ?>/index.php" class="btn btn-secondary">Beranda</a>
        </div>
    </div>
</div>
<?php endif; ?>

</body>
</html>
