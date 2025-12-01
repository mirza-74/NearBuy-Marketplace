<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/settings.php';
if (function_exists('csrf_verify_or_die')) { csrf_verify_or_die(); }

$BASE = '/Marketplace_SellExa/public';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: '.$BASE.'/index.php');
  exit;
}

$user = $_SESSION['user'] ?? null;
if (!$user || !in_array($user['role'] ?? 'guest', ['pengguna','admin'], true)) {
  header('Location: '.$BASE.'/login.php?next='.urlencode($BASE.'/checkout.php'));
  exit;
}

$userId    = (int)$user['id'];
$productId = max(0, (int)($_POST['product_id'] ?? 0));
$qty       = max(1, (int)($_POST['qty'] ?? 1));

try {
  $pdo->beginTransaction();

  // ambil info produk (harga & stok)
  $stmt = $pdo->prepare("
    SELECT id, price, stock, is_active
    FROM products
    WHERE id = ?
    LIMIT 1
  ");
  $stmt->execute([$productId]);
  $p = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$p || (int)$p['is_active'] !== 1) {
    throw new Exception('Produk tidak aktif');
  }
  if ((int)$p['stock'] < $qty) {
    throw new Exception('Stok tidak mencukupi');
  }

  $unitPrice = (float)$p['price'];

  // cari cart aktif
  $stmt = $pdo->prepare("SELECT id FROM carts WHERE user_id=? AND status='active' ORDER BY id DESC LIMIT 1");
  $stmt->execute([$userId]);
  $cartId = (int)($stmt->fetchColumn() ?: 0);

  if ($cartId === 0) {
    $stmt = $pdo->prepare("INSERT INTO carts (user_id, status) VALUES (?, 'active')");
    $stmt->execute([$userId]);
    $cartId = (int)$pdo->lastInsertId();
  }

  // cek apakah item sudah ada di cart
  $find = $pdo->prepare("
    SELECT id, qty FROM cart_items
    WHERE cart_id = ? AND product_id = ? AND variant_id IS NULL
    LIMIT 1
  ");
  $find->execute([$cartId, $productId]);
  $existing = $find->fetch(PDO::FETCH_ASSOC);

  if ($existing) {
    $newQty = (int)$existing['qty'] + $qty;
    $upd = $pdo->prepare("UPDATE cart_items SET qty=?, price=? WHERE id=?");
    $upd->execute([$newQty, $unitPrice, (int)$existing['id']]);
  } else {
    $ins = $pdo->prepare("
      INSERT INTO cart_items (cart_id, product_id, variant_id, qty, price)
      VALUES (?, ?, NULL, ?, ?)
    ");
    $ins->execute([$cartId, $productId, $qty, $unitPrice]);
  }

  $pdo->commit();
  // langsung ke halaman checkout
  header('Location: '.$BASE.'/checkout.php');
} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  header('Location: '.$BASE.'/index.php?error='.urlencode($e->getMessage()));
}
