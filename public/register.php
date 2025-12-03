<?php
// ===============================================================
// NearBuy – Registrasi Akun Pengguna
// ===============================================================
declare(strict_types=1);

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';

// BASE disesuaikan dengan folder kamu
$BASE = '/NearBuy-marketplace/public';

const DEBUG = false;

$error   = '';
$success = false;

// nilai lama untuk refill form
$old = [
    'full_name' => '',
    'email'     => '',
    'phone'     => '',
    'gender'    => '',
    'city'      => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // cek CSRF kalau helper tersedia
    if (function_exists('csrf_verify')) {
        $csrfOk = csrf_verify($_POST['csrf'] ?? '');
        if (!$csrfOk) {
            $error = 'Sesi kamu sudah habis. Coba muat ulang halaman lalu daftar lagi.';
        }
    }

    if ($error === '') {

        // ambil input
        foreach ($old as $k => $_) {
            $old[$k] = trim($_POST[$k] ?? '');
        }
        $password = trim($_POST['password'] ?? '');
        $confirm  = trim($_POST['confirm'] ?? '');

        // validasi dasar
        if ($old['full_name'] === '' || $old['email'] === '' || $password === '' || $confirm === '') {
            $error = 'Nama lengkap, email, dan password wajib diisi.';
        } elseif (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
            $error = 'Format email tidak valid.';
        } elseif (strlen($password) < 6) {
            $error = 'Password minimal 6 karakter.';
        } elseif ($password !== $confirm) {
            $error = 'Konfirmasi password tidak sama.';
        } else {
            try {
                if (!isset($pdo) || !($pdo instanceof PDO)) {
                    throw new RuntimeException('Koneksi database tidak tersedia.');
                }

                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

                // cek email unik
                $cek = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
                $cek->execute([$old['email']]);
                if ($cek->fetch()) {
                    $error = 'Email sudah terdaftar. Silakan gunakan email lain atau login.';
                } else {
                    $pdo->beginTransaction();

                    $hash = password_hash($password, PASSWORD_DEFAULT);

                    // sesuaikan dengan struktur tabel users kamu
                    $ins = $pdo->prepare("
                        INSERT INTO users (
                            email,
                            password_hash,
                            full_name,
                            phone,
                            gender,
                            city,
                            role,
                            is_active
                        ) VALUES (?, ?, ?, ?, ?, ?, 'pengguna', 1)
                    ");

                    $ins->execute([
                        $old['email'],
                        $hash,
                        $old['full_name'],
                        $old['phone'] ?: null,
                        $old['gender'] ?: null,
                        $old['city'] ?: null
                    ]);

                    $pdo->commit();
                    $success = true;

                    // kosongkan form
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

function h(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Registrasi | NearBuy</title>
  <link rel="stylesheet" href="<?= h($BASE) ?>/style-auth.css">
  <style>
    .alert {margin:10px 0;padding:10px 12px;border-radius:8px;font-size:14px}
    .alert-error{background:#fee2e2;border:1px solid #fecaca;color:#991b1b}
    .modal-mask { position: fixed; inset: 0; background: rgba(0,0,0,.55);
      display: flex; align-items: center; justify-content: center; z-index: 9999; }
    .modal-card { width: min(90vw, 420px); background: #fff; border-radius: 16px;
      padding: 26px 20px 24px; text-align: center; box-shadow: 0 8px 20px rgba(0,0,0,.2); }
    .modal-card h3 { margin-top: 0; font-size: 1.4rem; color: #222; }
    .modal-card p { margin: 6px 0 18px; color: #555; }
    .modal-actions { display: flex; gap: 10px; justify-content: center; flex-wrap: wrap; }
    .btn { padding: 10px 18px; border-radius: 8px; font-weight: 600; text-decoration: none;
      border: none; cursor: pointer; transition: .2s ease; }
    .btn-primary { background: #1e2a47; color: #fff; }
    .btn-secondary { background: #f3f4f6; color: #111; }
    .btn:hover { opacity: .9; }
  </style>
</head>
<body>
<div class="auth-container">

  <!-- Branding kiri -->
  <div class="auth-left">
    <img src="<?= h($BASE) ?>/assets/logo-sellexa.png" alt="NearBuy" class="logo-auth">
    <h1>NearBuy</h1>
    <p>Temukan kebutuhan harian dari penjual terdekat di sekitarmu.</p>
  </div>

  <!-- Form kanan -->
  <div class="auth-right">
    <h2>Registrasi Akun Baru</h2>

    <?php if ($error): ?>
      <div class="alert alert-error"><?= h($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="" class="form-auth" autocomplete="off" novalidate>
      <?php if (function_exists('csrf_token')): ?>
        <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
      <?php endif; ?>

      <label>Nama Lengkap</label>
      <input type="text" name="full_name" value="<?= h($old['full_name']) ?>" required>

      <label>Email</label>
      <input type="email" name="email" value="<?= h($old['email']) ?>" required>

      <label>Nomor Telepon</label>
      <input type="text" name="phone" value="<?= h($old['phone']) ?>">

      <label>Jenis Kelamin</label>
      <select name="gender">
        <option value="" <?= $old['gender']===''?'selected':'' ?>>- Pilih -</option>
        <option value="male"   <?= $old['gender']==='male'?'selected':'' ?>>Laki laki</option>
        <option value="female" <?= $old['gender']==='female'?'selected':'' ?>>Perempuan</option>
        <option value="other"  <?= $old['gender']==='other'?'selected':'' ?>>Lainnya</option>
      </select>

      <label>Kota Domisili</label>
      <input type="text" name="city" placeholder="Contoh: Bandar Lampung" value="<?= h($old['city']) ?>">

      <label>Password</label>
      <input type="password" name="password" required>

      <label>Konfirmasi Password</label>
      <input type="password" name="confirm" required>

      <button type="submit">Daftar</button>
    </form>

    <div class="auth-links">
      <p>Sudah punya akun? <a href="<?= h($BASE) ?>/login.php">Masuk di sini</a></p>
    </div>
  </div>
</div>

<?php if ($success): ?>
<div class="modal-mask">
  <div class="modal-card">
    <h3>Akun Berhasil Dibuat 🎉</h3>
    <p>Selamat datang di <b>NearBuy</b>! Silakan login untuk mulai mencari produk di sekitarmu.</p>
    <div class="modal-actions">
      <a href="<?= h($BASE) ?>/login.php" class="btn btn-primary">Ke Halaman Login</a>
      <a href="<?= h($BASE) ?>/index.php" class="btn btn-secondary">Kembali ke Beranda</a>
    </div>
  </div>
</div>
<?php endif; ?>
</body>
</html>
