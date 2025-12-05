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
require_once __DIR__ . '/../includes/db.php'; // Pastikan file ini menyediakan variabel $pdo

// Base URL (sesuaikan dengan folder kamu)
$BASE = '/NearBuy-Marketplace/public';

// Redirect jika sudah login
if (!empty($_SESSION['user'])) {
    $role = $_SESSION['user']['role'] ?? 'pengguna';
    header("Location: $BASE/{$role}/index.php");
    exit;
}

// ========================
// STATE
// ========================
$error = '';
// Variabel $success tidak lagi digunakan, karena kita menggunakan redirect

// Kolom ini sesuai dengan kolom di tabel `users` database Anda
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

    // CSRF check kalau fungsi tersedia (asumsi Anda punya fungsi ini)
    if (function_exists('csrf_verify')) {
        $token = $_POST['csrf'] ?? '';
        if (!csrf_verify($token)) {
            $error = 'Sesi formulir sudah habis. Silakan muat ulang halaman dan coba lagi.';
        }
    }

    // Ambil input dan bersihkan (sanitize)
    foreach ($old as $key => $_) {
        $old[$key] = trim((string)($_POST[$key] ?? ''));
    }
    
    // Ambil password dan konfirmasi
    $password = trim((string)($_POST['password'] ?? ''));
    $confirm  = trim((string)($_POST['confirm'] ?? ''));
    

    // --- VALIDATION ---
    if ($error === '') {
        // Validation checks
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
            $error = 'Harap isi kota domisili agar rekomendasi produk bisa disesuaikan dengan lokasi kamu.';
        } else {
            // ---------- SIMPAN KE DATABASE ----------
            try {
                if (!isset($pdo) || !($pdo instanceof PDO)) {
                    // Pastikan $pdo tersedia dari includes/db.php
                    throw new RuntimeException('Koneksi database tidak tersedia. Cek includes/db.php.');
                }

                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

                // Cek apakah email sudah terdaftar
                $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
                $checkStmt->execute([$old['email']]);
                if ($checkStmt->fetchColumn() > 0) {
                    $error = 'Email ini sudah terdaftar. Silakan gunakan email lain atau login.';
                } else {
                    
                    $pdo->beginTransaction();

                    // Hashing password
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    if ($hash === false) {
                        throw new RuntimeException('Gagal melakukan hashing password.');
                    }

                    $insert = $pdo->prepare("
                        INSERT INTO users (
                            full_name, email, phone, gender, birth_date, 
                            address, city, province, postal_code, password_hash
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");

                    // Gunakan null jika kolom opsional kosong
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
                    
                    // ===================================
                    // LOGIKA REDIRECT SETELAH SUKSES
                    // ===================================
                    
                    // Simpan pesan sukses ke sesi untuk ditampilkan di halaman login (Flash message)
                    $_SESSION['flash'] = [
                        'type' => 'success',
                        'message' => 'Akun berhasil dibuat! Silakan login.'
                    ];
                    
                    // Alihkan ke halaman login
                    header("Location: $BASE/login.php");
                    exit;
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
    <style>
        /* CSS tambahan untuk memastikan layout dua kolom berfungsi */
        .two-columns {
            display: flex;
            gap: 20px;
            margin-bottom: 15px;
        }
        .two-columns .input-box {
            flex: 1;
        }
        .two-columns label {
            display: block;
            margin-bottom: 5px;
        }
        .two-columns input, .two-columns select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box; /* Penting untuk padding */
        }
    </style>
</head>
<body>

<div class="container">

    <div class="left-panel">
        <img src="<?= h($BASE) ?>/assets/logo_nearbuy.png" alt="NearBuy" class="logo">
        <p class="slogan">“Menghubungkan pelanggan dengan produk terdekat di domisili kamu.”</p>
    </div>

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
                            <option value="male"   <?= $old['gender']==='male' ? 'selected' : '' ?>>Laki-laki</option>
                            <option value="female" <?= $old['gender']==='female' ? 'selected' : '' ?>>Perempuan</option>
                            <option value="other"  <?= $old['gender']==='other' ? 'selected' : '' ?>>Lainnya</option>
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

</body>
</html>