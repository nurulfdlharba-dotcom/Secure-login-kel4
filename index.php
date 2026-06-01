<?php
// ============================================================
// index.php — Halaman Login
// ============================================================
require_once 'config/koneksi.php';
startSecureSession();

// Jika sudah login, langsung ke dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

// --- Olah pesan dari query string ---
$error   = '';
$success = '';
$warning = '';

switch ($_GET['error'] ?? '') {
    case 'empty_fields':
        $error = 'Username dan password tidak boleh kosong.';
        break;
    case 'invalid_credentials':
        $left = isset($_GET['attempts_left']) ? " Sisa percobaan: <strong>{$_GET['attempts_left']}</strong>." : '';
        $error = "Username atau password salah.$left";
        break;
    case 'account_locked':
        $mins = (int)($_GET['minutes'] ?? 15);
        $warning = "Akun dikunci karena terlalu banyak percobaan gagal. Coba lagi dalam <strong>$mins menit</strong>.";
        break;
}

switch ($_GET['msg'] ?? '') {
    case 'logged_out':        $success = 'Kamu telah berhasil logout.'; break;
    case 'session_expired':   $warning = 'Session telah berakhir. Silakan login kembali.'; break;
    case 'login_required':    $warning = 'Kamu harus login untuk mengakses halaman tersebut.'; break;
}

if ($_GET['success'] ?? '' === 'registered') {
    $success = 'Registrasi berhasil! Silakan login dengan akun barumu.';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — SecureAuth</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="auth-wrapper">
    <div class="auth-card">

        <!-- Logo -->
        <div class="auth-logo">
            <div class="icon-shield">🔐</div>
            <h1>Secure<span>Auth</span></h1>
            <p>SHA-256 + Salt Authentication System</p>
        </div>

        <!-- Pesan notifikasi -->
        <?php if ($error): ?>
            <div class="alert-crypto"><?= $error ?></div>
        <?php endif; ?>
        <?php if ($warning): ?>
            <div class="alert-crypto warning"><?= $warning ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert-crypto success"><?= $success ?></div>
        <?php endif; ?>

        <!-- Form Login -->
        <form id="loginForm" action="process/login.php" method="POST" novalidate>

            <!-- Username -->
            <div class="mb-3">
                <label class="form-label" for="username">Username</label>
                <div class="input-icon-wrap">
                    <span class="input-icon">👤</span>
                    <input
                        type="text"
                        class="form-control"
                        id="username"
                        name="username"
                        placeholder="Masukkan username"
                        autocomplete="username"
                        required
                        value="<?= sanitize($_GET['u'] ?? '') ?>"
                    >
                </div>
            </div>

            <!-- Password -->
            <div class="mb-4">
                <label class="form-label" for="password">Password</label>
                <div class="input-icon-wrap" style="position:relative;">
                    <span class="input-icon">🔑</span>
                    <input
                        type="password"
                        class="form-control"
                        id="password"
                        name="password"
                        placeholder="Masukkan password"
                        autocomplete="current-password"
                        required
                    >
                    <button type="button" class="toggle-pw" data-target="password" title="Lihat password">👁️</button>
                </div>
            </div>

            <button type="submit" class="btn-crypto">🔓 Login</button>
        </form>

        <hr class="divider">

        <p class="text-center mb-0" style="font-size:0.875rem;color:var(--text-muted);">
            Belum punya akun?
            <a href="register.php" style="color:var(--accent);text-decoration:none;font-weight:700;">Daftar di sini</a>
        </p>

    </div><!-- /.auth-card -->
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/script.js"></script>
</body>
</html>
