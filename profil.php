<?php
require __DIR__ . '/inc/lib.php';
$u = need();
$id = (int)$u['id_user'];
$isM = $u['role'] === 'masyarakat';

/* Simpan foto: dipotong persegi 256px dan disimpan sebagai JPEG.
   Bila gagal, $alasan berisi penyebabnya. */
function simpan_foto(array $f, int $id, string &$alasan = ''): ?string {
    if (!function_exists('imagecreatetruecolor')) { $alasan = 'ekstensi GD PHP belum aktif'; return null; }
    $e = $f['error'] ?? UPLOAD_ERR_NO_FILE;
    if ($e === UPLOAD_ERR_INI_SIZE || $e === UPLOAD_ERR_FORM_SIZE) { $alasan = 'ukuran file melebihi batas server'; return null; }
    if ($e !== UPLOAD_ERR_OK) { $alasan = 'file tidak terkirim (kode ' . $e . ')'; return null; }
    if ($f['size'] > 3 * 1024 * 1024) { $alasan = 'ukuran file lebih dari 3 MB'; return null; }
    $info = @getimagesize($f['tmp_name']);
    if (!$info) { $alasan = 'file bukan gambar yang valid'; return null; }
    $src = match ($info[2]) {
        IMAGETYPE_JPEG => @imagecreatefromjpeg($f['tmp_name']),
        IMAGETYPE_PNG => @imagecreatefrompng($f['tmp_name']),
        IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($f['tmp_name']) : false,
        default => false,
    };
    if (!$src) { $alasan = 'format gambar tidak didukung atau file rusak'; return null; }
    $dir = __DIR__ . '/uploads/foto/';
    if (!is_dir($dir) || !is_writable($dir)) { $alasan = 'folder uploads/foto tidak ada atau tidak bisa ditulis'; return null; }
    $w = imagesx($src); $h = imagesy($src); $s = min($w, $h);
    $dst = imagecreatetruecolor(256, 256);
    imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));
    imagecopyresampled($dst, $src, 0, 0, intdiv($w - $s, 2), intdiv($h - $s, 2), 256, 256, $s, $s);
    $nama = 'u' . $id . '_' . bin2hex(random_bytes(6)) . '.jpg';
    $ok = imagejpeg($dst, $dir . $nama, 85);
    if (!$ok) $alasan = 'gagal menyimpan file ke folder uploads/foto';
    return $ok ? $nama : null;
}
function hapus_file_foto(?string $n): void {
    if ($n && preg_match('/^[A-Za-z0-9_]+\.jpg$/', $n)) @unlink(__DIR__ . '/uploads/foto/' . $n);
}

$err = '';
$w = ['provinsi' => $u['provinsi'], 'kabupaten_kota' => $u['kabupaten_kota'], 'kecamatan' => $u['kecamatan'] ?? '', 'kelurahan' => $u['kelurahan']];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $a = (string)($_POST['aksi'] ?? '');
    if ($a === 'foto') {
        $alasan = '';
        $n = simpan_foto($_FILES['foto'] ?? [], $id, $alasan);
        if ($n) {
            hapus_file_foto($u['foto']);
            $pdo->prepare('UPDATE users SET foto=? WHERE id_user=?')->execute([$n, $id]);
            flash('Foto profil diperbarui.');
        } else flash('Foto gagal diunggah: ' . ($alasan !== '' ? $alasan : 'penyebab tidak diketahui') . '. Pakai JPG, PNG, atau WebP maksimal 3 MB.', 'err');
        redirect('profil.php');
    } elseif ($a === 'hapus_foto') {
        hapus_file_foto($u['foto']);
        $pdo->prepare('UPDATE users SET foto=NULL WHERE id_user=?')->execute([$id]);
        flash('Foto profil dihapus.');
        redirect('profil.php');
    } elseif ($a === 'data') {
        $nama = trim((string)($_POST['nama_lengkap'] ?? ''));
        $tl = trim((string)($_POST['tempat_lahir'] ?? ''));
        $tg = (string)($_POST['tanggal_lahir'] ?? '');
        $det = trim((string)($_POST['detail_alamat'] ?? ''));
        $pos = trim((string)($_POST['kode_pos'] ?? ''));
        $pw0 = (string)($_POST['password_lama'] ?? '');
        $pw1 = (string)($_POST['password_baru'] ?? '');
        $w = wilayah_post();
        $d = DateTime::createFromFormat('Y-m-d', $tg);
        if ($nama === '' || $tl === '' || $det === '') $err = 'Lengkapi semua kolom.';
        elseif (!$d || $d->format('Y-m-d') !== $tg || $tg > date('Y-m-d')) $err = 'Tanggal lahir tidak valid.';
        elseif (!preg_match('/^\d{5}$/', $pos)) $err = 'Kode pos harus 5 angka.';
        elseif (in_array('', $w, true)) $err = 'Lengkapi provinsi, kabupaten/kota, kecamatan, dan kelurahan/desa.';
        elseif ($pw1 !== '' && strlen($pw1) < 6) $err = 'Password baru minimal 6 karakter.';
        elseif ($pw1 !== '' && !password_verify($pw0, (string)$u['password_hash'])) $err = 'Password lama salah.';
        else {
            $pdo->prepare('UPDATE users SET nama_lengkap=?, tempat_lahir=?, tanggal_lahir=?, provinsi=?, kabupaten_kota=?, kecamatan=?, kelurahan=?, detail_alamat=?, kode_pos=? WHERE id_user=?')
                ->execute([mb_substr($nama, 0, 100), mb_substr($tl, 0, 50), $tg, $w['provinsi'], $w['kabupaten_kota'], $w['kecamatan'], $w['kelurahan'], mb_substr($det, 0, 255), $pos, $id]);
            if ($pw1 !== '') $pdo->prepare('UPDATE users SET password_hash=? WHERE id_user=?')->execute([password_hash($pw1, PASSWORD_DEFAULT), $id]);
            flash('Profil diperbarui.');
            redirect('profil.php');
        }
    }
}

