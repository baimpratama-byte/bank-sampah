<?php
require __DIR__ . '/inc/lib.php';
$u = need();
$isP = $u['role'] === 'petugas';

if ($isP && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $a = (string)($_POST['aksi'] ?? '');
    $id = (int)($_POST['id'] ?? 0);
    $hg = (float)str_replace(',', '.', (string)($_POST['harga'] ?? '-1'));
    if ($a === 'harga' && $hg >= 0 && $hg <= 10000000) {
        $pdo->prepare('UPDATE jenis_sampah SET harga_per_kg=? WHERE id_jenis=?')->execute([$hg, $id]);
        flash('Harga diperbarui.');
    } elseif ($a === 'hapus') {
        $pdo->prepare('UPDATE jenis_sampah SET aktif=0 WHERE id_jenis=?')->execute([$id]);
        flash('Jenis sampah dihapus dari daftar. Riwayat lama tetap aman.');
    } elseif ($a === 'tambah') {
        $n = trim((string)($_POST['nama'] ?? ''));
        $k = (string)($_POST['kategori'] ?? '');
        if ($n !== '' && mb_strlen($n) <= 50 && in_array($k, ['organik', 'anorganik', 'lainnya'], true) && $hg >= 0 && $hg <= 10000000) {
            $pdo->prepare('INSERT INTO jenis_sampah (nama_jenis, kategori, harga_per_kg) VALUES (?,?,?)')->execute([$n, $k, $hg]);
            flash('Jenis sampah ditambahkan.');
        } else flash('Isi nama, kategori, dan harga dengan benar.', 'err');
    } else flash('Harga tidak valid.', 'err');
    redirect('harga.php');
}

$L = $pdo->query('SELECT * FROM jenis_sampah WHERE aktif=1 ORDER BY nama_jenis')->fetchAll();
layout_top($isP ? 'Jenis dan harga' : 'Harga sampah', 'harga', $u);
?>
<div class="card tw">
  <h3>Harga sampah per kilogram</h3>
  <table>
    <tr><th>Jenis</th><th>Kategori</th><th>Harga per kg</th><?= $isP ? '<th></th>' : '' ?></tr>
    <?php foreach ($L as $j): ?>
    <tr>
      <td><?= e($j['nama_jenis']) ?></td><td><?= e($j['kategori']) ?></td>
      <?php if ($isP): ?>
      <td>
        <form method="post" id="f<?= (int)$j['id_jenis'] ?>"><?= csrf_field() ?><input type="hidden" name="aksi" value="harga"><input type="hidden" name="id" value="<?= (int)$j['id_jenis'] ?>">
        <input type="number" name="harga" min="0" step="50" value="<?= e((float)$j['harga_per_kg']) ?>" aria-label="Harga <?= e($j['nama_jenis']) ?>"></form>
      </td>
      <td style="white-space:nowrap">
        <button class="btn sm" type="submit" form="f<?= (int)$j['id_jenis'] ?>"><?= ic('check', 18) ?>Simpan</button>
        <form method="post" class="inl" data-confirm="Hapus jenis ini? Riwayat lama tetap aman."><?= csrf_field() ?><input type="hidden" name="aksi" value="hapus"><input type="hidden" name="id" value="<?= (int)$j['id_jenis'] ?>">
        <button class="btn del" type="submit"><?= ic('trash', 18) ?>Hapus</button></form>
      </td>
      <?php else: ?><td><?= rp($j['harga_per_kg']) ?></td><?php endif; ?>
    </tr>
    <?php endforeach; ?>
  </table>
  <?php if (!$L): ?><div class="empty">Belum ada jenis sampah.</div><?php endif; ?>
</div>
<?php if ($isP): ?>
<form method="post" class="card">
  <?= csrf_field() ?><input type="hidden" name="aksi" value="tambah">
  <h3>Tambah jenis sampah</h3>
  <div class="row">
    <div><label for="nama">Nama jenis</label><input id="nama" name="nama" maxlength="50" required></div>
    <div><label for="kategori">Kategori</label><select id="kategori" name="kategori"><option>organik</option><option selected>anorganik</option><option>lainnya</option></select></div>
    <div><label for="harga">Harga per kg</label><input id="harga" name="harga" type="number" min="0" step="50" required></div>
  </div>
  <button class="btn" type="submit"><?= ic('plus', 18) ?>Tambah jenis</button>
</form>
<?php endif;
layout_bottom();
