<?php
// ============================================================
// includes/sidebar.php
// Sidebar navigasi yang dipakai di semua halaman dashboard
// ============================================================

// Tentukan halaman aktif
$currentPage = basename($_SERVER['PHP_SELF']);
$username    = $_SESSION['username'] ?? 'User';
$initial     = strtoupper(substr($username, 0, 1));
?>
<aside class="sidebar" id="sidebar">

    <!-- Brand -->
    <div class="sidebar-brand">
        <div class="d-flex align-items-center">
            <div class="logo-icon">🔐</div>
            <span>Secure<em>Auth</em></span>
        </div>
        <small style="color:var(--text-muted);font-size:0.68rem;margin-top:0.25rem;display:block;">
            Kriptografi UAS — SHA-256 + Salt
        </small>
    </div>

    <!-- Info User -->
    <div class="sidebar-user d-flex align-items-center">
        <div class="avatar"><?= htmlspecialchars($initial) ?></div>
        <div>
            <div class="name"><?= htmlspecialchars($username) ?></div>
            <div class="role">● Online</div>
        </div>
    </div>

    <!-- Navigasi -->
    <nav class="sidebar-nav">
        <div class="nav-section">Utama</div>

        <a href="dashboard.php" class="<?= $currentPage === 'dashboard.php' ? 'active' : '' ?>">
            <span class="nav-icon">🏠</span> Dashboard
        </a>
        <a href="profile.php" class="<?= $currentPage === 'profile.php' ? 'active' : '' ?>">
            <span class="nav-icon">👤</span> Profil Saya
        </a>
        <a href="login-history.php" class="<?= $currentPage === 'login-history.php' ? 'active' : '' ?>">
            <span class="nav-icon">📋</span> Riwayat Login
        </a>

        <div class="nav-section">Kriptografi</div>

        <a href="crypto-demo.php" class="<?= $currentPage === 'crypto-demo.php' ? 'active' : '' ?>">
            <span class="nav-icon">🧪</span> Demo Kriptografi
        </a>
    </nav>

    <!-- Footer Sidebar -->
    <div class="sidebar-footer">
        <a href="process/logout.php" style="display:flex;align-items:center;gap:0.6rem;color:var(--danger);text-decoration:none;font-size:0.85rem;font-weight:600;padding:0.5rem 0.75rem;border-radius:var(--radius-sm);transition:background 0.2s;" onmouseover="this.style.background='rgba(255,77,109,0.1)'" onmouseout="this.style.background='none'">
            <span>🚪</span> Logout
        </a>
    </div>

</aside>
