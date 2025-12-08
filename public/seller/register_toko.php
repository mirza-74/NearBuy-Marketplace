<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/db.php';

$BASE = '/NearBuy-Marketplace/public';

$error = '';

if (!function_exists('h')) {
    function h(?string $s): string {
        return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
    }
}

// ========================
// Wajib login dulu
// ========================
$user = $_SESSION['user'] ?? null;
if (!$user || empty($user['id'])) {
    header('Location: ' . $BASE . '/login.php');
    exit;
}

$userId = (int)$user['id'];

// ========================
// Cek apakah user sudah punya toko
// ========================
try {
    $checkShop = $pdo->prepare("SELECT id FROM shops WHERE user_id = ? LIMIT 1");
    $checkShop->execute([$userId]);
    $existingShop = $checkShop->fetch(PDO::FETCH_ASSOC);

    if ($existingShop) {
        // Sudah punya toko → langsung ke halaman Toko Saya
        header('Location: ' . $BASE . '/seller/toko.php');
        exit;
    }
} catch (Throwable $e) {
    // kalau error, tetap tampilkan form, tapi kamu bisa log error di sini kalau mau
}

// ========================
// Default koordinat: coba pakai dari users, fallback Indonesia tengah
// ========================
$defaultLat = -2.5489;
$defaultLng = 118.0149;

try {
    $stmtUserLoc = $pdo->prepare("SELECT latitude, longitude FROM users WHERE id = ? LIMIT 1");
    $stmtUserLoc->execute([$userId]);
    if ($row = $stmtUserLoc->fetch(PDO::FETCH_ASSOC)) {
        if (!is_null($row['latitude']) && !is_null($row['longitude'])) {
            $defaultLat = (float)$row['latitude'];
            $defaultLng = (float)$row['longitude'];
        }
    }
} catch (Throwable $e) {
    // abaikan, pakai default
}

// ========================
// Konfigurasi upload logo toko
// ========================
$UPLOAD_DIR_FS  = dirname(__DIR__) . '/uploads/shops';      // /public/uploads/shops
$UPLOAD_DIR_URL = $BASE . '/uploads/shops';                 // URL akses
$MAX_SIZE       = 3 * 1024 * 1024;                          // 3MB
$ALLOWED_MIME   = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
];

if (!is_dir($UPLOAD_DIR_FS)) {
    @mkdir($UPLOAD_DIR_FS, 0775, true);
}

