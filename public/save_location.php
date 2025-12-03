<?php
// ===============================================================
// NearBuy – Simpan Lokasi User ke Database
// ===============================================================
declare(strict_types=1);

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');

// deteksi base url kalau nanti mau dipakai
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$BASE = rtrim($scriptDir, '/');

// pastikan user sudah login
$user = $_SESSION['user'] ?? null;
if (!$user || empty($user['id'])) {
    http_response_code(401);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Kamu harus login dulu'
    ]);
    exit;
}

$userId = (int)$user['id'];

// ambil data dari POST
$lat = isset($_POST['lat']) ? (float)$_POST['lat'] : null;
$lng = isset($_POST['lng']) ? (float)$_POST['lng'] : null;

if ($lat === 0.0 && $lng === 0.0) {
    http_response_code(400);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Koordinat tidak valid'
    ]);
    exit;
}

if ($lat === null || $lng === null) {
    http_response_code(400);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Koordinat tidak lengkap'
    ]);
    exit;
}

try {
    // update kolom latitude dan longitude
    $stmt = $pdo->prepare("
        UPDATE users
        SET latitude = ?, longitude = ?
        WHERE id = ?
        LIMIT 1
    ");
    $stmt->execute([$lat, $lng, $userId]);

    // simpan juga ke session
    $_SESSION['user_lat'] = $lat;
    $_SESSION['user_lng'] = $lng;

    // update user di session agar konsisten
    $_SESSION['user']['latitude'] = $lat;
    $_SESSION['user']['longitude'] = $lng;

    echo json_encode([
        'status'  => 'ok',
        'message' => 'Lokasi berhasil disimpan',
        'lat'     => $lat,
        'lng'     => $lng
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Gagal menyimpan lokasi'
    ]);
}
