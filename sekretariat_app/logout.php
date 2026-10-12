<?php
/**
 * Logout sungguhan: menghancurkan session PHP di server,
 * bukan cuma menghapus data di localStorage browser.
 */

session_start();

if (!empty($_SESSION['user_id'])) {
    require 'koneksi.php';
    require 'catat_audit.php';

    // Pakai nama profil (kalau ada) supaya kolom "Pengguna" di halaman Audit
    // langsung sesuai profil, bukan username.
    $namaUntukAudit = $_SESSION['username'] ?? 'Tidak dikenal';
    if ($stmtNamaLogout = $koneksi->prepare('SELECT nama FROM users WHERE id = ? LIMIT 1')) {
        $stmtNamaLogout->bind_param('i', $_SESSION['user_id']);
        $stmtNamaLogout->execute();
        $rowNamaLogout = $stmtNamaLogout->get_result()->fetch_assoc();
        $stmtNamaLogout->close();
        if (!empty($rowNamaLogout['nama'])) {
            $namaUntukAudit = $rowNamaLogout['nama'];
        }
    }

    catatAuditManual($koneksi, (int) $_SESSION['user_id'], $namaUntukAudit, 'Autentikasi', 'logout', 'Logout dari sistem');
}

$_SESSION = [];
session_unset();
session_destroy();

// Hapus cookie session juga, biar bersih total
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}

header('Location: login.php');
exit;
