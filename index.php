<?php
require __DIR__ . '/inc/lib.php';
if ($u = me()) redirect($u['role'] === 'petugas' ? 'setor.php' : 'beranda.php');
$kontak = require __DIR__ . '/inc/kontak.php'; // data contact person: ubah di inc/kontak.php
function li(string $t): string { return '<li>' . ic('check', 18) . '<span>' . $t . '</span></li>'; }
function st(int $n, string $t, string $d): string { return '<div><div class="lp-n">' . $n . '</div><b>' . $t . '</b><p>' . $d . '</p></div>'; }
?><!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>Bank Sampah Digital</title>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@600;800&family=Public+Sans:wght@400;500;600&display=swap">
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="lp">
<header class="lp-nav"><div class="lp-w lp-nr">
  <div class="lp-brand"><?= logo(34) ?><span>Bank Sampah Digital</span></div>
  <div class="lp-links"><a href="#beranda">Beranda</a><a href="#fitur">Fitur</a><a href="#cara">Cara Kerja</a><a href="#kontak">Kontak</a></div>
  <a class="btn alt sm pill" href="login.php"><?= ic('login', 18) ?>Masuk</a>
  <a class="btn sm pill" href="register.php"><?= ic('userplus', 18) ?>Daftar Sekarang</a>
</div></header>

<section class="lp-hero" id="beranda"><div class="lp-w lp-hg">
  <div>
    <span class="lp-tag">Platform bank sampah modern</span>
    <h1>Ubah sampah jadi <span>tabungan</span></h1>
    <p>Bank Sampah Digital menghubungkan warga dan petugas. Setor sampah, pantau saldo, dan ikuti jadwal pengumpulan di kelurahanmu, semuanya transparan.</p>
    <div class="lp-cta"><a class="btn pill" href="register.php">Mulai setor sampah</a><a class="btn alt pill" href="#fitur">Pelajari fitur</a></div>
  </div>
  <div class="lp-ill">
    <svg viewBox="0 0 320 240" role="img" aria-label="Ilustrasi tong daur ulang dan koin tabungan" style="width:100%;height:auto;display:block"><circle cx="160" cy="120" r="104" fill="#dcf5e5"/><rect x="102" y="70" width="116" height="14" rx="7" fill="#14532d"/><rect x="144" y="58" width="32" height="10" rx="5" fill="#14532d"/><path d="M112 90h96l-8 110a10 10 0 0 1-10 9h-60a10 10 0 0 1-10-9z" fill="#16a34a"/><path d="M160 196c-24-6-32-28-27-48 20 0 37 11 39 30 3 9 0 15-12 18zM160 196v-34" fill="none" stroke="#fff" stroke-width="6" stroke-linecap="round" stroke-linejoin="round"/><circle cx="238" cy="72" r="18" fill="#4ade80"/><circle cx="238" cy="72" r="11" fill="none" stroke="#16a34a" stroke-width="3"/><circle cx="84" cy="152" r="14" fill="#4ade80"/><circle cx="84" cy="152" r="8" fill="none" stroke="#16a34a" stroke-width="3"/><circle cx="252" cy="172" r="9" fill="#bbf7d0"/><circle cx="76" cy="84" r="9" fill="#bbf7d0"/></svg>
  </div>
</div></section>

