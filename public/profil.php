<?php
declare(strict_types=1);

// ========================
// INCLUDES & CONFIG
// ========================
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';

$BASE = '/NearBuy-Marketplace/public';

function h(?string $s): string {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

$error   = '';
$success = '';

// ========================
// CEK LOGIN
// ========================
$user = $_SESSION['user'] ?? null;
$role = $user['role'] ?? 'guest';

if (!$user || !in_array($role, ['pengguna','seller','admin'], true)) {
    header('Location: '.$BASE.'/login.php?next='.urlencode($BASE.'/profil.php'));
    exit;
}

$userId = (int)$user['id'];

// ========================
// AMBIL DATA USER DARI DB
// ========================
$form = [
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

try {
    $stmt = $pdo->prepare("
        SELECT full_name, email, phone, gender, birth_date,
               address, city, province, postal_code
        FROM users
        WHERE id = ?
        LIMIT 1
    ");
    $stmt->execute([$userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        foreach ($form as $k => $_) {
            $form[$k] = (string)($row[$k] ?? '');
        }
    }
} catch (Throwable $e) {
    $error = 'Gagal memuat data profil.';
}

// ========================
// HANDLE UPDATE (POST)
// ========================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF jika ada
    if (function_exists('csrf_verify')) {
        $token = $_POST['csrf'] ?? '';
        if (!csrf_verify($token)) {
            $error = 'Sesi formulir sudah habis. Silakan muat ulang halaman.';
        }
    }

    // Ambil nilai dari form (supaya tetap muncul di input)
    foreach ($form as $k => $_) {
        $form[$k] = trim((string)($_POST[$k] ?? ''));
    }

    if ($error === '') {
        if ($form['full_name'] === '' || $form['email'] === '') {
            $error = 'Nama lengkap dan email wajib diisi.';
        } elseif (!filter_var($form['email'], FILTER_VALIDATE_EMAIL)) {
            $error = 'Format email tidak valid.';
        } elseif ($form['city'] === '') {
            $error = 'Harap isi kota domisili agar rekomendasi produk bisa disesuaikan.';
        } else {
            try {
                $birthDate = $form['birth_date'] !== '' ? $form['birth_date'] : null;

                $stmt = $pdo->prepare("
                    UPDATE users
                    SET full_name = ?, email = ?, phone = ?, gender = ?, birth_date = ?,
                        address = ?, city = ?, province = ?, postal_code = ?
                    WHERE id = ?
                    LIMIT 1
                ");
                $stmt->execute([
                    $form['full_name'],
                    $form['email'],
                    $form['phone'] ?: null,
                    $form['gender'] ?: null,
                    $birthDate,
                    $form['address'] ?: null,
                    $form['city'] ?: null,
                    $form['province'] ?: null,
                    $form['postal_code'] ?: null,
                    $userId,
                ]);

                // update data di session biar header & lain-lain ikut ter-refresh
                $_SESSION['user']['full_name'] = $form['full_name'];
                $_SESSION['user']['email']     = $form['email'];

                $success = 'Profil berhasil diperbarui.';
            } catch (Throwable $e) {
                $error = 'Terjadi kesalahan saat menyimpan profil.';
            }
        }
    }
}

// ========================
// VIEW
// ========================
$EXTRA_CSS = ['style-profil.css'];
require_once __DIR__ . '/../includes/header.php';
?>

<div class="profile-page-simple">
  <div class="profile-card">
    <h1>Profil Saya</h1>
    <p class="profile-subtitle">
      Perbarui biodata yang kamu isi saat registrasi akun NearBuy.
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

      <label>Nama Lengkap *</label>
      <input type="text" name="full_name" value="<?= h($form['full_name']) ?>" required>

      <label>Email *</label>
      <input type="email" name="email" value="<?= h($form['email']) ?>" required>

      <label>Nomor Telepon</label>
      <input type="text" name="phone" value="<?= h($form['phone']) ?>">

      <div class="profile-row">
        <div class="profile-col">
          <label>Jenis Kelamin</label>
          <select name="gender">
            <option value="" <?= $form['gender']==='' ? 'selected' : '' ?>>- Pilih -</option>
            <option value="male"   <?= $form['gender']==='male'   ? 'selected' : '' ?>>Laki-laki</option>
            <option value="female" <?= $form['gender']==='female' ? 'selected' : '' ?>>Perempuan</option>
            <option value="other"  <?= $form['gender']==='other'  ? 'selected' : '' ?>>Lainnya</option>
          </select>
        </div>
        <div class="profile-col">
          <label>Tanggal Lahir</label>
          <input type="date" name="birth_date" value="<?= h($form['birth_date']) ?>">
        </div>
      </div>

      <label>Alamat Lengkap (Domisili)</label>
      <textarea name="address" rows="3"
        placeholder="Contoh: Jl. Mawar No. 10, Kel. X, Kec. Y"><?= h($form['address']) ?></textarea>

      <label>Kota Domisili *</label>
      <input type="text" name="city" value="<?= h($form['city']) ?>" required>

      <label>Provinsi</label>
      <input type="text" name="province" value="<?= h($form['province']) ?>">

      <label>Kode Pos</label>
      <input type="text" name="postal_code" value="<?= h($form['postal_code']) ?>">

      <button type="submit" class="btn-primary">Simpan Perubahan</button>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
</main>
</body>
</html>
