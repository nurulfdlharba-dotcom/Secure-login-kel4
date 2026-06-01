<?php
// ============================================================
// dashboard.php — Halaman Utama setelah Login
// ============================================================
require_once 'config/koneksi.php';
requireLogin(); // Cek login + session timeout

$username  = $_SESSION['username'];
$loginTime = $_SESSION['login_time'];

$conn = getConnection();

// --- Ambil data statistik ---
// Total login berhasil milik user ini
$stmtOk = $conn->prepare("SELECT COUNT(*) FROM login_logs WHERE username=? AND status='Berhasil'");
$stmtOk->bind_param('s', $username);
$stmtOk->execute();
$stmtOk->bind_result($totalSuccess);
$stmtOk->fetch();
$stmtOk->close();

// Total login gagal milik user ini
$stmtFail = $conn->prepare("SELECT COUNT(*) FROM login_logs WHERE username=? AND status='Gagal'");
$stmtFail->bind_param('s', $username);
$stmtFail->execute();
$stmtFail->bind_result($totalFail);
$stmtFail->fetch();
$stmtFail->close();

// Total seluruh pengguna terdaftar
$totalUsers = $conn->query("SELECT COUNT(*) FROM users")->fetch_row()[0];

// Login terakhir sebelum sesi ini
$stmtPrev = $conn->prepare(
    "SELECT login_time FROM login_logs WHERE username=? AND status='Berhasil' ORDER BY login_time DESC LIMIT 1 OFFSET 1"
);
$stmtPrev->bind_param('s', $username);
$stmtPrev->execute();
$stmtPrev->bind_result($lastLogin);
$stmtPrev->fetch();
$stmtPrev->close();

// 5 aktivitas login terbaru (semua status)
$recentLogs = $conn->prepare(
    "SELECT login_time, status, ip_address FROM login_logs WHERE username=? ORDER BY login_time DESC LIMIT 5"
);
$recentLogs->bind_param('s', $username);
$recentLogs->execute();
$logsResult = $recentLogs->get_result();

$conn->close();

// Sisa waktu session
$elapsed   = time() - ($_SESSION['last_activity'] ?? time());
$remaining = max(0, SESSION_TIMEOUT - $elapsed);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — SecureAuth</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="app-layout">

    <!-- ======================================================
         SIDEBAR
    ====================================================== -->
    <?php include 'includes/sidebar.php'; ?>

    <!-- ======================================================
         MAIN CONTENT
    ====================================================== -->
    <div class="main-content">

        <!-- Topbar -->
        <div class="topbar">
            <div class="d-flex align-items-center gap-3">
                <button id="sidebarToggle" class="btn btn-sm d-md-none"
                        style="background:none;border:1px solid var(--border);color:var(--text-muted);">☰</button>
                <h5 class="page-title">Dashboard</h5>
            </div>
            <div class="topbar-right">
                <div class="session-timer" id="sessionTimer" data-remaining="<?= $remaining ?>">
                    <span>--:--</span>
                </div>
                <a href="process/logout.php" class="btn btn-sm"
                   style="background:rgba(255,77,109,0.1);border:1px solid rgba(255,77,109,0.3);color:#ff4d6d;font-size:0.8rem;">
                    🚪 Logout
                </a>
            </div>
        </div>

        <!-- Page Body -->
        <div class="page-body">

            <!-- Welcome Banner -->
            <div class="panel mb-4" style="border-color:rgba(0,212,255,0.3);">
                <div class="panel-body">
                    <div class="d-flex align-items-center gap-3">
                        <div style="font-size:2.5rem;">👋</div>
                        <div>
                            <h4 style="font-weight:800;margin:0;">
                                Halo, <span style="color:var(--accent);"><?= htmlspecialchars($username) ?></span>!
                            </h4>
                            <p style="color:var(--text-muted);font-size:0.85rem;margin:0.25rem 0 0;">
                                Login pada: <span style="color:var(--text-primary);font-family:var(--font-mono);"><?= $loginTime ?></span>
                                <?php if ($lastLogin): ?>
                                    · Login sebelumnya: <span style="font-family:var(--font-mono);"><?= $lastLogin ?></span>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stat Cards -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon ic-cyan">✅</div>
                        <div class="stat-value text-accent">
                            <span class="stat-counter" data-target="<?= $totalSuccess ?>"><?= $totalSuccess ?></span>
                        </div>
                        <div class="stat-label">Login Berhasil</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon ic-red">❌</div>
                        <div class="stat-value" style="color:var(--danger);">
                            <span class="stat-counter" data-target="<?= $totalFail ?>"><?= $totalFail ?></span>
                        </div>
                        <div class="stat-label">Login Gagal</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon ic-green">👥</div>
                        <div class="stat-value" style="color:var(--success);">
                            <span class="stat-counter" data-target="<?= $totalUsers ?>"><?= $totalUsers ?></span>
                        </div>
                        <div class="stat-label">Total Pengguna</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon ic-yellow">🔐</div>
                        <div class="stat-value" style="color:var(--warning);">SHA-256</div>
                        <div class="stat-label">Algoritma Hash</div>
                    </div>
                </div>
            </div>

            <!-- Aktivitas Login Terbaru -->
            <div class="panel">
                <div class="panel-header">
                    <span class="dot"></span>
                    Aktivitas Login Terbaru
                </div>
                <div class="panel-body p-0">
                    <table class="table table-crypto">
                        <thead>
                            <tr>
                                <th>Waktu</th>
                                <th>IP Address</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $logsResult->fetch_assoc()): ?>
                            <tr>
                                <td class="mono"><?= htmlspecialchars($row['login_time']) ?></td>
                                <td class="mono"><?= htmlspecialchars($row['ip_address']) ?></td>
                                <td>
                                    <?php if ($row['status'] === 'Berhasil'): ?>
                                        <span class="badge-success">✅ Berhasil</span>
                                    <?php else: ?>
                                        <span class="badge-danger">❌ Gagal</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div><!-- /.page-body -->
    </div><!-- /.main-content -->
</div><!-- /.app-layout -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/script.js"></script>
</body>
</html>