<section class="lp-sec" id="fitur"><div class="lp-w">
  <h2>Satu platform, dua solusi</h2>
  <p class="lp-sub">Peran dibagi jelas antara petugas dan masyarakat agar pengelolaan bank sampah lebih rapi.</p>
  <div class="lp-two">
    <div class="lp-card"><div class="lp-ic"><?= ic('users') ?></div><h3>Petugas bank sampah</h3>
      <p class="mut">Dashboard untuk mengelola operasional bank sampah di kelurahan.</p>
      <ul><?= li('Catat setoran dengan ID anggota atau scan QR') . li('Kelola jenis sampah dan harga per kilogram') . li('Lihat data anggota dan saldo tabungan') . li('Atur jadwal pengumpulan untuk satu kelurahan') ?></ul></div>
    <div class="lp-card g"><div class="lp-ic"><?= ic('user') ?></div><h3>Masyarakat (warga)</h3>
      <p class="mut">Aplikasi sederhana bagi warga yang ingin ikut menjaga lingkungan.</p>
      <ul><?= li('Daftar akun dan dapatkan QR anggota') . li('Pantau saldo dan riwayat setoran') . li('Lihat harga sampah per kilogram') . li('Terima notifikasi jadwal di kelurahan sendiri') ?></ul></div>
  </div>
</div></section>

<section class="lp-dark" id="cara"><div class="lp-w">
  <h2 style="text-align:center">Semudah 1-2-3-4</h2>
  <p class="lp-sub" style="color:#b6d4c1;margin-bottom:0">Dari rumah sampai jadi saldo tabungan.</p>
  <div class="lp-steps">
    <?= st(1, 'Daftar', 'Buat akun warga dengan alamat sampai kelurahan.') . st(2, 'Setor', 'Bawa sampah ke petugas dan tunjukkan QR anggota.') . st(3, 'Timbang', 'Petugas menimbang dan mencatat jenis serta beratnya.') . st(4, 'Terima saldo', 'Nilai setoran langsung masuk ke tabungan.') ?>
  </div>
</div></section>

<section class="lp-sec" id="kontak"><div class="lp-w">
<?php if ($kontak): ?>
  <h2>Hubungi kami</h2>
  <p class="lp-sub">Ada pertanyaan soal setoran, harga sampah, atau jadwal pengumpulan? Hubungi contact person kami.</p>
  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:18px;margin-bottom:28px">
  <?php foreach ($kontak as $k):
      $wa = preg_replace('/\D+/', '', (string)($k['telp'] ?? ''));
      if (str_starts_with($wa, '0')) $wa = '62' . substr($wa, 1);
      $pesan = rawurlencode('Halo, saya ingin bertanya tentang Bank Sampah Digital.'); ?>
    <div class="lp-card"><div class="lp-ic"><?= ic('user') ?></div><h3><?= e($k['nama'] ?? '') ?></h3>
      <?php if (!empty($k['peran'])): ?><p class="mut"><?= e($k['peran']) ?></p><?php endif; ?>
      <?php if ($wa !== ''): ?><p><?= e($k['telp']) ?></p><?php endif; ?>
      <?php if (!empty($k['email'])): ?><p><a class="link" href="mailto:<?= e($k['email']) ?>"><?= e($k['email']) ?></a></p><?php endif; ?>
      <?php if (!empty($k['jam'])): ?><p class="mut">Jam layanan: <?= e($k['jam']) ?></p><?php endif; ?>
      <?php if ($wa !== ''): ?>
      <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:12px">
        <a class="btn sm pill" style="text-decoration:none" href="https://wa.me/<?= $wa ?>?text=<?= $pesan ?>" target="_blank" rel="noopener">WhatsApp</a>
        <a class="btn alt sm pill" style="text-decoration:none" href="tel:+<?= $wa ?>">Telepon</a>
      </div>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>
  </div>
<?php endif; ?>
  <div class="lp-banner">
    <h2>Mari wujudkan lingkungan asri dan bernilai ekonomi</h2>
    <p>Bergabunglah dan mulai ubah sampah rumah tangga menjadi tabungan.</p>
    <a class="btn pill" href="register.php">Daftar sebagai warga</a> <a class="btn alt pill" href="login.php">Masuk</a>
  </div>
</div></section>
<footer class="lp-foot">Bank Sampah Digital</footer>
</div>

<script>
  // Menghapus class preload setelah halaman selesai dimuat sepenuhnya
  window.addEventListener("load", function () {
    document.body.classList.remove("preload");
  });
</script>
</body>
</html>