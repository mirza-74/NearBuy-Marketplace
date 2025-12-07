<?php
// ===============================================================
// NearBuy – Kelola Produk Seller (CRUD produk per toko seller)
// ===============================================================
declare(strict_types=1);

// Deteksi BASE otomatis, buang /seller di ujung
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$BASE = preg_replace('~/seller$~', '', rtrim($scriptDir, '/'));

// Include dasar
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/db.php';

// Cek login dan role seller
$user = $_SESSION['user'] ?? null;
if (!$user || ($user['role'] ?? '') !== 'seller') {
    header('Location: ' . $BASE . '/login.php');
    exit;
}

$sellerId = (int)($user['id'] ?? 0);

// CSS khusus seller dashboard
$EXTRA_CSS = ['seller/style-admin-dashboard.css'];

// Helper dasar
if (!function_exists('e')) {
    function e(string $s): string {
        return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
    }
}

// ===============================================================
// Konfigurasi Upload Gambar Produk
// ===============================================================
$UPLOAD_DIR_FS 	= dirname(__DIR__) . '/uploads/products'; 	// /public/uploads/products
$UPLOAD_DIR_URL = $BASE . '/uploads/products'; 				// /NearBuy-marketplace/public/uploads/products
$MAX_SIZE 		= 5 * 1024 * 1024; 							// 5MB
$ALLOWED_MIME 	= [
    'image/jpeg' => 'jpg',
    'image/png' 	=> 'png',
    'image/webp' => 'webp',
];

if (!is_dir($UPLOAD_DIR_FS)) {
    @mkdir($UPLOAD_DIR_FS, 0775, true);
}

// Helper upload
function limit_words(string $s, int $max = 250): string {
    $s = trim($s);
    if ($s === '') return '';
    $words = preg_split('~\s+~u', $s);
    if (!$words) return '';
    if (count($words) <= $max) return $s;
    return implode(' ', array_slice($words, 0, $max));
}

function is_https_url(string $p): bool {
    return (bool)preg_match('~^https?://~i', $p);
}

