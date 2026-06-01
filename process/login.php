<?php
// ============================================================
// process/login.php
// Memproses form login: verifikasi password dengan SHA-256 + salt
// ============================================================

require_once '../config/koneksi.php';
startSecureSession();

// Tolak akses langsung bukan dari POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php');
    exit;
}

// --- Ambil dan sanitasi input ---
$username  = sanitize($_POST['username'] ?? '');
$password  = $_POST['password'] ?? '';  // Jangan sanitasi password agar karakter khusus tetap valid
$ip        = getClientIP();
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

// --- Validasi input kosong ---
if (empty($username) || empty($password)) {
    header('Location: ../index.php?error=empty_fields');
    exit;
}

$conn = getConnection();

// ============================================================
// LANGKAH 1: Cari user di database berdasarkan username
// ============================================================
$stmt = $conn->prepare(
    'SELECT id, username, salt, password_hash, failed_attempts, locked_until 
     FROM users WHERE username = ? LIMIT 1'
);
$stmt->bind_param('s', $username);
$stmt->execute();
$result = $stmt->get_result();
$user   = $result->fetch_assoc();
$stmt->close();

// ============================================================
// LANGKAH 2: Cek apakah akun ada
// Gunakan pesan error generik agar tidak bocorkan info (username valid/tidak)
// ============================================================
if (!$user) {
    // Catat percobaan gagal meskipun username tidak ada
    logLoginAttempt($conn, $username, $ip, $userAgent, 'Gagal');
    header('Location: ../index.php?error=invalid_credentials');
    exit;
}

// ============================================================
// LANGKAH 3: Cek apakah akun sedang dikunci
// ============================================================
if ($user['locked_until'] !== null) {
    $lockTime = strtotime($user['locked_until']);
    if (time() < $lockTime) {
        $remaining = ceil(($lockTime - time()) / 60);
        logLoginAttempt($conn, $username, $ip, $userAgent, 'Gagal');
        header("Location: ../index.php?error=account_locked&minutes=$remaining");
        exit;
    } else {
        // Kunci sudah expired, reset
        $reset = $conn->prepare('UPDATE users SET failed_attempts=0, locked_until=NULL WHERE id=?');
        $reset->bind_param('i', $user['id']);
        $reset->execute();
        $reset->close();
        $user['failed_attempts'] = 0;
        $user['locked_until']    = null;
    }
}

// ============================================================
// LANGKAH 4: VERIFIKASI PASSWORD dengan SHA-256 + Salt
//
// Proses di balik verifyPassword():
//   input_hash = SHA-256(password_input + salt_dari_db)
//   bandingkan input_hash dengan password_hash di database
//   Jika cocok → login berhasil
// ============================================================
$isValid = verifyPassword($password, $user['salt'], $user['password_hash']);

if ($isValid) {
    // ✅ LOGIN BERHASIL

    // Regenerasi session ID untuk mencegah session fixation attack
    session_regenerate_id(true);

    // Simpan data user di session
    $_SESSION['user_id']      = $user['id'];
    $_SESSION['username']     = $user['username'];
    $_SESSION['login_time']   = date('Y-m-d H:i:s');
    $_SESSION['last_activity'] = time();

    // Reset hitungan gagal login
    $resetFail = $conn->prepare('UPDATE users SET failed_attempts=0, locked_until=NULL WHERE id=?');
    $resetFail->bind_param('i', $user['id']);
    $resetFail->execute();
    $resetFail->close();

    // Catat log login berhasil
    logLoginAttempt($conn, $username, $ip, $userAgent, 'Berhasil');

    $conn->close();
    header('Location: ../dashboard.php');
    exit;

} else {
    // ❌ LOGIN GAGAL

    $newFailed = $user['failed_attempts'] + 1;

    if ($newFailed >= MAX_FAILED_ATTEMPTS) {
        // Kunci akun selama LOCK_DURATION menit
        $lockUntil = date('Y-m-d H:i:s', strtotime('+' . LOCK_DURATION . ' minutes'));
        $lockStmt  = $conn->prepare(
            'UPDATE users SET failed_attempts=?, locked_until=? WHERE id=?'
        );
        $lockStmt->bind_param('isi', $newFailed, $lockUntil, $user['id']);
        $lockStmt->execute();
        $lockStmt->close();

        logLoginAttempt($conn, $username, $ip, $userAgent, 'Gagal');
        $conn->close();
        header("Location: ../index.php?error=account_locked&minutes=" . LOCK_DURATION);
        exit;
    } else {
        // Update hitungan gagal
        $failStmt = $conn->prepare('UPDATE users SET failed_attempts=? WHERE id=?');
        $failStmt->bind_param('ii', $newFailed, $user['id']);
        $failStmt->execute();
        $failStmt->close();

        logLoginAttempt($conn, $username, $ip, $userAgent, 'Gagal');
        $conn->close();
        $remaining = MAX_FAILED_ATTEMPTS - $newFailed;
        header("Location: ../index.php?error=invalid_credentials&attempts_left=$remaining");
        exit;
    }
}

// ============================================================
// Fungsi helper: catat log ke tabel login_logs
// ============================================================
function logLoginAttempt(mysqli $conn, string $username, string $ip, string $ua, string $status): void {
    $stmt = $conn->prepare(
        'INSERT INTO login_logs (username, ip_address, status, user_agent) VALUES (?, ?, ?, ?)'
    );
    $stmt->bind_param('ssss', $username, $ip, $status, $ua);
    $stmt->execute();
    $stmt->close();
}
