<?php
// ===============================================================
// NearBuy – Edit Profil Pengguna (profil-edit.php)
// ===============================================================
declare(strict_types=1);

// BASE: otomatis ambil folder /public yang sedang dipakai
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$BASE = rtrim($scriptDir, '/');

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/settings.php';
require_once __DIR__ . '/../includes/header.php';

// Data user dari session
$user    = $_SESSION['user'] ?? null;
$userId  = (int)($user['id'] ?? 0);
$isGuest = ($userId <= 0);

// ===================== KEAMANAN: Cek Login =====================
if ($isGuest) {
    // Redireksi jika user belum login
    $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Anda harus masuk untuk mengakses halaman ini.'];
    header('Location: ' . $BASE . '/index.php'); 
    exit;
}

// ===================== FUNGSI HELPER =====================
if (!function_exists('e')) {
    function e(?string $str): string {
        return htmlspecialchars((string) $str ?? '', ENT_QUOTES, 'UTF-8');
    }
}
if (!function_exists('validate_email')) {
    function validate_email(string $email): bool {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}

// Ambil data user saat ini dari DB
$userData = null;
try {
    // PERBAIKAN 1: Mengubah kolom 'name' menjadi 'full_name' sesuai DB
    $stmt = $pdo->prepare("
        SELECT id, full_name, email, phone, address, role
        FROM users 
        WHERE id = ?
        LIMIT 1
    ");
    $stmt->execute([$userId]);
    $uData = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$uData) {
        $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Data pengguna tidak ditemukan.'];
        header('Location: ' . $BASE . '/index.php');
        exit;
    }
    
    // Konversi full_name menjadi name untuk konsistensi variabel form ($inputName)
    $uData['name'] = $uData['full_name']; 
    $userData = $uData;

} catch (Throwable $e) {
    // Tangani error database (misalnya nama kolom salah, koneksi putus)
    // ERROR: Tambahkan pesan debug untuk melihat error SQL sebenarnya (Hapus setelah berhasil)
    // $debugMessage = 'Terjadi kesalahan sistem saat mengambil data. [DEBUG: ' . $e->getMessage() . ']';
    // $_SESSION['flash_message'] = ['type' => 'error', 'message' => $debugMessage];

    $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Terjadi kesalahan sistem saat mengambil data.'];
    header('Location: ' . $BASE . '/index.php');
    exit;
}

// Inisialisasi variabel formulir
$inputName    = $userData['name'];
$inputEmail   = $userData['email'];
$inputPhone   = $userData['phone'];
$inputAddress = $userData['address'];
$errors       = [];

// ===================== PROSES FORM SUBMIT =====================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Sanitize input
    $inputName    = trim($_POST['name'] ?? $inputName);
    $inputPhone   = trim($_POST['phone'] ?? $inputPhone);
    $inputAddress = trim($_POST['address'] ?? $inputAddress);

    // --- A. UPDATE DETAIL PROFIL (Name, Phone, Address) ---
    if ($action === 'update_profile') {
        // Validasi
        if (mb_strlen($inputName) < 3 || mb_strlen($inputName) > 100) {
            $errors['name'] = "Nama harus antara 3 dan 100 karakter.";
        }
        if (mb_strlen($inputPhone) < 8 || mb_strlen($inputPhone) > 20) {
            $errors['phone'] = "Nomor telepon tidak valid.";
        }

        if (empty($errors)) {
            try {
                // PERBAIKAN 2: Mengubah kolom 'name' menjadi 'full_name' di UPDATE
                $stmt = $pdo->prepare("
                    UPDATE users 
                    SET full_name = ?, phone = ?, address = ?, updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$inputName, $inputPhone, $inputAddress, $userId]);

                // Update Session Data (menggunakan key 'name' untuk konsistensi)
                $_SESSION['user']['name']    = $inputName;
                $_SESSION['user']['phone']   = $inputPhone;
                $_SESSION['user']['address'] = $inputAddress;

                $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Profil berhasil diperbarui!'];
                
                header('Location: ' . $BASE . '/profil-edit.php');
                exit;

            } catch (Throwable $e) {
                $errors['general'] = 'Gagal memperbarui profil: ' . $e->getMessage();
            }
        }
    }

    // --- B. UPDATE PASSWORD ---
    if ($action === 'update_password') {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword     = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        // PERBAIKAN 3: Mengubah kolom 'password' menjadi 'password_hash' di SELECT
        $stmtPass = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
        $stmtPass->execute([$userId]);
        $userHash = $stmtPass->fetchColumn();

        // Validasi Password
        if (!password_verify($currentPassword, (string)$userHash)) {
            $errors['current_password'] = "Kata sandi saat ini salah.";
        }
        if (mb_strlen($newPassword) < 8) {
            $errors['new_password'] = "Kata sandi baru minimal 8 karakter.";
        }
        if ($newPassword !== $confirmPassword) {
            $errors['confirm_password'] = "Konfirmasi kata sandi tidak cocok.";
        }

        if (empty($errors)) {
            try {
                $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
                // PERBAIKAN 4: Mengubah kolom 'password' menjadi 'password_hash' di UPDATE
                $stmt = $pdo->prepare("
                    UPDATE users 
                    SET password_hash = ?, updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$newPasswordHash, $userId]);
                
                $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Kata sandi berhasil diubah!'];
                
                header('Location: ' . $BASE . '/profil-edit.php');
                exit;

            } catch (Throwable $e) {
                $errors['pass_general'] = 'Gagal memperbarui kata sandi: ' . $e->getMessage();
            }
        }
    }
}


