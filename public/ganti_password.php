<?php
declare(strict_types=1);

// ========================
// INCLUDES & CONFIG
// ========================
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';
// Asumsi: File db.php menyediakan objek PDO ($pdo)
// Asumsi: File session.php menyediakan fungsi h() dan memulai sesi

$BASE = '/NearBuy-Marketplace/public';

// Pastikan fungsi h() ada dan berfungsi
if (!function_exists('h')) {
    function h(?string $s): string {
        return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
    }
}

$error   = '';
$success = '';

// ========================
// CEK LOGIN
// ========================
$user = $_SESSION['user'] ?? null;
$role = $user['role'] ?? 'guest';

if (!$user || !in_array($role, ['pengguna','seller','admin'], true)) {
    // Arahkan ke halaman login jika belum login
    header('Location: '.$BASE.'/login.php?next='.urlencode($BASE.'/ganti_password.php'));
    exit;
}

$userId = (int)$user['id'];

// ========================
// HANDLE UPDATE (POST)
// ========================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $oldPassword     = trim((string)($_POST['old_password'] ?? ''));
    $newPassword     = trim((string)($_POST['new_password'] ?? ''));
    $confirmPassword = trim((string)($_POST['confirm_password'] ?? ''));
    
    // CSRF
    if (function_exists('csrf_verify')) {
        if (!csrf_verify($_POST['csrf'] ?? '')) {
            $error = 'Sesi formulir sudah habis. Silakan muat ulang halaman.';
        }
    }

    if ($error === '') {
        // Validasi input
        if ($oldPassword === '' || $newPassword === '' || $confirmPassword === '') {
            $error = 'Semua kolom password wajib diisi.';
        } elseif ($newPassword !== $confirmPassword) {
            $error = 'Password baru dan konfirmasi password **tidak cocok**.';
        } elseif (strlen($newPassword) < 6) { 
            // Mengikuti batas 6 karakter dari diskusi sebelumnya
            $error = 'Password baru minimal harus **6 karakter**.';
        } else {
            try {
                // 1. Ambil hash password lama dari database
                // PERBAIKAN UTAMA: Menggunakan kolom 'password_hash'
                $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ? LIMIT 1");
                $stmt->execute([$userId]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$row) {
                    $error = 'Pengguna tidak ditemukan.'; 
                } else {
                    // Ambil hash dari kolom 'password_hash'
                    $hashedPassword = $row['password_hash'];

                    // 2. Verifikasi password lama
                    if (!password_verify($oldPassword, $hashedPassword)) {
                        $error = 'Password lama **salah**.';
                    } else {
                        // 3. Update password baru
                        $newHashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

                        // PERBAIKAN UTAMA: Menggunakan kolom 'password_hash'
                        $updateStmt = $pdo->prepare("
                            UPDATE users SET password_hash = ? WHERE id = ? LIMIT 1
                        ");
                        $updateStmt->execute([$newHashedPassword, $userId]);

                        $success = 'Password berhasil diperbarui!';
                        
                        // Opsional: Hapus password lama dan paksa login ulang untuk sesi yang lebih aman.
                        // session_unset();
                        // session_destroy();
                        // header('Location: '.$BASE.'/login.php?msg=password_changed');
                        // exit;
                    }
                }
            } catch (Throwable $e) {
                // Untuk debugging, Anda bisa ganti baris di bawah ini:
                // $error = 'Terjadi kesalahan saat mengganti password. Detail: ' . $e->getMessage();
                $error = 'Terjadi kesalahan saat mengganti password.';
            }
        }
    }
}

// ========================
// VIEW
// ========================
// Pastikan edit_profil.css dimuat
$EXTRA_CSS = ['edit_profil.css']; 
require_once __DIR__ . '/../includes/header.php';
?>

<div class="profile-page-simple">
  <div class="profile-card">
    <h1>Ganti Password 🔐</h1>
    <p class="profile-subtitle">
      Masukkan password lama Anda, kemudian masukkan password baru untuk mengamankan akun.
    </p>

    <?php if ($error): ?>
      <div class="alert alert-error"><?= h($error) ?></div>
    <?php elseif ($success): ?>
      <div class="alert alert-success"><?= h($success) ?></div>
    <?php endif; ?>

    <form method="post" class="profile-form">
      <?php if (function_exists('csrf_token')): ?>
        <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
      <?php endif; ?>

      <label>Password Lama *</label>
      <input type="password" name="old_password" required autocomplete="current-password">

      <label>Password Baru *</label>
      <input type="password" name="new_password" required minlength="6" autocomplete="new-password">

      <label>Konfirmasi Password Baru *</label>
      <input type="password" name="confirm_password" required minlength="6" autocomplete="new-password">
      
      <button type="submit" class="btn-primary">Simpan Password Baru</button>
      
      <p class="profile-links back-link">
        <a href="<?= $BASE ?>/profil.php">← Kembali ke Profil</a>
      </p>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>