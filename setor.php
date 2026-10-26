<?php
require __DIR__ . '/inc/lib.php';
$u = need('petugas');

function cari_anggota(PDO $pdo, string $q): ?array {
    $n = preg_replace('/^BS-?/i', '', trim($q));
    if ($n === '' || !ctype_digit($n)) return null;
    $s = $pdo->prepare("SELECT u.id_user, u.nama_lengkap, u.foto, u.kelurahan, COALESCE(t.saldo,0) AS saldo FROM users u LEFT JOIN tabungan t ON t.id_user=u.id_user WHERE u.id_user=? AND u.role='masyarakat'");
    $s->execute([(int)$n]);
    return $s->fetch() ?: null;
}

$m = null;
$jenis = $pdo->query('SELECT * FROM jenis_sampah WHERE aktif=1 ORDER BY nama_jenis')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $m = cari_anggota($pdo, (string)($_POST['id_m'] ?? ''));
    $harga = [];
    foreach ($jenis as $j) $harga[(int)$j['id_jenis']] = (float)$j['harga_per_kg'];
    $items = []; $total = 0.0;
    foreach ((array)($_POST['berat'] ?? []) as $jid => $b) {
        $b = (float)str_replace(',', '.', (string)$b);
        $jid = (int)$jid;
        if ($b > 0 && $b <= 100000 && isset($harga[$jid])) {
            $sub = round($b * $harga[$jid], 2);
            $items[] = [$jid, round($b, 2), $harga[$jid], $sub];
            $total += $sub;
        }
    }
    if (!$m) { flash('Anggota tidak ditemukan.', 'err'); }
    elseif (!$items) { flash('Isi berat untuk minimal satu jenis sampah.', 'err'); }
    else {
        try {
            $pdo->beginTransaction();
            $pdo->prepare('INSERT INTO setoran (id_masyarakat, id_petugas, total_nilai) VALUES (?,?,?)')->execute([$m['id_user'], $u['id_user'], $total]);
            $sid = (int)$pdo->lastInsertId();
            $ins = $pdo->prepare('INSERT INTO detail_setoran (id_setoran, id_jenis, berat_kg, harga_per_kg, subtotal) VALUES (?,?,?,?,?)');
            foreach ($items as [$jid, $b, $h, $sub]) $ins->execute([$sid, $jid, $b, $h, $sub]);
            $pdo->prepare('UPDATE tabungan SET saldo = saldo + ? WHERE id_user = ?')->execute([$total, $m['id_user']]);
            $pdo->commit();
            flash('Setoran tersimpan. Saldo ' . $m['nama_lengkap'] . ' bertambah ' . rp($total) . '.');
            redirect('setor.php');
        } catch (Throwable $ex) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            flash('Setoran gagal disimpan. Coba lagi.', 'err');
        }
    }
} elseif (trim((string)($_GET['id'] ?? '')) !== '') {
    $m = cari_anggota($pdo, (string)$_GET['id']);
    if (!$m) flash('ID anggota tidak ditemukan.', 'err');
}

layout_top('Catat setoran', 'setor', $u);
if (!$m): ?>
<div class="card">
  <h3>Pilih anggota</h3>
  <p class="mut">Masukkan ID anggota, atau scan QR yang ditunjukkan anggota.</p>
  <form method="get" id="cariForm">
    <label for="idm">ID anggota</label>
    <div class="in"><?= ic('user', 18) ?><input id="idm" name="id" placeholder="Contoh: BS-0002" autocomplete="off" required></div>
    <div style="display:flex;gap:10px;flex-wrap:wrap">
      <button class="btn" type="submit"><?= ic('search', 18) ?>Cari anggota</button>
      <button class="btn alt" type="button" id="scanBtn"><?= ic('qr', 18) ?>Scan QR anggota</button>
    </div>
  </form>
</div>
<div id="scm" class="modal" style="display:none" role="dialog" aria-modal="true" aria-label="Scan QR anggota">
  <div class="mbox"><h3>Scan QR anggota</h3>
    <video id="scv" muted playsinline></video><canvas id="scc" style="display:none"></canvas>
    <p id="scst" class="mut"></p>
    <div class="mact">
      <label class="btn alt sm" style="margin:0;cursor:pointer">Unggah gambar QR<input type="file" id="scFile" accept="image/*" hidden></label>
      <button class="btn sm" type="button" id="scClose">Tutup</button>
    </div>
  </div>
</div>
<?php else: ?>
<div class="card" style="display:flex;align-items:center;gap:14px;flex-wrap:wrap">
  <?= avatar(['foto' => $m['foto'], 'nama_lengkap' => $m['nama_lengkap']]) ?>
  <div style="flex:1;min-width:160px"><b><?= e($m['nama_lengkap']) ?></b><br><span class="mut"><?= kode((int)$m['id_user']) ?> | Kel. <?= e($m['kelurahan']) ?> | Saldo <?= rp($m['saldo']) ?></span></div>
  <a class="btn alt sm" href="setor.php">Ganti anggota</a>
</div>
<form method="post" class="card tw">
  <?= csrf_field() ?>
  <input type="hidden" name="id_m" value="<?= (int)$m['id_user'] ?>">
  <h3>Rincian setoran</h3>
  <p class="mut">Isi berat untuk setiap jenis sampah yang disetor. Bisa lebih dari satu jenis sekaligus, kosongkan jenis yang tidak ada.</p>
  <table>
    <tr><th>Jenis sampah</th><th>Harga per kg</th><th>Berat (kg)</th><th>Subtotal</th></tr>
    <?php foreach ($jenis as $j): $jid = (int)$j['id_jenis']; ?>
    <tr>
      <td><?= e($j['nama_jenis']) ?><br><small class="mut"><?= e($j['kategori']) ?></small></td>
      <td><?= rp($j['harga_per_kg']) ?></td>
      <td><input class="berat" type="text" inputmode="decimal" name="berat[<?= $jid ?>]" placeholder="0" value="<?= e($_POST['berat'][$jid] ?? '') ?>" data-id="<?= $jid ?>" data-harga="<?= e($j['harga_per_kg']) ?>" aria-label="Berat <?= e($j['nama_jenis']) ?>"></td>
      <td id="st<?= $jid ?>">Rp0</td>
    </tr>
    <?php endforeach; ?>
  </table>
  <p>Total nilai setoran: <span class="tot" id="tt">Rp0</span></p>
  <button class="btn" type="submit"><?= ic('check', 18) ?>Simpan setoran dan perbarui saldo</button>
</form>
<?php endif;
layout_bottom($m ? [] : ['https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.js']);
