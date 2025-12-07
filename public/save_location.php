<?php
// ===============================================================
// NearBuy – Save Lokasi User (AJAX dari set_lokasi.php)
// ===============================================================
declare(strict_types=1);

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json; charset=UTF-8');

// BASE otomatis kalau nanti perlu redirect (saat error)
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$BASE = rtrim($scriptDir, '/');
if ($BASE === '' || $BASE === '/') {
    $BASE = '/NearBuy-marketplace/public';
}

// cek login
$user = $_SESSION['user'] ?? null;
if (!$user || empty($user['id'])) {
    echo json_encode([
        'status'  => 'error',
        'message' => 'Anda harus login dulu.'
    ]);
    exit;
}

$userId = (int)$user['id'];

$lat = isset($_POST['lat']) ? (float)$_POST['lat'] : null;
$lng = isset($_POST['lng']) ? (float)$_POST['lng'] : null;

if ($lat === null || $lng === null) {
    echo json_encode([
        'status'  => 'error',
        'message' => 'Koordinat tidak lengkap.'
    ]);
    exit;
}

// validasi kasar range koordinat
if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
    echo json_encode([
        'status'  => 'error',
        'message' => 'Koordinat tidak valid.'
    ]);
    exit;
}

try {
    // update tabel users → supaya defaultnya ikut data registrasi yg sudah diperbarui
    $stmt = $pdo->prepare("
        UPDATE users
        SET latitude = :lat,
            longitude = :lng
        WHERE id = :id
        LIMIT 1
    ");
    $stmt->execute([
        ':lat' => $lat,
        ':lng' => $lng,
        ':id'  => $userId,
    ]);

    // update session → dipakai index.php, profil.php, toko.php, dll
    $_SESSION['user_lat'] = $lat;
    $_SESSION['user_lng'] = $lng;
    $_SESSION['user']['latitude']  = $lat;
    $_SESSION['user']['longitude'] = $lng;

    echo json_encode([
        'status'  => 'ok',
        'message' => 'Lokasi tersimpan.'
    ]);
} catch (Throwable $e) {
    echo json_encode([
        'status'  => 'error',
        'message' => 'Gagal menyimpan lokasi: '.$e->getMessage()
    ]);
}
