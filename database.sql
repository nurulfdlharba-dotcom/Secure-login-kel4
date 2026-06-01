-- ============================================================
-- DATABASE: secure_login
-- Aplikasi Login Aman dengan Hash SHA-256 + Salt
-- Tugas UAS Kriptografi
-- ============================================================

CREATE DATABASE IF NOT EXISTS secure_login
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE secure_login;

-- ============================================================
-- TABEL: users
-- Menyimpan data pengguna dengan salt dan hash password
-- PENTING: Password asli TIDAK pernah disimpan!
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username    VARCHAR(50)  NOT NULL UNIQUE,
    salt        VARCHAR(64)  NOT NULL COMMENT 'Salt acak 32 byte → hex 64 karakter',
    password_hash VARCHAR(64) NOT NULL COMMENT 'SHA-256(password + salt) → hex 64 karakter',
    failed_attempts TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Hitungan gagal login',
    locked_until DATETIME NULL COMMENT 'Akun dikunci sampai waktu ini',
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABEL: login_logs
-- Mencatat seluruh aktivitas login (berhasil maupun gagal)
-- ============================================================
CREATE TABLE IF NOT EXISTS login_logs (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username    VARCHAR(50)  NOT NULL,
    ip_address  VARCHAR(45)  NOT NULL COMMENT 'Mendukung IPv4 dan IPv6',
    status      ENUM('Berhasil','Gagal') NOT NULL,
    user_agent  VARCHAR(255) NULL COMMENT 'Browser / perangkat pengguna',
    login_time  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- DATA DEMO: Akun admin bawaan
-- Username: admin | Password: Admin@123
-- Salt    : a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2
-- Hash    : SHA-256("Admin@123" + salt di atas)
-- Gunakan halaman Register untuk membuat akun baru.
-- ============================================================

-- Index untuk performa query
CREATE INDEX idx_login_logs_username ON login_logs(username);
CREATE INDEX idx_login_logs_login_time ON login_logs(login_time);
CREATE INDEX idx_users_username ON users(username);