// helper upload logo
function move_uploaded_logo(array $f, string $UPLOAD_DIR_FS, array $ALLOWED_MIME, int $MAX_SIZE): string {
    if (($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return '';
    }

    if ($f['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Gagal upload logo. Kode error: ' . $f['error']);
    }

    if ($f['size'] > $MAX_SIZE) {
        throw new RuntimeException('Ukuran logo melebihi 3MB.');
    }

    $fi   = new finfo(FILEINFO_MIME_TYPE);
    $mime = $fi->file($f['tmp_name']) ?: '';

    if (!isset($ALLOWED_MIME[$mime])) {
        throw new RuntimeException('Tipe logo tidak didukung. Gunakan JPG, PNG, atau WebP.');
    }

    $ext   = $ALLOWED_MIME[$mime];
    $stamp = date('Ymd_His');
    $rand  = bin2hex(random_bytes(3));
    $fname = "shop_{$stamp}_{$rand}.{$ext}";

    $dest = rtrim($UPLOAD_DIR_FS, '/\\') . '/' . $fname;

    if (!move_uploaded_file($f['tmp_name'], $dest)) {
        throw new RuntimeException('Gagal menyimpan file logo.');
    }

    // disimpan relatif dari folder /uploads
    return 'shops/' . $fname;
}

// ========================
// nilai lama untuk refill form
// ========================
$old = [
    'name'        => '',
    'address'     => '',
    'latitude'    => (string)$defaultLat,
    'longitude'   => (string)$defaultLng,
    'description' => '',
];

// ========================
// PROSES SUBMIT FORM
// ========================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // CSRF jika tersedia
    if (function_exists('csrf_verify')) {
        $token = $_POST['csrf'] ?? '';
        if (!csrf_verify($token)) {
            $error = 'Sesi formulir sudah habis. Silakan muat ulang halaman dan coba lagi.';
        }
    }

    foreach ($old as $k => $_) {
        $old[$k] = trim((string)($_POST[$k] ?? ''));
    }

    if ($error === '') {
        // ===== Validasi dasar =====
        if ($old['name'] === '' || $old['address'] === '' || $old['latitude'] === '' || $old['longitude'] === '') {
            $error = 'Nama toko, alamat, dan koordinat lokasi wajib diisi.';
        } elseif (!is_numeric($old['latitude']) || !is_numeric($old['longitude'])) {
            $error = 'Latitude dan longitude harus berupa angka (format desimal).';
        } else {
            $lat = (float)$old['latitude'];
            $lng = (float)$old['longitude'];

            if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
                $error = 'Koordinat lokasi di luar jangkauan. Periksa kembali titik peta.';
            }
        }
    }

    // ===== Proses upload logo jika tidak ada error validasi =====
    $logoPath = null;
    if ($error === '' && !empty($_FILES['logo_file']) && ($_FILES['logo_file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        try {
            $logoRel = move_uploaded_logo($_FILES['logo_file'], $UPLOAD_DIR_FS, $ALLOWED_MIME, $MAX_SIZE);
            if ($logoRel !== '') {
                $logoPath = $logoRel; // contoh: "shops/shop_2025xxxx.jpg"
            }
        } catch (Throwable $e) {
            $error = $e->getMessage();
        }
    }

    if ($error === '') {
        try {
            if (!isset($pdo) || !($pdo instanceof PDO)) {
                throw new RuntimeException('Koneksi database tidak tersedia.');
            }

            // Pastikan belum keburu punya toko (race condition)
            $checkShop2 = $pdo->prepare("SELECT id FROM shops WHERE user_id = ? LIMIT 1");
            $checkShop2->execute([$userId]);
            if ($checkShop2->fetch(PDO::FETCH_ASSOC)) {
                header('Location: ' . $BASE . '/seller/toko.php');
                exit;
            }

            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

            $pdo->beginTransaction();

            // INSERT ke tabel shops
            // Pastikan tabel shops punya kolom: logo (VARCHAR NULL) & created_at
            $stmt = $pdo->prepare("
                INSERT INTO shops
                    (user_id, name, address, latitude, longitude, description, logo, is_active, created_at)
                VALUES
                    (?, ?, ?, ?, ?, ?, ?, 0, NOW())
            ");

            $stmt->execute([
                $userId,
                $old['name'],
                $old['address'],
                (float)$old['latitude'],
                (float)$old['longitude'],
                $old['description'] ?: null,
                $logoPath, // boleh null
            ]);

            $pdo->commit();

            // Setelah berhasil, arahkan ke halaman Toko Saya
            // Di sana akan muncul status "Menunggu persetujuan admin" (is_active = 0)
            $_SESSION['flash'] = 'Toko berhasil didaftarkan. Menunggu persetujuan admin.';
            header('Location: ' . $BASE . '/seller/toko.php');
            exit;

        } catch (Throwable $e) {
            if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = 'Terjadi kesalahan saat menyimpan data toko. Silakan coba lagi.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi Toko | NearBuy</title>

    <!-- Pakai style-auth yang sama dengan login/registrasi user -->
    <link rel="stylesheet" href="<?= h($BASE) ?>/style-auth.css">

    <!-- Leaflet CSS -->
    <link rel="stylesheet"
          href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
          integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
          crossorigin=""/>

    <style>
        .two-columns {
            display: flex;
            gap: 20px;
            margin-bottom: 15px;
        }
        .two-columns .input-box {
            flex: 1;
        }
        .two-columns label {
            display: block;
            margin-bottom: 5px;
        }
        .two-columns input {
            width: 100%;
            padding: 9px 10px;
            border-radius: 6px;
            border: 1px solid #d1d5db;
        }
        #map {
            width: 100%;
            height: 260px;
            border-radius: 12px;
            margin-top: 10px;
            margin-bottom: 10px;
            overflow: hidden;
            box-shadow: 0 4px 16px rgba(15,23,42,0.08);
        }
        .logo-hint {
            font-size: 12px;
            color: #6b7280;
            margin-top: 4px;
        }
    </style>
</head>
<body>

<div class="container">

    <!-- LEFT PANEL -->
    <div class="left-panel">
        <img src="<?= h($BASE) ?>/assets/logo_nearbuy.png" alt="NearBuy" class="logo">
        <p class="slogan">
            “Buka toko di NearBuy dan jangkau pembeli terdekat di sekitar domisili kamu.”
        </p>
    </div>

    <!-- RIGHT PANEL -->
    <div class="right-panel">
        <div class="form-container">

            <h2>Registrasi Toko NearBuy</h2>

            <?php if ($error): ?>
                <div class="alert alert-error" role="alert">
                    <?= h($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" autocomplete="off" novalidate enctype="multipart/form-data">
                <?php if (function_exists('csrf_token')): ?>
                    <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
                <?php endif; ?>

                <!-- Info pemilik (readonly) -->
                <label>Pemilik Akun</label>
                <input type="text" value="<?= h($user['full_name'] ?? '') ?>" readonly>

                <label>Email Akun</label>
                <input type="email" value="<?= h($user['email'] ?? '') ?>" readonly>

                <!-- Data toko -->
                <label for="name">Nama Toko *</label>
                <input id="name" type="text" name="name"
                       value="<?= h($old['name']) ?>" placeholder="Contoh: Warung Harian Makmur" required>

                <label for="address">Alamat Lengkap Toko *</label>
                <textarea id="address" name="address"
                          placeholder="Contoh: Jl. Mawar No. 10, RT 02 / RW 05, Pangkalpinang"
                          style="min-height:70px; resize:vertical;"><?= h($old['address']) ?></textarea>

                <!-- Koordinat + Peta -->
                <div class="two-columns">
                    <div class="input-box">
                        <label for="latitude">Latitude *</label>
                        <input id="latitude" type="text" name="latitude"
                               value="<?= h($old['latitude']) ?>" required>
                    </div>
                    <div class="input-box">
                        <label for="longitude">Longitude *</label>
                        <input id="longitude" type="text" name="longitude"
                               value="<?= h($old['longitude']) ?>" required>
                    </div>
                </div>

                <small style="display:block;margin:-6px 0 8px;font-size:12px;color:#6b7280;">
                    Kamu bisa menggeser pin di peta atau mengisi koordinat manual dari Google Maps.
                </small>

                <div id="map"></div>

                <!-- Logo toko -->
                <label for="logo_file">Logo / Foto Toko (opsional)</label>
                <input id="logo_file" type="file" name="logo_file" accept=".jpg,.jpeg,.png,.webp">
                <div class="logo-hint">
                    Maksimal 3MB. Format yang diizinkan: JPG, PNG, WebP.
                </div>

                <label for="description">Deskripsi Toko (opsional)</label>
                <textarea id="description" name="description"
                          placeholder="Contoh: Menyediakan gas elpiji, air galon, dan kebutuhan harian area Pangkalpinang."><?= h($old['description']) ?></textarea>

                <small style="display:block;margin-top:4px;font-size:12px;color:#6b7280;">
                    Dengan mendaftarkan toko, kamu menyatakan telah membaca dan menyetujui
                    <a href="<?= h($BASE) ?>/seller/peraturan_toko.php" style="color:#1d75ff;">Peraturan Toko NearBuy</a>.
                </small>

                <button type="submit">Daftarkan Toko</button>
            </form>

            <div class="register-text">
                <a href="<?= h($BASE) ?>/seller/toko.php">Kembali ke halaman Toko Saya</a>
            </div>

        </div>
    </div>

</div>

<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
        crossorigin=""></script>

<script>
  const latInput = document.getElementById('latitude');
  const lngInput = document.getElementById('longitude');

  let startLat = parseFloat(latInput.value || '<?= h((string)$defaultLat) ?>');
  let startLng = parseFloat(lngInput.value || '<?= h((string)$defaultLng) ?>');

  if (isNaN(startLat)) startLat = <?= h((string)$defaultLat) ?>;
  if (isNaN(startLng)) startLng = <?= h((string)$defaultLng) ?>;

  const map = L.map('map').setView([startLat, startLng], 14);

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      attribution: '&copy; OpenStreetMap contributors'
  }).addTo(map);

  const marker = L.marker([startLat, startLng], { draggable: true }).addTo(map);

  function updateInputs(lat, lng) {
      latInput.value = lat.toFixed(7);
      lngInput.value = lng.toFixed(7);
  }

  marker.on('dragend', function (e) {
      const pos = marker.getLatLng();
      updateInputs(pos.lat, pos.lng);
  });

  // Jika user ubah manual, update marker
  function moveMarkerFromInput() {
      const lat = parseFloat(latInput.value);
      const lng = parseFloat(lngInput.value);
      if (!isNaN(lat) && !isNaN(lng)) {
          marker.setLatLng([lat, lng]);
          map.setView([lat, lng], 15);
      }
  }

  latInput.addEventListener('change', moveMarkerFromInput);
  lngInput.addEventListener('change', moveMarkerFromInput);
</script>

</body>
</html>
