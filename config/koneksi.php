<?php
// ============================================================
// config/koneksi.php
// Konfigurasi koneksi database MySQL
// ============================================================

// --- Konfigurasi Database ---
define('DB_HOST', 'localhost');
define('DB_USER', 'root');        // Ganti sesuai user MySQL Anda
define('DB_PASS', '');            // Ganti sesuai password MySQL Anda
define('DB_NAME', 'secure_login');
define('DB_CHARSET', 'utf8mb4');

// --- Konfigurasi Session ---
define('SESSION_TIMEOUT', 900);   // 15 menit dalam detik
define('MAX_FAILED_ATTEMPTS', 3); // Maksimal gagal login sebelum akun dikunci
define('LOCK_DURATION', 15);      // Durasi kunci akun dalam menit

// ============================================================
// Fungsi koneksi menggunakan MySQLi (berorientasi objek)
// Mengembalikan objek $conn yang dipakai di seluruh aplikasi
// ============================================================
function getConnection(): mysqli {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    // Cek error koneksi
    if ($conn->connect_error) {
        // Tampilkan pesan ramah, jangan bocorkan detail teknis ke pengguna
        die('<div style="font-family:sans-serif;padding:2rem;color:#dc3545;">
             <h3>⚠️ Koneksi Database Gagal</h3>
             <p>Pastikan MySQL aktif dan konfigurasi di <code>config/koneksi.php</code> sudah benar.</p>
             </div>');
    }

    // Set charset agar mendukung karakter internasional
    $conn->set_charset(DB_CHARSET);

    return $conn;
}

// ============================================================
// Fungsi manajemen session yang aman
// ============================================================
function startSecureSession(): void {
    // Konfigurasi session sebelum start
    ini_set('session.cookie_httponly', 1);    // Cookie tidak bisa diakses JavaScript
    ini_set('session.use_strict_mode', 1);     // Tolak session ID yang tidak valid
    ini_set('session.cookie_samesite', 'Strict'); // Proteksi CSRF

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

// ============================================================
// Cek apakah user sudah login dan session belum expired
// Redirect ke index.php jika belum login
// ============================================================
function requireLogin(): void {
    startSecureSession();

    if (!isset($_SESSION['user_id'])) {
        header('Location: ../index.php?msg=login_required');
        exit;
    }

    // Cek session timeout (15 menit tidak aktif)
    if (isset($_SESSION['last_activity'])) {
        $elapsed = time() - $_SESSION['last_activity'];
        if ($elapsed > SESSION_TIMEOUT) {
            // Hapus session dan redirect
            session_unset();
            session_destroy();
            header('Location: ../index.php?msg=session_expired');
            exit;
        }
    }

    // Perbarui waktu aktivitas terakhir
    $_SESSION['last_activity'] = time();
}

// ============================================================
// FUNGSI INTI KRIPTOGRAFI
// Membuat hash SHA-256 dari password + salt
// Inilah inti dari keamanan aplikasi ini!
// ============================================================

/**
 * Membuat salt acak 32 byte menggunakan random_bytes()
 * random_bytes() menghasilkan byte acak yang kuat secara kriptografi (CSPRNG)
 * Dikonversi ke hex agar bisa disimpan di database (64 karakter)
 */
function generateSalt(): string {
    return bin2hex(random_bytes(32)); // 32 byte = 64 karakter hex
}

/**
 * Membuat hash SHA-256 dari kombinasi password + salt
 * 
 * Proses:
 * 1. Gabungkan: password + salt
 * 2. Hash dengan SHA-256
 * 3. Hasil: string hex 64 karakter
 * 
 * Mengapa SHA-256?
 * - Output tetap 256 bit (64 hex) apapun inputnya
 * - One-way: tidak bisa dibalik (irreversible)
 * - Perubahan kecil pada input → output berubah drastis (avalanche effect)
 */
function hashPassword(string $password, string $salt): string {
    $combined = $password . $salt;       // Gabungkan password dan salt
    return hash('sha256', $combined);    // Hash menggunakan SHA-256
}

/**
 * Verifikasi password saat login
 * 
 * Proses verifikasi:
 * 1. Ambil salt dari database berdasarkan username
 * 2. Hash password yang diinputkan dengan salt tersebut
 * 3. Bandingkan hasil hash dengan hash yang tersimpan di database
 * 4. Jika sama → login berhasil
 */
function verifyPassword(string $inputPassword, string $storedSalt, string $storedHash): bool {
    $inputHash = hashPassword($inputPassword, $storedSalt);
    // hash_equals() mencegah timing attack
    return hash_equals($storedHash, $inputHash);
}

/**
 * Sanitasi input untuk mencegah XSS
 */
function sanitize(string $input): string {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

/**
 * Mendapatkan IP address pengguna
 * Mempertimbangkan proxy dan load balancer
 */
function getClientIP(): string {
    $keys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
    foreach ($keys as $key) {
        if (!empty($_SERVER[$key])) {
            $ip = trim(explode(',', $_SERVER[$key])[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }
    return '0.0.0.0';
}
