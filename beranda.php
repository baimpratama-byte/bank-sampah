<?php
require __DIR__ . '/inc/lib.php';
$u = need('masyarakat');
$id = (int)$u['id_user'];

$s = $pdo->prepare('SELECT COUNT(*) FROM setoran WHERE id_masyarakat=?');
$s->execute([$id]);
$jml = (int)$s->fetchColumn();
$s = $pdo->prepare('SELECT COALESCE(SUM(d.berat_kg),0) FROM detail_setoran d JOIN setoran s ON s.id_setoran=d.id_setoran WHERE s.id_masyarakat=?');
$s->execute([$id]);
$kg = (float)$s->fetchColumn();
$s = $pdo->prepare('SELECT * FROM jadwal_pengumpulan WHERE provinsi=? AND kabupaten_kota=? AND kelurahan=? AND tanggal>=CURDATE() ORDER BY tanggal, jam_mulai LIMIT 1');
$s->execute([$u['provinsi'], $u['kabupaten_kota'], $u['kelurahan']]);
$nj = $s->fetch();
$ads = $pdo->query('SELECT * FROM iklan WHERE aktif=1 ORDER BY urutan, id_iklan')->fetchAll();
$bg = ['linear-gradient(135deg,#14532d,#22c55e)', 'linear-gradient(135deg,#166534,#4ade80)', 'linear-gradient(135deg,#065f46,#10b981)'];

/* Contact person Bank Sampah. Ubah sesuai kebutuhan.
   Tambah blok [ ... ] lain untuk kontak tambahan, atau kosongkan array ini ($kontak = [];)
   untuk menyembunyikan kartu kontak. */
$kontak = [
    [
        'nama'  => 'Bank Sampah Grup',      // ganti dengan nama sebenarnya
        'peran' => 'Pengelola Bank Sampah',
        'telp'  => '0815-2575-7088',           // untuk tombol WhatsApp dan Telepon (0812... atau +62812...)
        'email' => 'info@banksampah.id',                         // isi bila ada, mis. 'info@banksampah.id'
        'jam'   => 'Senin-Jumat, 08.00-16.00 WIB',
    ],
];

layout_top('Beranda', 'beranda', $u);
?>
<?php if ($ads): ?>
<div class="ads" id="ads" role="region" aria-roledescription="carousel" aria-label="Pengumuman">
  <div class="ads-t">
  <?php foreach ($ads as $i => $a):
      $img = $a['gambar'] ? "url('uploads/iklan/" . rawurlencode($a['gambar']) . "') center/cover" : $bg[$i % 3]; ?>
    <div class="ad" style="background:linear-gradient(0deg,rgba(0,0,0,.55),rgba(0,0,0,0) 65%),<?= e($img) ?>">
      <h3><?= e($a['judul']) ?></h3><p><?= e($a['teks']) ?></p>
    </div>
  <?php endforeach; ?>
  </div>
  <button class="ads-b p" data-d="-1" aria-label="Iklan sebelumnya"><?= ic('back') ?></button>
  <button class="ads-b n" data-d="1" aria-label="Iklan berikutnya"><?= ic('back') ?></button>
  <div class="dots"><?php foreach ($ads as $i => $a): ?><button class="dot<?= $i === 0 ? ' on' : '' ?>" aria-label="Iklan <?= $i + 1 ?>"></button><?php endforeach; ?></div>
</div>
<?php endif; ?>

<div class="grid">
  <div class="card saldo"><small>Saldo tabungan</small><h2><?= rp($u['saldo']) ?></h2></div>
  <div class="card"><small class="mut">Total setoran</small><h2><?= $jml ?> kali, <?= rtrim(rtrim(number_format($kg, 2, ',', '.'), '0'), ',') ?: '0' ?> kg</h2></div>
  <div class="card"><small class="mut">ID anggota</small><h2><?= kode($id) ?></h2><a class="link" href="profil.php">Tampilkan QR</a></div>
</div>
<div class="dash2">
  <div class="card"><h3>Jadwal pengumpulan berikutnya</h3>
    <?php if ($nj): ?>
      <b><?= e(tgl($nj['tanggal'])) ?></b>, <?= e(substr($nj['jam_mulai'], 0, 5)) ?> sampai <?= e(substr($nj['jam_selesai'], 0, 5)) ?><br><?= e($nj['lokasi']) ?>
      <?php if ($nj['keterangan']): ?><br><span class="mut"><?= e($nj['keterangan']) ?></span><?php endif; ?>
    <?php else: ?><div class="empty">Belum ada jadwal di kelurahanmu. Cek lagi nanti.</div><?php endif; ?>
  </div>
  <div class="card"><h3>Profil</h3>
    <?= e($u['nama_lengkap']) ?><br>
    <span class="mut">Lahir di <?= e($u['tempat_lahir']) ?>, <?= e(tgl($u['tanggal_lahir'])) ?><br>
    <?= e($u['detail_alamat']) ?>, <?= e($u['kelurahan']) ?>, <?= e($u['kabupaten_kota']) ?>, <?= e($u['provinsi']) ?> <?= e($u['kode_pos']) ?></span></div>
</div>

<?php if ($kontak): ?>
<div class="card" style="margin-top:14px"><h3>Contact person</h3>
  <?php foreach ($kontak as $k):
      $wa = preg_replace('/\D+/', '', (string)($k['telp'] ?? ''));
      if (str_starts_with($wa, '0')) $wa = '62' . substr($wa, 1);
      $pesan = rawurlencode('Halo, saya ' . $u['nama_lengkap'] . ' (' . kode($id) . '), ingin bertanya tentang Bank Sampah.'); ?>
  <div style="display:flex;gap:14px;flex-wrap:wrap;align-items:center;justify-content:space-between;padding:8px 0">
    <div>
      <b><?= e($k['nama'] ?? '') ?></b><?php if (!empty($k['peran'])): ?> <span class="mut">(<?= e($k['peran']) ?>)</span><?php endif; ?><br>
      <?php if ($wa !== ''): ?><span class="mut"><?= e($k['telp']) ?></span><br><?php endif; ?>
      <?php if (!empty($k['email'])): ?><a class="link" href="mailto:<?= e($k['email']) ?>"><?= e($k['email']) ?></a><br><?php endif; ?>
      <?php if (!empty($k['jam'])): ?><span class="mut">Jam layanan: <?= e($k['jam']) ?></span><?php endif; ?>
    </div>
    <?php if ($wa !== ''): ?>
    <div style="display:flex;gap:10px;flex-wrap:wrap">
      <a class="btn sm" style="text-decoration:none" href="https://wa.me/<?= $wa ?>?text=<?= $pesan ?>" target="_blank" rel="noopener">WhatsApp</a>
      <a class="btn alt sm" style="text-decoration:none" href="tel:+<?= $wa ?>">Telepon</a>
    </div>
    <?php endif; ?>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>
<?php layout_bottom();