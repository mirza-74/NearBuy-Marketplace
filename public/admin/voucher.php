<?php
declare(strict_types=1);

$BASE = '/Marketplace_SellExa/public';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';

$admin = require_admin($BASE);

// ----------------- HANDLE ACTION + CSRF -----------------
$action = $_POST['action'] ?? '';
if (!empty($action) && function_exists('csrf_verify') && !csrf_verify($_POST['csrf'] ?? '')) {
    $_SESSION['flash'] = 'Sesi berakhir. Silakan coba lagi.';
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}

// ----------------- CREATE VOUCHER -----------------
if ($action === 'create') {
    $code  = strtoupper(preg_replace('~\s+~', '', $_POST['code'] ?? ''));
    $title = trim($_POST['title'] ?? '');
    $desc  = trim($_POST['description'] ?? '');
    $minp  = (int)($_POST['min_points'] ?? 0);

    // tipe diskon: percent / nominal / free_shipping
    $discountType = $_POST['discount_type'] ?? 'percent';
    $allowedTypes = ['percent','nominal','free_shipping'];
    if (!in_array($discountType, $allowedTypes, true)) {
        $discountType = 'percent';
    }

    $discountValue   = (int)($_POST['discount_value'] ?? 0);
    $minTransaction  = (int)($_POST['min_transaction'] ?? 0);
    $maxDiscount     = (int)($_POST['max_discount'] ?? 0);

    if ($code && $title) {
        $stmt = $pdo->prepare("
            INSERT INTO vouchers (
                code, title, description,
                min_points, is_active,
                discount_type, discount_value,
                min_transaction, max_discount,
                created_at
            ) VALUES (
                ?, ?, ?,
                ?, 1,
                ?, ?,
                ?, ?,
                NOW()
            )
        ");
        $stmt->execute([
            $code,
            $title,
            $desc !== '' ? $desc : null,
            $minp,
            $discountType,
            $discountValue,
            $minTransaction,
            $maxDiscount
        ]);
        $_SESSION['flash'] = 'Voucher ditambahkan.';
    }
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}

// ----------------- TOGGLE & DELETE -----------------
if ($action === 'toggle') {
    $id = (int)$_POST['id'];
    $pdo->prepare("UPDATE vouchers SET is_active = 1 - is_active WHERE id = ?")->execute([$id]);
    $_SESSION['flash'] = 'Status voucher diubah.';
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}

if ($action === 'delete') {
    $id = (int)$_POST['id'];
    $pdo->prepare("DELETE FROM vouchers WHERE id = ?")->execute([$id]);
    $_SESSION['flash'] = 'Voucher dihapus.';
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}

// ----------------- LOAD DATA UNTUK TAMPILAN -----------------
$vouchers = $pdo->query("
    SELECT *
    FROM vouchers
    ORDER BY created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

$claims = $pdo->query("
  SELECT vc.id, u.email, v.code, v.title, vc.claimed_at
  FROM voucher_claims vc
  JOIN users u ON u.id = vc.user_id
  JOIN vouchers v ON v.id = vc.voucher_id
  ORDER BY vc.claimed_at DESC
  LIMIT 100
")->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/../../includes/header.php';
?>
<style>
.admin-wrap{max-width:1150px;margin:16px auto;padding:0 16px}
.grid{display:grid;grid-template-columns:1fr;gap:16px}
@media(min-width:1024px){.grid{grid-template-columns:1.1fr .9fr}}
.card{background:#fff;border:1px solid #e5e8ef;border-radius:14px;padding:16px}
.card h3{margin-top:0;margin-bottom:10px}
.table{width:100%;border-collapse:collapse;font-size:0.9rem}
.table th,.table td{padding:8px;border-bottom:1px solid #eef2f7;text-align:left;vertical-align:top}
.table th{background:#f8fafc}
.form-row{display:flex;flex-wrap:wrap;gap:8px;margin:8px 0}
.form-row input,.form-row select{padding:8px;border:1px solid #e5e8ef;border-radius:10px;font-size:.9rem}
.form-row input[type="text"]{min-width:160px}
.form-row input[type="number"]{width:110px}
.form-row button{padding:8px 12px;border:none;border-radius:10px;background:#0f172a;color:#fff;font-size:.9rem;cursor:pointer}
.form-row button:hover{opacity:.9}
.badge{padding:4px 8px;border-radius:999px;font-size:.75rem;white-space:nowrap}
.b-on{background:#dcfce7;color:#166534}
.b-off{background:#fee2e2;color:#991b1b}
.type-pill{padding:2px 6px;border-radius:999px;font-size:.7rem;border:1px solid #e5e7eb;background:#f9fafb}
.flash{background:#ecfeff;color:#164e63;padding:8px 10px;border-radius:10px;margin:10px 0;border:1px solid #a5f3fc}
.small-muted{font-size:.78rem;color:#6b7280}
</style>

<section class="admin-wrap">
  <h1>Kelola Voucher</h1>
  <?php if (!empty($_SESSION['flash'])): ?>
    <div class="flash"><?= e($_SESSION['flash']); unset($_SESSION['flash']); ?></div>
  <?php endif; ?>

  <div class="grid">
    <!-- ================== FORM & DAFTAR VOUCHER ================== -->
    <div class="card">
      <h3>Buat Voucher Baru</h3>
      <p class="small-muted">
        Atur tipe voucher:<br>
        <b>Percent</b> = potongan persentase (mis. 10%) •
        <b>Nominal</b> = potongan rupiah (mis. 20.000) •
        <b>Free shipping</b> = ongkir jadi 0.
      </p>
      <form method="post" class="form-row">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="create">

        <input type="text" name="code" placeholder="KODE (mis. HEMAT10)" required>
        <input type="text" name="title" placeholder="Judul Voucher" required>
        <input type="text" name="description" placeholder="Deskripsi singkat">

        <select name="discount_type">
          <option value="percent">Percent (%)</option>
          <option value="nominal">Nominal (Rp)</option>
          <option value="free_shipping">Free Shipping</option>
        </select>

        <input type="number" name="discount_value" placeholder="Nilai diskon" min="0" value="0">
        <input type="number" name="max_discount" placeholder="Max diskon (Rp, opsional)" min="0" value="0">
        <input type="number" name="min_transaction" placeholder="Min. transaksi (Rp)" min="0" value="0">
        <input type="number" name="min_points" placeholder="Min. poin klaim" min="0" value="0">

        <button type="submit">Simpan Voucher</button>
      </form>

      <h3 style="margin-top:18px;">Daftar Voucher</h3>
      <table class="table">
        <thead>
          <tr>
            <th>Kode</th>
            <th>Detail</th>
            <th>Syarat</th>
            <th>Status</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($vouchers as $v): ?>
          <?php
            $typeLabel = 'Percent';
            if ($v['discount_type'] === 'nominal') $typeLabel = 'Nominal';
            elseif ($v['discount_type'] === 'free_shipping') $typeLabel = 'Free Shipping';

            $discText = '';
            if ($v['discount_type'] === 'percent') {
              $discText = (int)$v['discount_value'] . '%';
              if ((int)$v['max_discount'] > 0) {
                $discText .= ' (maks Rp' . number_format((int)$v['max_discount'], 0, ',', '.') . ')';
              }
            } elseif ($v['discount_type'] === 'nominal') {
              $discText = 'Rp' . number_format((int)$v['discount_value'], 0, ',', '.');
            } else {
              $discText = 'Gratis Ongkir';
            }

            $syaratParts = [];
            if ((int)$v['min_transaction'] > 0) {
              $syaratParts[] = 'Min. belanja Rp' . number_format((int)$v['min_transaction'], 0, ',', '.');
            }
            if ((int)$v['min_points'] > 0) {
              $syaratParts[] = 'Min. ' . (int)$v['min_points'] . ' poin';
            }
            $syaratText = $syaratParts ? implode(' • ', $syaratParts) : '-';
          ?>
          <tr>
            <td><b><?= e($v['code']) ?></b></td>
            <td>
              <div><?= e($v['title']) ?></div>
              <div class="small-muted">
                <?= $discText ?> |
                <span class="type-pill"><?= e($typeLabel) ?></span>
              </div>
              <?php if (!empty($v['description'])): ?>
                <div class="small-muted"><?= e($v['description']) ?></div>
              <?php endif; ?>
            </td>
            <td class="small-muted"><?= $syaratText ?></td>
            <td>
              <span class="badge <?= $v['is_active'] ? 'b-on' : 'b-off' ?>">
                <?= $v['is_active'] ? 'Aktif' : 'Nonaktif' ?>
              </span>
            </td>
            <td>
              <form style="display:inline" method="post">
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="toggle">
                <input type="hidden" name="id" value="<?= (int)$v['id'] ?>">
                <button type="submit">Aktif/Nonaktif</button>
              </form>
              <form style="display:inline" method="post" onsubmit="return confirm('Hapus voucher ini?')">
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= (int)$v['id'] ?>">
                <button type="submit">Hapus</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- ================== RIWAYAT KLAIM ================== -->
    <div class="card">
      <h3>Riwayat Klaim Voucher</h3>
      <table class="table">
        <thead>
          <tr>
            <th>Waktu</th>
            <th>User</th>
            <th>Kode</th>
            <th>Voucher</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($claims as $c): ?>
          <tr>
            <td><?= e($c['claimed_at']) ?></td>
            <td><?= e($c['email']) ?></td>
            <td><b><?= e($c['code']) ?></b></td>
            <td><?= e($c['title']) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
