<?php
// ============================================================
// logout.php — Redirect ke proses logout
// ============================================================
require_once 'config/koneksi.php';
startSecureSession();
header('Location: process/logout.php');
exit;
