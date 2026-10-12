<?php
/**
 * catat_aktivitas.php
 * -----------------------------------------------------------------
 * Ditaruh (require) di setiap halaman yang butuh login, tepat setelah
 * koneksi.php di-include. Setiap kali user membuka/reload halaman apa
 * saja di aplikasi ini, kolom users.last_seen diperbarui ke waktu
 * sekarang.
 *
 * Ini dipakai untuk fitur "Perangkat Aktif" di profil.php: menghitung
 * berapa dari 3 akun (LOVI/DEA/BAPAK-BAPAK) yang SEDANG membuka
 * aplikasi ini saat ini juga (last_seen dalam beberapa menit terakhir),
 * bukan riwayat login satu akun saja.
 *
 * Butuh kolom `last_seen` di tabel `users` -- lihat migrasi
 * migrasi_login_log.sql (bagian last_seen) sebelum file ini dipakai.
 * Kalau kolom belum ada, query akan gagal secara diam-diam (tidak akan
 * menghentikan halaman) supaya tidak merusak aplikasi yang sudah jalan.
 */

if (!empty($_SESSION['user_id']) && isset($koneksi)) {
    if ($stmtAktif = $koneksi->prepare('UPDATE users SET last_seen = NOW() WHERE id = ?')) {
        $stmtAktif->bind_param('i', $_SESSION['user_id']);
        @$stmtAktif->execute();
        $stmtAktif->close();
    }
}
