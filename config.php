<?php
/**
 * Pelangi Mart - pengaturan database dan fungsi bersama.
 *
 * Cara pakai di XAMPP: nyalakan Apache dan MySQL, lalu buka
 * http://localhost/supermarket_mtk/ . Database dan tabel dibuat otomatis
 * saat halaman pertama kali dibuka.
 */

// ---- Pengaturan database (ubah di sini kalau perlu) ----
const DB_DRIVER = 'mysql';            // 'mysql' (XAMPP) atau 'sqlite' (tanpa MySQL)
const DB_HOST   = '127.0.0.1';        // pakai IP, bukan 'localhost', supaya tidak lambat di Windows
const DB_PORT   = 3306;
const DB_NAME   = 'supermarket_mtk';
const DB_USER   = 'root';             // bawaan XAMPP
const DB_PASS   = '';                 // bawaan XAMPP: kosong
const DB_SQLITE_FILE = __DIR__ . '/supermarket.sqlite';

// Daftar rak yang boleh dipakai (harus sama dengan di game).
const RAK = ['Minuman', 'Camilan', 'Buah & Sayur', 'Seafood', 'Roti & Susu', 'Rumah Tangga'];

/** Barang awal. Dipakai saat tabel pertama kali dibuat dan saat tombol "Kembalikan barang awal". */
function daftar_awal(): array
{
    return [
        ['Susu Stroberi', 6500, 'Minuman', '🥛'],
        ['Jus Jeruk', 5000, 'Minuman', '🧃'],
        ['Boba Cokelat', 12000, 'Minuman', '🧋'],
        ['Air Mineral', 3000, 'Minuman', '💧'],
        ['Kopi Susu', 8000, 'Minuman', '☕'],
        ['Popcorn', 7000, 'Camilan', '🍿'],
        ['Cokelat Batang', 9500, 'Camilan', '🍫'],
        ['Lolipop', 2500, 'Camilan', '🍭'],
        ['Biskuit Mentega', 4500, 'Camilan', '🍪'],
        ['Permen Jelly', 3500, 'Camilan', '🍬'],
        ['Apel Merah', 4000, 'Buah & Sayur', '🍎'],
        ['Pisang Sesisir', 12000, 'Buah & Sayur', '🍌'],
        ['Stroberi', 15000, 'Buah & Sayur', '🍓'],
        ['Wortel', 3000, 'Buah & Sayur', '🥕'],
        ['Semangka Mini', 20000, 'Buah & Sayur', '🍉'],
        ['Brokoli', 8500, 'Buah & Sayur', '🥦'],
        ['Udang Segar', 25000, 'Seafood', '🦐'],
        ['Cumi-cumi', 28000, 'Seafood', '🦑'],
        ['Kepiting', 45000, 'Seafood', '🦀'],
        ['Lobster', 85000, 'Seafood', '🦞'],
        ['Ikan Kembung', 18000, 'Seafood', '🐟'],
        ['Ikan Salmon', 42000, 'Seafood', '🐠'],
        ['Gurita', 38000, 'Seafood', '🐙'],
        ['Kerang', 15000, 'Seafood', '🦪'],
        ['Roti Tawar', 14000, 'Roti & Susu', '🍞'],
        ['Croissant', 9000, 'Roti & Susu', '🥐'],
        ['Cupcake', 8000, 'Roti & Susu', '🧁'],
        ['Keju Slice', 11000, 'Roti & Susu', '🧀'],
        ['Telur Ayam', 22000, 'Roti & Susu', '🥚'],
        ['Donat Gula', 6000, 'Roti & Susu', '🍩'],
        ['Sabun Mandi', 5500, 'Rumah Tangga', '🧼'],
        ['Tisu Gulung', 9000, 'Rumah Tangga', '🧻'],
        ['Sampo', 18000, 'Rumah Tangga', '🧴'],
        ['Sikat Gigi', 7000, 'Rumah Tangga', '🪥'],
        ['Spons Cuci', 3500, 'Rumah Tangga', '🧽'],
    ];
}

