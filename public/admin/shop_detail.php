<?php
declare(strict_types=1);

$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$BASE = preg_replace('~/admin$~', '', rtrim($scriptDir, '/'));

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/db.php';

// cek admin
$user = $_SESSION['user'] ?? null;
if (!$user || ($user['role'] ?? '') !== 'admin') {
    header('Location: ' . $BASE . '/login.php');
    exit;
}

if (!function_exists('e')) {
    function e($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}

// ambil id toko
$shopId = (int)($_GET['id'] ?? 0);
if ($shopId <= 0) {
    header('Location: ' . $BASE . '/admin/index.php');
    exit;
}

// proses POST: toggle status produk
$flash = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action    = $_POST['action'] ?? '';
    $productId = (int)($_POST['product_id'] ?? 0);

    if ($productId > 0 && ($action === 'deactivate' || $action === 'activate')) {
        $newStatus = ($action === 'activate') ? 1 : 0;
        try {
            // pastikan produk milik toko ini
            $stmt = $pdo->prepare("UPDATE products SET is_active = ? WHERE id = ? AND shop_id = ? LIMIT 1");
            $stmt->execute([$newStatus, $productId, $shopId]);

            if ($stmt->rowCount() > 0) {
                $flash = ($newStatus === 1)
                    ? "Produk #{$productId} diaktifkan kembali."
                    : "Produk #{$productId} dinonaktifkan.";
            } else {
                $flash = "Produk tidak ditemukan atau bukan milik toko ini.";
            }
        } catch (Throwable $e) {
            $flash = "Gagal mengubah status produk: " . $e->getMessage();
        }
    }

    // redirect supaya tidak double submit
    $_SESSION['flash_admin'] = $flash;
    header('Location: ' . $BASE . '/admin/shop_detail.php?id=' . $shopId);
    exit;
}

// baca flash (kalau ada)
if (empty($flash) && !empty($_SESSION['flash_admin'])) {
    $flash = $_SESSION['flash_admin'];
    unset($_SESSION['flash_admin']);
}

// ambil data toko
$stmt = $pdo->prepare("
    SELECT 
      s.id,
      s.name,
      s.address,
      s.latitude,
      s.longitude,
      s.description,
      s.is_active,
      s.created_at,
      u.full_name,
      u.email,
      u.phone
    FROM shops s
    JOIN users u ON u.id = s.user_id
    WHERE s.id = ?
    LIMIT 1
");
$stmt->execute([$shopId]);
$shop = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$shop) {
    header('Location: ' . $BASE . '/admin/index.php');
    exit;
}

