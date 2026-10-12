<?php
/**
 * Alat periksa database Pelangi Mart.
 * Buka: http://localhost/supermarket_mtk/tes_api.php
 * Menambah satu barang percobaan lalu menghapusnya lagi. Tidak menunggu lebih dari 5 detik.
 */
require __DIR__ . '/config.php';
header('Content-Type: text/plain; charset=utf-8');

function ms(float $t): string { return number_format((microtime(true) - $t) * 1000, 0, ',', '.') . ' ms'; }

try {
    $t = microtime(true);
    $pdo = koneksi();
    echo "1. Terhubung ke database      : " . ms($t) . "\n";

    $t = microtime(true);
    $n = count(ambil_produk());
    echo "2. Baca daftar ($n barang)    : " . ms($t) . "\n";

    $t = microtime(true);
    $id = 0;
    try {
        $pdo->prepare('INSERT INTO produk (nama, harga, rak, emoji) VALUES (?, ?, ?, ?)')->execute(['__tes__', 1000, 'Seafood', '🧪']);
        $id = (int) $pdo->lastInsertId();
        echo "3. Simpan 1 barang (INSERT)   : " . ms($t) . "\n";
    } catch (Throwable $e) {
        echo "3. Simpan 1 barang (INSERT)   : GAGAL setelah " . ms($t) . "\n   Pesan: " . $e->getMessage() . "\n";
    }

    if ($id) {
        $t = microtime(true);
        $pdo->prepare('DELETE FROM produk WHERE id = ?')->execute([$id]);
        echo "4. Hapus barang tes (DELETE)  : " . ms($t) . "\n";
    }

    echo "\nRak yang diizinkan: " . implode(', ', RAK) . "\n";

    if (DB_DRIVER === 'mysql') {
        echo "\n--- Koneksi MySQL yang sedang aktif ---\n";
        try {
            foreach ($pdo->query('SHOW FULL PROCESSLIST')->fetchAll() as $r) {
                echo sprintf("#%s %s | %s | %ss | %s | %s\n", $r['Id'], $r['User'], $r['Command'], $r['Time'], $r['State'] ?? '', mb_substr((string) ($r['Info'] ?? ''), 0, 70));
            }
        } catch (Throwable $e) { echo "(tidak bisa dibaca: " . $e->getMessage() . ")\n"; }

        echo "\n--- Transaksi yang menggantung (kalau ada) ---\n";
        try {
            $rows = $pdo->query('SELECT trx_mysql_thread_id AS th, trx_started AS mulai, trx_state AS st, trx_query AS q FROM information_schema.innodb_trx')->fetchAll();
            if (!$rows) echo "Tidak ada.\n";
            foreach ($rows as $r) {
                echo sprintf("koneksi #%s | mulai %s | %s | %s\n", $r['th'], $r['mulai'], $r['st'], mb_substr((string) $r['q'], 0, 70));
            }
        } catch (Throwable $e) { echo "(tidak bisa dibaca: " . $e->getMessage() . ")\n"; }
    }
    echo "\nSelesai.\n";
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
