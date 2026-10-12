<?php
/**
 * Koneksi database - Sekretariat Admin Panel
 * Default disesuaikan untuk XAMPP (MySQL lokal).
 * Kalau setup MySQL kamu beda, tinggal ubah 4 baris di bawah ini.
 */

$DB_HOST = 'localhost';
$DB_NAME = 'sekretariat_db';
$DB_USER = 'root';
$DB_PASS = ''; // default XAMPP: kosong

try {
    $pdo = new PDO(
        "mysql:host={$DB_HOST};dbname={$DB_NAME};charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    die('Koneksi database gagal. Pastikan XAMPP (Apache & MySQL) sudah nyala dan database "sekretariat_db" ada. Detail: ' . $e->getMessage());
}
