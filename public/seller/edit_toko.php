<?php
// ===============================================================
// NearBuy – Edit Data & Lokasi Toko Seller
// ===============================================================
declare(strict_types=1);

// includes
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/db.php';

// deteksi base path
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$BASE = preg_replace('~/seller$~', '', rtrim($scriptDir, '/'));

// helper escape
if (!function_exists('e')) {
    function e(string $s): string {
        return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
    }
}

// 1. Validasi Login dan Peran Seller
$user = $_SESSION['user'] ?? null;
if (!$user || empty($user['id']) || ($user['role'] ?? '') !== 'seller') {
    header('Location: ' . $BASE . '/login.php');
    exit;
}
$userId = (int)$user['id'];

// 2. Ambil Data Toko
$stmtShop = $pdo->prepare("SELECT id, name, address, latitude, longitude FROM shops WHERE user_id = ? LIMIT 1");
$stmtShop->execute([$userId]);
$shop = $stmtShop->fetch(PDO::FETCH_ASSOC);

if (!$shop) {
    // Jika belum punya toko, arahkan untuk daftar/buat toko dulu
    $_SESSION['flash'] = 'Anda harus mendaftar toko terlebih dahulu.';
    header('Location: ' . $BASE . '/seller/toko.php');
    exit;
}

$shopId = (int)$shop['id'];
$flash  = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

// 3. Logika Pembaruan (Menggunakan Form POST Biasa)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_location') {
    // 💡 Implementasi keamanan CSRF di sini, misalnya:
    /*
    if (!function_exists('csrf_verify') || !csrf_verify($_POST['csrf'] ?? '')) {
        $_SESSION['flash'] = 'Error: Sesi berakhir atau token tidak valid.';
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }
    */

    try {
        $address = trim($_POST['address'] ?? '');
        // Menggunakan filter_var untuk memastikan float yang valid
        $lat     = filter_var($_POST['latitude'] ?? 0, FILTER_VALIDATE_FLOAT);
        $lng     = filter_var($_POST['longitude'] ?? 0, FILTER_VALIDATE_FLOAT);

        if ($address === '' || $lat === false || $lng === false) {
            // Jika koordinat nol (0.0), biarkan karena mungkin valid. Cek hanya jika filter_var gagal.
            throw new RuntimeException('Alamat, Latitude, dan Longitude wajib diisi atau tidak valid.');
        }

        // Query UPDATE ke tabel shops
        $stmt = $pdo->prepare("
            UPDATE shops 
            SET address = ?, latitude = ?, longitude = ?, updated_at = NOW() 
            WHERE id = ? AND user_id = ?
        ");
        $stmt->execute([$address, $lat, $lng, $shopId, $userId]);

        $_SESSION['flash'] = '✅ Lokasi dan Alamat Toko berhasil diperbarui.';
        header('Location: ' . $BASE . '/seller/toko.php'); // Kembali ke dashboard toko
        exit;

    } catch (Throwable $e) {
        $_SESSION['flash'] = 'Error: ' . $e->getMessage();
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }
}

// 4. Tampilan HTML Form
require_once __DIR__ . '/../../includes/header.php'; // Muat header setelah pemrosesan POST

// Ambil nilai awal untuk peta (jika ada)
$startLat = $shop['latitude'] ?: -2.5489; // Default Indonesia tengah
$startLng = $shop['longitude'] ?: 118.0149;

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Atur Lokasi Toko | NearBuy</title>

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    
    <link rel="stylesheet" href="<?= e($BASE) ?>/style.css"> 
    <style>
        /* CSS Anda di sini */
        .lokasi-wrapper { max-width: 900px; margin: 20px auto; background: #ffffff; border-radius: 16px; padding: 18px 18px 22px; box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08); }
        #map { width: 100%; height: 380px; border-radius: 14px; margin-top: 12px; overflow: hidden; }
        .form-input { width: 100%; padding: 10px; margin-bottom: 10px; border: 1px solid #ccc; border-radius: 8px; box-sizing: border-box; }
        .form-group { margin-bottom: 15px; }
        .btn-primary { background: #1e3a8a; color: #ffffff; border: none; border-radius: 999px; padding: 10px 18px; font-weight: 600; cursor: pointer; }
        .btn-outline { background: #f9fafb; border: 1px solid #e5e7eb; padding: 8px 14px; cursor: pointer; }
        .nb-actions { display: flex; gap: 10px; margin-top: 15px; }
    </style>
</head>
<body>

<div class="page-wrap">
    <div class="content">
        <div class="lokasi-wrapper">
            <h2>Atur Lokasi Toko: <?= e($shop['name'] ?? 'Toko Saya') ?></h2>
            <p class="info-text">
                Update **Alamat Fisik Toko** dan **Koordinat GPS** agar akurat saat dilihat pembeli di peta.
            </p>

            <?php if ($flash): ?>
                <div style="background:#dcfce7; color:#166534; padding:10px; border-radius:8px; margin-bottom:15px;"><?= e($flash) ?></div>
            <?php endif; ?>

            <form method="post">
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="update_location">

                <div class="form-group">
                    <label>Alamat Toko</label>
                    <textarea name="address" class="form-input" rows="3" required><?= e($shop['address'] ?? '') ?></textarea>
                </div>
                
                <div id="map"></div>

                <div class="nb-actions" style="justify-content: space-between;">
                    <button type="button" class="btn-outline" id="btnMyLocation">
                        Gunakan lokasi perangkat saya
                    </button>
                    <div>
                        <a class="btn-outline" href="<?= e($BASE) ?>/seller/toko.php">Batal</a>
                        <button type="submit" class="btn-primary">Simpan Perubahan Toko</button>
                    </div>
                </div>
                
                <div class="form-group" style="margin-top: 15px;">
                    <label>Koordinat GPS</label>
                    <input type="text" name="latitude" id="latInput" class="form-input" placeholder="Latitude" required readonly
                        value="<?= e((string)$startLat) ?>">
                    <input type="text" name="longitude" id="lngInput" class="form-input" placeholder="Longitude" required readonly
                        value="<?= e((string)$startLng) ?>">
                </div>
            </form>

        </div>
    </div>
</div>

<script>
    // 5. Inisialisasi Peta Leaflet dan Marker
    const startLat  = parseFloat(document.getElementById('latInput').value);
    const startLng  = parseFloat(document.getElementById('lngInput').value);
    const defaultZoom = <?= $shop['latitude'] && $shop['longitude'] ? 15 : 5 ?>; // Zoom lebih dekat jika koordinat sudah diset

    const map = L.map('map').setView([startLat, startLng], defaultZoom);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    const marker = L.marker([startLat, startLng], { draggable: true }).addTo(map);

    function updateCoordInputs(lat, lng) {
        document.getElementById('latInput').value = lat.toFixed(7);
        document.getElementById('lngInput').value = lng.toFixed(7);
    }

    marker.on('dragend', function (e) {
        const pos = marker.getLatLng();
        updateCoordInputs(pos.lat, pos.lng);
    });

    // tombol "Gunakan lokasi perangkat saya"
    document.getElementById('btnMyLocation').addEventListener('click', function () {
        if (!navigator.geolocation) {
            alert('Browser kamu tidak mendukung geolocation');
            return;
        }

        navigator.geolocation.getCurrentPosition(function (pos) {
            const lat = pos.coords.latitude;
            const lng = pos.coords.longitude;
            marker.setLatLng([lat, lng]);
            map.setView([lat, lng], 15);
            updateCoordInputs(lat, lng);
        }, function () {
            alert('Gagal mengambil lokasi dari perangkat');
        });
    });
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
</body>
</html>