<?php
// ============================================================
// login-history.php — Riwayat Login Lengkap
// ============================================================
require_once 'config/koneksi.php';
requireLogin();

$username  = $_SESSION['username'];
$conn      = getConnection();

// Pagination
$perPage = 15;
$page    = max(1, (int)($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;

// Filter status
$filter = $_GET['status'] ?? 'all';
$whereExtra = '';
$bindTypes  = 's';
$bindParams = [$username];

if ($filter === 'success') {
    $whereExtra  = " AND status='Berhasil'";
} elseif ($filter === 'fail') {
    $whereExtra  = " AND status='Gagal'";
}

// Hitung total
$countStmt = $conn->prepare("SELECT COUNT(*) FROM login_logs WHERE username=?$whereExtra");
$countStmt->bind_param($bindTypes, ...$bindParams);
$countStmt->execute();
$countStmt->bind_result($totalRows);
$countStmt->fetch();
$countStmt->close();
$totalPages = max(1, ceil($totalRows / $perPage));

// Ambil data dengan pagination
$logsStmt = $conn->prepare(
    "SELECT login_time, ip_address, status, user_agent
     FROM login_logs WHERE username=?$whereExtra
     ORDER BY login_time DESC LIMIT ? OFFSET ?"
);
$logsStmt->bind_param('sii', $username, $perPage, $offset);
$logsStmt->execute();
$logs = $logsStmt->get_result();
$logsStmt->close();
$conn->close();

$remaining = max(0, SESSION_TIMEOUT - (time() - ($_SESSION['last_activity'] ?? time())));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Login — SecureAuth</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="app-layout">
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="topbar">
            <h5 class="page-title">📋 Riwayat Login</h5>
            <div class="topbar-right">
                <div class="session-timer" id="sessionTimer" data-remaining="<?= $remaining ?>"><span>--:--</span></div>
            </div>
        </div>

        <div class="page-body">

            <!-- Filter Buttons -->
            <div class="d-flex gap-2 mb-3 flex-wrap">
                <a href="?status=all"
                   style="padding:0.4rem 1rem;border-radius:20px;font-size:0.82rem;font-weight:600;text-decoration:none;border:1px solid var(--border);
                          <?= $filter==='all' ? 'background:var(--accent);color:#0a0e17;border-color:var(--accent);' : 'color:var(--text-muted);background:none;' ?>">
                    Semua (<?= $totalRows ?>)
                </a>
                <a href="?status=success"
                   style="padding:0.4rem 1rem;border-radius:20px;font-size:0.82rem;font-weight:600;text-decoration:none;border:1px solid var(--border);
                          <?= $filter==='success' ? 'background:var(--success);color:#0a0e17;border-color:var(--success);' : 'color:var(--text-muted);background:none;' ?>">
                    ✅ Berhasil
                </a>
                <a href="?status=fail"
                   style="padding:0.4rem 1rem;border-radius:20px;font-size:0.82rem;font-weight:600;text-decoration:none;border:1px solid var(--border);
                          <?= $filter==='fail' ? 'background:var(--danger);color:#fff;border-color:var(--danger);' : 'color:var(--text-muted);background:none;' ?>">
                    ❌ Gagal
                </a>
            </div>

            <!-- Tabel Riwayat -->
            <div class="panel">
                <div class="panel-header">
                    <span class="dot"></span>
                    Log Aktivitas — <span style="color:var(--accent);"><?= htmlspecialchars($username) ?></span>
                    <span style="color:var(--text-muted);font-weight:400;font-size:0.8rem;margin-left:auto;">
                        Halaman <?= $page ?> dari <?= $totalPages ?>
                    </span>
                </div>
                <div class="panel-body p-0">
                    <div class="table-responsive">
                        <table class="table table-crypto">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Waktu Login</th>
                                    <th>IP Address</th>
                                    <th>Status</th>
                                    <th>Browser/Perangkat</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $no = $offset + 1;
                                while ($row = $logs->fetch_assoc()):
                                    $ua = $row['user_agent'] ?? '-';
                                    // Sederhanakan user agent
                                    if (strpos($ua, 'Chrome') !== false)       $uaShort = '🌐 Chrome';
                                    elseif (strpos($ua, 'Firefox') !== false)  $uaShort = '🦊 Firefox';
                                    elseif (strpos($ua, 'Safari') !== false)   $uaShort = '🍎 Safari';
                                    elseif (strpos($ua, 'Edge') !== false)     $uaShort = '🔷 Edge';
                                    else $uaShort = '🖥️ Browser Lain';
                                ?>
                                <tr>
                                    <td class="mono" style="color:var(--text-muted);"><?= $no++ ?></td>
                                    <td class="mono"><?= htmlspecialchars($row['login_time']) ?></td>
                                    <td class="mono"><?= htmlspecialchars($row['ip_address']) ?></td>
                                    <td>
                                        <?php if ($row['status'] === 'Berhasil'): ?>
                                            <span class="badge-success">✅ Berhasil</span>
                                        <?php else: ?>
                                            <span class="badge-danger">❌ Gagal</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="font-size:0.82rem;"><?= $uaShort ?></td>
                                </tr>
                                <?php endwhile; ?>
                                <?php if ($totalRows === 0): ?>
                                <tr>
                                    <td colspan="5" style="text-align:center;color:var(--text-muted);padding:2rem;">
                                        Belum ada riwayat login.
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <div class="d-flex gap-1 justify-content-center mt-3">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="?status=<?= $filter ?>&page=<?= $i ?>"
                       style="width:36px;height:36px;display:flex;align-items:center;justify-content:center;border-radius:var(--radius-sm);font-size:0.82rem;font-weight:600;text-decoration:none;border:1px solid var(--border);
                              <?= $i===$page ? 'background:var(--accent);color:#0a0e17;border-color:var(--accent);' : 'color:var(--text-muted);' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>
            </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/script.js"></script>
</body>
</html>
