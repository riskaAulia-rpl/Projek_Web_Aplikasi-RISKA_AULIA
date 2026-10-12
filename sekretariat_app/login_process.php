<?php
/**
 * Proses login - dipanggil via fetch() dari login.php
 * Mengembalikan JSON: { success: bool, message: string }
 *
 * Catatan: login dibatasi otomatis hanya untuk akun yang ADA di tabel users.
 * Karena tabel users cuma diisi 3 akun (lewat setup awal), login otomatis
 * hanya berlaku untuk 3 orang itu. Jangan buat form pendaftaran akun baru
 * di aplikasi ini kalau memang ingin tetap dibatasi 3 orang.
 */

session_start();
header('Content-Type: application/json');
require 'koneksi.php';
require 'catat_audit.php';

/**
 * Deteksi OS & browser secara kasar dari User-Agent, dipakai untuk
 * mencatat "perangkat" di login_log. Sengaja sederhana (bukan library
 * penuh) karena cuma untuk ditampilkan, bukan untuk keputusan keamanan.
 */
function deteksiPerangkatDariUserAgent(string $ua): string {
    if ($ua === '') return 'Perangkat Tidak Dikenal';

    if (preg_match('/Windows/i', $ua)) $os = 'Windows';
    elseif (preg_match('/Mac OS X/i', $ua)) $os = 'macOS';
    elseif (preg_match('/Android/i', $ua)) $os = 'Android';
    elseif (preg_match('/iPhone|iPad|iPod/i', $ua)) $os = 'iOS';
    elseif (preg_match('/Linux/i', $ua)) $os = 'Linux';
    else $os = 'Perangkat Tidak Dikenal';

    if (preg_match('/Edg\//i', $ua)) $browser = 'Edge';
    elseif (preg_match('/OPR\//i', $ua) || preg_match('/Opera/i', $ua)) $browser = 'Opera';
    elseif (preg_match('/Chrome\//i', $ua) && !preg_match('/Edg\//i', $ua)) $browser = 'Chrome';
    elseif (preg_match('/Firefox\//i', $ua)) $browser = 'Firefox';
    elseif (preg_match('/Safari\//i', $ua) && !preg_match('/Chrome/i', $ua)) $browser = 'Safari';
    else $browser = 'Browser Tidak Dikenal';

    return "{$os} · {$browser}";
}

$username = trim($_POST['username'] ?? '');
$password = trim($_POST['password'] ?? '');

if ($username === '' || $password === '') {
    echo json_encode(['success' => false, 'message' => 'Username dan kata sandi wajib diisi']);
    exit;
}

$stmt = $koneksi->prepare('SELECT id, username, password, nama FROM users WHERE username = ? LIMIT 1');
$stmt->bind_param('s', $username);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if ($user && password_verify($password, $user['password'])) {
    // Cegah session fixation
    session_regenerate_id(true);

    $_SESSION['user_id']  = $user['id'];
    $_SESSION['username'] = $user['username'];

    // ---------- CATAT LOGIN INI DI SERVER (bukan cuma di localStorage) ----------
    // Ini yang membuat "Login Terakhir" di profil.php benar-benar bisa
    // dipercaya: siapapun & dari perangkat manapun yang login ke akun ini,
    // tercatat di database, sehingga terlihat oleh pemilik akun yang sah
    // walau mereka membuka profil dari browser/HP yang berbeda.
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
    $device    = deteksiPerangkatDariUserAgent($_SERVER['HTTP_USER_AGENT'] ?? '');

    $_SESSION['login_log_id'] = null;
    if ($stmtLog = $koneksi->prepare('INSERT INTO login_log (user_id, ip_address, device, login_at) VALUES (?, ?, ?, NOW())')) {
        $stmtLog->bind_param('iss', $user['id'], $ipAddress, $device);
        $stmtLog->execute();
        $_SESSION['login_log_id'] = $koneksi->insert_id;
        $stmtLog->close();
    }
    // Kalau tabel login_log belum dibuat (migrasi belum dijalankan), prepare()
    // di atas akan gagal (false) dan baris ini dilewati saja -- login tetap
    // berhasil seperti biasa, hanya saja histori login belum tercatat.

    // Pakai nama profil (kalau ada) supaya kolom "Pengguna" di halaman Audit
    // langsung sesuai profil sejak baris pertama dicatat, bukan menunggu
    // auditBenarkanNamaLama() membenarkannya belakangan.
    $namaUntukAudit = !empty($user['nama']) ? $user['nama'] : $user['username'];
    catatAuditManual($koneksi, (int) $user['id'], $namaUntukAudit, 'Autentikasi', 'login', 'Login ke sistem', null, 'berhasil');

    echo json_encode(['success' => true, 'message' => 'Login berhasil']);
} else {
    catatAuditManual($koneksi, $user['id'] ?? null, $username !== '' ? $username : 'Tidak dikenal', 'Autentikasi', 'login', 'Percobaan login gagal', 'Username: ' . $username, 'gagal');

    echo json_encode(['success' => false, 'message' => 'Username atau kata sandi salah']);
}

