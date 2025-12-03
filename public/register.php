<?php
// ===============================================================
// NearBuy – Registrasi Pengguna
// ===============================================================
declare(strict_types=1);

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';

// DEBUG (boleh dimatikan di produksi)
const DEBUG = false;
if (DEBUG) {
  ini_set('display_errors','1');
  ini_set('display_startup_errors','1');
  error_reporting(E_ALL);
}

// BASE otomatis ikut folder /public
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$BASE = rtrim($scriptDir, '/');

$error   = '';
$success = false;

// field yang dipakai di form
$old = [
  'full_name'        => '',
  'email'            => '',
  'phone'            => '',
  'gender'           => '',
  'city'             => '',
  'travel_frequency' => '', // seberapa sering bepergian di sekitar rumah
  'mobility_mode'    => '', // cara utama berpindah tempat
];

// handle submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // cek CSRF
    if (!function_exists('csrf_verify') || !csrf_verify($_POST['csrf'] ?? '')) {
        $error = 'Sesi berakhir atau token tidak valid. Silakan muat ulang halaman.';
    } else {

        // ambil input
        foreach ($old as $k => $_) {
            $old[$k] = trim($_POST[$k] ?? '');
        }
        $password = trim($_POST['password'] ?? '');
        $confirm  = trim($_POST['confirm'] ?? '');

        // validasi dasar
        if ($old['email'] === '' || $old['full_name'] === '' || $password === '' || $confirm === '') {
            $error = 'Harap isi semua kolom wajib seperti Nama, Email, dan Password.';
        } elseif (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
            $error = 'Format email tidak valid.';
        } elseif (strlen($password) < 6) {
            $error = 'Password minimal 6 karakter.';
        } elseif ($password !== $confirm) {
            $error = 'Kata sandi dan konfirmasi tidak sama.';
        } else {
            try {
                if (!isset($pdo) || !($pdo instanceof PDO)) {
                    throw new RuntimeException('Koneksi database tidak tersedia.');
                }

                // cek email unik
                $check = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
                $check->execute([$old['email']]);
                if ($check->fetch()) {
                    $error = 'Email sudah digunakan, coba pakai email lain.';
                } else {
                    $pdo->beginTransaction();

                    $hash = password_hash($password, PASSWORD_DEFAULT);

                    // simpan user baru
                    // pastikan struktur tabel users sudah punya:
                    // email, password_hash, full_name, phone, gender, city, role, is_active
                    $ins = $pdo->prepare("
                        INSERT INTO users (email, password_hash, full_name, phone, gender, city, role, is_active)
                        VALUES (?, ?, ?, ?, ?, ?, 'pengguna', 1)
                    ");
                    $ins->execute([
                        $old['email'],
                        $hash,
                        $old['full_name'],
                        $old['phone'] ?: null,
                        $old['gender'] ?: null,
                        $old['city'] ?: null,
                    ]);

                    $userId = (int)$pdo->lastInsertId();

                    // kalau nanti mau simpan travel_frequency dan mobility_mode
                    // bisa dibuat tabel user_profiles lalu insert di sini

                    $pdo->commit();
                    $success = true;

                    // bersihkan input lama
                    foreach ($old as $k => $_) {
                        $old[$k] = '';
                    }
                }
            } catch (Throwable $e) {
                if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
                    try { $pdo->rollBack(); } catch (Throwable $__) {}
                }
                $error = 'Terjadi kesalahan saat menyimpan data. Silakan coba lagi.';
                if (DEBUG) {
                    $error .= ' [DEBUG: ' . $e->getMessage() . ']';
                }
            }
        }
    }
}

