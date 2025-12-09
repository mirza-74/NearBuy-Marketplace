<?php
// ===============================================================
// NearBuy – Pesan Sekarang (buat pesanan 1 produk)
// ===============================================================
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

$BASE = '/NearBuy-marketplace/public';

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/settings.php';

// helper escape (jaga-jaga kalau belum ada)
if (!function_exists('e')) {
    function e($s): string {
        return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
    }
}

// Hanya boleh via POST
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method !== 'POST') {
    header('Location: ' . $BASE . '/index.php');
    exit;
}

// Cek CSRF (token otomatis diambil dari $_POST['csrf'] / $_POST['csrf_token'])
if (function_exists('csrf_verify')) {
    if (!csrf_verify()) {
        $_SESSION['flash'] = [
            'type'    => 'error',
            'message' => 'Sesi formulir sudah tidak valid. Silakan coba lagi.'
        ];
        header('Location: ' . $BASE . '/index.php');
        exit;
    }
}

// Cek login
$user = $_SESSION['user'] ?? null;
if (!$user || empty($user['id'])) {
    $_SESSION['flash'] = [
        'type'    => 'error',
        'message' => 'Silakan login terlebih dahulu untuk melakukan pemesanan.'
    ];
    header('Location: ' . $BASE . '/login.php');
    exit;
}

$userId = (int)$user['id'];

// Ambil data dari POST
$productId = (int)($_POST['product_id'] ?? 0);
$qty       = (int)($_POST['qty'] ?? 1);
if ($qty < 1) {
    $qty = 1;
}

if ($productId <= 0) {
    $_SESSION['flash'] = [
        'type'    => 'error',
        'message' => 'Produk tidak valid.'
    ];
    header('Location: ' . $BASE . '/index.php');
    exit;
}

// Ambil produk dari DB
$stmt = $pdo->prepare("
    SELECT 
      p.id,
      p.slug,
      p.title,
      p.price,
      p.stock,
      p.is_active
    FROM products p
    WHERE p.id = ?
    LIMIT 1
");
$stmt->execute([$productId]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product || !(int)$product['is_active']) {
    $_SESSION['flash'] = [
        'type'    => 'error',
        'message' => 'Produk tidak tersedia atau sudah tidak aktif.'
    ];
    header('Location: ' . $BASE . '/index.php');
    exit;
}

$slug   = (string)$product['slug'];
$title  = (string)$product['title'];
$stock  = (int)$product['stock'];
$price  = (float)$product['price'];

if ($stock <= 0) {
    $_SESSION['flash'] = [
        'type'    => 'error',
        'message' => 'Stok produk habis.'
    ];
    header('Location: ' . $BASE . '/detail_produk.php?slug=' . urlencode($slug));
    exit;
}

if ($qty > $stock) {
    $_SESSION['flash'] = [
        'type'    => 'error',
        'message' => 'Stok tidak mencukupi. Stok tersedia: ' . $stock . ' pcs.'
    ];
    header('Location: ' . $BASE . '/detail_produk.php?slug=' . urlencode($slug));
    exit;
}

// ========== Proses buat pesanan ==========
try {
    $pdo->beginTransaction();

    $total_items        = $qty;
    $total_barang       = $price * $qty;
    $admin_fee_per_item = 1500.00;
    $total_pajak_admin  = $admin_fee_per_item * $qty;
    $total_ongkir       = 0.00;  // untuk sekarang 0, nanti bisa dihitung dari jarak
    $total_diskon       = 0.00;
    $grand_total        = $total_barang + $total_ongkir + $total_pajak_admin - $total_diskon;

    // =====================================================
    // INSERT ke tabel orders
    // Tabel: orders(
    //   id, user_id, cart_id, total_items, total_barang,
    //   total_ongkir, total_pajak_admin, total_diskon, grand_total,
    //   payment_method, note, ship_name, ship_phone,
    //   ship_address, ship_city, ship_province, ship_postal_code,
    //   status, created_at, updated_at
    // )
    // Banyak kolom punya DEFAULT, jadi kita isi yang penting saja
    // =====================================================
    $stmtOrder = $pdo->prepare("
        INSERT INTO orders (
            user_id,
            total_items,
            total_barang,
            total_ongkir,
            total_pajak_admin,
            total_diskon,
            grand_total
        ) VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmtOrder->execute([
        $userId,
        $total_items,
        $total_barang,
        $total_ongkir,
        $total_pajak_admin,
        $total_diskon,
        $grand_total
    ]);
    $orderId = (int)$pdo->lastInsertId();

    // =====================================================
    // INSERT ke tabel order_items
    // Tabel: order_items(
    //   id, order_id, product_id, variant_id, title,
    //   qty, price, subtotal, admin_fee_per_item,
    //   admin_fee_total, created_at
    // )
    // =====================================================
    $subtotal          = $price * $qty;
    $admin_fee_total   = $admin_fee_per_item * $qty;

    $stmtItem = $pdo->prepare("
        INSERT INTO order_items (
            order_id,
            product_id,
            variant_id,
            title,
            qty,
            price,
            subtotal,
            admin_fee_per_item,
            admin_fee_total
        ) VALUES (?, ?, NULL, ?, ?, ?, ?, ?, ?)
    ");
    $stmtItem->execute([
        $orderId,
        $productId,
        $title,
        $qty,
        $price,
        $subtotal,
        $admin_fee_per_item,
        $admin_fee_total
    ]);

    // Kurangi stok produk
    $stmtStock = $pdo->prepare("
        UPDATE products 
        SET stock = stock - ? 
        WHERE id = ? AND stock >= ?
    ");
    $stmtStock->execute([$qty, $productId, $qty]);

    $pdo->commit();

    $_SESSION['flash'] = [
        'type'    => 'success',
        'message' => 'Pesanan kamu sudah dibuat. Silakan cek di menu pesanan.'
    ];

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    // Debug ke log (optional)
    // error_log('Pesan.php error: ' . $e->getMessage());

    $_SESSION['flash'] = [
        'type'    => 'error',
        'message' => 'Terjadi kesalahan saat membuat pesanan. Coba lagi beberapa saat lagi.'
    ];
}

// Setelah selesai, kembali ke halaman detail produk
header('Location: ' . $BASE . '/detail_produk.php?slug=' . urlencode($slug));
exit;