function move_uploaded_image(array $f, string $UPLOAD_DIR_FS, array $ALLOWED_MIME, int $MAX_SIZE): string {
    if (($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return '';
    }

    if ($f['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Gagal upload gambar. Kode error: '.$f['error']);
    }

    if ($f['size'] > $MAX_SIZE) {
        throw new RuntimeException('Ukuran gambar melebihi 5MB.');
    }

    $fi 	= new finfo(FILEINFO_MIME_TYPE);
    $mime = $fi->file($f['tmp_name']) ?: '';

    if (!isset($ALLOWED_MIME[$mime])) {
        throw new RuntimeException('Tipe gambar tidak didukung. Gunakan JPG, PNG, atau WebP.');
    }

    $ext 	= $ALLOWED_MIME[$mime];
    $stamp = date('Ymd_His');
    $rand 	= bin2hex(random_bytes(3));
    $fname = "product_{$stamp}_{$rand}.{$ext}";

    $dest = rtrim($UPLOAD_DIR_FS, '/\\') . '/' . $fname;

    if (!move_uploaded_file($f['tmp_name'], $dest)) {
        throw new RuntimeException('Gagal menyimpan file gambar.');
    }

    // simpan relatif dari folder /uploads
    return 'products/' . $fname;
}

function delete_local_image_if_any(?string $path): void {
    if (!$path) return;
    if (is_https_url($path)) return;

    $abs = dirname(__DIR__) . '/uploads/' . ltrim($path, '/');
    $absNorm = str_replace('\\', '/', $abs);

    if (is_file($abs) && str_contains($absNorm, '/uploads/products/')) {
        @unlink($abs);
    }
}

function slugify(string $s): string {
    $s = strtolower(trim($s));
    $s = preg_replace('~[^a-z0-9]+~', '-', $s);
    $s = trim($s, '-');
    return $s ?: ('produk-' . date('YmdHis'));
}

function make_unique_slug(PDO $pdo, string $base, ?int $excludeId = null): string {
    $slug = $base;
    $i 	= 1;

    while (true) {
        if ($excludeId) {
            $q = $pdo->prepare("SELECT COUNT(*) FROM products WHERE slug = ? AND id <> ?");
            $q->execute([$slug, $excludeId]);
        } else {
            $q = $pdo->prepare("SELECT COUNT(*) FROM products WHERE slug = ?");
            $q->execute([$slug]);
        }
        $cnt = (int)$q->fetchColumn();
        if ($cnt === 0) {
            return $slug;
        }
        $i++;
        $slug = $base . '-' . $i;
    }
}

// ===============================================================
// Ambil daftar toko milik seller
// ===============================================================
$stmtShop = $pdo->prepare("SELECT id, name, address FROM shops WHERE user_id = ? ORDER BY created_at ASC");
$stmtShop->execute([$sellerId]);
$sellerShops = $stmtShop->fetchAll(PDO::FETCH_ASSOC);

$hasShop = !empty($sellerShops);

// Jika belum punya toko, nanti di tampilan akan ada pesan
// dan form tambah produk tidak akan muncul

// ===============================================================
// Ambil kategori aktif untuk checkbox
// ===============================================================
$allCats = $pdo->query("
    SELECT id, name 
    FROM categories 
    WHERE is_active = 1 
    ORDER BY sort_order, name
")->fetchAll(PDO::FETCH_ASSOC);

// ===============================================================
// Aksi form (create, update, delete) khusus produk milik seller
// ===============================================================
$action = $_POST['action'] ?? '';

if (!empty($action) && function_exists('csrf_verify') && !csrf_verify($_POST['csrf'] ?? '')) {
    $_SESSION['flash'] = 'Sesi berakhir. Coba ulangi.';
    header('Location: '.$_SERVER['REQUEST_URI']);
    exit;
}

try {
    if ($action === 'create') {
        if (!$hasShop) {
            throw new RuntimeException('Buat toko terlebih dahulu sebelum menambah produk.');
        }

        // -----------------------------------------------------------------
        // >>> LOGIKA BATASAN PRODUK DITAMBAHKAN DI SINI <<<
        // -----------------------------------------------------------------
        $MAX_PRODUCTS_LIMIT = 20; // Atur batas maksimum produk per seller

        $currentProductCount = $pdo->prepare("
            SELECT COUNT(p.id) 
            FROM products p 
            JOIN shops s ON s.id = p.shop_id 
            WHERE s.user_id = ?
        ");
        $currentProductCount->execute([$sellerId]);
        $count = (int)$currentProductCount->fetchColumn();

        if ($count >= $MAX_PRODUCTS_LIMIT) {
             throw new RuntimeException('Batas produk kamu ('.$MAX_PRODUCTS_LIMIT.') sudah tercapai. Hapus produk lama atau hubungi admin untuk *upgrade* paket.');
        }
        // -----------------------------------------------------------------
        // >>> AKHIR LOGIKA BATASAN PRODUK <<<
        // -----------------------------------------------------------------
        
        $title 	 = trim($_POST['title'] ?? '');
        $shopId 	= (int)($_POST['shop_id'] ?? 0);
        $desc 	 = limit_words((string)($_POST['description'] ?? ''), 250);
        $price 	= (float)($_POST['price'] ?? 0);
        $compare = ($_POST['compare_price'] === '' ? null : (float)$_POST['compare_price']);
        $stock 	= (int)($_POST['stock'] ?? 0);
        $catIds 	= array_map('intval', $_POST['category_ids'] ?? []);

        if ($title === '' || $shopId <= 0 || $price <= 0) {
            throw new RuntimeException('Nama produk, toko, dan harga wajib diisi.');
        }

        // Pastikan toko ini milik seller yang login
        $checkShop = $pdo->prepare("SELECT id FROM shops WHERE id = ? AND user_id = ? LIMIT 1");
        $checkShop->execute([$shopId, $sellerId]);
        if (!$checkShop->fetchColumn()) {
            throw new RuntimeException('Toko tidak valid.');
        }

        // Gambar
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
        $slug 	 = make_unique_slug($pdo, $baseSlug, null);

        $pdo->beginTransaction();
        try {
            // is_active = 0 untuk status pending, admin yang mengaktifkan
            $stmt = $pdo->prepare("
                INSERT INTO products (
                    shop_id, title, slug, description,
                    price, compare_price, stock, main_image,
                    is_active, created_at
                )
                VALUES (?,?,?,?,?,?,?,?,0,NOW())
            ");

            $stmt->execute([
                $shopId,
                $title,
                $slug,
                $desc,
                $price,
                $compare,
                $stock,
                $mainImage ?: null
            ]);

            $productId = (int)$pdo->lastInsertId();

            if ($catIds) {
                $ins = $pdo->prepare("
                    INSERT IGNORE INTO product_categories (product_id, category_id)
                    VALUES (?, ?)
                ");
                foreach ($catIds as $cid) {
                    if ($cid > 0) {
                        $ins->execute([$productId, $cid]);
                    }
                }
            }

            $pdo->commit();
        } catch (Throwable $ie) {
            $pdo->rollBack();
            throw $ie;
        }

        $_SESSION['flash'] = 'Produk berhasil ditambahkan. Menunggu persetujuan admin.';
        header('Location: '.$_SERVER['REQUEST_URI']);
        exit;
    }

    if ($action === 'update') {
        $id 	 	= (int)($_POST['id'] ?? 0);
        $title 	 = trim($_POST['title'] ?? '');
        $desc 	 = limit_words((string)($_POST['description'] ?? ''), 250);
        $price 	= (float)($_POST['price'] ?? 0);
        $compare = ($_POST['compare_price'] === '' ? null : (float)$_POST['compare_price']);
        $stock 	= (int)($_POST['stock'] ?? 0);
        $catIds 	= array_map('intval', $_POST['category_ids'] ?? []);

        if ($id <= 0 || $title === '' || $price <= 0) {
            throw new RuntimeException('Data produk tidak lengkap.');
        }

        // Pastikan produk ini milik toko yang dimiliki seller
        $sel = $pdo->prepare("
            SELECT p.id, p.shop_id, p.main_image, p.slug
            FROM products p
            JOIN shops s ON s.id = p.shop_id
            WHERE p.id = ? AND s.user_id = ?
            LIMIT 1
        ");
        $sel->execute([$id, $sellerId]);
        $oldRow = $sel->fetch(PDO::FETCH_ASSOC);

        if (!$oldRow) {
            throw new RuntimeException('Produk tidak ditemukan atau bukan milik Anda.');
        }

        $oldImg 	= (string)($oldRow['main_image'] ?? '');
        $oldSlug = (string)($oldRow['slug'] ?? '');

        // Slug baru jika judul berubah
        $baseSlug = slugify($title);
        $slug 	 = ($baseSlug === $oldSlug)
            ? $oldSlug
            : make_unique_slug($pdo, $baseSlug, $id);

        // Gambar
        $newImg = '';
        if (!empty($_FILES['image_file']) && ($_FILES['image_file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $newImg = move_uploaded_image($_FILES['image_file'], $UPLOAD_DIR_FS, $ALLOWED_MIME, $MAX_SIZE);
            delete_local_image_if_any($oldImg);
        } else {
            $manual = trim($_POST['main_image'] ?? '');
            if ($manual !== '') {
                $newImg = is_https_url($manual) ? $manual : ltrim($manual, '/');
                if ($newImg !== $oldImg) {
                    delete_local_image_if_any($oldImg);
                }
            } else {
                $newImg = $oldImg;
            }
        }

        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("
                UPDATE products
                SET title = ?, slug = ?, description = ?,
                    price = ?, compare_price = ?, stock = ?, 
                    main_image = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([
                $title,
                $slug,
                $desc,
                $price,
                $compare,
                $stock,
                $newImg ?: null,
                $id
            ]);

            // Reset kategori
            $pdo->prepare("DELETE FROM product_categories WHERE product_id = ?")
                ->execute([$id]);

            if ($catIds) {
                $ins = $pdo->prepare("
                    INSERT IGNORE INTO product_categories (product_id, category_id)
                    VALUES (?, ?)
                ");
                foreach ($catIds as $cid) {
                    if ($cid > 0) {
                        $ins->execute([$id, $cid]);
                    }
                }
            }

            $pdo->commit();
        } catch (Throwable $ie) {
            $pdo->rollBack();
            throw $ie;
        }

        $_SESSION['flash'] = 'Produk berhasil diperbarui.';
        header('Location: '.$BASE.'/seller/produk.php');
        exit;
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            throw new RuntimeException('Produk tidak valid.');
        }

        // Pastikan produk milik seller
        $sel = $pdo->prepare("
            SELECT p.main_image
            FROM products p
            JOIN shops s ON s.id = p.shop_id
            WHERE p.id = ? AND s.user_id = ?
            LIMIT 1
        ");
        $sel->execute([$id, $sellerId]);
        $oldImg = $sel->fetchColumn();

        if ($oldImg === false) {
            throw new RuntimeException('Produk tidak ditemukan atau bukan milik Anda.');
        }

        delete_local_image_if_any((string)$oldImg);

        $pdo->prepare("DELETE FROM products WHERE id = ?")->execute([$id]);

        $_SESSION['flash'] = 'Produk berhasil dihapus.';
        header('Location: '.$_SERVER['REQUEST_URI']);
        exit;
    }
} catch (Throwable $e) {
    $_SESSION['flash'] = 'Error: ' . $e->getMessage();
    header('Location: '.$_SERVER['REQUEST_URI']);
    exit;
}

// ===============================================================
// Data untuk tampilan: produk milik seller
// ===============================================================
$editId 	 	= isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$editItem 	 	= null;
$editItemCatIds = [];

// Produk yang bisa diedit hanya produk milik seller
if ($editId > 0) {
    $sel = $pdo->prepare("
        SELECT p.id, p.shop_id, p.title, p.description, p.price, 
               p.compare_price, p.stock, p.main_image, p.is_active, 
               p.created_at, s.name AS shop_name
        FROM products p
        JOIN shops s ON s.id = p.shop_id
        WHERE p.id = ? AND s.user_id = ?
        LIMIT 1
    ");
    $sel->execute([$editId, $sellerId]);
    $editItem = $sel->fetch(PDO::FETCH_ASSOC);

    if ($editItem) {
        $pc = $pdo->prepare("SELECT category_id FROM product_categories WHERE product_id = ?");
        $pc->execute([$editId]);
        $editItemCatIds = array_map('intval', $pc->fetchAll(PDO::FETCH_COLUMN));
    }
}

// List produk milik seller
$stmtItems = $pdo->prepare("
    SELECT 
        p.id, p.title, p.description, p.price, p.compare_price,
        p.stock, p.main_image, p.is_active, p.created_at,
        s.name AS shop_name
    FROM products p
    JOIN shops s ON s.id = p.shop_id
    WHERE s.user_id = ?
    ORDER BY p.created_at DESC
    -- LIMIT 200 DIHILANGKAN untuk menampilkan semua produk
    -- LIMIT 200
");
$stmtItems->execute([$sellerId]);
$items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

// Ambil kategori semua produk untuk chips
$ids 	= array_map(fn($r) => (int)$r['id'], $items);
$catMap = [];

if ($ids) {
    $in = implode(',', array_fill(0, count($ids), '?'));
    $q 	= $pdo->prepare("
        SELECT pc.product_id, c.name
        FROM product_categories pc
        JOIN categories c ON c.id = pc.category_id
        WHERE pc.product_id IN ($in)
        ORDER BY c.sort_order, c.name
    ");
    $q->execute($ids);

    foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $pid = (int)$row['product_id'];
        $catMap[$pid][] = $row['name'];
    }
}

// Load header NearBuy
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
.form-actions{display:flex;gap:8px;margin-top:6px;flex-wrap:wrap;align-items:center}
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
.shop-pill{display:inline-flex;align-items:center;gap:6px;background:#eff6ff;border-radius:999px;padding:2px 10px;font-size:.78rem;color:#1d4ed8;border:1px solid #bfdbfe}
</style>

<section class="admin-wrap">
    <h1>Produk Toko Saya</h1>

    <?php if (!empty($_SESSION['flash'])): ?>
        <div class="flash">
            <?= e($_SESSION['flash']); unset($_SESSION['flash']); ?>
        </div>
    <?php endif; ?>

    <?php if (!$hasShop): ?>
        <div class="edit-card">
            <h3 style="margin-top:0;">Belum ada toko</h3>
            <p class="small-note">
                Kamu belum memiliki toko di NearBuy.
                Buat toko terlebih dahulu agar bisa menambah produk dan menghubungkannya dengan lokasi.
            </p>
            <a class="btn primary" href="<?= e($BASE) ?>/seller/toko.php">Buka Toko Sekarang</a>
        </div>
    <?php else: ?>

        <div class="edit-card">
            <h3 style="margin:0 0 8px">Tambah Produk Baru</h3>
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="create">

                <div class="form-grid">
                    <select name="shop_id" required>
                        <option value="">Pilih Toko</option>
                        <?php foreach ($sellerShops as $s): ?>
                            <option value="<?= (int)$s['id'] ?>">
                                <?= e($s['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <input type="text" 	name="title" 		placeholder="Nama produk *" required>
                    <input type="number" name="price" 		placeholder="Harga (Rp) *" step="100" min="0" required>
                    <input type="number" name="compare_price" placeholder="Harga banding (opsional)" step="100" min="0">
                    <input type="number" name="stock" 		placeholder="Stok *" min="0" required>

                    <input type="file" 	name="image_file" 	accept=".jpg,.jpeg,.png,.webp">
                    <input type="text" 	name="main_image" 	placeholder="Opsional: path atau URL gambar">

                    <textarea class="full" name="description" placeholder="Deskripsi singkat produk, maksimal sekitar 250 kata."></textarea>

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
                    <span class="small-note">
                        Produk baru akan berstatus pending sampai admin NearBuy menyetujui dan mengaktifkannya.
                    </span>
                </div>
            </form>
        </div>

        <?php if ($editItem): ?>
            <div class="edit-card">
                <h3 style="margin:0 0 8px">
                    Edit Produk: <?= e($editItem['title']) ?>
                </h3>
                <p class="small-note">
                    Toko: <?= e($editItem['shop_name'] ?? 'Toko') ?>.
                    Status saat ini:
                    <?php if ((int)$editItem['is_active'] === 1): ?>
                        <span class="badge b-on">Aktif (disetujui admin)</span>
                    <?php else: ?>
                        <span class="badge b-off">Pending admin</span>
                    <?php endif; ?>
                </p>

                <form method="post" enctype="multipart/form-data">
                    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" value="<?= (int)$editItem['id'] ?>">

                    <div class="form-grid">
                        <input type="text" 	name="title" value="<?= e($editItem['title']) ?>" required>
                        <input type="number" name="price" value="<?= (float)$editItem['price'] ?>" step="100" min="0" required>
                        <input type="number" name="compare_price" value="<?= is_null($editItem['compare_price']) ? '' : (float)$editItem['compare_price'] ?>" step="100" min="0">
                        <input type="number" name="stock" value="<?= (int)$editItem['stock'] ?>" min="0" required>

                        <input type="file" 	name="image_file" accept=".jpg,.jpeg,.png,.webp">
                        <input type="text" 	name="main_image" value="<?= e($editItem['main_image']) ?>" placeholder="Kosongkan jika tidak diubah">

                        <textarea class="full" name="description"><?= e($editItem['description']) ?></textarea>

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
                        <button class="btn primary" type="submit">Simpan Perubahan</button>
                        <a class="btn" href="<?= e($BASE) ?>/seller/produk.php">Batal</a>
                        <span class="small-note">
                            Jika admin sudah menyetujui, produk akan tampil di beranda NearBuy sesuai lokasi toko.
                        </span>
                    </div>
                </form>
            </div>
        <?php endif; ?>

    <?php endif; ?>

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
            <?php if (empty($items)): ?>
                <tr>
                    <td colspan="5">Belum ada produk. Tambahkan produk pertama untuk toko kamu.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($items as $it): ?>
                    <tr>
                        <td>
                            <div style="display:flex;align-items:center;gap:10px;">
                                <?php
                                    if (!empty($it['main_image'])) {
                                        if (is_https_url($it['main_image'])) {
                                            $src = $it['main_image'];
                                        } else {
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
                                    <div class="small-note">
                                        Toko:
                                        <span class="shop-pill">
                                            <?= e($it['shop_name'] ?? 'Toko') ?>
                                        </span>
                                    </div>
                                    <div class="small-note">
                                        <?= e(mb_strimwidth(trim($it['description'] ?? ''), 0, 70, '…', 'UTF-8')) ?>
                                    </div>
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
                                <div style="text-decoration:line-through;color:#94a3b8">
                                    Rp<?= number_format((float)$it['compare_price'], 0, ',', '.') ?>
                                </div>
                            <?php endif; ?>
                            <div>Rp<?= number_format((float)$it['price'], 0, ',', '.') ?></div>
                        </td>
                        <td><?= (int)$it['stock'] ?></td>
                        <td>
                            <?php if ((int)$it['is_active'] === 1): ?>
                                <span class="badge b-on">Aktif (disetujui admin)</span>
                            <?php else: ?>
                                <span class="badge b-off">Pending admin</span>
                            <?php endif; ?>
                        </td>
                        <td class="action">
                            <a class="btn" href="<?= e($BASE) ?>/seller/produk.php?edit=<?= (int)$it['id'] ?>">Edit</a>
                            <form method="post" style="display:inline" onsubmit="return confirm('Hapus produk ini dari toko kamu?')">
                                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= (int)$it['id'] ?>">
                                <button class="btn" type="submit">Hapus</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</section>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
</main>
</body>
</html>