function h(?string $s): string {
  return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
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
    .modal-mask {
      position: fixed; inset: 0; background: rgba(0,0,0,.55);
      display: flex; align-items: center; justify-content: center;
      z-index: 9999; animation: fadeIn .25s ease;
    }
    .modal-card {
      width: min(90vw, 420px); background: #fff; border-radius: 16px;
      padding: 26px 20px 24px; text-align: center;
      box-shadow: 0 8px 20px rgba(0,0,0,.2); animation: popIn .3s ease;
    }
    .modal-card h3 { margin-top: 0; font-size: 1.4rem; color: #222; }
    .modal-card p { margin: 6px 0 18px; color: #555; }
    .modal-actions {
      display: flex; gap: 10px; justify-content: center; flex-wrap: wrap;
    }
    .btn {
      padding: 10px 18px; border-radius: 8px; font-weight: 600;
      text-decoration: none; border: none; cursor: pointer; transition: .2s ease;
    }
    .btn-primary { background: #1e2a47; color: #fff; }
    .btn-secondary { background: #f3f4f6; color: #111; }
    .btn:hover { opacity: .9; }
    @keyframes fadeIn { from {opacity:0;} to {opacity:1;} }
    @keyframes popIn { from {transform:scale(.95);opacity:0;} to {transform:scale(1);opacity:1;} }
    .error {
      background:#fee2e2; color:#b91c1c; padding:10px 12px;
      border-radius:8px; margin:12px 0;
    }
  </style>
</head>
<body>
<div class="auth-container">
  <!-- Branding kiri -->
  <div class="auth-left">
    <img src="<?= h($BASE) ?>/assets/logo-sellexa.png" alt="NearBuy" class="logo-auth">
    <h1>NearBuy</h1>
    <p>Daftar untuk menemukan kebutuhan harian dari penjual terdekat.</p>
  </div>

  <!-- Form kanan -->
  <div class="auth-right">
    <h2>Registrasi Akun NearBuy</h2>

    <?php if ($error): ?>
      <div class="error"><?= h($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="" class="form-auth" autocomplete="off" novalidate>
      <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">

      <label>Nama Lengkap</label>
      <input type="text" name="full_name" value="<?= h($old['full_name']) ?>" required>

      <label>Email</label>
      <input type="email" name="email" value="<?= h($old['email']) ?>" required>

      <label>Nomor WhatsApp atau Telepon</label>
      <input type="text" name="phone" value="<?= h($old['phone']) ?>" placeholder="Contoh: 0812xxxxxxx">

      <label>Password</label>
      <input type="password" name="password" required>

      <label>Konfirmasi Password</label>
      <input type="password" name="confirm" required>

      <label>Jenis Kelamin</label>
      <select name="gender">
        <option value="" <?= $old['gender']===''?'selected':'' ?>>Pilih salah satu</option>
        <option value="male"   <?= $old['gender']==='male'?'selected':'' ?>>Laki laki</option>
        <option value="female" <?= $old['gender']==='female'?'selected':'' ?>>Perempuan</option>
        <option value="other"  <?= $old['gender']==='other'?'selected':'' ?>>Lainnya</option>
      </select>

      <label>Domisili utama kamu</label>
      <input type="text" name="city" placeholder="Contoh: Kedaton, Bandar Lampung" value="<?= h($old['city']) ?>">

      <label>Seberapa sering kamu bepergian di sekitar rumah untuk beli kebutuhan harian</label>
      <select name="travel_frequency">
        <option value="" <?= $old['travel_frequency']===''?'selected':'' ?>>Pilih salah satu</option>
        <option value="harian"   <?= $old['travel_frequency']==='harian'?'selected':'' ?>>Hampir setiap hari</option>
        <option value="mingguan" <?= $old['travel_frequency']==='mingguan'?'selected':'' ?>>Sekali atau dua kali seminggu</option>
        <option value="bulanan"  <?= $old['travel_frequency']==='bulanan'?'selected':'' ?>>Sekali sebulan atau lebih jarang</option>
      </select>

      <label>Cara utama kamu berpindah tempat di sekitar rumah</label>
      <select name="mobility_mode">
        <option value="" <?= $old['mobility_mode']===''?'selected':'' ?>>Pilih salah satu</option>
        <option value="jalan-kaki"   <?= $old['mobility_mode']==='jalan-kaki'?'selected':'' ?>>Jalan kaki</option>
        <option value="motor"        <?= $old['mobility_mode']==='motor'?'selected':'' ?>>Motor</option>
        <option value="mobil"        <?= $old['mobility_mode']==='mobil'?'selected':'' ?>>Mobil</option>
        <option value="ojek-online"  <?= $old['mobility_mode']==='ojek-online'?'selected':'' ?>>Ojek atau taksi online</option>
      </select>

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
    <p>
      Selamat datang di <b>NearBuy</b>.  
      Kamu bisa login dan mulai mencari kebutuhan harian dari penjual terdekat.
    </p>
    <div class="modal-actions">
      <a href="<?= h($BASE) ?>/login.php" class="btn btn-primary">Ke Halaman Login</a>
      <a href="<?= h($BASE) ?>/index.php" class="btn btn-secondary">Kembali ke Beranda</a>
    </div>
  </div>
</div>
<?php endif; ?>
</body>
</html>
