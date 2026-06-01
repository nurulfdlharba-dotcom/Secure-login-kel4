<?php
// ============================================================
// process/logout.php
// Menghancurkan session dan redirect ke halaman login
// ============================================================

require_once '../config/koneksi.php';
startSecureSession();

// Hapus semua variabel session
session_unset();

// Hapus cookie session jika ada
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}

// Hancurkan session
session_destroy();

header('Location: ../index.php?msg=logged_out');
exit;
