<?php
// ===============================================================
// SellExa – Halaman Checkout
// ===============================================================
declare(strict_types=1);

$BASE = '/Marketplace_SellExa/public';

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/settings.php';

// (boleh dimatikan nanti kalau sudah stabil)
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

// ====== CSS tambahan untuk checkout ======
$EXTRA_CSS = ['style-checkout.css'];

// ====== Helper upload gambar ======
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

// header (navbar dll)
require_once __DIR__ . '/../includes/header.php';

// ===============================================================
// 1. Data user (alamat)
// ===============================================================
$fullName   = (string)($user['full_name'] ?? '');
$phone      = (string)($user['phone'] ?? '');
$address    = (string)($user['address'] ?? '');
$city       = (string)($user['city'] ?? '');
$province   = (string)($user['province'] ?? '');
$postalCode = (string)($user['postal_code'] ?? '');

// ===============================================================
// 2. Ambil cart aktif & item yang dipilih
// ===============================================================

// cari cart aktif
$stmtCart = $pdo->prepare("
    SELECT id 
    FROM carts 
    WHERE user_id = ? AND status = 'active'
    ORDER BY id DESC
    LIMIT 1
");
$stmtCart->execute([$userId]);
$cartId = (int)($stmtCart->fetchColumn() ?: 0);

$items       = [];
$totalBarang = 0.0;
$totalQty    = 0;

if ($cartId > 0) {

    // kalau ada ?items=1,3,5 → hanya item itu
    $selectedIds = [];
    $itemsParam  = $_GET['items'] ?? '';
    if ($itemsParam !== '') {
        foreach (explode(',', $itemsParam) as $raw) {
            $id = (int)trim($raw);
            if ($id > 0) $selectedIds[] = $id;
        }
    }

    if (!empty($selectedIds)) {
        $placeholders = implode(',', array_fill(0, count($selectedIds), '?'));
        $sql = "
            SELECT 
              ci.id   AS cart_item_id,
              ci.product_id,
              ci.qty,
              ci.price,
              p.title,
              p.slug,
              p.main_image,
              p.compare_price
            FROM cart_items ci
            JOIN carts c  ON c.id = ci.cart_id
            JOIN products p ON p.id = ci.product_id
            WHERE c.user_id = ?
              AND c.status  = 'active'
              AND ci.id IN ($placeholders)
            ORDER BY ci.created_at ASC, ci.id ASC
        ";
        $params = array_merge([$userId], $selectedIds);
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
    } else {
        // semua item cart aktif
        $stmt = $pdo->prepare("
            SELECT 
              ci.id   AS cart_item_id,
              ci.product_id,
              ci.qty,
              ci.price,
              p.title,
              p.slug,
              p.main_image,
              p.compare_price
            FROM cart_items ci
            JOIN carts c  ON c.id = ci.cart_id
            JOIN products p ON p.id = ci.product_id
            WHERE c.user_id = ?
              AND c.status  = 'active'
            ORDER BY ci.created_at ASC, ci.id ASC
        ");
        $stmt->execute([$userId]);
    }

    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($items as &$it) {
        $qty   = (int)$it['qty'];
        $price = (float)$it['price'];
        $sub   = $qty * $price;
        $it['subtotal'] = $sub;

        $totalBarang += $sub;
        $totalQty    += $qty;
    }
    unset($it);
}

// ===============================================================
// 3. Hitung ongkir, pajak, total
// ===============================================================
$ONGKIR_PER_ITEM = 15000;
$PAJAK_PER_ITEM  = 1500;

$ongkirTotal    = $totalQty * $ONGKIR_PER_ITEM;
$pajakTotal     = $totalQty * $PAJAK_PER_ITEM;
$voucherDiskon  = 0.0;
$totalTagihan   = $totalBarang + $ongkirTotal + $pajakTotal - $voucherDiskon;
?>
<div class="checkout-page">

  <h1 class="co-title">Checkout</h1>

  <?php if (empty($items)): ?>
    <div class="co-empty">
      <p><strong>Tidak ada barang yang dipilih untuk checkout.</strong></p>
      <p>Silakan kembali ke keranjang dan pilih produk yang ingin kamu bayar.</p>
      <a href="<?= e($BASE) ?>/keranjang.php" class="btn-back-cart">Kembali ke Keranjang</a>
    </div>
  <?php else: ?>

  <form method="post" action="<?= e($BASE) ?>/proses_checkout.php" class="co-form">
    <?php if (function_exists('csrf_input')) { csrf_input(); } ?>

    <!-- hidden summary -->
    <input type="hidden" name="cart_id" value="<?= (int)$cartId ?>">
    <?php foreach ($items as $it): ?>
      <input type="hidden" name="cart_item_ids[]" value="<?= (int)$it['cart_item_id'] ?>">
    <?php endforeach; ?>
    <input type="hidden" name="total_barang" value="<?= htmlspecialchars((string)$totalBarang, ENT_QUOTES, 'UTF-8') ?>">
    <input type="hidden" name="total_ongkir" value="<?= htmlspecialchars((string)$ongkirTotal, ENT_QUOTES, 'UTF-8') ?>">
    <input type="hidden" name="total_pajak_admin" value="<?= htmlspecialchars((string)$pajakTotal, ENT_QUOTES, 'UTF-8') ?>">
    <input type="hidden" name="total_diskon" value="<?= htmlspecialchars((string)$voucherDiskon, ENT_QUOTES, 'UTF-8') ?>">
    <input type="hidden" name="grand_total" value="<?= htmlspecialchars((string)$totalTagihan, ENT_QUOTES, 'UTF-8') ?>">

    <div class="co-layout">

      <!-- Kolom kiri -->
      <div class="co-col-left">

        <!-- Alamat -->
        <section class="co-card co-address">
          <div class="co-address-header">
            <span class="co-address-label">Alamat pengiriman kamu</span>
            <a href="<?= e($BASE) ?>/profil.php" class="co-address-edit">Ubah</a>
          </div>
          <div class="co-address-body">
            <div class="co-address-name">
              <?= e($fullName !== '' ? $fullName : 'Pengguna SellExa') ?>
              <?php if ($phone): ?>
                <span class="co-address-phone">• <?= e($phone) ?></span>
              <?php endif; ?>
            </div>
            <div class="co-address-detail">
              <?= e(trim("$address, $city, $province $postalCode", ", ")) ?>
            </div>
          </div>
        </section>

        <!-- List Barang -->
        <section class="co-card co-items">
          <h2 class="co-section-title">Pesanan kamu</h2>

          <?php foreach ($items as $it):
            $img = upload_image_url($it['main_image'] ?? null, $BASE);
            $hasPromo   = (!is_null($it['compare_price']) && (float)$it['compare_price'] > (float)$it['price']);
            $detailUrl  = $BASE.'/detail_produk.php?slug='.urlencode($it['slug']);
          ?>
            <div class="co-item-row">
              <div class="co-item-left">
                <img src="<?= e($img) ?>" alt="<?= e($it['title']) ?>" class="co-item-img" loading="lazy">
              </div>
              <div class="co-item-body">
                <a href="<?= e($detailUrl) ?>" class="co-item-title">
                  <?= e($it['title']) ?>
                </a>
                <div class="co-item-meta">
                  <span class="co-item-qty">Qty: <?= (int)$it['qty'] ?></span>
                </div>
                <div class="co-item-price">
                  <?php if ($hasPromo): ?>
                    <span class="co-item-price-current">
                      Rp<?= number_format((float)$it['price'], 0, ',', '.') ?>
                    </span>
                    <span class="co-item-price-compare">
                      Rp<?= number_format((float)$it['compare_price'], 0, ',', '.') ?>
                    </span>
                  <?php else: ?>
                    <span class="co-item-price-current">
                      Rp<?= number_format((float)$it['price'], 0, ',', '.') ?>
                    </span>
                  <?php endif; ?>
                </div>
                <div class="co-item-subtotal">
                  Subtotal: <b>Rp<?= number_format((float)$it['subtotal'], 0, ',', '.') ?></b>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </section>

        <!-- Pengiriman & Catatan -->
        <section class="co-card co-shipping">
          <h2 class="co-section-title">Pengiriman</h2>
          <div class="co-shipping-row">
            <div class="co-ship-name">
              Ekonomi (Rp<?= number_format($ONGKIR_PER_ITEM,0,',','.') ?> / barang)
            </div>
            <div class="co-ship-estimasi">
              Estimasi tiba 3–5 hari kerja
            </div>
            <div class="co-ship-total">
              Ongkir: <b>Rp<?= number_format($ongkirTotal, 0, ',', '.') ?></b>
            </div>
          </div>

          <div class="co-note">
            <label for="note" class="co-note-label">Kasih catatan</label>
            <textarea id="note" name="note" rows="2" class="co-note-text"
              placeholder="Contoh: tolong dibungkus bubble wrap, warna random tidak apa-apa"></textarea>
          </div>
        </section>

        <!-- Metode Pembayaran -->
        <section class="co-card co-payment">
          <h2 class="co-section-title">Metode pembayaran</h2>
          <div class="co-pay-option">
            <label>
              <input type="radio" name="payment_method" value="bca_va" checked>
              <span>BCA Virtual Account</span>
            </label>
          </div>
          <div class="co-pay-option">
            <label>
              <input type="radio" name="payment_method" value="alfamart">
              <span>Alfamart / Alfamidi / Lawson / Dan+Dan</span>
            </label>
          </div>
          <div class="co-pay-option">
            <label>
              <input type="radio" name="payment_method" value="mandiri_va">
              <span>Mandiri Virtual Account</span>
            </label>
          </div>
          <div class="co-pay-option">
            <label>
              <input type="radio" name="payment_method" value="bri_va">
              <span>BRI Virtual Account</span>
            </label>
          </div>
        </section>

      </div>

      <!-- Kolom kanan (ringkasan) -->
      <div class="co-col-right">
        <section class="co-card co-summary">
          <h2 class="co-section-title">Cek ringkasan belanjamu, yuk</h2>

          <div class="co-summary-row">
            <span>Total Harga (<?= $totalQty ?> barang)</span>
            <span>Rp<?= number_format($totalBarang, 0, ',', '.') ?></span>
          </div>
          <div class="co-summary-row">
            <span>Total Ongkos Kirim</span>
            <span>Rp<?= number_format($ongkirTotal, 0, ',', '.') ?></span>
          </div>
          <div class="co-summary-row">
            <span>Pajak / Biaya Admin</span>
            <span>Rp<?= number_format($pajakTotal, 0, ',', '.') ?></span>
          </div>
          <div class="co-summary-row">
            <span>Diskon Voucher</span>
            <span>- Rp<?= number_format($voucherDiskon, 0, ',', '.') ?></span>
          </div>

          <div class="co-summary-total-row">
            <span>Total Tagihan</span>
            <span class="co-summary-total">Rp<?= number_format($totalTagihan, 0, ',', '.') ?></span>
          </div>

          <button type="submit" class="co-btn-pay">
            Bayar Sekarang
          </button>
        </section>
      </div>

    </div> <!-- .co-layout -->

  </form>
  <?php endif; ?>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
</main>
</body>
</html>
