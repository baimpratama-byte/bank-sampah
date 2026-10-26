<?php
declare(strict_types=1);
// Daftar wilayah bertingkat untuk dropdown alamat (dipakai assets/app.js).
//   ?l=p                       -> daftar provinsi
//   ?l=k&p=PROV                -> kabupaten/kota di provinsi itu
//   ?l=c&p=PROV&k=KAB          -> kecamatan di kabupaten/kota itu
//   ?l=l&p=PROV&k=KAB&c=KEC    -> kelurahan/desa di kecamatan itu
// Hasil: array JSON berisi nama (string). Halaman daftar bersifat publik.
require __DIR__ . '/../inc/lib.php';

header('Content-Type: application/json; charset=utf-8');

$l = (string)($_GET['l'] ?? '');
$p = trim((string)($_GET['p'] ?? ''));
$k = trim((string)($_GET['k'] ?? ''));
$c = trim((string)($_GET['c'] ?? ''));

switch ($l) {
    case 'p':
        $sql = 'SELECT nama FROM provinsi ORDER BY nama';
        $args = [];
        break;
    case 'k':
        $sql = 'SELECT kb.nama FROM kabupaten_kota kb
                JOIN provinsi p ON p.id_provinsi = kb.id_provinsi
                WHERE p.nama = ? ORDER BY kb.nama';
        $args = [$p];
        break;
    case 'c':
        $sql = 'SELECT kc.nama FROM kecamatan kc
                JOIN kabupaten_kota kb ON kb.id_kab = kc.id_kab
                JOIN provinsi p ON p.id_provinsi = kb.id_provinsi
                WHERE p.nama = ? AND kb.nama = ? ORDER BY kc.nama';
        $args = [$p, $k];
        break;
    case 'l':
        $sql = 'SELECT kl.nama FROM kelurahan kl
                JOIN kecamatan kc ON kc.id_kec = kl.id_kec
                JOIN kabupaten_kota kb ON kb.id_kab = kc.id_kab
                JOIN provinsi p ON p.id_provinsi = kb.id_provinsi
                WHERE p.nama = ? AND kb.nama = ? AND kc.nama = ? ORDER BY kl.nama';
        $args = [$p, $k, $c];
        break;
    default:
        echo '[]';
        exit;
}

$s = $pdo->prepare($sql);
$s->execute($args);
echo json_encode($s->fetchAll(PDO::FETCH_COLUMN), JSON_UNESCAPED_UNICODE);