/** Membuka koneksi database. Database dan tabel dibuat otomatis kalau belum ada. */
function koneksi(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $opsi = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    if (DB_DRIVER === 'sqlite') {
        $pdo = new PDO('sqlite:' . DB_SQLITE_FILE, null, null, $opsi);
    } else {
        $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';charset=utf8mb4';
        try {
            // Jalur cepat: database sudah ada, langsung terhubung ke sana.
            $pdo = new PDO($dsn . ';dbname=' . DB_NAME, DB_USER, DB_PASS, $opsi);
        } catch (PDOException $e) {
            // Database belum ada: buat dulu, lalu pakai.
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $opsi);
            try {
                $pdo->exec('CREATE DATABASE IF NOT EXISTS `' . DB_NAME . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
            } catch (PDOException $e2) {
                // Tidak punya izin membuat database: lanjut, mungkin database sudah ada.
            }
            $pdo->exec('USE `' . DB_NAME . '`');
        }
        // Kalau tabel sedang terkunci, jangan menunggu berpuluh detik: gagal cepat dan beri pesan jelas.
        try {
            $pdo->exec('SET SESSION innodb_lock_wait_timeout = 5, SESSION lock_wait_timeout = 5');
        } catch (PDOException $e) {
            // Server lama yang tidak mengenal pengaturan ini: abaikan.
        }
    }

    siapkan_tabel($pdo);
    return $pdo;
}

/** Membuat tabel produk (dan mengisi barang awal) hanya kalau tabelnya belum ada. */
function siapkan_tabel(PDO $pdo): void
{
    if (DB_DRIVER === 'sqlite') {
        $ada = (bool) $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='produk'")->fetchColumn();
    } else {
        $ada = (bool) $pdo->query("SHOW TABLES LIKE 'produk'")->fetchColumn();
    }
    if ($ada) {
        return;
    }

    if (DB_DRIVER === 'sqlite') {
        $pdo->exec("CREATE TABLE produk (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nama TEXT NOT NULL,
            harga INTEGER NOT NULL,
            rak TEXT NOT NULL,
            emoji TEXT NOT NULL DEFAULT '🛒',
            dibuat TEXT DEFAULT CURRENT_TIMESTAMP
        )");
    } else {
        $pdo->exec("CREATE TABLE produk (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            nama VARCHAR(24) NOT NULL,
            harga INT UNSIGNED NOT NULL,
            rak VARCHAR(32) NOT NULL,
            emoji VARCHAR(16) NOT NULL DEFAULT '🛒',
            dibuat TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }
    isi_awal($pdo);
}

function isi_awal(PDO $pdo): void
{
    $stmt = $pdo->prepare('INSERT INTO produk (nama, harga, rak, emoji) VALUES (?, ?, ?, ?)');
    $pdo->beginTransaction();
    foreach (daftar_awal() as $b) {
        $stmt->execute($b);
    }
    $pdo->commit();
}

/** Mengosongkan tabel lalu mengisi barang awal lagi. */
function reset_produk(PDO $pdo): void
{
    if (DB_DRIVER === 'sqlite') {
        $pdo->exec('DELETE FROM produk');
        $pdo->exec("DELETE FROM sqlite_sequence WHERE name='produk'");
    } else {
        $pdo->exec('TRUNCATE TABLE produk');
    }
    isi_awal($pdo);
}

/** Mengubah satu baris database menjadi bentuk yang dipakai game. */
function baris_ke_produk(array $r): array
{
    return [
        'id'    => (string) $r['id'],
        'name'  => $r['nama'],
        'price' => (int) $r['harga'],
        'cat'   => $r['rak'],
        'emoji' => $r['emoji'],
    ];
}

function ambil_produk(): array
{
    $baris = koneksi()->query('SELECT id, nama, harga, rak, emoji FROM produk ORDER BY id')->fetchAll();
    return array_map('baris_ke_produk', $baris);
}

/** Halaman error yang ramah kalau database tidak bisa dibuka. */
function halaman_error(Throwable $e): void
{
    http_response_code(500);
    $pesan = htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
    echo '<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Pelangi Mart</title>'
        . '<style>body{margin:0;min-height:100vh;display:grid;place-items:center;background:#FFF5FA;color:#46356E;font:600 17px/1.55 system-ui,sans-serif;padding:24px;box-sizing:border-box}'
        . '.k{background:#fff;border:2px solid #EFE0F7;border-radius:26px;padding:28px 32px;max-width:620px;box-shadow:0 8px 0 #EAE3FF}'
        . 'h1{margin:0 0 8px;font-size:28px}ol{padding-left:22px}code{background:#EAE3FF;border-radius:8px;padding:2px 8px;font-size:14px;word-break:break-word}</style></head><body><div class="k">'
        . '<h1>Database belum bisa dibuka</h1><p>Pelangi Mart butuh database untuk menyimpan barang. Coba langkah ini:</p><ol>'
        . '<li>Buka XAMPP Control Panel, lalu klik <b>Start</b> pada <b>MySQL</b> (dan Apache).</li>'
        . '<li>Cek nama pengguna dan kata sandi di <code>config.php</code>. Bawaan XAMPP: <code>root</code> dan kosong.</li>'
        . '<li>Muat ulang halaman ini.</li></ol>'
        . '<p>Pesan teknis: <code>' . $pesan . '</code></p></div></body></html>';
    exit;
}