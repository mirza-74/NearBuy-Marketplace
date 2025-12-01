<?php
// ===============================================================
// SellExa – Login (redirect admin -> /admin/index.php)
// ===============================================================
declare(strict_types=1);

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';

$BASE = '/Marketplace_SellExa/public';

// Pastikan PDO strict (jika belum diset di db.php)
if (isset($pdo) && $pdo instanceof PDO) {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
}

$note  = '';
$error = '';

// Notifikasi dari register (opsional pakai ?registered=1)
if (isset($_GET['registered']) && $_GET['registered'] === '1') {
    $note = 'Akun berhasil dibuat. Silakan login untuk mulai berbelanja. 🎉';
}

// Kalau sudah login, arahkan sesuai role
if (!empty($_SESSION['user'])) {
    $role = $_SESSION['user']['role'] ?? 'pengguna';
    if ($role === 'admin') {
        header('Location: ' . $BASE . '/admin/index.php');
        exit;
    }
    header('Location: ' . $BASE . '/index.php');
    exit;
}

// Proses form login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email'] ?? ''));
    $pass  = (string)($_POST['password'] ?? '');

    if ($email === '' || $pass === '') {
        $error = 'Email dan kata sandi wajib diisi.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND is_active = 1 LIMIT 1");
        $stmt->execute([$email]);
        $u = $stmt->fetch();

        if ($u && password_verify($pass, $u['password_hash'])) {
            // Regenerate session id (anti session fixation)
            if (function_exists('session_regenerate_id')) {
                session_regenerate_id(true);
            }

            // Simpan user ke session tanpa password_hash
            $userSafe = $u;
            unset($userSafe['password_hash']);
            $_SESSION['user'] = $userSafe;

            // Redirect by role
            if (($u['role'] ?? 'pengguna') === 'admin') {
                header('Location: ' . $BASE . '/admin/index.php');
                exit;
            }

            header('Location: ' . $BASE . '/index.php');
            exit;
        } else {
            $error = 'Email atau kata sandi salah.';
        }
    }
}

// helper
function h(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login | SellExa</title>
  <link rel="stylesheet" href="<?= h($BASE) ?>/style-auth.css">
  <style>
    .alert {margin:10px 0;padding:10px;border-radius:8px;font-size:14px}
    .alert-note {background:#ecfeff;border:1px solid #a5f3fc;color:#075985}
    .alert-error{background:#fee2e2;border:1px solid #fecaca;color:#991b1b}
  </style>
</head>
<body>
  <div class="auth-container">
    <div class="auth-left">
      <img src="<?= h($BASE) ?>/assets/logo-sellexa.png" alt="SellExa" class="logo-auth">
      <h1>SellExa</h1>
      <p>Belanja Lebih Cerdas, Lebih Cepat</p>
      <p style="font-size:13px;margin-top:6px;color:#e5e7eb;">Admin & Seller SellExa</p>
    </div>

    <div class="auth-right">
      <h2>Masuk ke Akun</h2>

      <?php if ($note): ?>
        <div class="alert alert-note"><?= h($note) ?></div>
      <?php endif; ?>

      <?php if ($error): ?>
        <div class="alert alert-error"><?= h($error) ?></div>
      <?php endif; ?>

      <form method="POST" class="form-auth" autocomplete="off">
        <label>Email</label>
        <input type="email" name="email" required>

        <label>Kata Sandi</label>
        <input type="password" name="password" required>

        <button type="submit">Masuk</button>
      </form>

      <div class="auth-links">
        <a href="#">Lupa kata sandi?</a>
        <p>Belum punya akun? <a href="<?= h($BASE) ?>/register.php">Daftar di sini</a></p>
      </div>
    </div>
  </div>
</body>
</html>