// ambil produk milik toko ini
$products = [];
try {
    $stmt = $pdo->prepare("
        SELECT id, title, price, compare_price, stock, is_active, main_image, created_at
        FROM products
        WHERE shop_id = ?
        ORDER BY created_at DESC
    ");
    $stmt->execute([$shopId]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $products = [];
}

require_once __DIR__ . '/../../includes/header.php';
?>
<div style="max-width:1000px;margin:22px auto;padding:0 16px;">
  <a href="<?= e($BASE) ?>/admin/index.php" style="font-size:13px;color:#2563eb;text-decoration:none;">← Kembali ke Dashboard Admin</a>

  <h1 style="margin-top:10px;margin-bottom:4px;">Profil Toko</h1>
  <p style="color:#6b7280;margin-top:0;margin-bottom:16px;">
    Lihat detail toko dan kelola status produk (nonaktifkan jika melanggar peraturan).
  </p>

  <?php if ($flash): ?>
    <div style="padding:10px;border-radius:8px;background:#f0fdf4;color:#065f46;margin-bottom:14px;">
      <?= e($flash) ?>
    </div>
  <?php endif; ?>

  <!-- Info Toko -->
  <div style="padding:12px;border-radius:10px;border:1px solid #e6e9ef;background:#fff;margin-bottom:18px;">
    <div style="display:flex;justify-content:space-between;gap:12px;">
      <div>
        <div style="font-weight:700;font-size:18px;"><?= e($shop['name']) ?> <span style="font-size:12px;color:#6b7280;">(ID: <?= (int)$shop['id'] ?>)</span></div>
        <div style="font-size:13px;color:#6b7280;margin-top:4px;">
          Pemilik: <?= e($shop['full_name'] ?? '-') ?> · <?= e($shop['email'] ?? '-') ?>
          <?php if (!empty($shop['phone'])): ?>
            · Telp: <?= e($shop['phone']) ?>
          <?php endif; ?>
        </div>
        <div style="margin-top:8px;color:#374151;font-size:14px;">
          <?= nl2br(e($shop['address'] ?: 'Alamat belum diisi')) ?>
        </div>
        <div style="margin-top:6px;color:#6b7280;font-size:13px;">
          Koordinat: <?= e($shop['latitude'] ?? '-') ?>, <?= e($shop['longitude'] ?? '-') ?>
        </div>
      </div>
      <div style="text-align:right;">
        <div style="font-size:13px;color:#6b7280;">Status toko:</div>
        <div style="font-weight:700;font-size:14px;">
          <?php if ((int)$shop['is_active'] === 1): ?>
            <span style="color:#16a34a;">Aktif</span>
          <?php elseif ((int)$shop['is_active'] === 0): ?>
            <span style="color:#f97316;">Menunggu</span>
          <?php else: ?>
            <span style="color:#b91c1c;">Ditolak / Nonaktif</span>
          <?php endif; ?>
        </div>
        <div style="font-size:12px;color:#9ca3af;margin-top:4px;">
          Terdaftar: <?= e($shop['created_at'] ?? '') ?>
        </div>
      </div>
    </div>

    <?php if (!empty($shop['description'])): ?>
      <div style="margin-top:10px;font-size:14px;color:#4b5563;">
        <b>Deskripsi toko:</b><br>
        <?= nl2br(e($shop['description'])) ?>
      </div>
    <?php endif; ?>
  </div>

  <!-- Produk Toko -->
  <h2 style="margin-bottom:8px;">Produk Toko Ini</h2>
  <p style="color:#6b7280;font-size:13px;margin-top:0;margin-bottom:10px;">
    Kamu dapat menonaktifkan produk yang melanggar peraturan, atau mengaktifkan kembali jika sudah aman.
  </p>

  <?php if (empty($products)): ?>
    <div style="color:#64748b;">Toko ini belum memiliki produk.</div>
  <?php else: ?>
    <div style="display:grid;gap:10px;">
      <?php foreach ($products as $p): ?>
        <div style="padding:10px;border-radius:10px;border:1px solid #e6e9ef;background:#fff;display:flex;gap:10px;align-items:center;">
          <div style="width:80px;height:64px;flex:0 0 80px;overflow:hidden;border-radius:8px;background:#f8fafc;">
            <img src="<?= e($p['main_image'] ?: 'https://via.placeholder.com/160x120?text=No+Img') ?>"
                 alt=""
                 style="width:100%;height:100%;object-fit:cover;">
          </div>
          <div style="flex:1;">
            <div style="font-weight:600;"><?= e($p['title']) ?> <span style="font-size:11px;color:#9ca3af;">(#<?= (int)$p['id'] ?>)</span></div>
            <div style="font-size:13px;color:#6b7280;margin-top:2px;">
              Harga: Rp<?= number_format((float)$p['price'],0,',','.') ?>
              <?php if (!is_null($p['compare_price']) && (float)$p['compare_price'] > (float)$p['price']): ?>
                · <span style="text-decoration:line-through;">Rp<?= number_format((float)$p['compare_price'],0,',','.') ?></span>
              <?php endif; ?>
            </div>
            <div style="font-size:12px;color:#6b7280;margin-top:2px;">
              Stok: <?= (int)$p['stock'] ?> · Status:
              <?php if ((int)$p['is_active'] === 1): ?>
                <span style="color:#16a34a;">Aktif</span>
              <?php else: ?>
                <span style="color:#b91c1c;">Nonaktif</span>
              <?php endif; ?>
            </div>
            <div style="font-size:12px;color:#9ca3af;margin-top:2px;">
              Dibuat: <?= e($p['created_at'] ?? '') ?>
            </div>
          </div>
          <div style="text-align:right;">
            <?php if ((int)$p['is_active'] === 1): ?>
              <form method="post" style="display:inline;">
                <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
                <input type="hidden" name="action" value="deactivate">
                <button type="submit"
                        style="padding:8px 10px;border-radius:8px;background:#fff;border:1px solid #f97373;color:#b91c1c;font-size:12px;cursor:pointer;">
                  Nonaktifkan
                </button>
              </form>
            <?php else: ?>
              <form method="post" style="display:inline;">
                <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
                <input type="hidden" name="action" value="activate">
                <button type="submit"
                        style="padding:8px 10px;border-radius:8px;background:#16a34a;border:none;color:#fff;font-size:12px;cursor:pointer;">
                  Aktifkan kembali
                </button>
              </form>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
</body>
</html>
