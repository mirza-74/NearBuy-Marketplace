<?php
// ===============================================================
// nearbuy– Halaman Keranjang
// Mirip Tokped: qty +/-, hapus, total, rekomendasi
// ===============================================================
declare(strict_types=1);

$BASE = '/NearBuy-Marketplace/public';

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/settings.php';

// pastikan user login
$user = $_SESSION['user'] ?? null;
$role = $user['role'] ?? 'guest';
if (!$user || !in_array($role, ['pengguna','admin'], true)) {
    header('Location: '.$BASE.'/login.php?next='.urlencode($BASE.'/keranjang.php'));
    exit;
}
$userId = (int)$user['id'];

// untuk load CSS tambahan
$EXTRA_CSS = ['style-keranjang.css'];

require_once __DIR__ . '/../includes/header.php';

// -------------------- Helper kecil --------------------
if (!function_exists('upload_image_url')) {
    function upload_image_url(?string $path, string $BASE): string {
        if (!$path) return 'https://via.placeholder.com/200x200?text=No+Image';
        if (preg_match('~^https?://~i', $path)) return $path;
        $p = ltrim($path, '/');
        if (str_starts_with($p, 'products/')) {
            return rtrim($BASE, '/').'/uploads/'.$p;
        }
        if (str_starts_with($p, 'uploads/')) {
            return rtrim($BASE, '/').'/'.$p;
        }
        if (str_starts_with($p, 'public/uploads/')) {
            return rtrim($BASE, '/').'/'.substr($p, 7);
        }
        return rtrim($BASE, '/').'/uploads/'.$p;
    }
}

// -------------------- Handle aksi (POST) --------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (function_exists('csrf_verify_or_die')) { csrf_verify_or_die(); }

    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'update_qty') {
            $cartItemId = max(0, (int)($_POST['cart_item_id'] ?? 0));
            $newQty     = max(1, (int)($_POST['qty'] ?? 1));

            // cek item milik user + stok
            $stmt = $pdo->prepare("
                SELECT ci.id, ci.qty, p.stock
                FROM cart_items ci
                JOIN carts c  ON ci.cart_id = c.id
                JOIN products p ON p.id = ci.product_id
                WHERE ci.id = :ciid
                  AND c.user_id = :uid
                  AND c.status  = 'active'
                LIMIT 1
            ");
            $stmt->execute([
                ':ciid' => $cartItemId,
                ':uid'  => $userId,
            ]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $stok = (int)$row['stock'];
                if ($stok > 0 && $newQty > $stok) {
                    $newQty = $stok; // batasi ke stok
                }
                $upd = $pdo->prepare("UPDATE cart_items SET qty = :qty WHERE id = :id");
                $upd->execute([':qty' => $newQty, ':id' => $cartItemId]);
            }
        } elseif ($action === 'remove_item') {
            $cartItemId = max(0, (int)($_POST['cart_item_id'] ?? 0));
            // hapus item milik cart user
            $del = $pdo->prepare("
                DELETE ci FROM cart_items ci
                JOIN carts c ON ci.cart_id = c.id
                WHERE ci.id = :ciid AND c.user_id = :uid AND c.status='active'
            ");
            $del->execute([':ciid' => $cartItemId, ':uid' => $userId]);
        }
    } catch (Throwable $e) {
        // boleh kamu log error di sini kalau mau
    }

    header('Location: '.$BASE.'/keranjang.php');
    exit;
}

