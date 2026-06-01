<?php
// ============================================================
// crypto-demo.php — Demo Visual Proses Kriptografi SHA-256 + Salt
// Halaman terpenting untuk presentasi UAS!
// ============================================================
require_once 'config/koneksi.php';
requireLogin();

$remaining = max(0, SESSION_TIMEOUT - (time() - ($_SESSION['last_activity'] ?? time())));

// ============================================================
// Proses Demo ketika form disubmit
// Menampilkan seluruh tahap kriptografi secara transparan
// ============================================================
$demoResult = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['demo_password'])) {
    $demoPassword = $_POST['demo_password']; // Ambil input password demo

    // LANGKAH 1: Generate salt acak
    $demoSalt = generateSalt();

    // LANGKAH 2: Gabungkan password + salt
    $combined = $demoPassword . $demoSalt;

    // LANGKAH 3: Hash dengan SHA-256
    $demoHash = hash('sha256', $combined);

    // LANGKAH 4: Simulasi apa yang disimpan ke DB
    $demoResult = [
        'password'  => $demoPassword,
        'salt'      => $demoSalt,
        'combined'  => $combined,
        'hash'      => $demoHash,
        'pw_len'    => strlen($demoPassword),
        'salt_len'  => strlen($demoSalt),
        'hash_len'  => strlen($demoHash),
    ];

    // Demo verifikasi: hashing ulang untuk membuktikan password cocok
    $demoResult['verify_hash']   = hash('sha256', $demoPassword . $demoSalt);
    $demoResult['verify_match']  = hash_equals($demoHash, $demoResult['verify_hash']);

    // Demo salt berbeda → hash berbeda (membuktikan salt bekerja)
    $differentSalt  = generateSalt();
    $differentHash  = hash('sha256', $demoPassword . $differentSalt);
    $demoResult['diff_salt'] = $differentSalt;
    $demoResult['diff_hash'] = $differentHash;
    $demoResult['diff_match'] = ($demoHash === $differentHash); // Harus false!
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demo Kriptografi — SecureAuth</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="app-layout">
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="topbar">
            <h5 class="page-title">🧪 Demo Kriptografi SHA-256 + Salt</h5>
            <div class="topbar-right">
                <div class="session-timer" id="sessionTimer" data-remaining="<?= $remaining ?>"><span>--:--</span></div>
            </div>
        </div>

        <div class="page-body">

            <!-- Penjelasan Konsep -->
            <div class="panel mb-4" style="border-color:rgba(0,212,255,0.3);">
                <div class="panel-header"><span class="dot"></span>📚 Konsep Dasar</div>
                <div class="panel-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div style="background:rgba(0,212,255,0.06);border:1px solid var(--border);border-radius:var(--radius-sm);padding:1rem;height:100%;">
                                <div style="font-size:1.5rem;margin-bottom:0.5rem;">🎲</div>
                                <div style="font-weight:700;color:var(--accent);margin-bottom:0.35rem;">Salt</div>
                                <div style="font-size:0.82rem;color:var(--text-muted);line-height:1.6;">
                                    Data acak unik yang dibuat untuk setiap pengguna menggunakan <code>random_bytes(32)</code>.
                                    Memastikan dua user dengan password sama mendapat hash berbeda.
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div style="background:rgba(192,132,252,0.06);border:1px solid rgba(192,132,252,0.2);border-radius:var(--radius-sm);padding:1rem;height:100%;">
                                <div style="font-size:1.5rem;margin-bottom:0.5rem;">🔒</div>
                                <div style="font-weight:700;color:#c084fc;margin-bottom:0.35rem;">SHA-256</div>
                                <div style="font-size:0.82rem;color:var(--text-muted);line-height:1.6;">
                                    Fungsi hash satu arah (one-way). Output selalu 256 bit (64 hex).
                                    Tidak dapat dibalik. Perubahan kecil pada input → output berubah drastis.
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div style="background:rgba(0,230,118,0.06);border:1px solid rgba(0,230,118,0.2);border-radius:var(--radius-sm);padding:1rem;height:100%;">
                                <div style="font-size:1.5rem;margin-bottom:0.5rem;">🛡️</div>
                                <div style="font-weight:700;color:var(--success);margin-bottom:0.35rem;">Mengapa Aman?</div>
                                <div style="font-size:0.82rem;color:var(--text-muted);line-height:1.6;">
                                    Salt mencegah <em>rainbow table attack</em>.
                                    Hash mencegah pembacaan password langsung dari database.
                                    Password asli tidak pernah tersimpan.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Form Demo -->
            <div class="demo-form-card mb-4">
                <h6 style="font-weight:700;margin-bottom:1rem;">🔬 Coba Sendiri — Masukkan Password untuk Demo</h6>
                <form method="POST" action="">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-8">
                            <label class="form-label">Password Demo</label>
                            <div class="input-icon-wrap">
                                <span class="input-icon">🔑</span>
                                <input
                                    type="text"
                                    class="form-control"
                                    name="demo_password"
                                    placeholder="Contoh: MyPassword@123"
                                    value="<?= htmlspecialchars($_POST['demo_password'] ?? '') ?>"
                                    required
                                >
                            </div>
                            <small style="color:var(--text-muted);font-size:0.72rem;">
                                Password ini hanya untuk demo visual, tidak disimpan ke database.
                            </small>
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn-crypto" style="padding:0.65rem 1rem;">
                                ⚡ Generate Hash
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <?php if ($demoResult): ?>
            <!-- ============================================================
                 HASIL DEMO: Alur Kriptografi Lengkap
                 Ini yang paling penting untuk presentasi UAS!
            ============================================================ -->

            <div class="row g-4">
                <!-- Kolom Kiri: Alur SHA-256 + Salt -->
                <div class="col-lg-6">
                    <div class="panel">
                        <div class="panel-header">
                            <span class="dot"></span>
                            🔄 Alur Proses Kriptografi (Registrasi)
                        </div>
                        <div class="panel-body">
                            <div class="crypto-flow">

                                <!-- Step 1: Password Asli -->
                                <div class="crypto-step">
                                    <div class="step-num">Input</div>
                                    <div class="step-title">① Password Asli</div>
                                    <div class="step-value">
                                        <?= htmlspecialchars($demoResult['password']) ?>
                                    </div>
                                    <small style="color:var(--text-muted);font-size:0.7rem;">
                                        Panjang: <?= $demoResult['pw_len'] ?> karakter
                                    </small>
                                </div>

                                <div class="crypto-arrow">⬇️</div>

                                <!-- Step 2: Salt -->
                                <div class="crypto-step">
                                    <div class="step-num">random_bytes(32)</div>
                                    <div class="step-title">② Salt Acak Dibuat</div>
                                    <div class="step-value combined">
                                        <?= htmlspecialchars($demoResult['salt']) ?>
                                    </div>
                                    <small style="color:var(--text-muted);font-size:0.7rem;">
                                        <?= $demoResult['salt_len'] ?> karakter hex = 32 byte CSPRNG · <strong>Unik untuk setiap user!</strong>
                                    </small>
                                </div>

                                <div class="crypto-arrow">⬇️ <small style="font-size:0.7rem;color:var(--text-muted);">Digabungkan</small></div>

                                <!-- Step 3: Gabungan -->
                                <div class="crypto-step">
                                    <div class="step-num">Konkatenasi</div>
                                    <div class="step-title">③ Password + Salt</div>
                                    <div class="step-value combined" style="max-height:70px;overflow:hidden;">
                                        <?= htmlspecialchars(substr($demoResult['combined'], 0, 80)) ?>…
                                    </div>
                                    <small style="color:var(--text-muted);font-size:0.7rem;">
                                        "<?= htmlspecialchars($demoResult['password']) ?>" + salt
                                    </small>
                                </div>

                                <div class="crypto-arrow">⬇️ <small style="font-size:0.7rem;color:var(--text-muted);">SHA-256</small></div>

                                <!-- Step 4: Hash -->
                                <div class="crypto-step" style="border-color:rgba(0,212,255,0.3);">
                                    <div class="step-num">hash('sha256', combined)</div>
                                    <div class="step-title">④ Hash SHA-256 Dihasilkan</div>
                                    <div class="step-value hash">
                                        <?= htmlspecialchars($demoResult['hash']) ?>
                                    </div>
                                    <small style="color:var(--text-muted);font-size:0.7rem;">
                                        <?= $demoResult['hash_len'] ?> karakter = 256 bit · <strong style="color:var(--success);">One-way, tidak bisa dibalik!</strong>
                                    </small>
                                </div>

                                <div class="crypto-arrow">⬇️ <small style="font-size:0.7rem;color:var(--text-muted);">Disimpan ke DB</small></div>

                                <!-- Step 5: Yang Disimpan -->
                                <div class="crypto-step" style="border-color:rgba(255,214,10,0.3);">
                                    <div class="step-num">INSERT INTO users</div>
                                    <div class="step-title">⑤ Data yang Disimpan ke Database</div>
                                    <div class="db-cols">
                                        <div class="db-col">
                                            <div class="col-name">username</div>
                                            <div class="col-val v-user"><?= htmlspecialchars($_SESSION['username']) ?></div>
                                        </div>
                                        <div class="db-col">
                                            <div class="col-name">salt</div>
                                            <div class="col-val v-salt" style="font-size:0.65rem;"><?= htmlspecialchars(substr($demoResult['salt'],0,32))?>…</div>
                                        </div>
                                        <div class="db-col">
                                            <div class="col-name">password_hash</div>
                                            <div class="col-val v-hash" style="font-size:0.65rem;"><?= htmlspecialchars(substr($demoResult['hash'],0,32))?>…</div>
                                        </div>
                                        <div class="db-col">
                                            <div class="col-name">password (asli)</div>
                                            <div class="col-val v-no">❌ TIDAK ADA</div>
                                        </div>
                                    </div>
                                </div>

                            </div><!-- /.crypto-flow -->
                        </div>
                    </div>
                </div>

                <!-- Kolom Kanan: Verifikasi + Bukti Salt Efektif -->
                <div class="col-lg-6">

                    <!-- Proses Login / Verifikasi -->
                    <div class="panel mb-3">
                        <div class="panel-header">
                            <span class="dot" style="background:var(--success);box-shadow:0 0 8px var(--success);"></span>
                            🔓 Proses Verifikasi saat Login
                        </div>
                        <div class="panel-body">
                            <div style="font-size:0.82rem;color:var(--text-muted);margin-bottom:1rem;line-height:1.6;">
                                Saat login, sistem <strong>tidak membandingkan password langsung</strong>.
                                Sistem melakukan hashing ulang dan membandingkan hasilnya:
                            </div>

                            <div style="display:flex;flex-direction:column;gap:0.5rem;">
                                <div style="background:rgba(0,0,0,0.3);border:1px solid var(--border);border-radius:var(--radius-sm);padding:0.75rem;font-family:var(--font-mono);font-size:0.78rem;">
                                    <span style="color:var(--text-muted);">// Input login:</span><br>
                                    <span style="color:#c084fc;">$input</span> = <span style="color:var(--success);">"<?= htmlspecialchars($demoResult['password']) ?>"</span>
                                </div>
                                <div style="text-align:center;color:var(--text-muted);font-size:0.8rem;">↓ Ambil salt dari DB, hash ulang</div>
                                <div style="background:rgba(0,0,0,0.3);border:1px solid var(--border);border-radius:var(--radius-sm);padding:0.75rem;font-family:var(--font-mono);font-size:0.75rem;word-break:break-all;">
                                    <span style="color:var(--text-muted);">// Hasil hash ulang:</span><br>
                                    <span style="color:var(--accent);"><?= $demoResult['verify_hash'] ?></span>
                                </div>
                                <div style="text-align:center;color:var(--text-muted);font-size:0.8rem;">↓ Dibandingkan dengan hash di DB</div>
                                <div style="background:<?= $demoResult['verify_match'] ? 'rgba(0,230,118,0.1)' : 'rgba(255,77,109,0.1)' ?>;border:1px solid <?= $demoResult['verify_match'] ? 'rgba(0,230,118,0.3)' : 'rgba(255,77,109,0.3)' ?>;border-radius:var(--radius-sm);padding:0.75rem;text-align:center;font-weight:700;font-size:0.9rem;color:<?= $demoResult['verify_match'] ? 'var(--success)' : 'var(--danger)' ?>;">
                                    <?= $demoResult['verify_match'] ? '✅ COCOK — Login BERHASIL!' : '❌ TIDAK COCOK — Login GAGAL!' ?>
                                </div>
                            </div>

                            <div style="background:rgba(0,212,255,0.06);border:1px solid var(--border);border-radius:var(--radius-sm);padding:0.75rem;margin-top:1rem;font-size:0.78rem;color:var(--text-muted);">
                                💡 <strong style="color:var(--accent);">Kode PHP:</strong><br>
                                <code style="color:var(--text-primary);font-family:var(--font-mono);">
                                    $inputHash = hash('sha256', $password . $salt);<br>
                                    $ok = hash_equals($storedHash, $inputHash);
                                </code>
                            </div>
                        </div>
                    </div>

                    <!-- Bukti Salt Efektif -->
                    <div class="panel">
                        <div class="panel-header">
                            <span class="dot" style="background:var(--warning);box-shadow:0 0 8px var(--warning);"></span>
                            🧬 Bukti: Salt Berbeda → Hash Berbeda
                        </div>
                        <div class="panel-body">
                            <div style="font-size:0.82rem;color:var(--text-muted);margin-bottom:1rem;">
                                Password <strong style="color:var(--success);">"<?= htmlspecialchars($demoResult['password']) ?>"</strong>
                                dengan <strong>salt berbeda</strong> menghasilkan hash yang sama sekali berbeda:
                            </div>

                            <!-- Hash 1 -->
                            <div style="margin-bottom:0.75rem;">
                                <div style="font-size:0.7rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:1px;margin-bottom:0.3rem;">
                                    Hash dengan Salt #1 (dari demo di atas):
                                </div>
                                <div style="font-family:var(--font-mono);font-size:0.72rem;background:rgba(0,0,0,0.3);border:1px solid var(--border);border-radius:var(--radius-sm);padding:0.5rem 0.75rem;color:var(--accent);word-break:break-all;">
                                    <?= $demoResult['hash'] ?>
                                </div>
                            </div>

                            <!-- Hash 2 (salt berbeda) -->
                            <div style="margin-bottom:0.75rem;">
                                <div style="font-size:0.7rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:1px;margin-bottom:0.3rem;">
                                    Hash dengan Salt #2 (salt baru yang berbeda):
                                </div>
                                <div style="font-family:var(--font-mono);font-size:0.72rem;background:rgba(0,0,0,0.3);border:1px solid var(--border);border-radius:var(--radius-sm);padding:0.5rem 0.75rem;color:#c084fc;word-break:break-all;">
                                    <?= $demoResult['diff_hash'] ?>
                                </div>
                            </div>

                            <!-- Kesimpulan -->
                            <div style="background:rgba(255,214,10,0.08);border:1px solid rgba(255,214,10,0.3);border-radius:var(--radius-sm);padding:0.75rem;text-align:center;">
                                <div style="color:var(--warning);font-weight:700;margin-bottom:0.25rem;">
                                    <?= $demoResult['diff_match'] ? '⚠️ Hash sama (sangat jarang terjadi!)' : '✅ Hash BERBEDA — Salt bekerja dengan benar!' ?>
                                </div>
                                <div style="color:var(--text-muted);font-size:0.78rem;">
                                    Password sama + salt berbeda = hash BERBEDA<br>
                                    <strong>Inilah yang mencegah rainbow table attack!</strong>
                                </div>
                            </div>

                        </div>
                    </div>

                </div><!-- /.col-lg-6 -->
            </div><!-- /.row -->

            <?php else: ?>
            <!-- Placeholder sebelum demo dijalankan -->
            <div class="panel" style="border-style:dashed;">
                <div class="panel-body text-center" style="padding:3rem;">
                    <div style="font-size:3rem;margin-bottom:1rem;">🔬</div>
                    <h5 style="font-weight:700;color:var(--text-muted);">Masukkan password di atas untuk melihat proses kriptografi</h5>
                    <p style="color:var(--text-muted);font-size:0.85rem;">
                        Sistem akan menampilkan: salt yang dibuat, proses penggabungan,<br>
                        hash SHA-256 yang dihasilkan, dan data yang disimpan ke database.
                    </p>
                </div>
            </div>
            <?php endif; ?>

        </div><!-- /.page-body -->
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/script.js"></script>
</body>
</html>