/// Ambil pesan flash jika ada
$flash = $_SESSION['flash_message'] ?? null;
unset($_SESSION['flash_message']);
?>

<div class="page-wrap">
  <div class="content">

    <section class="form-section">
      <h1>Edit Profil</h1>
      <link rel="stylesheet" href="<?= e($BASE) ?>/editprofil.css?v=1.0">
      <?php if ($flash): ?>
          <div class="flash-message <?= e($flash['type']) ?>">
              <?= e($flash['message']) ?>
          </div>
      <?php endif; ?>

      <div class="card" style="margin-bottom: 30px;">
          <h4 style="margin-top:0;">Detail Akun</h4>
          <form method="post" action="<?= e($BASE) ?>/profil-edit.php">
              <input type="hidden" name="action" value="update_profile">
              
              <div class="form-group">
                  <label for="name">Nama Lengkap</label>
                  <input type="text" id="name" name="name" value="<?= e($inputName) ?>" required maxlength="100">
                  <?php if (isset($errors['name'])): ?><p class="error-text"><?= e($errors['name']) ?></p><?php endif; ?>
              </div>

              <div class="form-group">
                  <label for="email">Email (Tidak dapat diubah)</label>
                  <input type="email" id="email" value="<?= e($userData['email']) ?>" readonly disabled>
              </div>

              <div class="form-group">
                  <label for="phone">Nomor Telepon</label>
                  <input type="text" id="phone" name="phone" value="<?= e($inputPhone) ?>" required maxlength="20">
                  <?php if (isset($errors['phone'])): ?><p class="error-text"><?= e($errors['phone']) ?></p><?php endif; ?>
              </div>

              <div class="form-group">
                  <label for="address">Alamat (Opsional)</label>
                  <textarea id="address" name="address" rows="3"><?= e($inputAddress) ?></textarea>
              </div>

              <?php if (isset($errors['general'])): ?><p class="error-text" style="margin-bottom: 10px;"><?= e($errors['general']) ?></p><?php endif; ?>
              
              <button type="submit" class="button button-primary">Simpan Perubahan</button>
          </form>
      </div>
      
      <div class="card">
          <h4 style="margin-top:0;">Ganti Kata Sandi</h4>
          <form method="post" action="<?= e($BASE) ?>/profil-edit.php">
              <input type="hidden" name="action" value="update_password">

              <div class="form-group">
                  <label for="current_password">Kata Sandi Saat Ini</label>
                  <input type="password" id="current_password" name="current_password" required>
                  <?php if (isset($errors['current_password'])): ?><p class="error-text"><?= e($errors['current_password']) ?></p><?php endif; ?>
              </div>

              <div class="form-group">
                  <label for="new_password">Kata Sandi Baru (min 8 karakter)</label>
                  <input type="password" id="new_password" name="new_password" required minlength="8">
                  <?php if (isset($errors['new_password'])): ?><p class="error-text"><?= e($errors['new_password']) ?></p><?php endif; ?>
              </div>

              <div class="form-group">
                  <label for="confirm_password">Konfirmasi Kata Sandi Baru</label>
                  <input type="password" id="confirm_password" name="confirm_password" required>
                  <?php if (isset($errors['confirm_password'])): ?><p class="error-text"><?= e($errors['confirm_password']) ?></p><?php endif; ?>
              </div>

              <?php if (isset($errors['pass_general'])): ?><p class="error-text" style="margin-bottom: 10px;"><?= e($errors['pass_general']) ?></p><?php endif; ?>
              
              <button type="submit" class="button button-danger">Ganti Kata Sandi</button>
          </form>
      </div>
      

    </section>

  </div>

  <aside class="sidebar">
    <div class="card sidebar-nav-card">
      <h4>Navigasi Profil</h4>
      <ul class="sidebar-nav-list">
        <li><a href="<?= e($BASE) ?>/profil-edit.php" class="active-link">Edit Profil</a></li>
      </ul>
    </div>
  </aside>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>