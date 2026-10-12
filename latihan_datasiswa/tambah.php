<?php
include 'koneksi.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nomor = mysqli_real_escape_string($koneksi, $_POST['nomor']);
    $nama = mysqli_real_escape_string($koneksi, $_POST['nama_anak']);
    $usia = mysqli_real_escape_string($koneksi, $_POST['usia_anak']);
    $hobi = mysqli_real_escape_string($koneksi, $_POST['hobi_anak']);

    mysqli_query($koneksi, "INSERT INTO anak (nomor, nama_anak, usia_anak, hobi_anak) VALUES ('$nomor', $nama', '$usia', '$hobi')");
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Data Siswa</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h1 class="judul-besar">Tambah Data Siswa</h1>
    <form method="post" action="tambah.php">
        <label>No:</label><br>
        <input type="text" name="nomor" required><br>
        <label>Nama:<br>
            <input type="text" name="nama_anak" required>
        </label><br>
        <label>Usia:<br>
            <input type="text" name="usia_anak" required>
        </label><br>
        <label>Hobi:<br>
            <input type="text" name="hobi_anak">
        </label><br><br>
        <button type="submit">Tambah</button>
        <a href="index.php">Batal</a>
    </form>
</body>
</html>
