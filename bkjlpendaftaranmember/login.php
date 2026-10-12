<?php
session_start();
include "koneksi.php";

if (isset($_SESSION['username'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: halaman_admin.php");
        exit();
    }

    // Semua role selain admin (member & karyawan) sekarang pakai satu halaman profil yang sama
    header("Location: profil_member.php");
    exit();
}

if (!isset($_SESSION['username']) && isset($_COOKIE['login_user'])) {
    $cookie_username = trim($_COOKIE['login_user']);

    if (!empty($cookie_username)) {
        $query_admin = mysqli_prepare($koneksi, "SELECT * FROM admin_sistem WHERE username = ? LIMIT 1");
        if ($query_admin) {
            mysqli_stmt_bind_param($query_admin, "s", $cookie_username);
            mysqli_stmt_execute($query_admin);
            $result_admin = mysqli_stmt_get_result($query_admin);
            $admin_data = mysqli_fetch_assoc($result_admin);
            mysqli_stmt_close($query_admin);

            if ($admin_data) {
                $_SESSION['id_admin'] = $admin_data['id_admin'];
                $_SESSION['username'] = $admin_data['username'];
                $_SESSION['role'] = 'admin';
                header("Location: halaman_admin.php");
                exit();
            }
        }

        $query_member = mysqli_prepare($koneksi, "SELECT * FROM pendaftaran_member WHERE username = ? LIMIT 1");
        if ($query_member) {
            mysqli_stmt_bind_param($query_member, "s", $cookie_username);
            mysqli_stmt_execute($query_member);
            $result_member = mysqli_stmt_get_result($query_member);
            $member_data = mysqli_fetch_assoc($result_member);
            mysqli_stmt_close($query_member);

            if ($member_data) {
                $_SESSION['id_member'] = $member_data['id_member'];
                $_SESSION['username'] = $member_data['username'];
                $_SESSION['role'] = $member_data['role'];
                $_SESSION['status_manifest'] = $member_data['status_manifest']; // TAMBAHAN: Set status verifikasi

                // Semua role selain admin (member & karyawan) sekarang pakai satu halaman profil yang sama
                header("Location: profil_member.php");
                exit();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Pendaftaran BKJL</title>
    <link rel="stylesheet" href="css/login.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .btn-back-home {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 14px;
            color: #555;
            text-decoration: none;
            margin-bottom: 16px;
            transition: color 0.2s ease;
        }
        .btn-back-home:hover {
            color: #000;
        }
        .btn-back-home i {
            font-size: 12px;
        }
    </style>
</head>
<body>

    <div class="login-container">
        <div class="login-sidebar">
            <div class="sidebar-overlay"></div>
            <div class="sidebar-content">
                <i class="fa-solid fa-ship brand-icon"></i>
                <h2>Sistem Informasi BKJL</h2>
                <p>Gerbang Digital Pendaftaran dan Manajemen Logistik Maritim.</p>
            </div>
        </div>

        <div class="login-form-section">
            <div class="form-box">
                <div class="form-header">
                    <a href="index.php" class="btn-back-home"><i class="fa-solid fa-arrow-left"></i> Kembali ke Beranda</a>
                    <h2>Selamat Datang</h2>
                    <p>Silakan masuk ke akun BKJL Anda</p>
                </div>

                <form action="proses_login.php" method="POST">
                    <div class="input-group">
                        <label for="username">Username</label>
                        <div class="input-wrapper">
                            <i class="fa-solid fa-user input-icon"></i>
                            <input type="text" id="username" name="username" placeholder="Masukkan username Anda" required>
                        </div>
                    </div>

                    <div class="input-group">
                        <label for="password">Password</label>
                        <div class="input-wrapper">
                            <i class="fa-solid fa-lock input-icon"></i>
                            <input type="password" id="password" name="password" placeholder="Masukkan password" required>
                        </div>
                    </div>

                    <div class="form-options">
                        <label class="remember-me">
                            <input type="checkbox" name="remember">Tetap Masuk
                        </label>
                        <a href="https://wa.me/6289663108220?text=Halo%20Admin%20BKJL,%20saya%20lupa%20password%20untuk%20akun%20member%20saya.%20Mohon%20bantuannya." target="_blank" class="forgot-pass">Lupa Password?</a>
                    </div>

                    <button type="submit" class="btn-login">Masuk Pelabuhan</button>
                </form>

                <div class="form-footer">
                    <p>Belum punya akun? <a href="https://wa.me/6289663108220?text=Halo%20Admin%20BKJL,%20saya%20belum %20punya%20akun%20untuk%20login%20saya.%20Mohon%20bantuannya." target="_blank" class="forgot-pass">Chat Admin Sekarang</a></p>
                </div>
            </div>
        </div>
    </div>

</body>
</html>