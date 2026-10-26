<?php
require __DIR__ . '/inc/lib.php';
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $c = session_get_cookie_params();
    setcookie(session_name(), '', time() - 3600, $c['path'], $c['domain'], (bool)$c['secure'], (bool)$c['httponly']);
}
session_destroy();
redirect('index.php');
