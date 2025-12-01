<?php
// ===============================================================
// SellExa – Registrasi (simpan ke DB + tampilkan popup sukses)
// ===============================================================
declare(strict_types=1);

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';

// ====== DEBUG (nyalakan saat tes, matikan di produksi) ======
const DEBUG = false;
if (DEBUG) {
  ini_set('display_errors','1');
  ini_set('display_startup_errors','1');
  error_reporting(E_ALL);
}

// Sesuaikan dengan folder kamu:
$BASE = '/Marketplace_SellExa/public';

$error = '';
$success = false;
$old = [
  'full_name'    => '',
  'email'        => '',
  'phone'        => '',
  'gender'       => '',
  'age'          => '',
  'city'         => '',
  'fav_category' => '',
  'frequency'    => '',
  'budget'       => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // --- Verifikasi CSRF (wajib kalau session.php kamu aktifkan helpernya) ---
    if (!function_exists('csrf_verify') || !csrf_verify($_POST['csrf'] ?? '')) {
        $error = 'Sesi berakhir atau token tidak valid. Silakan muat ulang halaman.';
    } else {
        // Ambil input
        foreach ($old as $k => $_) { $old[$k] = trim($_POST[$k] ?? ''); }
        $password = trim($_POST['password'] ?? '');
        $confirm  = trim($_POST['confirm'] ?? '');

        // Validasi dasar
        if ($old['email'] === '' || $old['full_name'] === '' || $password === '' || $confirm === '') {
            $error = 'Harap isi semua kolom wajib (Nama, Email, Password).';
        } elseif (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
            $error = 'Format email tidak valid.';
        } elseif (strlen($password) < 6) {
            $error = 'Password minimal 6 karakter.';
        } elseif ($password !== $confirm) {
            $error = 'Kata sandi tidak cocok.';
        } else {
            try {
                if (!isset($pdo) || !($pdo instanceof PDO)) {
                    throw new RuntimeException('Koneksi database tidak tersedia.');
                }

                // Cek email unik
                $check = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
                $check->execute([$old['email']]);
                if ($check->fetch()) {
                    $error = 'Email sudah digunakan.';
                } else {
                    // Insert user dalam transaksi kecil agar konsisten
                    $pdo->beginTransaction();

                    $hash = password_hash($password, PASSWORD_DEFAULT);

                    // Simpan user (kolom sesuai skema tabel kamu)
                    $ins = $pdo->prepare("
                        INSERT INTO users (email, password_hash, full_name, phone, gender, city, role, is_active)
                        VALUES (?, ?, ?, ?, ?, ?, 'pengguna', 1)
                    ");
                    $ins->execute([
                        $old['email'], $hash, $old['full_name'], $old['phone'],
                        $old['gender'] ?: null, $old['city'] ?: null
                    ]);

                    $userId = (int)$pdo->lastInsertId();

                    // Commit dulu supaya akun pasti tersimpan
                    $pdo->commit();
                    $success = true;

                    // Setelah akun ada, coba simpan preferensi kategori (tidak mem-block sukses akun)
                    if ($old['fav_category'] !== '') {
                        try {
                            $items = array_filter(array_map('trim', explode(',', $old['fav_category'])));
                            if ($items) {
                                $find = $pdo->prepare("
                                    SELECT id FROM categories
                                    WHERE LOWER(name)=LOWER(?) OR slug=?
                                    LIMIT 1
                                ");
                                $insPref = $pdo->prepare("
                                    INSERT IGNORE INTO user_preferences (user_id, category_id) VALUES (?, ?)
                                ");
                                foreach ($items as $raw) {
                                    if ($raw === '') continue;
                                    $slug = strtolower(preg_replace('~\s+~', '-', $raw));
                                    $find->execute([$raw, $slug]);
                                    if ($cat = $find->fetch(PDO::FETCH_ASSOC)) {
                                        $insPref->execute([$userId, (int)$cat['id']]);
                                    }
                                }
                            }
                        } catch (Throwable $prefErr) {
                            if (DEBUG) { error_log('[PREF_ERR] ' . $prefErr->getMessage()); }
                            // Biarkan; akun sudah sukses dibuat.
                        }
                    }

                    // Bersihkan input lama
                    foreach ($old as $k => $_) $old[$k] = '';
                }
            } catch (Throwable $e) {
                if (isset($pdo) && $pdo instanceof PDO) {
                    try { if ($pdo->inTransaction()) { $pdo->rollBack(); } } catch (Throwable $__) {}
                }
                $error = 'Terjadi kesalahan saat menyimpan data. Silakan coba lagi.';
                if (DEBUG) { $error .= ' [DEBUG: ' . $e->getMessage() . ']'; }
            }
        }
    }
}

function h(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Registrasi | SellExa</title>
  <link rel="stylesheet" href="<?= h($BASE) ?>/style-auth.css">
  <style>
    .modal-mask { position: fixed; inset: 0; background: rgba(0,0,0,.55);
      display: flex; align-items: center; justify-content: center; z-index: 9999; animation: fadeIn .25s ease; }
    .modal-card { width: min(90vw, 420px); background: #fff; border-radius: 16px;
      padding: 26px 20px 24px; text-align: center; box-shadow: 0 8px 20px rgba(0,0,0,.2); animation: popIn .3s ease; }
    .modal-card h3 { margin-top: 0; font-size: 1.4rem; color: #222; }
    .modal-card p { margin: 6px 0 18px; color: #555; }
    .modal-actions { display: flex; gap: 10px; justify-content: center; flex-wrap: wrap; }
    .btn { padding: 10px 18px; border-radius: 8px; font-weight: 600; text-decoration: none; border: none; cursor: pointer; transition: .2s ease; }
    .btn-primary { background: #1e2a47; color: #fff; }
    .btn-secondary { background: #f3f4f6; color: #111; }
    .btn:hover { opacity: .9; }
    @keyframes fadeIn { from {opacity:0;} to {opacity:1;} }
    @keyframes popIn { from {transform:scale(.95);opacity:0;} to {transform:scale(1);opacity:1;} }
    .error { background:#fee2e2; color:#b91c1c; padding:10px 12px; border-radius:8px; margin:12px 0; }
  </style>
</head>
<body>
<div class="auth-container">
  <!-- Branding kiri -->
  <div class="auth-left">
    <img src="<?= h($BASE) ?>/assets/logo-sellexa.png" alt="SellExa" class="logo-auth">
    <h1>SellExa</h1>
    <p>Gabung Sekarang dan Mulai Belanja Lebih Cerdas</p>
  </div>

  <!-- Form kanan -->
  <div class="auth-right">
    <h2>Registrasi Akun Baru</h2>

    <?php if ($error): ?>
      <div class="error"><?= h($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="" class="form-auth" autocomplete="off" novalidate>
      <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">

      <label>Nama Lengkap</label>
      <input type="text" name="full_name" value="<?= h($old['full_name']) ?>" required>

      <label>Email</label>
      <input type="email" name="email" value="<?= h($old['email']) ?>" required>

      <label>Nomor Telepon</label>
      <input type="text" name="phone" value="<?= h($old['phone']) ?>">

      <label>Password</label>
      <input type="password" name="password" required>

      <label>Konfirmasi Password</label>
      <input type="password" name="confirm" required>

      <label>Jenis Kelamin</label>
      <select name="gender">
        <option value="" <?= $old['gender']===''?'selected':'' ?>>- Pilih -</option>
        <option value="male"   <?= $old['gender']==='male'?'selected':'' ?>>Laki-laki</option>
        <option value="female" <?= $old['gender']==='female'?'selected':'' ?>>Perempuan</option>
        <option value="other"  <?= $old['gender']==='other'?'selected':'' ?>>Lainnya</option>
      </select>

      <label>Usia</label>
      <input type="number" name="age" min="10" max="100" placeholder="Opsional" value="<?= h($old['age']) ?>">

      <label>Lokasi (Kota)</label>
      <input type="text" name="city" placeholder="Contoh: Bandar Lampung" value="<?= h($old['city']) ?>">

      <label>Kategori Favorit (pisahkan koma)</label>
      <input type="text" name="fav_category" placeholder="Contoh: Fashion, Elektronik" value="<?= h($old['fav_category']) ?>">

      <label>Frekuensi Belanja</label>
      <select name="frequency">
        <option value="" <?= $old['frequency']===''?'selected':'' ?>>- Pilih -</option>
        <option value="jarang" <?= $old['frequency']==='jarang'?'selected':'' ?>>Jarang</option>
        <option value="rutin"  <?= $old['frequency']==='rutin'?'selected':'' ?>>Rutin</option>
        <option value="sering" <?= $old['frequency']==='sering'?'selected':'' ?>>Sering</option>
      </select>

      <label>Budget Bulanan (Rp)</label>
      <input type="number" name="budget" placeholder="Contoh: 1000000" value="<?= h($old['budget']) ?>">

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
    <p>Selamat datang di <b>SellExa</b>! Akun kamu sudah aktif dan siap digunakan.<br>
    Silakan login untuk menikmati fitur rekomendasi produk sesuai preferensimu.</p>
    <div class="modal-actions">
      <a href="<?= h($BASE) ?>/login.php" class="btn btn-primary">Ke Halaman Login</a>
      <a href="<?= h($BASE) ?>/index.php" class="btn btn-secondary">Kembali ke Beranda</a>
    </div>
  </div>
</div>
<?php endif; ?>
</body>
</html>
