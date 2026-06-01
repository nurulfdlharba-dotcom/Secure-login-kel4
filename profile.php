<?php
// ============================================================
// profile.php — Halaman Profil Pengguna
// ============================================================
require_once 'config/koneksi.php';
requireLogin();

$username  = $_SESSION['username'];
$conn      = getConnection();

// Ambil data lengkap user dari database
$stmt = $conn->prepare('SELECT id, username, salt, password_hash, created_at FROM users WHERE username = ?');
$stmt->bind_param('s', $username);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();
$conn->close();

$remaining = max(0, SESSION_TIMEOUT - (time() - ($_SESSION['last_activity'] ?? time())));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil — SecureAuth</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="app-layout">
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="topbar">
            <h5 class="page-title">Profil Saya</h5>
            <div class="topbar-right">
                <div class="session-timer" id="sessionTimer" data-remaining="<?= $remaining ?>"><span>--:--</span></div>
            </div>
        </div>

        <div class="page-body">
            <div class="row g-4">

                <!-- Kartu Identitas -->
                <div class="col-md-4">
                    <div class="panel h-100">
                        <div class="panel-header"><span class="dot"></span>Identitas Pengguna</div>
                        <div class="panel-body text-center">
                            <div style="width:80px;height:80px;background:linear-gradient(135deg,var(--accent),#7c3aed);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:2rem;font-weight:800;color:#fff;margin:0 auto 1rem;">
                                <?= strtoupper(substr($user['username'], 0, 1)) ?>
                            </div>
                            <h5 style="font-weight:800;"><?= htmlspecialchars($user['username']) ?></h5>
                            <p style="color:var(--text-muted);font-size:0.8rem;">
                                Terdaftar: <?= htmlspecialchars($user['created_at']) ?>
                            </p>
                            <hr class="divider">
                            <div style="font-family:var(--font-mono);font-size:0.78rem;color:var(--text-muted);">
                                User ID: <span style="color:var(--accent);">#<?= $user['id'] ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Data Kriptografi User -->
                <div class="col-md-8">
                    <div class="panel">
                        <div class="panel-header"><span class="dot"></span>Data Kriptografi Tersimpan di Database</div>
                        <div class="panel-body">

                            <p style="color:var(--text-muted);font-size:0.82rem;margin-bottom:1.25rem;">
                                Berikut adalah data yang <strong style="color:var(--accent);">benar-benar tersimpan</strong> di tabel <code>users</code> untuk akun ini.
                                Perhatikan bahwa password asli <strong style="color:var(--danger);">tidak ada</strong> di sini.
                            </p>

                            <!-- Salt -->
                            <div class="mb-3">
                                <div style="font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:var(--text-muted);margin-bottom:0.35rem;">
                                    🎲 Salt (random_bytes(32) → hex)
                                </div>
                                <div style="font-family:var(--font-mono);font-size:0.78rem;background:rgba(192,132,252,0.08);border:1px solid rgba(192,132,252,0.2);border-radius:var(--radius-sm);padding:0.6rem 0.9rem;color:#c084fc;word-break:break-all;">
                                    <?= htmlspecialchars($user['salt']) ?>
                                </div>
                                <small style="color:var(--text-muted);font-size:0.7rem;">64 karakter hex = 32 byte acak</small>
                            </div>

                            <!-- Hash -->
                            <div class="mb-3">
                                <div style="font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:var(--text-muted);margin-bottom:0.35rem;">
                                    🔒 Password Hash (SHA-256)
                                </div>
                                <div style="font-family:var(--font-mono);font-size:0.78rem;background:rgba(0,212,255,0.06);border:1px solid var(--border);border-radius:var(--radius-sm);padding:0.6rem 0.9rem;color:var(--accent);word-break:break-all;">
                                    <?= htmlspecialchars($user['password_hash']) ?>
                                </div>
                                <small style="color:var(--text-muted);font-size:0.7rem;">64 karakter hex = 256 bit = output SHA-256</small>
                            </div>

                            <!-- Password Asli -->
                            <div>
                                <div style="font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:var(--text-muted);margin-bottom:0.35rem;">
                                    🚫 Password Asli
                                </div>
                                <div style="font-family:var(--font-mono);font-size:0.78rem;background:rgba(255,77,109,0.06);border:1px solid rgba(255,77,109,0.2);border-radius:var(--radius-sm);padding:0.6rem 0.9rem;color:var(--danger);">
                                    [TIDAK DISIMPAN — Hanya hash yang ada di database]
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- Info Keamanan -->
                    <div class="panel mt-3" style="border-color:rgba(0,230,118,0.2);">
                        <div class="panel-header">
                            <span class="dot" style="background:var(--success);box-shadow:0 0 8px var(--success);"></span>
                            Keamanan Akun
                        </div>
                        <div class="panel-body">
                            <div class="row g-3">
                                <div class="col-sm-6">
                                    <div style="font-size:0.8rem;color:var(--text-muted);">Algoritma Hash</div>
                                    <div style="font-family:var(--font-mono);color:var(--accent);font-weight:700;">SHA-256</div>
                                </div>
                                <div class="col-sm-6">
                                    <div style="font-size:0.8rem;color:var(--text-muted);">Panjang Salt</div>
                                    <div style="font-family:var(--font-mono);color:#c084fc;font-weight:700;">32 byte (64 hex)</div>
                                </div>
                                <div class="col-sm-6">
                                    <div style="font-size:0.8rem;color:var(--text-muted);">Generator Salt</div>
                                    <div style="font-family:var(--font-mono);color:var(--success);font-weight:700;">random_bytes()</div>
                                </div>
                                <div class="col-sm-6">
                                    <div style="font-size:0.8rem;color:var(--text-muted);">Session Timeout</div>
                                    <div style="font-family:var(--font-mono);color:var(--warning);font-weight:700;">15 menit</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/script.js"></script>
</body>
</html>
