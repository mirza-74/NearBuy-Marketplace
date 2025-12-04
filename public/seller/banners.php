<?php
declare(strict_types=1);

$BASE = '/Marketplace_SellExa/public';

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';

$admin = require_admin($BASE);

$action = $_POST['action'] ?? '';
if (!empty($action) && function_exists('csrf_verify') && !csrf_verify($_POST['csrf'] ?? '')) {
    $_SESSION['flash'] = 'Sesi berakhir.';
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}

/**
 * Helper upload file banner
 * return: string URL web (mis: /Marketplace_SellExa/public/uploads/banners/xxxx.jpg)
 */
function upload_banner_file(string $fieldName, string $BASE): ?string {
    if (empty($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $file     = $_FILES[$fieldName];
    $tmpPath  = $file['tmp_name'];
    $origName = $file['name'] ?? '';
    $ext      = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

    $allowed = ['jpg','jpeg','png','webp'];
    if (!in_array($ext, $allowed, true)) {
        $_SESSION['flash'] = 'Format file tidak didukung. Gunakan JPG/PNG/WebP.';
        return null;
    }

    // folder upload di public/uploads/banners
    $uploadDirFs  = __DIR__ . '/../../public/uploads/banners';
    if (!is_dir($uploadDirFs)) {
        @mkdir($uploadDirFs, 0777, true);
    }

    $newName  = 'banner_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $destPath = $uploadDirFs . '/' . $newName;

    if (!move_uploaded_file($tmpPath, $destPath)) {
        $_SESSION['flash'] = 'Gagal menyimpan file upload.';
        return null;
    }

    // URL untuk dipakai di <img src="...">
    $webPath = $BASE . '/uploads/banners/' . $newName;
    return $webPath;
}

// ================== ACTION: SET BANNER UTAMA (DEFAULT) ==================
if ($action === 'set_home') {
    $imgUrl = upload_banner_file('home_banner_file', $BASE);
    if ($imgUrl) {
        // simpan ke site_settings.key = 'home_banner'
        $stmt = $pdo->prepare("
            INSERT INTO site_settings (`key`, `value`)
            VALUES ('home_banner', :val)
            ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)
        ");
        $stmt->execute([':val' => $imgUrl]);
        $_SESSION['flash'] = 'Banner utama berhasil diperbarui.';
    }
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}

// ================== ACTION: TAMBAH BANNER IKLAN ==================
if ($action === 'create') {
    $imgUrl = upload_banner_file('image_file', $BASE);
    $url    = trim($_POST['link_url'] ?? '');

    if ($imgUrl) {
        $pdo->prepare("
            INSERT INTO banners (image_path, link_url, is_active, created_at)
            VALUES (?, ?, 1, NOW())
        ")->execute([$imgUrl, $url !== '' ? $url : null]);
        $_SESSION['flash'] = 'Banner iklan ditambahkan.';
    }
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}

// ================== ACTION: TOGGLE & DELETE BANNER IKLAN ==================
if ($action === 'toggle') {
    $id = (int)$_POST['id'];
    $pdo->prepare("UPDATE banners SET is_active = 1 - is_active WHERE id = ?")->execute([$id]);
    $_SESSION['flash'] = 'Status banner diubah.';
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}
if ($action === 'delete') {
    $id = (int)$_POST['id'];
    $pdo->prepare("DELETE FROM banners WHERE id = ?")->execute([$id]);
    $_SESSION['flash'] = 'Banner dihapus.';
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}

// ambil banner iklan
$rows = $pdo->query("
    SELECT id, image_path, link_url, is_active, created_at
    FROM banners
    ORDER BY id DESC
")->fetchAll(PDO::FETCH_ASSOC);

// ambil banner utama (default) dari site_settings
$homeBanner = null;
$stmtHome = $pdo->prepare("SELECT `value` FROM site_settings WHERE `key` = 'home_banner' LIMIT 1");
$stmtHome->execute();
$homeBanner = $stmtHome->fetchColumn();

require_once __DIR__ . '/../../includes/header.php';
?>
<style>
.admin-wrap{max-width:1000px;margin:16px auto;padding:0 16px}
.form-inline{display:flex;gap:8px;flex-wrap:wrap;margin:12px 0}
.form-inline input[type="file"]{padding:4px 0}
.form-inline input[type="url"]{padding:8px;border:1px solid #e5e8ef;border-radius:10px;min-width:260px}
.form-inline button{padding:8px 12px;border:none;border-radius:10px;background:#0f172a;color:#fff;cursor:pointer}
.table{width:100%;border-collapse:collapse;background:#fff;border:1px solid #e5e8ef;border-radius:12px;overflow:hidden;margin-top:8px}
.table th,.table td{padding:10px;border-bottom:1px solid #eef2f7;text-align:left;vertical-align:middle}
.table th{background:#f8fafc}
.badge{padding:4px 8px;border-radius:8px;font-size:.8rem}
.b-on{background:#dcfce7;color:#166534}
.b-off{background:#fee2e2;color:#991b1b}
.flash{margin:8px 0;padding:8px 12px;border-radius:8px;background:#e0f2fe;color:#082f49;font-size:.9rem}
.banner-preview{max-width:360px;max-height:160px;border-radius:10px;border:1px solid #e5e8ef;object-fit:cover}
</style>

<section class="admin-wrap">
  <h1>Kelola Banner</h1>
  <?php if (!empty($_SESSION['flash'])): ?>
    <div class="flash"><?= e($_SESSION['flash']); unset($_SESSION['flash']); ?></div>
  <?php endif; ?>

  <!-- ========== FORM 1: BANNER UTAMA (DEFAULT HOMEPAGE) ========== -->
  <h2>Banner Utama Homepage</h2>
  <p style="font-size:.9rem;color:#555;margin-bottom:6px">
    Banner ini dipakai sebagai banner utama di halaman index (user login & non-login).
  </p>

  <?php if ($homeBanner): ?>
    <div style="margin-bottom:8px">
      <img src="<?= e($homeBanner) ?>" alt="Banner utama" class="banner-preview">
    </div>
  <?php else: ?>
    <p style="font-size:.85rem;color:#777">Belum ada banner utama yang diatur.</p>
  <?php endif; ?>

  <form method="post" class="form-inline" enctype="multipart/form-data">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="action" value="set_home">

    <input id="home_banner_file" type="file" name="home_banner_file" accept=".jpg,.jpeg,.png,.webp" required>

    <button type="submit">Unggah Banner Utama</button>
  </form>

  <hr style="margin:24px 0;border:none;border-top:1px solid #e5e8ef">

  <!-- ========== FORM 2: BANNER IKLAN (BANYAK) ========== -->
  <h2>Banner Iklan</h2>
  <p style="font-size:.9rem;color:#555;margin-bottom:6px">
    Banner iklan ini bisa dipakai untuk slider/promosi di homepage atau halaman lainnya.
  </p>

  <form method="post" class="form-inline" enctype="multipart/form-data">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="action" value="create">

    <!-- Upload file banner iklan -->
    <input id="image_file" type="file" name="image_file" accept=".jpg,.jpeg,.png,.webp" required>

    <!-- opsional: link ketika banner diklik -->
    <input type="url"  name="link_url" placeholder="https://tautan-iklan-opsional.com">

    <button type="submit">+ Unggah Banner Iklan</button>
  </form>

  <table class="table">
    <thead>
      <tr>
        <th>Preview</th>
        <th>Link</th>
        <th>Status</th>
        <th>Dibuat</th>
        <th>Aksi</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($rows)): ?>
        <tr><td colspan="5">Belum ada banner iklan.</td></tr>
      <?php else: ?>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td>
              <?php if (!empty($r['image_path'])): ?>
                <img src="<?= e($r['image_path']) ?>" alt="Banner" style="max-width:180px;max-height:80px;border-radius:8px;object-fit:cover">
              <?php else: ?>
                <?= e($r['image_path']) ?>
              <?php endif; ?>
            </td>
            <td><?= e($r['link_url'] ?? '-') ?></td>
            <td>
              <span class="badge <?= $r['is_active'] ? 'b-on' : 'b-off' ?>">
                <?= $r['is_active'] ? 'Aktif' : 'Nonaktif' ?>
              </span>
            </td>
            <td><?= e($r['created_at']) ?></td>
            <td>
              <form style="display:inline" method="post">
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="toggle">
                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                <button type="submit">Aktif/Nonaktif</button>
              </form>
              <form style="display:inline" method="post" onsubmit="return confirm('Hapus banner ini?')">
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                <button type="submit">Hapus</button>
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
