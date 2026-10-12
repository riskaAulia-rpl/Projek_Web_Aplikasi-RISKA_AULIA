<?php
session_start();
// Kalau sudah login (session valid), langsung lempar ke dashboard
if (!empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="id" class="loading">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login - Sekretariat Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@500;600;700;800&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
<style>
  :root{
    --cream:#fbf5ea;
    --ink:#5c6b93;
    --ink-soft:#8b96b8;
    --blue:#bcd7f7;
    --pink:#f8c9dc;
    --pink-deep:#ef9dbf;
    --yellow:#ffe6a0;
    --yellow-deep:#f4c765;
    --mint:#bdeccb;
    --lavender:#dcd0f5;
    --white:#ffffff;
    --shadow: 0 10px 24px rgba(150,130,180,.14);
  }
  *{box-sizing:border-box;}
  html.loading{visibility:hidden;}
  body{
    margin:0; font-family:'Nunito',sans-serif;
    background:
      radial-gradient(circle at 8% 12%, #d7e8fb 0%, transparent 40%),
      radial-gradient(circle at 95% 8%, #f0e2fb 0%, transparent 42%),
      radial-gradient(circle at 90% 90%, #fbe3ec 0%, transparent 38%),
      var(--cream);
    color:var(--ink); min-height:100vh;
    display:flex; align-items:center; justify-content:center;
    padding:24px;
    overflow-x:hidden;
  }
  h1,h2,h3,h4{font-family:'Baloo 2',sans-serif; margin:0;}

  /* ---------- SPLASH ---------- */
  #splash{
    position:fixed; inset:0; z-index:999; display:flex; flex-direction:column; align-items:center; justify-content:center;
    background:
      radial-gradient(circle at 8% 12%, #d7e8fb 0%, transparent 40%),
      radial-gradient(circle at 95% 8%, #f0e2fb 0%, transparent 42%),
      radial-gradient(circle at 90% 90%, #fbe3ec 0%, transparent 38%),
      var(--cream);
    transition:opacity .5s ease, visibility .5s ease;
  }
  #splash.hide{opacity:0; visibility:hidden; pointer-events:none;}
  .splash-ring{position:relative; width:96px; height:96px; display:flex; align-items:center; justify-content:center; margin-bottom:6px;}
  .splash-ring svg.orbit{position:absolute; inset:0; width:96px; height:96px; animation:splashSpin 2.6s linear infinite;}
  .splash-badge{width:64px; height:64px; border-radius:22px; background:linear-gradient(160deg,var(--lavender),var(--blue)); display:flex; align-items:center; justify-content:center; box-shadow:var(--shadow); overflow:hidden; animation:splashPop .7s cubic-bezier(.34,1.56,.64,1) both, splashFloat 2.4s ease-in-out .7s infinite;}
  .splash-title{font-family:'Baloo 2',sans-serif; font-size:21px; font-weight:800; color:var(--ink); opacity:0; letter-spacing:.01em; animation:splashFadeUp .6s ease .35s forwards;}
  .splash-sub{font-size:11px; font-weight:800; color:var(--ink-soft); letter-spacing:.14em; text-transform:uppercase; opacity:0; animation:splashFadeUp .6s ease .5s forwards; margin-top:-2px;}
  .splash-dots{display:flex; gap:7px; margin-top:14px; opacity:0; animation:splashFadeUp .5s ease .68s forwards;}
  .splash-dots span{width:8px; height:8px; border-radius:50%; display:inline-block; animation:splashBounce 1s ease-in-out infinite;}
  .splash-dots span:nth-child(1){background:var(--pink-deep);}
  .splash-dots span:nth-child(2){background:var(--yellow-deep); animation-delay:.15s;}
  .splash-dots span:nth-child(3){background:#8a5fce; animation-delay:.3s;}

  @keyframes splashSpin{from{transform:rotate(0deg);} to{transform:rotate(360deg);}}
  @keyframes splashPop{0%{transform:scale(.4) rotate(-15deg); opacity:0;} 60%{transform:scale(1.08) rotate(4deg); opacity:1;} 100%{transform:scale(1) rotate(0); opacity:1;}}
  @keyframes splashFloat{0%,100%{transform:translateY(0);} 50%{transform:translateY(-5px);}}
  @keyframes splashFadeUp{from{opacity:0; transform:translateY(10px);} to{opacity:1; transform:translateY(0);}}
  @keyframes splashBounce{0%,80%,100%{transform:translateY(0); opacity:.5;} 40%{transform:translateY(-9px); opacity:1;}}

  /* ---------- FLOATING DECOR ---------- */
  .deco{position:fixed; pointer-events:none; opacity:.55; animation:decoFloat 6s ease-in-out infinite; z-index:0;}
  .deco.d1{top:8%; left:9%; font-size:34px; animation-delay:0s;}
  .deco.d2{top:16%; right:12%; font-size:26px; animation-delay:1.2s;}
  .deco.d3{bottom:12%; left:14%; font-size:28px; animation-delay:.6s;}
  .deco.d4{bottom:18%; right:9%; font-size:22px; animation-delay:1.8s;}
  @keyframes decoFloat{0%,100%{transform:translateY(0) rotate(0deg);} 50%{transform:translateY(-14px) rotate(6deg);}}

  /* ---------- APP REVEAL ---------- */
  .login-wrap{opacity:0; transform:translateY(14px); transition:opacity .5s ease, transform .5s ease; position:relative; z-index:1; width:100%; max-width:920px;}
  .login-wrap.show{opacity:1; transform:translateY(0);}

  /* ---------- CARD ---------- */
  .login-card{
    background:var(--white); border-radius:30px; box-shadow:var(--shadow);
    display:flex; overflow:hidden; min-height:520px;
  }

  /* Left / brand panel */
  .login-side{
    flex:0 0 42%; background:linear-gradient(160deg,var(--lavender),var(--blue));
    padding:44px 38px; display:flex; flex-direction:column; justify-content:space-between;
    position:relative; overflow:hidden;
  }
  .login-side::before{
    content:""; position:absolute; width:220px; height:220px; border-radius:50%;
    background:rgba(255,255,255,.18); top:-70px; right:-70px;
  }
  .login-side::after{
    content:""; position:absolute; width:160px; height:160px; border-radius:50%;
    background:rgba(255,255,255,.14); bottom:-50px; left:-40px;
  }
  .side-brand{display:flex; align-items:center; gap:12px; position:relative; z-index:1;}
  .side-brand-badge{width:52px; height:52px; border-radius:18px; background:rgba(255,255,255,.55); display:flex; align-items:center; justify-content:center; box-shadow:0 6px 16px rgba(90,70,140,.15); flex:0 0 52px; overflow:hidden; animation:brandBounce 4.2s ease-in-out infinite;}
  @keyframes brandBounce{ 0%,100%{transform:translateY(0) rotate(0deg);} 50%{transform:translateY(-3px) rotate(2deg);} }
  .side-brand-name{font-weight:800; font-size:18px; color:#4b4478; display:flex; align-items:center; gap:5px;}
  .side-brand-sub{font-size:10.5px; font-weight:800; letter-spacing:.08em; color:#5c4d95; margin-top:2px;}

  .side-illustration{position:relative; z-index:1; display:flex; justify-content:center; align-items:center; flex:1; margin:18px 0;}
  .side-illustration svg{filter:drop-shadow(0 10px 18px rgba(90,70,140,.18));}

  .side-quote{position:relative; z-index:1; color:#4b4478;}
  .side-quote h3{font-size:19px; font-weight:800; line-height:1.35; margin-bottom:6px;}
  .side-quote p{font-size:12.5px; font-weight:700; color:#5c4d95; opacity:.85; line-height:1.5; margin:0;}

  /* Right / form panel */
  .login-form-panel{flex:1; padding:48px 46px; display:flex; flex-direction:column; justify-content:center;}
  .form-head{margin-bottom:28px;}
  .form-eyebrow{font-size:11px; font-weight:800; letter-spacing:.1em; color:#8a5fce; background:linear-gradient(135deg,#f3edff,#fdeef7); display:inline-block; padding:4px 12px; border-radius:999px; margin-bottom:14px;}
  .form-title{font-size:27px; font-weight:800; color:var(--ink); margin-bottom:6px; display:flex; align-items:center; gap:8px;}
  .form-title .wave{display:inline-block; animation:pfWiggle 2.6s ease-in-out infinite;}
  @keyframes pfWiggle{0%,100%{transform:rotate(0deg);} 25%{transform:rotate(14deg);} 75%{transform:rotate(-10deg);}}
  .form-subtitle{font-size:13px; color:var(--ink-soft); font-weight:700;}

  .field{margin-bottom:18px;}
  .field label{display:block; font-size:12px; font-weight:800; color:var(--ink); margin-bottom:7px;}
  .field-input{
    display:flex; align-items:center; gap:10px; background:var(--cream); border:1.5px solid #f0e6d3;
    border-radius:16px; padding:12px 16px; transition:border-color .2s ease, box-shadow .2s ease, background .2s ease;
  }
  .field-input:focus-within{border-color:#c9b6f2; background:var(--white); box-shadow:0 0 0 4px rgba(201,182,242,.18);}
  .field-input svg{width:17px; height:17px; color:var(--ink-soft); flex:0 0 17px; transition:transform .2s ease, color .2s ease;}
  .field-input:focus-within svg{color:#8a5fce; transform:scale(1.1);}
  .field-input input{border:none; outline:none; background:transparent; font-family:inherit; font-size:13.5px; font-weight:700; color:var(--ink); flex:1; min-width:0;}
  .field-input input::placeholder{color:#b9c1dd; font-weight:600;}
  .toggle-eye{cursor:pointer; user-select:none; display:flex; align-items:center;}
  .toggle-eye:hover svg{color:#8a5fce;}

  .field-error{font-size:11px; font-weight:700; color:#e05c8a; margin-top:6px; min-height:14px; display:flex; align-items:center; gap:5px; opacity:0; transform:translateY(-3px); transition:opacity .2s ease, transform .2s ease;}
  .field-error.show{opacity:1; transform:translateY(0);}

  .row-between{display:flex; align-items:center; justify-content:space-between; margin-bottom:22px; flex-wrap:wrap; gap:8px;}
  .remember{display:flex; align-items:center; gap:8px; font-size:12px; font-weight:700; color:var(--ink-soft); cursor:pointer; user-select:none;}
  .chk{width:18px; height:18px; border-radius:6px; border:1.5px solid #d9cdf0; background:var(--white); display:flex; align-items:center; justify-content:center; flex:0 0 18px; transition:background .15s ease, border-color .15s ease;}
  .chk svg{width:11px; height:11px; opacity:0; transform:scale(.5); transition:opacity .15s ease, transform .15s ease; color:#fff;}
  .remember input{display:none;}
  .remember input:checked + .chk{background:linear-gradient(135deg,#c9b6f2,#a98ee8); border-color:transparent;}
  .remember input:checked + .chk svg{opacity:1; transform:scale(1);}
  .forgot-link{font-size:12px; font-weight:800; color:#8a5fce; text-decoration:none; cursor:pointer;}
  .forgot-link:hover{text-decoration:underline;}

  .btn-login{
    width:100%; border:none; cursor:pointer; font-family:inherit; font-weight:800; font-size:14px;
    padding:14px 18px; border-radius:16px; box-shadow:var(--shadow);
    background:linear-gradient(135deg,#c9b6f2,#a98ee8); color:#fff;
    display:flex; align-items:center; justify-content:center; gap:8px;
    transition:transform .15s ease, box-shadow .15s ease;
  }
  .btn-login:hover{transform:translateY(-2px); box-shadow:0 14px 28px rgba(150,130,180,.24);}
  .btn-login:active{transform:translateY(0);}
  .btn-login svg{width:16px; height:16px; transition:transform .3s ease;}
  .btn-login:hover svg{transform:translateX(3px);}
  .btn-login.loading{opacity:.8; pointer-events:none;}
  .spinner{width:16px; height:16px; border-radius:50%; border:2.5px solid rgba(255,255,255,.4); border-top-color:#fff; animation:spin .7s linear infinite; display:none;}
  .btn-login.loading .spinner{display:inline-block;}
  .btn-login.loading .btn-login-text, .btn-login.loading .btn-arrow{display:none;}
  @keyframes spin{to{transform:rotate(360deg);}}

  .divider{display:flex; align-items:center; gap:12px; margin:26px 0 18px; color:var(--ink-soft); font-size:11px; font-weight:800; letter-spacing:.04em;}
  .divider::before, .divider::after{content:""; flex:1; height:1px; background:#f0e6d3;}

  .demo-hint{
    display:flex; align-items:flex-start; gap:9px; background:linear-gradient(135deg,#fdf7ea,#fef2fa);
    border:1px dashed #f0dfc4; border-radius:14px; padding:11px 14px; font-size:11.5px; font-weight:700; color:var(--ink-soft); line-height:1.5;
  }
  .demo-hint b{color:var(--ink);}
  .demo-hint .dh-icon{font-size:15px; flex:0 0 auto;}

  .footnote{text-align:center; margin-top:22px; font-size:11.5px; font-weight:700; color:var(--ink-soft);}

  .toast{
    position:fixed; bottom:26px; left:50%; transform:translateX(-50%) translateY(12px); z-index:1200;
    background:var(--white); color:var(--ink); font-size:12.5px; font-weight:800; padding:12px 20px; border-radius:14px;
    box-shadow:var(--shadow); opacity:0; pointer-events:none; transition:opacity .25s ease, transform .25s ease;
    display:flex; align-items:center; gap:8px;
  }
  .toast.show{opacity:1; transform:translateX(-50%) translateY(0);}

  .shake{animation:shakeX .4s ease;}
  @keyframes shakeX{0%,100%{transform:translateX(0);} 20%{transform:translateX(-7px);} 40%{transform:translateX(6px);} 60%{transform:translateX(-4px);} 80%{transform:translateX(3px);}}

  @media (max-width: 820px){
    .login-card{flex-direction:column; min-height:0;}
    .login-side{flex:none; padding:32px 30px;}
    .side-illustration{display:none;}
    .login-form-panel{padding:36px 28px;}
  }
</style>
</head>
<body>

<!-- ============ SPLASH / INTRO ============ -->
<div id="splash">
  <div class="splash-ring">
    <svg class="orbit" viewBox="0 0 96 96" fill="none">
      <circle cx="48" cy="48" r="42" stroke="#e7defb" stroke-width="3"/>
      <path d="M48 6a42 42 0 0 1 42 42" stroke="#a98ee8" stroke-width="3" stroke-linecap="round"/>
    </svg>
    <div class="splash-badge">
      <img src="assets/logo-sekretariat.jpg" alt="Sekretariat" style="width:100%;height:100%;object-fit:cover;display:block;">
    </div>
  </div>
  <div class="splash-title">Sekretariat</div>
  <div class="splash-sub">Admin Panel</div>
  <div class="splash-dots"><span></span><span></span><span></span></div>
</div>

<!-- floating decor -->
<span class="deco d1">✨</span>
<span class="deco d2">🌸</span>
<span class="deco d3">⭐</span>
<span class="deco d4">💌</span>

<div class="login-wrap" id="loginWrap">
  <div class="login-card">

    <!-- ============ LEFT BRAND PANEL ============ -->
    <div class="login-side">
      <div class="side-brand">
        <div class="side-brand-badge">
          <img src="assets/logo-sekretariat.jpg" alt="Sekretariat" style="width:100%;height:100%;object-fit:cover;display:block;">
        </div>
        <div>
          <div class="side-brand-name">Sekretariat 💗</div>
          <div class="side-brand-sub">ADMIN PANEL</div>
        </div>
      </div>

      <div class="side-illustration">
        <svg width="180" height="180" viewBox="0 0 200 200" fill="none">
          <circle cx="100" cy="100" r="86" fill="rgba(255,255,255,.35)"/>
          <rect x="55" y="60" width="90" height="105" rx="14" fill="#fff"/>
          <rect x="55" y="60" width="90" height="26" rx="14" fill="#f8c9dc"/>
          <circle cx="70" cy="73" r="4" fill="#ef9dbf"/>
          <circle cx="83" cy="73" r="4" fill="#ffe6a0"/>
          <line x1="68" y1="100" x2="132" y2="100" stroke="#dcd0f5" stroke-width="6" stroke-linecap="round"/>
          <line x1="68" y1="116" x2="118" y2="116" stroke="#bcd7f7" stroke-width="6" stroke-linecap="round"/>
          <line x1="68" y1="132" x2="126" y2="132" stroke="#bdeccb" stroke-width="6" stroke-linecap="round"/>
          <circle cx="150" cy="145" r="22" fill="#ffe6a0"/>
          <path d="M141 145l6 6 12-13" stroke="#fff" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </div>

      <div class="side-quote">
        <h3>Selamat datang kembali! 🌷</h3>
        <p>Kelola surat, arsip, agenda, dan seluruh aktivitas kesekretariatan dalam satu tempat yang rapi dan menyenangkan.</p>
      </div>
    </div>

    <!-- ============ RIGHT FORM PANEL ============ -->
    <div class="login-form-panel">
      <div class="form-head">
        <span class="form-eyebrow">LOGIN ADMIN</span>
        <div class="form-title">Halo, Sekretaris! <span class="wave">👋</span></div>
        <div class="form-subtitle">Masuk untuk melanjutkan ke dashboard kamu</div>
      </div>

      <form id="loginForm" novalidate>
        <div class="field">
          <label for="username">Username / Email</label>
          <div class="field-input" id="userWrap">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            <input type="text" id="username" placeholder="Masukkan username atau email" autocomplete="username">
          </div>
          <div class="field-error" id="userError">⚠ Username / email wajib diisi</div>
        </div>

        <div class="field">
          <label for="password">Kata Sandi</label>
          <div class="field-input" id="passWrap">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            <input type="password" id="password" placeholder="Masukkan kata sandi" autocomplete="current-password">
            <span class="toggle-eye" id="toggleEye" onclick="togglePassword()">
              <svg id="eyeIcon" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            </span>
          </div>
          <div class="field-error" id="passError">⚠ Kata sandi wajib diisi</div>
        </div>

        <div class="row-between">
          <label class="remember">
            <input type="checkbox" id="remember">
            <span class="chk"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg></span>
            Ingat saya
          </label>
          <a class="forgot-link" onclick="showToast('Hubungi admin sistem untuk reset kata sandi 🔐')">Lupa kata sandi?</a>
        </div>

        <button type="submit" class="btn-login" id="btnLogin">
          <span class="spinner"></span>
          <span class="btn-login-text">Masuk ke Dashboard</span>
          <svg class="btn-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
        </button>
      </form>

      <div class="footnote">© 2026 Sekretariat · Dibuat oleh Riska Aulia ✈️ & Keyzha Maharani 💗</div>
    </div>

  </div>
</div>

<div class="toast" id="toast"></div>

<script>
  function togglePassword(){
    const pass = document.getElementById('password');
    const eye = document.getElementById('eyeIcon');
    if(pass.type === 'password'){
      pass.type = 'text';
      eye.innerHTML = '<path d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-8-11-8a20.6 20.6 0 0 1 5.06-5.94M9.9 4.24A10.4 10.4 0 0 1 12 4c7 0 11 8 11 8a20.6 20.6 0 0 1-3.22 4.53M14.12 14.12a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/>';
    } else {
      pass.type = 'password';
      eye.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>';
    }
  }

  let toastTimer = null;
  function showToast(msg){
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.classList.add('show');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => t.classList.remove('show'), 2400);
  }

  // ---------- CATAT INFO LOGIN TERAKHIR ----------
  // Tanggal/jam/perangkat/IP sekarang dicatat langsung oleh login_process.php
  // di server (tabel login_log), jadi tercatat siapapun & dari perangkat
  // manapun yang login -- tidak lagi disimpan di localStorage browser.
  //
  // Lokasi presisi (kota/negara) tetap dideteksi di sini lewat GPS browser
  // (butuh izin lokasi) + reverse-geocoding, karena itu hanya bisa didapat
  // dari perangkat client. Hasilnya lalu dikirim ke simpan_lokasi_login.php
  // untuk disimpan di baris login_log yang sama.
  function kirimLokasiKeServer(lokasi){
    const formData = new FormData();
    formData.append('lokasi', lokasi);
    return fetch('simpan_lokasi_login.php', { method: 'POST', body: formData }).catch(() => {});
  }

  // selesai() dipanggil begitu lokasi siap dikirim atau kalau gagal/timeout,
  // supaya proses redirect ke dashboard tidak nunggu selamanya.
  function simpanInfoLogin(selesai){
    let sudahSelesai = false;
    function tandaiSelesai(){
      if(sudahSelesai) return;
      sudahSelesai = true;
      selesai();
    }

    // Jaring pengaman kedua: apapun yang terjadi, jangan biarkan user
    // menunggu lebih dari 8 detik sebelum masuk ke dashboard.
    setTimeout(tandaiSelesai, 8000);

    if(!navigator.geolocation){
      kirimLokasiKeServer('Lokasi tidak diketahui').then(tandaiSelesai);
      return;
    }

    const batasWaktu = setTimeout(() => {
      kirimLokasiKeServer('Lokasi tidak diketahui').then(tandaiSelesai);
    }, 6000);

    navigator.geolocation.getCurrentPosition(async (pos) => {
      clearTimeout(batasWaktu);
      let lokasi = 'Lokasi tidak diketahui';
      try{
        const { latitude, longitude } = pos.coords;
        // Batasi waktu tunggu fetch ke Nominatim maksimal 4 detik,
        // supaya kalau server pihak ketiga lambat/tidak merespons,
        // proses login tetap lanjut ke dashboard (tidak nge-hang).
        const fetchController = new AbortController();
        const fetchTimeout = setTimeout(() => fetchController.abort(), 4000);
        const res = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${latitude}&lon=${longitude}&zoom=10&addressdetails=1`, { signal: fetchController.signal });
        clearTimeout(fetchTimeout);
        const data = await res.json();
        const addr = data.address || {};
        const kota = addr.city || addr.town || addr.village || addr.county || addr.state || '';
        const negara = addr.country || '';
        lokasi = [kota, negara].filter(Boolean).join(', ') || 'Lokasi tidak diketahui';
      } catch(err){
        lokasi = 'Lokasi tidak diketahui';
      }
      kirimLokasiKeServer(lokasi).then(tandaiSelesai);
    }, () => {
      clearTimeout(batasWaktu);
      kirimLokasiKeServer('Lokasi tidak diketahui (izin ditolak)').then(tandaiSelesai);
    }, { timeout: 6000 });
  }

  const form = document.getElementById('loginForm');
  const userInput = document.getElementById('username');
  const passInput = document.getElementById('password');
  const userWrap = document.getElementById('userWrap');
  const passWrap = document.getElementById('passWrap');
  const userError = document.getElementById('userError');
  const passError = document.getElementById('passError');
  const btnLogin = document.getElementById('btnLogin');

  function setFieldError(wrap, errEl, on){
    if(on){
      wrap.style.borderColor = '#f0a9c2';
      errEl.classList.add('show');
    } else {
      wrap.style.borderColor = '';
      errEl.classList.remove('show');
    }
  }

  userInput.addEventListener('input', () => setFieldError(userWrap, userError, false));
  passInput.addEventListener('input', () => setFieldError(passWrap, passError, false));

  form.addEventListener('submit', function(e){
    e.preventDefault();
    const u = userInput.value.trim();
    const p = passInput.value.trim();
    let valid = true;

    if(!u){ setFieldError(userWrap, userError, true); valid = false; }
    if(!p){ setFieldError(passWrap, passError, true); valid = false; }

    if(!valid){
      const card = document.querySelector('.login-card');
      card.classList.add('shake');
      setTimeout(() => card.classList.remove('shake'), 420);
      return;
    }

    btnLogin.classList.add('loading');

    const formData = new FormData();
    formData.append('username', u);
    formData.append('password', p);

    fetch('login_process.php', { method: 'POST', body: formData })
      .then(res => res.json())
      .then(data => {
        if(data.success){
          showToast('Login berhasil! Mendeteksi lokasi & perangkat... 📍');
          simpanInfoLogin(() => {
            showToast('Login berhasil! Mengalihkan ke dashboard... ✨');
            setTimeout(() => { window.location.href = 'index.php'; }, 500);
          });
        } else {
          btnLogin.classList.remove('loading');
          setFieldError(userWrap, userError, false);
          passError.textContent = '⚠ ' + (data.message || 'Username atau kata sandi salah');
          setFieldError(passWrap, passError, true);
          const card = document.querySelector('.login-card');
          card.classList.add('shake');
          setTimeout(() => card.classList.remove('shake'), 420);
        }
      })
      .catch(() => {
        btnLogin.classList.remove('loading');
        showToast('⚠ Gagal terhubung ke server. Coba lagi.');
      });
  });

  // ---------- SPLASH / INTRO ----------
  window.addEventListener('load', () => {
    setTimeout(() => {
      const splash = document.getElementById('splash');
      const wrap = document.getElementById('loginWrap');
      document.documentElement.classList.remove('loading');
      wrap.classList.add('show');
      splash.classList.add('hide');
      setTimeout(() => splash.remove(), 600);
    }, 1300);
  });
</script>
</body>
</html>