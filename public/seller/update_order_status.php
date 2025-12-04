<?php
// ===============================================================
// SellExa – Update Status Pesanan (Admin Action)
// ===============================================================
declare(strict_types=1);

$BASE = '/Marketplace_SellExa/public';

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/settings.php';

// pastikan admin
$user = $_SESSION['user'] ?? null;
if (!$user || ($user['role'] ?? '') !== 'admin') {
    header('Location: ' . $BASE . '/login.php');
    exit;
}

// hanya izinkan POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $BASE . '/admin/pesanan.php');
    exit;
}

// verifikasi CSRF kalau ada helper-nya
if (function_exists('csrf_verify')) {
    $token = $_POST['csrf_token'] ?? '';
    if (!csrf_verify($token)) {
        http_response_code(400);
        echo "<h1>CSRF token tidak valid</h1>";
        echo "<p>Silakan kembali ke halaman pesanan dan coba lagi.</p>";
        echo '<p><a href="'.$BASE.'/admin/pesanan.php">Kembali ke Kelola Pesanan</a></p>';
        exit;
    }
}

$orderId = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
$status  = $_POST['status'] ?? '';

// status yang valid sesuai ENUM di tabel orders
$allowedStatus = [
    'menunggu_pembayaran',
    'diproses',
    'dikirim',
    'selesai',
    'dibatalkan',
];

if ($orderId <= 0 || !in_array($status, $allowedStatus, true)) {
    // input tidak valid
    header('Location: ' . $BASE . '/admin/pesanan.php?error=1');
    exit;
}

// update status
$stmt = $pdo->prepare("
    UPDATE orders
    SET status = :status, updated_at = NOW()
    WHERE id = :id
");
$stmt->execute([
    ':status' => $status,
    ':id'     => $orderId,
]);

// redirect kembali ke kelola pesanan
$redirect = $BASE . '/admin/pesanan.php';

// kalau mau balikin ke halaman sebelumnya (mis: dashboard), bisa pakai HTTP_REFERER
if (!empty($_SERVER['HTTP_REFERER'])) {
    $ref = $_SERVER['HTTP_REFERER'];
    // pastikan masih di area admin (biar aman)
    if (strpos($ref, '/admin/') !== false) {
        $redirect = $ref;
    }
}

header('Location: ' . $redirect);
exit;
