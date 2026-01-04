<?php
/**
 * File: db.php
 * Deskripsi: Koneksi ke database menggunakan PDO
 */

declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Konfigurasi koneksi database
$DB_HOST = '127.0.0.1';
$DB_NAME = 'uts_web';
$DB_USER = 'root';
$DB_PASS = '';

try {
    // Buat koneksi PDO
    $pdo = new PDO(
        "mysql:host={$DB_HOST};dbname={$DB_NAME};charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,       // Mode error: Exception
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,  // Hasil query berupa array asosiatif
        ]
    );
} catch (PDOException $e) {
    // Jangan tampilkan error detail ke user (keamanan)
    http_response_code(500);
    echo 'Database connection error.';
    exit;
}
