<?php
include 'koneksi.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Siswa</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h1 class="judul-besar">Data Siswa Rpl B</h1>
    <button class="tambah-anak"><a href="tambah.php">➕ Tambah Siswa</a></button>
    <table class="pembungkus-tabel" border="2">
        <tr>
            <th>No</th>
            <th>Nama</th>
            <th>Usia</th>
            <th>Hobi</th>
            <th>Aksi</th>
        </tr>

         <?php 
            $query = mysqli_query($koneksi, "SELECT * FROM anak");
            while ($row = mysqli_fetch_assoc($query)) {
                ?>
        <tr>
            <td><?php echo $row['nomor']; ?></td>
            <td><?php echo $row['nama_anak']; ?></td>
            <td><?php echo $row['usia_anak']; ?></td>
            <td><?php echo $row['hobi_anak']; ?></td>
            <td class="tombol-aksi">
                <a class="edit" href="edit.php?id=<?php echo $row['nomor']; ?>">Edit</a>
                &nbsp;|&nbsp;
                <a class="hapus" href="delete.php?id=<?php echo $row['nomor']; ?>" onclick="return confirm('Yakin ingin menghapus data ini?')">Hapus</a>
            </td>
        </tr>
        <?php
            }
        ?>
    </table>
</body>
</html>