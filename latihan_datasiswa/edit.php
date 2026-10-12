<?php
include 'koneksi.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) $_POST['id'];
    $nama = mysqli_real_escape_string($koneksi, $_POST['nama_anak']);
    $usia = mysqli_real_escape_string($koneksi, $_POST['usia_anak']);
    $hobi = mysqli_real_escape_string($koneksi, $_POST['hobi_anak']);

    mysqli_query($koneksi, "UPDATE anak SET nama_anak='$nama', usia_anak='$usia', hobi_anak='$hobi' WHERE nomor=$id");
    header('Location: index.php');
    exit;
}

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$result = mysqli_query($koneksi, "SELECT * FROM anak WHERE nomor=$id");
$row = mysqli_fetch_assoc($result);
if (!$row) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Data Siswa</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h1 class="judul-besar">Edit Data Siswa</h1>
    <form method="post" action="edit.php" class="pembungkus-form-edit">
        <input type="hidden" name="id" value="<?php echo $row['nomor']; ?>">
        <label>Nama:<br>
            <input type="text" name="nama_anak" value="<?php echo htmlspecialchars($row['nama_anak']); ?>">
        </label><br>
        <label>Usia:<br>
            <input type="text" name="usia_anak" value="<?php echo htmlspecialchars($row['usia_anak']); ?>">
        </label><br>
        <label>Hobi:<br>
            <input type="text" name="hobi_anak" value="<?php echo htmlspecialchars($row['hobi_anak']); ?>">
        </label><br><br>
        <button type="submit" class="tombol-simpan-data">Simpan</button>
        <button type="submit"  class="tombol-edit-data"><a href="index.php">Batal</a></button>
    </form>
</body>
</html>
