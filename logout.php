<?php
/**
 * logout.php — Menghapus sesi admin dan redirect ke halaman login.
 */
session_start();

// Hapus seluruh variabel sesi
$_SESSION = [];

// Hapus cookie sesi jika aktif
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

session_destroy();

header('Location: login.php');
exit;