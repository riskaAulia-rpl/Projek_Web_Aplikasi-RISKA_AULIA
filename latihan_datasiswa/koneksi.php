<?php
$host = "localhost";
$username = "root";
$password = "";
$dbname = "dataanak_db";

$koneksi = new mysqli($host, $username, $password, $dbname);

if ($koneksi->connect_error) {
    die("Koneksi Gagal: " . $koneksi->connect_error);
}
?>