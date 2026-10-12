<?php
/**
 * Pelangi Mart - API barang (JSON).
 *
 * GET  api.php?action=list      daftar barang
 * POST api.php  {"action":"add|update|delete|reset", ...}
 */
require __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function balas(array $data, int $kode = 200): void
{
    http_response_code($kode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/** Memeriksa dan merapikan data barang dari game. */
function validasi(array $in): array
{
    $nama = trim(preg_replace('/\s+/u', ' ', (string) ($in['name'] ?? '')));
    if ($nama === '') {
        throw new InvalidArgumentException('Nama barang tidak boleh kosong.');
    }
    $nama = mb_substr($nama, 0, 24);

    $harga = filter_var($in['price'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 100, 'max_range' => 999999999]]);
    if ($harga === false) {
        throw new InvalidArgumentException('Harga harus berupa angka bulat, minimal Rp 100.');
    }

    $rak = (string) ($in['cat'] ?? '');
    if (!in_array($rak, RAK, true)) {
        throw new InvalidArgumentException('Rak tidak dikenal.');
    }

    $emoji = trim((string) ($in['emoji'] ?? ''));
    if (preg_match('/^\X/u', $emoji, $m) && mb_strlen($m[0]) <= 8) {
        $emoji = $m[0];
    } else {
        $emoji = '🛒';
    }

    return [$nama, $harga, $rak, $emoji];
}

function ambil_satu(PDO $pdo, int $id): ?array
{
    $st = $pdo->prepare('SELECT id, nama, harga, rak, emoji FROM produk WHERE id = ?');
    $st->execute([$id]);
    $r = $st->fetch();
    return $r ? baris_ke_produk($r) : null;
}

try {
    $metode = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $in = [];

    if ($metode === 'POST') {
        // Hanya terima JSON supaya situs lain tidak bisa mengirim form diam-diam ke sini.
        $tipe = $_SERVER['CONTENT_TYPE'] ?? '';
        if (stripos($tipe, 'application/json') !== 0) {
            balas(['ok' => false, 'error' => 'Permintaan tidak valid.'], 415);
        }
        $in = json_decode(file_get_contents('php://input') ?: '', true);
        if (!is_array($in)) {
            balas(['ok' => false, 'error' => 'Data tidak terbaca.'], 400);
        }
        $aksi = (string) ($in['action'] ?? '');
    } else {
        $aksi = (string) ($_GET['action'] ?? 'list');
    }

    $pdo = koneksi();

    switch ($aksi) {
        case 'list':
            balas(['ok' => true, 'produk' => ambil_produk()]);

        case 'add':
            if ($metode !== 'POST') balas(['ok' => false, 'error' => 'Gunakan POST.'], 405);
            [$nama, $harga, $rak, $emoji] = validasi($in);
            $pdo->prepare('INSERT INTO produk (nama, harga, rak, emoji) VALUES (?, ?, ?, ?)')
                ->execute([$nama, $harga, $rak, $emoji]);
            balas(['ok' => true, 'produk' => ambil_satu($pdo, (int) $pdo->lastInsertId())], 201);

        case 'update':
            if ($metode !== 'POST') balas(['ok' => false, 'error' => 'Gunakan POST.'], 405);
            $id = filter_var($in['id'] ?? null, FILTER_VALIDATE_INT);
            if ($id === false || ambil_satu($pdo, $id) === null) {
                balas(['ok' => false, 'error' => 'Barang tidak ditemukan.'], 404);
            }
            [$nama, $harga, $rak, $emoji] = validasi($in);
            $pdo->prepare('UPDATE produk SET nama = ?, harga = ?, rak = ?, emoji = ? WHERE id = ?')
                ->execute([$nama, $harga, $rak, $emoji, $id]);
            balas(['ok' => true, 'produk' => ambil_satu($pdo, $id)]);

        case 'delete':
            if ($metode !== 'POST') balas(['ok' => false, 'error' => 'Gunakan POST.'], 405);
            $id = filter_var($in['id'] ?? null, FILTER_VALIDATE_INT);
            if ($id === false) {
                balas(['ok' => false, 'error' => 'Barang tidak ditemukan.'], 404);
            }
            $pdo->prepare('DELETE FROM produk WHERE id = ?')->execute([$id]);
            balas(['ok' => true]);

        case 'reset':
            if ($metode !== 'POST') balas(['ok' => false, 'error' => 'Gunakan POST.'], 405);
            reset_produk($pdo);
            balas(['ok' => true, 'produk' => ambil_produk()]);

        default:
            balas(['ok' => false, 'error' => 'Aksi tidak dikenal.'], 400);
    }
} catch (InvalidArgumentException $e) {
    balas(['ok' => false, 'error' => $e->getMessage()], 422);
} catch (Throwable $e) {
    balas(['ok' => false, 'error' => 'Tidak bisa menyimpan ke database. Pastikan MySQL di XAMPP sudah menyala.'], 500);
}
