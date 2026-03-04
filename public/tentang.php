<?php
declare(strict_types=1);

$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$BASE = rtrim($scriptDir, '/');

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/settings.php';
require_once __DIR__ . '/../includes/header.php';
?>

<style>

/* =========================
LAYOUT FULL WIDTH
========================= */

.page-wrap{
display:block !important;
}

.content{
width:100% !important;
}

/* =========================
HERO
========================= */

.about-hero{
text-align:center;
padding:80px 20px;
background:linear-gradient(145deg,#eaf3ff,#f6f9ff);
border-radius:16px;
margin-bottom:30px;
}

.logo-hero{
max-width:300px;
margin-bottom:15px;
}

.about-hero h1{
font-size:2.5rem;
margin-bottom:10px;
color:#111827;
}

.about-hero p{
font-size:1.1rem;
color:#4b5563;
max-width:600px;
margin:auto;
}

/* =========================
SECTION
========================= */

.about-container{
max-width:1000px;
margin:auto;
padding:0 20px;
}

.section-card{
background:#fff;
padding:25px;
border-radius:14px;
margin-bottom:20px;
box-shadow:0 4px 15px rgba(0,0,0,0.08);
}

.section-card h2{
font-size:1.4rem;
margin-bottom:10px;
color:#111827;
border-left:4px solid #3b82f6;
padding-left:10px;
}

.section-card p{
line-height:1.7;
color:#4b5563;
}

.section-card ul{
padding-left:20px;
color:#4b5563;
line-height:1.7;
}

/* =========================
FITUR
========================= */

.feature-grid{
display:grid;
grid-template-columns:repeat(auto-fit,minmax(200px,1fr));
gap:15px;
margin-top:15px;
}

.feature-item{
background:#f8fafc;
padding:15px;
border-radius:12px;
font-size:0.95rem;
}

/* =========================
NOTE BOX
========================= */

.note-box{
background:#eef4ff;
padding:15px;
border-left:5px solid #3b82f6;
border-radius:10px;
margin-top:15px;
font-size:0.95rem;
color:#334155;
}

/* =========================
CTA
========================= */

.cta-center{
text-align:center;
margin-top:30px;
}

.btn-primary{
background:#3b82f6;
color:white;
padding:12px 24px;
border-radius:10px;
text-decoration:none;
font-weight:600;
}

.btn-primary:hover{
background:#2563eb;
}

</style>


<div class="page-wrap">
<div class="content">

<header class="about-hero">

<img src="<?= e($BASE) ?>/assets/logo_nearbuy.png" class="logo-hero">

<h1>Tentang NearBuy</h1>

<p>
Platform marketplace berbasis lokasi yang membantu pengguna
menemukan kebutuhan harian dari penjual lokal di sekitar mereka.
</p>

</header>


<div class="about-container">


<section class="section-card">

<h2>Apa itu NearBuy?</h2>

<p>
NearBuy adalah platform marketplace yang menghubungkan pembeli dengan 
penjual lokal berdasarkan lokasi pengguna. Dengan memanfaatkan teknologi 
lokasi perangkat, NearBuy dapat menampilkan produk dari toko yang berada 
di sekitar pengguna sehingga kebutuhan harian dapat ditemukan dengan 
lebih cepat dan efisien.
</p>

<p>
NearBuy berperan sebagai perantara yang mempertemukan pembeli dan penjual. 
Semua produk dijual langsung oleh penjual lokal, sedangkan proses 
pembayaran dilakukan langsung kepada penjual melalui QRIS atau metode 
COD (Cash on Delivery).
</p>

</section>


<section class="section-card">

<h2>Cara Kerja NearBuy</h2>

<ul>

<li>Pengguna mengaktifkan lokasi perangkat.</li>

<li>Sistem menampilkan produk dari penjual yang berada di sekitar lokasi pengguna.</li>

<li>Pembeli memilih produk dan melakukan pemesanan.</li>

<li>Pembayaran dilakukan langsung kepada penjual melalui QRIS atau COD.</li>

<li>Pembeli mengonfirmasi penerimaan barang melalui sistem.</li>

</ul>

</section>


<section class="section-card">

<h2>Visi</h2>

<p>
Menjadi platform marketplace lokal yang membantu masyarakat menemukan 
kebutuhan harian secara lebih cepat serta mendukung pertumbuhan penjual 
lokal dan UMKM di lingkungan sekitar.
</p>

</section>


<section class="section-card">

<h2>Misi</h2>

<ul>

<li>Menyediakan platform pencarian produk berbasis lokasi untuk kebutuhan harian.</li>

<li>Menghubungkan pembeli dengan penjual lokal secara lebih efisien.</li>

<li>Mendukung digitalisasi UMKM agar lebih mudah menjangkau konsumen sekitar.</li>

<li>Memberikan pengalaman belanja yang sederhana dan mudah digunakan.</li>

</ul>

</section>


<section class="section-card">

<h2>Keunggulan NearBuy</h2>

<div class="feature-grid">

<div class="feature-item">
📍 <b>Pencarian Berdasarkan Lokasi</b><br>
Menampilkan produk dari penjual yang berada di sekitar pengguna.
</div>

<div class="feature-item">
🏪 <b>Mendukung Penjual Lokal</b><br>
Membantu UMKM menjangkau konsumen di area sekitar.
</div>

<div class="feature-item">
💳 <b>Pembayaran Fleksibel</b><br>
Pembayaran dapat dilakukan melalui QRIS atau COD langsung ke penjual.
</div>

<div class="feature-item">
📊 <b>Laporan Produk Terlaris</b><br>
Penjual dapat melihat produk yang paling banyak terjual.
</div>

</div>

</section>


<div class="note-box">

NearBuy adalah platform perantara yang mempertemukan pembeli dan penjual.
Seluruh produk dan layanan disediakan oleh penjual lokal.
NearBuy tidak terlibat langsung dalam proses pengiriman maupun transaksi
pembayaran antara pembeli dan penjual.

</div>


<div class="cta-center">

<a href="<?= e($BASE) ?>/index.php" class="btn-primary">
Mulai Belanja Sekarang
</a>

</div>


</div>
</div>
</div>


<?php require_once __DIR__ . '/../includes/footer.php'; ?>

</main>
</body>
</html>