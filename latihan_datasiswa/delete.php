<?php
include 'koneksi.php';

if (isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    mysqli_query($koneksi, "DELETE FROM anak WHERE nomor=$id");
}

header('Location: index.php');
exit;
