<?php
// ===============================================================
// SellExa – Proses Checkout (buat entri ke orders + order_items)
// ===============================================================
declare(strict_types=1);

$BASE = '/Marketplace_SellExa/public';

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/settings.php';

error_reporting(E_ALL);
ini_set('display_errors', '1');

// ====== Pastikan user login ======
$user = $_SESSION['user'] ?? null;
$role = $user['role'] ?? 'guest';
if (!$user || !in_array($role, ['pengguna','admin'], true)) {
    header('Location: '.$BASE.'/login.php?next='.urlencode($BASE.'/checkout.php'));
    exit;
}
$userId = (int)$user['id'];

// hanya POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: '.$BASE.'/checkout.php');
    exit;
}

// ====== CEK CSRF TOKEN ======
if (function_exists('csrf_verify')) {
    $token = $_POST['csrf_token'] ?? '';
    if (!csrf_verify($token)) {
        http_response_code(400);
        echo "<h1>CSRF token tidak valid</h1>";
        echo "<p>Silakan kembali ke halaman checkout dan coba lagi.</p>";
        echo '<p><a href="'.$BASE.'/checkout.php">Kembali ke Checkout</a></p>';
        exit;
    }
}

// ambil data dasar dari POST
$cartId        = isset($_POST['cart_id']) ? (int)$_POST['cart_id'] : 0;
$cartItemIds   = isset($_POST['cart_item_ids']) ? (array)$_POST['cart_item_ids'] : [];
$paymentMethod = $_POST['payment_method'] ?? 'bca_va';
$note          = trim((string)($_POST['note'] ?? ''));

// data alamat dari user (biar konsisten dengan checkout)
$shipName      = (string)($user['full_name'] ?? '');
$shipPhone     = (string)($user['phone'] ?? '');
$shipAddress   = (string)($user['address'] ?? '');
$shipCity      = (string)($user['city'] ?? '');
$shipProvince  = (string)($user['province'] ?? '');
$shipPostal    = (string)($user['postal_code'] ?? '');

// konstanta ongkir & pajak
$ONGKIR_PER_ITEM = 15000;
$PAJAK_PER_ITEM  = 1500;

if ($cartId <= 0 || empty($cartItemIds)) {
    // input tidak valid
    header('Location: '.$BASE.'/checkout.php');
    exit;
}

