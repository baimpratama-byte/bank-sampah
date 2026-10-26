<?php
function auth_top(string $mode, string $title): void { ?><!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= e($title) ?> | Bank Sampah Digital</title>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@600;800&family=Public+Sans:wght@400;500;600&display=swap">
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="authp">
  <section class="hero">
    <?= logo(56) ?>
    <h1>Bank Sampah Digital</h1>
    <p>Setor sampah, pantau saldo, dan lihat jadwal pengumpulan dari satu tempat.</p>
    <ul>
      <li><?= ic('leaf') ?>Sampah ditimbang dan dicatat petugas</li>
      <li><?= ic('tag') ?>Harga per kilogram terbuka untuk semua</li>
      <li><?= ic('history') ?>Riwayat setoran selalu tersimpan</li>
    </ul>
  </section>
  <section class="formp">
    <a class="link" href="index.php" style="align-self:flex-start;margin-bottom:12px">&larr; Kembali ke beranda</a>
    <div class="seg">
      <a class="<?= $mode === 'login' ? 'on' : '' ?>" href="login.php">Masuk</a>
      <a class="<?= $mode === 'reg' ? 'on' : '' ?>" href="register.php">Daftar</a>
    </div>
<?php }
function auth_bottom(): void { ?>
  </section>
</div>
<script src="assets/app.js"></script>
</body>
</html>
<?php }
