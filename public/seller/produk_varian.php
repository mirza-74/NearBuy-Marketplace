<?php
declare(strict_types=1);

// Pastikan BASE untuk header/asset
if (!isset($BASE) || !$BASE) { $BASE = '/Marketplace_SellExa/public'; }

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';

// Wajib admin
$admin = require_admin($BASE);

/* =========================
   Konfigurasi Upload
   ========================= */
$UPLOAD_DIR_FS  = dirname(__DIR__) . '/uploads/products';   // filesystem: /public/uploads/products
$UPLOAD_DIR_URL = $BASE . '/uploads/products';              // url: /Marketplace_SellExa/public/uploads/products
$MAX_SIZE       = 5 * 1024 * 1024;                          // 5 MB
$ALLOWED_MIME   = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
if (!is_dir($UPLOAD_DIR_FS)) { @mkdir($UPLOAD_DIR_FS, 0775, true); }

/* =========================
   Helper
   ========================= */
function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function is_https_url(string $p): bool { return (bool)preg_match('~^https?://~i', $p); }
function move_uploaded_image(array $f, string $UPLOAD_DIR_FS, array $ALLOWED_MIME, int $MAX_SIZE): string {
  if (($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return '';
  if ($f['error'] !== UPLOAD_ERR_OK) { throw new RuntimeException('Gagal upload (ERR '.$f['error'].')'); }
  if ($f['size'] > $MAX_SIZE) { throw new RuntimeException('Ukuran gambar > 5MB'); }
  $fi = new finfo(FILEINFO_MIME_TYPE);
  $mime = $fi->file($f['tmp_name']) ?: '';
  if (!isset($ALLOWED_MIME[$mime])) { throw new RuntimeException('Tipe tidak didukung (hanya JPG/PNG/WebP)'); }
  $ext   = $ALLOWED_MIME[$mime];
  $stamp = date('Ymd_His');
  $rand  = bin2hex(random_bytes(3));
  $fname = "variant_{$stamp}_{$rand}.{$ext}";
  $dest  = rtrim($UPLOAD_DIR_FS,'/\\') . '/' . $fname;
  if (!move_uploaded_file($f['tmp_name'], $dest)) { throw new RuntimeException('Tidak dapat menyimpan file'); }
  return 'products/' . $fname; // simpan relatif dari /uploads
}
function delete_local_image_if_any(?string $path): void {
  if (!$path) return;
  if (is_https_url($path)) return; // URL eksternal, jangan dihapus
  $abs = dirname(__DIR__) . '/uploads/' . ltrim($path,'/');
  if (is_file($abs) && str_contains(str_replace('\\','/',$abs), '/uploads/products/')) {
    @unlink($abs);
  }
}

/* =========================
   Deteksi kolom opsional (compare_price) di product_variants
   ========================= */
$hasCompare = true;
try { $pdo->query("SELECT compare_price FROM product_variants LIMIT 0"); } catch (Throwable $e) { $hasCompare = false; }

/* =========================
   Ambil produk induk
   ========================= */
$productId = (int)($_GET['product_id'] ?? 0);
if ($productId <= 0) { header('Location: '.$BASE.'/admin/produk.php'); exit; }

$prod = $pdo->prepare("SELECT id, title, price, compare_price, stock, main_image, is_active FROM products WHERE id=?");
$prod->execute([$productId]);
$product = $prod->fetch(PDO::FETCH_ASSOC);
if (!$product) {
  $_SESSION['flash'] = 'Produk tidak ditemukan.';
  header('Location: '.$BASE.'/admin/produk.php'); exit;
}

/* =========================
   Actions
   ========================= */
$action = $_POST['action'] ?? '';
if (!empty($action) && function_exists('csrf_verify') && !csrf_verify($_POST['csrf'] ?? '')) {
  $_SESSION['flash'] = 'Sesi berakhir. Coba lagi.';
  header('Location: '.$_SERVER['REQUEST_URI']); exit;
}

try {
  if ($action === 'create_var') {
    $name  = trim($_POST['name'] ?? '');
    if ($name === '') throw new RuntimeException('Nama varian wajib diisi.');
    $sku   = trim($_POST['sku'] ?? '');
    $price = ($_POST['price'] === '' ? null : (float)$_POST['price']);
    $cprice= $hasCompare ? ($_POST['compare_price'] === '' ? null : (float)$_POST['compare_price']) : null;
    $stock = (int)($_POST['stock'] ?? 0);

    // Gambar varian: upload file atau path/url manual
    $img = '';
    if (!empty($_FILES['image_file']) && ($_FILES['image_file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
      $img = move_uploaded_image($_FILES['image_file'], $UPLOAD_DIR_FS, $ALLOWED_MIME, $MAX_SIZE);
    } else {
      $manual = trim($_POST['image'] ?? '');
      if ($manual !== '') {
        $img = is_https_url($manual) ? $manual : ltrim($manual, '/');
      }
    }

    if ($hasCompare) {
      $stmt = $pdo->prepare("INSERT INTO product_variants (product_id, name, sku, price, compare_price, stock, image, is_active, created_at)
                             VALUES (?,?,?,?,?,?,?,1,NOW())");
      $stmt->execute([$productId, $name, ($sku ?: null), $price, $cprice, $stock, ($img ?: null)]);
    } else {
      $stmt = $pdo->prepare("INSERT INTO product_variants (product_id, name, sku, price, stock, image, is_active, created_at)
                             VALUES (?,?,?,?,?,?,1,NOW())");
      $stmt->execute([$productId, $name, ($sku ?: null), $price, $stock, ($img ?: null)]);
    }

    $_SESSION['flash'] = 'Varian ditambahkan.';
    header('Location: '.$_SERVER['REQUEST_URI']); exit;
  }

  if ($action === 'update_var') {
    $id    = (int)($_POST['id'] ?? 0);
    $name  = trim($_POST['name'] ?? '');
    if ($id <= 0 || $name === '') throw new RuntimeException('Data tidak valid.');

    $sku   = trim($_POST['sku'] ?? '');
    $price = ($_POST['price'] === '' ? null : (float)$_POST['price']);
    $cprice= $hasCompare ? ($_POST['compare_price'] === '' ? null : (float)$_POST['compare_price']) : null;
    $stock = (int)($_POST['stock'] ?? 0);

    // Ambil gambar lama
    $old = $pdo->prepare("SELECT image FROM product_variants WHERE id=? AND product_id=?");
    $old->execute([$id,$productId]);
    $old_img = (string)$old->fetchColumn();

    $newImg = '';
    if (!empty($_FILES['image_file']) && ($_FILES['image_file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
      $newImg = move_uploaded_image($_FILES['image_file'], $UPLOAD_DIR_FS, $ALLOWED_MIME, $MAX_SIZE);
      delete_local_image_if_any($old_img);
    } else {
      $manual = trim($_POST['image'] ?? '');
      if ($manual !== '') {
        $newImg = is_https_url($manual) ? $manual : ltrim($manual, '/');
        if ($newImg !== $old_img) { delete_local_image_if_any($old_img); }
      } else {
        $newImg = $old_img;
      }
    }

    if ($hasCompare) {
      $stmt = $pdo->prepare("UPDATE product_variants SET name=?, sku=?, price=?, compare_price=?, stock=?, image=?, updated_at=NOW()
                             WHERE id=? AND product_id=?");
      $stmt->execute([$name, ($sku ?: null), $price, $cprice, $stock, ($newImg ?: null), $id, $productId]);
    } else {
      $stmt = $pdo->prepare("UPDATE product_variants SET name=?, sku=?, price=?, stock=?, image=?, updated_at=NOW()
                             WHERE id=? AND product_id=?");
      $stmt->execute([$name, ($sku ?: null), $price, $stock, ($newImg ?: null), $id, $productId]);
    }

    $_SESSION['flash'] = 'Varian diperbarui.';
    header('Location: '.$_SERVER['REQUEST_URI']); exit;
  }

  if ($action === 'toggle_var') {
    $id = (int)$_POST['id'];
    $pdo->prepare("UPDATE product_variants SET is_active=1-is_active WHERE id=? AND product_id=?")
        ->execute([$id,$productId]);
    $_SESSION['flash'] = 'Status varian diubah.';
    header('Location: '.$_SERVER['REQUEST_URI']); exit;
  }

  if ($action === 'delete_var') {
    $id = (int)$_POST['id'];

    $stmt = $pdo->prepare("SELECT image FROM product_variants WHERE id=? AND product_id=?");
    $stmt->execute([$id,$productId]);
    $img = (string)$stmt->fetchColumn();
    delete_local_image_if_any($img);

    $pdo->prepare("DELETE FROM product_variants WHERE id=? AND product_id=?")
        ->execute([$id,$productId]);
    $_SESSION['flash'] = 'Varian dihapus.';
    header('Location: '.$_SERVER['REQUEST_URI']); exit;
  }

} catch (Throwable $e) {
  $_SESSION['flash'] = 'Error: '.$e->getMessage();
  header('Location: '.$_SERVER['REQUEST_URI']); exit;
}

/* =========================
   Data tampilan
   ========================= */
$vars = $pdo->prepare("SELECT id, name, sku, price ".($hasCompare?", compare_price":"").", stock, image, is_active, created_at
                       FROM product_variants
                       WHERE product_id=?
                       ORDER BY name ASC, id DESC");
$vars->execute([$productId]);
$variants = $vars->fetchAll(PDO::FETCH_ASSOC);

// Setelah semua siap → muat header
require_once __DIR__ . '/../../includes/header.php';
?>

<style>
.admin-wrap{max-width:1000px;margin:24px auto;padding:0 16px;}
h1{font-size:1.6rem;font-weight:700;margin-bottom:12px;}
.flash{background:#ecfeff;color:#164e63;padding:10px 12px;border-radius:10px;margin:10px 0;border:1px solid #a5f3fc;}
.form-grid{display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:8px;align-items:start}
.form-grid .full{grid-column:1 / -1}
.form-grid input,.form-grid textarea{padding:8px;border:1px solid #e5e8ef;border-radius:10px;background:#fff}
.form-grid input[type="file"]{border-style:dashed}
.form-actions{display:flex;gap:8px;margin-top:6px}
.btn{padding:8px 12px;border-radius:10px;border:1px solid #e5e8ef;background:#f9fafb;cursor:pointer}
.btn.primary{border:none;background:linear-gradient(90deg,#3b82f6,#8b5cf6);color:#fff;font-weight:600}
.table{width:100%;border-collapse:collapse;background:#fff;border:1px solid #e5e8ef;border-radius:10px;overflow:hidden;margin-top:12px}
.table th,.table td{padding:10px;border-bottom:1px solid #eef2f7;text-align:left;font-size:.95rem;vertical-align:middle}
.table th{background:#f8fafc}
.thumb{width:56px;height:56px;object-fit:cover;border-radius:8px;border:1px solid #e5e8ef;background:#f3f4f6}
.badge{padding:4px 8px;border-radius:8px;font-size:.8rem;font-weight:600}
.b-on{background:#dcfce7;color:#166534}
.b-off{background:#fee2e2;color:#991b1b}
.small-note{color:#64748b;font-size:.85rem}
.edit-card{background:#fff;border:1px solid #e5e8ef;border-radius:12px;padding:12px;margin:10px 0}
.action form{display:inline}
.action .btn{margin:2px}
</style>

<section class="admin-wrap">
  <h1>Varian Produk — <?= e($product['title']) ?></h1>
  <p class="small-note">
    Harga produk induk: 
    <?php if (!is_null($product['compare_price']) && (float)$product['compare_price'] > (float)$product['price']): ?>
      <span style="text-decoration:line-through;color:#94a3b8">Rp<?= number_format((float)$product['compare_price'],0,',','.') ?></span>
    <?php endif; ?>
    <strong>Rp<?= number_format((float)$product['price'],0,',','.') ?></strong>
  </p>
  <p><a class="btn" href="<?= e($BASE) ?>/admin/produk.php">← Kembali ke Produk</a></p>

  <?php if (!empty($_SESSION['flash'])): ?>
    <div class="flash"><?= e($_SESSION['flash']); unset($_SESSION['flash']); ?></div>
  <?php endif; ?>

  <!-- CREATE VARIANT -->
  <div class="edit-card">
    <h3 style="margin:0 0 8px">Tambah Varian</h3>
    <form method="post" enctype="multipart/form-data">
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="action" value="create_var">
      <div class="form-grid">
        <input class="col" type="text"   name="name"  placeholder="Nama varian (cth: Merah / Biru / Hijau) *" required>
        <input class="col" type="text"   name="sku"   placeholder="SKU (opsional)">
        <input class="col" type="number" name="price" placeholder="Harga khusus (opsional)" step="100" min="0">
        <?php if ($hasCompare): ?>
          <input class="col" type="number" name="compare_price" placeholder="Harga banding (opsional)" step="100" min="0">
        <?php endif; ?>
        <input class="col" type="number" name="stock" placeholder="Stok *" min="0" required>

        <input class="col" type="file"   name="image_file" accept=".jpg,.jpeg,.png,.webp">
        <input class="col" type="text"   name="image" placeholder="(Opsional) path/URL gambar varian">
      </div>
      <div class="form-actions">
        <button class="btn primary" type="submit">+ Tambah Varian</button>
        <span class="small-note">Jika upload & path/URL diisi, yang dipakai adalah file upload. Maks 5MB.</span>
      </div>
    </form>
  </div>

  <!-- LIST VARIANTS -->
  <table class="table">
    <thead>
      <tr>
        <th>Varian</th>
        <th>Harga</th>
        <th>Stok</th>
        <th>Status</th>
        <th style="width:340px">Aksi / Edit Cepat</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($variants as $v): ?>
        <tr>
          <td>
            <div style="display:flex;align-items:center;gap:10px;">
              <?php
                $src = '';
                if (!empty($v['image'])) {
                  $src = is_https_url($v['image']) ? $v['image'] : ($BASE.'/'.ltrim($v['image'],'/'));
                } else {
                  $src = 'https://via.placeholder.com/56?text=No+Img';
                }
              ?>
              <img class="thumb" src="<?= e($src) ?>" alt="">
              <div>
                <div style="font-weight:600"><?= e($v['name']) ?></div>
                <div class="small-note">SKU: <?= e($v['sku'] ?? '-') ?></div>
                <div class="small-note">Dibuat: <?= e($v['created_at']) ?></div>
              </div>
            </div>
          </td>
          <td>
            <?php if ($hasCompare && !is_null($v['compare_price']) && (float)$v['compare_price'] > (float)($v['price'] ?? $product['price'])): ?>
              <div style="text-decoration:line-through;color:#94a3b8">Rp<?= number_format((float)$v['compare_price'],0,',','.') ?></div>
            <?php endif; ?>
            <div>
              <?php if (is_null($v['price'])): ?>
                <span class="small-note" style="color:#64748b">Ikut induk</span>
                <strong> Rp<?= number_format((float)$product['price'],0,',','.') ?></strong>
              <?php else: ?>
                <strong>Rp<?= number_format((float)$v['price'],0,',','.') ?></strong>
              <?php endif; ?>
            </div>
          </td>
          <td><?= (int)$v['stock'] ?></td>
          <td>
            <span class="badge <?= $v['is_active'] ? 'b-on' : 'b-off' ?>">
              <?= $v['is_active'] ? 'Aktif' : 'Nonaktif' ?>
            </span>
          </td>
          <td class="action">
            <!-- Edit cepat -->
            <form method="post" enctype="multipart/form-data" style="display:flex;gap:6px;flex-wrap:wrap;align-items:center">
              <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
              <input type="hidden" name="action" value="update_var">
              <input type="hidden" name="id" value="<?= (int)$v['id'] ?>">

              <input type="text"   name="name"  value="<?= e($v['name']) ?>" style="width:140px" required>
              <input type="text"   name="sku"   value="<?= e($v['sku'] ?? '') ?>" style="width:110px">
              <input type="number" name="price" value="<?= is_null($v['price']) ? '' : (float)$v['price'] ?>" step="100" min="0" style="width:120px">
              <?php if ($hasCompare): ?>
                <input type="number" name="compare_price" value="<?= (isset($v['compare_price']) && !is_null($v['compare_price'])) ? (float)$v['compare_price'] : '' ?>" step="100" min="0" style="width:120px">
              <?php endif; ?>
              <input type="number" name="stock" value="<?= (int)$v['stock'] ?>" min="0" style="width:90px">

              <input type="file"   name="image_file" accept=".jpg,.jpeg,.png,.webp" style="width:180px">
              <input type="text"   name="image" value="<?= e($v['image'] ?? '') ?>" placeholder="atau path/URL gambar" style="width:220px">

              <button class="btn">Simpan</button>
            </form>

            <form method="post" style="display:inline">
              <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
              <input type="hidden" name="action" value="toggle_var">
              <input type="hidden" name="id" value="<?= (int)$v['id'] ?>">
              <button class="btn">Aktif/Nonaktif</button>
            </form>

            <form method="post" style="display:inline" onsubmit="return confirm('Hapus varian ini?')">
              <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
              <input type="hidden" name="action" value="delete_var">
              <input type="hidden" name="id" value="<?= (int)$v['id'] ?>">
              <button class="btn">Hapus</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$variants): ?>
        <tr><td colspan="5" class="small-note" style="color:#64748b">Belum ada varian. Tambahkan di form di atas.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>

  <p style="margin-top:12px">
    <a class="btn" href="<?= e($BASE) ?>/admin/produk.php">← Kembali ke Produk</a>
  </p>
</section>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
