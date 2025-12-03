<?php
// ===============================================================
// NearBuy – Login
// ===============================================================
declare(strict_types=1);

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';

// AUTO BASE (tidak perlu hardcode)
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$BASE = rtrim($scriptDir, '/');

// variabel UI
$note  = '';
$error = '';

// notifikasi dari register?registered=1
if (isset($_GET['registered']) && $_GET['registered'] === '1') {
    $note = "Akun berhasil dibuat. Silakan login. 🎉";
}

// jika sudah login arahkan langsung
if (!empty($_SESSION['user'])) {
    $role = $_SESSION['user']['role'] ?? 'pengguna';
    if ($role === 'admin') {
        header("Location: {$BASE}/admin/index.php");
        exit;
    } elseif ($role === 'seller') {
        header("Location: {$BASE}/seller/index.php");
        exit;
    } else {
        header("Location: {$BASE}/index.php");
        exit;
    }
}

// proses login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email'] ?? ''));
    $pass  = trim($_POST['password'] ?? '');

    if ($email === '' || $pass === '') {
        $error = "Email dan kata sandi wajib diisi.";
    } else {
        // pastikan kolom yang dipakai sesuai DATABASE NEARBUY
        $stmt = $pdo->prepare("
            SELECT id, nama, email, password, role, latitude, longitude
            FROM users
            WHERE email = ?
            LIMIT 1
        ");
        $stmt->execute([$email]);
        $u = $stmt->fetch();

        // verifikasi password
        if ($u && $pass === $u['password']) { // karena database kamu masih plain text
            // regenerate session id
            if (function_exists('session_regenerate_id')) {
                session_regenerate_id(true);
            }

            // hapus password sebelum simpan session
            unset($u['password']);

            $_SESSION['user'] = $u;
            $_SESSION['user_lat'] = $u['latitude'] ?? null;
            $_SESSION['user_lng'] = $u['longitude'] ?? null;

            // redirect per role
            if ($u['role'] === 'admin') {
                header("Location: {$BASE}/admin/index.php");
                exit;
            }
            if ($u['role'] === 'seller') {
                header("Location: {$BASE}/seller/index.php");
                exit;
            }

            // pengguna biasa → beranda
            header("Location: {$BASE}/index.php");
            exit;
        } else {
            $error = "Email atau kata sandi salah.";
        }
    }
}

// helper escape
function e(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login | NearBuy</title>
  <link rel="stylesheet" href="<?= e($BASE) ?>/style-auth.css">
  <style>
    .alert{padding:10px;border-radius:8px;margin:10px 0;font-size:14px}
    .note{background:#e0f2fe;color:#0369a1}
    .err{background:#fee2e2;color:#b91c1c}
  </style>
</head>
<body>
<div class="auth-container">

  <div class="auth-left">
    <img src="<?= e($BASE) ?>/assets/logo-sellexa.png" class="logo-auth" alt="NearBuy">
    <h1>NearBuy</h1>
    <p>Temukan produk kebutuhan harian dari seller terdekat di sekitarmu</p>
    <p style="font-size:13px;color:#d1d5db;">Admin • Seller • Pengguna</p>
  </div>

  <div class="auth-right">
    <h2>Masuk ke Akun</h2>

    <?php if ($note): ?>
      <div class="alert note"><?= e($note) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
      <div class="alert err"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="POST" class="form-auth" autocomplete="off">
      <label>Email</label>
      <input type="email" name="email" required>

      <label>Kata Sandi</label>
      <input type="password" name="password" required>

      <button type="submit">Masuk</button>
    </form>

    <div class="auth-links">
      <p>Belum punya akun? <a href="<?= e($BASE) ?>/register.php">Daftar di sini</a></p>
    </div>
  </div>

</div>
</body>
</html>
