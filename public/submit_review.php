<?php
// ===============================================================
// SellExa – Kirim Ulasan Produk
// ===============================================================
declare(strict_types=1);

$BASE = '/Marketplace_SellExa/public';

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';

// pastikan user login
$user = $_SESSION['user'] ?? null;
if (!$user || empty($user['id'])) {
    header('Location: '.$BASE.'/login.php');
    exit;
}
$userId = (int)$user['id'];

// hanya POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: '.$BASE.'/index.php');
    exit;
}

// cek CSRF jika ada
if (function_exists('csrf_verify')) {
    if (!csrf_verify($_POST['csrf'] ?? '')) {
        $slug = $_POST['slug'] ?? '';
        $to   = $slug ? $BASE.'/detail_produk.php?slug='.urlencode($slug) : $BASE.'/index.php';
        header('Location: '.$to.'&msg='.urlencode('Sesi berakhir, silakan coba lagi.'));
        exit;
    }
}

$productId = (int)($_POST['product_id'] ?? 0);
$orderId   = (int)($_POST['order_id'] ?? 0);
$rating    = (int)($_POST['rating'] ?? 0);
$comment   = trim((string)($_POST['comment'] ?? ''));
$slug      = trim((string)($_POST['slug'] ?? ''));

$redirect = $slug 
  ? $BASE.'/detail_produk.php?slug='.urlencode($slug) 
  : $BASE.'/index.php';

if ($productId <= 0 || $orderId <= 0 || $rating < 1 || $rating > 5) {
    header('Location: '.$redirect.'&msg='.urlencode('Data ulasan tidak valid.'));
    exit;
}

try {
    // pastikan order milik user dan sudah selesai serta memuat produk ini
    $stmt = $pdo->prepare("
        SELECT o.id, o.status
        FROM orders o
        JOIN order_items oi ON oi.order_id = o.id
        WHERE o.id = ? 
          AND o.user_id = ? 
          AND oi.product_id = ?
        LIMIT 1
    ");
    $stmt->execute([$orderId, $userId, $productId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        header('Location: '.$redirect.'&msg='.urlencode('Pesanan untuk produk ini tidak ditemukan.'));
        exit;
    }

    if ($order['status'] !== 'selesai') {
        header('Location: '.$redirect.'&msg='.urlencode('Kamu hanya bisa mengulas produk dari pesanan yang sudah selesai.'));
        exit;
    }

    // cek apakah sudah pernah mengulas produk ini untuk order tersebut
    $check = $pdo->prepare("
        SELECT id 
        FROM product_reviews
        WHERE user_id = ? AND product_id = ? AND order_id = ?
        LIMIT 1
    ");
    $check->execute([$userId, $productId, $orderId]);
    if ($check->fetch()) {
        header('Location: '.$redirect.'&msg='.urlencode('Kamu sudah menulis ulasan untuk produk ini.'));
        exit;
    }

    // simpan ulasan
    $ins = $pdo->prepare("
        INSERT INTO product_reviews (user_id, product_id, order_id, rating, comment, created_at)
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    $ins->execute([$userId, $productId, $orderId, $rating, $comment !== '' ? $comment : null]);

    header('Location: '.$redirect.'&msg='.urlencode('Terima kasih, ulasanmu sudah tersimpan.'));
    exit;

} catch (Throwable $e) {
    header('Location: '.$redirect.'&msg='.urlencode('Terjadi kesalahan saat menyimpan ulasan.'));
    exit;
}
