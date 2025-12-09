<?php
// ===============================================================
// NearBuy – Tentang (Versi Final: Logo dan Teks di Hero Section, CTA Fix)
// ===============================================================
declare(strict_types=1);

$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$BASE = rtrim($scriptDir, '/');

// Pastikan file-file berikut ada di path yang benar:
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/settings.php';
require_once __DIR__ . '/../includes/header.php';
?>

<style>
/* ===============================================================
 * CSS Override untuk Full Width (Menghilangkan Sidebar)
 * =============================================================== */
.content {
  width: 100% !important; 
  float: none !important;
}
.page-wrap {
  display: block !important; 
  grid-template-columns: 1fr !important;
}

/* ===============================================================
 * General Styling & Layout
 * =============================================================== */
 .about-wrapper {
  padding: 0;
  animation: fadeIn .4s ease;
  margin: 0 auto;
 }

 .section-card-container {
  max-width: 1100px; 
  margin: 0 auto;
  padding: 0 20px; 
 }
 
 .section-card {
  background: #fff;
  border-radius: 16px;
  padding: 25px;
  margin-bottom: 20px;
  box-shadow: 0 6px 20px rgba(0,0,0,0.08); 
 }

 .section-card h2 {
  font-size: 1.6rem;
  margin-bottom: 12px;
  color: #1f2937;
  border-left: 4px solid #3b82f6;
  padding-left: 12px;
  font-weight: 700;
 }

 .section-card p, .section-card ul {
  line-height: 1.7;
  color: #4b5563;
  margin-bottom: 0;
 }

 .section-card ul {
  padding-left: 20px;
 }

/* ===============================================================
 * Hero Section (Logo dan Teks di Tengah)
 * =============================================================== */
.about-hero {
  /* Latar belakang abu-abu seperti di gambar */

  padding: 80px 5vw; 
  border-radius: 0;
  color: black; /* Teks di hero berwarna putih */
  margin-bottom: 30px;
  min-height: 250px;
  position: relative;
  text-align: center;
}
 
/* Hapus overlay jika tidak dibutuhkan, biarkan warna logo utama mendominasi */
.about-hero::before {
  content: none; 
}

.hero-content-inner {
  max-width: 1100px; 
  margin: 0 auto;
  position: relative;
  z-index: 2; 
  padding: 0 20px; 
  
  /* Flexbox untuk menempatkan semua elemen di tengah */
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
}

.about-hero h1 {
  font-size: 3rem;
  margin-top: 10px; 
  margin-bottom: 10px;
  font-weight: 900;
  color: black; /* Judul berwarna putih */
  text-shadow: 1px 1px 3px rgba(245, 242, 242, 0.6);
}

.about-hero p {
  opacity: 1;
  font-size: 1.2rem;
  max-width: 600px;
  color: black; /* Deskripsi berwarna putih */
  text-shadow: 1px 1px 3px rgba(245, 243, 243, 0.6);
}

/* ===============================================================
 * Logo di Hero Section (Placeholder Logo Besar)
 * =============================================================== */
 .logo-hero {
  /* Ganti path gambar ini ke logo Anda (Pin Map dan Teks 'NearBuy' yang berwarna biru) */
  max-width: 450px; 
  height: auto;
  margin-bottom: 20px; 
  margin-top: 0; 
}

/* ===============================================================
 * CTA (Call to Action) Styling
 * =============================================================== */
 .cta-center {
  text-align: center;
  margin-top: 30px;
  margin-bottom: 40px;
 }

.btn-primary {
  background: #3b82f6;
  padding: 14px 28px;
  color: white;
  border-radius: 12px;
  text-decoration: none;
  font-size: 1.1rem;
  font-weight: 600;
  transition: .3s;
  display: inline-block;
}

.btn-primary:hover {
  background: #2563eb;
  box-shadow: 0 4px 15px rgba(59, 130, 246, 0.4);
}

