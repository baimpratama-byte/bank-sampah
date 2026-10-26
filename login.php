<?php
require __DIR__ . '/inc/lib.php';
require __DIR__ . '/inc/auth.php';
if ($u = me()) redirect($u['role'] === 'petugas' ? 'setor.php' : 'beranda.php');

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    if (($_SESSION['gagal'] ?? 0) >= 5) sleep(2); // pelambat sederhana untuk tebak-tebakan password
    $s = $pdo->prepare('SELECT id_user, password_hash, role FROM users WHERE username=?');
    $s->execute([strtolower(trim((string)($_POST['username'] ?? '')))]);
    $r = $s->fetch();
    if ($r && password_verify((string)($_POST['password'] ?? ''), $r['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['uid'] = (int)$r['id_user'];
        $_SESSION['gagal'] = 0;
        redirect($r['role'] === 'petugas' ? 'setor.php' : 'beranda.php');
    }
    $_SESSION['gagal'] = ($_SESSION['gagal'] ?? 0) + 1;
    $err = 'Username atau password salah';
}
auth_top('login', 'Masuk');
?>
    <h2>Selamat datang kembali</h2>
    <p class="mut">Masuk dengan username dan password kamu.</p>
    <?php if (!empty($_SESSION['flash'])): [$fm, $ft] = $_SESSION['flash']; unset($_SESSION['flash']); ?>
      <div class="flash <?= $ft === 'err' ? 'err' : 'ok' ?>"><?= e($fm) ?></div>
    <?php endif; ?>
    <?php if ($err): ?><div class="err-box" role="alert"><?= e($err) ?></div><?php endif; ?>
    <form method="post" autocomplete="on">
      <?= csrf_field() ?>
      <?= field('username', 'Username', 'user', 'autocomplete="username" required autofocus', (string)($_POST['username'] ?? '')) ?>
      <?= field('password', 'Password', 'lock', 'type="password" autocomplete="current-password" required') ?>
      <button class="btn full" type="submit"><?= ic('login', 18) ?>Masuk</button>
    </form>
<?php auth_bottom();
