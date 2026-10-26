<?php
require __DIR__ . '/inc/lib.php';
$u = need();
$isP = $u['role'] === 'petugas';
$sql = 'SELECT s.id_setoran, s.tanggal_setor, s.total_nilai, m.nama_lengkap AS anggota, p.nama_lengkap AS petugas
        FROM setoran s JOIN users m ON m.id_user=s.id_masyarakat JOIN users p ON p.id_user=s.id_petugas' .
       ($isP ? '' : ' WHERE s.id_masyarakat=?') . ' ORDER BY s.tanggal_setor DESC, s.id_setoran DESC LIMIT 200';
$st = $pdo->prepare($sql);
$st->execute($isP ? [] : [$u['id_user']]);
$L = $st->fetchAll();
$det = [];
if ($L) {
    $ids = array_column($L, 'id_setoran');
    $q = $pdo->prepare('SELECT d.*, j.nama_jenis FROM detail_setoran d JOIN jenis_sampah j ON j.id_jenis=d.id_jenis WHERE d.id_setoran IN (' . implode(',', array_fill(0, count($ids), '?')) . ') ORDER BY j.nama_jenis');
    $q->execute($ids);
    foreach ($q->fetchAll() as $d) $det[$d['id_setoran']][] = $d;
}
layout_top('Riwayat', 'riwayat', $u);
?>
<?php if (!$L): ?><div class="card empty">Belum ada setoran tercatat.</div><?php else: ?>
<div class="card tw">
  <table>
    <tr><th>Waktu</th><?= $isP ? '<th>Anggota</th>' : '' ?><th>Petugas</th><th>Rincian</th><th>Total</th></tr>
    <?php foreach ($L as $r): ?>
    <tr>
      <td><?= e(date('d/m/Y H:i', strtotime($r['tanggal_setor']))) ?></td>
      <?= $isP ? '<td>' . e($r['anggota']) . '</td>' : '' ?>
      <td><?= e($r['petugas']) ?></td>
      <td><?php foreach ($det[$r['id_setoran']] ?? [] as $d): ?><?= e($d['nama_jenis']) ?> <?= fmtkg($d['berat_kg']) ?> kg x <?= rp($d['harga_per_kg']) ?><br><?php endforeach; ?></td>
      <td><b><?= rp($r['total_nilai']) ?></b></td>
    </tr>
    <?php endforeach; ?>
  </table>
</div>
<?php endif;
layout_bottom();
