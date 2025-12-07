<?php
// ===============================================================
// NearBuy – Set Lokasi Saya
// ===============================================================
declare(strict_types=1);

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';

// ================== BASE OTOMATIS ==================
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$BASE = rtrim($scriptDir, '/');
if ($BASE === '' || $BASE === '/') {
    $BASE = '/NearBuy-marketplace/public';
}

// cek user login
$user = $_SESSION['user'] ?? null;
if (!$user || empty($user['id'])) {
    header("Location: {$BASE}/login.php");
    exit;
}

$userId = (int)$user['id'];

// helper escape (header.php juga punya, tapi jaga-jaga)
if (!function_exists('e')) {
    function e(string $s): string {
        return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
    }
}

// ================== AMBIL LOKASI AWAL ==================
//
// Prioritas:
// 1) session user_lat / user_lng (kalau sebelumnya sudah set lokasi)
// 2) kolom users.latitude / users.longitude (hasil registrasi)
// 3) fallback Indonesia tengah
//
$userLat = isset($_SESSION['user_lat']) ? (float)$_SESSION['user_lat'] : null;
$userLng = isset($_SESSION['user_lng']) ? (float)$_SESSION['user_lng'] : null;

if ($userLat === null || $userLng === null) {
    $stmt = $pdo->prepare("SELECT latitude, longitude FROM users WHERE id = ? LIMIT 1");
    $stmt->execute([$userId]);
    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if ($row['latitude'] !== null && $row['longitude'] !== null) {
            $userLat = (float)$row['latitude'];
            $userLng = (float)$row['longitude'];

            // simpan ke session supaya dipakai halaman lain
            $_SESSION['user_lat'] = $userLat;
            $_SESSION['user_lng'] = $userLng;
            $_SESSION['user']['latitude']  = $userLat;
            $_SESSION['user']['longitude'] = $userLng;
        }
    }
}

// kalau masih kosong, pakai default Indonesia tengah
if ($userLat === null || $userLng === null) {
    $userLat = -2.5489;
    $userLng = 118.0149;
}

// CSS khusus halaman ini kalau mau (opsional pakai file sendiri)
// $EXTRA_CSS = $EXTRA_CSS ?? [];
// $EXTRA_CSS[] = 'style-lokasi.css';

// panggil header global
require_once __DIR__ . '/../includes/header.php';
?>

<style>
  .lokasi-wrapper {
    max-width: 900px;
    margin: 20px auto;
    background: #ffffff;
    border-radius: 16px;
    padding: 18px 18px 22px;
    box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08);
  }
  #map {
    width: 100%;
    height: 380px;
    border-radius: 14px;
    margin-top: 12px;
    overflow: hidden;
  }
  .lokasi-actions {
    margin-top: 12px;
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    align-items: center;
    justify-content: space-between;
  }
  .btn-primary {
    background: #1e3a8a;
    color: #ffffff;
    border: none;
    border-radius: 999px;
    padding: 10px 18px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
  }
  .btn-outline {
    background: #f9fafb;
    color: #111827;
    border-radius: 999px;
    border: 1px solid #e5e7eb;
    padding: 8px 14px;
    font-size: 13px;
    cursor: pointer;
  }
  .coord-text {
    font-size: 13px;
    color: #4b5563;
    margin-top: 4px;
  }
  .info-text {
    font-size: 13px;
    color: #6b7280;
    margin-top: 6px;
  }
</style>

<!-- Leaflet CSS & JS (boleh di body, browser tidak masalah) -->
<link rel="stylesheet"
      href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
      integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
      crossorigin=""/>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
        crossorigin=""></script>

<div class="page-wrap">
  <div class="content">
    <div class="lokasi-wrapper">
      <h2>Set Lokasi Saya</h2>
      <p class="info-text">
        Titik ini akan dipakai NearBuy untuk menampilkan produk dari seller yang lokasinya paling dekat dengan kamu.
        Kamu bisa geser pin di peta atau gunakan lokasi otomatis dari perangkat.
      </p>

      <div id="map"></div>

      <div class="lokasi-actions">
        <div>
          <button type="button" class="btn-outline" id="btnMyLocation">
            Gunakan lokasi saya
          </button>
          <div class="coord-text" id="coordText">
            Titik saat ini:
            <b>Lat:</b> <span id="latText"><?= e((string)$userLat) ?></span>,
            <b>Lng:</b> <span id="lngText"><?= e((string)$userLng) ?></span>
          </div>
        </div>
        <div>
          <button type="button" class="btn-primary" id="btnSave">
            Simpan Lokasi
          </button>
        </div>
      </div>

      <input type="hidden" id="latInput" value="<?= e((string)$userLat) ?>">
      <input type="hidden" id="lngInput" value="<?= e((string)$userLng) ?>">
    </div>
  </div>
</div>

<script>
  const baseUrl   = <?= json_encode($BASE, JSON_UNESCAPED_SLASHES) ?>;
  const startLat  = parseFloat(document.getElementById('latInput').value);
  const startLng  = parseFloat(document.getElementById('lngInput').value);

  const map = L.map('map').setView([startLat, startLng], 13);

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '&copy; OpenStreetMap contributors'
  }).addTo(map);

  const marker = L.marker([startLat, startLng], { draggable: true }).addTo(map);

  function updateCoordDisplay(lat, lng) {
    document.getElementById('latInput').value = lat.toFixed(7);
    document.getElementById('lngInput').value = lng.toFixed(7);
    document.getElementById('latText').textContent = lat.toFixed(7);
    document.getElementById('lngText').textContent = lng.toFixed(7);
  }

  marker.on('dragend', function () {
    const pos = marker.getLatLng();
    updateCoordDisplay(pos.lat, pos.lng);
  });

  // tombol "Gunakan lokasi saya"
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
      updateCoordDisplay(lat, lng);
    }, function () {
      alert('Gagal mengambil lokasi dari perangkat');
    });
  });

  // tombol "Simpan Lokasi"
  document.getElementById('btnSave').addEventListener('click', function () {
    const lat = document.getElementById('latInput').value;
    const lng = document.getElementById('lngInput').value;

    const formData = new FormData();
    formData.append('lat', lat);
    formData.append('lng', lng);

    fetch(baseUrl + '/save_location.php', {
      method: 'POST',
      body: formData
    })
      .then(r => r.json())
      .then(data => {
        if (data.status === 'ok') {
          alert('Lokasi berhasil disimpan. Rekomendasi akan menyesuaikan lokasi kamu.');
          window.location.href = baseUrl + '/index.php';
        } else {
          alert(data.message || 'Gagal menyimpan lokasi');
        }
      })
      .catch(() => {
        alert('Terjadi kesalahan jaringan');
      });
  });
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
</main>
</body>
</html>
