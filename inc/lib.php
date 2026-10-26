<?php
declare(strict_types=1);
require __DIR__ . '/../config.php';
session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
session_start();

$pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
]);

function e($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function rp($n): string { return 'Rp' . number_format((float)$n, 0, ',', '.'); }
function kode(int $id): string { return 'BS-' . str_pad((string)$id, 4, '0', STR_PAD_LEFT); }
function redirect(string $u): void { header('Location: ' . $u); exit; }
function flash(string $m, string $t = 'ok'): void { $_SESSION['flash'] = [$m, $t]; }
function tgl(string $d): string {
    $b = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    $t = strtotime($d);
    return date('j', $t) . ' ' . $b[(int)date('n', $t)] . ' ' . date('Y', $t);
}

function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
    return $_SESSION['csrf'];
}
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">'; }
function csrf_verify(): void {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $t = $_POST['csrf'] ?? ($_SERVER['HTTP_X_CSRF'] ?? '');
        if (!hash_equals($_SESSION['csrf'] ?? '', (string)$t)) { http_response_code(419); exit('Sesi tidak valid. Muat ulang halaman lalu coba lagi.'); }
    }
}

function me(): ?array {
    global $pdo;
    static $u = false;
    if ($u === false) {
        $u = null;
        if (!empty($_SESSION['uid'])) {
            $s = $pdo->prepare('SELECT u.*, COALESCE(t.saldo,0) AS saldo FROM users u LEFT JOIN tabungan t ON t.id_user=u.id_user WHERE u.id_user=?');
            $s->execute([$_SESSION['uid']]);
            $u = $s->fetch() ?: null;
        }
    }
    return $u;
}
function need(?string $role = null): array {
    $u = me();
    if (!$u) redirect('login.php');
    if ($role && $u['role'] !== $role) redirect($u['role'] === 'petugas' ? 'setor.php' : 'beranda.php');
    return $u;
}

const ICONS = [
 'leaf' => '<path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10Z"/><path d="M2 21c0-3 1.85-5.36 5.08-6C9.5 14.52 12 13 13 12"/>',
 'home' => '<path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>',
 'history' => '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>',
 'tag' => '<path d="M12 2H2v10l9.29 9.29a1 1 0 0 0 1.42 0l8.58-8.58a1 1 0 0 0 0-1.42z"/><circle cx="7" cy="7" r="1.5"/>',
 'calendar' => '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>',
 'plus' => '<circle cx="12" cy="12" r="10"/><path d="M12 8v8M8 12h8"/>',
 'users' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>',
 'logout' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>',
 'login' => '<path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/>',
 'userplus' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M19 8v6M22 11h-6"/>',
 'menu' => '<path d="M3 6h18M3 12h18M3 18h18"/>',
 'check' => '<polyline points="20 6 9 17 4 12"/>',
 'trash' => '<path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-1-2L5 6"/>',
 'back' => '<path d="m12 19-7-7 7-7M19 12H5"/>',
 'user' => '<path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
 'lock' => '<rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>',
 'pin' => '<path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/>',
 'mail' => '<rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 6L2 7"/>',
 'search' => '<circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>',
 'qr' => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><path d="M14 14h3v3h-3zM20 14v.01M14 20v.01M20 20v.01M17 17v3h3"/>',
 'bell' => '<path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/>',
];
function ic(string $n, int $s = 20): string {
    return '<svg width="' . $s . '" height="' . $s . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . (ICONS[$n] ?? '') . '</svg>';
}
function logo(int $s): string {
    return '<span class="logo" style="width:' . $s . 'px;height:' . $s . 'px">' . ic('leaf', (int)round($s * .58)) . '</span>';
}
function avatar(array $u): string {
    if (!empty($u['foto'])) return '<span class="av"><img src="uploads/foto/' . e($u['foto']) . '" alt=""></span>';
    return '<span class="av">' . e(mb_strtoupper(mb_substr($u['nama_lengkap'], 0, 1))) . '</span>';
}

/* ---------- Alamat bertingkat: baca hasil form ---------- */
function wilayah_post(): array {
    $r = [];
    foreach (['provinsi', 'kabupaten_kota', 'kecamatan', 'kelurahan'] as $k) {
        $v = trim((string)($_POST[$k] ?? ''));
        if ($v === '__m') $v = trim((string)($_POST[$k . '_x'] ?? ''));
        $r[$k] = mb_substr($v, 0, 80);
    }
    return $r;
}
function wilayah_fields(array $v = []): string {
    $o = '';
    foreach ([['provinsi', 'Provinsi'], ['kabupaten_kota', 'Kabupaten/Kota'], ['kecamatan', 'Kecamatan'], ['kelurahan', 'Kelurahan/Desa']] as [$k, $l]) {
        $o .= '<div><label for="' . $k . '">' . $l . '</label><div class="in">' . ic('pin', 18) .
              '<select id="' . $k . '" name="' . $k . '" data-v="' . e($v[$k] ?? '') . '"><option value="">Pilih</option></select></div>' .
              '<input class="hid" id="' . $k . '_x" name="' . $k . '_x" maxlength="80" placeholder="Ketik ' . strtolower($l) . '" aria-label="' . $l . ' (ketik sendiri)" style="margin-top:6px"></div>';
    }
    return '<div class="row wil">' . $o . '</div>';
}
function field(string $name, string $label, string $icon, string $attr = '', string $val = ''): string {
    return '<div><label for="' . $name . '">' . $label . '</label><div class="in">' . ic($icon, 18) .
           '<input id="' . $name . '" name="' . $name . '" value="' . e($val) . '" ' . $attr . '></div></div>';
}

