<?php
declare(strict_types=1);

// ========================
// CONFIG & ERROR HANDLING
// ========================
const DEBUG = false;
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

// Base URL
$BASE = '/NearBuy-Marketplace/public';

// ========================
// STATE
// ========================
$error   = '';
$success = false;

$old = [
    'full_name'    => '',
    'email'        => '',
    'phone'        => '',
    'gender'       => '',
    'age'          => '',
    'city'         => '',
    'fav_category' => '',
    'frequency'    => '',
    'budget'       => '',
];

// ========================
// FORM SUBMISSION
// ========================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    foreach ($old as $key => $_) {
        $old[$key] = trim((string)($_POST[$key] ?? ''));
    }

    $password = trim((string)($_POST['password'] ?? ''));
    $confirm  = trim((string)($_POST['confirm'] ?? ''));

    // --- VALIDATION ---
    if ($old['full_name'] === '' || $old['email'] === '' || $password === '' || $confirm === '') {
        $error = 'Harap isi semua kolom wajib (Nama, Email, Password).';
    } elseif (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter.';
    } elseif ($password !== $confirm) {
        $error = 'Kata sandi tidak cocok.';
    } else {

        try {
            if (!isset($pdo) || !($pdo instanceof PDO)) {
                throw new RuntimeException('Koneksi database tidak tersedia.');
            }

            // cek email
            $check = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
            $check->execute([$old['email']]);

            if ($check->fetch()) {
                $error = 'Email sudah digunakan.';
            } else {

                $pdo->beginTransaction();

                $hash = password_hash($password, PASSWORD_DEFAULT);

                $insert = $pdo->prepare("
                    INSERT INTO users 
                    (email, password, full_name, phone, gender, age, city, role, is_active, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'pengguna', 1, NOW())
                ");

                $insert->execute([
                    $old['email'],
                    $hash,
                    $old['full_name'],
                    $old['phone'] ?: null,
                    $old['gender'] ?: null,
                    $old['age'] !== '' ? (int)$old['age'] : null,
                    $old['city'] ?: null
                ]);

                $pdo->commit();
                $success = true;

                // reset inputs
                foreach ($old as $k => $_) {
                    $old[$k] = '';
                }
            }

        } catch (Throwable $e) {
            if ($pdo instanceof PDO && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = 'Terjadi kesalahan saat menyimpan data. Silakan coba lagi.';
            if (DEBUG) $error .= ' [DEBUG: ' . $e->getMessage() . ']';
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

    <link rel="stylesheet" href="<?= h($BASE) ?>/style-auth.css?v=3">
</head>
<body>

<div class="container">

    <!-- ==================== LEFT PANEL ==================== -->
    <div class="left-panel">
        <img src="<?= h($BASE) ?>/assets/logo_nearbuy.png" alt="NearBuy" class="logo">
        <p class="slogan">“Menghubungkan pelanggan dengan produk terdekat.”</p>
    </div>

    <!-- ==================== RIGHT PANEL ==================== -->
    <div class="right-panel">
        <div class="form-container">

            <h2>Registrasi Akun NearBuy</h2>

            <?php if ($error): ?>
                <div class="error" role="alert">
                    <?= h($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" autocomplete="off" novalidate>

                <label for="full_name">Nama Lengkap</label>
                <input id="full_name" type="text" name="full_name" 
                       value="<?= h($old['full_name']) ?>" required>

                <label for="email">Email</label>
                <input id="email" type="email" name="email"
                       value="<?= h($old['email']) ?>" required>

                <label for="phone">Nomor Telepon</label>
                <input id="phone" type="text" name="phone"
                       value="<?= h($old['phone']) ?>">

                <label for="password">Password</label>
                <input id="password" type="password" name="password" required>

                <label for="confirm">Konfirmasi Password</label>
                <input id="confirm" type="password" name="confirm" required>

                <label for="gender">Jenis Kelamin</label>
                <select id="gender" name="gender">
                    <option value="" <?= $old['gender']==='' ? 'selected' : '' ?>>- Pilih -</option>
                    <option value="male"   <?= $old['gender']==='male' ? 'selected' : '' ?>>Laki-laki</option>
                    <option value="female" <?= $old['gender']==='female' ? 'selected' : '' ?>>Perempuan</option>
                    <option value="other"  <?= $old['gender']==='other' ? 'selected' : '' ?>>Lainnya</option>
                </select>

                <label for="age">Usia</label>
                <input id="age" type="number" name="age" min="10" max="100"
                       placeholder="Opsional" value="<?= h($old['age']) ?>">

                <label for="city">Lokasi (Kota)</label>
                <input id="city" type="text" name="city"
                       placeholder="Contoh: Pangkalpinang" value="<?= h($old['city']) ?>">

                <button type="submit">Daftar</button>
            </form>

            <div class="register-text">
                Sudah punya akun?
                <a href="<?= h($BASE) ?>/login.php">Masuk di sini</a>
            </div>

        </div>
    </div>
</div>

<!-- ==================== MODAL SUCCESS ==================== -->
<?php if ($success): ?>
<div class="modal-mask" role="dialog" aria-modal="true">
    <div class="modal-card">
        <h3>Akun Berhasil Dibuat 🎉</h3>

        <p>Selamat datang di NearBuy! Silakan login untuk mulai belanja.</p>

        <div class="modal-actions">
            <a href="<?= h($BASE) ?>/login.php" class="btn btn-primary">Login</a>
            <a href="<?= h($BASE) ?>/index.php" class="btn btn-secondary">Beranda</a>
        </div>
    </div>
</div>
<?php endif; ?>

</body>
</html>