// -------------------- Ambil cart aktif + item --------------------
$stmt = $pdo->prepare("
    SELECT c.id AS cart_id
    FROM carts c
    WHERE c.user_id = :uid AND c.status = 'active'
    ORDER BY c.id DESC
    LIMIT 1
");
$stmt->execute([':uid' => $userId]);
$cartId = (int)($stmt->fetchColumn() ?: 0);

$items = [];
$totalSubtotal = 0;
$totalItems    = 0;

if ($cartId > 0) {
    $stmt = $pdo->prepare("
        SELECT 
          ci.id           AS cart_item_id,
          ci.product_id,
          ci.qty,
          ci.price,
          p.title,
          p.slug,
          p.main_image,
          p.compare_price,
          p.stock
        FROM cart_items ci
        JOIN products p ON p.id = ci.product_id
        WHERE ci.cart_id = :cid
        ORDER BY ci.created_at ASC, ci.id ASC
    ");
    $stmt->execute([':cid' => $cartId]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($items as &$it) {
        $qty = (int)$it['qty'];
        $price = (float)$it['price'];
        $sub = $qty * $price;
        $it['subtotal'] = $sub;
        $totalSubtotal += $sub;
        $totalItems    += $qty;
    }
    unset($it);
}

// -------------------- Rekomendasi Produk --------------------
$rekom = [];
if ($userId > 0) {
    $stmt = $pdo->prepare("
        SELECT DISTINCT 
          r.product_id AS id,
          r.slug,
          r.title,
          r.price,
          r.compare_price,
          r.main_image
        FROM v_user_recommendations r
        WHERE r.user_id = :uid
          AND r.stock > 0
          AND r.product_id NOT IN (
            SELECT ci.product_id
            FROM carts c 
            JOIN cart_items ci ON ci.cart_id = c.id
            WHERE c.user_id = :uid AND c.status='active'
          )
        ORDER BY r.popularity DESC, r.created_at DESC
        LIMIT 8
    ");
    $stmt->execute([':uid' => $userId]);
    $rekom = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // fallback kalau kosong
    if (empty($rekom)) {
        $stmt = $pdo->query("
            SELECT id, slug, title, price, compare_price, main_image
            FROM products
            WHERE is_active=1 AND stock>0
            ORDER BY popularity DESC, created_at DESC
            LIMIT 8
        ");
        $rekom = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>

<div class="cart-page">

  <h1 class="cart-title">Keranjang</h1>

  <?php if (empty($items)): ?>
    <div class="cart-empty">
      <p>Keranjangmu masih kosong.</p>
      <a href="<?= e($BASE) ?>/index.php" class="btn-back-shop">Mulai belanja</a>
    </div>
  <?php else: ?>

    <!-- LIST ITEM KERANJANG -->
    <section class="cart-section">
      <?php foreach ($items as $it): 
        $img = upload_image_url($it['main_image'] ?? null, $BASE);
        $hasPromo = (!is_null($it['compare_price']) && (float)$it['compare_price'] > (float)$it['price']);
        $detailUrl = $BASE.'/detail_produk.php?slug='.urlencode($it['slug']);
      ?>
      <div class="cart-item">
        <!-- checkbox + gambar -->
        <div class="cart-item-left">
          <label class="check-wrap">
            <input type="checkbox" class="item-check" checked data-subtotal="<?= (float)$it['subtotal'] ?>">
            <span class="check-custom"></span>
          </label>

          <a href="<?= e($detailUrl) ?>" class="cart-img-link">
            <img src="<?= e($img) ?>" alt="<?= e($it['title']) ?>" class="cart-img" loading="lazy">
          </a>
        </div>

        <!-- detail -->
        <div class="cart-item-body">
          <a href="<?= e($detailUrl) ?>" class="cart-item-title">
            <?= e($it['title']) ?>
          </a>

          <div class="cart-price">
            <?php if ($hasPromo): ?>
              <span class="price-current">Rp<?= number_format((float)$it['price'], 0, ',', '.') ?></span>
              <span class="price-compare">Rp<?= number_format((float)$it['compare_price'], 0, ',', '.') ?></span>
            <?php else: ?>
              <span class="price-current">Rp<?= number_format((float)$it['price'], 0, ',', '.') ?></span>
            <?php endif; ?>
          </div>

          <div class="cart-item-bottom">
            <!-- qty control -->
            <form method="post" class="qty-form">
              <?php if (function_exists('csrf_input')) { csrf_input(); } ?>
              <input type="hidden" name="action" value="update_qty">
              <input type="hidden" name="cart_item_id" value="<?= (int)$it['cart_item_id'] ?>">

              <button type="submit" name="qty" value="<?= max(1, (int)$it['qty'] - 1) ?>" class="qty-btn" aria-label="Kurangi jumlah">−</button>
              <span class="qty-value"><?= (int)$it['qty'] ?></span>
              <button type="submit" name="qty" value="<?= (int)$it['qty'] + 1 ?>" class="qty-btn" aria-label="Tambah jumlah">+</button>
            </form>

            <!-- hapus -->
            <form method="post" class="remove-form">
              <?php if (function_exists('csrf_input')) { csrf_input(); } ?>
              <input type="hidden" name="action" value="remove_item">
              <input type="hidden" name="cart_item_id" value="<?= (int)$it['cart_item_id'] ?>">
              <button type="submit" class="btn-remove">Hapus</button>
            </form>
          </div>

        </div>
      </div>
      <?php endforeach; ?>
    </section>

    <!-- Rekomendasi Produk -->
    <?php if (!empty($rekom)): ?>
    <section class="cart-recom-section">
      <h2>Rekomendasi untukmu</h2>
      <div class="recom-grid">
        <?php foreach ($rekom as $p):
          $img = upload_image_url($p['main_image'] ?? null, $BASE);
          $hasPromo = (!is_null($p['compare_price']) && (float)$p['compare_price'] > (float)$p['price']);
          $detailUrl = $BASE.'/detail_produk.php?slug='.urlencode($p['slug']);
        ?>
        <div class="recom-card">
          <a href="<?= e($detailUrl) ?>" class="recom-link">
            <img src="<?= e($img) ?>" alt="<?= e($p['title']) ?>" class="recom-img" loading="lazy">
            <h3 class="recom-title"><?= e($p['title']) ?></h3>
            <div class="recom-price">
              <?php if ($hasPromo): ?>
                <span class="recom-price-current">Rp<?= number_format((float)$p['price'],0,',','.') ?></span>
                <span class="recom-price-compare">Rp<?= number_format((float)$p['compare_price'],0,',','.') ?></span>
              <?php else: ?>
                <span class="recom-price-current">Rp<?= number_format((float)$p['price'],0,',','.') ?></span>
              <?php endif; ?>
            </div>
          </a>
          <form method="post" action="add_to_cart.php" class="recom-add-form">
            <?php if (function_exists('csrf_input')) { csrf_input(); } ?>
            <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
            <input type="hidden" name="qty" value="1">
            <button type="submit" class="recom-btn">+ Keranjang</button>
          </form>
        </div>
        <?php endforeach; ?>
      </div>
    </section>
    <?php endif; ?>

    <!-- Summary bawah (mirip Tokped) -->
    <section class="cart-summary-bar">
      <div class="summary-left">
        <label class="check-wrap">
          <input type="checkbox" class="check-all" checked>
          <span class="check-custom"></span>
        </label>
        <span class="summary-text">Semua</span>
      </div>
      <div class="summary-mid">
        <div class="summary-label">Total</div>
        <div class="summary-total" id="summary-total">
          Rp<?= number_format($totalSubtotal, 0, ',', '.') ?>
        </div>
      </div>
      <div class="summary-right">
        <button type="button" class="btn-buy-main" id="btn-buy-main">
          Beli (<?= count($items) ?>)
        </button>
      </div>
    </section>

  <?php endif; ?>

</div>

<script>
// =======================
// Hitung total client-side + aksi Beli
// =======================
(function(){
  const BASE_URL  = <?= json_encode($BASE) ?>;
  const checkAll  = document.querySelector('.check-all');
  const checks    = document.querySelectorAll('.item-check');
  const totalEl   = document.getElementById('summary-total');
  const buyBtn    = document.getElementById('btn-buy-main');

  if (!checks.length) return;

  function recalc(){
    let total = 0;
    let count = 0;
    checks.forEach(ch => {
      if (ch.checked) {
        total += parseFloat(ch.dataset.subtotal || '0');
        count++;
      }
    });
    totalEl.textContent = 'Rp' + total.toLocaleString('id-ID');
    if (buyBtn) {
      buyBtn.textContent = 'Beli (' + count + ')';
    }
  }

  checks.forEach(ch => {
    ch.addEventListener('change', recalc);
  });

  if (checkAll) {
    checkAll.addEventListener('change', function(){
      checks.forEach(ch => ch.checked = checkAll.checked);
      recalc();
    });
  }

  // === Aksi tombol Beli → redirect ke checkout ===
  if (buyBtn) {
    buyBtn.addEventListener('click', function () {
      const hasSelected = Array.from(checks).some(ch => ch.checked);
      if (!hasSelected) {
        alert('Pilih minimal satu produk terlebih dahulu.');
        return;
      }
      // checkout akan baca cart aktif user
      window.location.href = BASE_URL + '/checkout.php';
    });
  }

  recalc();
})();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
</main>
</body>
</html>