try {
    $pdo->beginTransaction();

    // pastikan cart milik user dan masih active
    $stmtCart = $pdo->prepare("
        SELECT *
        FROM carts
        WHERE id = :id
          AND user_id = :uid
          AND status = 'active'
        LIMIT 1
        FOR UPDATE
    ");
    $stmtCart->execute([
        ':id'  => $cartId,
        ':uid' => $userId,
    ]);
    $cartRow = $stmtCart->fetch(PDO::FETCH_ASSOC);
    if (!$cartRow) {
        throw new RuntimeException('Cart tidak ditemukan / sudah tidak aktif.');
    }

    // ambil item yang benar2 ada di cart tsb
    $idsInt = [];
    foreach ($cartItemIds as $cid) {
        $id = (int)$cid;
        if ($id > 0) $idsInt[] = $id;
    }
    if (empty($idsInt)) {
        throw new RuntimeException('Tidak ada item yang dipilih.');
    }

    $placeholders = implode(',', array_fill(0, count($idsInt), '?'));
    $sqlItems = "
        SELECT 
          ci.id   AS cart_item_id,
          ci.product_id,
          ci.qty,
          ci.price,
          p.title,
          p.stock
        FROM cart_items ci
        JOIN products p ON p.id = ci.product_id
        WHERE ci.cart_id = ?
          AND ci.id IN ($placeholders)
        FOR UPDATE
    ";
    $params = array_merge([$cartId], $idsInt);
    $stmtItems = $pdo->prepare($sqlItems);
    $stmtItems->execute($params);
    $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

    if (empty($items)) {
        throw new RuntimeException('Item tidak ditemukan di cart.');
    }

    // hitung total
    $totalQty          = 0;
    $totalBarang       = 0.0;
    $totalAdminFeeItem = 0.0;

    foreach ($items as $it) {
        $qty   = (int)$it['qty'];
        $price = (float)$it['price'];

        if ($qty <= 0) {
            throw new RuntimeException('Qty tidak valid.');
        }
        // cek stok
        $stock = (int)$it['stock'];
        if ($stock < $qty) {
            throw new RuntimeException('Stok produk "'.$it['title'].'" tidak mencukupi.');
        }

        $subtotal = $qty * $price;
        $totalBarang += $subtotal;
        $totalQty    += $qty;

        // fee per item * qty
        $totalAdminFeeItem += $qty * $PAJAK_PER_ITEM;
    }

    $totalOngkir      = $totalQty * $ONGKIR_PER_ITEM;
    $totalPajakAdmin  = $totalAdminFeeItem;  // sama2 1500/item
    $totalDiskon      = 0.0;                 // belum ada voucher beneran
    $grandTotal       = $totalBarang + $totalOngkir + $totalPajakAdmin - $totalDiskon;

    // buat entri di orders
    $stmtOrder = $pdo->prepare("
        INSERT INTO orders (
            user_id,
            cart_id,
            total_items,
            total_barang,
            total_ongkir,
            total_pajak_admin,
            total_diskon,
            grand_total,
            payment_method,
            note,
            ship_name,
            ship_phone,
            ship_address,
            ship_city,
            ship_province,
            ship_postal_code,
            status,
            created_at
        ) VALUES (
            :user_id,
            :cart_id,
            :total_items,
            :total_barang,
            :total_ongkir,
            :total_pajak_admin,
            :total_diskon,
            :grand_total,
            :payment_method,
            :note,
            :ship_name,
            :ship_phone,
            :ship_address,
            :ship_city,
            :ship_province,
            :ship_postal_code,
            :status,
            NOW()
        )
    ");
    $stmtOrder->execute([
        ':user_id'          => $userId,
        ':cart_id'          => $cartId,
        ':total_items'      => $totalQty,
        ':total_barang'     => $totalBarang,
        ':total_ongkir'     => $totalOngkir,
        ':total_pajak_admin'=> $totalPajakAdmin,
        ':total_diskon'     => $totalDiskon,
        ':grand_total'      => $grandTotal,
        ':payment_method'   => $paymentMethod,
        ':note'             => $note,
        ':ship_name'        => $shipName,
        ':ship_phone'       => $shipPhone,
        ':ship_address'     => $shipAddress,
        ':ship_city'        => $shipCity,
        ':ship_province'    => $shipProvince,
        ':ship_postal_code' => $shipPostal,
        // setelah klik "Bayar Sekarang" kita anggap langsung diproses
        ':status'           => 'diproses',
    ]);

    $orderId = (int)$pdo->lastInsertId();

    // buat entri order_items + update stok products
    $stmtInsertItem = $pdo->prepare("
        INSERT INTO order_items (
            order_id,
            product_id,
            variant_id,
            title,
            qty,
            price,
            subtotal,
            admin_fee_per_item,
            admin_fee_total,
            created_at
        ) VALUES (
            :order_id,
            :product_id,
            NULL,
            :title,
            :qty,
            :price,
            :subtotal,
            :admin_fee_per_item,
            :admin_fee_total,
            NOW()
        )
    ");

    $stmtUpdateStock = $pdo->prepare("
        UPDATE products
        SET stock = stock - :qty
        WHERE id = :pid
    ");

    foreach ($items as $it) {
        $qty      = (int)$it['qty'];
        $price    = (float)$it['price'];
        $subtotal = $qty * $price;

        $stmtInsertItem->execute([
            ':order_id'          => $orderId,
            ':product_id'        => (int)$it['product_id'],
            ':title'             => (string)$it['title'],
            ':qty'               => $qty,
            ':price'             => $price,
            ':subtotal'          => $subtotal,
            ':admin_fee_per_item'=> $PAJAK_PER_ITEM,
            ':admin_fee_total'   => $qty * $PAJAK_PER_ITEM,
        ]);

        // kurangi stok
        $stmtUpdateStock->execute([
            ':qty' => $qty,
            ':pid' => (int)$it['product_id'],
        ]);
    }

    // ubah status cart jadi ordered
    $stmtUpdateCart = $pdo->prepare("
        UPDATE carts
        SET status = 'ordered', updated_at = NOW()
        WHERE id = :id
    ");
    $stmtUpdateCart->execute([':id' => $cartId]);

    $pdo->commit();

    // redirect ke halaman sukses / rincian pesanan
    header('Location: '.$BASE.'/checkout_success.php?order_id='.$orderId);
    exit;

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    // untuk dev: tampilkan pesan error biar kelihatan
    echo "<h1>Terjadi kesalahan saat memproses checkout</h1>";
    echo "<p>".$e->getMessage()."</p>";
    echo '<p><a href="'.$BASE.'/checkout.php">Kembali ke Checkout</a></p>';
    exit;
}
