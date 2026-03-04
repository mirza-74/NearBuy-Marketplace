<?php
declare(strict_types=1);

/* ================= CONFIG ================= */

const DEBUG = false;

if (DEBUG) {
ini_set('display_errors','1');
error_reporting(E_ALL);
}

/* ================= INCLUDE ================= */

require_once __DIR__.'/../includes/session.php';
require_once __DIR__.'/../includes/db.php';

$BASE='/NearBuy-Marketplace/public';

/* ================= REDIRECT JIKA LOGIN ================= */

if (!empty($_SESSION['user'])) {

$role=$_SESSION['user']['role'] ?? 'pengguna';

if($role==='admin'){
header("Location: $BASE/admin/index.php");
exit;
}

if($role==='seller'){
header("Location: $BASE/seller/index.php");
exit;
}

header("Location: $BASE/index.php");
exit;

}

/* ================= STATE ================= */

$error='';

$old=[
'full_name'=>'',
'email'=>'',
'phone'=>'',
'gender'=>'',
'birth_date'=>'',
'address'=>'',
'city'=>'',
'province'=>'',
'postal_code'=>''
];

/* ================= FORM SUBMIT ================= */

if($_SERVER['REQUEST_METHOD']==='POST'){

if(function_exists('csrf_verify')){
$token=$_POST['csrf'] ?? '';
if(!csrf_verify($token)){
$error='Sesi formulir sudah habis. Silakan refresh halaman.';
}
}

foreach($old as $key=>$_){
$old[$key]=trim((string)($_POST[$key] ?? ''));
}

$password=trim((string)($_POST['password'] ?? ''));
$confirm=trim((string)($_POST['confirm'] ?? ''));

/* ===== VALIDATION ===== */

if($error===''){

if(
$old['full_name']==='' ||
$old['email']==='' ||
$password==='' ||
$confirm===''
){
$error='Nama lengkap, email, dan password wajib diisi.';
}

elseif(!filter_var($old['email'],FILTER_VALIDATE_EMAIL)){
$error='Format email tidak valid.';
}

elseif(strlen($password)<6){
$error='Password minimal 6 karakter.';
}

elseif($password!==$confirm){
$error='Konfirmasi password tidak sama.';
}

elseif($old['phone']!=='' && !preg_match('/^[0-9+ ]{8,15}$/',$old['phone'])){
$error='Nomor telepon tidak valid.';
}

elseif($old['birth_date']!==''){

$age=date_diff(date_create($old['birth_date']),date_create('today'))->y;

if($age<13){
$error='Minimal usia pengguna adalah 13 tahun.';
}

}

elseif($old['city']===''){
$error='Harap isi kota domisili agar rekomendasi produk sesuai lokasi.';
}

else{

try{

$pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);

/* cek email */

$check=$pdo->prepare("SELECT COUNT(*) FROM users WHERE email=?");
$check->execute([$old['email']]);

if($check->fetchColumn()>0){
$error='Email sudah terdaftar. Silakan login.';
}

else{

$pdo->beginTransaction();

/* hash password */

$hash=password_hash($password,PASSWORD_DEFAULT);

$birthDate=$old['birth_date']!=='' ? $old['birth_date'] : null;

$insert=$pdo->prepare("

INSERT INTO users
(
full_name,
email,
phone,
gender,
birth_date,
address,
city,
province,
postal_code,
password_hash,
role
)

VALUES (?,?,?,?,?,?,?,?,?,?,?)

");

$insert->execute([
$old['full_name'],
$old['email'],
$old['phone'] ?: null,
$old['gender'] ?: null,
$birthDate,
$old['address'] ?: null,
$old['city'],
$old['province'] ?: null,
$old['postal_code'] ?: null,
$hash,
'pengguna'
]);

$pdo->commit();

/* flash */

$_SESSION['flash']=[
'type'=>'success',
'message'=>'Akun berhasil dibuat. Silakan login.'
];

header("Location: $BASE/login.php");
exit;

}

}catch(Throwable $e){

if($pdo->inTransaction()){
$pdo->rollBack();
}

$error='Terjadi kesalahan saat menyimpan data.';

if(DEBUG){
$error.=' '.$e->getMessage();
}

}

}

}

}

/* ================= HELPER ================= */

function h(?string $s):string{
return htmlspecialchars((string)$s,ENT_QUOTES,'UTF-8');
}

?>

<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">

<title>Registrasi | NearBuy</title>

<link rel="stylesheet" href="<?= h($BASE) ?>/style-auth.css">

<style>

.two-columns{
display:flex;
gap:20px;
margin-bottom:15px;
}

.two-columns .input-box{
flex:1;
}

.two-columns input,
.two-columns select{
width:100%;
padding:10px;
border:1px solid #ccc;
border-radius:5px;
}

small{
display:block;
color:#6b7280;
margin-bottom:10px;
font-size:0.85rem;
}

</style>

</head>

<body>

<div class="container">

<div class="left-panel">

<img src="<?= h($BASE) ?>/assets/logo_nearbuy.png" class="logo">

<p class="slogan">
“Menghubungkan pelanggan dengan produk terdekat di domisili kamu.”
</p>

</div>

<div class="right-panel">

<div class="form-container">

<h2>Registrasi Akun NearBuy</h2>

<?php if($error): ?>

<div class="alert alert-error">
<?= h($error) ?>
</div>

<?php endif; ?>

<form method="POST" autocomplete="off">

<?php if(function_exists('csrf_token')): ?>
<input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
<?php endif; ?>

<label>Nama Lengkap *</label>
<input type="text" name="full_name" value="<?= h($old['full_name']) ?>" required>

<label>Email *</label>
<input type="email" name="email" value="<?= h($old['email']) ?>" required>

<label>Nomor Telepon</label>
<input type="tel" name="phone" value="<?= h($old['phone']) ?>" pattern="[0-9+ ]*">

<label>Password *</label>
<input type="password" name="password" required>

<label>Konfirmasi Password *</label>
<input type="password" name="confirm" required>

<div class="two-columns">

<div class="input-box">

<label>Jenis Kelamin</label>

<select name="gender">

<option value="">- Pilih -</option>
<option value="male" <?= $old['gender']==='male'?'selected':'' ?>>Laki-laki</option>
<option value="female" <?= $old['gender']==='female'?'selected':'' ?>>Perempuan</option>
<option value="other" <?= $old['gender']==='other'?'selected':'' ?>>Lainnya</option>

</select>

</div>

<div class="input-box">

<label>Tanggal Lahir</label>

<input type="date" name="birth_date" value="<?= h($old['birth_date']) ?>">

</div>

</div>

<label>Alamat Lengkap</label>

<textarea name="address">
<?= h($old['address']) ?>
</textarea>

<label>Kota Domisili *</label>

<small>
Digunakan untuk menampilkan rekomendasi produk di sekitar lokasi kamu
</small>

<input type="text" name="city" value="<?= h($old['city']) ?>" required>

<label>Provinsi</label>
<input type="text" name="province" value="<?= h($old['province']) ?>">

<label>Kode Pos</label>
<input type="text" name="postal_code" value="<?= h($old['postal_code']) ?>">

<button type="submit">Daftar</button>

</form>

<div class="register-text">

Sudah punya akun?

<a href="<?= h($BASE) ?>/login.php">Masuk di sini</a>

</div>

</div>
</div>
</div>

</body>
</html>