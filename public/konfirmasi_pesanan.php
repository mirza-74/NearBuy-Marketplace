<?php
// ===============================================================
// SellExa – Konfirmasi Pesanan Sudah Diterima (oleh pembeli)
// ===============================================================
declare(strict_types=1);

$BASE = '/Marketplace_SellExa/public';

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';

// pastikan user login
$user = $_SESSION['user'] ?? null;
if (!$user || empty($user['id'])) {
    header('Location: '.$BASE.'/login.php?next='.urlencode($BASE.'/transaksi.php'));
    exit;
}
$userId = (int)$user['id'];

// hanya boleh POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: '.$BASE.'/transaksi.php');
    exit;
}

// cek CSRF kalau helper ada
if (function_exists('csrf_verify')) {
    if (!csrf_verify($_POST['csrf'] ?? '')) {
        header('Location: '.$BASE.'/transaksi.php?tab=dikirim&msg='.urlencode('Sesi berakhir, silakan coba lagi.'));
        exit;
    }
}

$orderId = (int)($_POST['order_id'] ?? 0);
if ($orderId <= 0) {
    header('Location: '.$BASE.'/transaksi.php?tab=dikirim&msg='.urlencode('Pesanan tidak valid.'));
    exit;
}

try {
    // pastikan pesanan milik user ini dan statusnya dikirim
    $stmt = $pdo->prepare("
        SELECT id, status
        FROM orders
        WHERE id = ? AND user_id = ?
        LIMIT 1
    ");
    $stmt->execute([$orderId, $userId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        header('Location: '.$BASE.'/transaksi.php?tab=dikirim&msg='.urlencode('Pesanan tidak ditemukan.'));
        exit;
    }

    if ($order['status'] !== 'dikirim') {
        header('Location: '.$BASE.'/transaksi.php?tab=dikirim&msg='.urlencode('Pesanan ini belum dalam status dikirim.'));
        exit;
    }

    // ubah status menjadi selesai
    $upd = $pdo->prepare("
        UPDATE orders
        SET status = 'selesai', updated_at = NOW()
        WHERE id = ? AND user_id = ?
    ");
    $upd->execute([$orderId, $userId]);

    header('Location: '.$BASE.'/transaksi.php?tab=tiba&msg='.urlencode('Terima kasih, pesanan sudah dikonfirmasi selesai.'));
    exit;

} catch (Throwable $e) {
    header('Location: '.$BASE.'/transaksi.php?tab=dikirim&msg='.urlencode('Terjadi kesalahan saat konfirmasi pesanan.'));
    exit;
}
