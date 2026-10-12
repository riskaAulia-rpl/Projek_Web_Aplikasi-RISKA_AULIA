<?php 
$host = 'localhost';
$username = 'root';
$password = '';
$dbname = 'sekretariat_db';

$koneksi = new mysqli($host, $username, $password, $dbname);

if ($koneksi->connect_error) {
    die ('Koneksi db gagal; ' . $koneksi->connect_error);
}

$koneksi->set_charset('utf8mb4');
?>