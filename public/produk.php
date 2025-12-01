<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/header.php';

$BASE = '/Marketplace_SellExa/public';
$user = $_SESSION['user'] ?? null;
$role = $user['role'] ?? 'guest';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
  echo "<div class='container'><p>Produk tidak ditemukan.</p></div>";
  require_once __DIR__ . '/../includes/footer.php';
  exit;
}

/* ---------------- Ambil data produk ---------------- */
$stmt = $pdo->prepare("
  SELECT id, title, description, price, compare_price, stock, main_image, created_at
  FROM products
  WHERE id = ? AND is_active = 1
  LIMIT 1
");
$stmt->execute([$id]);
$produk = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$produk) {
  echo "<div class='container'><p>Produk tidak ditemukan atau nonaktif.</p></div>";
  require_once __DIR__ . '/../includes/footer.php';
  exit;
}

/* ---------------- Hitung diskon ---------------- */
$discount = 0;
if (!empty($produk['compare_price']) && $produk['compare_price'] > $produk['price']) {
  $discount = (int)round((1 - ($produk['price'] / $produk['compare_price'])) * 100);
}

/* ---------------- Ambil ulasan ---------------- */
$reviews = $pdo->prepare("
  SELECT r.id, r.user_id, r.rating, r.comment, r.created_at, u.username
  FROM reviews r
  JOIN users u ON r.user_id = u.id
  WHERE r.product_id = ?
  ORDER BY r.created_at DESC
");
$reviews->execute([$id]);
$ulasan = $reviews->fetchAll(PDO::FETCH_ASSOC);

/* ---------------- Cek apakah user boleh review ---------------- */
$boleh_review = false;
if ($role === 'pengguna' && isset($user['id'])) {
  $cek = $pdo->prepare("
    SELECT COUNT(*) FROM orders o
    JOIN order_items i ON i.order_id = o.id
    WHERE o.user_id = ? AND i.product_id = ? AND o.status IN ('selesai','completed')
  ");
  $cek->execute([$user['id'], $id]);
  $boleh_review = $cek->fetchColumn() > 0;
}

/* ---------------- Tambah ulasan (POST) ---------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment']) && $boleh_review) {
  $rating = max(1, min(5, (int)$_POST['rating']));
  $comment = trim($_POST['comment']);
  if ($comment !== '') {
    $stmt = $pdo->prepare("INSERT INTO reviews (product_id, user_id, rating, comment, created_at) VALUES (?,?,?,?,NOW())");
    $stmt->execute([$id, $user['id'], $rating, $comment]);
    header("Location: ".$_SERVER['REQUEST_URI']);
    exit;
  }
}

/* ---------------- Helper ---------------- */
function rupiah($n){ return 'Rp'.number_format((float)$n,0,',','.'); }
?>

<style>
.product-detail{max-width:1100px;margin:20px auto;padding:0 16px;display:grid;gap:20px;grid-template-columns:1fr}
@media(min-width:768px){.product-detail{grid-template-columns:400px 1fr}}
.product-image img{width:100%;border-radius:14px;object-fit:cover;box-shadow:0 4px 12px rgba(0,0,0,.1)}
.product-info h1{margin:0 0 10px;font-size:1.8rem;font-weight:700}
.product-price{font-size:1.4rem;font-weight:800;color:#dc2626}
.product-compare{text-decoration:line-through;color:#6b7280;margin-left:8px;font-size:1rem}
.product-discount{background:#fef2f2;color:#b91c1c;padding:4px 10px;border-radius:8px;font-weight:700;margin-left:8px}
.product-stock{color:#15803d;margin-top:4px;font-weight:600}
.product-desc{margin-top:16px;color:#374151;line-height:1.6}
.btns{margin-top:20px;display:flex;flex-wrap:wrap;gap:10px}
.btn{display:inline-flex;align-items:center;justify-content:center;padding:10px 14px;border-radius:10px;font-weight:700;cursor:pointer;text-decoration:none}
.btn-add{background:linear-gradient(90deg,#3b82f6,#8b5cf6);color:#fff;border:0}
.btn-disabled{background:#e5e7eb;color:#9ca3af;cursor:not-allowed}
.btn-view{background:#f3f4f6}
.review-section{margin-top:40px}
.review{border-top:1px solid #e5e7eb;padding:12px 0}
.review strong{color:#111827}
.review small{color:#6b7280;font-size:.9rem}
.add-review textarea{width:100%;padding:10px;border:1px solid #d1d5db;border-radius:10px;min-height:80px}
.add-review select,.add-review button{margin-top:8px;padding:8px;border-radius:8px;border:1px solid #d1d5db}
.add-review button{background:#0f172a;color:#fff;font-weight:700;cursor:pointer}
</style>

<section class="product-detail">
  <div class="product-image">
    <img src="<?= htmlspecialchars($produk['main_image'] ?: 'https://via.placeholder.com/640x480?text=SellExa') ?>" alt="<?= htmlspecialchars($produk['title']) ?>">
  </div>

  <div class="product-info">
    <h1><?= htmlspecialchars($produk['title']) ?></h1>
    <div>
      <span class="product-price"><?= rupiah($produk['price']) ?></span>
      <?php if($produk['compare_price']): ?>
        <span class="product-compare"><?= rupiah($produk['compare_price']) ?></span>
      <?php endif; ?>
      <?php if($discount>0): ?>
        <span class="product-discount">-<?= $discount ?>%</span>
      <?php endif; ?>
    </div>

    <div class="product-stock">
      <?= (int)$produk['stock']>0 ? "Stok: ".$produk['stock'] : "Stok Habis" ?>
    </div>

    <div class="product-desc">
      <?= nl2br(htmlspecialchars($produk['description'] ?: 'Belum ada deskripsi.')) ?>
    </div>

    <div class="btns">
      <?php
        $addUrl  = "{$BASE}/pengguna/add.php?pid={$produk['id']}";
        $wishUrl = "{$BASE}/pengguna/wishlist_add.php?pid={$produk['id']}";
        $loginAdd = "{$BASE}/login.php?next=" . urlencode($addUrl);
        $loginWish = "{$BASE}/login.php?next=" . urlencode($wishUrl);
      ?>
      <?php if($role==='pengguna'): ?>
        <a class="btn btn-add" href="<?=$addUrl?>">Tambah ke Keranjang</a>
        <a class="btn btn-view" href="<?=$wishUrl?>">❤️ Wishlist</a>
      <?php else: ?>
        <a class="btn btn-disabled" href="<?=$loginAdd?>">Tambah ke Keranjang</a>
        <a class="btn btn-disabled" href="<?=$loginWish?>">❤️ Wishlist</a>
      <?php endif; ?>
    </div>
  </div>
</section>

<section class="container review-section">
  <h2>Ulasan Produk</h2>

  <?php if(empty($ulasan)): ?>
    <p>Belum ada ulasan untuk produk ini.</p>
  <?php else: ?>
    <?php foreach($ulasan as $u): ?>
      <div class="review">
        <strong><?= htmlspecialchars($u['username']) ?></strong>
        <small> — <?= date('d M Y', strtotime($u['created_at'])) ?> | ⭐<?= $u['rating'] ?>/5</small>
        <p><?= nl2br(htmlspecialchars($u['comment'])) ?></p>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>

  <?php if($boleh_review): ?>
    <div class="add-review">
      <h3>Tulis Ulasan Anda</h3>
      <form method="post">
        <select name="rating" required>
          <option value="">Pilih Rating</option>
          <option value="5">⭐ 5 - Sangat Baik</option>
          <option value="4">⭐ 4 - Baik</option>
          <option value="3">⭐ 3 - Cukup</option>
          <option value="2">⭐ 2 - Kurang</option>
          <option value="1">⭐ 1 - Buruk</option>
        </select>
        <textarea name="comment" maxlength="500" placeholder="Tulis pengalaman Anda..."></textarea>
        <button type="submit">Kirim Ulasan</button>
      </form>
    </div>
  <?php elseif($role==='pengguna'): ?>
    <p style="color:#6b7280">Anda hanya bisa memberikan ulasan setelah membeli produk ini.</p>
  <?php else: ?>
    <p><a href="<?=$BASE?>/login.php">Login</a> untuk menulis ulasan.</p>
  <?php endif; ?>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
