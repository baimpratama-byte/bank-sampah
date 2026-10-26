<?php
require __DIR__ . '/inc/lib.php';
$u = need('petugas');
$q = trim((string)($_GET['q'] ?? ''));
$sql = "SELECT u.*, COALESCE(t.saldo,0) AS saldo FROM users u LEFT JOIN tabungan t ON t.id_user=u.id_user WHERE u.role='masyarakat'";
$par = [];
if ($q !== '') {
    $n = preg_replace('/^BS-?/i', '', $q);
    $sql .= ' AND (u.nama_lengkap LIKE ? OR u.username LIKE ?' . (ctype_digit($n) ? ' OR u.id_user = ?' : '') . ')';
    $par = ['%' . $q . '%', '%' . $q . '%'];
    if (ctype_digit($n)) $par[] = (int)$n;
}
$s = $pdo->prepare($sql . ' ORDER BY u.id_user');
$s->execute($par);
$L = $s->fetchAll();
layout_top('Anggota', 'anggota', $u);
?>
<form method="get" class="card" style="display:flex;gap:10px;flex-wrap:wrap;align-items:end">
  <div style="flex:1;min-width:220px"><label for="q">Cari anggota (nama, username, atau ID)</label>
  <div class="in"><?= ic('search', 18) ?><input id="q" name="q" value="<?= e($q) ?>"></div></div>
  <button class="btn" type="submit" style="margin:0"><?= ic('search', 18) ?>Cari</button>
</form>
<div class="card tw">
  <h3>Anggota bank sampah (<?= count($L) ?>)</h3>
  <?php if (!$L): ?><div class="empty">Belum ada anggota yang cocok.</div><?php else: ?>
  <table>
    <tr><th>ID</th><th>Nama</th><th>Username</th><th>Tempat, tanggal lahir</th><th>Alamat</th><th>Saldo</th></tr>
    <?php foreach ($L as $r): ?>
    <tr>
      <td><b><?= kode((int)$r['id_user']) ?></b></td>
      <td><?= e($r['nama_lengkap']) ?></td>
      <td><?= e($r['username']) ?></td>
      <td><?= e($r['tempat_lahir']) ?>, <?= e($r['tanggal_lahir']) ?></td>
      <td><?= e($r['detail_alamat']) ?>, <?= e($r['kelurahan']) ?>, <?= e($r['kabupaten_kota']) ?>, <?= e($r['provinsi']) ?> <?= e($r['kode_pos']) ?></td>
      <td><b><?= rp($r['saldo']) ?></b></td>
    </tr>
    <?php endforeach; ?>
  </table>
  <?php endif; ?>
</div>
<?php layout_bottom();
