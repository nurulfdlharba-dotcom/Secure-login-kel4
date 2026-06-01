<?php
// ============================================================
// register.php — Halaman Registrasi
// ============================================================
require_once 'config/koneksi.php';
startSecureSession();

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

// Olah pesan error
$errors = [
    'empty_fields'   => 'Semua field wajib diisi.',
    'username_length'=> 'Username harus 3–50 karakter.',
    'username_invalid'=> 'Username hanya boleh berisi huruf, angka, dan underscore.',
    'username_taken' => 'Username sudah dipakai. Pilih username lain.',
    'password_short' => 'Password minimal 8 karakter.',
    'password_weak'  => 'Password harus mengandung huruf besar, huruf kecil, dan angka.',
    'password_mismatch'=> 'Konfirmasi password tidak cocok.',
    'db_error'       => 'Terjadi kesalahan sistem. Coba lagi.',
];
$errorMsg = $errors[$_GET['error'] ?? ''] ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register — SecureAuth</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="auth-wrapper">
    <div class="auth-card" style="max-width:480px;">

        <!-- Logo -->
        <div class="auth-logo">
            <div class="icon-shield">🛡️</div>
            <h1>Daftar <span>Akun</span></h1>
            <p>Password akan di-hash dengan SHA-256 + Salt</p>
        </div>

        <!-- Penjelasan singkat kriptografi -->
        <div style="background:rgba(0,212,255,0.06);border:1px solid var(--border);border-radius:var(--radius-sm);padding:0.75rem 1rem;margin-bottom:1.25rem;font-size:0.8rem;color:var(--text-muted);line-height:1.6;">
            🔒 <strong style="color:var(--accent);">Cara kerja:</strong>
            Password kamu akan digabung dengan <em>salt acak</em> lalu di-hash menggunakan <strong>SHA-256</strong>.
            Password asli <u>tidak pernah</u> disimpan ke database.
        </div>

        <?php if ($errorMsg): ?>
            <div class="alert-crypto"><?= htmlspecialchars($errorMsg) ?></div>
        <?php endif; ?>

        <!-- Form Registrasi -->
        <form id="registerForm" action="process/register.php" method="POST" novalidate>

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
                        placeholder="Contoh: budi_santoso"
                        autocomplete="username"
                        required
                        pattern="[a-zA-Z0-9_]{3,50}"
                    >
                </div>
                <small style="color:var(--text-muted);font-size:0.73rem;">Huruf, angka, underscore. 3–50 karakter.</small>
            </div>

            <!-- Password -->
            <div class="mb-2">
                <label class="form-label" for="password">Password</label>
                <div class="input-icon-wrap" style="position:relative;">
                    <span class="input-icon">🔑</span>
                    <input
                        type="password"
                        class="form-control"
                        id="password"
                        name="password"
                        placeholder="Min. 8 karakter"
                        autocomplete="new-password"
                        required
                    >
                    <button type="button" class="toggle-pw" data-target="password">👁️</button>
                </div>
                <!-- Strength Meter -->
                <div class="strength-bar-wrap mt-1">
                    <div class="strength-bar" id="strengthBar"></div>
                </div>
                <div class="strength-text" id="strengthText"></div>
            </div>

            <!-- Syarat password -->
            <div id="pwRequirements" style="background:rgba(255,255,255,0.03);border:1px solid var(--border);border-radius:var(--radius-sm);padding:0.6rem 0.9rem;margin-bottom:0.75rem;font-size:0.75rem;">
                <div id="req-len"  class="req-item" style="color:var(--text-muted);">• Minimal 8 karakter</div>
                <div id="req-upper" class="req-item" style="color:var(--text-muted);">• Ada huruf BESAR</div>
                <div id="req-lower" class="req-item" style="color:var(--text-muted);">• Ada huruf kecil</div>
                <div id="req-num"  class="req-item" style="color:var(--text-muted);">• Ada angka (0-9)</div>
            </div>

            <!-- Konfirmasi Password -->
            <div class="mb-4">
                <label class="form-label" for="confirm_password">Konfirmasi Password</label>
                <div class="input-icon-wrap" style="position:relative;">
                    <span class="input-icon">🔏</span>
                    <input
                        type="password"
                        class="form-control"
                        id="confirm_password"
                        name="confirm_password"
                        placeholder="Ulangi password"
                        autocomplete="new-password"
                        required
                    >
                    <button type="button" class="toggle-pw" data-target="confirm_password">👁️</button>
                </div>
                <div id="matchMsg" style="font-size:0.75rem;margin-top:0.3rem;font-family:var(--font-mono);"></div>
            </div>

            <button type="submit" class="btn-crypto">✅ Buat Akun</button>
        </form>

        <hr class="divider">
        <p class="text-center mb-0" style="font-size:0.875rem;color:var(--text-muted);">
            Sudah punya akun?
            <a href="index.php" style="color:var(--accent);text-decoration:none;font-weight:700;">Login di sini</a>
        </p>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/script.js"></script>
<script>
// Inline: real-time requirement checker
document.getElementById('password').addEventListener('input', function () {
    const v = this.value;
    const set = (id, ok) => {
        const el = document.getElementById(id);
        if (el) { el.style.color = ok ? 'var(--success)' : 'var(--text-muted)'; }
    };
    set('req-len',   v.length >= 8);
    set('req-upper', /[A-Z]/.test(v));
    set('req-lower', /[a-z]/.test(v));
    set('req-num',   /[0-9]/.test(v));
    checkMatch();
});

document.getElementById('confirm_password').addEventListener('input', checkMatch);

function checkMatch() {
    const pw  = document.getElementById('password').value;
    const cpw = document.getElementById('confirm_password').value;
    const msg = document.getElementById('matchMsg');
    if (!cpw) { msg.textContent = ''; return; }
    if (pw === cpw) {
        msg.textContent = '✅ Password cocok';
        msg.style.color = 'var(--success)';
    } else {
        msg.textContent = '❌ Password tidak cocok';
        msg.style.color = 'var(--danger)';
    }
}
</script>
</body>
</html>
