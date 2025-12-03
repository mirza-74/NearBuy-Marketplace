<?php
// ===============================================================
// NearBuy – Login
// ===============================================================
declare(strict_types=1);

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';

$BASE = '/NearBuy-marketplace/public';

$error = '';
$note  = '';

// Jika sudah login redirect sesuai role
if (!empty($_SESSION['user'])) {
    $role = $_SESSION['user']['role'] ?? 'pengguna';

    if ($role === 'admin') {
        header("Location: $BASE/admin/index.php");
        exit;
    } elseif ($role === 'seller') {
        header("Location: $BASE/seller/index.php");
        exit;
    } else {
        header("Location: $BASE/index.php");
        exit;
    }
}

// Proses Login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = strtolower(trim($_POST['email'] ?? ''));
    $pass  = trim($_POST['password'] ?? '');

    if ($email === '' || $pass === '') {
        $error = 'Email dan kata sandi wajib diisi.';
    } else {
        // Ambil user berdasarkan email
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND is_active = 1 LIMIT 1");
        $stmt->execute([$email]);
        $u = $stmt->fetch();

        if ($u && password_verify($pass, $u['password_hash'])) {

            // regenerate session
            session_regenerate_id(true);

            // Simpan data user ke session (HAPUS password_hash!)
            unset($u['password_hash']);
            $_SESSION['user'] = $u;

            // Redirect sesuai role
            if ($u['role'] === 'admin') {
                header("Location: $BASE/admin/index.php");
                exit;
            } elseif ($u['role'] === 'seller') {
                header("Location: $BASE/seller/index.php");
                exit;
            } else {
                header("Location: $BASE/index.php");
                exit;
            }

        } else {
            $error = 'Email atau kata sandi salah.';
        }
    }
}

// Escaper
function h($s){ return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Login | NearBuy</title>
  <link rel="stylesheet" href="<?= h($BASE) ?>/style-auth.css">
</head>

<body>
  <div class="auth-container">

    <div class="auth-left">
      <img src="<?= h($BASE) ?>/assets/logo-sellexa.png" class="logo-auth">
      <h1>NearBuy</h1>
      <p>Belanja lebih dekat, lebih cepat.</p>
    </div>

    <div class="auth-right">
      <h2>Login</h2>

      <?php if ($error): ?>
        <div class="alert alert-error"><?= h($error) ?></div>
      <?php endif; ?>

      <form method="POST" class="form-auth">
        <label>Email</label>
        <input type="email" name="email" required>

        <label>Password</label>
        <input type="password" name="password" required>

        <button type="submit">Masuk</button>
      </form>

      <div class="auth-links">
        <p>Belum punya akun? <a href="<?= h($BASE) ?>/register.php">Daftar di sini</a></p>
      </div>
    </div>
  </div>
</body>
</html>
