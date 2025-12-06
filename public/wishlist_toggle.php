<?php
// ===============================================================
//  – Toggle Wishlist (tambah / hapus produk dari wishlist)
// ===============================================================
declare(strict_types=1);

$BASE = '/NearBuy-Marketplace/public';

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';

// --- CEK USER LOGIN ---
$user = $_SESSION['user'] ?? null;
if (!$user || empty($user['id'])) {
    $_SESSION['flash'] = 'Silakan login untuk menggunakan wishlist.';
    header('Location: ' . $BASE . '/login.php');
    exit;
}
$userId = (int)$user['id'];

// --- CEK METHOD ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $BASE . '/index.php');
    exit;
}

// --- CEK CSRF (kalau helper ada & token dikirim) ---
if (function_exists('csrf_verify') && isset($_POST['csrf'])) {
    if (!csrf_verify($_POST['csrf'])) {
        $_SESSION['flash'] = 'Sesi berakhir, silakan coba lagi.';
        $back = $_POST['back'] ?? ($BASE . '/index.php');
        header('Location: ' . $back);
        exit;
    }
}

$productId = (int)($_POST['product_id'] ?? 0);
if ($productId <= 0) {
    $_SESSION['flash'] = 'Produk tidak valid.';
    $back = $_POST['back'] ?? ($BASE . '/index.php');
    header('Location: ' . $back);
    exit;
}

try {
    // cek sudah ada di wishlist atau belum
    $check = $pdo->prepare("
        SELECT id 
        FROM wishlists 
        WHERE user_id = ? AND product_id = ? 
        LIMIT 1
    ");
    $check->execute([$userId, $productId]);
    $row = $check->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        // sudah ada → hapus (un-wishlist)
        $del = $pdo->prepare("DELETE FROM wishlists WHERE id = ?");
        $del->execute([(int)$row['id']]);
        $_SESSION['flash'] = 'Produk dihapus dari wishlist.';
    } else {
        // belum ada → insert
        $ins = $pdo->prepare("
            INSERT INTO wishlists (user_id, product_id, created_at)
            VALUES (?, ?, NOW())
        ");
        $ins->execute([$userId, $productId]);
        $_SESSION['flash'] = 'Produk ditambahkan ke wishlist.';
    }

} catch (Throwable $e) {
    if (defined('DEBUG') && DEBUG) {
        $_SESSION['flash'] = 'Error wishlist: ' . $e->getMessage();
    } else {
        $_SESSION['flash'] = 'Terjadi kesalahan saat mengubah wishlist.';
    }
}

// redirect balik ke halaman sebelumnya (index / kategori / hasil search)
$back = $_POST['back'] ?? ($_SERVER['HTTP_REFERER'] ?? ($BASE . '/index.php'));
header('Location: ' . $back);
exit;
