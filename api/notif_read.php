<?php
require __DIR__ . '/../inc/lib.php';
header('Content-Type: application/json; charset=utf-8');
$u = me();
if (!$u || $_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(403); echo '{"ok":false}'; exit; }
csrf_verify();
$pdo->prepare('UPDATE notifikasi SET dibaca=1 WHERE id_user=?')->execute([$u['id_user']]);
echo '{"ok":true}';
