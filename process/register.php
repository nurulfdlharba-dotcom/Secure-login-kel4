<?php
// ============================================================
// process/register.php
// Memproses form registrasi: membuat salt + hash SHA-256
// ============================================================

require_once '../config/koneksi.php';
startSecureSession();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../register.php');
    exit;
}

// --- Ambil input ---
$username        = sanitize($_POST['username'] ?? '');
$password        = $_POST['password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

// --- Validasi: field tidak boleh kosong ---
if (empty($username) || empty($password) || empty($confirmPassword)) {
    header('Location: ../register.php?error=empty_fields');
    exit;
}

// --- Validasi: panjang username ---
if (strlen($username) < 3 || strlen($username) > 50) {
    header('Location: ../register.php?error=username_length');
    exit;
}

// --- Validasi: username hanya boleh huruf, angka, underscore ---
if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
    header('Location: ../register.php?error=username_invalid');
    exit;
}

// --- Validasi: password minimal 8 karakter ---
if (strlen($password) < 8) {
    header('Location: ../register.php?error=password_short');
    exit;
}

// --- Validasi: password harus mengandung huruf besar, kecil, angka ---
if (!preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password)) {
    header('Location: ../register.php?error=password_weak');
    exit;
}

// --- Validasi: konfirmasi password harus sama ---
if ($password !== $confirmPassword) {
    header('Location: ../register.php?error=password_mismatch');
    exit;
}

$conn = getConnection();

// --- Cek apakah username sudah dipakai ---
$checkStmt = $conn->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
$checkStmt->bind_param('s', $username);
$checkStmt->execute();
$checkStmt->store_result();

if ($checkStmt->num_rows > 0) {
    $checkStmt->close();
    $conn->close();
    header('Location: ../register.php?error=username_taken');
    exit;
}
$checkStmt->close();

// ============================================================
// PROSES KRIPTOGRAFI - INTI REGISTRASI
//
// LANGKAH 1: Buat salt acak menggunakan random_bytes()
//   - random_bytes(32) → 32 byte data acak (dari CSPRNG OS)
//   - bin2hex() → konversi ke string hex 64 karakter
//   - Setiap user mendapat salt UNIK dan BERBEDA
//
// LANGKAH 2: Hash password + salt dengan SHA-256
//   - SHA-256(password + salt) → string hex 64 karakter
//   - Salt memastikan dua user dengan password sama
//     mendapat hash yang BERBEDA
//
// LANGKAH 3: Simpan hanya salt dan hash ke database
//   - Password ASLI tidak pernah disimpan!
// ============================================================

$salt         = generateSalt();                         // LANGKAH 1
$passwordHash = hashPassword($password, $salt);         // LANGKAH 2

// --- Simpan ke database (LANGKAH 3) ---
$insertStmt = $conn->prepare(
    'INSERT INTO users (username, salt, password_hash) VALUES (?, ?, ?)'
);
$insertStmt->bind_param('sss', $username, $salt, $passwordHash);

if ($insertStmt->execute()) {
    $insertStmt->close();
    $conn->close();
    // Simpan info untuk ditampilkan di halaman sukses
    $_SESSION['reg_success'] = $username;
    header('Location: ../index.php?success=registered');
    exit;
} else {
    $insertStmt->close();
    $conn->close();
    header('Location: ../register.php?error=db_error');
    exit;
}
