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
function limit_words(string $s, int $max=250): string {
  $s = trim($s);
  if ($s === '') return '';
  $words = preg_split('~\s+~u', $s);
  if (!$words) return '';
  if (count($words) <= $max) return $s;
  return implode(' ', array_slice($words, 0, $max));
}
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
  $fname = "product_{$stamp}_{$rand}.{$ext}";
  $dest  = rtrim($UPLOAD_DIR_FS,'/\\') . '/' . $fname;
  if (!move_uploaded_file($f['tmp_name'], $dest)) { throw new RuntimeException('Tidak dapat menyimpan file'); }
  return 'products/' . $fname; // simpan relatif dari /uploads
}
function delete_local_image_if_any(?string $path): void {
  if (!$path) return;
  if (is_https_url($path)) return; // URL eksternal, jangan dihapus
  $abs = dirname(__DIR__) . '/uploads/' . ltrim($path,'/');
  // safety: hanya hapus jika benar di /uploads/products/
  if (is_file($abs) && str_contains(str_replace('\\','/',$abs), '/uploads/products/')) {
    @unlink($abs);
  }
}
function slugify(string $s): string {
  $s = strtolower(trim($s));
  $s = preg_replace('~[^a-z0-9]+~', '-', $s);
  $s = trim($s, '-');
  return $s ?: ('produk-'.date('YmdHis'));
}
function make_unique_slug(PDO $pdo, string $base, ?int $excludeId = null): string {
  $slug = $base;
  $i = 1;
  while (true) {
    if ($excludeId) {
      $q = $pdo->prepare("SELECT COUNT(*) FROM products WHERE slug=? AND id<>?");
      $q->execute([$slug, $excludeId]);
    } else {
      $q = $pdo->prepare("SELECT COUNT(*) FROM products WHERE slug=?");
      $q->execute([$slug]);
    }
    $cnt = (int)$q->fetchColumn();
    if ($cnt === 0) return $slug;
    $i++;
    $slug = $base . '-' . $i;
  }
}

/* =========================
   Ambil data kategori aktif (untuk form)
   ========================= */
$allCats = $pdo->query("SELECT id, name FROM categories WHERE is_active=1 ORDER BY sort_order, name")
               ->fetchAll(PDO::FETCH_ASSOC);

/* =========================
   Actions
   ========================= */
$action = $_POST['action'] ?? '';
if (!empty($action) && function_exists('csrf_verify') && !csrf_verify($_POST['csrf'] ?? '')) {
  $_SESSION['flash'] = 'Sesi berakhir. Coba lagi.';
  header('Location: '.$_SERVER['REQUEST_URI']); exit;
}

