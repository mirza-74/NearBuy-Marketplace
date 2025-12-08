<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';

$BASE  = '/NearBuy-marketplace/public'; // samakan dengan header.php
$error = '';

// ===================================================
// Redirect kalau SUDAH login
// ===================================================
if (!empty($_SESSION['user'])) {
    $role = $_SESSION['user']['role'] ?? 'pengguna';

    if ($role === 'admin') {
        header("Location: $BASE/admin/index.php");
        exit;
    } else {
        // seller & pengguna biasa sama-sama ke halaman utama public
        header("Location: $BASE/index.php");
        exit;
    }
}

// ===================================================
// Proses LOGIN
// ===================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim((string)($_POST['email'] ?? '')));
    $pass  = trim((string)($_POST['password'] ?? ''));

    if ($email === '' || $pass === '') {
        $error = 'Email dan kata sandi wajib diisi.';
    } else {
        try {
            if (!isset($pdo) || !($pdo instanceof PDO)) {
                throw new RuntimeException('Koneksi database tidak tersedia.');
            }

            $stmt = $pdo->prepare("
                SELECT id, full_name, email, password_hash, role, is_active
                FROM users
                WHERE email = ?
                LIMIT 1
            ");
            $stmt->execute([$email]);
            $u = $stmt->fetch(PDO::FETCH_ASSOC);

            $loginOk = false;

            if ($u && (int)$u['is_active'] === 1) {
                $hash = $u['password_hash'];

                if (is_string($hash) && $hash !== '') {
                    // utama: password_hash
                    if (password_verify($pass, $hash)) {
                        $loginOk = true;
                    } else {
                        // fallback: kalau dulu pernah tersimpan plain text
                        if ($pass === $hash) {
                            $loginOk = true;
                        }
                    }
                }
            }

            if ($loginOk) {
                session_regenerate_id(true);

                unset($u['password_hash']);
                $_SESSION['user'] = $u;

                // Redirect setelah login
                if ($u['role'] === 'admin') {
                    header("Location: $BASE/admin/index.php");
                } else {
                    // seller & pengguna biasa ke public index
                    header("Location: $BASE/index.php");
                }
                exit;
            } else {
                $error = 'Email atau kata sandi salah.';
            }

        } catch (Throwable $e) {
            $error = 'Terjadi kesalahan saat memproses login.';
            // kalau mau debug:
            // $error .= ' [DEBUG: '.$e->getMessage().']';
        }
    }
}

function h($s) {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | NearBuy</title>
    <link rel="stylesheet" href="<?= h($BASE) ?>/style-auth.css">
</head>
<body>

<div class="container">

    <!-- LEFT PANEL -->
    <div class="left-panel">
        <img src="<?= h($BASE) ?>/assets/logo_nearbuy.png" alt="NearBuy" class="logo">
        <p class="slogan">“Menghubungkan pelanggan dengan produk terdekat.”</p>
    </div>

    <!-- RIGHT PANEL -->
    <div class="right-panel">
        <div class="form-container">
            <h2>Login Akun NearBuy</h2>

            <?php if ($error): ?>
                <div class="alert alert-error" role="alert"><?= h($error) ?></div>
            <?php endif; ?>

            <form action="" method="POST" autocomplete="off" novalidate>
                <label for="email">Email</label>
                <input id="email" type="email" name="email" required placeholder="Masukkan email...">

                <label for="password">Password</label>
                <input id="password" type="password" name="password" required placeholder="Masukkan password...">

                <button type="submit" class="btn-submit">Masuk</button>
            </form>

            <div class="register-text">
                Belum punya akun? <a href="<?= h($BASE) ?>/register.php">Daftar di sini</a>
            </div>
        </div>
    </div>

</div>

</body>
</html>