layout_top('Profil', 'profil', $u);
$v = fn(string $k, string $def): string => (string)($_POST[$k] ?? $def);
?>
<div class="prof">
  <div>
    <div class="card pf">
      <?= avatar($u) ?>
      <div style="flex:1;min-width:180px"><h2 style="margin:0"><?= e($u['nama_lengkap']) ?></h2><span class="mut">@<?= e($u['username']) ?></span> <span class="badge"><?= e($u['role']) ?></span></div>
      <div style="display:flex;gap:10px;flex-wrap:wrap;justify-content:center">
        <form method="post" enctype="multipart/form-data" class="inl"><?= csrf_field() ?><input type="hidden" name="aksi" value="foto">
          <label class="btn alt sm" style="margin:0;cursor:pointer"><?= ic('user', 18) ?>Ganti foto<input type="file" id="fotoInput" name="foto" accept="image/jpeg,image/png,image/webp" hidden></label></form>
        <?php if ($u['foto']): ?>
        <form method="post" class="inl" data-confirm="Hapus foto profil?"><?= csrf_field() ?><input type="hidden" name="aksi" value="hapus_foto"><button class="btn del" type="submit"><?= ic('trash', 18) ?>Hapus foto</button></form>
        <?php endif; ?>
      </div>
    </div>
    <?php if ($isM): ?>
    <div class="card qrc">
      <div id="qrbox" data-kode="<?= kode($id) ?>"></div>
      <div><h3>QR anggota</h3><p>Tunjukkan QR ini ke petugas saat menyetor sampah.</p><p>ID anggota: <b class="tot"><?= kode($id) ?></b></p></div>
    </div>
    <?php endif; ?>
  </div>
  <form method="post" class="card">
    <?= csrf_field() ?><input type="hidden" name="aksi" value="data">
    <h3>Ubah data diri</h3>
    <?php if ($err): ?><div class="err-box" role="alert"><?= e($err) ?></div><?php endif; ?>
    <div class="row">
      <?= field('nama_lengkap', 'Nama lengkap', 'user', 'required maxlength="100"', $v('nama_lengkap', $u['nama_lengkap'])) ?>
      <?= field('tempat_lahir', 'Tempat lahir', 'pin', 'required maxlength="50"', $v('tempat_lahir', $u['tempat_lahir'])) ?>
      <?= field('tanggal_lahir', 'Tanggal lahir', 'calendar', 'type="date" required max="' . date('Y-m-d') . '"', $v('tanggal_lahir', $u['tanggal_lahir'])) ?>
    </div>
    <h3>Alamat</h3>
    <?= wilayah_fields($w) ?>
    <?= field('detail_alamat', 'Detail alamat (RT/RW, jalan, nomor rumah)', 'pin', 'required maxlength="255"', $v('detail_alamat', $u['detail_alamat'])) ?>
    <?= field('kode_pos', 'Kode pos', 'mail', 'required inputmode="numeric" maxlength="5" pattern="\d{5}"', $v('kode_pos', $u['kode_pos'])) ?>
    <h3>Keamanan</h3>
    <p class="mut">Kosongkan jika tidak ingin mengganti password.</p>
    <div class="row">
      <?= field('password_lama', 'Password lama', 'lock', 'type="password" autocomplete="current-password"') ?>
      <?= field('password_baru', 'Password baru (minimal 6 karakter)', 'lock', 'type="password" autocomplete="new-password"') ?>
    </div>
    <button class="btn" type="submit"><?= ic('check', 18) ?>Simpan profil</button>
  </form>
</div>
<?php layout_bottom($isM ? ['https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js'] : []);