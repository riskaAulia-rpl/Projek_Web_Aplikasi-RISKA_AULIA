<?php
// Daftar jabatan lengkap — disamakan persis dengan opsi di form Tambah/Edit Karyawan (tambah_member.php)
$daftar_jabatan = [
    "ADVISOR", "ADMIN", "CHIEF HATCH BOSS", "DIREKTUR", "DRIVER",
    "DRIVER GROUP A", "DRIVER GROUP B", "DRIVER GROUP C", "DRIVER GROUP D", "DRIVER GROUP E", "DRIVER GROUP F",
    "FOREMAN KADE", "HATCH BOSS", "INDIKATOR", "KABAG", "KABAG GUDANG CARGODORE", "KABAG PAYROLL",
    "KASIE", "KASIR", "KOMISARIS", "KOORDINATOR TEAM", "KR. CHECKER", "LEADMAN",
    "LQ GATE", "LQ GATE 1", "LQ GATE 2", "LQ GATE 3", "MANAJER", "MEKANIK", "OFFICE BOY",
    "OPERATOR ALBER", "PARKIR", "PERBANTUAN", "PIC IMPORT", "SEKRETARIAT", "SECURITY",
    "SHUTTLE DRIVER", "SQ CARGODORE", "SQ STEVEDORE", "STAFF", "STORING",
    "TALLY BARCODE/LAYOUT", "TALLY FALL OUT", "TALLY KEY", "TALLY MAN", "TALLY MARLONG",
    "TALLY PORT IN", "TALLY RECEIVING", "TALLY SHORTING", "TAM/GENCO", "SIGNAL MAN", "WAKIL MANAJER",
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />

    <title>Pendaftaran member</title>
    <!-- <<Mobile Viewport Code>> -->
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <!-- <<Attched Stylesheets>> -->
    <link rel="stylesheet" href="./css/theme.css?v=4" type="text/css" />
    <link rel="stylesheet" href="./css/media.css?v=12" type="text/css" />
    <link rel="stylesheet" href="./css/font-awesome.min.css" type="text/css" />
    <link href='https://fonts.googleapis.com/css?family=Open+Sans:400,300,600,600italic,400italic,800,700' rel='stylesheet' type='text/css'>    
    <link href='https://fonts.googleapis.com/css?family=Oswald:400,700,300' rel='stylesheet' type='text/css'>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>
<body>
<div class="DesignHolder">
	<!-- \\ Begin Frame \\ -->
	<div class="LayoutFrame">
        <!-- \\ Begin Header \\ -->
        <header>
            <div class="Center">
                <div class="site-logo">
                    <img src="img/logobkjl.jpeg" alt="logo bkjl" class="logobkjl">
                	<h1><a href="#"> PT Bandar Krida Jala</a></h1>
                </div>
               <div id="mobile_sec">
               <div class="mobile"><i class="fa fa-bars"></i><i class="fa fa-times"></i></div>
                <div class="menumobile">
                    <!-- \\ Begin Navigation \\ -->
                    <nav class="Navigation">
                        <ul>
                            <li class="active">                                
                                <a href="#home">Beranda</a>
                                <span class="menu-item-bg"></span>
                            </li>
                            <li>
                                <a href="#about">Panduan</a>
                                <span class="menu-item-bg"></span>
                            </li>
                            <li>
                                <a href="#services">Mendaftar</a>
                                <span class="menu-item-bg"></span>
                            </li>
                            <li>
                                <a href="#contact">Kontak</a>
                                <span class="menu-item-bg"></span>
                            </li>
                          <li class="profil-menu-item">
                               <a href="login.php">
                              <i class="fa-regular fa-circle-user"></i> Profil
                               </a>
                             <span class="menu-item-bg"></span>
                         </li>
                        </ul>
                    </nav>
                    <!-- // End Navigation // -->
				</div>
				</div>
                <div class="clear"></div>
            </div>
        </header>
       
        <div class="Banner_sec" id="home">
            <!--  \\ Begin banner Side -->
            <div class="bannerside">
	            <div class="Center">
                    <!--  \\ Begin Left Side -->
                    <div class="leftside">
                        <h3>LOGISTIK<span>dan MARITIM TERPANDU</span></h3>
                        <p>PT Bandar Krida Jala berkomitmen memberikan pelayanan jasa kepelabuhan dan logistik terbaik di Kawasan Pelabuhan Partimban. Kami mengintegrasikan sistem operasional yang aman, cepat, dan terpercaya.</p>
                        <a href="#about">Selengkapnya</a>
                    </div>                        								
                    <!--  // End Left Side // -->

                    <!--  \\ Begin Right Side -->
                    <div class="rightside">
                    	<ul id="slider">	
                    		<li>
                                <div class="Slider">
                                    <figure>
                                        <video src="video/bannervid.mp4" autoplay loop muted playsinline></video>
                                    </figure>
                                    <div class="text">
                                    </div>
                                </div>
							</li>
						</ul>                                                            
                                                     
                    </div>
                    <!--  // End Right Side // -->
	            </div>
            </div>
            <!--  // End banner Side // -->
              
            <div class="clear"></div>
        </div>
        <!-- // End Banner Section // -->
         <div class="bgcolor"></div>
        <!-- \\ Begin Container \\ -->
        <div id="Container">
            <!-- \\ Begin About Section \\ -->
            <div class="About_sec" id="about">
                <div class="Center">            	
                    <h2>PANDUAN PENDAFTARAN</h2>            		
                    <p>Selamat datang di Portal Pendaftaran Member Resmi PT Bandar Krida Jala Patimban. Untuk memudahkan proses registrasi keanggotaan Anda <br>
                         (Keagenan Kapal/Mitra Logistik), silakan ikuti langkah-langkah mudah yang tertera pada tab di bawah ini. Pastikan Anda telah <br>menyiapkan seluruh dokumen persyaratan sebelum memulai pendaftaran.</p>
                    <div class="Line"></div>	
                    <!-- \\ Begin Tab side \\ -->
                    <div class="Tabside">
                        <ul>
                            <li><a href="javascript:;" class="tabLink activeLink" id="cont-1">Alur</a></li>
                            <li><a href="javascript:;" class="tabLink" id="cont-2">Syarat</a></li>
                            <li><a href="javascript:;" class="tabLink" id="cont-3">Bantuan</a></li>
                        </ul>
                      <div class="clear"></div>
                        <div class="tabcontent" id="cont-1-1">
                            <div class="TabImage">
                                <div class="img1">
                                   
                                </div>
                                <div class="img2">
                                    <figure><img src="https://tse4.mm.bing.net/th/id/OIP.htmRLYHIcVgN0p9FSWqLUwAAAA?r=0&rs=1&pid=ImgDetMain&o=7&rm=3" alt="image"></figure>
                                </div>
                            </div>
                            <div class="Description">
                                <h3>TAHAPAN REGISTRASI MEMBER<span>alur mudah pendaftaran keanggotaan PT Bandar Krida Jala</span></h3>
                                <p>Langkah 1: Pengisian Formulir > Klik menu "Mendaftar" pada navigasi atas, lalu isi data profil instansi atau agen Anda secara lengkap, benar, dan valid.<br><br>
                                Langkah 2: Validasi Sistem Keanggotaan > Setelah menekan tombol kirim, data Anda akan otomatis tercatat ke dalam sistem database Manifest Keanggotaan PT BKJL dengan status awal 'Pending' (Menunggu).<br><br>
                                Langkah 3: Verifikasi & Aktivasi > Tim administrasi pelabuhan akan memeriksa keabsahan data NIP serta operasional instansi Anda. Jika disetujui, status manifest akan diaktifkan menjadi 'Clear' (Aktif) dan akun resmi Anda siap digunakan.</p>
                            </div>
                        </div>
                        <div class="tabcontent hide" id="cont-2-1">
                            <div class="TabImage">
                                <div class="img1">
                                    <figure><img src="https://media.istockphoto.com/id/508469247/photo/industrial-port-in-thailand.jpg?s=170667a&w=0&k=20&c=gLvb9qW6ztPfSYbzB84ZGrp4DQ69kXsWiUFklb4G4Ks=" alt="image"></figure>	
                                </div>
                                <div class="img2">
                                   
                                </div>
                            </div>
                            <div class="Description">
                                <h3>DOKUMEN PERSYARATAN MEMBER<span>Pastikan berkas berikut siap dalam format PDF/JPG maksimal dua MB.</span></h3>
                                <p>Untuk menjaga keamanan transaksi dan legalitas operasional di pelabuhan patimban, calon member wajib mengunggah dokumen pendukung sebagai berikut :
                                    Surat Permohonan Menjadi Member resmi perusahaan.
                                <br><br>
                                1. Salinan Nomor Induk Pegawai (NIP) atau SIUP yang masih berlaku.
                                <br>
                                2. Salinan NPWP Perusahaan/Instansi.
                                <br>
                                3. Kartu Identitas (KTP/Paspor) Penanggung Jawab Agen.
                                <br><br>
                                Catatan: Proses verifikasi hanya akan dilakukan pada dokumen yang terbaca dengan jelas dan sah secara hukum.
                                </p>
                            </div>
                        </div>
                        <div class="tabcontent hide" id="cont-3-1">
                            <div class="TabImage">
                                <div class="img1">
                                    <figure><img src="https://files01.pna.gov.ph/category-list/2025/07/17/dumaguete-city-port-of-dumaguete-jfp-file-photo-.jpg" alt="image"></figure>	
                                </div>
                                <div class="img2">
                                    
                                </div>
                            </div>
                            <div class="Description">
                                <h3>LAYANAN PENGADUAN DAN BANTUAN <span>Hubungi kami jika anda mengalami kendala tekknis saat registrasi</span></h3>
                                <p>Mengalami kesulitan saat mengisi formulir atau mengunggah dokumen persyaratan? Tim Helpdesk PT Bandar Krida Jala siap membantu Anda.
                                <br><br>
                                Kontak Support: > * Email: support@bkjl-patimban.co.id (Respon maksimal 1 jam)
                                <br>
                                WhatsApp Admin: 0812-XXXX-XXXX (Senin - Jumat, 08.00 - 17.00 WIB)
                                <br><br>
                                Anda juga bisa langsung mengunjungi Kantor Operasional PT BKJL di Kawasan Pelabuhan Patimban pada jam kerja untuk panduan pendaftaran secara langsung.</p>
                            </div>
                        </div>
	                    <div class="clear"></div>	
                    </div>                    
                    <!-- // End Tab Side // -->
                </div>
            </div>
            
        <div class="Services_sec" id="services">
            <div class="Center">
                <h2>MENDAFTAR</h2>
                <p>Selesaikan pendaftaran Anda di sini untuk mendapatkan akses penuh ke layanan logistik terpandu PT Bandar Krida Jala Patimban. <br>Silahkan pilih kategori keanggotaan yang sesuai dan isi data Anda dengan benar demi kelancaran proses verifikasi.</p>		
                <div class="Line"></div>

                <!-- FORMULIR PENDAFTARAN -->
                 <div class="registration-container" id="registration-form">
    <div class="form-header">
        <h3>FORMULIR REGISTRASI MEMBER BARU</h3>
        <p>PT Bandar Krida Jala - Kawasan Pelabuhan Patimban</p>
    </div>
    <form action="proses_daftar.php" method="POST" class="maritime-form">
        <div class="form-section">
            <h4><i class="fa fa-user"></i>  Kontak & Penanggung Jawab</h4>
            <div class="form-group-row">
                <div class="form-group">
                    <label for="nama_pj">Nama Penanggung Jawab *</label>
                    <input type="text" id="nama_pj" name="nama_penanggung_jawab" placeholder="Nama sesuai KTP" required>
                </div>
                <div class="form-group">
                    <label for="jabatan">Jabatan *</label>
                    <select id="jabatan" name="jabatan" required>
                        <option value="">-- Pilih Jabatan --</option>
                        <?php foreach ($daftar_jabatan as $opsi_jabatan): ?>
                            <option value="<?php echo htmlspecialchars($opsi_jabatan); ?>">
                                <?php echo htmlspecialchars($opsi_jabatan); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-group-row">
                <div class="form-group">
                    <label for="jenis_member">Kategori Keanggotaan *</label>
                    <select id="jenis_member" name="kategori_keanggotaan" required>
                        <option value="">-- Pilih Kategori --</option>
                        <option value="Stevedore">Stevedore</option>
                        <option value="Cargodore">Cargodore</option>
                        <option value="Staff Kantor">Staff Kantor</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="nip">Nomor Induk Pegawai (NIP) *</label>
                    <input type="text" id="nip" name="nip" placeholder="9 Digit Nomor NIP" required>
                </div>
            </div>

            <div class="form-group-row">
                <div class="form-group">
                    <label for="email_perusahaan">Alamat Email Aktif *</label>
                    <input type="email" id="email_perusahaan" name="email_aktif" placeholder="name@company.com" required>
                </div>
                <div class="form-group">
                    <label for="no_telp">Nomor WhatsApp Operasional *</label>
                    <input type="tel" id="no_telp" name="nomor_whatsapp" placeholder="Contoh: 081234567890" required>
                </div>
            </div>
        </div>


        <!-- ================= BAGIAN 2: BUAT AKUN LOGIN ================= -->
        <div class="form-section">
            <h4><i class="fa fa-lock"></i>  Akun Akses Login</h4>
            
           <div class="form-group-row full-width-confirm-row" style="margin-top: 20px; margin-bottom: 30px;">
                <div class="form-group">
                    <label for="username">Username Akun *</label>
                    <input type="text" id="username" name="username" placeholder="Buat username perusahaan (tanpa spasi)" required>
                </div>
            </div>

            <div class="form-group-row full-width-confirm-row" style="margin-top: -5px; margin-bottom: 30px;">
                <div class="form-group">
                    <label for="password">Password Akun *</label>
                    <div style="position: relative;">
                        <input type="password" id="password" name="password" placeholder="Buat password yang aman" required>
                        <span onclick="togglePassword()" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer; font-size: 18px; color: #2c3e50;" aria-label="Tampilkan atau sembunyikan password" title="Tampilkan atau sembunyikan password">
                            <i class="fa-solid fa-eye"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="form-group-row full-width-confirm-row" style="margin-top: -5px; margin-bottom: 30px;">
            <div class="form-group confirm-password-group">
                <label for="confirm_password">Konfirmasi Password *</label>
                <div class="confirm-password-wrapper">
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Ulangi password" required class="confirm-password-input">
                    <span onclick="togglePassword()" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer; font-size: 18px; color: #2c3e50;" aria-label="Tampilkan atau sembunyikan password" title="Tampilkan atau sembunyikan password">
                        <i class="fa-solid fa-eye"></i>
                    </span>
                </div>
            </div>
        </div>
        <!-- ============================================================= -->

        <div class="form-actions">
            <div class="terms-checkbox">
                <input type="checkbox" id="agree" name="agree" required>
                <label for="agree">Saya menyatakan bahwa data yang dimasukkan sah dan siap mengunggah dokumen pendukung untuk diverifikasi.</label>
            </div><br>
            <button type="submit" name="daftar" class="btn-submit-maritime">Kirim Permohonan Pendaftaran</button>
        </div>
    </form>
</div>
            </div>                
        </div>
        <!-- END FORMULIR PENDAFTARAN -->

        <!-- DAFTAR MEMBER BKJL -->
        <!-- END  DAFTAR MEMBER BKJL -->
        <div class="Contact_sec" id="contact">
            <div class="Contactside">
                <div class="Center">
                    <h2>KONTAK KAMI</h2>
                    <p>Kami berkomitmen membangun kemitraan yang kuat di sektor maritim. Jangan ragu untuk menghubungi layanan pelanggan kami <br> untuk konsultasi kerja sama, operasional crew, maupun logistik bongkar muat.</p>
                    <div class="Line"></div>
                </div>  

            </div>

                <div class="Contact_layout">
                    <div class="Map">
                        <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3965.8820273038723!2d107.85142717355515!3d-6.279237061462542!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e69455f2b3d70c9%3A0xca955ef3370f9de!2sPt.Bandar%20Krida%20Jala!5e0!3m2!1sid!2sid!4v1781773298246!5m2!1sid!2sid" width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                    </div>
                    
                    <!-- \\ Begin Get Section \\ -->
                    <div class="Get_sec">
                        <div class="Mid">					
                            <!-- \\ Begin Left Side \\ -->
                            <div class="Leftside">
                                <form action="proses_hubungi.php" method="POST">
                                    <fieldset>
                                        <p><input type="text" name="nama" value="" placeholder="Nama" class="field" required></p>
                                        <p><input type="email" name="email" value="" placeholder="Email" class="field" required></p>
                                        <p><input type="text" name="subjek" value="" placeholder="Subjek" class="field" required></p>
                                        <p><textarea cols="2" name="pesan" rows="2" placeholder="Pesan" required></textarea></p>
                                        <p><input type="submit" name="kirim" value="KIRIM" class="button"></p>
                                    </fieldset>
                                </form>
                            </div>
                            <!-- // End Left Side // -->
                            <!-- \\ Begin Right Side \\ -->
                            <div class="Rightside">
                                <h3>Hubungi Kami</h3>
                                    <address>
                                        Jl. Pelabuhan Patimban, Dusun Patimban, RT 01 / RW 01,<br>
                                         Desa Patimban, Kecamatan Pusakanagara, Kabupaten <br>
                                         Subang, Jawa Barat, Kode Pos 41255, Indonesia
                                    </address>	
                                    <address class="Number">
                                        (+62) 811-1012-6021
                                    </address><br>	
                                    <address class="Email">
                                        <a href="mailto:info@november.com">bkjl@patimban.com</a>
                                    </address>	
                        </div></div>
                    </div>
                </div>
              
            </div>
            <!-- // End Contact Section // -->
        </div>
        <footer>
            <div class="Cntr">                
                <p>COPYRIGHT © 2026 PT BANDAR KRIDA JALA</p>
            </div>
        </footer>
        <!-- // End Container // -->
	</div>
	<!-- // End Layout Frame // -->
</div>
<!-- // End Design Holder // -->
<div id="loader-wrapper">
<div id="loader"></div>

    <div class="loader-section section-left"></div>
    <div class="loader-section section-right"></div>

</div>

<!-- <<Attched Javascripts>> -->
<script type="text/javascript" src="js/jquery-1.11.0.min.js"></script>
<script type="text/javascript" src="js/jquery.sudoSlider.min.js"></script>
<script type="text/javascript" src="js/global.js"></script>
<script type="text/javascript" src="js/modernizr.js"></script>

<!-- js mata di form -->
 <script>
function togglePassword() {
    var passwordField = document.getElementById("password");
    var confirmField = document.getElementById("confirm_password");
    var showType = passwordField.type === "password" ? "text" : "password";

    passwordField.type = showType;
    if (confirmField) {
        confirmField.type = showType;
    }
}
</script>

</body>
</html>