/* ---------- Layout dashboard ---------- */
function layout_top(string $title, string $tab, array $u): void {
    global $pdo;
    $tabs = $u['role'] === 'petugas'
        ? [['setor', 'setor.php', 'Catat setoran', 'plus'], ['anggota', 'anggota.php', 'Anggota', 'users'], ['harga', 'harga.php', 'Jenis dan harga', 'tag'], ['jadwal', 'jadwal.php', 'Jadwal', 'calendar'], ['riwayat', 'riwayat.php', 'Riwayat', 'history']]
        : [['beranda', 'beranda.php', 'Beranda', 'home'], ['riwayat', 'riwayat.php', 'Riwayat', 'history'], ['harga', 'harga.php', 'Harga sampah', 'tag'], ['jadwal', 'jadwal.php', 'Jadwal', 'calendar']];
    $notif = []; $unread = 0;
    if ($u['role'] === 'masyarakat') {
        $s = $pdo->prepare('SELECT * FROM notifikasi WHERE id_user=? ORDER BY id_notif DESC LIMIT 8');
        $s->execute([$u['id_user']]);
        $notif = $s->fetchAll();
        $c = $pdo->prepare('SELECT COUNT(*) FROM notifikasi WHERE id_user=? AND dibaca=0');
        $c->execute([$u['id_user']]);
        $unread = (int)$c->fetchColumn();
    }
    ?><!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="csrf" content="<?= e(csrf_token()) ?>">
<title><?= e($title) ?> | Bank Sampah Digital</title>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@600;800&family=Public+Sans:wght@400;500;600&display=swap">
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="shell" id="shell">
<aside class="side" aria-label="Menu utama">
  <div class="sb-top"><?= logo(36) ?><span>Bank Sampah</span></div>
  <nav class="sn">
  <?php foreach ($tabs as [$k, $href, $lb, $icon]): ?>
    <a class="snl<?= $tab === $k ? ' on' : '' ?>" href="<?= $href ?>"><?= ic($icon) ?><span><?= $lb ?></span></a>
  <?php endforeach; ?>
  </nav>
  <a class="sn-out snl" href="logout.php"><?= ic('logout') ?><span>Keluar</span></a>
</aside>
<div class="scrim" id="scrim"></div>
<div class="main">
  <div class="top">
    <button class="burger" id="burger" aria-label="Buka atau tutup menu"><?= ic('menu') ?></button>
    <h1><?= e($title) ?></h1>
    <?php if ($u['role'] === 'masyarakat'): ?>
    <div class="bw">
      <button class="bell" id="bell" aria-label="Notifikasi" aria-expanded="false"><?= ic('bell') ?><?php if ($unread): ?><span class="nb" id="nb"><?= $unread ?></span><?php endif; ?></button>
      <div class="np hid" id="np"><b>Notifikasi</b>
        <?php if (!$notif): ?><div class="empty">Belum ada notifikasi.</div><?php endif; ?>
        <?php foreach ($notif as $n): ?>
          <div class="ni<?= $n['dibaca'] ? '' : ' new' ?>"><div><?= e($n['pesan']) ?></div><small class="mut"><?= e(date('d/m/Y H:i', strtotime($n['created_at']))) ?></small></div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>
    <a class="who" href="profil.php" aria-label="Buka profil" style="text-decoration:none"><?= avatar($u) ?><span class="wn"><?= e($u['nama_lengkap']) ?><small><?= e($u['role']) ?></small></span></a>
  </div>
  <div class="content">
  <?php if (!empty($_SESSION['flash'])): [$fm, $ft] = $_SESSION['flash']; unset($_SESSION['flash']); ?>
    <div class="flash <?= $ft === 'err' ? 'err' : 'ok' ?>" role="status"><?= e($fm) ?></div>
  <?php endif; ?>
<?php
}
function layout_bottom(array $scripts = []): void { ?>
  </div>
</div>
</div>
<?php foreach ($scripts as $s): ?><script src="<?= e($s) ?>"></script>
<?php endforeach; ?>
<script src="assets/app.js"></script>
</body>
</html>
<?php
}

function fmtkg($b): string {
    $s = rtrim(rtrim(number_format((float)$b, 2, ',', '.'), '0'), ',');
    return $s === '' ? '0' : $s;
}