<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/settings.php';
if (function_exists('csrf_verify_or_die')) { csrf_verify_or_die(); }

$BASE = '/Marketplace_SellExa/public';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: '.$BASE.'/index.php?error='.urlencode('Metode tidak valid')); exit;
}

$user = $_SESSION['user'] ?? null;
if (!$user || !in_array($user['role'] ?? 'guest', ['pengguna','admin'], true)) {
  header('Location: '.$BASE.'/login.php?next='.urlencode($BASE.'/index.php')); exit;
}

$userId     = (int)$user['id'];
$productId  = max(0, (int)($_POST['product_id'] ?? 0));
$variantId  = isset($_POST['variant_id']) && $_POST['variant_id'] !== '' ? (int)$_POST['variant_id'] : null;
$qty        = max(1, (int)($_POST['qty'] ?? 1));

try {
  $pdo->beginTransaction();

  // 1) Validasi produk/varian + ambil harga unit
  if ($variantId !== null) {
    $stmt = $pdo->prepare("
      SELECT p.id AS product_id,
             COALESCE(v.price, p.price) AS price,
             v.stock AS stock,
             p.is_active AS p_active,
             v.is_active AS v_active
      FROM products p
      JOIN product_variants v ON v.product_id = p.id
      WHERE p.id = :pid AND v.id = :vid
      LIMIT 1
    ");
    $stmt->execute([':pid' => $productId, ':vid' => $variantId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row || (int)$row['p_active'] !== 1 || (int)$row['v_active'] !== 1) {
      throw new Exception('Produk/varian tidak aktif');
    }
    if ((int)$row['stock'] <= 0) throw new Exception('Stok varian habis');
    $unitPrice = (float)$row['price'];
  } else {
    $stmt = $pdo->prepare("
      SELECT id AS product_id, price, stock, is_active
      FROM products
      WHERE id = :pid
      LIMIT 1
    ");
    $stmt->execute([':pid' => $productId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row || (int)$row['is_active'] !== 1) throw new Exception('Produk tidak aktif');
    if ((int)$row['stock'] <= 0) throw new Exception('Stok produk habis');
    $unitPrice = (float)$row['price'];
  }

  // 2) Ambil/buat cart aktif (tanpa trigger DB)
  $stmt = $pdo->prepare("SELECT id FROM carts WHERE user_id=:uid AND status='active' ORDER BY id DESC LIMIT 1");
  $stmt->execute([':uid' => $userId]);
  $cartId = (int)($stmt->fetchColumn() ?: 0);

  if ($cartId === 0) {
    // abandon cart active lain (logika bekas trigger)
    $pdo->prepare("UPDATE carts SET status='abandoned' WHERE user_id=:uid AND status='active'")
        ->execute([':uid' => $userId]);

    $pdo->prepare("INSERT INTO carts (user_id, status) VALUES (:uid, 'active')")
        ->execute([':uid' => $userId]);

    $cartId = (int)$pdo->lastInsertId();
  }

  // 3) Upsert cart_items (PAKAI NAMED PLACEHOLDERS SEMUA)
  $sqlFind = "
    SELECT id, qty 
    FROM cart_items
    WHERE cart_id = :cart_id 
      AND product_id = :product_id
      AND (
            (:vid IS NULL AND variant_id IS NULL)
         OR (variant_id = :vid)
          )
    LIMIT 1
  ";
  $stmt = $pdo->prepare($sqlFind);
  // bind nilai untuk kedua penggunaan :vid
  if ($variantId === null) {
    $stmt->bindValue(':vid', null, PDO::PARAM_NULL);
  } else {
    $stmt->bindValue(':vid', $variantId, PDO::PARAM_INT);
  }
  $stmt->bindValue(':cart_id', $cartId, PDO::PARAM_INT);
  $stmt->bindValue(':product_id', $productId, PDO::PARAM_INT);
  $stmt->execute();
  $ci = $stmt->fetch(PDO::FETCH_ASSOC);

  if ($ci) {
    $newQty = (int)$ci['qty'] + $qty;
    $upd = $pdo->prepare("UPDATE cart_items SET qty = :qty, price = :price WHERE id = :id");
    $upd->execute([':qty' => $newQty, ':price' => $unitPrice, ':id' => (int)$ci['id']]);
  } else {
    $ins = $pdo->prepare("
      INSERT INTO cart_items (cart_id, product_id, variant_id, qty, price)
      VALUES (:cart_id, :product_id, :variant_id, :qty, :price)
    ");
    $ins->execute([
      ':cart_id'   => $cartId,
      ':product_id'=> $productId,
      ':variant_id'=> $variantId, // PDO akan kirim NULL kalau null
      ':qty'       => $qty,
      ':price'     => $unitPrice
    ]);
  }

  // 4) (opsional) naikkan popularitas produk
  $pdo->prepare("UPDATE products SET popularity = popularity + 1 WHERE id = :pid")
      ->execute([':pid' => $productId]);

  // 5) Update preferensi user berdasar kategori produk yg dibeli/ditambah
  //    -> supaya view v_user_recommendations langsung relevan
  //    (INSERT IGNORE manual untuk MariaDB)
  $stmt = $pdo->prepare("
    SELECT DISTINCT pc.category_id
    FROM product_categories pc
    WHERE pc.product_id = :pid
  ");
  $stmt->execute([':pid' => $productId]);
  $catIds = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);

  if (!empty($catIds)) {
    $insPref = $pdo->prepare("
      INSERT INTO user_preferences (user_id, category_id)
      VALUES (:uid, :cid)
      ON DUPLICATE KEY UPDATE user_id = user_preferences.user_id
    ");
    foreach ($catIds as $cid) {
      $insPref->execute([':uid' => $userId, ':cid' => (int)$cid]);
    }
  }

    $pdo->commit();
  // balik ke home, biar header langsung update counter keranjang
  header('Location: '.$BASE.'/index.php?msg='.urlencode('Ditambahkan ke keranjang'));
} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  header('Location: '.$BASE.'/index.php?error='.urlencode($e->getMessage()));
}
