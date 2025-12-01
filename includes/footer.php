<?php
// ===============================================================
// SellExa – Footer Marketplace Style
// ===============================================================
declare(strict_types=1);

if (!function_exists('e')) {
  function e(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
}
$BASE = $BASE ?? '/Marketplace_SellExa/public';
?>
<footer class="footer-market">
  <div class="footer-top">
    <div class="footer-col about">
      <img src="<?= e($BASE) ?>/assets/logo-sellexa.png" alt="SellExa" class="footer-logo">
      <p><b>SellExa Marketplace</b> adalah platform jual-beli digital yang
         menghadirkan pengalaman belanja modern, aman, dan praktis bagi setiap pengguna.</p>
    </div>

    <div class="footer-col">
      <h4>Kategori Populer</h4>
      <ul>
        <li><a href="<?= e($BASE) ?>/kategori.php?k=elektronik">Elektronik</a></li>
        <li><a href="<?= e($BASE) ?>/kategori.php?k=fashion">Fashion</a></li>
        <li><a href="<?= e($BASE) ?>/kategori.php?k=kesehatan">Kesehatan & Kecantikan</a></li>
        <li><a href="<?= e($BASE) ?>/kategori.php?k=rumah">Peralatan Rumah Tangga</a></li>
      </ul>
    </div>

    <div class="footer-col">
      <h4>Bantuan & Layanan</h4>
      <ul>
        <li><a href="<?= e($BASE) ?>/bantuan.php">Pusat Bantuan</a></li>
        <li><a href="<?= e($BASE) ?>/cara-belanja.php">Cara Belanja</a></li>
        <li><a href="<?= e($BASE) ?>/pembayaran.php">Metode Pembayaran</a></li>
        <li><a href="<?= e($BASE) ?>/pengiriman.php">Pengiriman & Retur</a></li>
        <li><a href="<?= e($BASE) ?>/kebijakan.php">Kebijakan Privasi</a></li>
      </ul>
    </div>

    <div class="footer-col">
      <h4>Hubungi Kami</h4>
      <ul>
        <li>Email: <a href="mailto:support@sellexa.com">support@sellexa.com</a></li>
        <li>Telepon: +62 812-3456-7890</li>
        <li>Alamat: Bangka Belitung, Indonesia</li>
      </ul>
      <div class="social-links">
        <a href="#"><img src="<?= e($BASE) ?>/assets/icon-fb.svg" alt="Facebook"></a>
        <a href="#"><img src="<?= e($BASE) ?>/assets/icon-ig.svg" alt="Instagram"></a>
        <a href="#"><img src="<?= e($BASE) ?>/assets/icon-x.svg" alt="Twitter X"></a>
      </div>
    </div>
  </div>

  <div class="footer-bottom">
    <p>© <?= date('Y') ?> SellExa Marketplace • All Rights Reserved.</p>
  </div>
</footer>