try {
  if ($action === 'create') {
    $title   = trim($_POST['title'] ?? '');
    if ($title === '') throw new RuntimeException('Nama produk wajib diisi.');

    $desc    = limit_words((string)($_POST['description'] ?? ''), 250);
    $price   = (float)($_POST['price'] ?? 0);
    $compare = ($_POST['compare_price'] === '' ? null : (float)$_POST['compare_price']);
    $stock   = (int)($_POST['stock'] ?? 0);
    $catIds  = array_map('intval', $_POST['category_ids'] ?? []);

    // Gambar: upload file atau path/url manual
    $mainImage = '';
    if (!empty($_FILES['image_file']) && ($_FILES['image_file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
      $mainImage = move_uploaded_image($_FILES['image_file'], $UPLOAD_DIR_FS, $ALLOWED_MIME, $MAX_SIZE);
    } else {
      $manual = trim($_POST['main_image'] ?? '');
      if ($manual !== '') {
        $mainImage = is_https_url($manual) ? $manual : ltrim($manual, '/');
      }
    }

    $baseSlug = slugify($title);
    $slug     = make_unique_slug($pdo, $baseSlug, null);

    $pdo->beginTransaction();
    try {
      $stmt = $pdo->prepare("
        INSERT INTO products (title, slug, description, price, compare_price, stock, main_image, is_active, created_at)
        VALUES (?,?,?,?,?,?,?,1,NOW())
      ");
      $stmt->execute([$title,$slug,$desc,$price,$compare,$stock,$mainImage ?: null]);
      $productId = (int)$pdo->lastInsertId();

      if ($catIds) {
        $ins = $pdo->prepare("INSERT IGNORE INTO product_categories (product_id, category_id) VALUES (?,?)");
        foreach ($catIds as $cid) { $ins->execute([$productId, $cid]); }
      }

      $pdo->commit();
    } catch (Throwable $ie) {
      $pdo->rollBack(); throw $ie;
    }

    $_SESSION['flash'] = 'Produk ditambahkan.';
    header('Location: '.$_SERVER['REQUEST_URI']); exit;
  }

  if ($action === 'update') {
    $id      = (int)($_POST['id'] ?? 0);
    $title   = trim($_POST['title'] ?? '');
    if ($id <= 0 || $title === '') throw new RuntimeException('Data tidak valid.');

    $desc    = limit_words((string)($_POST['description'] ?? ''), 250);
    $price   = (float)($_POST['price'] ?? 0);
    $compare = ($_POST['compare_price'] === '' ? null : (float)$_POST['compare_price']);
    $stock   = (int)($_POST['stock'] ?? 0);
    $catIds  = array_map('intval', $_POST['category_ids'] ?? []);

    // Ambil data lama utk gambar & slug
    $old = $pdo->prepare("SELECT main_image, slug FROM products WHERE id=?");
    $old->execute([$id]);
    $oldRow = $old->fetch(PDO::FETCH_ASSOC);
    if (!$oldRow) throw new RuntimeException('Produk tidak ditemukan.');
    $old_img  = (string)($oldRow['main_image'] ?? '');
    $old_slug = (string)($oldRow['slug'] ?? '');

    // Slug unik (jika judul berubah)
    $baseSlug = slugify($title);
    $slug     = ($baseSlug === $old_slug) ? $old_slug : make_unique_slug($pdo, $baseSlug, $id);

    // Gambar
    $newImg = '';
    if (!empty($_FILES['image_file']) && ($_FILES['image_file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
      // upload baru → hapus lama jika lokal
      $newImg = move_uploaded_image($_FILES['image_file'], $UPLOAD_DIR_FS, $ALLOWED_MIME, $MAX_SIZE);
      delete_local_image_if_any($old_img);
    } else {
      $manual = trim($_POST['main_image'] ?? '');
      if ($manual !== '') {
        $newImg = is_https_url($manual) ? $manual : ltrim($manual, '/');
        if ($newImg !== $old_img) { delete_local_image_if_any($old_img); }
      } else {
        $newImg = $old_img;
      }
    }

    $pdo->beginTransaction();
    try {
      $stmt = $pdo->prepare("
        UPDATE products
        SET title=?, slug=?, description=?, price=?, compare_price=?, stock=?, main_image=?, updated_at=NOW()
        WHERE id=?
      ");
      $stmt->execute([$title,$slug,$desc,$price,$compare,$stock,$newImg ?: null,$id]);

      // Reset & pasang ulang kategori
      $pdo->prepare("DELETE FROM product_categories WHERE product_id=?")->execute([$id]);
      if ($catIds) {
        $ins = $pdo->prepare("INSERT IGNORE INTO product_categories (product_id, category_id) VALUES (?,?)");
        foreach ($catIds as $cid) { $ins->execute([$id, $cid]); }
      }

      $pdo->commit();
    } catch (Throwable $ie) {
      $pdo->rollBack(); throw $ie;
    }

    $_SESSION['flash'] = 'Produk diperbarui.';
    header('Location: '.$BASE.'/admin/produk.php'); exit;
  }

  if ($action === 'toggle_active') {
    $id = (int)$_POST['id'];
    $pdo->prepare("UPDATE products SET is_active = 1 - is_active WHERE id=?")->execute([$id]);
    $_SESSION['flash'] = 'Status aktif diubah.';
    header('Location: '.$_SERVER['REQUEST_URI']); exit;
  }

  if ($action === 'delete') {
    $id = (int)$_POST['id'];

    $stmt = $pdo->prepare("SELECT main_image FROM products WHERE id=?");
    $stmt->execute([$id]);
    $img = (string)$stmt->fetchColumn();
    delete_local_image_if_any($img);

    // hapus produk (pivot product_categories akan terhapus karena FK ON DELETE CASCADE)
    $pdo->prepare("DELETE FROM products WHERE id=?")->execute([$id]);
    $_SESSION['flash'] = 'Produk dihapus.';
    header('Location: '.$_SERVER['REQUEST_URI']); exit;
  }
} catch (Throwable $e) {
  $_SESSION['flash'] = 'Error: '.$e->getMessage();
  header('Location: '.$_SERVER['REQUEST_URI']); exit;
}

/* =========================
   Data untuk tampilan
   ========================= */
$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$editItem = null;
$editItemCatIds = [];
if ($editId > 0) {
  $sel = "SELECT id,title,description,price,compare_price,stock,main_image,is_active,created_at FROM products WHERE id=?";
  $st  = $pdo->prepare($sel); $st->execute([$editId]); $editItem = $st->fetch(PDO::FETCH_ASSOC);

  if ($editItem) {
    $pc = $pdo->prepare("SELECT category_id FROM product_categories WHERE product_id=?");
    $pc->execute([$editId]);
    $editItemCatIds = array_map('intval', $pc->fetchAll(PDO::FETCH_COLUMN));
  }
}

// list produk
$items = $pdo->query("
  SELECT id,title,price,compare_price,stock,main_image,is_active,created_at,description
  FROM products
  ORDER BY created_at DESC
  LIMIT 200
")->fetchAll(PDO::FETCH_ASSOC);

// Ambil kategori untuk seluruh produk list (satu query, lalu map)
$ids = array_map(fn($r)=> (int)$r['id'], $items);
$catMap = [];
if ($ids) {
  $in = implode(',', array_fill(0, count($ids), '?'));
  $q = $pdo->prepare("
    SELECT pc.product_id, c.name
    FROM product_categories pc
    JOIN categories c ON c.id=pc.category_id
    WHERE pc.product_id IN ($in)
    ORDER BY c.sort_order, c.name
  ");
  $q->execute($ids);
  foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $pid = (int)$row['product_id'];
    $catMap[$pid][] = $row['name'];
  }
}

// Setelah semua siap → muat header (mencetak <head> dan mulai <main>)
require_once __DIR__ . '/../../includes/header.php';
?>

<style>
.admin-wrap{max-width:1200px;margin:24px auto;padding:0 16px;}
h1{font-size:1.6rem;font-weight:700;margin-bottom:12px;}
.flash{background:#ecfeff;color:#164e63;padding:10px 12px;border-radius:10px;margin:10px 0;border:1px solid #a5f3fc;}
.form-grid{display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:8px;align-items:start}
.form-grid .full{grid-column:1 / -1}
.form-grid input,.form-grid textarea,.form-grid select{padding:8px;border:1px solid #e5e8ef;border-radius:10px;background:#fff}
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
.action form{display:inline}
.action .btn{margin:2px}
.edit-card{background:#fff;border:1px solid #e5e8ef;border-radius:12px;padding:12px;margin:10px 0}
.cat-chips{display:flex;gap:8px;flex-wrap:wrap;margin-top:6px}
.cat-chip{background:#f1f5f9;border:1px solid #e5e8ef;padding:2px 8px;border-radius:999px;font-size:.78rem;color:#334155}
</style>

<section class="admin-wrap">
  <h1>Kelola Produk</h1>

  <?php if (!empty($_SESSION['flash'])): ?>
    <div class="flash"><?= e($_SESSION['flash']); unset($_SESSION['flash']); ?></div>
  <?php endif; ?>

  <!-- CREATE -->
  <div class="edit-card">
    <h3 style="margin:0 0 8px">Tambah Produk</h3>
    <form method="post" enctype="multipart/form-data">
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="action" value="create">
      <div class="form-grid">
        <input class="col" type="text"   name="title"     placeholder="Nama produk *" required>
        <input class="col" type="number" name="price"     placeholder="Harga (Rp) *" step="100" min="0" required>
        <input class="col" type="number" name="compare_price" placeholder="Harga banding (opsional)" step="100" min="0">
        <input class="col" type="number" name="stock"     placeholder="Stok *" min="0" required>

        <input class="col" type="file"   name="image_file" accept=".jpg,.jpeg,.png,.webp">
        <input class="col" type="text"   name="main_image" placeholder="(Opsional) path/URL gambar">

        <textarea class="full" name="description" placeholder="Deskripsi singkat (maks 250 kata)"></textarea>

        <!-- Kategori (checkbox) -->
        <div class="full" style="display:flex;gap:12px;flex-wrap:wrap;align-items:center">
          <div style="font-weight:600">Kategori:</div>
          <?php foreach ($allCats as $c): ?>
            <label style="display:flex;gap:6px;align-items:center;border:1px solid #e5e8ef;padding:6px 10px;border-radius:10px;background:#fff">
              <input type="checkbox" name="category_ids[]" value="<?= (int)$c['id'] ?>">
              <span><?= e($c['name']) ?></span>
            </label>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="form-actions">
        <button class="btn primary" type="submit">+ Tambah Produk</button>
        <span class="small-note">Jika upload & path/URL diisi, yang dipakai adalah file upload. Maks 5MB.</span>
      </div>
    </form>
  </div>

  <!-- UPDATE (jika memilih edit) -->
  <?php if ($editItem): ?>
  <div class="edit-card">
    <h3 style="margin:0 0 8px">Edit Produk: <?= e($editItem['title']) ?></h3>
    <form method="post" enctype="multipart/form-data">
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="action" value="update">
      <input type="hidden" name="id" value="<?= (int)$editItem['id'] ?>">

      <div class="form-grid">
        <input class="col" type="text"   name="title" value="<?= e($editItem['title']) ?>" required>
        <input class="col" type="number" name="price" value="<?= (float)$editItem['price'] ?>" step="100" min="0" required>
        <input class="col" type="number" name="compare_price" value="<?= is_null($editItem['compare_price']) ? '' : (float)$editItem['compare_price'] ?>" step="100" min="0">
        <input class="col" type="number" name="stock" value="<?= (int)$editItem['stock'] ?>" min="0" required>

        <input class="col" type="file" name="image_file" accept=".jpg,.jpeg,.png,.webp">
        <input class="col" type="text" name="main_image" value="<?= e($editItem['main_image']) ?>" placeholder="Kosongkan jika tidak diubah">
        <textarea class="full" name="description"><?= e($editItem['description']) ?></textarea>

        <!-- Kategori (checkbox) -->
        <div class="full" style="display:flex;gap:12px;flex-wrap:wrap;align-items:center">
          <div style="font-weight:600">Kategori:</div>
          <?php foreach ($allCats as $c): ?>
            <?php $checked = in_array((int)$c['id'], $editItemCatIds, true); ?>
            <label style="display:flex;gap:6px;align-items:center;border:1px solid #e5e8ef;padding:6px 10px;border-radius:10px;background:#fff">
              <input type="checkbox" name="category_ids[]" value="<?= (int)$c['id'] ?>" <?= $checked ? 'checked' : '' ?>>
              <span><?= e($c['name']) ?></span>
            </label>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="form-actions">
        <a class="btn" href="<?= e($BASE) ?>/admin/produk_varian.php?product_id=<?= (int)$editItem['id'] ?>">Varian</a>
        <button class="btn primary" type="submit">Simpan Perubahan</button>
        <a class="btn" href="<?= e($BASE) ?>/admin/produk.php">Batal</a>
        <span class="small-note">Upload gambar baru akan mengganti yang lama.</span>
      </div>
    </form>
  </div>
  <?php endif; ?>

  <!-- LIST -->
  <table class="table">
    <thead>
      <tr>
        <th>Produk</th>
        <th>Harga</th>
        <th>Stok</th>
        <th>Status</th>
        <th style="width:320px">Aksi</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($items as $it): ?>
        <tr>
          <td>
            <div style="display:flex;align-items:center;gap:10px;">
              <?php
                $src = '';
                if (!empty($it['main_image'])) {
                  if (is_https_url($it['main_image'])) {
                    $src = $it['main_image'];
                  } else {
                    // main_image disimpan relatif dari /uploads → tambahkan prefix uploads
                    $src = $BASE . '/uploads/' . ltrim($it['main_image'], '/');
                  }
                } else {
                  $src = 'https://via.placeholder.com/56?text=No+Img';
                }
                $pid = (int)$it['id'];
                $catNames = $catMap[$pid] ?? [];
              ?>
              <img class="thumb" src="<?= e($src) ?>" alt="">
              <div>
                <div style="font-weight:600"><?= e($it['title']) ?></div>
                <div class="small-note"><?= e(mb_strimwidth(trim($it['description'] ?? ''), 0, 70, '…', 'UTF-8')) ?></div>
                <div class="small-note">Dibuat: <?= e($it['created_at']) ?></div>
                <?php if ($catNames): ?>
                <div class="cat-chips">
                  <?php foreach ($catNames as $nm): ?>
                    <span class="cat-chip"><?= e($nm) ?></span>
                  <?php endforeach; ?>
                </div>
                <?php endif; ?>
              </div>
            </div>
          </td>
          <td>
            <?php if (!is_null($it['compare_price']) && (float)$it['compare_price'] > (float)$it['price']): ?>
              <div style="text-decoration:line-through;color:#94a3b8">Rp<?= number_format((float)$it['compare_price'],0,',','.') ?></div>
            <?php endif; ?>
            <div>Rp<?= number_format((float)$it['price'],0,',','.') ?></div>
          </td>
          <td><?= (int)$it['stock'] ?></td>
          <td>
            <span class="badge <?= $it['is_active'] ? 'b-on':'b-off' ?>">
              <?= $it['is_active'] ? 'Aktif':'Nonaktif' ?>
            </span>
          </td>
          <td class="action">
            <a class="btn" href="<?= e($BASE) ?>/admin/produk.php?edit=<?= (int)$it['id'] ?>">Edit</a>
            <form method="post" style="display:inline">
              <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
              <input type="hidden" name="action" value="toggle_active">
              <input type="hidden" name="id" value="<?= (int)$it['id'] ?>">
              <button class="btn">Aktif/Nonaktif</button>
            </form>
            <form method="post" style="display:inline" onsubmit="return confirm('Hapus produk ini?')">
              <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= (int)$it['id'] ?>">
              <button class="btn">Hapus</button>
            </form>
            <!-- Jika nanti sudah ada halaman varian admin:
            <a class="btn" href="<?= e($BASE) ?>/admin/produk_varian.php?product_id=<?= (int)$it['id'] ?>">Varian</a>
            -->
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</section>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
