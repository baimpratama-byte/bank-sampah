<?php
require __DIR__ . '/inc/lib.php';
require __DIR__ . '/inc/auth.php';
if ($u = me()) redirect($u['role'] === 'petugas' ? 'setor.php' : 'beranda.php');

$err = '';
$w = ['provinsi' => '', 'kabupaten_kota' => '', 'kecamatan' => '', 'kelurahan' => ''];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $un = strtolower(trim((string)($_POST['username'] ?? '')));
    $nama = trim((string)($_POST['nama_lengkap'] ?? ''));
    $tl = trim((string)($_POST['tempat_lahir'] ?? ''));
    $tg = (string)($_POST['tanggal_lahir'] ?? '');
    $det = trim((string)($_POST['detail_alamat'] ?? ''));
    $pos = trim((string)($_POST['kode_pos'] ?? ''));
    $pw = (string)($_POST['password'] ?? '');
    $w = wilayah_post();
    $d = DateTime::createFromFormat('Y-m-d', $tg);
    
    if ($nama === '' || $tl === '' || $det === '') $err = 'Lengkapi semua kolom.';
    elseif (!preg_match('/^[a-z0-9_.]{3,30}$/', $un)) $err = 'Username 3-30 karakter: huruf kecil, angka, titik, atau garis bawah.';
    elseif (strlen($pw) < 6) $err = 'Password minimal 6 karakter.';
    elseif (!$d || $d->format('Y-m-d') !== $tg || $tg > date('Y-m-d')) $err = 'Tanggal lahir tidak valid.';
    elseif (!preg_match('/^\d{5}$/', $pos)) $err = 'Kode pos harus 5 angka.';
    elseif (in_array('', $w, true)) $err = 'Lengkapi provinsi, kabupaten/kota, kecamatan, dan kelurahan/desa.';
    else {
        try {
            $pdo->beginTransaction();
            
            // 1. Simpan ke tabel users
            $pdo->prepare('INSERT INTO users (username,password_hash,role,nama_lengkap,tempat_lahir,tanggal_lahir,provinsi,kabupaten_kota,kecamatan,kelurahan,detail_alamat,kode_pos) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)')
                ->execute([$un, password_hash($pw, PASSWORD_DEFAULT), 'masyarakat', mb_substr($nama, 0, 100), mb_substr($tl, 0, 50), $tg, $w['provinsi'], $w['kabupaten_kota'], $w['kecamatan'], $w['kelurahan'], mb_substr($det, 0, 255), $pos]);
            
            // Ambil ID user yang baru saja dibuat
            $new_user_id = (int)$pdo->lastInsertId();

            // 2. Buat baris tabungan. INSERT IGNORE dipakai agar tidak error bila baris
            //    tabungan sudah dibuat otomatis (mis. oleh TRIGGER di database) atau
            //    sisa data lama dengan id yang sama.
            $pdo->prepare('INSERT IGNORE INTO tabungan (id_user, saldo) VALUES (?, 0)')->execute([$new_user_id]);

            $pdo->commit();
            flash('Pendaftaran berhasil, silakan masuk.');
            redirect('login.php');
            
        } catch (PDOException $ex) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            
            if ($ex->getCode() === '23000' || $ex->getCode() === '1062') {
                if (stripos($ex->getMessage(), 'username') !== false) {
                    $err = 'Username sudah dipakai. Silakan pilih username lain.';
                } else {
                    error_log('Register error: ' . $ex->getMessage());
                    $err = 'Pendaftaran gagal karena data bentrok. Silakan coba lagi.';
                }
            } else {
                error_log('Register error: ' . $ex->getMessage());
                $err = 'Terjadi kesalahan database. Silakan coba lagi nanti.';
            }
        }
    }
}
auth_top('reg', 'Daftar');
$v = fn(string $k): string => (string)($_POST[$k] ?? '');
?>
    <h2>Buat akun masyarakat</h2>
    <p class="mut">Semua kolom wajib diisi.</p>
    
    <?php if ($err): ?>
        <div class="err-box" role="alert" style="color: red; background: #ffe6e6; padding: 10px; border-radius: 5px; margin-bottom: 15px;">
            <?= e($err) ?>
        </div>
    <?php endif; ?>

    <form method="post">
      <?= csrf_field() ?>
      <h3>Data diri</h3>
      <div class="row">
        <?= field('nama_lengkap', 'Nama lengkap', 'user', 'required maxlength="100"', $v('nama_lengkap')) ?>
        <?= field('username', 'Username', 'user', 'required maxlength="30" autocomplete="username"', $v('username')) ?>
      </div>
      <?= field('password', 'Password (minimal 6 karakter)', 'lock', 'type="password" required autocomplete="new-password"') ?>
      <div class="row">
        <?= field('tempat_lahir', 'Tempat lahir', 'pin', 'required maxlength="50"', $v('tempat_lahir')) ?>
        <?= field('tanggal_lahir', 'Tanggal lahir', 'calendar', 'type="date" required max="' . date('Y-m-d') . '"', $v('tanggal_lahir')) ?>
      </div>
      <h3>Alamat</h3>
      <?= wilayah_fields($w) ?>
      <?= field('detail_alamat', 'Detail alamat (RT/RW, jalan, nomor rumah)', 'pin', 'required maxlength="255" placeholder="Contoh: RT 02 RW 05, Jl. Melati No. 12"', $v('detail_alamat')) ?>
      <?= field('kode_pos', 'Kode pos', 'mail', 'required inputmode="numeric" maxlength="5" pattern="\d{5}"', $v('kode_pos')) ?>
      <button class="btn full" type="submit"><?= ic('userplus', 18) ?>Daftar</button>
    </form>
<?php auth_bottom();