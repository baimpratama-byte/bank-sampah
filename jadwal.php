<?php
require __DIR__ . '/inc/lib.php';
$u = need();
$isP = $u['role'] === 'petugas';
$wil = [$u['provinsi'], $u['kabupaten_kota'], $u['kelurahan']];

if ($isP && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $a = (string)($_POST['aksi'] ?? '');
    if ($a === 'hapus') {
        $pdo->prepare('DELETE FROM jadwal_pengumpulan WHERE id_jadwal=? AND provinsi=? AND kabupaten_kota=? AND kelurahan=?')
            ->execute([(int)($_POST['id'] ?? 0), ...$wil]);
        flash('Jadwal dihapus.');
    } elseif ($a === 'tambah') {
        $t = (string)($_POST['tanggal'] ?? ''); $m = (string)($_POST['mulai'] ?? ''); $s = (string)($_POST['selesai'] ?? '');
        $lok = trim((string)($_POST['lokasi'] ?? '')); $ket = trim((string)($_POST['ket'] ?? ''));
        $d = DateTime::createFromFormat('Y-m-d', $t);
        if (!$d || $d->format('Y-m-d') !== $t || $lok === '' || mb_strlen($lok) > 150 || !preg_match('/^\d{2}:\d{2}$/', $m) || !preg_match('/^\d{2}:\d{2}$/', $s) || $s <= $m) {
            flash('Isi tanggal, jam (selesai setelah mulai), dan lokasi dengan benar.', 'err');
        } else {
            try {
                $pdo->beginTransaction();
                $pdo->prepare('INSERT INTO jadwal_pengumpulan (tanggal,jam_mulai,jam_selesai,lokasi,keterangan,id_petugas,provinsi,kabupaten_kota,kelurahan) VALUES (?,?,?,?,?,?,?,?,?)')
                    ->execute([$t, $m, $s, $lok, mb_substr($ket, 0, 255) ?: null, $u['id_user'], ...$wil]);
                $jid = (int)$pdo->lastInsertId();
                $pesan = 'Jadwal pengumpulan baru: ' . tgl($t) . ', ' . $m . ' sampai ' . $s . ' di ' . $lok;
                $ins = $pdo->prepare("INSERT INTO notifikasi (id_user, id_jadwal, pesan) SELECT id_user, $jid, CONVERT(? USING utf8mb4) FROM users WHERE role='masyarakat' AND provinsi=? AND kabupaten_kota=? AND kelurahan=?");
                $ins->execute([mb_substr($pesan, 0, 255), ...$wil]);
                $n = $ins->rowCount();
                $pdo->commit();
                flash("Jadwal disimpan. Notifikasi terkirim ke $n anggota.");
            } catch (Throwable $ex) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                flash('Jadwal gagal disimpan.', 'err');
            }
        }
    }
    redirect('jadwal.php');
}

$s = $pdo->prepare('SELECT * FROM jadwal_pengumpulan WHERE provinsi=? AND kabupaten_kota=? AND kelurahan=? ORDER BY tanggal, jam_mulai');
$s->execute($wil);
$L = $s->fetchAll();
layout_top('Jadwal', 'jadwal', $u);
?>
<div class="card">
  <h3>Jadwal pengumpulan sampah</h3>
  <p class="mut">Wilayah: <?= e($u['kelurahan']) ?>, <?= e($u['kabupaten_kota']) ?>. Jadwal hanya terlihat oleh petugas dan anggota di kelurahan ini.</p>
  <?php if (!$L): ?><div class="empty">Belum ada jadwal.</div><?php endif; ?>
  <?php foreach ($L as $j): ?>
    <div style="padding:10px 0;border-bottom:1px solid var(--line)">
      <b><?= e(tgl($j['tanggal'])) ?></b>, <?= e(substr($j['jam_mulai'], 0, 5)) ?> sampai <?= e(substr($j['jam_selesai'], 0, 5)) ?><br>
      <?= e($j['lokasi']) ?><?= $j['keterangan'] ? ' <span class="mut">(' . e($j['keterangan']) . ')</span>' : '' ?>
      <?php if ($isP): ?>
      <form method="post" class="inl" data-confirm="Hapus jadwal ini?"><?= csrf_field() ?><input type="hidden" name="aksi" value="hapus"><input type="hidden" name="id" value="<?= (int)$j['id_jadwal'] ?>"><button class="btn del" type="submit"><?= ic('trash', 18) ?>Hapus</button></form>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>
</div>
<?php if ($isP): ?>
<form method="post" class="card">
  <?= csrf_field() ?><input type="hidden" name="aksi" value="tambah">
  <h3>Tambah jadwal</h3>
  <div class="row">
    <div><label for="tanggal">Tanggal</label><input id="tanggal" name="tanggal" type="date" min="<?= date('Y-m-d') ?>" required></div>
    <div><label for="mulai">Jam mulai</label><input id="mulai" name="mulai" type="time" value="08:00" required></div>
    <div><label for="selesai">Jam selesai</label><input id="selesai" name="selesai" type="time" value="11:00" required></div>
  </div>
  <label for="lokasi">Lokasi</label><input id="lokasi" name="lokasi" maxlength="150" required>
  <label for="ket">Keterangan (opsional)</label><input id="ket" name="ket" maxlength="255">
  <button class="btn" type="submit"><?= ic('check', 18) ?>Simpan jadwal</button>
</form>
<?php endif;
layout_bottom();
