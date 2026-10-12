<?php
$host       = "localhost";
$user       = "root";
$password   = ""; // Kosongkan kalau pakai XAMPP bawaan
$database   = "db_bkjl";

// Membuat koneksi ke database
$koneksi = mysqli_connect($host, $user, $password, $database);

// Memeriksa apakah koneksi berhasil
if (!$koneksi) {
    die("Koneksi ke database gagal: " . mysqli_connect_error());
}
// Pastikan koneksi menggunakan charset UTF-8
mysqli_set_charset($koneksi, "utf8mb4");
?>