.note-box {
  background: #f0f4ff;
  padding: 15px;
  border-left: 5px solid #3b82f6;
  border-radius: 10px;
  margin-top: 25px;
  color: #475569;
  font-size: 0.95rem;
}

/* ===============================================================
 * Responsiveness
 * =============================================================== */
@media (max-width: 768px) {
 .vision-mission-grid {
   grid-template-columns: 1fr;
 }
 .about-hero h1 {
   font-size: 2.5rem;
 }
 .logo-hero {
   max-width: 90%;
 }
}
</style>


<div class="page-wrap">
<div class="content">

 <div class="about-wrapper">

  <header class="about-hero">
   <div class="hero-content-inner">
            <img src="<?= e($BASE) ?>/assets/logo_nearbuy.png" alt="Logo NearBuy Lengkap" class="logo-hero">
          
     <h1>Tentang Kami</h1>
     <p>Platform belanja berbasis lokasi yang menghubungkan Anda dengan kebutuhan terdekat.</p>
   </div> 
  </header>

  <div class="section-card-container">

   <section class="section-card">
    <h2>Apa itu NearBuy?</h2>
    <p>
     NearBuy lahir dari semangat untuk **mendekatkan penjual lokal dengan konsumen di sekitarnya**. Kami adalah platform *e-commerce* yang menggunakan teknologi pintar berbasis lokasi untuk menampilkan produk dan toko yang berada paling dekat dengan posisi Anda. Ini berarti pengiriman produk menjadi **lebih cepat, lebih hemat biaya, dan lebih mendukung ekonomi lokal.**
    </p>
   </section>
   
     <div class="vision-mission-grid">
    <section class="section-card">
     <h2>✨ Visi Kami</h2>
     <p>
      **Menjadi solusi belanja harian utama yang paling efisien dan terpercaya** dengan memberdayakan setiap UMKM agar mampu bersaing secara digital di lingkungan lokal mereka.
     </p>
    </section>
    
    <section class="section-card">
     <h2>🎯 Misi Kami</h2>
     <ul>
      <li>Memberikan pengalaman belanja harian yang cepat, mudah, dan hemat biaya melalui teknologi geo-lokasi.</li>
      <li>Membantu pertumbuhan UMKM dengan memberikan akses ke pasar digital tanpa perlu bersaing dengan toko jarak jauh.</li>
      <li>Membangun komunitas lokal yang kuat dan saling mendukung antara penjual dan pembeli.</li>
     </ul>
    </section>
    </div>

   <section class="section-card">
    <h2>Keunggulan NearBuy</h2>

    <div class="feature-list">
     <div class="feature-item">
      <div class="feature-icon">📍</div>
      <div><b>Belanja Berbasis Lokasi</b><br>Toko terdekat, pengiriman super cepat, dan ongkir minimal.</div>
     </div>
     <div class="feature-item">
      <div class="feature-icon">🔍</div>
      <div><b>Temukan Toko Lokal Terbaik</b><br>Dukung UMKM di lingkungan Anda sambil memenuhi kebutuhan harian.</div>
     </div>
     <div class="feature-item">
      <div class="feature-icon">🔄</div>
      <div><b>Sistem Rekomendasi Pintar</b><br>Lihat penawaran yang benar-benar relevan dengan tempat tinggalmu.</div>
     </div>
     <div class="feature-item">
      <div class="feature-icon">🛒</div>
      <div><b>Pilihan Kebutuhan Harian Lengkap</b><br>Dari bahan pokok, makanan siap saji, hingga jasa lokal.</div>
     </div>
    </div>
   </section>

   <div class="note-box">
    💡 Catatan Penting: **NearBuy adalah platform penghubung**. Semua produk dan layanan dimiliki, dikelola, dan dikirim oleh mitra penjual lokal kami (UMKM). Kami berkomitmen pada kualitas dan transparansi.
   </div>

           <div class="cta-center">
    <a href="<?= e($BASE) ?>/index.php" class="btn-primary">Mulai Belanja Sekarang →</a>
   </div>
        
  </div>  </div> </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
</main>
</body>
</html>