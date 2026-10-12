<?php
session_start();
// Proteksi sesungguhnya: kalau tidak ada session login yang valid dari server,
// tendang balik ke halaman login. Ini tidak bisa "dipalsukan" dari console browser,
// beda dengan pengecekan localStorage yang lama.
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

include "koneksi.php";
require "catat_aktivitas.php"; // catat bahwa akun ini sedang aktif/membuka aplikasi
// Ambil nama & foto user yang sedang login, buat ditampilin di kartu sidebar
// (sinkron dengan profil.php, supaya nama & foto profil konsisten di semua halaman).
$cuNama = '';
$cuBio = '';
$cuFotoUrl = '';
$stmtSidebarUser = $koneksi->prepare('SELECT username, nama, bio, foto FROM users WHERE id = ? LIMIT 1');
$stmtSidebarUser->bind_param('i', $_SESSION['user_id']);
$stmtSidebarUser->execute();
$sidebarUserRow = $stmtSidebarUser->get_result()->fetch_assoc();
$stmtSidebarUser->close();
if ($sidebarUserRow) {
    $cuNama = $sidebarUserRow['nama'] ?: $sidebarUserRow['username'];
    $cuBio = $sidebarUserRow['bio'] ?: 'ADMIN';
    $cuFotoUrl = $sidebarUserRow['foto'] ? 'uploads/profil/' . $sidebarUserRow['foto'] : '';
}

date_default_timezone_set('Asia/Jakarta');

// ---------------------------------------------------------------------
// Helper status "hidup" (live) untuk Rapat & Agenda di panel "Operasional
// Harian" -- supaya konsisten dengan logika real-time yang dipakai di
// halaman Manajemen Rapat (rptLiveStatus di rapat.php) dan halaman Agenda
// (agnRenderStatusColumn di agenda.php), bukan cuma tebakan biner dari
// kolom status di database.
// ---------------------------------------------------------------------
function idxHitungRentangWaktu($tanggal, $waktuMulai, $waktuSelesai) {
    if (empty($tanggal) || empty($waktuMulai)) return null;
    $mulai = strtotime($tanggal . ' ' . $waktuMulai);
    if ($mulai === false) return null;
    $selesaiSrc = !empty($waktuSelesai) ? $waktuSelesai : $waktuMulai;
    $selesai = strtotime($tanggal . ' ' . $selesaiSrc);
    if ($selesai === false || $selesai < $mulai) $selesai = $mulai;
    return ['start' => $mulai, 'end' => $selesai];
}

// Status hidup RAPAT: 'selesai' (ditandai manual), 'berlangsung' (jam sekarang
// ada di antara waktu_mulai & waktu_selesai), 'lewat' (jam rapat sudah kelewat
// tapi belum ditandai selesai), atau 'menunggu' (belum mulai). Sama persis
// dengan fungsi rptLiveStatus() di rapat.php.
function idxRapatLiveStatus($row, $now) {
    if (($row['status'] ?? '') === 'selesai') return 'selesai';
    $range = idxHitungRentangWaktu($row['tanggal'] ?? null, $row['waktu_mulai'] ?? null, $row['waktu_selesai'] ?? null);
    if ($range) {
        if ($now >= $range['start'] && $now <= $range['end']) return 'berlangsung';
        if ($now > $range['end']) return 'lewat';
    }
    return 'menunggu';
}

// Status hidup AGENDA: sama seperti agnRenderStatusColumn() di agenda.php --
// 'selesai' (ditandai manual), 'berlangsung' (jam sekarang di antara
// waktu_mulai & waktu_selesai), atau 'terjadwal' (belum mulai / belum lewat).
// (Badge "Terdekat" di agenda.php khusus dipilih 1 agenda paling awal dari
// SELURUH data agenda, jadi tidak direplikasi di ringkasan dashboard ini.)
function idxAgendaLiveStatus($row, $now) {
    if (($row['status'] ?? '') === 'selesai') return 'selesai';
    $range = idxHitungRentangWaktu($row['tanggal'] ?? null, $row['waktu_mulai'] ?? null, $row['waktu_selesai'] ?? null);
    if ($range) {
        if ($now >= $range['start'] && $now <= $range['end']) return 'berlangsung';
        if ($now > $range['end']) return 'selesai'; // waktu sudah lewat -> otomatis dianggap selesai, sama seperti agenda.php
    }
    return 'terjadwal';
}

// Badge notifikasi sidebar: jumlah surat yang BENERAN dibuat/disimpan HARI INI
// (kolom dibuat_pada), sinkron dengan logika di surat_masuk.php & surat_keluar.php.
// Otomatis kehitung ulang tiap kali halaman dimuat, jadi selalu sesuai data asli.
$todayStr = date('Y-m-d');
$badgeSuratMasukHariIni = 0;
$qBadgeMasuk = mysqli_query($koneksi, "SELECT COUNT(*) AS jml FROM surat_masuk WHERE diarsipkan = 0 AND DATE(dibuat_pada) = '$todayStr'");
if ($qBadgeMasuk) {
    $badgeSuratMasukHariIni = (int) (mysqli_fetch_assoc($qBadgeMasuk)['jml'] ?? 0);
}
$badgeSuratKeluarHariIni = 0;
$qBadgeKeluar = mysqli_query($koneksi, "SELECT COUNT(*) AS jml FROM surat_keluar WHERE diarsipkan = 0 AND DATE(dibuat_pada) = '$todayStr'");
if ($qBadgeKeluar) {
    $badgeSuratKeluarHariIni = (int) (mysqli_fetch_assoc($qBadgeKeluar)['jml'] ?? 0);
}

// ---------------------------------------------------------------------
// Panel "Operasional Harian" (dashboard) -- DIROMBAK atas permintaan klien:
// sebelumnya cuma nampilin Rapat/Agenda/Tamu HARI INI saja (jadi "dadakan",
// begitu ganti hari datanya kosong lagi). Sekarang datanya direncanakan:
// diambil untuk rentang MINGGU INI (Senin-Minggu) dan BULAN INI (tanggal 1
// sampai akhir bulan), lalu user bisa switch tampilan antara keduanya lewat
// tombol di UI (murni tampil/sembunyi, kedua rentang sudah disiapkan dari
// PHP sekaligus supaya switch-nya instan tanpa reload).
// ---------------------------------------------------------------------

// Rentang tanggal MINGGU INI (Senin s/d Minggu) berdasarkan tanggal hari ini.
function idxRentangMingguIni($tanggalAcuan) {
    $d = new DateTime($tanggalAcuan);
    $isoHari = (int) $d->format('N'); // 1=Senin ... 7=Minggu
    $senin = clone $d;
    $senin->modify('-' . ($isoHari - 1) . ' days');
    $minggu = clone $senin;
    $minggu->modify('+6 days');
    return [$senin->format('Y-m-d'), $minggu->format('Y-m-d')];
}

// Rentang tanggal BULAN INI (tanggal 1 s/d hari terakhir bulan berjalan).
function idxRentangBulanIni($tanggalAcuan) {
    $d = new DateTime($tanggalAcuan);
    return [$d->format('Y-m-01'), $d->format('Y-m-t')];
}

list($opMingguMulai, $opMingguSelesai) = idxRentangMingguIni($todayStr);
list($opBulanMulai, $opBulanSelesai)   = idxRentangBulanIni($todayStr);

// Ambil Rapat + Agenda + Tamu untuk satu rentang tanggal [$mulai, $selesai]
// sekaligus (dipakai 2x: untuk rentang minggu & rentang bulan). Data yang
// jamnya sudah lewat TETAP disertakan (bukan cuma yang akan datang), supaya
// riwayat penuh dalam rentang itu kelihatan -- badge status (Selesai/Lewat/
// Berlangsung/Menunggu) yang membedakan mana yang sudah lewat.
function idxAmbilOperasionalRange($koneksi, $mulai, $selesai) {
    $out = ['rapat' => [], 'agenda' => [], 'tamu' => []];
    $mulaiEsc = mysqli_real_escape_string($koneksi, $mulai);
    $selesaiEsc = mysqli_real_escape_string($koneksi, $selesai);

    $qRapat = mysqli_query($koneksi, "SELECT * FROM rapat WHERE diarsipkan = 0 AND tanggal BETWEEN '$mulaiEsc' AND '$selesaiEsc' ORDER BY tanggal ASC, waktu_mulai ASC");
    if ($qRapat) { while ($r = mysqli_fetch_assoc($qRapat)) { $out['rapat'][] = $r; } }

    $qAgenda = mysqli_query($koneksi, "SELECT * FROM agenda WHERE diarsipkan = 0 AND tanggal BETWEEN '$mulaiEsc' AND '$selesaiEsc' ORDER BY tanggal ASC, waktu_mulai ASC");
    if ($qAgenda) { while ($r = mysqli_fetch_assoc($qAgenda)) { $out['agenda'][] = $r; } }

    $qTamu = mysqli_query($koneksi, "SELECT * FROM tamu WHERE diarsipkan = 0 AND status != 'deleted' AND tanggal BETWEEN '$mulaiEsc' AND '$selesaiEsc' ORDER BY tanggal ASC, waktu ASC");
    if ($qTamu) { while ($r = mysqli_fetch_assoc($qTamu)) { $out['tamu'][] = $r; } }

    return $out;
}

$opDataMinggu = idxAmbilOperasionalRange($koneksi, $opMingguMulai, $opMingguSelesai);
$opDataBulan  = idxAmbilOperasionalRange($koneksi, $opBulanMulai, $opBulanSelesai);

// Hitung jumlah Rapat / Agenda / Tamu MINGGU INI -- dipakai buat kartu
// "Perbandingan Operasional" (grafik radial 3-ring) di sidebar dashboard,
// supaya angkanya konsisten dengan panel Operasional Harian di atasnya.
$opCountRapatMinggu  = count($opDataMinggu['rapat']);
$opCountAgendaMinggu = count($opDataMinggu['agenda']);
$opCountTamuMinggu   = count($opDataMinggu['tamu']);

// Format jam "HH:MM - HH:MM" (atau cuma "HH:MM" kalau selesai kosong/sama).
function idxFormatJamRange($mulai, $selesai) {
    $m = !empty($mulai) ? substr($mulai, 0, 5) : '';
    $s = !empty($selesai) ? substr($selesai, 0, 5) : '';
    if ($m !== '' && $s !== '' && $s !== $m) return $m . ' - ' . $s;
    if ($m !== '') return $m;
    return '-';
}

// Gabungkan Rapat + Agenda + Tamu jadi satu daftar "event" seragam (tanggal,
// jam mulai/selesai dalam epoch, tipe, label) -- dipakai buat deteksi jadwal
// yang bentrok (jam saling tumpang-tindih di tanggal yang sama).
function idxSusunEventOperasional($data) {
    $events = [];
    foreach ($data['rapat'] as $r) {
        $rng = idxHitungRentangWaktu($r['tanggal'], $r['waktu_mulai'], $r['waktu_selesai']);
        if (!$rng) continue;
        $events[] = ['id' => $r['id'], 'tanggal' => $r['tanggal'], 'start' => $rng['start'], 'end' => $rng['end'],
            'type' => 'rapat', 'icon' => '🤝', 'label' => $r['judul'], 'jam' => idxFormatJamRange($r['waktu_mulai'], $r['waktu_selesai'])];
    }
    foreach ($data['agenda'] as $a) {
        $rng = idxHitungRentangWaktu($a['tanggal'], $a['waktu_mulai'], $a['waktu_selesai']);
        if (!$rng) continue;
        $events[] = ['id' => $a['id'], 'tanggal' => $a['tanggal'], 'start' => $rng['start'], 'end' => $rng['end'],
            'type' => 'agenda', 'icon' => '🗓️', 'label' => $a['judul'], 'jam' => idxFormatJamRange($a['waktu_mulai'], $a['waktu_selesai'])];
    }
    foreach ($data['tamu'] as $t) {
        $rng = idxHitungRentangWaktu($t['tanggal'], $t['waktu'], $t['selesai']);
        if (!$rng) continue;
        $events[] = ['id' => $t['id'], 'tanggal' => $t['tanggal'], 'start' => $rng['start'], 'end' => $rng['end'],
            'type' => 'tamu', 'icon' => '📖', 'label' => $t['instansi'], 'jam' => idxFormatJamRange($t['waktu'], $t['selesai'])];
    }
    return $events;
}

// Deteksi klaster jadwal yang bentrok: dikelompokkan per tanggal, diurutkan
// per jam mulai, lalu "sapu" berurutan -- kalau event berikutnya mulai
// sebelum event sebelumnya (dalam klaster berjalan) selesai, berarti
// tumpang-tindih jam dan digabung ke klaster yang sama. Klaster dengan >= 2
// event berarti bentrok, dikembalikan sebagai daftar buat panel "Jadwal
// Bentrok" + dipakai juga buat kasih tanda ⚠️ di baris tabel masing-masing.
function idxDeteksiJadwalBentrok($events) {
    $perTanggal = [];
    foreach ($events as $e) { $perTanggal[$e['tanggal']][] = $e; }

    $hasil = [];
    foreach ($perTanggal as $tgl => $list) {
        usort($list, function ($a, $b) { return $a['start'] <=> $b['start']; });
        $cluster = [];
        $clusterEnd = null;
        foreach ($list as $ev) {
            if ($cluster && $ev['start'] < $clusterEnd) {
                $cluster[] = $ev;
                $clusterEnd = max($clusterEnd, $ev['end']);
            } else {
                if (count($cluster) > 1) $hasil[] = ['tanggal' => $tgl, 'items' => $cluster];
                $cluster = [$ev];
                $clusterEnd = $ev['end'];
            }
        }
        if (count($cluster) > 1) $hasil[] = ['tanggal' => $tgl, 'items' => $cluster];
    }
    usort($hasil, function ($a, $b) { return strcmp($a['tanggal'], $b['tanggal']); });
    return $hasil;
}

// Kumpulan ID (per tipe) yang lagi bentrok, buat penanda ⚠️ cepat di baris tabel.
function idxKumpulkanIdBentrok($klasterBentrok) {
    $out = ['rapat' => [], 'agenda' => [], 'tamu' => []];
    foreach ($klasterBentrok as $klaster) {
        foreach ($klaster['items'] as $it) { $out[$it['type']][$it['id']] = true; }
    }
    return $out;
}

// Label "Senin, 06 Agustus 2026" berbahasa Indonesia buat header pengelompokan tanggal.
function idxLabelHariTanggal($tanggal) {
    static $hariIndo = ['Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => "Jum'at", 'Saturday' => 'Sabtu'];
    static $bulanIndo = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'];
    $ts = strtotime($tanggal);
    return $hariIndo[date('l', $ts)] . ', ' . date('d', $ts) . ' ' . $bulanIndo[(int) date('n', $ts)] . ' ' . date('Y', $ts);
}

// Render SATU set panel "Jadwal Bentrok" (minggu ATAU bulan) untuk wadah
// terpisah di kolom samping (menggantikan posisi "Pintasan Menu Cepat" yang
// digeser ke bawah) -- SENGAJA dipisah dari idxRenderPanelOperasional() di
// atas supaya jadwal bentrok tidak lagi menyatu/tercampur dengan tabel
// Rapat/Agenda/Tamu di panel Operasional Harian, biar lebih enak dilihat.
// Tetap dibungkus <div class="op-range-view" data-op-range="minggu|bulan">
// yang sama seperti panel Operasional Harian, jadi toggle "Minggu Ini" /
// "Bulan Ini" yang sudah ada otomatis ikut menyinkronkan wadah ini juga
// (lihat idxSwitchOpRange() di JS -- dia men-select SEMUA .op-range-view
// di halaman, tidak peduli di panel mana).
function idxRenderPanelBentrok($rangeKey, $rangeLabelKosong, $bentrok, $aktif) {
    ob_start();
    $kelasAktif = $aktif ? ' active' : '';
?>
<div class="op-range-view<?= $kelasAktif ?>" data-op-range="<?= $rangeKey ?>">
<?php if (!empty($bentrok)): ?>
  <div class="op-bentrok-list">
<?php foreach ($bentrok as $klaster):
      $jamMulaiK = min(array_column($klaster['items'], 'start'));
      $jamSelesaiK = max(array_column($klaster['items'], 'end'));
?>
    <div class="op-bentrok-card">
      <div class="op-bentrok-card-top">
        <span class="op-bentrok-date"><?= htmlspecialchars(idxLabelHariTanggal($klaster['tanggal'])) ?></span>
        <span class="op-bentrok-time"><?= date('H:i', $jamMulaiK) ?> - <?= date('H:i', $jamSelesaiK) ?></span>
      </div>
      <div class="op-bentrok-chiprow">
<?php foreach ($klaster['items'] as $itemK): ?>
        <span class="op-bentrok-chip op-bentrok-<?= $itemK['type'] ?>"><?= $itemK['icon'] ?> <?= htmlspecialchars($itemK['label']) ?> <b><?= $itemK['jam'] ?></b></span>
<?php endforeach; ?>
      </div>
    </div>
<?php endforeach; ?>
  </div>
<?php else: ?>
  <div class="op-bentrok-aman">
    <div class="op-bentrok-aman-icon">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
    </div>
    <div class="op-bentrok-aman-text">
      <b>Aman terkendali!</b>
      <span>Tidak ada jadwal yang bentrok <?= $rangeLabelKosong ?> ini.</span>
    </div>
  </div>
<?php endif; ?>
</div>
<?php
    return ob_get_clean();
}

$opBentrokMinggu = idxDeteksiJadwalBentrok(idxSusunEventOperasional($opDataMinggu));
$opBentrokBulan  = idxDeteksiJadwalBentrok(idxSusunEventOperasional($opDataBulan));
$opIdBentrokMinggu = idxKumpulkanIdBentrok($opBentrokMinggu);
$opIdBentrokBulan  = idxKumpulkanIdBentrok($opBentrokBulan);

// Nama bulan Indonesia (dipakai buat caption rentang & label tanggal).
function idxNamaBulanIndo($tanggal) {
    static $bulanIndo = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'];
    $ts = strtotime($tanggal);
    return $bulanIndo[(int) date('n', $ts)] . ' ' . date('Y', $ts);
}

// Caption singkat buat header panel, menjelaskan rentang tanggal yang lagi ditampilkan.
$opCaptionMinggu = 'Senin - Minggu, ' . date('d/m', strtotime($opMingguMulai)) . ' - ' . date('d/m/Y', strtotime($opMingguSelesai));
$opCaptionBulan  = 'Tanggal 1 - ' . date('d', strtotime($opBulanSelesai)) . ' ' . idxNamaBulanIndo($opBulanMulai);

// Baris header pemisah tanggal di dalam tabel (dipakai di ketiga tabel:
// Rapat/Agenda/Tamu) supaya jadwal "dikelompokkan per hari" -- begitu
// tanggalnya berbeda dari baris sebelumnya, header baru disisipkan.
function idxOpBarisHeaderTanggal($tanggal, $colspan, $todayStr, $jumlah) {
    $label = idxLabelHariTanggal($tanggal);
    $badgeHariIni = ($tanggal === $todayStr) ? ' <span class="op-date-today-badge">Hari Ini</span>' : '';
    $jumlahTxt = $jumlah . ($jumlah > 1 ? ' kegiatan' : ' kegiatan');
    return '<tr class="op-date-group"><td colspan="' . $colspan . '">' . htmlspecialchars($label) . $badgeHariIni
        . '<span class="op-date-count">' . $jumlahTxt . '</span></td></tr>';
}

// Versi "header tanggal" yang sama seperti idxOpBarisHeaderTanggal() di atas,
// tapi buat pengelompokan CARD (bukan baris <tr> tabel) -- dipakai di daftar
// kartu Rapat/Agenda/Tamu supaya tampilannya lebih enak dibaca (bukan tabel
// padat), namun tetap mempertahankan pengelompokan per tanggal + badge
// "Hari Ini" + jumlah kegiatan yang sudah ada sebelumnya.
function idxOpCardDateHeader($tanggal, $todayStr, $jumlah) {
    $label = idxLabelHariTanggal($tanggal);
    $badgeHariIni = ($tanggal === $todayStr) ? ' <span class="op-date-today-badge">Hari Ini</span>' : '';
    $jumlahTxt = $jumlah . ($jumlah > 1 ? ' kegiatan' : ' kegiatan');
    return '<div class="op-cardlist-datehead">' . htmlspecialchars($label) . $badgeHariIni
        . '<span class="op-date-count">' . $jumlahTxt . '</span></div>';
}

// Ikon SVG kecil dipakai berulang di kartu Rapat/Agenda/Tamu (orang & lokasi).
function idxOpIkonOrang() { return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>'; }
function idxOpIkonLokasi() { return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>'; }
function idxOpIkonJam() { return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><polyline points="12 7 12 12 15.5 14"/></svg>'; }

// Render SATU set panel Operasional Harian (Jadwal Bentrok + tabel Rapat +
// Agenda + Tamu) untuk satu rentang (minggu ATAU bulan). Dipanggil 2x dari
// HTML: sekali untuk data minggu ini, sekali untuk data bulan ini. Hasilnya
// dibungkus <div class="op-range-view" data-op-range="minggu|bulan"> supaya
// bisa di-switch tampil/sembunyi murni via JS (tanpa reload halaman).
function idxRenderPanelOperasional($rangeKey, $rangeLabelKosong, $data, $bentrok, $idBentrok, $todayStr, $aktif) {
    ob_start();
    $kelasAktif = $aktif ? ' active' : '';
?>
<div class="op-range-view<?= $kelasAktif ?>" data-op-range="<?= $rangeKey ?>">

  <div class="op-block">
    <div class="op-subtitle"><span class="op-emoji" style="background:#fff3c9;">🗓️</span>Rapat
      <span style="font-weight:700; font-size:11px; color:var(--ink-soft); margin-left:6px;">— dari halaman Manajemen Rapat</span>
    </div>
    <div class="op-cardlist-scroll">
      <div class="op-cardlist">
<?php if (empty($data['rapat'])): ?>
        <div class="op-cardlist-empty">
          <span class="op-cardlist-empty-icon">🗓️</span>
          <span>Tidak ada rapat terjadwal <?= $rangeLabelKosong ?> ini</span>
        </div>
<?php else:
        $rapatRowNo = 0; $tglBerjalanRpt = null;
        foreach ($data['rapat'] as $rowRapat): $rapatRowNo++;
            if ($rowRapat['tanggal'] !== $tglBerjalanRpt) {
                $tglBerjalanRpt = $rowRapat['tanggal'];
                $jmlHariIniRpt = 0;
                foreach ($data['rapat'] as $cekRpt) { if ($cekRpt['tanggal'] === $tglBerjalanRpt) $jmlHariIniRpt++; }
                echo idxOpCardDateHeader($tglBerjalanRpt, $todayStr, $jmlHariIniRpt);
            }
            $waktuRpt = idxFormatJamRange($rowRapat['waktu_mulai'], $rowRapat['waktu_selesai']);
            $statusRpt = idxRapatLiveStatus($rowRapat, time());
            $statusMapRpt = [
                'selesai'     => ['✅ Selesai', 'chip-green'],
                'lewat'       => ['✅ Selesai', 'chip-green'],
                'berlangsung' => ['🚀 Berlangsung', 'chip-amber'],
                'menunggu'    => ['⏳ Menunggu', 'chip-blue'],
            ];
            list($statusLabelRpt, $statusChipRpt) = $statusMapRpt[$statusRpt] ?? $statusMapRpt['menunggu'];
            $bentrokRpt = isset($idBentrok['rapat'][$rowRapat['id']]);
?>
        <div id="gsRapatRow-<?= $rangeKey ?>-<?= $rapatRowNo ?>" data-rapat-id="<?= (int) $rowRapat['id'] ?>" class="op-event-card op-event-clickable op-event-yellow<?= $bentrokRpt ? ' op-event-conflict' : '' ?>" onclick="goTo('rapat')" role="button" tabindex="0" onkeydown="if(event.key==='Enter')goTo('rapat')">
          <div class="op-event-top">
            <div class="op-event-title"><?= $bentrokRpt ? '<span class="op-conflict-flag" title="Jam bentrok dengan jadwal lain">⚠️</span> ' : '' ?><?= htmlspecialchars($rowRapat['judul']) ?></div>
            <span class="op-event-time op-event-time-yellow"><?= idxOpIkonJam() ?><?= $waktuRpt ?></span>
          </div>
          <div class="op-event-meta">
            <span class="op-event-meta-item"><?= idxOpIkonOrang() ?><?= htmlspecialchars($rowRapat['pic']) ?></span>
            <span class="op-event-meta-item"><?= idxOpIkonLokasi() ?><?= htmlspecialchars($rowRapat['lokasi'] !== '' ? $rowRapat['lokasi'] : '-') ?></span>
          </div>
          <div class="op-event-foot">
            <span class="chip <?= $statusChipRpt ?>"><?= $statusLabelRpt ?></span>
            <span class="op-event-actions">
<?php if ($statusRpt === 'selesai' || $statusRpt === 'lewat'): ?>
              <button class="op-event-archivebtn" onclick="event.stopPropagation(); idxOpArchive('rapat', <?= (int) $rowRapat['id'] ?>, this)" title="Arsipkan rapat ini ke Arsip Rapat">🗂️ Arsip</button>
<?php endif; ?>
              <button class="op-event-editbtn op-event-editbtn-yellow" onclick="event.stopPropagation(); goTo('rapat')" title="Kelola rapat ini di halaman Manajemen Rapat">✏️ Kelola</button>
            </span>
          </div>
        </div>
<?php endforeach; endif; ?>
      </div>
    </div>
  </div>

  <div class="op-block">
    <div class="op-subtitle"><span class="op-emoji" style="background:#dceafd;">🗓️</span>Agenda
      <span style="font-weight:700; font-size:11px; color:var(--ink-soft); margin-left:6px;">— dari halaman Agenda</span>
    </div>
    <div class="op-cardlist-scroll">
      <div class="op-cardlist">
<?php if (empty($data['agenda'])): ?>
        <div class="op-cardlist-empty">
          <span class="op-cardlist-empty-icon">🗓️</span>
          <span>Belum ada agenda <?= $rangeLabelKosong ?> ini</span>
        </div>
<?php else:
        $agendaRowNo = 0; $tglBerjalanAgn = null;
        foreach ($data['agenda'] as $rowAgenda): $agendaRowNo++;
            if ($rowAgenda['tanggal'] !== $tglBerjalanAgn) {
                $tglBerjalanAgn = $rowAgenda['tanggal'];
                $jmlHariIniAgn = 0;
                foreach ($data['agenda'] as $cekAgn) { if ($cekAgn['tanggal'] === $tglBerjalanAgn) $jmlHariIniAgn++; }
                echo idxOpCardDateHeader($tglBerjalanAgn, $todayStr, $jmlHariIniAgn);
            }
            $catKey    = $rowAgenda['kategori'];
            $chipClass = $catKey === 'direktur' ? 'chip-amber' : 'chip-blue';
            $chipLabel = $catKey === 'direktur' ? 'Agenda Direktur' : 'Agenda Perusahaan';
            $waktuAgn  = idxFormatJamRange($rowAgenda['waktu_mulai'], $rowAgenda['waktu_selesai']);
            $statusAgn = idxAgendaLiveStatus($rowAgenda, time());
            $statusMapAgn = [
                'selesai'     => ['Selesai', 'chip-green'],
                'berlangsung' => ['Berlangsung', 'chip-mint'],
                'terjadwal'   => ['Terjadwal', 'chip-grey'],
            ];
            list($statusLabelAgn, $statusChipAgn) = $statusMapAgn[$statusAgn] ?? $statusMapAgn['terjadwal'];
            $bentrokAgn = isset($idBentrok['agenda'][$rowAgenda['id']]);
?>
        <div id="gsAgendaRow-<?= $rangeKey ?>-<?= $agendaRowNo ?>" data-agenda-id="<?= (int) $rowAgenda['id'] ?>" class="op-event-card op-event-clickable op-event-blue<?= $bentrokAgn ? ' op-event-conflict' : '' ?>" onclick="goTo('agenda')" role="button" tabindex="0" onkeydown="if(event.key==='Enter')goTo('agenda')">
          <div class="op-event-top">
            <div class="op-event-title"><?= $bentrokAgn ? '<span class="op-conflict-flag" title="Jam bentrok dengan jadwal lain">⚠️</span> ' : '' ?><?= htmlspecialchars($rowAgenda['judul']) ?></div>
            <span class="op-event-time op-event-time-blue"><?= idxOpIkonJam() ?><?= $waktuAgn ?></span>
          </div>
          <div class="op-event-meta">
            <span class="op-event-meta-item"><?= idxOpIkonOrang() ?><?= htmlspecialchars($rowAgenda['penanggung_jawab']) ?></span>
            <span class="op-event-meta-item"><?= idxOpIkonLokasi() ?><?= htmlspecialchars($rowAgenda['lokasi'] !== '' ? $rowAgenda['lokasi'] : '-') ?></span>
          </div>
          <div class="op-event-foot">
            <span class="chip <?= $chipClass ?>"><?= $chipLabel ?></span>
            <span class="chip <?= $statusChipAgn ?>"><?= $statusLabelAgn ?></span>
            <span class="op-event-actions">
<?php if ($statusAgn === 'selesai'): ?>
              <button class="op-event-archivebtn" onclick="event.stopPropagation(); idxOpArchive('agenda', <?= (int) $rowAgenda['id'] ?>, this)" title="Arsipkan agenda ini ke Arsip Agenda">🗂️ Arsip</button>
<?php endif; ?>
              <button class="op-event-editbtn op-event-editbtn-blue" onclick="event.stopPropagation(); goTo('agenda')" title="Kelola agenda ini di halaman Agenda">✏️ Kelola</button>
            </span>
          </div>
        </div>
<?php endforeach; endif; ?>
      </div>
    </div>
  </div>

  <div class="op-block">
    <div class="op-subtitle"><span class="op-emoji" style="background:#fce0ee;">📖</span>Tamu
      <span style="font-weight:700; font-size:11px; color:var(--ink-soft); margin-left:6px;">— dari halaman Buku Tamu</span>
    </div>
    <div class="op-cardlist-scroll">
      <div class="op-cardlist">
<?php if (empty($data['tamu'])): ?>
        <div class="op-cardlist-empty">
          <span class="op-cardlist-empty-icon">📖</span>
          <span>Belum ada tamu terjadwal <?= $rangeLabelKosong ?> ini</span>
        </div>
<?php else:
        $tamuRowNo = 0; $tglBerjalanTmu = null;
        foreach ($data['tamu'] as $rowTamu): $tamuRowNo++;
            if ($rowTamu['tanggal'] !== $tglBerjalanTmu) {
                $tglBerjalanTmu = $rowTamu['tanggal'];
                $jmlHariIniTmu = 0;
                foreach ($data['tamu'] as $cekTmu) { if ($cekTmu['tanggal'] === $tglBerjalanTmu) $jmlHariIniTmu++; }
                echo idxOpCardDateHeader($tglBerjalanTmu, $todayStr, $jmlHariIniTmu);
            }
            $waktuTmu = idxFormatJamRange($rowTamu['waktu'], $rowTamu['selesai']);
            $statusTmu = $rowTamu['status'] ?? 'upcoming';
            $statusMapTmu = [
                'done'     => ['✔ Selesai', 'chip-green'],
                'pending'  => ['● Berlangsung', 'chip-amber'],
                'upcoming' => ['📅 Akan Datang', 'chip-blue'],
                'deleted'  => ['Dibatalkan', 'chip-red'],
            ];
            $statusChipTmu = $statusMapTmu[$statusTmu] ?? $statusMapTmu['upcoming'];
            $bentrokTmu = isset($idBentrok['tamu'][$rowTamu['id']]);
?>
        <div id="gsTamuRow-<?= $rangeKey ?>-<?= $tamuRowNo ?>" data-tamu-id="<?= (int) $rowTamu['id'] ?>" class="op-event-card op-event-clickable op-event-pink<?= $bentrokTmu ? ' op-event-conflict' : '' ?>" onclick="goTo('tamu')" role="button" tabindex="0" onkeydown="if(event.key==='Enter')goTo('tamu')">
          <div class="op-event-top">
            <div class="op-event-title"><?= $bentrokTmu ? '<span class="op-conflict-flag" title="Jam bentrok dengan jadwal lain">⚠️</span> ' : '' ?><?= htmlspecialchars($rowTamu['instansi']) ?></div>
            <span class="op-event-time op-event-time-pink"><?= idxOpIkonJam() ?><?= $waktuTmu ?></span>
          </div>
          <div class="op-event-meta">
            <span class="op-event-meta-item"><?= idxOpIkonOrang() ?><?= htmlspecialchars($rowTamu['penanggung_jawab']) ?><?= !empty($rowTamu['jabatan']) ? ' · ' . htmlspecialchars($rowTamu['jabatan']) : '' ?></span>
            <span class="op-event-meta-item"><?= idxOpIkonLokasi() ?><?= htmlspecialchars($rowTamu['keperluan']) ?></span>
<?php if (!empty($rowTamu['email'])): ?>
            <span class="op-event-meta-item op-event-meta-full">✉️ <?= htmlspecialchars($rowTamu['email']) ?></span>
<?php endif; ?>
          </div>
          <div class="op-event-foot">
            <span class="chip <?= $statusChipTmu[1] ?>"><?= $statusChipTmu[0] ?></span>
            <span class="op-event-actions">
<?php if ($statusTmu === 'done'): ?>
              <button class="op-event-archivebtn" onclick="event.stopPropagation(); idxOpArchive('tamu', <?= (int) $rowTamu['id'] ?>, this)" title="Arsipkan tamu ini ke Arsip Tamu">🗂️ Arsip</button>
<?php endif; ?>
              <button class="op-event-editbtn op-event-editbtn-pink" onclick="event.stopPropagation(); goTo('tamu')" title="Kelola tamu ini di halaman Buku Tamu">✏️ Kelola</button>
            </span>
          </div>
        </div>
<?php endforeach; endif; ?>
      </div>
    </div>
  </div>


</div>
<?php
    return ob_get_clean();
}

// ---------------------------------------------------------------------
// Data buat widget KALENDER di dashboard: gabungan Agenda + Rapat + Tamu
// dari SEMUA tanggal (bukan cuma hari ini), dikelompokkan per tanggal.
// Ini murni query & susun ulang, tidak butuh tabel/database tambahan.
// Dipakai buat nampilin titik penanda di tanggal yang ada jadwalnya, dan
// daftar info pas tanggalnya diklik.
// ---------------------------------------------------------------------
$calEventsByDate = [];
function bkjlTambahCalEvent(&$map, $tanggal, $item) {
    if (!$tanggal) return;
    $d = new DateTime($tanggal);
    // PENTING: bulan di sini HARUS 0-indexed (0=Jan ... 11=Des) supaya cocok
    // sama calState.month di JS (hasil dari Date.getMonth() yang juga 0-indexed).
    // format('n') PHP itu 1-12, jadi wajib dikurangi 1, kalau tidak titik agenda
    // bakal nempel di bulan yang salah (misal Agustus ketuker jadi September).
    $key = $d->format('Y') . '-' . ((int) $d->format('n') - 1) . '-' . $d->format('j');
    if (!isset($map[$key])) $map[$key] = [];
    $map[$key][] = $item;
}

$qCalAgenda = mysqli_query($koneksi, "SELECT * FROM agenda WHERE diarsipkan = 0 ORDER BY waktu_mulai ASC");
if ($qCalAgenda) {
    while ($ag = mysqli_fetch_assoc($qCalAgenda)) {
        bkjlTambahCalEvent($calEventsByDate, $ag['tanggal'], [
            'title'  => '🗓️ ' . $ag['judul'],
            'time'   => substr((string) $ag['waktu_mulai'], 0, 5),
            'detail' => trim(($ag['penanggung_jawab'] ? 'PJ: ' . $ag['penanggung_jawab'] . ' · ' : '') . $ag['lokasi']),
            'status' => $ag['status'],
            'type'   => 'agenda',
        ]);
    }
}

$qCalRapat = mysqli_query($koneksi, "SELECT * FROM rapat WHERE diarsipkan = 0 ORDER BY waktu_mulai ASC");
if ($qCalRapat) {
    while ($rp = mysqli_fetch_assoc($qCalRapat)) {
        bkjlTambahCalEvent($calEventsByDate, $rp['tanggal'], [
            'title'  => '🤝 ' . $rp['judul'],
            'time'   => substr((string) $rp['waktu_mulai'], 0, 5),
            'detail' => trim(($rp['pic'] ? 'PIC: ' . $rp['pic'] . ' · ' : '') . $rp['lokasi']),
            'status' => $rp['status'],
            'type'   => 'rapat',
        ]);
    }
}

$qCalTamu = mysqli_query($koneksi, "SELECT * FROM tamu WHERE diarsipkan = 0 AND status != 'deleted' ORDER BY waktu ASC");
if ($qCalTamu) {
    while ($tm = mysqli_fetch_assoc($qCalTamu)) {
        bkjlTambahCalEvent($calEventsByDate, $tm['tanggal'], [
            'title'  => '🏢 ' . $tm['instansi'],
            'time'   => $tm['waktu'] ? substr($tm['waktu'], 0, 5) : '',
            'detail' => trim(($tm['penanggung_jawab'] ? 'PJ: ' . $tm['penanggung_jawab'] . ' · ' : '') . ($tm['keperluan'] ?: '')),
            'status' => $tm['status'],
            'type'   => 'tamu',
        ]);
    }
}
// Urutkan tiap tanggal berdasarkan jam biar rapi
foreach ($calEventsByDate as &$calDayItems) {
    usort($calDayItems, function ($a, $b) { return strcmp($a['time'] ?: '99:99', $b['time'] ?: '99:99'); });
}
unset($calDayItems);

// Angka untuk 2 kartu statistik tambahan di baris atas dashboard (mengisi slot
// yang tadinya kosong di sebelah "Surat Masuk Aktif" & "Persetujuan Surat Keluar").
// Diambil langsung dari database juga, real-time.
// "Agenda Aktif" = agenda yang AKAN DILAKSANAKAN HARI INI (tanggal = hari ini)
// dan statusnya masih 'belum' (belum selesai) & belum diarsipkan. Begitu status
// agenda diubah jadi 'selesai' (lewat halaman Agenda), agenda itu otomatis tidak
// terhitung lagi di sini. Query ini juga dipakai ulang oleh agenda_api.php
// (action=stat_dashboard) supaya kartu bisa update sendiri tanpa reload halaman.
// "Surat Masuk Aktif" = surat masuk yang BELUM diarsipkan (sesuai subtitle
// kartu "Surat belum diarsipkan"), tidak peduli statusnya apa — jadi surat
// yang baru dipulihkan dari arsip langsung ikut kehitung lagi di sini,
// meskipun statusnya masih "Selesai" dari sebelum diarsipkan.
// Diambil langsung dari tabel surat_masuk, real-time, tidak lagi dari localStorage.
$totalSuratMasukAktif = 0;
$qStatSuratMasuk = mysqli_query($koneksi, "SELECT COUNT(*) AS jml FROM surat_masuk WHERE diarsipkan = 0");
if ($qStatSuratMasuk) {
    $totalSuratMasukAktif = (int) (mysqli_fetch_assoc($qStatSuratMasuk)['jml'] ?? 0);
}

$totalAgendaAktif = 0;
$qStatAgenda = mysqli_query($koneksi, "SELECT COUNT(*) AS jml FROM agenda WHERE diarsipkan = 0 AND status = 'belum' AND tanggal = '$todayStr'");
if ($qStatAgenda) {
    $totalAgendaAktif = (int) (mysqli_fetch_assoc($qStatAgenda)['jml'] ?? 0);
}
$totalTamuHariIni = 0;
$qStatTamu = mysqli_query($koneksi, "SELECT COUNT(*) AS jml FROM tamu WHERE diarsipkan = 0 AND tanggal = '$todayStr'");
if ($qStatTamu) {
    $totalTamuHariIni = (int) (mysqli_fetch_assoc($qStatTamu)['jml'] ?? 0);
}

// "Persetujuan Surat Keluar" = jumlah surat keluar yang statusnya masih
// "Menunggu Approval" (belum diarsipkan), bukan yang sudah "Disetujui".
// Diambil langsung dari tabel surat_keluar, real-time, sama seperti stat lain
// di atas — tidak lagi bergantung pada localStorage yang cuma keisi kalau
// halaman surat_keluar.php pernah dibuka.
$totalSuratKeluarMenungguApproval = 0;
$qStatSuratKeluar = mysqli_query($koneksi, "SELECT COUNT(*) AS jml FROM surat_keluar WHERE diarsipkan = 0 AND status = 'Menunggu Approval'");
if ($qStatSuratKeluar) {
    $totalSuratKeluarMenungguApproval = (int) (mysqli_fetch_assoc($qStatSuratKeluar)['jml'] ?? 0);
}

// =====================================================================
// Panel "Upcoming Deadline Rapat" di dashboard: diambil LANGSUNG dari
// tabel `rapat` (database asli, sama seperti halaman Manajemen Rapat),
// jadi begitu ada rapat baru yang belum "selesai" & jadwalnya akan
// datang, otomatis nongol di sini tanpa perlu apa-apa lagi.
// Rapat yang statusnya "selesai" atau yang jadwalnya sudah lewat tidak
// ditampilkan, sama seperti logic di halaman Manajemen Rapat.
// =====================================================================
$rptUpcomingDashboard = [];
$qRptUp = mysqli_query($koneksi, "SELECT id, judul, lokasi, tanggal, waktu_mulai, waktu_selesai, status
    FROM rapat
    WHERE status != 'selesai'
      AND (tanggal > CURDATE() OR (tanggal = CURDATE() AND waktu_selesai >= CURTIME()))
    ORDER BY tanggal ASC, waktu_mulai ASC
    LIMIT 15");
if ($qRptUp) {
    while ($r = mysqli_fetch_assoc($qRptUp)) {
        $rptUpcomingDashboard[] = $r;
    }
}

// =====================================================================
// Widget "Tugas Deadline" (dashboard): SEKARANG OTOMATIS, bukan diketik
// manual lagi. Ditarik langsung dari 5 sumber: Rapat, Agenda, Surat Masuk
// (yang belum "Selesai", dianggap harus ditindaklanjuti dalam
// $suratMasukSlaHari hari sejak tgl_terima), Surat Keluar (yang belum
// "Terkirim"/"Diterima", deadline-nya = tanggal_pengajuan), dan Buku Tamu
// (kunjungan yang belum "done"). Cuma item yang deadline-nya mulai dari
// SEKARANG sampai $deadlineLookaheadDays hari ke depan yang ditampilkan;
// begitu lewat waktunya, otomatis hilang sendiri karena dihitung ulang
// setiap halaman dimuat (bukan disimpan manual). Read-only, tidak ada
// checklist/hapus manual lagi.
//
// Mau ubah "berapa hari sebelum H mulai muncul"? Tinggal ganti angka
// $deadlineLookaheadDays di bawah ini. Mau ubah SLA surat masuk (default
// 3 hari kerja sejak diterima)? Ganti $suratMasukSlaHari.
// =====================================================================
$deadlineLookaheadDays = 7;
$suratMasukSlaHari = 3;
$autoDeadlineItems = [];

// --- 1. Rapat (Manajemen Rapat): deadline = tanggal + jam mulai rapat ---
$qDlRapat = mysqli_query($koneksi, "SELECT judul, tanggal, waktu_mulai FROM rapat
    WHERE diarsipkan = 0 AND status != 'selesai'
      AND TIMESTAMP(tanggal, waktu_mulai) BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL $deadlineLookaheadDays DAY)
    ORDER BY tanggal ASC, waktu_mulai ASC");
if ($qDlRapat) {
    while ($row = mysqli_fetch_assoc($qDlRapat)) {
        $autoDeadlineItems[] = [
            'icon'     => '🗓️',
            'text'     => 'Rapat: ' . $row['judul'],
            'due_ts'   => strtotime($row['tanggal'] . ' ' . $row['waktu_mulai']),
            'has_time' => true,
        ];
    }
}

// --- 2. Agenda: deadline = tanggal + jam mulai agenda ---
$qDlAgenda = mysqli_query($koneksi, "SELECT judul, tanggal, waktu_mulai FROM agenda
    WHERE diarsipkan = 0 AND status != 'selesai'
      AND TIMESTAMP(tanggal, waktu_mulai) BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL $deadlineLookaheadDays DAY)
    ORDER BY tanggal ASC, waktu_mulai ASC");
if ($qDlAgenda) {
    while ($row = mysqli_fetch_assoc($qDlAgenda)) {
        $autoDeadlineItems[] = [
            'icon'     => '📌',
            'text'     => 'Agenda: ' . $row['judul'],
            'due_ts'   => strtotime($row['tanggal'] . ' ' . $row['waktu_mulai']),
            'has_time' => true,
        ];
    }
}

// --- 3. Surat Masuk: belum "Selesai", deadline = tgl_terima + SLA ---
$qDlSm = mysqli_query($koneksi, "SELECT perihal, tgl_terima FROM surat_masuk
    WHERE diarsipkan = 0 AND status != 'Selesai'
      AND DATE_ADD(tgl_terima, INTERVAL $suratMasukSlaHari DAY)
          BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL $deadlineLookaheadDays DAY)
    ORDER BY tgl_terima ASC");
if ($qDlSm) {
    while ($row = mysqli_fetch_assoc($qDlSm)) {
        $autoDeadlineItems[] = [
            'icon'     => '📥',
            'text'     => 'Proses surat masuk: ' . $row['perihal'],
            'due_ts'   => strtotime($row['tgl_terima']) + ($suratMasukSlaHari * 86400),
            'has_time' => false,
        ];
    }
}

// --- 4. Surat Keluar: belum "Terkirim"/"Diterima", deadline = tanggal_pengajuan ---
$qDlSk = mysqli_query($koneksi, "SELECT perihal, tanggal_pengajuan FROM surat_keluar
    WHERE diarsipkan = 0 AND status NOT IN ('Terkirim', 'Diterima')
      AND tanggal_pengajuan BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL $deadlineLookaheadDays DAY)
    ORDER BY tanggal_pengajuan ASC");
if ($qDlSk) {
    while ($row = mysqli_fetch_assoc($qDlSk)) {
        $autoDeadlineItems[] = [
            'icon'     => '📤',
            'text'     => 'Kirim surat: ' . $row['perihal'],
            'due_ts'   => strtotime($row['tanggal_pengajuan']),
            'has_time' => false,
        ];
    }
}

// --- 5. Buku Tamu: kunjungan yang belum "done", deadline = tanggal + jam ---
$qDlTamu = mysqli_query($koneksi, "SELECT penanggung_jawab, instansi, tanggal, waktu FROM tamu
    WHERE diarsipkan = 0 AND status != 'done'
      AND TIMESTAMP(tanggal, waktu) BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL $deadlineLookaheadDays DAY)
    ORDER BY tanggal ASC, waktu ASC");
if ($qDlTamu) {
    while ($row = mysqli_fetch_assoc($qDlTamu)) {
        $namaTamuDl = $row['penanggung_jawab'] ?: $row['instansi'];
        $autoDeadlineItems[] = [
            'icon'     => '🙋',
            'text'     => 'Terima tamu: ' . $namaTamuDl,
            'due_ts'   => strtotime($row['tanggal'] . ' ' . $row['waktu']),
            'has_time' => true,
        ];
    }
}

// Gabungkan & urutkan semua sumber berdasarkan deadline paling dekat duluan
usort($autoDeadlineItems, function ($a, $b) {
    return $a['due_ts'] <=> $b['due_ts'];
});

// Bikin label deadline yang enak dibaca: "Hari ini, 14:00" / "Besok" / "3 hari lagi"
$todayMidnightTs = strtotime(date('Y-m-d'));
foreach ($autoDeadlineItems as &$dlItem) {
    $diffDays = (int) floor(($dlItem['due_ts'] - $todayMidnightTs) / 86400);
    if ($diffDays <= 0) {
        $dlItem['due_label'] = $dlItem['has_time'] ? ('Hari ini, ' . date('H:i', $dlItem['due_ts'])) : 'Hari ini';
    } elseif ($diffDays === 1) {
        $dlItem['due_label'] = $dlItem['has_time'] ? ('Besok, ' . date('H:i', $dlItem['due_ts'])) : 'Besok';
    } else {
        $dlItem['due_label'] = $diffDays . ' hari lagi';
    }
}
unset($dlItem);

// Hitung label urgensi (Hari ini / Besok / tanggal biasa / sedang berlangsung)
// persis seperti rptDeadlineUrgency() di JS halaman Manajemen Rapat, tapi
// versi PHP supaya bisa langsung dirender server-side di dashboard.
function idx_rapat_urgency($tanggal, $waktuMulai, $waktuSelesai) {
    $now = new DateTime();
    $start = DateTime::createFromFormat('Y-m-d H:i:s', $tanggal . ' ' . $waktuMulai);
    $end = DateTime::createFromFormat('Y-m-d H:i:s', $tanggal . ' ' . $waktuSelesai);
    if ($start && $end && $now >= $start && $now <= $end) {
        return ['cls' => 'ongoing', 'chip' => 'chip-green', 'label' => '🚀 Berlangsung'];
    }
    $timeStr = $start ? $start->format('H:i') : '';
    if ($start && $start->format('Y-m-d') === $now->format('Y-m-d')) {
        return ['cls' => 'urgent', 'chip' => 'chip-red', 'label' => '❗ Hari ini, ' . $timeStr];
    }
    $tomorrow = (clone $now)->modify('+1 day');
    if ($start && $start->format('Y-m-d') === $tomorrow->format('Y-m-d')) {
        return ['cls' => 'warning', 'chip' => 'chip-amber', 'label' => '⚠️ Besok, ' . $timeStr];
    }
    $bulanPendek = ['01'=>'Jan','02'=>'Feb','03'=>'Mar','04'=>'Apr','05'=>'Mei','06'=>'Jun','07'=>'Jul','08'=>'Agu','09'=>'Sep','10'=>'Okt','11'=>'Nov','12'=>'Des'];
    $label = $start ? ('✨ ' . $start->format('d') . ' ' . $bulanPendek[$start->format('m')] . ', ' . $timeStr) : '✨';
    return ['cls' => 'chill', 'chip' => 'chip-blue', 'label' => $label];
}
?>
<!DOCTYPE html>
<html lang="id" class="loading">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard - Sekretariat Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@500;600;700;800&family=Nunito:wght@400;600;700;800&family=Fredoka:wght@400;500;600;700&family=Comic+Neue:wght@400;700&family=Patrick+Hand&display=swap" rel="stylesheet">
<style>
  :root{
    --font-body:'Nunito',sans-serif;
    --font-heading:'Baloo 2',sans-serif;
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
  body{
    margin:0; font-family:var(--font-body);
    background:
      radial-gradient(circle at 8% 12%, #d7e8fb 0%, transparent 40%),
      radial-gradient(circle at 95% 8%, #f0e2fb 0%, transparent 42%),
      radial-gradient(circle at 90% 90%, #fbe3ec 0%, transparent 38%),
      var(--cream);
    color:var(--ink); min-height:100vh;
  }
  h1,h2,h3,h4{font-family:var(--font-heading); margin:0;}
  .app{display:flex; min-height:100vh;}

  /* ---------- SIDEBAR ---------- */
  .sidebar{width:238px; flex:0 0 238px; background:var(--cream); padding:26px 18px; display:flex; flex-direction:column; gap:6px; border-right:1px solid #eee2cd; position:sticky; top:0; height:100vh; overflow-y:auto;}
  .brand{display:flex; align-items:center; gap:10px; padding:0 6px 22px; position:relative;}
  .brand-badge{width:56px; height:56px; border-radius:18px; background:linear-gradient(160deg,var(--lavender),var(--blue)); display:flex; align-items:center; justify-content:center; box-shadow:var(--shadow); position:relative; overflow:hidden; flex:0 0 56px; animation:brandBounce 4.2s ease-in-out infinite; transition:transform .25s;}
  .brand-badge-img{width:100%; height:100%; object-fit:cover; display:block;}
  .brand-badge:hover{transform:scale(1.08) rotate(-6deg);}
  .brand-badge svg{position:relative; z-index:1;}
  .brand-sparkle{position:absolute; font-size:11px; pointer-events:none; animation:sparkleTwinkle 2.4s ease-in-out infinite;}
  .brand-sparkle.b1{top:-6px; right:-5px; animation-delay:.2s;}
  .brand-sparkle.b2{bottom:-3px; left:-7px; font-size:8px; animation-delay:1.1s;}
  .brand-name{font-weight:800; font-size:15px; color:var(--ink); line-height:1.1; display:flex; align-items:center; gap:4px;}
  .brand-name .heart{font-size:11px; animation:pfWiggle 2.6s ease-in-out infinite;}
  .brand-sub{font-size:9.5px; color:#8a5fce; font-weight:800; letter-spacing:.05em; background:linear-gradient(135deg,#f3edff,#fdeef7); display:inline-block; padding:2px 9px; border-radius:999px; margin-top:4px;}
  @keyframes brandBounce{ 0%,100%{transform:translateY(0) rotate(0deg);} 50%{transform:translateY(-2px) rotate(2deg);} }
  .nav-label{font-size:10.5px; font-weight:800; color:#c8b98a; letter-spacing:.08em; margin:14px 10px 6px;}
  .nav-item{display:flex; align-items:center; gap:10px; padding:10px 12px; border-radius:14px; font-weight:700; font-size:13.5px; color:#8a8672; cursor:pointer; position:relative; user-select:none; transition:background .15s, color .15s;}
  .nav-item svg{width:17px; height:17px; flex:0 0 17px; transition:transform .2s ease;}
  .nav-item:hover svg{animation:iconWiggle .5s ease-in-out;}
  .nav-item.active svg{animation:iconPop .4s ease;}
  .nav-item:hover{background:#f6f0e2;}
  .nav-item.active{background:linear-gradient(135deg,#fff, #f5eefe); color:#8a5fce; box-shadow:var(--shadow);}
  .nav-item.active.masuk-active{color:#e46a9b; background:linear-gradient(135deg,#fff, #fef2fa);}
  .nav-item .badge{margin-left:auto; background:var(--pink-deep); color:#fff; font-size:10px; font-weight:800; padding:1px 7px; border-radius:999px;}
  .sidebar-user{margin-top:auto; display:flex; align-items:center; gap:10px; padding:12px; background:var(--white); border-radius:18px; box-shadow:var(--shadow);}
  .sidebar-user .av{width:36px; height:36px; border-radius:50%; background:var(--lavender); display:flex; align-items:center; justify-content:center;}
  .sidebar-user .name{font-weight:800; font-size:12.5px;}
  .sidebar-user .role{font-size:10px; color:var(--ink-soft); font-weight:700;}

  /* ---------- MAIN ---------- */
  .main{flex:1; padding:22px 30px 40px; position:relative; min-width:0;}
  .topbar{display:flex; align-items:center; gap:14px; margin-bottom:22px; position:relative; z-index:100;}
  .search-pill{flex:1; max-width:420px; display:flex; align-items:center; gap:8px; background:var(--white); border-radius:999px; padding:11px 18px; box-shadow:var(--shadow);}
  .search-pill input{border:none; outline:none; font-family:inherit; font-size:13px; flex:1; background:transparent; color:var(--ink);}
  .search-pill svg{width:16px; height:16px; color:var(--ink-soft); transition:transform .3s ease;}
  .search-pill:focus-within svg{transform:scale(1.15) rotate(-8deg); color:#8a5fce;}

  /* ---------- GLOBAL SEARCH (kotak pencarian dashboard) ---------- */
  .global-search-results{
    position:absolute; top:calc(100% + 8px); left:0; right:0;
    background:var(--white); border-radius:16px; box-shadow:0 10px 30px rgba(0,0,0,.14);
    max-height:420px; overflow-y:auto; z-index:500; display:none; padding:6px;
  }
  .global-search-results.show{display:block;}
  .gs-group-label{font-size:10.5px; font-weight:800; letter-spacing:.04em; text-transform:uppercase; color:var(--ink-soft); padding:10px 12px 4px;}
  .gs-item{display:flex; align-items:center; gap:10px; padding:9px 12px; border-radius:12px; cursor:pointer; transition:background .15s ease;}
  .gs-item:hover, .gs-item.gs-active{background:#f3effb;}
  html.dark-mode .gs-item:hover, html.dark-mode .gs-item.gs-active{background:#243350;}
  .gs-item-ico{width:30px; height:30px; border-radius:10px; flex:0 0 30px; display:flex; align-items:center; justify-content:center; font-size:14px; background:#efeaf9;}
  .gs-item-text{min-width:0; flex:1;}
  .gs-item-title{font-size:12.5px; font-weight:700; color:var(--ink); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;}
  .gs-item-title mark{background:#ffe58a; color:inherit; border-radius:3px; padding:0 1px;}
  .gs-item-sub{font-size:10.5px; font-weight:600; color:var(--ink-soft); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;}
  .gs-empty{padding:16px 12px; text-align:center; font-size:12px; font-weight:700; color:var(--ink-soft);}
  @keyframes gsHitFlash{
    0%{background:#fff1b8;}
    100%{background:transparent;}
  }
  .gs-hit-flash{animation:gsHitFlash 1.8s ease;}
  .topbar-spacer{flex:1;}
  .icon-chip{width:40px; height:40px; border-radius:14px; background:var(--white); box-shadow:var(--shadow); display:flex; align-items:center; justify-content:center; color:var(--yellow-deep); cursor:pointer; transition:transform .2s ease;}
  .icon-chip svg{transition:transform .2s ease;}
  .icon-chip:hover{transform:translateY(-3px);}
  .icon-chip:hover svg{animation:iconRing .6s ease-in-out;}
  .avatar-chip{width:40px; height:40px; border-radius:50%; background:linear-gradient(160deg,var(--blue),var(--lavender)); display:flex; align-items:center; justify-content:center; box-shadow:var(--shadow); transition:transform .25s ease;}
  .avatar-chip:hover{transform:scale(1.08) rotate(-4deg);}
  .breadcrumb{font-size:11.5px; font-weight:800; color:var(--ink-soft); margin-bottom:4px; display:flex; align-items:center; gap:6px;}
  .breadcrumb .sep{opacity:.5;}
  .breadcrumb .current{color:#8a5fce;}

  .page-title-row{display:flex; align-items:center; gap:10px; margin-bottom:6px;}
  .page-title{font-size:26px; font-weight:800; color:var(--ink);}
  .page-title-deco{font-size:18px; display:inline-block; animation:iconFloat 2.8s ease-in-out infinite;}
  .page-title-deco:nth-of-type(2){animation-delay:.5s;}
  .page-title-deco:nth-of-type(3){animation-delay:1s;}
  .page-subtitle{font-size:13px; color:var(--ink-soft); font-weight:700; margin-bottom:16px;}

  .btn-new{display:flex; align-items:center; gap:7px; border:none; cursor:pointer; font-family:inherit; font-weight:800; font-size:12.5px; padding:10px 18px; border-radius:14px; box-shadow:var(--shadow); background:linear-gradient(135deg,#c9b6f2,#a98ee8); color:#fff;}
  .btn-new svg{width:14px; height:14px; transition:transform .3s ease;}
  .btn-new:hover svg{transform:rotate(180deg) scale(1.15);}

  /* ---------- TOOLBAR ---------- */
  .toolbar{display:flex; flex-wrap:wrap; gap:10px; align-items:center; margin-bottom:20px;}
  .toolbar input[type=text], .toolbar select, .toolbar input[type=date]{
    font-family:inherit; font-size:12.5px; font-weight:700; color:var(--ink);
    background:var(--white); border:1.5px solid #f0e6d3; border-radius:14px; padding:10px 14px;
    box-shadow:0 2px 8px rgba(150,130,180,.06);
  }
  .toolbar input[type=text]{flex:1; min-width:220px;}
  .toolbar-spacer{flex:1;}
  .btn-export{display:flex; align-items:center; gap:7px; border:none; cursor:pointer; font-family:inherit; font-weight:800; font-size:12.5px; padding:10px 16px; border-radius:14px; box-shadow:var(--shadow);}
  .btn-export svg{width:14px; height:14px;}
  .btn-export.excel{background:#ffe1ec; color:#d94f81;}
  .btn-export.pdf{background:#fff2cf; color:#c9932c;}

  /* ---------- TOP CARDS ROW ---------- */
  .cards-row{display:grid; grid-template-columns:1fr 1fr 1.3fr; gap:16px; margin-bottom:20px;}
  .cards-row.even3{grid-template-columns:1fr 1fr 1fr;}
  .panel{background:var(--white); border-radius:24px; padding:18px 20px; box-shadow:var(--shadow); position:relative; overflow:hidden; display:flex; flex-direction:column;}
  .panel h3{font-size:14.5px; font-weight:800; color:var(--ink); margin-bottom:2px;}
  .panel .sub{font-size:11px; color:var(--ink-soft); font-weight:700; margin-bottom:12px;}
  .panel-footer{margin-top:auto; padding-top:14px; border-top:1px dashed #eee2cd; display:flex; align-items:flex-start; gap:9px;}
  .panel-footer .pf-icon{width:26px; height:26px; border-radius:10px; flex:0 0 26px; display:flex; align-items:center; justify-content:center; font-size:13px;}
  .panel-footer .pf-text{font-size:11.5px; font-weight:700; color:var(--ink-soft); line-height:1.45; padding-top:3px;}
  .panel-footer .pf-text b{color:var(--ink);}
  .mini-bar-block{margin-top:22px;}
  .mini-bar-block .mbb-label{display:flex; justify-content:space-between; font-size:10.5px; font-weight:800; color:var(--ink-soft); margin-bottom:7px;}
  .mini-progress-bar{display:flex; height:11px; border-radius:999px; overflow:hidden; background:#f2ecdd;}
  .mini-progress-bar span{height:100%;}
  .donut-wrap{display:flex; align-items:center; gap:14px;}
  .legend-list{display:flex; flex-direction:column; gap:9px; flex:1;}
  .legend-row{display:flex; align-items:center; justify-content:space-between; font-size:12px; font-weight:700; color:var(--ink); gap:10px;}
  .legend-left{display:flex; align-items:center; gap:8px; white-space:nowrap;}
  .legend-dot{width:9px; height:9px; border-radius:50%; display:inline-block; flex:0 0 auto;}
  .legend-val{color:var(--ink-soft); font-weight:800; white-space:nowrap;}

  /* donut panel: stack chart on top, legend below full-width so labels never wrap awkwardly */
  .donut-wrap-stack{flex-direction:column; align-items:stretch; gap:16px;}
  .donut-wrap-stack .donut-chart-holder{display:flex; justify-content:center;}
  .donut-wrap-stack .legend-list{width:100%; gap:10px;}
  .donut-wrap-stack .legend-row{padding:6px 2px; border-bottom:1px solid #f4eee0;}
  .donut-wrap-stack .legend-row:last-child{border-bottom:none;}

  .trend-panel .cloud-row{display:flex; justify-content:space-between; align-items:flex-end; margin-top:14px; padding:0 4px;}
  .trend-day{display:flex; flex-direction:column; align-items:center; gap:6px; font-size:10.5px; font-weight:800; color:var(--ink-soft);}
  .trend-cloud{width:38px; height:26px; border-radius:50% 50% 40% 40%; background:var(--blue); display:flex; align-items:center; justify-content:center; color:#fff; font-size:10px; font-weight:800; position:relative; box-shadow:0 3px 8px rgba(150,180,230,.4);}
  .trend-cloud::before, .trend-cloud::after{content:""; position:absolute; background:inherit; border-radius:50%;}
  .trend-cloud::before{width:16px; height:16px; top:-7px; left:5px;}
  .trend-cloud::after{width:14px; height:14px; top:-5px; right:4px;}
  .trend-cloud.peak{background:var(--pink-deep); box-shadow:0 3px 8px rgba(240,150,190,.5);}
  .trend-cloud.lav{background:var(--lavender); color:#5b4590; box-shadow:0 3px 8px rgba(190,160,230,.4);}
  .trend-cloud.lav.peak{background:var(--pink-deep); color:#fff;}

  /* ---------- TREND BAR CHART MODEL ---------- */
  .barchart-wrap{display:flex; gap:10px; align-items:stretch; height:120px; margin-top:14px;}
  .barchart-yaxis{display:flex; flex-direction:column; justify-content:space-between; font-size:9.5px; font-weight:800; color:var(--ink-soft); padding:2px 0;}
  .barchart-bars{flex:1; display:flex; align-items:flex-end; gap:10px; border-left:1.5px solid #f0eadd; border-bottom:1.5px solid #f0eadd; padding-left:8px;}
  .barchart-col{flex:1; display:flex; flex-direction:column; align-items:center; justify-content:flex-end; height:100%; gap:6px; position:relative;}
  .barchart-bar{width:60%; border-radius:6px 6px 0 0; background:linear-gradient(180deg,#c9b6f2,#a98ee8); position:relative;}
  .barchart-bar.peak{background:linear-gradient(180deg,#f6a8c6,#ef85b0);}
  .barchart-bar .bc-val{position:absolute; top:-18px; left:50%; transform:translateX(-50%); font-size:10px; font-weight:800; color:var(--ink-soft);}
  .barchart-col .bc-lbl{font-size:9.5px; font-weight:800; color:var(--ink-soft); margin-top:2px;}
  .trend-summary{display:flex; gap:22px; margin-top:16px; padding-top:14px; border-top:1px dashed #efe4d0;}
  .trend-metric .num{font-size:20px; font-weight:800; color:var(--ink);}
  .trend-metric .lbl{font-size:10.5px; color:var(--ink-soft); font-weight:700;}

  /* ---------- TREND LINE CHART MODEL (Surat Keluar) ---------- */
  .linechart-wrap{margin-top:14px; padding:4px 4px 0;}
  .linechart-svg{width:100%; height:130px; display:block; overflow:visible;}
  .lk-grid{stroke:#f0eadd; stroke-width:1.5; stroke-dasharray:4 5;}
  .lk-line{fill:none; stroke:#8a5fce; stroke-width:3; stroke-linecap:round; stroke-linejoin:round;}
  .lk-dot{fill:#fff; stroke:#8a5fce; stroke-width:2.5;}
  .lk-dot.lk-dot-peak{fill:#ef9dbf; stroke:#ef9dbf;}
  .lk-val{font-family:'Nunito',sans-serif; font-size:11px; font-weight:800; fill:var(--ink-soft);}
  .lk-val.lk-val-peak{fill:#c4507e;}
  .linechart-labels{display:flex; justify-content:space-between; padding:0 6px; margin-top:2px;}
  .linechart-labels span{font-size:10.5px; font-weight:800; color:var(--ink-soft); flex:1; text-align:center;}

  /* ---------- STAT CARDS ---------- */
  .stat-grid{display:grid; grid-template-columns:repeat(4,1fr); gap:16px; margin-bottom:22px;}
  .stat-card{border-radius:24px; padding:18px 20px; position:relative; overflow:hidden; box-shadow:var(--shadow);}
  .stat-card .lbl{font-size:12.5px; font-weight:800; opacity:.72;}
  .stat-card .num{font-size:32px; font-weight:800; margin-top:2px;}
  .stat-card .note{font-size:10.5px; font-weight:700; opacity:.65; margin-top:4px;}
  .stat-card .bubble{position:absolute; top:14px; right:14px; width:38px; height:38px; border-radius:50%; background:rgba(255,255,255,.65); display:flex; align-items:center; justify-content:center;}
  .stat-card .bubble svg{width:17px; height:17px;}
  .stat-blue{background:linear-gradient(155deg,#eaf3ff,#d9e9fd); color:#3c66a8;}
  .stat-yellow{background:linear-gradient(155deg,#fff8e2,#fdedbd); color:#a97a13;}
  .stat-pink{background:linear-gradient(155deg,#ffeef3,#fddce6); color:#c4507e;}
  .stat-green{background:linear-gradient(155deg,#eafcf0,#d3f5df); color:#309a5c;}

  /* ---------- TABLE ---------- */
  .table-card{background:var(--white); border-radius:24px; box-shadow:var(--shadow); overflow:hidden;}
  table{width:100%; border-collapse:collapse;}
  thead tr{background:linear-gradient(120deg,#efe6fb,#e2edfc);}
  thead th{text-align:left; padding:15px 14px; font-size:11.5px; font-weight:800; color:#7a6da8; text-transform:uppercase; letter-spacing:.03em; white-space:nowrap;}
  tbody td{padding:14px; font-size:13px; border-bottom:1px solid #f5efe1; vertical-align:top; color:var(--ink);}
  tbody tr:last-child td{border-bottom:none;}
  tbody tr:hover{background:#fbf8f2;}
  .num-link{font-weight:800; color:#e46a9b; cursor:pointer;}
  .num-link.sk{color:#8a5fce;}
  .unread-dot{width:7px; height:7px; border-radius:50%; background:#ef6a9a; display:inline-block;}
  .sub-date{font-size:11px; color:var(--ink-soft); font-weight:700; margin-top:3px;}
  .pengirim, .tujuan{font-weight:800;}
  .chip{display:inline-flex; align-items:center; gap:5px; padding:4px 11px; border-radius:999px; font-size:10.5px; font-weight:800;}
  .chip-dot{width:6px; height:6px; border-radius:50%; display:inline-block;}
  .chip-blue{background:#dceafd; color:#3d72c4;}
  .chip-amber{background:#ffe9c2; color:#b5791b;}
  .chip-red{background:#ffd9de; color:#c9455f;}
  .chip-grey{background:#eceff5; color:#7c8496;}
  .chip-dinas{background:#dceafd; color:#1e40af;}
  .chip-undangan{background:#fff3c9; color:#92400e;}
  .chip-permohonan{background:#d7f5e4; color:#065f46;}
  .chip-eksternal{background:#fce0ee; color:#9d174d;}
  .chip-lainnya{background:#ece2fb; color:#5b21b6;}
  .chip-mint{background:#d3f3df; color:#2f9d5e;}
  .status-chip{display:inline-flex; align-items:center; gap:5px; padding:5px 12px; border-radius:999px; font-size:11.5px; font-weight:800;}
  .status-proses{background:#d3f5df; color:#2f9d61;}
  .status-baru{background:#dceafd; color:#3d72c4;}
  .status-disposisi{background:#ffe9c2; color:#b5791b;}
  .status-selesai{background:#e2dcfb; color:#7c5fce;}
  .status-pending{background:#dceafd; color:#3d72c4;}
  .status-disetujui{background:#e2dcfb; color:#7c5fce;}
  .status-terkirim{background:#ffd7e6; color:#c4507e;}
  .status-diterima{background:#d3f5df; color:#2f9d61;}
  .file-link{color:#4f8fe0; font-weight:700; font-size:12px; text-decoration:underline;}
  .actions{display:flex; gap:6px; justify-content:flex-end; flex-wrap:wrap;}
  .icon-btn{width:30px; height:30px; border-radius:50%; border:none; cursor:pointer; display:flex; align-items:center; justify-content:center; background:#f4eee0; color:#8a8672;}
  .icon-btn svg{width:13px; height:13px;}
  .icon-btn.edit{background:#dceafd; color:#3d72c4;}
  .icon-btn.del{background:#ffd9de; color:#c9455f;}
  .icon-btn.ok{background:#d3f5df; color:#2f9d61;}
  .approve-btn{border:none; cursor:pointer; font-family:inherit; font-weight:800; font-size:11px; padding:7px 12px; border-radius:999px; background:#d3f5df; color:#2f9d61;}
  .checkbox{width:16px; height:16px; accent-color:#ef9dbf;}

  /* ---------- AGENDA PAGE ---------- */
  .agn-tl-time{font-size:10.5px; font-weight:800; color:var(--ink-soft); display:block;}
  .agn-tl-title{font-size:13px; font-weight:800; color:var(--ink); margin:1px 0;}
  .agn-tl-pj{font-size:11px; font-weight:700; color:var(--ink-soft);}
  .agn-layout-grid{display:grid; grid-template-columns:320px 1fr; grid-template-rows:auto auto; gap:16px; margin-bottom:20px;}
  .agn-donut-col{grid-column:1; grid-row:1 / 3; align-self:start;}
  .agn-donut-deco{margin-top:16px; padding-top:14px; border-top:1px dashed #eee2cd; display:flex; align-items:flex-end; justify-content:center; gap:20px;}
  .agn-donut-deco-icon{width:34px; height:34px; transform-origin:center bottom;}
  .agn-donut-deco-icon.teddy{width:66px; height:66px; animation:critterBounce 2.4s ease-in-out infinite;}
  @keyframes critterBounce{ 0%,100%{transform:translateY(0) rotate(0deg);} 50%{transform:translateY(-5px) rotate(-4deg);} }
  @keyframes critterHop{ 0%,20%,100%{transform:translateY(0) scaleY(1);} 10%{transform:translateY(-9px) scaleY(1.05);} 15%{transform:translateY(0) scaleY(.9);} }
  html.dark-mode .agn-donut-deco{border-top:1px dashed rgba(255,255,255,.10);}
  @media (prefers-reduced-motion: reduce){
    .agn-donut-deco-icon{animation:none !important;}
  }
  .agn-stat-trio{grid-column:2; grid-row:1; display:grid; grid-template-columns:repeat(3,1fr); gap:16px;}
  .agn-stat-trio .stat-card{display:flex; flex-direction:column; align-items:center; justify-content:center; text-align:center; padding:18px 20px;}
  .agn-timeline-col{grid-column:2; grid-row:2; margin-bottom:0; align-self:start;}
  @media (max-width: 1100px){
    .agn-layout-grid{grid-template-columns:1fr; grid-template-rows:auto auto auto;}
    .agn-donut-col{grid-column:1; grid-row:1;}
    .agn-stat-trio{grid-column:1; grid-row:2; grid-template-columns:1fr 1fr;}
    .agn-timeline-col{grid-column:1; grid-row:3;}
  }
  .agn-tl-table{width:100%; border-collapse:collapse; margin-top:6px;}
  .agn-tl-table th{color:var(--ink-soft); font-weight:700; font-size:11.5px; padding:8px 10px; text-align:left;}
  .agn-tl-table td{padding:10px; border-bottom:1px solid #f2ecdd; font-size:12.5px; font-weight:700; color:var(--ink);}
  .agn-tl-table tr:last-child td{border-bottom:none;}
  .agn-tl-cell{position:relative; display:flex; align-items:center;}
  .agn-tl-cell::before{content:''; position:absolute; left:4px; top:-11px; bottom:-11px; width:2px; background:#eee2cd;}
  .agn-tl-dot{width:10px; height:10px; background:var(--blue); border-radius:50%; margin-right:16px; position:relative; left:0; z-index:1; flex:0 0 10px;}
  .agn-tl-time{font-size:11px; color:var(--ink-soft); display:block; font-weight:700;}
  .agn-tl-title{margin:1px 0 0; font-size:13px; font-weight:800; color:var(--ink);}

  .agn-row2{display:grid; grid-template-columns:1.05fr 1fr; gap:16px; margin-bottom:20px; align-items:stretch;}
  .agn-archive-card{background:linear-gradient(135deg,var(--pink-deep) 0%,var(--yellow-deep) 130%); border-radius:24px; padding:22px 24px; color:#fff; display:flex; flex-direction:column; justify-content:space-between; box-shadow:var(--shadow); position:relative; overflow:hidden;}
  .agn-archive-icon{width:44px; height:44px; border-radius:14px; background:rgba(255,255,255,.25); display:flex; align-items:center; justify-content:center; margin-bottom:10px;}
  .agn-archive-icon svg{width:22px; height:22px;}
  .agn-archive-card h3{font-size:13px; font-weight:700; opacity:.9; margin-bottom:2px;}
  .agn-archive-card .num{font-size:26px; font-weight:800; margin-bottom:14px;}
  .agn-archive-btn{align-self:flex-start; display:flex; align-items:center; gap:7px; background:rgba(255,255,255,.25); border:none; color:#fff; font-weight:800; font-size:12px; padding:8px 16px; border-radius:12px; cursor:pointer; transition:.25s;}
  .agn-archive-btn:hover{background:rgba(255,255,255,.42);}
  .agn-archive-deco{position:absolute; right:-10px; bottom:-14px; font-size:64px; opacity:.18;}

  .agn-reminder-panel{background:linear-gradient(155deg,#fffaea,#fef3c8); border:1px solid #f6e6b0;}
  html.dark-mode .agn-reminder-panel{background:linear-gradient(155deg,#332c16,#332c16); border-color:#3d341b;}
  .agn-reminder-head{display:flex; align-items:center; justify-content:space-between; margin-bottom:4px;}
  .agn-reminder-head h3{display:flex; align-items:center; gap:7px; color:#8a6d1c;}
  html.dark-mode .agn-reminder-head h3{color:#d9ac5e;}
  #agnReminderList{list-style:none; margin:8px 0 0; padding:0;}
  #agnReminderList li{display:flex; align-items:center; gap:8px; padding:8px 2px; border-bottom:1px solid rgba(138,109,28,.12); font-size:12.5px; font-weight:700; color:#6d551a;}
  html.dark-mode #agnReminderList li{color:#8fb2e6; border-bottom-color:rgba(238,192,107,.18);}
  #agnReminderList li:last-child{border-bottom:none;}
  #agnReminderList li span.rtext{flex:1;}
  #agnReminderList li.done span.rtext{text-decoration:line-through; opacity:.55;}
  #agnReminderList .actions{display:flex; gap:5px;}
  #agnReminderList .check-btn, #agnReminderList .del-btn{border:none; background:rgba(255,255,255,.55); width:24px; height:24px; border-radius:8px; cursor:pointer; font-size:11px; display:flex; align-items:center; justify-content:center;}
  #agnReminderList .check-btn:hover{background:#d3f5df; color:#2f9d61;}
  #agnReminderList .del-btn:hover{background:#ffd9de; color:#c9455f;}
  .agn-empty{font-size:12px; font-weight:700; color:var(--ink-soft); padding:14px 2px; text-align:center;}

  tr.agn-done td:nth-child(2), tr.agn-done td:nth-child(3){text-decoration:line-through; opacity:.5;}

  /* ---------- PAGE SWITCH ---------- */
  .page{display:none; animation:fadeIn .25s ease;}
  .page.active{display:block;}
  @keyframes fadeIn{from{opacity:0; transform:translateY(6px);} to{opacity:1; transform:translateY(0);}}

  /* placeholder page */
  .placeholder-card{
    background:var(--white); border-radius:24px; padding:60px 30px; text-align:center; box-shadow:var(--shadow);
  }
  .placeholder-card .emoji{font-size:44px; margin-bottom:10px;}
  .placeholder-card h2{font-size:18px; color:var(--ink); margin-bottom:6px;}
  .placeholder-card p{font-size:13px; color:var(--ink-soft); font-weight:700;}

  /* toast */
  #toast{
    position:fixed; bottom:24px; left:50%; transform:translateX(-50%) translateY(20px);
    background:#5c6b93; color:#fff; padding:11px 20px; border-radius:999px; font-size:12.5px; font-weight:700;
    box-shadow:0 10px 24px rgba(0,0,0,.18); opacity:0; pointer-events:none; transition:all .25s ease; z-index:50;
  }
  #toast.show{opacity:1; transform:translateX(-50%) translateY(0);}  /* ---------- DASHBOARD EXTRAS ---------- */
  .shortcut-grid{display:grid; grid-template-columns:repeat(4,1fr); gap:16px; margin-bottom:22px;}
  .shortcut-card{background:var(--white); border-radius:22px; padding:18px; box-shadow:var(--shadow); cursor:pointer; display:flex; flex-direction:column; gap:10px; transition:transform .15s ease; border:1.5px solid transparent;}
  .shortcut-card:hover{transform:translateY(-3px); border-color:#eee2fb;}
  .shortcut-icon{width:40px; height:40px; border-radius:14px; display:flex; align-items:center; justify-content:center;}
  .shortcut-icon svg{width:19px; height:19px;}
  .shortcut-title{font-weight:800; font-size:13.5px; color:var(--ink);}
  .shortcut-desc{font-size:11px; color:var(--ink-soft); font-weight:700; line-height:1.4;}
  .shortcut-arrow{margin-left:auto; font-size:12px; color:#c7b6ee; font-weight:800;}
  .shortcut-top{display:flex; align-items:center;}

  .section-heading{display:flex; align-items:center; justify-content:space-between; margin:26px 0 12px;}
  .section-heading h2{font-size:16.5px; font-weight:800; color:var(--ink);}
  .section-heading .link-all{font-size:11.5px; font-weight:800; color:#8a5fce; cursor:pointer;}

  .activity-list{background:var(--white); border-radius:24px; box-shadow:var(--shadow); overflow:hidden;}
  .activity-row{display:flex; align-items:center; gap:14px; padding:14px 20px; border-bottom:1px solid #f5efe1;}
  .activity-row:last-child{border-bottom:none;}
  .activity-icon{width:38px; height:38px; border-radius:13px; display:flex; align-items:center; justify-content:center; flex:0 0 38px;}
  .activity-icon svg{width:17px; height:17px;}
  .activity-body{flex:1; min-width:0;}
  .activity-title{font-weight:800; font-size:13px; color:var(--ink); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;}
  .activity-sub{font-size:11.5px; color:var(--ink-soft); font-weight:700; margin-top:2px;}
  .activity-time{font-size:11px; color:var(--ink-soft); font-weight:800; white-space:nowrap;}

  /* ---------- SPLASH / INTRO ANIMATION ---------- */
  html.loading, html.loading body{overflow:hidden; height:100%;}
  #splash{
    position:fixed; inset:0; z-index:999; display:flex; flex-direction:column; align-items:center; justify-content:center; gap:6px;
    background:
      radial-gradient(circle at 8% 12%, #d7e8fb 0%, transparent 40%),
      radial-gradient(circle at 95% 8%, #f0e2fb 0%, transparent 42%),
      radial-gradient(circle at 90% 90%, #fbe3ec 0%, transparent 38%),
      var(--cream);
    transition:opacity .55s ease, visibility .55s ease;
  }
  #splash.hide{opacity:0; visibility:hidden; pointer-events:none;}
  .splash-ring{position:relative; width:96px; height:96px; display:flex; align-items:center; justify-content:center; margin-bottom:6px;}
  .splash-ring svg.orbit{position:absolute; inset:0; width:96px; height:96px; animation:splashSpin 2.6s linear infinite;}
  .splash-badge{width:64px; height:64px; border-radius:22px; background:linear-gradient(160deg,var(--lavender),var(--blue)); display:flex; align-items:center; justify-content:center; box-shadow:var(--shadow); animation:splashPop .7s cubic-bezier(.34,1.56,.64,1) both, splashFloat 2.4s ease-in-out .7s infinite;}
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

  /* app reveal after splash */
  .app{opacity:0; transition:opacity .6s ease;}
  .app.show{opacity:1;}

  /* ---------- DASHBOARD v2 EXTRAS ---------- */
  .bigstat-grid{display:grid; grid-template-columns:repeat(4,1fr); gap:16px; margin-bottom:22px;}
  .bigstat-card{border-radius:24px; padding:20px 22px 18px; box-shadow:var(--shadow); position:relative; overflow:hidden; display:flex; flex-direction:column; min-height:148px;}
  .bigstat-card .bs-icon{position:absolute; top:16px; right:16px; width:42px; height:42px; border-radius:50%; background:rgba(255,255,255,.65); display:flex; align-items:center; justify-content:center;}
  .bigstat-card .bs-icon svg{width:19px; height:19px;}
  .bigstat-card .bs-num{font-size:32px; font-weight:800; font-family:'Baloo 2',sans-serif; line-height:1;}
  .bigstat-card .bs-lbl{font-size:12.5px; font-weight:800; margin-top:5px; opacity:.8;}
  .bigstat-card .bs-sub{font-size:11px; font-weight:600; margin-top:2px; opacity:.65;}
  .bigstat-card .bs-more{margin-top:auto; padding-top:16px; font-size:11.5px; font-weight:800; display:flex; align-items:center; gap:5px; cursor:pointer; opacity:.85;}
  .bs-teal{background:linear-gradient(155deg,#eaf3ff,#d9e9fd); color:#3c66a8; border:1.5px solid #bcd7f7;}
  .bs-green{background:linear-gradient(155deg,#eafcf0,#d3f5df); color:#309a5c;}
  .bs-lavender{background:linear-gradient(155deg,#f3edff,#e6dbfb); color:#7c5fce;}
  .bs-pink{background:linear-gradient(155deg,#fef0f7,#fbdfec); color:#c4507e;}
  .bs-gold{background:linear-gradient(155deg,#fff8e2,#fdedbd); color:#a97a13; border:1.5px solid #f4c765;}
  .bs-red{background:linear-gradient(155deg,#ffeef3,#fddce6); color:#c4507e; border:1.5px solid #f8c9dc;}
  .bs-lavender{background:linear-gradient(155deg,#f3edfd,#e4d7fa); color:#6b46ad; border:1.5px solid #dcd0f5;}

  /* ---------- INVENTARIS & ASET ---------- */
  .inv-page-wrap{position:relative;}
  .inv-deco{position:absolute; pointer-events:none; opacity:.6; font-size:20px; z-index:0; user-select:none; animation:invDecoFloat 3s ease-in-out infinite;}
  .inv-deco.small{font-size:13px;}
  .inv-deco.big{font-size:26px;}
  .inv-hero{background:linear-gradient(120deg,#f7f1fd,#fdeef6); border-radius:26px; padding:24px 30px; box-shadow:var(--shadow); display:flex; align-items:center; justify-content:space-between; margin-bottom:20px; gap:18px; flex-wrap:wrap; position:relative; z-index:1;}
  .inv-hero .page-title-row{margin-bottom:4px;}
  .inv-hero .page-subtitle{margin-bottom:0;}
  .inv-hero-right{display:flex; align-items:center; gap:14px; position:relative;}
  .inv-speech{background:#fff; border-radius:16px 16px 4px 16px; padding:9px 14px; font-size:11px; font-weight:800; color:var(--ink); box-shadow:var(--shadow); line-height:1.35; white-space:nowrap; animation:invSpeechBob 3.4s ease-in-out infinite;}
  .inv-hero-illust{animation:invBunnyHop 2.2s ease-in-out infinite; transform-origin:bottom center;}
  @keyframes invBunnyHop{ 0%,100%{transform:translateY(0) rotate(0deg);} 30%{transform:translateY(-7px) rotate(-3deg);} 55%{transform:translateY(0) rotate(2deg);} 75%{transform:translateY(-3px) rotate(-1deg);} }
  @keyframes invSpeechBob{ 0%,100%{transform:translateY(0);} 50%{transform:translateY(-4px);} }
  @keyframes invDecoFloat{ 0%,100%{transform:translateY(0) rotate(0deg);} 50%{transform:translateY(-6px) rotate(10deg);} }
  .inv-th-deco{font-size:11px; opacity:.55; margin-right:4px;}
  .pg-info-deco{margin-left:6px; opacity:.6;}
  .bigstat-card.inv-card{border:1.5px solid transparent;}
  .bigstat-card .bs-deco{position:absolute; bottom:12px; right:16px; font-size:14px; opacity:.55;}
  .panel-corner-deco{position:absolute; bottom:8px; right:10px; font-size:22px; opacity:.4; pointer-events:none;}
  .panel-head-row{display:flex; align-items:center; justify-content:space-between; margin-bottom:14px;}
  .panel-head-row h3{margin-bottom:0;}
  .panel-menu-btn{width:26px; height:26px; border-radius:50%; border:none; background:#f6f0e2; color:var(--ink-soft); display:flex; align-items:center; justify-content:center; cursor:pointer;}
  .panel-menu-btn svg{width:14px; height:14px;}
  .invcat-row{display:flex; align-items:center; gap:12px; padding:7px 0;}
  .invcat-icon{width:34px; height:34px; border-radius:11px; display:flex; align-items:center; justify-content:center; font-size:15px; flex:0 0 34px;}
  .invcat-name{flex:1; font-weight:700; font-size:12.5px; color:var(--ink);}
  .invcat-count{background:#f6f0e2; color:var(--ink); font-weight:800; font-size:11.5px; padding:4px 12px; border-radius:999px;}
  .inv-donut-legend{display:flex; flex-direction:column; gap:12px;}
  .inv-donut-legend .legend-row{font-size:12.5px;}
  .sticky-note{margin-top:16px; background:#fff8d9; border:1px solid #f7e7a0; border-radius:14px; padding:10px 14px; font-size:11px; font-weight:800; color:#a97a13; transform:rotate(-1.2deg); display:inline-flex; align-items:center; gap:6px;}
  .sticky-note.flat{transform:none;}
  .status-tersedia{background:#d3f5df; color:#2f9d61;}
  .status-dipinjam{background:#fff3c9; color:#92400e;}
  .status-rusak{background:#ffd9de; color:#c9455f;}
  .status-baik{background:#d3f5df; color:#2f9d61;}
  .status-perlu{background:#fff3c9; color:#92400e;}
  .asset-icon{width:34px; height:34px; border-radius:11px; display:flex; align-items:center; justify-content:center; font-size:15px; flex:0 0 34px; background:#eef1fb;}
  .asset-name-cell{display:flex; align-items:center; gap:11px; font-weight:800; color:var(--ink);}

  .dash-grid{display:grid; grid-template-columns:2.15fr 1fr; gap:16px; align-items:start; margin-bottom:20px;}
  /* ---- Baris "Operasional Harian" -- side-col diperlebar supaya kalender +
     daftar jadwal muat berdampingan dengan nyaman (lihat .op-cal-combo). ---- */
  .dash-grid-op{grid-template-columns:minmax(400px,1.5fr) minmax(360px,1fr);}
  /* ---- Kalender + "Jadwal untuk [tanggal]" berdampingan di kolom samping ---- */
  .op-cal-combo{display:grid; grid-template-columns:repeat(auto-fit, minmax(210px,1fr)); gap:16px; align-items:stretch;}
  .op-cal-combo .calendar-card, .op-cal-combo .agenda-card{margin:0;}
  .op-panel{background:var(--white); border-radius:24px; box-shadow:var(--shadow); padding:26px 24px 22px; position:relative; overflow:hidden;}
  .op-panel::before{content:''; position:absolute; top:0; left:0; right:0; height:6px; border-radius:24px 24px 0 0; background:linear-gradient(90deg,var(--yellow-deep),var(--blue),var(--pink-deep));}
  .op-panel > h2{font-size:19px; font-weight:800; color:var(--ink); margin-bottom:0;}
  .op-panel-head{display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px; margin-bottom:6px;}
  /* ---- Judul panel + ikon badge kecil di depan "Operasional Harian" ---- */
  .op-panel-title{display:flex; align-items:center; gap:12px;}
  .op-panel-icon{width:40px; height:40px; border-radius:14px; flex:0 0 40px; display:flex; align-items:center; justify-content:center; font-size:19px; background:linear-gradient(155deg,var(--lavender),var(--blue)); box-shadow:0 6px 14px rgba(140,150,220,.35); animation:opIconFloat 3.6s ease-in-out infinite;}
  @keyframes opIconFloat{0%,100%{transform:translateY(0) rotate(-4deg);} 50%{transform:translateY(-3px) rotate(4deg);}}
  /* ---- Toggle "Minggu Ini" / "Bulan Ini" di header panel Operasional Harian ---- */
  .op-range-toggle{display:flex; gap:4px; background:#f7f3e8; border-radius:999px; padding:4px;}
  .op-range-btn{border:none; cursor:pointer; font-family:inherit; font-weight:800; font-size:12px; padding:8px 16px; border-radius:999px; background:transparent; color:var(--ink-soft); transition:background .15s ease, color .15s ease, box-shadow .15s ease, transform .15s ease;}
  .op-range-btn:hover{color:var(--ink); transform:translateY(-1px);}
  .op-range-btn.active{background:linear-gradient(135deg,#fff,#fff8ea); color:#c9932c; box-shadow:0 3px 10px rgba(180,140,40,.22);}
  .op-range-caption{display:flex; align-items:center; gap:9px; font-size:11.5px; font-weight:700; color:#8a7a4a; background:linear-gradient(135deg,#fbf5ea,#f6efdc); border:1.5px solid #f0e6c9; border-radius:14px; padding:10px 14px; margin-bottom:18px;}
  .op-range-caption-icon{flex:0 0 auto; font-size:14px; line-height:1;}
  .op-range-caption b{color:var(--ink);}
  /* ---- Dua set data (minggu/bulan) disiapkan sekaligus dari PHP; JS cuma
     switch tampil/sembunyi murni lewat class .active, tanpa reload. ---- */
  .op-range-view{display:none;}
  .op-range-view.active{display:block;}
  /* ---- Header pengelompokan per tanggal di dalam tabel Rapat/Agenda/Tamu ---- */
  .op-date-group td{background:#f7f3e8 !important; color:#8a7a4a; font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.02em; padding:7px 12px !important; border-bottom:1px solid #ece4cf !important;}
  .op-date-today-badge{display:inline-block; margin-left:6px; padding:2px 8px; border-radius:999px; background:#c9932c; color:#fff; font-size:9.5px; font-weight:800; text-transform:none; letter-spacing:0;}
  .op-date-count{float:right; font-weight:700; color:#b3a173; text-transform:none; letter-spacing:0;}
  /* ---- Tanda baris yang jadwalnya bentrok jam dengan jadwal lain ---- */
  .op-row-conflict{background:#fff6f6 !important;}
  .op-row-conflict td{border-bottom-color:#ffdfe2 !important;}
  .op-conflict-flag{margin-right:2px;}
  /* ---- Panel ringkasan "Jadwal Bentrok" ---- */
  .op-bentrok-block{padding-bottom:20px; border-bottom:1.5px dashed #f0ecdf; margin-bottom:20px;}
  .op-table-wrap.op-red{border-color:#ffd0d6;}
  .op-table-wrap.op-red thead th{background:linear-gradient(180deg,#ff9fae,#ef5c72);}
  .op-table-wrap.op-red tbody tr:nth-child(even){background:#fff5f6;}
  .op-table-wrap.op-red tbody tr:hover{background:#ffe9ec;}
  .op-table-wrap.op-red .op-table-scroll::-webkit-scrollbar-thumb{background:#f9c3cb;}
  .op-bentrok-chiprow{display:flex; flex-wrap:wrap; gap:6px;}
  .op-bentrok-chip{display:inline-flex; align-items:center; gap:5px; padding:5px 10px; border-radius:999px; font-size:10.5px; font-weight:700; white-space:nowrap;}
  .op-bentrok-chip b{font-weight:800;}
  .op-bentrok-chip.op-bentrok-rapat{background:#fff3c9; color:#92700f;}
  .op-bentrok-chip.op-bentrok-agenda{background:#dceafd; color:#2f5fa8;}
  .op-bentrok-chip.op-bentrok-tamu{background:#fce0ee; color:#9d174d;}
  .op-bentrok-aman{background:#eafcf0; color:#2f9d5e; border:1.5px solid #cdf1da; border-radius:14px; padding:12px 16px; font-size:12.5px; font-weight:800; margin-bottom:20px;}
  .op-table-scroll-6rows{max-height:340px; overflow-y:auto; overflow-x:hidden;}
  .op-block{margin-bottom:20px;}
  .op-block:last-child{margin-bottom:0;}
  .op-block:not(:last-child){padding-bottom:20px; border-bottom:1.5px dashed #f0ecdf;}
  .op-block:last-child{margin-bottom:0;}
  .op-subtitle{display:flex; align-items:center; gap:8px; font-size:14.5px; font-weight:800; color:var(--ink); margin-bottom:12px;}
  .op-subtitle .op-emoji{width:30px; height:30px; border-radius:11px; display:flex; align-items:center; justify-content:center; font-size:15px; flex:0 0 30px; box-shadow:0 2px 6px rgba(0,0,0,.05);}
  .op-table-wrap{border-radius:18px; border:1.5px solid #f0eadd; box-shadow:0 4px 14px rgba(150,130,180,.08); overflow:hidden; transition:box-shadow .2s ease;}
  .op-table-wrap:hover{box-shadow:0 8px 20px rgba(150,130,180,.14);}
  .op-table-wrap.op-blue{border-color:#d3e8fd;}
  .op-table-wrap.op-pink{border-color:#fbdcea;}
  .op-table-wrap.op-yellow{border-color:#f5e6bd;}
  .op-table-scroll{max-height:296px; overflow-y:auto; overflow-x:hidden;}
  /* Khusus panel "Daftar Inventaris ATK" di dashboard: begitu isinya lebih
     dari 2 baris, tabelnya jadi bisa discroll (tinggi dibatasi kira-kira
     pas untuk header + 2 baris data) supaya panel tidak makin tinggi terus
     kalau barangnya banyak. */
  .op-table-scroll-2rows{max-height:136px; overflow-y:auto; overflow-x:hidden;}
  /* Khusus panel "Buku Tamu Terbaru": scroll aktif setelah 3 baris data. */
  .op-table-scroll-3rows{max-height:184px; overflow-y:auto; overflow-x:hidden;}
  .op-table-scroll-4rows{max-height:208px; overflow-y:auto; overflow-x:hidden;}
  .op-table-wrap table{width:100%; border-collapse:collapse; table-layout:auto;}
  .op-table-wrap thead th{color:#fff; font-size:10.5px; font-weight:800; text-transform:uppercase; letter-spacing:.02em; padding:11px 12px; text-align:left; white-space:normal; line-height:1.3; position:sticky; top:0; z-index:2; box-shadow:0 2px 0 rgba(0,0,0,.04);}
  .op-table-wrap tbody tr{transition:background .15s ease;}
  .op-table-wrap tbody td{padding:10.5px 12px; font-size:11.5px; color:var(--ink); border-bottom:1px solid #f2ecdd; font-weight:700; vertical-align:middle; line-height:1.4;}
  .op-table-wrap tbody td.op-nowrap{white-space:nowrap;}
  .op-table-wrap .chip{white-space:nowrap; font-size:10px; padding:4px 9px;}
  .op-table-wrap tbody tr:last-child td{border-bottom:none;}
  .op-table-wrap.op-pink thead th{background:linear-gradient(180deg,#f8b9d3,#ee80ac);}
  .op-table-wrap.op-pink tbody tr:nth-child(even){background:#fff3f8;}
  .op-table-wrap.op-pink tbody tr:hover{background:#ffe6f0;}
  .op-table-wrap.op-blue thead th{background:linear-gradient(180deg,#9fcdf7,#569ce6);}
  .op-table-wrap.op-blue tbody tr:nth-child(even){background:#f0f7ff;}
  .op-table-wrap.op-blue tbody tr:hover{background:#e3f0ff;}
  .op-table-wrap.op-yellow thead th{background:linear-gradient(180deg,#ffdd8c,#f4b73a);}
  .op-table-wrap.op-yellow tbody tr:nth-child(even){background:#fffaee;}
  .op-table-wrap.op-yellow tbody tr:hover{background:#fff2d4;}
  ::-webkit-scrollbar-button{display:none; width:0; height:0;}
  .op-table-scroll::-webkit-scrollbar{width:8px;}
  .op-table-scroll::-webkit-scrollbar-track{background:transparent;}
  .op-table-scroll::-webkit-scrollbar-thumb{background:#e7dcc4; border-radius:999px;}
  .op-table-wrap.op-blue .op-table-scroll::-webkit-scrollbar-thumb{background:#bcdafc;}
  .op-table-wrap.op-pink .op-table-scroll::-webkit-scrollbar-thumb{background:#f7cfe1;}

  /* =====================================================================
     KARTU Rapat / Agenda / Tamu di panel "Operasional Harian" -- pengganti
     tabel padat yang lama. Tiap kegiatan jadi satu kartu berdiri sendiri
     (judul, jam, PIC/lokasi, status, tombol kelola), dikelompokkan per
     tanggal, supaya lebih enak dipindai mata daripada baris tabel rapat.
     ===================================================================== */
  .op-cardlist-scroll{max-height:352px; overflow-y:auto; overflow-x:hidden; padding-right:4px; margin-right:-4px;
    scrollbar-width:thin; scrollbar-color:#e7dcc4 transparent;}
  .op-cardlist-scroll::-webkit-scrollbar{width:7px;}
  .op-cardlist-scroll::-webkit-scrollbar-track{background:transparent;}
  .op-cardlist-scroll::-webkit-scrollbar-thumb{background:#e7dcc4; border-radius:999px;}
  .op-cardlist{display:flex; flex-direction:column; gap:10px;}
  .op-cardlist-datehead{font-size:10.5px; font-weight:800; text-transform:uppercase; letter-spacing:.03em; color:#a99a6f;
    padding:2px 4px; margin-top:4px;}
  .op-cardlist-datehead:first-child{margin-top:0;}
  .op-cardlist-empty{display:flex; flex-direction:column; align-items:center; justify-content:center; gap:8px;
    text-align:center; padding:30px 16px; color:var(--ink-soft); font-weight:700; font-size:12px;
    background:#faf7ee; border-radius:16px; border:1.5px dashed #ede4cd;}
  .op-cardlist-empty-icon{font-size:26px; opacity:.75;}

  .op-event-card{border-radius:16px; padding:13px 15px; border:1.5px solid transparent; box-shadow:0 3px 10px rgba(150,130,180,.06);
    transition:transform .16s ease, box-shadow .16s ease, border-color .16s ease;}
  .op-event-card:hover{transform:translateY(-2px); box-shadow:0 10px 20px rgba(150,130,180,.14);}
  .op-event-clickable{cursor:pointer;}
  .op-event-clickable:active{transform:translateY(0) scale(.99);}
  .op-event-clickable:focus-visible{outline:2px solid #a98ee8; outline-offset:2px;}
  .op-event-top{display:flex; align-items:flex-start; justify-content:space-between; gap:10px; margin-bottom:8px;}
  .op-event-title{font-size:13px; font-weight:800; color:var(--ink); line-height:1.35;}
  .op-event-time{display:inline-flex; align-items:center; gap:4px; font-size:10.5px; font-weight:800; white-space:nowrap;
    padding:4px 10px; border-radius:999px; flex:0 0 auto;}
  .op-event-time svg{width:11px; height:11px; flex:0 0 11px;}
  .op-event-meta{display:flex; flex-wrap:wrap; gap:6px 14px; margin-bottom:11px;}
  .op-event-meta-item{display:inline-flex; align-items:center; gap:5px; font-size:11px; font-weight:700; color:var(--ink-soft); min-width:0;}
  .op-event-meta-item svg{width:12px; height:12px; flex:0 0 12px; opacity:.75;}
  .op-event-meta-full{flex-basis:100%;}
  .op-event-foot{display:flex; align-items:center; flex-wrap:wrap; gap:7px;}
  .op-event-editbtn{border:none; cursor:pointer; font-family:inherit; font-weight:800; font-size:10.5px;
    padding:6px 12px; border-radius:10px; display:inline-flex; align-items:center; gap:5px; transition:transform .15s ease;}
  .op-event-editbtn:hover{transform:translateY(-1px);}
  /* Wrapper tombol aksi kanan (Arsip + Kelola) -- didorong ke kanan pakai margin-left:auto
     di wrapper-nya, bukan di masing-masing tombol, supaya jarak antar tombolnya rapi. */
  .op-event-actions{margin-left:auto; display:inline-flex; align-items:center; gap:7px; flex-wrap:wrap;}
  .op-event-archivebtn{border:none; cursor:pointer; font-family:inherit; font-weight:800; font-size:10.5px;
    padding:6px 12px; border-radius:10px; display:inline-flex; align-items:center; gap:5px; transition:transform .15s ease;
    color:#7c5fce; background:#e2dcfb;}
  .op-event-archivebtn:hover{transform:translateY(-1px);}
  .op-event-archivebtn:disabled{opacity:.55; cursor:default; transform:none;}
  html.dark-mode .op-event-archivebtn{background:#26213f; color:#b6a3ec;}

  /* --- Tema warna: Rapat (kuning) --- */
  .op-event-yellow{background:linear-gradient(150deg,#fffbf0,#fff8e6); border-color:#f5e6bd;}
  .op-event-yellow:hover{border-color:#f4b73a;}
  .op-event-time-yellow{background:linear-gradient(135deg,#ffdd8c,#f4b73a); color:#7a5308;}
  .op-event-editbtn-yellow{color:#b5791b; background:#fff3c9;}
  /* --- Tema warna: Agenda (biru) --- */
  .op-event-blue{background:linear-gradient(150deg,#f6faff,#eef6ff); border-color:#d3e8fd;}
  .op-event-blue:hover{border-color:#569ce6;}
  .op-event-time-blue{background:linear-gradient(135deg,#9fcdf7,#569ce6); color:#1e4c80;}
  .op-event-editbtn-blue{color:#3d72c4; background:#dceafd;}
  /* --- Tema warna: Tamu (pink) --- */
  .op-event-pink{background:linear-gradient(150deg,#fff7fb,#fef0f7); border-color:#fbdcea;}
  .op-event-pink:hover{border-color:#ee80ac;}
  .op-event-time-pink{background:linear-gradient(135deg,#f8b9d3,#ee80ac); color:#7a1f47;}
  .op-event-editbtn-pink{color:#c4507e; background:#fce0ee;}
  /* --- Kartu yang jadwalnya bentrok dengan kartu lain --- */
  .op-event-conflict{border-color:#ffb4c1 !important; box-shadow:0 3px 12px rgba(239,92,114,.16);}
  .op-event-conflict:hover{box-shadow:0 10px 22px rgba(239,92,114,.22);}

  /* ---- Panel Daftar Inventaris ATK (dashboard) ---- */
  .idxatk-pgbtn{min-width:26px; height:26px; padding:0 6px; border:none; cursor:pointer; border-radius:8px; font-family:inherit; font-weight:800; font-size:11.5px; color:#b5791b; background:#fff3c9; transition:transform .15s ease;}
  .idxatk-pgbtn:hover{transform:translateY(-1px);}
  .op-actions{display:flex; gap:7px;}
  .op-actbtn{width:26px; height:26px; border-radius:50%; border:none; cursor:pointer; display:flex; align-items:center; justify-content:center;}
  .op-actbtn svg{width:12px; height:12px;}
  .op-actbtn.yes{background:#d3f5df; color:#2f9d61;}
  .op-actbtn.no{background:#ffd9de; color:#c9455f;}

  .side-col{display:flex; flex-direction:column; gap:16px;}
  .side-panel{background:var(--white); border-radius:24px; box-shadow:var(--shadow); padding:20px 20px 22px;}
  .side-panel > h3{font-size:15px; font-weight:800; color:var(--ink); margin-bottom:12px;}

  /* ---- Kartu "Jadwal Bentrok" di kolom samping ---- */
  .op-bentrok-panel{position:relative; overflow:hidden;}
  .op-bentrok-panel::before{content:''; position:absolute; top:0; left:0; right:0; height:5px;
    background:linear-gradient(90deg,#ffb4c1,#ff9fae,#ffcf8c,#ff9fae,#ffb4c1); background-size:200% 100%;
    animation:opBentrokSheen 5s ease-in-out infinite;}
  @keyframes opBentrokSheen{0%,100%{background-position:0% 50%;} 50%{background-position:100% 50%;}}
  .op-bentrok-head{display:flex; align-items:flex-start; gap:12px; margin-bottom:16px;}
  .op-bentrok-icon{width:42px; height:42px; border-radius:14px; flex:0 0 42px; display:flex; align-items:center; justify-content:center;
    background:linear-gradient(155deg,#ffb4c1,#ef5c72); color:#fff; box-shadow:0 8px 16px rgba(239,92,114,.32);
    animation:opBentrokPulse 2.8s ease-in-out infinite;}
  .op-bentrok-icon svg{width:22px; height:22px;}
  @keyframes opBentrokPulse{0%,100%{transform:scale(1) rotate(0deg);} 50%{transform:scale(1.07) rotate(-3deg);}}
  .op-bentrok-head-text h3{font-size:15px; font-weight:800; color:var(--ink); margin-bottom:3px;}
  .op-bentrok-head-text p{font-size:11px; font-weight:700; color:var(--ink-soft); line-height:1.4; margin:0;}

  .op-bentrok-list{display:flex; flex-direction:column; gap:10px; max-height:280px; overflow-y:auto; padding-right:3px; margin-right:-3px;}
  .op-bentrok-list::-webkit-scrollbar{width:6px;}
  .op-bentrok-list::-webkit-scrollbar-track{background:transparent;}
  .op-bentrok-list::-webkit-scrollbar-thumb{background:#f9c3cb; border-radius:999px;}
  .op-bentrok-card{background:linear-gradient(150deg,#fff8f8,#fff0f2); border:1.5px solid #ffd0d6; border-radius:16px; padding:12px 14px;
    transition:transform .18s ease, box-shadow .18s ease, border-color .18s ease;}
  .op-bentrok-card:hover{transform:translateY(-2px); box-shadow:0 10px 20px rgba(239,92,114,.16); border-color:#ffb4c1;}
  .op-bentrok-card-top{display:flex; align-items:center; justify-content:space-between; gap:8px; flex-wrap:wrap; margin-bottom:9px;}
  .op-bentrok-date{font-size:11px; font-weight:800; color:#c9455f;}
  .op-bentrok-time{font-size:10.5px; font-weight:800; color:#fff; background:linear-gradient(135deg,#ff9fae,#ef5c72); padding:3.5px 10px; border-radius:999px; white-space:nowrap; box-shadow:0 3px 8px rgba(239,92,114,.25);}

  .op-bentrok-aman{display:flex; align-items:center; gap:12px; background:linear-gradient(135deg,#eafcf0,#f3fef7);
    border:1.5px solid #cdf1da; border-radius:18px; padding:15px 16px; margin-bottom:0;}
  .op-bentrok-aman-icon{width:40px; height:40px; border-radius:50%; flex:0 0 40px; display:flex; align-items:center; justify-content:center;
    background:linear-gradient(155deg,#bdeccb,#5fc384); color:#fff; box-shadow:0 6px 14px rgba(47,157,94,.3);}
  .op-bentrok-aman-icon svg{width:19px; height:19px;}
  .op-bentrok-aman-text b{display:block; font-size:13px; font-weight:800; color:#2f9d5e; margin-bottom:2px;}
  .op-bentrok-aman-text span{display:block; font-size:11px; font-weight:700; color:#4fae78; line-height:1.4;}
  .qm-panel-head{display:flex; align-items:flex-start; gap:9px; margin-bottom:12px; position:relative;}
  .qm-panel-head-icon{font-size:25px; line-height:1; flex:0 0 auto; animation:qmBounce 2.6s ease-in-out infinite;}
  .qm-panel-head-text{flex:1; min-width:0;}
  .qm-panel-head-text h3{font-size:16.5px; font-weight:900; background:linear-gradient(90deg,#7c5fce,#e0699a); -webkit-background-clip:text; background-clip:text; color:transparent; margin-bottom:2px;}
  .qm-panel-head-text p{font-size:11px; font-weight:700; color:var(--ink-soft); margin:0;}
  .qm-panel-head-decor{position:absolute; right:-2px; top:-6px; font-size:18px; opacity:.9;}
  @keyframes qmBounce{0%,100%{transform:translateY(0) rotate(0deg);} 50%{transform:translateY(-3px) rotate(-8deg);}}

  .quickmenu-grid{display:grid; grid-template-columns:repeat(3, 1fr); gap:9px;}
  .qm-tile{position:relative; overflow:hidden; border-radius:18px; padding:12px 6px 10px; display:flex; flex-direction:column; align-items:center; gap:3px; text-align:center; cursor:pointer; border:1.5px solid transparent; transition:transform .18s ease, box-shadow .18s ease, border-color .18s ease;}
  .qm-tile:hover{transform:translateY(-3px) scale(1.015); box-shadow:0 10px 18px rgba(150,130,180,.2);}
  .qm-tile-sparkle{display:none;}
  .qm-tile-sticker{position:absolute; font-size:18px; opacity:.9; pointer-events:none; filter:drop-shadow(0 2px 3px rgba(0,0,0,.08));}
  .qm-tile-sticker-bl{bottom:3px; left:2px; transform:rotate(-12deg);}
  .qm-tile-sticker-br{bottom:2px; right:0px; transform:rotate(10deg);}
  .qm-icon-wrap{width:46px; height:46px; border-radius:50%; display:flex; align-items:center; justify-content:center; margin-bottom:1px;}
  .qm-icon{width:35px; height:35px; border-radius:50%; display:flex; align-items:center; justify-content:center; box-shadow:0 4px 10px rgba(0,0,0,.10);}
  .qm-icon svg{width:16px; height:16px; stroke-width:2.4;}
  .qm-label{font-size:11.5px; font-weight:900; line-height:1.2; margin-top:1px;}
  .qm-desc{font-size:8.6px; font-weight:700; color:var(--ink-soft); line-height:1.3;}

  .qm-tile-violet{background:linear-gradient(160deg,#efe7fc,#f8f4fd); border-color:#e6d9fa;}
  .qm-tile-violet .qm-icon-wrap{background:#e2d3f9;}
  .qm-tile-violet .qm-icon{background:linear-gradient(135deg,#dccdf7,#b79aec); color:#6a4fc4;}
  .qm-tile-violet .qm-label{color:#6a4fc4;}
  .qm-tile-violet:hover{border-color:#d2bcf2;}

  .qm-tile-gold{background:linear-gradient(160deg,#fff3d6,#fffaef); border-color:#f9e7b3;}
  .qm-tile-gold .qm-icon-wrap{background:#ffe6ab;}
  .qm-tile-gold .qm-icon{background:linear-gradient(135deg,#ffe19f,#f0bc57); color:#96650f;}
  .qm-tile-gold .qm-label{color:#96650f;}
  .qm-tile-gold:hover{border-color:#f0d181;}

  .qm-tile-indigo{background:linear-gradient(160deg,#e7ecfc,#f4f6fd); border-color:#d3ddf9;}
  .qm-tile-indigo .qm-icon-wrap{background:#cdd9f9;}
  .qm-tile-indigo .qm-icon{background:linear-gradient(135deg,#c6d3f7,#93a9ec); color:#3f5bc4;}
  .qm-tile-indigo .qm-label{color:#3f5bc4;}
  .qm-tile-indigo:hover{border-color:#b7c6f5;}

  .qm-tile-mint{background:linear-gradient(160deg,#e1f7e8,#f2fbf4); border-color:#c7ecd3;}
  .qm-tile-mint .qm-icon-wrap{background:#bdeccb;}
  .qm-tile-mint .qm-icon{background:linear-gradient(135deg,#bdeccb,#78cf97); color:#227849;}
  .qm-tile-mint .qm-label{color:#227849;}
  .qm-tile-mint:hover{border-color:#a2e3ba;}

  .qm-tile-pink{background:linear-gradient(160deg,#fce3ef,#fdf3f8); border-color:#f7cfe4;}
  .qm-tile-pink .qm-icon-wrap{background:#f7cee2;}
  .qm-tile-pink .qm-icon{background:linear-gradient(135deg,#f8c9dc,#ee8fb9); color:#a83b68;}
  .qm-tile-pink .qm-label{color:#a83b68;}
  .qm-tile-pink:hover{border-color:#f2b6d6;}

  .qm-tile-red{background:linear-gradient(160deg,#ffe1e4,#fff4f5); border-color:#fdc7cc;}
  .qm-tile-red .qm-icon-wrap{background:#fdc4ca;}
  .qm-tile-red .qm-icon{background:linear-gradient(135deg,#ffbfc7,#ef7f8f); color:#a8324a;}
  .qm-tile-red .qm-label{color:#a8324a;}
  .qm-tile-red:hover{border-color:#f8aab4;}

  .btn-audit{display:flex; align-items:center; gap:11px; width:100%; text-align:left; border:none; cursor:pointer; font-family:inherit; padding:11px 14px; border-radius:16px; background:linear-gradient(135deg,#bdeccb,#7fd6a0); color:#1f6b3f; margin-top:10px; box-shadow:var(--shadow);}
  .btn-audit-icon{width:34px; height:34px; border-radius:50%; background:rgba(255,255,255,.55); display:flex; align-items:center; justify-content:center; font-size:16px; flex:0 0 auto;}
  .btn-audit-text{flex:1; min-width:0; display:flex; flex-direction:column; gap:1px;}
  .btn-audit-text b{font-size:12.5px; font-weight:900;}
  .btn-audit-text small{font-size:9.5px; font-weight:700; opacity:.85;}
  .btn-audit-arrow{width:26px; height:26px; border-radius:50%; background:#fff; color:#2f9d61; display:flex; align-items:center; justify-content:center; font-size:15px; font-weight:900; flex:0 0 auto;}



  .total-data-panel{background:linear-gradient(160deg,#eafcf0,#e3f6ec); text-align:center; flex:1; display:flex; flex-direction:column; justify-content:center; align-items:center;}
  .total-data-panel h3{margin-bottom:10px;}

  /* ---- Kartu "Perbandingan Operasional" -- pengganti donut "Total Seluruh
     Data" atas permintaan klien: sekarang fokus MEMBANDINGKAN 3 kategori
     operasional (Rapat / Agenda / Tamu) minggu ini lewat 3-ring radial
     chart yang lebih hidup & playful, bukan cuma total gabungan. ---- */
  .op-compare-panel{position:relative; overflow:hidden; text-align:center; flex:1; display:flex; flex-direction:column; align-items:center; background:linear-gradient(165deg,#f6ecff 0%,#fbe9f3 45%,#eaf1ff 100%); border:1.5px solid #fff; box-shadow:0 0 0 1px #f3e6fb inset, 0 14px 30px -14px rgba(170,140,220,.35); isolation:isolate; padding:13px 20px 13px;}
  /* ---- Blob dekoratif blur di background kartu -- kesan "depth" & lebih
     estetik, murni dekorasi (pointer-events off, di belakang konten). ---- */
  .op-compare-panel::before, .op-compare-panel::after{content:""; position:absolute; border-radius:50%; filter:blur(28px); z-index:0; pointer-events:none;}
  .op-compare-panel::before{width:150px; height:150px; top:-55px; right:-45px; background:radial-gradient(circle,#ffc3e3,transparent 70%); opacity:.75;}
  .op-compare-panel::after{width:170px; height:170px; bottom:-65px; left:-55px; background:radial-gradient(circle,#c9d9ff,transparent 70%); opacity:.65;}
  .op-compare-panel > *{position:relative; z-index:1;}
  .op-compare-panel h3{margin-bottom:2px; letter-spacing:.01em; font-size:15px; background:linear-gradient(100deg,#7c5fce,#e263a8 55%,#ef8fb0); -webkit-background-clip:text; background-clip:text; color:transparent; display:inline-flex; align-items:center; gap:5px;}
  .op-compare-panel h3::after{content:"💗"; font-size:12px; -webkit-text-fill-color:initial;}
  .opc-caption-top{display:inline-flex; align-items:center; gap:5px; font-size:10px; font-weight:700; color:#8b6bb0; margin-bottom:6px; background:rgba(255,255,255,.75); padding:3px 11px; border-radius:999px; border:1px solid rgba(255,255,255,.9); box-shadow:0 3px 10px rgba(180,150,220,.16);}
  .opc-sparkle{position:absolute; font-size:13px; color:#e3b8f0; opacity:.7; animation:opcTwinkle 2.6s ease-in-out infinite; z-index:1;}
  .opc-sparkle-1{top:14px; right:22px; animation-delay:.2s;}
  .opc-sparkle-2{top:54px; left:14px; font-size:10px; animation-delay:1s;}
  .opc-sparkle-3{bottom:96px; right:18px; font-size:11px; animation-delay:1.7s;}
  .opc-decor{position:absolute; z-index:0; pointer-events:none; opacity:.85; filter:drop-shadow(0 3px 5px rgba(150,130,190,.18));}
  .opc-decor-cloud1{top:10px; left:10px; font-size:19px; animation:opcFloat 5s ease-in-out infinite;}
  .opc-decor-cloud2{bottom:56px; right:8px; font-size:16px; animation:opcFloat 6s ease-in-out infinite 1.2s;}
  .opc-decor-star{bottom:130px; left:6px; font-size:14px; animation:opcBounce 2.4s ease-in-out infinite;}
  @keyframes opcFloat{0%,100%{transform:translateY(0);} 50%{transform:translateY(-5px);}}
  @keyframes opcTwinkle{0%,100%{opacity:.25; transform:scale(.85);} 50%{opacity:.9; transform:scale(1.15);}}
  @keyframes opcBounce{0%,100%{transform:translateY(0);} 50%{transform:translateY(-3px);}}
  /* ---- Podium "juara" Rapat/Agenda/Tamu -- pengganti ring bulat: 3 batang
     ala podium olimpiade, tinggi tiap batang proporsional ke nilainya
     sendiri, posisi (order) otomatis ditata JS supaya yang paling tinggi
     nilainya selalu ada di TENGAH podium seperti juara 1 sungguhan. ---- */
  .opc-podium-wrap{width:100%; margin:2px 0 6px;}
  .opc-podium{display:flex; align-items:flex-end; justify-content:center; gap:12px; min-height:96px; padding:0 4px;}
  .opc-pod-col{display:flex; flex-direction:column; align-items:center; width:68px;}
  .opc-pod-medal{width:24px; height:24px; border-radius:50%; background:linear-gradient(155deg,#fff,#fff3da); display:flex; align-items:center; justify-content:center; font-size:14px; margin-bottom:6px; box-shadow:0 4px 9px rgba(190,150,60,.28), 0 0 0 2px #fff; opacity:0; transform:translateY(5px) scale(.7); transition:opacity .35s ease .95s, transform .35s ease .95s;}
  .opc-pod-medal.show{opacity:1; transform:translateY(0) scale(1);}
  .opc-pod-avatar-wrap{position:relative; display:flex; align-items:center; justify-content:center; margin-bottom:7px;}
  .opc-pod-avatar-wrap::before{content:""; position:absolute; width:46px; height:46px; border-radius:50%; z-index:1; opacity:.55; filter:blur(1px); transition:width .3s ease, height .3s ease, opacity .3s ease;}
  .opc-pod-col[data-key="rapat"] .opc-pod-avatar-wrap::before{background:radial-gradient(circle,#ffd6ea 55%,transparent 78%);}
  .opc-pod-col[data-key="agenda"] .opc-pod-avatar-wrap::before{background:radial-gradient(circle,#d3f5e0 55%,transparent 78%);}
  .opc-pod-col[data-key="tamu"] .opc-pod-avatar-wrap::before{background:radial-gradient(circle,#e6dcfb 55%,transparent 78%);}
  .opc-pod-col.rank-1 .opc-pod-avatar-wrap::before{width:58px; height:58px; opacity:.8; background:radial-gradient(circle,#ffe6b0 55%,transparent 78%);}
  .opc-pod-avatar{width:34px; height:34px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:16px; box-shadow:0 4px 12px rgba(150,130,190,.18); border:3px solid #fff; position:relative; z-index:2; transition:width .3s ease, height .3s ease, font-size .3s ease, box-shadow .3s ease;}
  .opc-pod-col[data-key="rapat"] .opc-pod-avatar{box-shadow:0 0 0 3px #ffe3ef, 0 4px 12px rgba(255,150,190,.28);}
  .opc-pod-col[data-key="agenda"] .opc-pod-avatar{box-shadow:0 0 0 3px #e2f8ea, 0 4px 12px rgba(140,215,175,.28);}
  .opc-pod-col[data-key="tamu"] .opc-pod-avatar{box-shadow:0 0 0 3px #ede6fc, 0 4px 12px rgba(180,155,235,.28);}
  .opc-pod-col.rank-1 .opc-pod-avatar{width:42px; height:42px; font-size:19px; box-shadow:0 0 0 3px #fff, 0 0 0 6px rgba(255,210,130,.5), 0 6px 16px rgba(200,150,60,.25); animation:opcBounce 1.9s ease-in-out infinite;}
  .opc-pod-num{font-family:'Baloo 2',sans-serif; font-weight:800; font-size:12px; color:#5c6b93; margin-bottom:5px; background:rgba(255,255,255,.8); padding:1px 10px; border-radius:999px; box-shadow:0 1px 4px rgba(150,130,190,.12);}
  .opc-pod-bar{width:100%; height:6px; border-radius:16px 16px 8px 8px; transition:height 1s cubic-bezier(.22,1.4,.36,1) .1s; position:relative; overflow:visible;}
  .opc-pod-bar::before{content:""; position:absolute; inset:0; border-radius:inherit; overflow:hidden;}
  .opc-pod-bar::after{content:""; position:absolute; top:0; left:8%; right:60%; height:92%; background:linear-gradient(180deg,rgba(255,255,255,.65),transparent 70%); border-radius:16px; pointer-events:none;}
  #opcPodBarRapat{box-shadow:0 10px 16px -8px rgba(255,140,185,.65);}
  #opcPodBarAgenda{box-shadow:0 10px 16px -8px rgba(120,205,155,.65);}
  #opcPodBarTamu{box-shadow:0 10px 16px -8px rgba(175,145,230,.6);}
  .opc-pod-name{margin-top:7px; font-size:11px; font-weight:800; color:var(--ink-soft); letter-spacing:.01em;}
  .opc-pod-col.rank-1 .opc-pod-name{color:#b8873f;}
  .opc-pod-floor{height:12px; width:94%; margin:0 auto; border-radius:50%; background:radial-gradient(ellipse at center,rgba(200,175,235,.4),transparent 75%);}
  .opc-legend{display:flex; flex-direction:column; gap:5px; width:100%;}
  .opc-row{display:flex; align-items:center; gap:9px; background:rgba(255,255,255,.65); border-radius:13px; padding:5px 10px; border:1px solid rgba(255,255,255,.85); box-shadow:0 2px 8px rgba(160,140,200,.08);}
  .opc-icon{width:23px; height:23px; border-radius:8px; flex:0 0 23px; display:flex; align-items:center; justify-content:center; font-size:12px; box-shadow:0 2px 5px rgba(150,130,190,.14);}
  .opc-row-body{flex:1; min-width:0; text-align:left;}
  .opc-row-top{display:flex; align-items:center; justify-content:space-between; font-size:11px; font-weight:800; color:var(--ink); margin-bottom:3px;}
  .opc-row-top b{font-size:13px; color:#5c6b93; background:rgba(255,255,255,.9); padding:1px 9px; border-radius:999px;}
  .opc-bar-track{background:#f2ecfa; border-radius:999px; height:6px; overflow:hidden;}
  .opc-bar-fill{height:100%; width:0%; border-radius:999px; transition:width 1.1s cubic-bezier(.22,1,.36,1); position:relative;}
  .opc-animal{flex:0 0 26px; width:26px; height:26px; display:flex; align-items:center; justify-content:center; font-size:17px; filter:drop-shadow(0 2px 3px rgba(150,130,190,.2));}
  .opc-caption{margin-top:6px; display:inline-flex; align-items:center; gap:6px; font-size:10.5px; font-weight:700; color:#9c62b0; background:linear-gradient(#fff,#fff) padding-box, linear-gradient(120deg,#ffd3e8,#d9c9f7,#c8f2d7) border-box; border:1.5px solid transparent; border-radius:999px; padding:4px 13px; line-height:1.3; box-shadow:0 4px 12px rgba(150,130,190,.14);}
  .opc-caption-star{font-size:14px; flex:0 0 auto;}
  .opc-caption b{color:#e263a8;}


  .calendar-card{background:var(--white); border-radius:24px; box-shadow:var(--shadow); padding:18px 20px;}
  .cal-head{display:flex; align-items:center; justify-content:space-between; background:linear-gradient(135deg,#c9b6f2,#a98ee8); color:#fff; border-radius:14px; padding:9px 14px; font-weight:800; font-size:14px; margin-bottom:10px;}
  .cal-head svg{width:16px; height:16px; cursor:pointer;}
  .cal-months{display:flex; flex-wrap:wrap; gap:6px; margin-bottom:12px;}
  .cal-month{font-size:10.5px; font-weight:800; color:var(--ink-soft); padding:4px 8px; border-radius:8px; cursor:pointer;}
  .cal-month.active{background:#e2dcfb; color:#7c5fce;}
  .cal-dow{display:grid; grid-template-columns:repeat(7,1fr); text-align:center; font-size:10px; font-weight:800; color:var(--ink-soft); margin-bottom:6px;}
  .cal-days{display:grid; grid-template-columns:repeat(7,1fr); gap:4px;}
  .cal-day{aspect-ratio:1; display:flex; align-items:center; justify-content:center; font-size:11.5px; font-weight:700; color:var(--ink); border-radius:50%; cursor:pointer;}
  .cal-day:hover{background:#f6f0e2;}
  .cal-day.today{background:#3d4f8f; color:#fff;}
  .cal-day.selected{background:#bcd7f7; color:#2e4e80;}
  .cal-day.empty{visibility:hidden;}

  .agenda-card{background:var(--white); border-radius:24px; box-shadow:var(--shadow); padding:18px 20px; display:flex; flex-direction:column; overflow:hidden;}
  .agenda-card h3{margin-bottom:12px; color:#7c5fce;}
  #agendaList{flex:1 1 auto; min-height:0; overflow-y:auto; scrollbar-width:thin;}
  #agendaList::-webkit-scrollbar{width:5px;}
  #agendaList::-webkit-scrollbar-track{background:transparent;}
  #agendaList::-webkit-scrollbar-thumb{background:#ddd6fe; border-radius:10px;}
  #agendaList::-webkit-scrollbar-thumb:hover{background:#c4b5fd;}
  .agenda-item{background:#f5f0ff; border-left:4px solid #a98ee8; border-radius:12px; padding:10px 14px; margin-bottom:10px; font-size:12.5px; font-weight:700; color:var(--ink); position:relative; padding-right:34px;}
  .agenda-item:last-child{margin-bottom:0;}
  .agenda-item-titlerow{display:flex; flex-direction:column; align-items:flex-start; gap:4px; margin-bottom:3px;}
  .agenda-item b{display:block; font-size:13px; color:var(--ink); line-height:1.35;}
  /* Badge pembeda jenis jadwal: Agenda / Rapat / Tamu, nempel rapat di atas judul */
  .agenda-item-badge{flex:0 0 auto; font-size:9px; font-weight:800; letter-spacing:.4px; text-transform:uppercase; padding:2px 7px; border-radius:6px; line-height:1.5;}
  /* Warna disamakan dengan tema kartu di panel "Operasional Harian":
     Rapat = kuning, Agenda = biru, Tamu = pink. */
  .agenda-item[data-type="agenda"]{border-left-color:#569ce6; background:#f6faff;}
  .agenda-item[data-type="agenda"] .agenda-item-badge{background:#dceafd; color:#3d72c4;}
  .agenda-item[data-type="rapat"]{border-left-color:#f4b73a; background:#fffbf0;}
  .agenda-item[data-type="rapat"] .agenda-item-badge{background:#fff3c9; color:#b5791b;}
  .agenda-item[data-type="tamu"]{border-left-color:#ee80ac; background:#fff7fb;}
  .agenda-item[data-type="tamu"] .agenda-item-badge{background:#fce0ee; color:#c4507e;}
  .agenda-head{display:flex; align-items:center; justify-content:space-between; margin-bottom:12px; flex:0 0 auto;}
  .agenda-head h3{margin-bottom:0;}
  .agenda-add-btn{width:26px; height:26px; border-radius:9px; border:none; cursor:pointer; background:linear-gradient(135deg,#c9b6f2,#a98ee8); color:#fff; display:flex; align-items:center; justify-content:center; box-shadow:var(--shadow); flex:0 0 26px;}
  .agenda-add-btn svg{width:13px; height:13px;}
  .agenda-form{display:none; flex-direction:column; gap:8px; background:#faf7ff; border:1.5px dashed #d9c9f7; border-radius:14px; padding:12px; margin-bottom:12px; flex-shrink:0;}
  .agenda-form.show{display:flex;}
  .agenda-form input{font-family:inherit; font-size:12px; font-weight:700; color:var(--ink); background:var(--white); border:1.5px solid #f0e6d3; border-radius:10px; padding:8px 10px; width:100%;}
  .agenda-form-row{display:flex; gap:8px;}
  .agenda-form-row input[type=time]{flex:0 0 100px;}
  .agenda-form-row input[type=text]{flex:1; min-width:0;}
  .agenda-form-actions{display:flex; justify-content:flex-end; gap:8px;}
  .agenda-form-cancel, .agenda-form-save{border:none; cursor:pointer; font-family:inherit; font-weight:800; font-size:11.5px; padding:7px 14px; border-radius:10px;}
  .agenda-form-cancel{background:#f2ecdd; color:var(--ink-soft);}
  .agenda-form-save{background:linear-gradient(135deg,#c9b6f2,#a98ee8); color:#fff;}
  .agenda-item-del{position:absolute; top:9px; right:10px; background:none; border:none; cursor:pointer; color:#b79fe0; padding:2px;}
  .agenda-item-del svg{width:13px; height:13px;}
  .agenda-empty{font-size:12px; font-weight:700; color:var(--ink-soft); padding:6px 2px;}
  .donut-legend{display:flex; flex-direction:column; gap:6px; margin-top:14px; width:100%;}
  .donut-legend-item{display:flex; align-items:center; gap:7px; font-size:11.5px; font-weight:700; color:var(--ink-soft);}
  .donut-legend-item b{margin-left:auto; color:var(--ink); font-size:12px;}
  .donut-dot{width:9px; height:9px; border-radius:50%; flex:0 0 9px;}
  .cal-day{position:relative;}
  .cal-day.has-event::after{content:""; position:absolute; bottom:3px; left:50%; transform:translateX(-50%); width:4px; height:4px; border-radius:50%; background:#ef9dbf;}
  .cal-day.today.has-event::after, .cal-day.selected.has-event::after{background:#fff;}

  /* ================= HALAMAN MANAJEMEN RAPAT ================= */
  .stat-lavender{background:linear-gradient(155deg,#f4efff,#e8dcfb); color:#6b4fa8;}
  html.dark-mode .stat-lavender{background:linear-gradient(155deg,#1e1a33,#26213f); color:#b6a3ec;}
  .rpt-top-grid{display:grid; grid-template-columns:220px 1fr 260px; gap:16px; margin-bottom:16px; align-items:stretch;}
  .rpt-side-col{display:flex; flex-direction:column; gap:16px;}
  .rpt-arsip-card{cursor:pointer; transition:transform .2s ease;}
  .rpt-arsip-card:hover{transform:translateY(-3px);}
  .rpt-arsip-card .lbl{font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.03em; margin-bottom:6px; opacity:.85;}
  .rpt-arsip-card .num{font-size:24px; font-weight:800; margin-bottom:2px;}
  .rpt-arsip-card .note{font-size:10.5px; font-weight:700; opacity:.75;}
  .rpt-today-panel{gap:10px;}
  .rpt-today-stats{display:flex; justify-content:space-around; gap:10px; margin-top:4px;}
  .rpt-today-item{display:flex; flex-direction:column; align-items:center; gap:4px;}
  .rpt-today-emoji{font-size:19px;}
  .rpt-today-label{font-size:10.5px; font-weight:700; color:var(--ink-soft);}
  .rpt-today-num{font-size:17px; font-weight:800; color:var(--ink);}
  .rpt-chart-panel .panel-head-row{margin-bottom:8px;}
  .rpt-chart-body{flex:1; display:flex; flex-direction:column; justify-content:center;}
  .rpt-chart-holder{height:150px; margin:6px 0 16px; border-radius:18px; overflow:hidden; border:2px solid #f2ecdd;}
  .rpt-chart-holder svg{display:block; width:100%; height:100%;}
  .rpt-chart-footer{display:flex; justify-content:space-between; color:var(--ink-soft); font-size:10.5px; font-weight:800; margin-top:6px;}
  .rpt-deadline-panel{gap:2px;}
  .rpt-deadline-list{list-style:none; margin:10px 0 0; padding:0; display:flex; flex-direction:column; gap:10px;}
  .rpt-deadline-item{display:flex; justify-content:space-between; align-items:center; gap:8px; padding:10px 12px; border-radius:14px; background:#faf7ef; border-left:4px solid var(--yellow-deep); transition:transform .2s ease, box-shadow .2s ease; cursor:pointer;}
  .rpt-deadline-item:hover{transform:translateX(5px); box-shadow:var(--shadow);}
  .rpt-deadline-item.urgent{background:#fff0f4; border-left-color:var(--pink-deep);}
  .rpt-deadline-item.warning{background:#fffaf0; border-left-color:var(--yellow-deep);}
  .rpt-deadline-item.chill{background:#f0f6ff; border-left-color:var(--blue);}
  .rpt-deadline-item.ongoing{background:#eafcf0; border-left-color:#4fae7c;}
  .rpt-deadline-info{display:flex; flex-direction:column; gap:2px; min-width:0;}
  .rpt-deadline-info b{font-size:12px; color:var(--ink); font-weight:800;}
  .rpt-deadline-info span{font-size:10.5px; color:var(--ink-soft); font-weight:700;}
  .rpt-deadline-time{flex:0 0 auto;}
  .rpt-main-row{display:flex; align-items:flex-start; gap:16px; flex-wrap:nowrap;}
  .rpt-list-panel{flex:0 0 300px; min-width:0;}
  .rpt-detail-panel{flex:1 1 0; min-width:0;}
  .rpt-meeting-list{list-style:none; margin:4px 0 0; padding:0; display:flex; flex-direction:column; gap:10px; max-height:520px; overflow-y:auto;}
  .rpt-meeting-item{display:flex; justify-content:space-between; align-items:center; gap:8px; background:#faf7ef; border-radius:15px; padding:12px 14px; border-left:4px solid var(--yellow-deep); cursor:pointer; transition:transform .2s ease, box-shadow .2s ease;}
  .rpt-meeting-item:hover{transform:translateY(-2px); box-shadow:var(--shadow);}
  .rpt-meeting-item.active{border-left-color:#a98ee8; background:#f5f0ff;}
  .rpt-meeting-item b{display:block; font-size:12.5px; color:var(--ink); margin-bottom:2px;}
  .rpt-meeting-item span{font-size:10.5px; color:var(--ink-soft); font-weight:700;}
  .rpt-meeting-actions{display:flex; gap:6px; flex:0 0 auto;}
  .rpt-edit-btn{padding:8px 14px !important; font-size:11.5px !important;}
  .rpt-detail-grid{display:grid; grid-template-columns:1.1fr 1fr .85fr; gap:14px; margin:14px 0;}
  .rpt-info-side, .rpt-action-side, .rpt-log-side{background:#faf7ef; border-radius:18px; padding:14px 16px; min-width:0;}
  .rpt-info-side h4, .rpt-action-side h4, .rpt-log-side h4, .rpt-card-box h4{font-size:12px; font-weight:800; color:var(--ink); margin-bottom:10px; text-transform:uppercase; letter-spacing:.03em;}
  .rpt-detail-item{display:flex; align-items:center; gap:8px; font-size:12.5px; color:var(--ink); font-weight:700; margin-bottom:9px; flex-wrap:wrap;}
  .rpt-detail-item:last-child{margin-bottom:0;}
  .rpt-detail-ico{flex:0 0 auto;}
  .rpt-detail-label{min-width:52px; color:var(--ink-soft); font-weight:800;}
  .rpt-detail-value{color:var(--ink); font-weight:700;}
  .rpt-action-list{list-style:none; margin:0 0 12px; padding:0; display:flex; flex-direction:column; gap:8px;}
  .rpt-action-list label{display:flex; align-items:center; gap:8px; font-size:12px; color:var(--ink); font-weight:700; cursor:pointer;}
  .rpt-action-list input[type=checkbox]{width:15px; height:15px; accent-color:#a98ee8;}
  .rpt-btn-remind{width:100%; border:none; cursor:pointer; font-family:inherit; font-weight:800; font-size:12px; padding:10px; border-radius:12px; background:linear-gradient(135deg,#f6a8c6,#ef9dbf); color:#fff; box-shadow:var(--shadow);}
  .rpt-log-side p{font-size:11px; font-weight:700; color:var(--ink-soft); margin-bottom:6px;}
  .rpt-log-side p:last-child{margin-bottom:0;}
  .rpt-bottom-grid{display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:14px;}
  .rpt-card-box{background:#faf7ef; border-radius:18px; padding:14px 16px; min-height:80px;}
  .rpt-empty{font-size:11.5px; font-weight:700; color:var(--ink-soft);}
  .rpt-btn-done{width:100%; border:none; cursor:pointer; font-family:inherit; font-weight:800; font-size:13px; padding:12px; border-radius:14px; background:linear-gradient(135deg,#8ee0ae,#4fc27a); color:#fff; box-shadow:var(--shadow);}
  .rpt-meeting-item.rpt-fade-out{opacity:0; transform:translateX(20px);}
  @media (max-width:1100px){
    .rpt-top-grid{grid-template-columns:1fr; }
    .rpt-side-col{flex-direction:row;}
    .rpt-detail-grid{grid-template-columns:1fr;}
  }
  @media (max-width:900px){
    .rpt-side-col{flex-direction:column;}
    .rpt-main-row{flex-direction:column;}
    .rpt-list-panel{flex:1 1 auto; width:100%;}
  }
  html.dark-mode .rpt-deadline-item, html.dark-mode .rpt-meeting-item, html.dark-mode .rpt-info-side, html.dark-mode .rpt-action-side, html.dark-mode .rpt-log-side, html.dark-mode .rpt-card-box{background:#26213f;}
  html.dark-mode .rpt-deadline-item.urgent{background:#3a2338;}
  html.dark-mode .rpt-deadline-item.chill{background:#1a2c46;}
  html.dark-mode .rpt-meeting-item.active{background:#26213f;}
  /* ================= END HALAMAN MANAJEMEN RAPAT ================= */

  .todo-card{background:var(--white); border-radius:24px; box-shadow:var(--shadow); padding:18px 20px; display:flex; flex-direction:column; overflow:hidden;}
  .todo-head{display:flex; align-items:center; justify-content:space-between; margin-bottom:14px; flex:0 0 auto;}
  .todo-head h3{color:#2f9d61;}
  .todo-add{width:30px; height:30px; border-radius:10px; border:none; cursor:pointer; background:#2f9d61; color:#fff; display:flex; align-items:center; justify-content:center; box-shadow:var(--shadow);}
  .todo-add svg{width:15px; height:15px;}
  .todo-item{display:flex; align-items:center; gap:10px; padding:9px 2px; border-bottom:1px solid #f5efe1; font-size:13px; font-weight:700; color:var(--ink);}
  .todo-item:last-child{border-bottom:none;}
  .todo-item input[type=checkbox]{width:16px; height:16px; accent-color:#2f9d61;}
  .todo-item span{flex:1;}
  .todo-item span[contenteditable="true"]{outline:none; cursor:text; border-radius:6px; padding:2px 4px; margin:-2px -4px;}
  .todo-item span[contenteditable="true"]:hover{background:#f6f0e2;}
  .todo-item span[contenteditable="true"]:focus{background:#f5f0ff; box-shadow:0 0 0 1.5px #c9b6f2;}
  .todo-item.done span{text-decoration:line-through; color:var(--ink-soft);}
  .todo-del{border:none; background:none; cursor:pointer; color:#e05a7a; display:flex;}
  .todo-del svg{width:15px; height:15px;}
  #todoList{flex:1 1 auto; min-height:0; overflow-y:auto; scrollbar-width:thin;}
  #todoList::-webkit-scrollbar{width:5px;}
  #todoList::-webkit-scrollbar-track{background:transparent;}
  #todoList::-webkit-scrollbar-thumb{background:#ddd6fe; border-radius:10px;}
  #todoList::-webkit-scrollbar-thumb:hover{background:#c4b5fd;}
  .todo-empty{font-size:12px; font-weight:700; color:var(--ink-soft); padding:14px 2px; text-align:center;}

  .notes-card{background:var(--white); border-radius:24px; box-shadow:var(--shadow); padding:18px 20px; display:flex; flex-direction:column;}
  .notes-card h3{color:#c9932c; margin-bottom:12px; flex:0 0 auto;}
  .notes-card textarea{min-height:150px; resize:none; border:none; outline:none; background:#fffaf0; border-radius:14px; padding:14px; font-family:inherit; font-size:12.5px; font-weight:700; color:var(--ink); overflow-y:auto; box-sizing:border-box;}
  .notes-card textarea::-webkit-scrollbar{width:5px;}
  .notes-card textarea::-webkit-scrollbar-track{background:transparent;}
  .notes-card textarea::-webkit-scrollbar-thumb{background:#f0dca3; border-radius:10px;}
  .notes-card textarea::-webkit-scrollbar-thumb:hover{background:#e6c877;}

  .bottom-grid{display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:20px;}
  .chart-card{background:var(--white); border-radius:24px; box-shadow:var(--shadow); padding:20px 22px;}
  .chart-card h3{text-align:center; margin-top:14px; font-size:14px;}
  .chart-legend{display:flex; justify-content:center; gap:16px; font-size:11px; font-weight:800; color:var(--ink); margin-bottom:8px;}
  .chart-legend span{display:inline-flex; align-items:center; gap:6px;}
  .chart-legend i{width:12px; height:12px; border-radius:4px; display:inline-block;}
  /* =====================================================================
     Kartu "Statistik Surat Mingguan" -- versi lebih manis: kop gradien
     pastel, judul pakai font heading, legend jadi chip bulat. Dibuat
     sepadat mungkin (head/legend dirapatkan) supaya TINGGI kartu tetap
     sama seperti versi sebelumnya, cuma tampilannya yang dipercantik.
     ===================================================================== */
  #chartCardSurat{position:relative; overflow:hidden; padding-top:0;}
  #chartCardSurat::before{content:''; position:absolute; top:0; left:0; right:0; height:70px; background:linear-gradient(120deg,#eef2ff 0%,#fdeffa 55%,#fff8ec 100%); border-radius:24px 24px 0 0; z-index:0;}
  .surat-chart-head{position:relative; z-index:1; text-align:center; padding-top:16px;}
  .surat-chart-head h3{font-family:var(--font-heading); font-size:16px; font-weight:700; color:var(--ink); margin:0 0 2px; letter-spacing:.01em;}
  .surat-chart-sub{font-size:10px; font-weight:700; color:var(--ink-soft);}
  .surat-chart-legend{position:relative; z-index:1; display:flex; justify-content:center; gap:10px; margin:10px 0 4px;}
  .surat-legend-chip{display:inline-flex; align-items:center; gap:6px; font-size:11px; font-weight:800; padding:5px 12px; border-radius:999px; background:var(--white); box-shadow:0 4px 10px rgba(150,130,180,.16);}
  .surat-legend-chip i{width:10px; height:10px; border-radius:50%; display:inline-block;}
  .surat-legend-chip.masuk{color:#2f6fb0;}
  .surat-legend-chip.keluar{color:#c4507e;}

  .deadline-head{background:#fbf8f2; border-radius:16px; padding:14px 16px; margin-bottom:14px; flex:0 0 auto;}
  .deadline-head h3{font-size:17px; margin-bottom:3px;}
  .deadline-head p{font-size:11.5px; color:var(--ink-soft); font-weight:700; margin:0;}
  .deadline-input-row{display:flex; gap:10px; margin-bottom:16px; flex:0 0 auto;}
  .deadline-input-row input{flex:1; border:1.5px solid #f0e6d3; border-radius:14px; padding:11px 16px; font-family:inherit; font-size:12.5px; font-weight:700; color:var(--ink); background:var(--white);}
  .deadline-input-row input:focus{outline:none; border-color:#c9b6f2;}
  .deadline-add{width:44px; border-radius:14px; border:none; background:linear-gradient(135deg,#8f7bd6,#6b52c9); color:#fff; cursor:pointer; display:flex; align-items:center; justify-content:center; box-shadow:var(--shadow);}
  .deadline-add svg{width:16px; height:16px;}
  .deadline-list{flex:1 1 auto; min-height:20px; overflow-y:auto; scrollbar-width:thin; margin-bottom:16px;}
  .deadline-list::-webkit-scrollbar{width:5px;}
  .deadline-list::-webkit-scrollbar-track{background:transparent;}
  .deadline-list::-webkit-scrollbar-thumb{background:#e5b7c6; border-radius:10px;}
  .deadline-list::-webkit-scrollbar-thumb:hover{background:#d896ab;}
  .deadline-list .todo-item{border-bottom:1px dashed #f0e6d3;}
  .deadline-empty{font-size:12px; font-weight:700; color:var(--ink-soft); padding:14px 2px; text-align:center;}
  .btn-clear{display:block; width:100%; border:none; cursor:pointer; font-family:inherit; font-weight:800; font-size:13px; padding:13px; border-radius:14px; background:linear-gradient(135deg,#f5a3bb,#e8637e); color:#fff; box-shadow:var(--shadow); flex:0 0 auto;}

  /* ---------- APP FOOTER ---------- */
  .app-footer{margin-top:22px; background:var(--white); border-radius:22px; box-shadow:var(--shadow); padding:16px 28px; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:14px; position:relative; overflow:hidden;}
  .footer-left{display:flex; align-items:center; gap:10px;}
  .footer-left .fl-flower{font-size:15px; flex:0 0 auto;}
  .footer-left .fl-mascot{width:30px; height:30px; flex:0 0 30px;}
  .footer-copy{font-size:11.5px; font-weight:700; color:var(--ink-soft); white-space:nowrap;}
  .footer-mid{display:flex; align-items:center; gap:10px; flex-wrap:wrap; font-size:11.5px; font-weight:700; color:var(--ink-soft);}
  .footer-mid span{cursor:pointer;}
  .footer-mid span:hover{color:#8a5fce;}
  .footer-mid .fm-dot{color:#e2cdf0;}
  .footer-right{display:flex; align-items:center; gap:10px;}
  .footer-right .fr-text{font-size:11.5px; font-weight:700; color:var(--ink-soft); white-space:nowrap;}
  .footer-right .fr-cloud{width:34px; height:34px; flex:0 0 34px; opacity:.9;}

  /* ---------- MANAJEMEN ATK ---------- */
  .atk-stat-grid{display:grid; grid-template-columns:repeat(5,1fr); gap:14px; margin-bottom:20px;}
  .atk-stat-card{border-radius:22px; padding:18px 20px 16px; box-shadow:var(--shadow); position:relative;}
  .atk-stat-card .astc-icon{width:40px; height:40px; border-radius:14px; display:flex; align-items:center; justify-content:center; font-size:18px; margin-bottom:12px;}
  .atk-stat-card .astc-num{font-size:25px; font-weight:800; font-family:'Baloo 2',sans-serif; line-height:1; margin-bottom:4px;}
  .atk-stat-card .astc-lbl{font-size:12px; font-weight:800; color:var(--ink); margin-bottom:2px;}
  .atk-stat-card .astc-sub{font-size:10.5px; font-weight:700; color:var(--ink-soft); margin-bottom:12px;}
  .atk-stat-card .astc-link{font-size:11px; font-weight:800; cursor:pointer; display:inline-flex; align-items:center; gap:3px;}
  .astc-pink{background:#fdeef6; border:1.5px solid #f8d3e4;}
  .astc-pink .astc-icon{background:#f6a8c6; color:#a3335e;}
  .astc-pink .astc-num, .astc-pink .astc-link{color:#c4507e;}
  .astc-orange{background:#fff8e6; border:1.5px solid #f7e3ad;}
  .astc-orange .astc-icon{background:#ffd782; color:#8a5f0f;}
  .astc-orange .astc-num, .astc-orange .astc-link{color:#a97a13;}
  .astc-blue{background:#eaf4ff; border:1.5px solid #c3ddf7;}
  .astc-blue .astc-icon{background:#a9cdf5; color:#2b4d80;}
  .astc-blue .astc-num, .astc-blue .astc-link{color:#3c66a8;}
  .astc-green{background:#eafaf0; border:1.5px solid #bfeed2;}
  .astc-green .astc-icon{background:#9fe3bc; color:#1f7a45;}
  .astc-green .astc-num, .astc-green .astc-link{color:#2f9d61;}
  .astc-cloud{background:#eef2fb; border:1.5px solid #cfdaf3;}
  .astc-cloud .astc-icon{background:#b9c9ef; color:#38487a;}
  .astc-cloud .astc-num, .astc-cloud .astc-link{color:#4d5f8f;}

  .atk-panel{background:var(--white); border-radius:26px; padding:24px 26px; box-shadow:var(--shadow); position:relative; overflow:hidden; margin-bottom:18px;}
  .atk-panel-head{display:flex; align-items:flex-start; justify-content:space-between; gap:16px; flex-wrap:wrap; margin-bottom:6px;}
  .atk-panel-title{display:flex; align-items:center; gap:10px; font-size:17px; font-weight:800; color:var(--ink); margin-bottom:4px;}
  .atk-panel-title .apt-icon{width:34px; height:34px; border-radius:12px; background:#eaf2fd; color:#3c66a8; display:flex; align-items:center; justify-content:center; font-size:16px; flex:0 0 34px;}
  .atk-panel-sub{font-size:12px; color:var(--ink-soft); font-weight:700; margin-left:44px;}
  .atk-mascot-corner{position:absolute; top:12px; right:26px; font-size:34px; opacity:.9;}

  .atk-pills{display:flex; flex-wrap:wrap; gap:10px; margin:20px 0 20px;}
  .atk-pill{display:flex; align-items:center; gap:6px; padding:9px 16px; border-radius:14px; font-size:12px; font-weight:800; cursor:pointer; background:#fff; border:1.5px solid #f0e6d3; color:var(--ink-soft); user-select:none;}
  .atk-pill.active{background:linear-gradient(135deg,#c9b6f2,#a98ee8); color:#fff; border-color:transparent; box-shadow:var(--shadow);}

  .atk-item-grid{display:grid; grid-template-columns:repeat(4,1fr); gap:14px;}
  .atk-item-card{background:#fbf8ff; border:1.5px solid #f0e9fb; border-radius:20px; padding:16px; position:relative;}
  .atk-item-illust{width:100%; height:66px; display:flex; align-items:center; justify-content:center; font-size:34px; margin-bottom:8px;}
  .atk-item-name{font-size:13px; font-weight:800; color:var(--ink); margin-bottom:8px;}
  .atk-item-stock{display:flex; justify-content:space-between; font-size:10.5px; font-weight:700; color:var(--ink-soft); margin-bottom:12px;}
  .atk-item-stock b{color:var(--ink); font-weight:800;}
  .atk-item-footer{display:flex; align-items:center; gap:8px;}
  .atk-qty{display:flex; align-items:center; border:1.5px solid #eee2f7; border-radius:10px; overflow:hidden; background:#fff;}
  .atk-qty button{width:24px; height:26px; border:none; background:#fff; color:var(--ink-soft); font-weight:800; cursor:pointer; font-size:13px;}
  .atk-qty span{width:22px; text-align:center; font-size:12px; font-weight:800; color:var(--ink);}
  .atk-btn-add{flex:1; border:none; border-radius:10px; background:linear-gradient(135deg,#f6a8c6,#ef85b0); color:#fff; font-weight:800; font-size:11.5px; padding:7px 0; cursor:pointer; box-shadow:0 4px 10px rgba(239,133,176,.35);}

  .atk-special-banner{display:flex; align-items:center; justify-content:space-between; gap:16px; background:linear-gradient(120deg,#fdeef6,#fce4ef); border:1.5px solid #f8d3e4; border-radius:20px; padding:16px 22px; margin-top:18px;}
  .atk-special-left{display:flex; align-items:center; gap:14px;}
  .atk-special-left .asl-emoji{font-size:34px;}
  .atk-special-left .asl-title{font-size:13px; font-weight:800; color:#c4507e; margin-bottom:2px;}
  .atk-special-left .asl-sub{font-size:11.5px; font-weight:700; color:var(--ink-soft);}
  .atk-btn-special{display:flex; align-items:center; gap:7px; border:1.5px solid #f6a8c6; background:#fff; color:#c4507e; font-family:inherit; font-weight:800; font-size:12px; padding:10px 16px; border-radius:14px; cursor:pointer; white-space:nowrap;}

  .atk-feature-grid{display:grid; grid-template-columns:repeat(4,1fr); gap:14px;}
  .atk-feature-card{background:var(--white); border-radius:20px; padding:16px 16px 14px; box-shadow:var(--shadow); position:relative;}
  .atk-feature-card .afc-icon{width:36px; height:36px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:16px; margin-bottom:10px;}
  .atk-feature-card .afc-title{font-size:12.5px; font-weight:800; color:var(--ink); margin-bottom:3px;}
  .atk-feature-card .afc-desc{font-size:10.5px; font-weight:700; color:var(--ink-soft); line-height:1.4; margin-bottom:10px;}
  .atk-feature-card .afc-link{font-size:11px; font-weight:800; color:#8a5fce; display:inline-flex; align-items:center; gap:3px;}
  .afc-badge{position:absolute; top:14px; right:14px; background:var(--pink-deep); color:#fff; font-size:9.5px; font-weight:800; padding:1px 6px; border-radius:999px;}

  .atk-rank-row{display:flex; align-items:center; gap:10px; padding:8px 0; border-bottom:1px dashed #f4eee0;}
  .atk-rank-row:last-child{border-bottom:none;}
  .atk-rank-num{width:20px; height:20px; border-radius:50%; background:#fff3cf; color:#a97a13; font-size:10.5px; font-weight:800; display:flex; align-items:center; justify-content:center; flex:0 0 20px;}
  .atk-rank-name{flex:1; font-size:12.5px; font-weight:700; color:var(--ink);}
  .atk-rank-count{font-size:11.5px; font-weight:800; color:var(--ink-soft);}

  .atk-hist-row{display:flex; align-items:center; justify-content:space-between; gap:10px; padding:10px 0; border-bottom:1px dashed #f4eee0;}
  .atk-hist-row:last-child{border-bottom:none;}
  .atk-hist-code{font-size:12.5px; font-weight:800; color:#c4507e;}
  .atk-hist-sub{font-size:10.5px; font-weight:700; color:var(--ink-soft); margin-top:2px;}

  .status-atk-disetujui{background:#d3f5df; color:#2f9d61;}
  .status-atk-menunggu{background:#fff3c9; color:#92400e;}
  .status-atk-selesai{background:#dceafd; color:#3d72c4;}
  .status-atk-ditolak{background:#ffd9de; color:#c9455f;}

  .atk-info-row{display:flex; align-items:center; gap:10px; padding:9px 0; font-size:12px; font-weight:700; color:var(--ink); cursor:pointer;}
  .atk-info-row .air-icon{width:28px; height:28px; border-radius:10px; background:#f3edfd; color:#8a5fce; display:flex; align-items:center; justify-content:center; font-size:13px; flex:0 0 28px;}
  .atk-info-row .air-arrow{margin-left:auto; color:#8a5fce; font-weight:800;}

  @media (max-width:1200px){
    .atk-stat-grid{grid-template-columns:repeat(2,1fr);}
    .atk-item-grid{grid-template-columns:repeat(2,1fr);}
    .atk-feature-grid{grid-template-columns:repeat(2,1fr);}
  }

  @media (max-width:1200px){
    .bigstat-grid{grid-template-columns:repeat(2,1fr);}
    .dash-grid{grid-template-columns:1fr;}
    .bottom-grid{grid-template-columns:1fr;}
  }

  /* ---------- MANAJEMEN DOKUMEN ---------- */
  .doc-hero{background:linear-gradient(120deg,#fff8ec,#fdeee0); border-radius:26px; padding:26px 30px; box-shadow:var(--shadow); display:flex; align-items:center; justify-content:space-between; margin-bottom:20px; overflow:hidden; position:relative;}
  .doc-hero-left .page-title{margin-bottom:2px;}
  .doc-hero-illust{flex:0 0 150px; display:flex; align-items:center; justify-content:center;}

  .doc-statgrid{display:grid; grid-template-columns:repeat(4,1fr); gap:14px; margin-bottom:18px;}
  .doc-statgrid .bigstat-card{min-height:118px; padding:16px 18px;}
  .doc-statgrid .bs-num{font-size:26px;}
  .doc-statgrid .bs-lbl{font-size:11.5px;}
  .doc-statgrid .bs-note{font-size:10.5px; font-weight:700; opacity:.65; margin-top:2px;}

  .quickaction-panel{background:var(--white); border-radius:22px; box-shadow:var(--shadow); padding:16px 18px; margin-bottom:18px;}
  .quickaction-panel h4{font-size:12.5px; font-weight:800; color:var(--ink-soft); text-transform:uppercase; letter-spacing:.04em; margin-bottom:12px;}
  .quickaction-row{display:flex; gap:10px; flex-wrap:wrap;}
  .quickaction-btn{display:flex; align-items:center; gap:7px; padding:10px 16px; border-radius:14px; border:1.5px solid #f0e6d3; background:#fff; font-family:inherit; font-weight:800; font-size:12.5px; color:var(--ink); cursor:pointer; box-shadow:0 2px 8px rgba(150,130,180,.06); transition:transform .12s ease;}
  .quickaction-btn:hover{transform:translateY(-2px);}
  .quickaction-btn svg{width:15px; height:15px;}
  .quickaction-btn.primary{border-color:#dcc9f7; background:#f8f3ff; color:#7c5fce;}

  .btn-outline{display:flex; align-items:center; justify-content:center; gap:7px; border:1.5px solid #e8dff5; cursor:pointer; font-family:inherit; font-weight:800; font-size:12.5px; padding:10px 16px; border-radius:14px; background:#fff; color:#7c5fce;}
  .btn-outline svg{width:14px; height:14px;}
  .doc-side-actions{display:flex; flex-direction:column; gap:10px; margin-bottom:16px;}

  .storage-panel .storage-head{display:flex; align-items:center; gap:10px; margin-bottom:12px;}
  .storage-panel .storage-icon{width:36px; height:36px; border-radius:12px; background:#eee2fb; color:#7c5fce; display:flex; align-items:center; justify-content:center; flex:0 0 36px;}
  .storage-panel .storage-icon svg{width:17px; height:17px;}
  .storage-panel .storage-title{font-size:13px; font-weight:800; color:var(--ink);}
  .storage-bar-track{height:9px; border-radius:999px; background:#f0eadd; overflow:hidden; margin-bottom:6px;}
  .storage-bar-fill{height:100%; border-radius:999px; background:linear-gradient(90deg,#c9b6f2,#a98ee8);}
  .storage-panel .storage-note{font-size:11px; font-weight:800; color:var(--ink-soft); text-align:right; margin-bottom:14px;}
  .storage-upsell{background:#fff8e2; border-radius:16px; padding:12px 14px; font-size:11px; font-weight:700; color:#a97a13; line-height:1.5; margin-bottom:12px;}
  .storage-upsell b{display:block; font-size:12px; margin-bottom:2px;}
  .btn-upgrade{display:block; width:100%; text-align:center; border:none; cursor:pointer; font-family:inherit; font-weight:800; font-size:12px; padding:11px; border-radius:12px; background:linear-gradient(135deg,#c9b6f2,#a98ee8); color:#fff;}

  .doc-icon{width:36px; height:36px; border-radius:11px; display:flex; align-items:center; justify-content:center; font-size:9.5px; font-weight:800; color:#fff; flex:0 0 36px; letter-spacing:.02em;}
  .doc-icon.pdf{background:linear-gradient(155deg,#f3897d,#e35a4d);}
  .doc-icon.docx{background:linear-gradient(155deg,#6fa8ef,#3d72c4);}
  .doc-icon.xlsx{background:linear-gradient(155deg,#5cc98a,#2f9d61);}
  .doc-icon.pptx{background:linear-gradient(155deg,#f2946a,#dd7233);}
  .doc-icon.zip{background:linear-gradient(155deg,#9aa6c9,#6b7aa8);}
  .doc-name-cell{display:flex; align-items:center; gap:11px;}
  .doc-name-cell .doc-name-txt{font-weight:800; color:var(--ink); display:flex; align-items:center; gap:6px;}
  .doc-name-cell .doc-star{color:#f4c765;}
  .doc-name-cell .doc-star svg{width:13px; height:13px;}
  .doc-uploader{font-weight:800; color:var(--ink);}
  .doc-uploader-role{font-size:11px; color:var(--ink-soft); font-weight:700; margin-top:2px;}

  .table-card.pink-head thead tr{background:linear-gradient(120deg,#fde3ec,#fbcbdd);}
  .table-card.pink-head thead th{color:#c4507e;}

  .pagination-row{display:flex; align-items:center; justify-content:space-between; margin-top:16px; flex-wrap:wrap; gap:10px;}
  .pagination-row .pg-info{font-size:12px; font-weight:700; color:var(--ink-soft);}
  .pg-btns{display:flex; align-items:center; gap:6px;}
  .pg-btn{min-width:30px; height:30px; padding:0 6px; border-radius:10px; border:none; background:#fff; box-shadow:0 2px 8px rgba(150,130,180,.08); font-family:inherit; font-weight:800; font-size:12px; color:var(--ink-soft); cursor:pointer;}
  .pg-btn.active{background:linear-gradient(135deg,#c9b6f2,#a98ee8); color:#fff;}
  .pg-dots{color:var(--ink-soft); font-weight:800; padding:0 2px;}
  .pg-perpage{border:1.5px solid #f0e6d3; border-radius:12px; padding:7px 12px; font-family:inherit; font-size:11.5px; font-weight:700; color:var(--ink); background:#fff;}

  .cat-list{display:flex; flex-direction:column; gap:11px;}
  .cat-row{display:flex; align-items:center; justify-content:space-between; font-size:12.5px; font-weight:700; color:var(--ink);}
  .cat-left{display:flex; align-items:center; gap:9px;}
  .cat-dot{width:9px; height:9px; border-radius:50%; display:inline-block;}
  .cat-count{color:var(--ink-soft); font-weight:800;}
  .cat-link-all{font-size:11px; font-weight:800; color:#8a5fce; cursor:pointer;}

  .helper-bubble{position:fixed; bottom:22px; right:26px; display:flex; align-items:center; gap:10px; background:var(--white); border-radius:999px; padding:8px 16px 8px 8px; box-shadow:var(--shadow); cursor:pointer; z-index:20;}
  .helper-bubble .av{width:34px; height:34px; border-radius:50%; background:linear-gradient(160deg,var(--blue),var(--lavender)); display:flex; align-items:center; justify-content:center;}
  .helper-bubble .txt b{display:block; font-size:11.5px; color:var(--ink);}
  .helper-bubble .txt span{font-size:10px; color:var(--ink-soft); font-weight:700;}

  @media (max-width:1200px){
    .doc-statgrid{grid-template-columns:repeat(2,1fr);}
    .doc-hero{flex-direction:column; align-items:flex-start; gap:14px;}
  }

  /* ===================== HALAMAN: BUKU TAMU (imported) ===================== */
.tm-wrap{
    --purple: #8b5cf6;
    --purple-dark: #7c3aed;
    --pink: #ec4899;
    --text-dark: #4c1d95;
    --text-muted: #9ca3af;
    --border: #f1e9fb;
    --green: #16a34a;
    --green-bg: #dcfce7;
    --orange: #f59e0b;
    --orange-bg: #fef3c7;
    --red: #ef4444;
    --red-bg: #fee2e2;
    --radius-lg: 20px;
    --radius-md: 14px;
    --radius-sm: 10px;
    --shadow: 0 3px 14px rgba(139, 92, 246, 0.10);
    --shadow-soft: 0 10px 28px rgba(139, 92, 246, 0.10);
  }
.tm-wrap *{ box-sizing: border-box; margin:0; padding:0; }
.tm-wrap{
    font-family: 'Segoe UI', 'Poppins', Arial, sans-serif;
    color: var(--text-dark);
  }
.tm-wrap a{ text-decoration:none; color:inherit; }
.tm-wrap button{ font-family:inherit; cursor:pointer; border:none; }
.tm-wrap{
    max-width: 1320px;
    margin: 0 auto;
  }
.tm-wrap .content-row{
    padding: 22px 22px 0;
  }
.tm-wrap .tm-main{ width: 100%; }
.tm-wrap .top-widgets{
    display: flex;
    gap: 14px;
    margin-bottom: 18px;
  }
.tm-wrap .top-widgets .reminder-card{ flex: 2; }
.tm-wrap .top-widgets .upcoming-widget{ flex: 1; display:flex; flex-direction:column; align-items:center; justify-content:center; }
.tm-wrap .hero{
    position: relative;
    background: linear-gradient(120deg, #efe8fe 0%, #fdeaf4 100%);
    border-radius: var(--radius-lg);
    padding: 24px 30px;
    margin-bottom: 18px;
    overflow: hidden;
    min-height: 118px;
  }
.tm-wrap .hero h1{
    font-size: 23px;
    font-weight: 800;
    color: #7c3aed;
    display:flex; align-items:center; gap:8px;
  }
.tm-wrap .hero p{
    margin-top: 5px;
    font-size: 13px;
    font-weight: 700;
    color: #b565c9;
  }
.tm-wrap .hero .bunny{
    position: absolute;
    right: 40px;
    top: 4px;
    font-size: 78px;
    filter: drop-shadow(0 8px 10px rgba(0,0,0,.08));
  }
.tm-wrap .hero .heart{
    position: absolute;
    right: 155px;
    top: 30px;
    font-size: 38px;
  }
.tm-wrap .hero .spark{ position:absolute; font-size: 15px; opacity:.75; }
.tm-wrap .hero .spark.s1{ top: 14px; left: 42%; }
.tm-wrap .hero .spark.s2{ top: 55px; left: 56%; }
.tm-wrap .hero .spark.s3{ bottom: 14px; left: 48%; }
.tm-wrap .hero .cloud{ position:absolute; font-size: 24px; opacity:.9; }
.tm-wrap .hero .cloud.c1{ top: 8px; left: 4px; }
.tm-wrap .stats{
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 12px;
    margin-bottom: 18px;
  }
.tm-wrap .tm-stat-card{
    border-radius: var(--radius-md);
    padding: 14px 14px 12px;
    box-shadow: var(--shadow);
    position: relative;
    overflow: hidden;
  }
.tm-wrap .tm-stat-card .label{ font-size: 11.5px; font-weight: 700; padding-right: 26px; line-height: 1.3; }
.tm-wrap .tm-stat-card .value{ font-size: 24px; font-weight: 800; margin-top: 4px; }
.tm-wrap .tm-stat-card .sub{ font-size: 10.5px; font-weight: 600; margin-top: 1px; opacity:.9; }
.tm-wrap .tm-stat-card .sub2{ font-size: 10.5px; font-weight: 700; margin-top: 8px; }
.tm-wrap .tm-stat-card .mascot{
    position: absolute;
    right: 12px;
    bottom: 10px;
    font-size: 26px;
    opacity: .95;
  }
.tm-wrap .tm-stat-card.pink{ background: linear-gradient(150deg, #fde3ef, #fbcfe8); color: #be185d; }
.tm-wrap .tm-stat-card.orange{ background: linear-gradient(150deg, #fef3c7, #fde68a); color: #b45309; }
.tm-wrap .tm-stat-card.green{ background: linear-gradient(150deg, #dcfce7, #bbf7d0); color: #15803d; }
.tm-wrap .tm-stat-card.blue{ background: linear-gradient(150deg, #dbeafe, #bfdbfe); color: #1d4ed8; }
.tm-wrap .tm-stat-card.purple{ background: linear-gradient(150deg, #ede9fe, #ddd6fe); color: #6d28d9; }
.tm-wrap .tm-stat-card .badge-red{
    position:absolute; top:10px; right: 10px;
    background: var(--red); color:#fff;
    font-size: 10px; font-weight: 700;
    padding: 2px 7px; border-radius: 10px;
    z-index: 2;
  }
.tm-wrap .section-header{
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 14px;
    flex-wrap: wrap;
  }
.tm-wrap .section-header h2{
    font-size: 17px;
    font-weight: 800;
    color: #7c3aed;
    display:flex; align-items:center; gap:6px;
    white-space: nowrap;
  }
.tm-wrap .filters{
    display: flex;
    align-items: center;
    gap: 8px;
    margin-left: auto;
    flex-wrap: nowrap;
    flex: 1 1 auto;
    justify-content: flex-end;
    overflow-x: auto;
    scrollbar-width: none;
    -ms-overflow-style: none;
    padding-bottom: 2px;
  }
.tm-wrap .filters::-webkit-scrollbar{ display:none; }
.tm-wrap .filter-btn{
    background: #fff;
    border: 1px solid var(--border);
    border-radius: 18px;
    padding: 7px 13px;
    font-size: 11.5px;
    font-weight: 600;
    color: #6b5b95;
    box-shadow: var(--shadow);
    display:flex; align-items:center; gap:5px;
    white-space: nowrap;
    flex-shrink: 0;
  }
.tm-wrap .filter-btn.danger{ color: #ef4444; }
.tm-wrap .filter-btn.primary{
    background: linear-gradient(90deg, #a78bfa, #ec4899);
    color: #fff;
    border: none;
  }
.tm-wrap .search-box{
    display:flex;
    align-items:center;
    gap:6px;
    background:#fff;
    border: 1px solid var(--border);
    border-radius: 18px;
    padding: 7px 13px;
    box-shadow: var(--shadow);
    flex: 1 1 170px;
    max-width: 220px;
    min-width: 130px;
    flex-shrink: 1;
  }
.tm-wrap .search-box .ico{ font-size: 12px; opacity:.8; flex-shrink:0; }
.tm-wrap .search-box input{
    border:none;
    outline:none;
    background:transparent;
    font-family:inherit;
    font-size: 11.5px;
    font-weight: 600;
    color: var(--text-dark);
    width: 100%;
    min-width: 0;
  }
.tm-wrap .search-box input::placeholder{ color: var(--text-muted); font-weight:600; }
.tm-wrap .tm-table-card{
    background: #fff;
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-soft);
    padding: 4px 0 14px;
    overflow-x: auto;
  }
.tm-wrap table{ width: 100%; border-collapse: collapse; min-width: 780px; table-layout: fixed; }
.tm-wrap col.c-no{ width: 5%; }
.tm-wrap col.c-istansi{ width: 21%; }
.tm-wrap col.c-pj{ width: 14%; }
.tm-wrap col.c-keperluan{ width: 18%; }
.tm-wrap col.c-tanggal{ width: 10%; }
.tm-wrap col.c-waktu{ width: 12%; }
.tm-wrap col.c-status{ width: 10%; }
.tm-wrap col.c-aksi{ width: 10%; }
.tm-wrap thead th{
    text-align: left;
    font-size: 10.5px;
    font-weight: 700;
    color: #b3a4d4;
    padding: 12px 8px;
    border-bottom: 2px solid #f4effc;
    text-transform: uppercase;
    letter-spacing: .02em;
  }
.tm-wrap thead th:first-child, .tm-wrap tbody td:first-child{ padding-left: 20px; }
.tm-wrap thead th:last-child, .tm-wrap tbody td:last-child{ padding-right: 20px; }
.tm-wrap tbody td{
    padding: 10px 8px;
    border-bottom: 1px solid #f6f2fb;
    vertical-align: middle;
    font-size: 11.5px;
  }
.tm-wrap tbody tr:last-child td{ border-bottom: none; }
.tm-wrap .num-badge{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    width: 28px; height: 28px;
    border-radius: 9px;
    font-size: 10.5px;
    font-weight: 800;
    color: #fff;
  }
.tm-wrap .num-badge.n1{ background: linear-gradient(135deg,#f472b6,#ec4899); }
.tm-wrap .num-badge.n2{ background: linear-gradient(135deg,#fbbf24,#f59e0b); }
.tm-wrap .num-badge.n3{ background: linear-gradient(135deg,#34d399,#10b981); }
.tm-wrap .num-badge.n4{ background: linear-gradient(135deg,#60a5fa,#3b82f6); }
.tm-wrap .num-badge.n5{ background: linear-gradient(135deg,#a78bfa,#8b5cf6); }
.tm-wrap .user-cell{ display:flex; align-items:center; gap:8px; min-width: 0; }
.tm-wrap .user-cell .av{
    width: 32px; height: 32px;
    border-radius: 50%;
    background: linear-gradient(135deg,#ddd6fe,#fbcfe8);
    display:flex; align-items:center; justify-content:center;
    font-size: 15px;
    flex-shrink: 0;
  }
.tm-wrap .user-cell .info{ min-width: 0; }
.tm-wrap .user-cell .info .uname{
    font-weight: 700;
    font-size: 11.5px;
    color: var(--text-dark);
    display:flex; align-items:center; gap:5px;
    flex-wrap: wrap;
  }
.tm-wrap .user-cell .info .uemail{ font-size: 10px; color: var(--text-muted); word-wrap: break-word; }
.tm-wrap .tag{
    font-size: 8px;
    font-weight: 800;
    padding: 1px 6px;
    border-radius: 7px;
    color: #fff;
  }
.tm-wrap .tag.new{ background: #ef4444; }
.tm-wrap .tag.top{ background: #f59e0b; }
.tm-wrap .msg-cell{ color: #52525b; line-height: 1.45; word-wrap: break-word; }
.tm-wrap .pj-cell .pname{ font-weight: 700; font-size: 11.5px; color: var(--text-dark); word-wrap: break-word; }
.tm-wrap .pj-cell .prole{ font-size: 10px; color: var(--text-muted); margin-top: 2px; }
.tm-wrap .keperluan-cell{ color: #52525b; line-height: 1.45; font-weight: 600; word-wrap: break-word; }
.tm-wrap .simple-cell{ font-weight: 700; color: var(--text-dark); }
.tm-wrap .simple-cell .sub{ font-size: 10px; color: var(--text-muted); margin-top: 2px; display:flex; align-items:center; gap:3px; font-weight:600; }
.tm-wrap .date-cell .d{ font-weight: 700; color: var(--text-dark); }
.tm-wrap .date-cell .t{ font-size: 10px; color: var(--text-muted); display:flex; align-items:center; gap:3px; margin-top:1px; }
.tm-wrap .ip-cell .ip{ font-weight: 700; color: var(--text-dark); }
.tm-wrap .ip-cell .browser{ font-size: 10px; color: var(--text-muted); display:flex; align-items:center; gap:3px; margin-top:1px; }
.tm-wrap .status-badge{
    display:inline-flex;
    align-items:center;
    gap: 3px;
    font-size: 10px;
    font-weight: 700;
    padding: 4px 10px;
    border-radius: 18px;
    white-space: nowrap;
  }
.tm-wrap .status-badge.done{ background: var(--green-bg); color: var(--green); }
.tm-wrap .status-badge.pending{ background: var(--orange-bg); color: var(--orange); }
.tm-wrap .status-badge.deleted{ background: var(--red-bg); color: var(--red); }
.tm-wrap .tm-actions{ display:flex; gap:5px; }
.tm-wrap .action-btn{
    width: 25px; height: 25px;
    border-radius: 7px;
    display:flex; align-items:center; justify-content:center;
    font-size: 11px;
  }
.tm-wrap .action-btn.view{ background: #ede9fe; color: #8b5cf6; }
.tm-wrap .action-btn.edit{ background: #fef3c7; color: #f59e0b; }
.tm-wrap .action-btn.del{ background: #fee2e2; color: #ef4444; }
.tm-wrap .table-footer{
    display:flex;
    align-items:center;
    justify-content: space-between;
    padding: 14px 4px 4px;
    flex-wrap: wrap;
    gap: 10px;
  }
.tm-wrap .table-footer .showing{ font-size: 11px; color: var(--text-muted); font-weight:600; }
.tm-wrap .pagination{ display:flex; align-items:center; gap: 5px; }
.tm-wrap .page-btn{
    min-width: 26px; height: 26px;
    border-radius: 7px;
    display:flex; align-items:center; justify-content:center;
    font-size: 11px;
    font-weight: 700;
    color: #7c5cbf;
    background: #f7f3ff;
    padding: 0 6px;
  }
.tm-wrap .page-btn.active{ background: linear-gradient(90deg,#a78bfa,#8b5cf6); color:#fff; }
.tm-wrap .rcard{
    background: #fff;
    border-radius: var(--radius-md);
    padding: 14px 15px;
    box-shadow: var(--shadow);
    position: relative;
    overflow: hidden;
  }
.tm-wrap .rcard h3{
    font-size: 12.5px;
    font-weight: 800;
    color: var(--text-dark);
    display:flex; align-items:center; gap:5px;
    margin-bottom: 10px;
  }
.tm-wrap .reminder-head{
    display:flex; align-items:center; justify-content:space-between;
    gap:8px; margin-bottom: 10px;
  }
.tm-wrap .reminder-head h3{ margin-bottom: 0; }
.tm-wrap .reminder-clear-btn{
    flex-shrink:0;
    font-size: 10px;
    font-weight: 700;
    color: #ef4444;
    background: #fee2e2;
    padding: 4px 10px;
    border-radius: 10px;
    display:flex; align-items:center; gap:4px;
    white-space: nowrap;
  }
.tm-wrap .reminder-clear-btn:hover{ background:#fecaca; }
.tm-wrap .reminder-list{
    display:flex; flex-direction:column; gap:2px;
    max-height: 132px;
    overflow-y: auto;
    padding-right: 2px;
    scrollbar-width: thin;
    scrollbar-color: #ddd6fe transparent;
  }
.tm-wrap .reminder-list::-webkit-scrollbar{ width: 5px; }
.tm-wrap .reminder-list::-webkit-scrollbar-track{ background: transparent; }
.tm-wrap .reminder-list::-webkit-scrollbar-thumb{ background: #ddd6fe; border-radius: 10px; }
.tm-wrap .reminder-list::-webkit-scrollbar-thumb:hover{ background: #c4b5fd; }
.tm-wrap .reminder-item{
    display:flex; align-items:flex-start; gap:7px;
    font-size:11px; color:#57534e; font-weight:600; line-height:1.35;
    cursor:pointer;
    padding: 5px 4px;
    border-radius: 8px;
    transition: background .15s ease;
  }
.tm-wrap .reminder-item:hover{ background: #faf8ff; }
.tm-wrap .reminder-item:hover .reminder-del{ opacity:1; }
.tm-wrap .reminder-check{ position:absolute; opacity:0; width:14px; height:14px; pointer-events:none; }
.tm-wrap .chk-box{
    width:14px;height:14px;border-radius:4px;
    border: 1.5px solid #ddd6fe;
    background:#fff;
    display:flex;align-items:center;justify-content:center;
    flex-shrink:0; margin-top:1px;
    font-size:9px; color:#fff; font-weight:800;
    transition: all .15s ease;
  }
.tm-wrap .reminder-check:checked + .chk-box{
    background: linear-gradient(135deg,#34d399,#10b981);
    border-color: transparent;
  }
.tm-wrap .reminder-check:checked + .chk-box::after{ content:"✓"; }
.tm-wrap .reminder-text{ flex:1; word-break: break-word; transition: all .15s ease; }
.tm-wrap .reminder-check:checked ~ .reminder-text{
    text-decoration: line-through;
    color: var(--text-muted);
    opacity: .75;
  }
.tm-wrap .reminder-del{
    flex-shrink:0;
    width:18px; height:18px;
    border-radius:6px;
    display:flex; align-items:center; justify-content:center;
    font-size:10px;
    color:#ef4444;
    background:#fee2e2;
    opacity:0;
    transition: opacity .15s ease, background .15s ease;
  }
.tm-wrap .reminder-del:hover{ background:#fecaca; }
.tm-wrap .reminder-add-row{
    display:flex; gap:6px;
    margin-top: 10px;
    padding-top: 10px;
    border-top: 1px dashed #ede9fe;
  }
.tm-wrap .reminder-input{
    flex:1; min-width:0;
    border: 1px solid var(--border);
    border-radius: 10px;
    padding: 6px 10px;
    font-size: 11px;
    font-family: inherit;
    font-weight: 600;
    color: var(--text-dark);
    outline: none;
  }
.tm-wrap .reminder-input::placeholder{ color: var(--text-muted); font-weight:600; }
.tm-wrap .reminder-input:focus{ border-color: var(--purple); }
.tm-wrap .reminder-add-btn{
    flex-shrink:0;
    background: linear-gradient(90deg, #a78bfa, #ec4899);
    color:#fff;
    font-size: 11px;
    font-weight: 700;
    padding: 6px 13px;
    border-radius: 10px;
    white-space: nowrap;
  }
.tm-wrap .reminder-card .deco{
    position:absolute; bottom:6px; right:10px; font-size: 26px; opacity:.15;
    pointer-events:none;
  }
.tm-wrap .upcoming-widget{
    background: linear-gradient(160deg, #fef9c3, #fde68a 55%, #fdba74);
    text-align:center;
    padding: 16px 14px;
  }
.tm-wrap .upcoming-widget h3{ justify-content:center; }
.tm-wrap .upcoming-avatar{
    width: 42px; height: 42px;
    border-radius: 50%;
    background: linear-gradient(135deg,#fef08a,#fdba74);
    display:flex; align-items:center; justify-content:center;
    font-size: 19px;
    margin: 2px auto 8px;
  }
.tm-wrap .upcoming-name{ font-size: 12.5px; font-weight: 800; color: #7c3aed; line-height:1.3; }
.tm-wrap .upcoming-role{ font-size: 10px; font-weight: 600; color: #a16207; margin-top: 2px; }
.tm-wrap .upcoming-time{ font-size: 11px; font-weight: 700; color: #c2410c; margin-top: 8px; display:flex; align-items:center; justify-content:center; gap:4px; }
.tm-wrap .upcoming-countdown{
    display:inline-block;
    font-size: 10px; font-weight: 700;
    color: #9a3412;
    background: #fff7ed;
    padding: 3px 11px;
    border-radius: 12px;
    margin-top: 7px;
  }
.tm-wrap .footer-bar{
    position: relative;
    background: linear-gradient(90deg, #c4a4f5, #f6a8d0);
    color: #fff;
    text-align: center;
    font-weight: 700;
    font-size: 12.5px;
    padding: 14px;
    margin: 18px 22px 22px;
    border-radius: 16px;
    letter-spacing: .01em;
    overflow: visible;
  }
.tm-wrap .footer-bar .corner{
    position:absolute;
    bottom: -8px;
    font-size: 34px;
  }
.tm-wrap .footer-bar .corner.left{ left: 6px; }
.tm-wrap .footer-bar .corner.right{ right: 6px; }


  @media (max-width: 700px){
.tm-wrap .top-widgets{ flex-direction: column; }
.tm-wrap .stats{ grid-template-columns: repeat(2,1fr); }
.tm-wrap .filters{ width: 100%; flex-wrap: wrap; overflow-x: visible; justify-content: flex-start; }
.tm-wrap .search-box{ flex: 1 1 100%; order: -1; max-width: none; }

  }



  /* ===================== HALAMAN: DATA PEGAWAI ===================== */
  .peg-header-wrap{position:relative;}
  .peg-bear-deco{position:absolute; top:-18px; right:6px; font-size:64px; filter:drop-shadow(0 8px 10px rgba(150,130,180,.18)); pointer-events:none; display:inline-block; transform-origin:70% 85%; animation:teddyEmojiWave 1.8s ease-in-out infinite;}
  .peg-bear-spark{position:absolute; top:-4px; right:96px; font-size:18px; opacity:.8; pointer-events:none; animation:sparkleTwinkle 2.2s ease-in-out infinite;}
  @keyframes teddyEmojiWave{ 0%,20%,100%{transform:rotate(0deg);} 30%{transform:rotate(-14deg);} 40%{transform:rotate(10deg);} 50%{transform:rotate(-10deg);} 60%{transform:rotate(6deg);} 70%{transform:rotate(0deg);} }

  .stat-purple{background:linear-gradient(155deg,#f1ecff,#e2d6fb); color:#6d28d9;}

  .peg-gender-panel .donut-wrap{align-items:stretch;}
  .peg-gender-boxes{display:flex; gap:12px; margin-top:2px;}
  .peg-gender-box{flex:1; border-radius:18px; padding:14px 12px; text-align:center; display:flex; flex-direction:column; align-items:center; gap:6px;}
  .peg-gender-box.male{background:linear-gradient(155deg,#eaf3ff,#d9e9fd);}
  .peg-gender-box.female{background:linear-gradient(155deg,#ffeef3,#fddce6);}
  .peg-gender-avatar{width:40px; height:40px; border-radius:50%; background:#fff; display:flex; align-items:center; justify-content:center; font-size:19px; box-shadow:0 3px 8px rgba(0,0,0,.06);}
  .peg-gender-label{font-size:11.5px; font-weight:800; color:#3c66a8;}
  .peg-gender-box.female .peg-gender-label{color:#c4507e;}
  .peg-gender-num{font-size:22px; font-weight:800; color:#1d4ed8;}
  .peg-gender-box.female .peg-gender-num{color:#db2777;}
  .peg-gender-pct{font-size:10.5px; font-weight:700; color:#5f7cad; opacity:.85;}
  .peg-gender-box.female .peg-gender-pct{color:#c4507e; opacity:.85;}
  .peg-gender-bar{display:flex; height:12px; border-radius:999px; overflow:hidden; margin-top:14px; background:#f2ecdd;}
  .peg-gender-bar span{height:100%;}
  .peg-gender-bar-row{display:flex; align-items:center; justify-content:space-between; margin-top:6px; font-size:14px;}

  .peg-status-list{display:flex; flex-direction:column; gap:11px; margin-top:2px;}
  .peg-status-row{display:flex; align-items:center; gap:10px; font-size:12.5px; font-weight:800; color:var(--ink);}
  .peg-status-icon{width:28px; height:28px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:13.5px; flex:0 0 28px;}
  .peg-status-icon.tetap{background:#ede9fe; color:#7c3aed;}
  .peg-status-icon.kontrak{background:#fef3c7; color:#b45309;}
  .peg-status-icon.magang{background:#dbeafe; color:#1d4ed8;}
  .peg-status-icon.lainnya{background:#f2ecdd; color:#8a8672;}
  .peg-status-name{flex:1;}
  .peg-status-count{color:var(--ink); font-weight:800;}
  .peg-status-pct{color:var(--ink-soft); font-weight:700; font-size:11.5px; min-width:44px; text-align:right;}

  .peg-lihat-semua{border:none; cursor:pointer; font-family:inherit; font-weight:800; font-size:10.5px; padding:6px 14px; border-radius:999px; background:#ffd7e6; color:#c4507e; margin-left:auto;}

  .peg-list-row{display:flex; align-items:center; gap:10px; padding:9px 0; border-bottom:1px dashed #f2ecdd;}
  .peg-list-row:last-child{border-bottom:none;}
  .peg-list-avatar{width:34px; height:34px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:16px; flex:0 0 34px; box-shadow:0 3px 8px rgba(150,130,180,.12);}
  .peg-list-info{flex:1; min-width:0;}
  .peg-list-name{font-size:12.5px; font-weight:800; color:var(--ink);}
  .peg-list-role{font-size:10.5px; font-weight:700; color:var(--ink-soft); margin-top:1px;}
  .peg-list-date{font-size:11px; font-weight:800; color:var(--ink-soft); white-space:nowrap;}
  .peg-list-tag{font-size:9.5px; font-weight:800; padding:3px 9px; border-radius:999px; white-space:nowrap;}
  .peg-list-head{display:flex; align-items:center; gap:8px; margin-bottom:2px;}

  .peg-attend-list{display:flex; flex-direction:column; gap:12px; margin-top:6px;}
  .peg-attend-row{display:flex; align-items:center; gap:9px; font-size:12.5px; font-weight:700; color:var(--ink);}
  .peg-attend-icon{width:26px; height:26px; border-radius:9px; display:flex; align-items:center; justify-content:center; font-size:13px; flex:0 0 26px;}
  .peg-attend-name{flex:1;}
  .peg-attend-val{font-weight:800; color:var(--ink);}

  .peg-table-head{display:flex; align-items:center; gap:10px; flex-wrap:wrap; margin-bottom:14px;}
  .peg-table-head h3{font-size:15.5px; font-weight:800; color:var(--ink); display:flex; align-items:center; gap:7px;}
  .peg-table-tools{display:flex; align-items:center; gap:10px; margin-left:auto; flex-wrap:wrap;}
  .peg-search-mini{display:flex; align-items:center; gap:7px; background:var(--white); border-radius:999px; padding:9px 15px; box-shadow:var(--shadow); min-width:190px;}
  .peg-search-mini svg{width:14px; height:14px; color:var(--ink-soft); flex:0 0 auto;}
  .peg-search-mini input{border:none; outline:none; font-family:inherit; font-size:12.5px; flex:1; background:transparent; color:var(--ink);}
  .peg-filter-btn{display:flex; align-items:center; gap:6px; border:none; cursor:pointer; font-family:inherit; font-weight:800; font-size:12px; padding:9px 15px; border-radius:999px; background:var(--white); color:var(--ink); box-shadow:var(--shadow);}
  .peg-filter-btn svg{width:13px; height:13px;}

  .chip-tetap{background:#ede9fe; color:#7c3aed;}
  .chip-kontrak{background:#fef3c7; color:#b45309;}
  .chip-magang{background:#dbeafe; color:#1d4ed8;}
  .chip-laki{background:#dbeafe; color:#1d4ed8;}
  .chip-perempuan{background:#fce7f3; color:#db2777;}

  .peg-emp-cell{display:flex; align-items:center; gap:9px;}
  .peg-emp-avatar{width:32px; height:32px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:14px; flex:0 0 32px; box-shadow:0 3px 8px rgba(150,130,180,.12);}

  .peg-pagination{display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px; margin-top:16px;}
  .peg-pagination .info{font-size:12px; font-weight:700; color:var(--ink-soft);}
  .peg-page-btns{display:flex; align-items:center; gap:6px;}
  .peg-page-btn{min-width:30px; height:30px; padding:0 8px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:12.5px; font-weight:800; color:#8a5fce; background:var(--white); box-shadow:var(--shadow); cursor:pointer;}
  .peg-page-btn.active{background:linear-gradient(135deg,#c9b6f2,#a98ee8); color:#fff;}
  .peg-page-btn.dots{box-shadow:none; background:transparent; cursor:default;}
  .peg-perpage{font-family:inherit; font-size:12px; font-weight:700; color:var(--ink); background:var(--white); border:none; border-radius:12px; padding:8px 14px; box-shadow:var(--shadow);}

  /* ================= MODAL GENERIK ================= */
  .modal-overlay{position:fixed; inset:0; background:rgba(92,107,147,.35); backdrop-filter:blur(2px); display:none; align-items:flex-start; justify-content:center; z-index:200; padding:40px 16px; overflow-y:auto;}
  .modal-overlay.show{display:flex;}
  .modal-box{background:var(--cream); border-radius:26px; box-shadow:0 20px 50px rgba(120,100,160,.28); width:100%; max-width:560px; padding:0; position:relative; animation:modalPop .25s ease; overflow:hidden;}
  @keyframes modalPop{from{opacity:0; transform:translateY(14px) scale(.97);} to{opacity:1; transform:translateY(0) scale(1);}}
  .modal-head{display:flex; align-items:center; gap:12px; padding:22px 26px 16px; background:linear-gradient(135deg,#f3e9ff,#eef5ff);}
  .modal-head-icon{width:42px; height:42px; border-radius:15px; background:linear-gradient(160deg,var(--lavender),var(--blue)); display:flex; align-items:center; justify-content:center; box-shadow:var(--shadow); flex:0 0 42px;}
  .modal-head-icon svg{width:20px; height:20px; color:#5b4590;}
  .modal-title{font-family:'Baloo 2',sans-serif; font-weight:800; font-size:17px; color:var(--ink);}
  .modal-sub{font-size:11.5px; font-weight:700; color:var(--ink-soft); margin-top:1px;}
  .modal-close{margin-left:auto; width:32px; height:32px; border-radius:50%; border:none; cursor:pointer; background:#fff; display:flex; align-items:center; justify-content:center; color:var(--ink-soft); box-shadow:var(--shadow); flex:0 0 32px;}
  .modal-close svg{width:14px; height:14px;}
  .modal-body{padding:20px 26px 6px; max-height:60vh; overflow-y:auto;}
  .modal-footer{display:flex; justify-content:flex-end; gap:10px; padding:18px 26px 24px;}

  /* ---- Preferensi: toggle switch & pilihan ---- */
  .pref-modal-desc{font-size:12.5px; font-weight:700; color:var(--ink-soft); margin-bottom:16px; line-height:1.5;}
  .toggle-row{display:flex; align-items:center; justify-content:space-between; gap:14px; padding:13px 2px; border-bottom:1px solid #f0e6d3;}
  .toggle-row:last-child{border-bottom:none;}
  .toggle-row-label{font-size:13px; font-weight:800; color:var(--ink);}
  .toggle-row-sub{font-size:11px; font-weight:700; color:var(--ink-soft); margin-top:2px;}
  .toggle-switch{position:relative; display:inline-block; width:42px; height:24px; flex:0 0 42px; cursor:pointer;}
  .toggle-switch input{opacity:0; width:0; height:0;}
  .toggle-switch .slider{position:absolute; inset:0; background:#e6e0d3; border-radius:999px; transition:background .2s ease;}
  .toggle-switch .slider::before{content:""; position:absolute; width:18px; height:18px; left:3px; top:3px; background:#fff; border-radius:50%; transition:transform .2s ease; box-shadow:0 1px 3px rgba(0,0,0,.25);}
  .toggle-switch input:checked + .slider{background:linear-gradient(135deg,#c9b6f2,#a98ee8);}
  .toggle-switch input:checked + .slider::before{transform:translateX(18px);}

  .option-list{display:flex; flex-direction:column; gap:8px;}
  .option-row{display:flex; align-items:center; justify-content:space-between; gap:10px; padding:12px 14px; border:1.5px solid #f0e6d3; border-radius:14px; cursor:pointer; transition:border-color .15s, background .15s;}
  .option-row:hover{background:#faf7ef;}
  .option-row.active{border-color:#a98ee8; background:#f6f0ff;}
  .option-row-left{display:flex; align-items:center; gap:10px; font-size:13px; font-weight:800; color:var(--ink);}
  .option-row-flag{font-size:16px;}
  .option-row-swatch{width:26px; height:26px; border-radius:50%; flex:0 0 26px; box-shadow:0 0 0 2px #fff, 0 0 0 3px #e9e2d3;}
  .cursor-swatch{background:#f3eefc; color:#7c5fce; font-size:14px; display:flex; align-items:center; justify-content:center;}
  .pref-quick-list{display:flex; flex-direction:column; gap:2px;}
  .option-row-check{width:18px; height:18px; color:#a98ee8; opacity:0; transition:opacity .15s;}
  .option-row.active .option-row-check{opacity:1;}

  /* ---- Dark Mode ---- */
  html.dark-mode{
    --cream:#14161f;
    --ink:#e6e8f2;
    --ink-soft:#98a0bb;
    --blue:#35507d;
    --pink:#6a3f5a;
    --pink-deep:#d98bb0;
    --yellow:#4a3d1f;
    --yellow-deep:#d9a95c;
    --mint:#285944;
    --lavender:#4a3f78;
    --white:#1b1e2c;
    --shadow: 0 10px 24px rgba(0,0,0,.5);
  }
  html.dark-mode body{
    background:
      radial-gradient(circle at 10% 10%, rgba(60,90,140,.18) 0%, transparent 45%),
      radial-gradient(circle at 92% 6%, rgba(110,90,160,.14) 0%, transparent 45%),
      radial-gradient(circle at 85% 95%, rgba(90,70,120,.10) 0%, transparent 40%),
      var(--cream);
  }
  html.dark-mode .sidebar{border-right-color:#1a2c46;}
  html.dark-mode .toolbar input[type=text], html.dark-mode .toolbar select, html.dark-mode .toolbar input[type=date],
  html.dark-mode .form-control, html.dark-mode .agenda-form input, html.dark-mode .search-pill input{
    background:var(--white); border-color:#3a3f5c; color:var(--ink);
  }
  html.dark-mode .nav-item.active{background:linear-gradient(135deg,#1a2c46,#26213f);}
  html.dark-mode .nav-item.active.masuk-active{background:linear-gradient(135deg,#1a2c46,#3a2338);}
  .form-group{margin-bottom:16px;}
  .form-group label{display:block; font-size:11.5px; font-weight:800; color:var(--ink); margin-bottom:6px;}
  .form-group .hint{font-size:10.5px; font-weight:700; color:var(--ink-soft); margin-top:5px;}
  .form-row{display:grid; grid-template-columns:1fr 1fr; gap:14px;}
  .form-control{width:100%; font-family:inherit; font-size:12.5px; font-weight:700; color:var(--ink); background:var(--white); border:1.5px solid #f0e6d3; border-radius:14px; padding:10px 14px; outline:none;}
  .form-control:focus{border-color:#c9b6f2;}
  textarea.form-control{resize:vertical; min-height:78px; font-family:inherit;}
  .btn-secondary{border:none; cursor:pointer; font-family:inherit; font-weight:800; font-size:12.5px; padding:10px 18px; border-radius:14px; background:#eee6f9; color:#7c5fce;}
  .btn-primary{display:flex; align-items:center; gap:7px; border:none; cursor:pointer; font-family:inherit; font-weight:800; font-size:12.5px; padding:10px 18px; border-radius:14px; box-shadow:var(--shadow); background:linear-gradient(135deg,#c9b6f2,#a98ee8); color:#fff;}
  .btn-primary svg{width:14px; height:14px;}

  /* ---------- PROFIL SAYA ---------- */
  .profil-banner{position:relative; overflow:hidden; border-radius:28px; padding:26px 34px; margin-bottom:18px; background:linear-gradient(120deg,#eaf1fd 0%,#f1e7fc 48%,#fde9f1 100%); box-shadow:var(--shadow); display:flex; align-items:center; justify-content:space-between; gap:18px; flex-wrap:wrap;}
  .profil-banner-text{position:relative; z-index:2;}
  .profil-banner-greet{font-size:12.5px; font-weight:800; color:#a98ee8; display:flex; align-items:center; gap:6px;}
  .profil-banner-title{font-size:25px; font-weight:800; color:var(--ink); margin-top:5px; display:flex; align-items:center; gap:8px;}
  .profil-banner-sub{font-size:12.5px; font-weight:700; color:var(--ink-soft); margin-top:6px;}
  .profil-banner-illus{flex:0 0 auto; position:relative; z-index:2; line-height:0;}
  .profil-banner-peek{position:absolute; top:-24px; right:70px; width:52px; height:52px; border-radius:50%; background:linear-gradient(160deg,var(--blue),#9cc6f6); box-shadow:var(--shadow); z-index:1;}
  .profil-banner-peek.p2{top:20px; right:-16px; width:30px; height:30px; background:linear-gradient(160deg,var(--pink),var(--pink-deep)); opacity:.75;}
  @media (max-width: 900px){ .profil-banner-illus{display:none;} }

  .profil-top-flex{display:flex; gap:22px; align-items:flex-start; flex-wrap:wrap;}
  .profil-avatar-col{display:flex; flex-direction:column; align-items:center; gap:5px; flex:0 0 128px; text-align:center;}
  .profil-photo-avatar{position:relative; width:84px; height:84px; flex:0 0 84px; border-radius:50%; background:linear-gradient(160deg,var(--blue),var(--lavender)); display:flex; align-items:center; justify-content:center; box-shadow:var(--shadow); overflow:hidden;}
  .profil-photo-avatar img{width:100%; height:100%; object-fit:cover;}
  .avatar-cam-btn{position:absolute; bottom:-2px; right:-2px; width:26px; height:26px; border-radius:50%; background:#fff; border:2px solid #f3eefc; display:flex; align-items:center; justify-content:center; font-size:12px; cursor:pointer; box-shadow:var(--shadow); padding:0;}
  .profil-photo-meta{flex:1 1 auto; min-width:120px;}
  .ppi-name{font-family:'Baloo 2',sans-serif; font-weight:800; font-size:14px; color:var(--ink); line-height:1.25; margin-top:4px;}
  .ppi-role{font-size:10.5px; color:var(--ink-soft); font-weight:700;}
  .profil-photo-actions{display:flex; align-items:center; gap:8px; flex:0 0 auto; margin-top:4px;}
  .profil-avatar-col .btn-outline{padding:7px 14px; font-size:11px; margin-top:4px;}
  .btn-text-danger{border:none; background:none; cursor:pointer; font-family:inherit; font-weight:800; font-size:11px; color:#e0668f; padding:2px;}
  .profil-data-col{flex:1; min-width:200px;}

  .profil-toprow{display:grid; grid-template-columns:1.3fr 1fr; gap:16px; align-items:start;}
  .profil-col-left, .profil-col-right{display:flex; flex-direction:column; gap:16px;}
  .profil-grid{display:grid; grid-template-columns:1fr 1fr; gap:16px; align-items:start;}
  .profil-panel{grid-column:span 1;}
  .profil-edit-btn{flex:0 0 auto; white-space:nowrap;}
  .profil-info-list{display:flex; flex-direction:column; margin-top:10px;}
  .profil-info-row{display:flex; align-items:center; justify-content:space-between; gap:10px; padding:12px 2px; border-bottom:1px dashed #eee2cd;}
  .profil-info-row:last-child{border-bottom:none;}
  .pi-label{font-size:12px; font-weight:800; color:var(--ink-soft); display:flex; align-items:center; gap:7px;}
  .pi-value{font-size:13px; font-weight:800; color:var(--ink); text-align:right;}
  .profil-form-actions{display:flex; justify-content:flex-end; gap:10px; margin-top:6px;}

  /* mini cards: Status Akun / Login Terakhir / Kel. Profil */
  .status-mini-row{display:grid; grid-template-columns:repeat(3,1fr); gap:16px; align-items:stretch; margin-top:16px;}
  .mini-card{background:var(--white); border-radius:20px; padding:16px 16px; box-shadow:var(--shadow); display:flex; flex-direction:column; position:relative; overflow:hidden;}
  .mini-card h4{font-size:12px; font-weight:800; display:flex; align-items:center; gap:6px; margin-bottom:11px; color:var(--ink); position:relative; z-index:1;}
  .mini-card-body{flex:1; display:flex; flex-direction:column; justify-content:flex-start; position:relative; z-index:1;}
  .check-row{display:flex; align-items:center; gap:7px; font-size:11px; font-weight:700; color:var(--ink); padding:4px 0;}
  .check-row svg{width:14px; height:14px; color:#2f9d61; flex:0 0 14px;}
  .check-row.pending svg{color:#c9b98a;}
  .login-line{font-size:11px; font-weight:700; color:var(--ink); display:flex; align-items:center; gap:7px; padding:4px 0;}
  .login-line svg{width:13px; height:13px; color:var(--ink-soft); flex:0 0 13px;}
  .kelprofil-donut{display:flex; flex-direction:column; align-items:center; gap:2px;}
  .kelprofil-pct{font-family:'Baloo 2',sans-serif; font-weight:800; font-size:20px; fill:#7c5fce;}
  .kelprofil-list{margin-top:8px; width:100%;}

  /* cute decorative blobs peeking from card corners */
  .mc-deco{position:absolute; border-radius:50%; z-index:0; pointer-events:none;}
  .mc-deco.d1{width:76px; height:76px; top:-30px; right:-24px; opacity:.55;}
  .mc-deco.d2{width:34px; height:34px; bottom:-12px; left:-10px; opacity:.4;}
  .mc-deco.mint1{background:linear-gradient(160deg,var(--mint),#eafcf0);}
  .mc-deco.mint2{background:linear-gradient(160deg,#8fd6a6,var(--mint));}
  .mc-deco.blue1{background:linear-gradient(160deg,var(--blue),#eaf2fd);}
  .mc-deco.blue2{background:linear-gradient(160deg,#9cc6f6,var(--blue));}
  .mc-deco.lav1{background:linear-gradient(160deg,var(--lavender),#f3eefc);}
  .mc-deco.lav2{background:linear-gradient(160deg,#c9b6f2,var(--lavender));}

  /* colorful pill chips for status/login info */
  .chip-row{display:flex; flex-wrap:wrap; gap:7px; margin-top:2px;}
  .chip-pill{display:inline-flex; align-items:center; gap:6px; padding:7px 11px; border-radius:999px; font-size:10.5px; font-weight:800; white-space:nowrap;}
  .chip-pill svg{width:11px; height:11px; flex:0 0 11px;}
  .chip-pill.mint{background:#eafcf0; color:#309a5c;}
  .chip-green{background:#dcf7e4; color:#2f9d5c;}
  .chip-pill.blue{background:#eaf2fd; color:#3c66a8;}

  /* playful highlight badge inside mini-card */
  .mc-highlight{margin-top:14px; display:flex; align-items:center; gap:10px; padding:11px 13px; border-radius:16px;}
  .mc-highlight.warm{background:linear-gradient(120deg,#fff3c9,#ffe6a0);}
  .mc-highlight.cool{background:linear-gradient(120deg,#eaf6ec,#d7f3e0);}
  .mc-highlight-emoji{font-size:19px; line-height:1;}
  .mc-highlight .num{font-family:'Baloo 2',sans-serif; font-weight:800; font-size:14.5px; color:var(--ink);}
  .mc-highlight .lbl{font-size:10px; font-weight:700; color:var(--ink-soft); margin-top:1px;}
  .mini-card .panel-footer{position:relative; z-index:1;}

  /* ---- cute little animations ---- */
  @keyframes mcFloatA{ 0%,100%{transform:translate(0,0) scale(1);} 50%{transform:translate(-4px,-9px) scale(1.05);} }
  @keyframes mcFloatB{ 0%,100%{transform:translate(0,0) scale(1);} 50%{transform:translate(5px,7px) scale(1.08);} }
  .mc-deco.d1{animation:mcFloatA 5s ease-in-out infinite;}
  .mc-deco.d2{animation:mcFloatB 4.2s ease-in-out infinite;}

  @keyframes mcPop{ 0%{opacity:0; transform:translateY(6px) scale(.9);} 100%{opacity:1; transform:translateY(0) scale(1);} }
  .chip-pill{animation:mcPop .45s ease-out backwards;}
  .chip-row .chip-pill:nth-child(1){animation-delay:.05s;}
  .chip-row .chip-pill:nth-child(2){animation-delay:.15s;}
  .chip-row .chip-pill:nth-child(3){animation-delay:.25s;}
  .chip-pill{transition:transform .2s ease;}
  .chip-pill:hover{transform:translateY(-2px) scale(1.04);}

  @keyframes mcBounce{ 0%,100%{transform:translateY(0) rotate(0deg);} 50%{transform:translateY(-3px) rotate(-6deg);} }
  .mc-highlight-emoji{display:inline-block; animation:mcBounce 1.8s ease-in-out infinite;}
  .mc-highlight.cool .mc-highlight-emoji{animation:mcPulseDot 1.4s ease-in-out infinite;}
  @keyframes mcPulseDot{ 0%,100%{transform:scale(1); filter:drop-shadow(0 0 0 rgba(66,190,120,.5));} 50%{transform:scale(1.25); filter:drop-shadow(0 0 5px rgba(66,190,120,.55));} }

  @keyframes sparkleTwinkle{ 0%,100%{opacity:0; transform:scale(.4) rotate(0deg);} 50%{opacity:1; transform:scale(1) rotate(15deg);} }
  .mc-sparkle{position:absolute; font-size:13px; z-index:1; pointer-events:none; animation:sparkleTwinkle 2.4s ease-in-out infinite;}
  .mc-sparkle.s1{top:10px; right:34px; animation-delay:.2s;}
  .mc-sparkle.s2{top:44px; right:14px; font-size:9px; animation-delay:1.1s;}

  .pf-icon{transition:transform .2s ease;}
  @keyframes pfWiggle{ 0%,100%{transform:rotate(0deg);} 25%{transform:rotate(-8deg);} 75%{transform:rotate(8deg);} }
  @keyframes iconWiggle{ 0%,100%{transform:rotate(0deg) scale(1);} 25%{transform:rotate(-12deg) scale(1.1);} 75%{transform:rotate(12deg) scale(1.1);} }
  @keyframes iconPop{ 0%{transform:scale(1);} 40%{transform:scale(1.25);} 100%{transform:scale(1);} }
  @keyframes iconRing{ 0%,100%{transform:rotate(0deg);} 15%{transform:rotate(-15deg);} 30%{transform:rotate(12deg);} 45%{transform:rotate(-9deg);} 60%{transform:rotate(6deg);} 75%{transform:rotate(-3deg);} }
  @keyframes iconFloat{ 0%,100%{transform:translateY(0) rotate(0deg);} 50%{transform:translateY(-4px) rotate(6deg);} }
  .panel-footer:hover .pf-icon{animation:pfWiggle .5s ease-in-out;}

  .kelprofil-donut svg circle.kp-prog{stroke-dasharray:251; stroke-dashoffset:251; animation:donutFill 1.3s cubic-bezier(.34,1.4,.64,1) .25s forwards;}
  @keyframes donutFill{ to{stroke-dashoffset:20;} }
  @keyframes kpPctPop{ 0%{opacity:0; transform:scale(.6);} 60%{opacity:1; transform:scale(1.12);} 100%{opacity:1; transform:scale(1);} }
  .kelprofil-pct{animation:kpPctPop .6s ease-out .9s backwards; transform-origin:50% 50%;}

  @media (prefers-reduced-motion: reduce){
    .mc-deco.d1, .mc-deco.d2, .chip-pill, .mc-highlight-emoji, .mc-sparkle, .kelprofil-donut svg circle.kp-prog, .kelprofil-pct{animation:none !important;}
  }

  /* ---- global floating teddy mascot (visible on every page) ---- */
  .global-teddy-mascot{position:fixed; bottom:22px; right:22px; width:76px; height:76px; border-radius:50%; background:linear-gradient(150deg,#efe6fb,#f9f1fd); box-shadow:var(--shadow); display:flex; align-items:center; justify-content:center; z-index:400; cursor:default; animation:gtmFloat 3.6s ease-in-out infinite;}
  .global-teddy-mascot:hover{animation-play-state:paused;}
  .gtm-sparkle{position:absolute; font-size:13px; pointer-events:none; animation:sparkleTwinkle 2.3s ease-in-out infinite;}
  .gtm-sparkle.g1{top:2px; left:-6px; animation-delay:.3s;}
  .gtm-sparkle.g2{bottom:8px; left:-12px; font-size:9px; animation-delay:1.2s;}
  .gtm-bear{width:58px; height:58px; overflow:visible;}
  .gtm-arm{transform-box:fill-box; transform-origin:22% 85%; animation:teddyWave 1.4s ease-in-out infinite;}
  @keyframes teddyWave{ 0%,100%{transform:rotate(0deg);} 25%{transform:rotate(-24deg);} 50%{transform:rotate(6deg);} 75%{transform:rotate(-18deg);} }
  @keyframes gtmFloat{ 0%,100%{transform:translateY(0);} 50%{transform:translateY(-6px);} }
  @media (max-width:900px){ .global-teddy-mascot{width:58px; height:58px; bottom:14px; right:14px;} .gtm-bear{width:44px; height:44px;} }
  @media (prefers-reduced-motion: reduce){
    .global-teddy-mascot, .gtm-sparkle, .gtm-arm{animation:none !important;}
  }

  /* bottom row: statistik / aktivitas / preferensi / badge */
  .profil-bottom-row{display:grid; grid-template-columns:1fr 1.35fr 1fr 1.2fr; gap:16px; margin-top:16px; align-items:start;}
  .mini-stat-grid{display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-top:2px;}
  .mini-stat-tile{border-radius:16px; padding:11px 10px; display:flex; flex-direction:column; gap:7px;}
  .mini-stat-tile .mst-icon{width:28px; height:28px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:13px;}
  .mini-stat-tile .mst-num{font-family:'Baloo 2',sans-serif; font-weight:800; font-size:17px; color:var(--ink); line-height:1;}
  .mini-stat-tile .mst-lbl{font-size:9.5px; font-weight:800; color:var(--ink-soft);}
  .mst-blue{background:#eaf2fd;} .mst-blue .mst-icon{background:#cfe3fb; color:#3c66a8;}
  .mst-yellow{background:#fff6e0;} .mst-yellow .mst-icon{background:#ffe9be; color:#b5791b;}
  .mst-purple{background:#f3eefc;} .mst-purple .mst-icon{background:#e3d6f9; color:#7c5fce;}
  .mst-pink{background:#fdeaf1;} .mst-pink .mst-icon{background:#fbd3e3; color:#c94f81;}

  .pref-row{display:flex; align-items:center; gap:10px; padding:10px 2px; border-bottom:1px dashed #eee2cd; cursor:pointer;}
  .pref-row:last-child{border-bottom:none;}
  .pref-row .pref-icon{width:28px; height:28px; border-radius:9px; background:#f3eefc; display:flex; align-items:center; justify-content:center; color:#7c5fce; flex:0 0 28px;}
  .pref-row .pref-icon svg{width:14px; height:14px;}
  .pref-row .pref-label{flex:1; font-size:12px; font-weight:800; color:var(--ink);}
  .pref-row .chev{color:var(--ink-soft); width:14px; height:14px;}

  .achv-grid{display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-top:2px;}
  .achv-tile{display:flex; align-items:center; gap:8px; border-radius:14px; padding:9px 10px;}
  .achv-tile .achv-icon{width:26px; height:26px; border-radius:9px; display:flex; align-items:center; justify-content:center; font-size:13px; flex:0 0 26px;}
  .achv-tile .achv-label{font-size:10px; font-weight:800; color:var(--ink); line-height:1.25;}
  .achv-gold{background:#fff6e0;} .achv-gold .achv-icon{background:#ffe9be;}
  .achv-blue{background:#eaf2fd;} .achv-blue .achv-icon{background:#cfe3fb;}
  .achv-green{background:#eafcf0;} .achv-green .achv-icon{background:#c9f2d7;}
  .achv-pink{background:#fdeaf1;} .achv-pink .achv-icon{background:#fbd3e3;}

  @media (max-width: 1180px){
    .profil-bottom-row{grid-template-columns:1fr 1fr;}
  }
  @media (max-width: 860px){
    .profil-grid, .profil-toprow{grid-template-columns:1fr;}
    .status-mini-row{grid-template-columns:1fr;}
    .profil-bottom-row{grid-template-columns:1fr;}
  }
  .file-drop{border:2px dashed #e3d6f7; border-radius:16px; padding:18px; text-align:center; background:#fbf8ff; cursor:pointer;}
  .file-drop svg{width:22px; height:22px; color:#a98ee8; margin-bottom:4px;}
  .file-drop .fd-title{font-size:12px; font-weight:800; color:var(--ink);}
  .file-drop .fd-sub{font-size:10.5px; font-weight:700; color:var(--ink-soft); margin-top:2px;}
  .day-count-pill{display:inline-flex; align-items:center; gap:6px; background:#fff3c9; color:#a97a13; font-weight:800; font-size:11.5px; padding:6px 13px; border-radius:999px; margin-top:2px;}

  /* ================= HALAMAN: PENGAJUAN CUTI ================= */
  .cuti-hero-wrap{position:relative;}
  .cuti-hero-deco{position:absolute; top:-6px; right:6px; font-size:26px;}
  .status-menunggu{background:#fff3c9; color:#a97a13;}
  .status-ditolak{background:#ffd9de; color:#c9455f;}
  .status-disetujui-cuti{background:#d3f5df; color:#2f9d61;}
  .status-dikembalikan{background:#dbeafe; color:#2563a8;}
  .chip-tahunan{background:#dceafd; color:#1e40af;}
  .chip-sakit{background:#ffd9de; color:#c9455f;}
  .chip-melahirkan{background:#fce0ee; color:#9d174d;}
  .chip-penting{background:#ece2fb; color:#5b21b6;}
  .chip-lain-cuti{background:#eceff5; color:#7c8496;}
  .cuti-emp-cell{display:flex; align-items:center; gap:9px;}
  .cuti-emp-avatar{width:32px; height:32px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:14px; flex:0 0 32px; background:linear-gradient(135deg,var(--blue),var(--lavender)); box-shadow:0 3px 8px rgba(150,130,180,.12);}
  .cuti-emp-name{font-weight:800; font-size:12.5px; color:var(--ink);}
  .cuti-emp-role{font-size:10.5px; font-weight:700; color:var(--ink-soft);}
  .cuti-daterange{font-weight:700; font-size:12px;}
  .cuti-daterange .arrow{color:var(--ink-soft); margin:0 3px;}
  .cuti-days-pill{background:#eef5ff; color:#3c66a8; font-weight:800; font-size:11px; padding:4px 10px; border-radius:999px; display:inline-block;}
  .cuti-reason{font-size:12px; color:var(--ink-soft); font-weight:700; max-width:190px; display:block; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;}
  .reject-btn{border:none; cursor:pointer; font-family:inherit; font-weight:800; font-size:11px; padding:7px 12px; border-radius:999px; background:#ffd9de; color:#c9455f;}
  .cuti-quota-list{display:flex; flex-direction:column; gap:12px; margin-top:4px;}
  .cuti-quota-row{display:flex; align-items:center; gap:10px;}
  .cuti-quota-icon{width:30px; height:30px; border-radius:11px; display:flex; align-items:center; justify-content:center; font-size:14px; flex:0 0 30px;}
  .cuti-quota-info{flex:1;}
  .cuti-quota-info .name{font-size:12px; font-weight:800; color:var(--ink);}
  .cuti-quota-bar{height:8px; border-radius:999px; background:#f2ecdd; margin-top:5px; overflow:hidden;}
  .cuti-quota-bar span{display:block; height:100%; border-radius:999px;}
  .cuti-quota-val{font-size:11px; font-weight:800; color:var(--ink-soft); white-space:nowrap;}
  .row-highlight-new{animation:rowGlow 1.6s ease;}
  @keyframes rowGlow{0%{background:#fff3c9;} 100%{background:transparent;}}

  /* ---- Hero banner khas halaman Cuti (beda dari header polos halaman lain) ---- */
  .cuti-page-hero{
    position:relative; overflow:hidden; border-radius:28px; margin-bottom:6px;
    padding:24px 26px 26px;
    background:linear-gradient(120deg,#fde3ec 0%, #eaddfb 48%, #dceafd 100%);
    box-shadow:var(--shadow);
  }
  .cuti-page-hero .cph-float{position:absolute; font-size:54px; opacity:.22; pointer-events:none; filter:blur(.2px);}
  .cuti-page-hero .cph-float.f1{top:-14px; right:18px; font-size:74px; transform:rotate(8deg);}
  .cuti-page-hero .cph-float.f2{bottom:-18px; right:120px; font-size:44px;}
  .cuti-page-hero .cph-float.f3{top:10px; left:-10px; font-size:60px; opacity:.16;}
  .cuti-page-hero .breadcrumb{color:#8a5fce; opacity:.85;}
  .cuti-page-hero .breadcrumb .current{color:#c4507e;}
  .cuti-page-hero .page-title-row{margin-bottom:4px;}
  .cuti-page-hero .page-title{color:#5b3e78;}
  .cuti-page-hero .page-subtitle{margin-bottom:0; color:#8a6ba0;}
  .cuti-hero-pillrow{display:flex; gap:8px; flex-wrap:wrap; margin-top:14px; position:relative; z-index:1;}
  .cuti-hero-pill{display:inline-flex; align-items:center; gap:6px; background:rgba(255,255,255,.72); backdrop-filter:blur(2px); font-weight:800; font-size:11px; color:#7c5fce; padding:6px 13px; border-radius:999px; box-shadow:0 4px 10px rgba(150,130,180,.12);}

  /* ---- Kartu daftar pengajuan cuti (beda dari tabel log audit) ---- */
  .cuti-card-grid{display:flex; flex-direction:column; gap:13px;}
  .cuti-leave-card{
    background:var(--white); border-radius:22px; padding:16px 18px;
    box-shadow:var(--shadow); display:flex; align-items:center; gap:16px; flex-wrap:wrap;
    border-left:7px solid #d8d2ea; position:relative; transition:transform .15s, box-shadow .15s;
  }
  .cuti-leave-card:hover{transform:translateY(-2px); box-shadow:0 14px 30px rgba(150,130,180,.2);}
  .cuti-leave-card.type-tahunan{border-left-color:#5fa3e8;}
  .cuti-leave-card.type-sakit{border-left-color:#ef85b0;}
  .cuti-leave-card.type-penting{border-left-color:#a98ee8;}
  .cuti-leave-card.type-melahirkan{border-left-color:#ef85b0;}
  .cuti-leave-card.type-lainnya{border-left-color:#c8b98a;}
  .clc-person{display:flex; align-items:center; gap:10px; flex:0 0 188px;}
  .clc-avatar{width:42px; height:42px; border-radius:16px; display:flex; align-items:center; justify-content:center; font-size:18px; flex:0 0 42px; background:linear-gradient(135deg,var(--blue),var(--lavender)); box-shadow:0 3px 8px rgba(150,130,180,.15);}
  .clc-mid{flex:1 1 260px; display:flex; flex-direction:column; gap:7px; min-width:200px;}
  .clc-meta-row{display:flex; align-items:center; gap:8px; flex-wrap:wrap;}
  .clc-reason{font-size:12px; color:var(--ink-soft); font-weight:700;}
  .clc-right{display:flex; flex-direction:column; align-items:flex-end; gap:9px; flex:0 0 auto; margin-left:auto;}
  .cuti-card-grid .cuti-reason{max-width:none; white-space:normal;}

  /* ================= HALAMAN: AUDIT TRAIL LOG ================= */
  .audit-action-cell{display:flex; align-items:center; gap:9px;}
  .audit-action-icon{width:30px; height:30px; border-radius:11px; display:flex; align-items:center; justify-content:center; font-size:14px; flex:0 0 30px;}
  .audit-action-icon.create{background:#d3f5df; color:#2f9d61;}
  .audit-action-icon.update{background:#fff3c9; color:#a97a13;}
  .audit-action-icon.delete{background:#ffd9de; color:#c9455f;}
  .audit-action-icon.login{background:#dceafd; color:#3d72c4;}
  .audit-action-icon.export{background:#ece2fb; color:#7c5fce;}
  .audit-action-title{font-weight:800; font-size:12.5px; color:var(--ink);}
  .audit-action-module{font-size:10.5px; font-weight:700; color:var(--ink-soft);}
  .audit-user-cell{display:flex; align-items:center; gap:9px;}
  .audit-user-avatar{width:30px; height:30px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:13px; flex:0 0 30px; background:linear-gradient(135deg,var(--blue),var(--lavender));}
  .audit-time{font-size:12px; font-weight:800; color:var(--ink);}
  .audit-time .date{display:block; font-size:10.5px; font-weight:700; color:var(--ink-soft); margin-top:1px;}
  .audit-ip{font-family:'Nunito',monospace; font-size:11px; font-weight:700; color:var(--ink-soft); background:#f4eee0; padding:3px 9px; border-radius:8px;}
  .status-berhasil{background:#d3f5df; color:#2f9d61;}
  .status-gagal{background:#ffd9de; color:#c9455f;}
  .audit-detail-row{display:none;}
  .audit-detail-row.show{display:table-row;}
  .audit-detail-box{background:#faf6ff; border-radius:16px; padding:14px 18px; margin:6px 0; font-size:11.5px; font-weight:700; color:var(--ink-soft); display:flex; flex-direction:column; gap:6px;}
  .audit-detail-box .diff-old{color:#c9455f; text-decoration:line-through; margin-right:6px;}
  .audit-detail-box .diff-new{color:#2f9d61; font-weight:800;}
  .audit-toggle-btn{background:none; border:none; cursor:pointer; color:#8a5fce; font-weight:800; font-size:11px; font-family:inherit; display:flex; align-items:center; gap:4px;}
  .audit-toggle-btn svg{width:12px; height:12px; transition:transform .2s;}
  .audit-toggle-btn.open svg{transform:rotate(180deg);}
  .audit-live-dot{width:8px; height:8px; border-radius:50%; background:#2f9d61; display:inline-block; box-shadow:0 0 0 0 rgba(47,157,97,.5); animation:pulseDot 1.6s infinite;}
  @keyframes pulseDot{0%{box-shadow:0 0 0 0 rgba(47,157,97,.45);} 70%{box-shadow:0 0 0 7px rgba(47,157,97,0);} 100%{box-shadow:0 0 0 0 rgba(47,157,97,0);}}


  /* ---- Dark Mode: refined component overrides (auto-tuned from brand palette) ---- */
  html.dark-mode ::selection{background:#1a2c46; color:#b6a3ec;}
  html.dark-mode ::-webkit-scrollbar{width:9px; height:9px;}
  html.dark-mode ::-webkit-scrollbar-track{background:var(--cream);}
  html.dark-mode ::-webkit-scrollbar-thumb{background:#1a2c46; border-radius:8px;}
  html.dark-mode ::-webkit-scrollbar-thumb:hover{background:#1a2c46;}
  html.dark-mode .modal-overlay{background:rgba(6,8,18,.62);}
  html.dark-mode .cuti-hero-pill{background:rgba(36,42,61,.72); color:#c9b6f2;}
  html.dark-mode .tm-wrap .reminder-list{scrollbar-color:#1a2c46 transparent;}
  html.dark-mode .tm-wrap .reminder-list::-webkit-scrollbar-thumb{background:#1a2c46;}
  html.dark-mode .tm-wrap .reminder-list::-webkit-scrollbar-thumb:hover{background:#8fb2e6;}
  html.dark-mode img{filter:brightness(.92) contrast(1.02);}
  html.dark-mode .panel, html.dark-mode .form-control, html.dark-mode .search-pill,
  html.dark-mode .toolbar input[type=text], html.dark-mode .toolbar select, html.dark-mode .toolbar input[type=date],
  html.dark-mode .icon-chip, html.dark-mode .avatar-chip, html.dark-mode .sidebar-user{
    border:1px solid rgba(255,255,255,.05);
  }
  html.dark-mode ::placeholder{color:var(--ink-soft); opacity:.8;}
  html{transition:background-color .25s ease;}
  html.dark-mode .panel, html.dark-mode .modal-box, html.dark-mode .sidebar-user, html.dark-mode .search-pill,
  html.dark-mode .icon-chip, html.dark-mode .avatar-chip{
    transition:background-color .25s ease, border-color .25s ease, color .25s ease;
  }
html.dark-mode body { background: radial-gradient(circle at 10% 10%, rgba(60,90,140,.18) 0%, transparent 45%),
      radial-gradient(circle at 92% 6%, rgba(110,90,160,.14) 0%, transparent 45%),
      radial-gradient(circle at 85% 95%, rgba(90,70,120,.10) 0%, transparent 40%),
      var(--cream); }
html.dark-mode .sidebar { border-right: 1px solid rgba(255,255,255,.08); }
html.dark-mode .brand-sub { color: #b6a3ec; background: linear-gradient(135deg,#1e1a33,#3a2338); }
html.dark-mode .nav-item:hover { background: #232636; }
html.dark-mode .nav-item.active { background: linear-gradient(135deg,var(--white), #1e1a33); color: #b6a3ec; }
html.dark-mode .nav-item.active.masuk-active { background: linear-gradient(135deg,var(--white), #301c30); }
html.dark-mode .search-pill:focus-within svg { color: #b6a3ec; }
html.dark-mode .breadcrumb .current { color: #b6a3ec; }
html.dark-mode .toolbar input[type=text], html.dark-mode .toolbar select, html.dark-mode .toolbar input[type=date] { border: 1.5px solid rgba(255,255,255,.08); }
html.dark-mode .btn-export.excel { background: #301c30; color: #dd8fb5; }
html.dark-mode .btn-export.pdf { background: #2f2814; color: #d9ac5e; }
html.dark-mode .panel-footer { border-top: 1px dashed rgba(255,255,255,.10); }
html.dark-mode .mini-progress-bar { background: #1f2233; }
html.dark-mode .donut-wrap-stack .legend-row { border-bottom: 1px solid rgba(255,255,255,.08); }
html.dark-mode .trend-cloud.lav { color: #b6a3ec; }
html.dark-mode .barchart-bars { border-left: 1.5px solid rgba(255,255,255,.08); border-bottom: 1.5px solid rgba(255,255,255,.08); }
html.dark-mode .trend-summary { border-top: 1px dashed rgba(255,255,255,.10); }
html.dark-mode .stat-blue { background: linear-gradient(155deg,#142238,#142238); color: #8fb2e6; }
html.dark-mode .stat-yellow { background: linear-gradient(155deg,#332c16,#3a3119); color: #d9ac5e; }
html.dark-mode .stat-pink { background: linear-gradient(155deg,#351720,#351720); color: #dd8fb5; }
html.dark-mode .stat-green { background: linear-gradient(155deg,#17402d,#123324); color: #83d9a6; }
html.dark-mode thead tr { background: linear-gradient(120deg,#1e1a33,#142238); }
html.dark-mode thead th { color: #8fb2e6; }
html.dark-mode tbody td { border-bottom: 1px solid rgba(255,255,255,.08); }
html.dark-mode tbody tr:hover { background: #232636; }
html.dark-mode .num-link.sk { color: #b6a3ec; }
html.dark-mode .chip-blue { background: #142238; color: #8fb2e6; }
html.dark-mode .chip-red { background: #351720; color: #e08fa0; }
html.dark-mode .chip-grey { background: #142238; }
html.dark-mode .chip-dinas { background: #142238; color: #8fb2e6; }
html.dark-mode .chip-undangan { background: #332c16; color: #d9ac5e; }
html.dark-mode .chip-permohonan { background: #123324; color: #83d9a6; }
html.dark-mode .chip-eksternal { background: #3a2338; color: #dd8fb5; }
html.dark-mode .chip-lainnya { background: #1e1a33; color: #b6a3ec; }
html.dark-mode .status-proses { background: #123324; color: #83d9a6; }
html.dark-mode .status-baru { background: #142238; color: #8fb2e6; }
html.dark-mode .status-disposisi { background: #2f2814; color: #d9ac5e; }
html.dark-mode .status-selesai { background: #142238; color: #b6a3ec; }
html.dark-mode .status-pending { background: #142238; color: #8fb2e6; }
html.dark-mode .status-disetujui { background: #142238; color: #b6a3ec; }
html.dark-mode .status-terkirim { background: #301c30; color: #dd8fb5; }
html.dark-mode .status-diterima { background: #123324; color: #83d9a6; }
html.dark-mode .file-link { color: #8fb2e6; }
html.dark-mode .icon-btn { background: #1f2233; }
html.dark-mode .icon-btn.edit { background: #142238; color: #8fb2e6; }
html.dark-mode .icon-btn.del { background: #351720; color: #e08fa0; }
html.dark-mode .icon-btn.ok { background: #123324; color: #83d9a6; }
html.dark-mode .approve-btn { background: #123324; color: #83d9a6; }
html.dark-mode .shortcut-card:hover { border-color: #26213f; }
html.dark-mode .section-heading .link-all { color: #b6a3ec; }
html.dark-mode .activity-row { border-bottom: 1px solid rgba(255,255,255,.08); }
html.dark-mode #splash { background: radial-gradient(circle at 10% 10%, rgba(60,90,140,.18) 0%, transparent 45%),
      radial-gradient(circle at 92% 6%, rgba(110,90,160,.14) 0%, transparent 45%),
      radial-gradient(circle at 85% 95%, rgba(90,70,120,.10) 0%, transparent 40%),
      var(--cream); }
html.dark-mode .bs-teal { background: linear-gradient(155deg,#142238,#142238); color: #8fb2e6; }
html.dark-mode .bs-green { background: linear-gradient(155deg,#17402d,#123324); color: #83d9a6; }
html.dark-mode .bs-lavender { background: linear-gradient(155deg,#1e1a33,#26213f); color: #b6a3ec; }
html.dark-mode .bs-pink { background: linear-gradient(155deg,#3a2338,#3a2338); color: #dd8fb5; }
html.dark-mode .bs-gold { background: linear-gradient(155deg,#332c16,#3a3119); color: #d9ac5e; }
html.dark-mode .bs-red { background: linear-gradient(155deg,#351720,#351720); color: #dd8fb5; }
html.dark-mode .bs-lavender { background: linear-gradient(155deg,#1e1a33,#1e1a33); color: #b6a3ec; }
html.dark-mode .inv-hero { background: linear-gradient(120deg,#1e1a33,#301c30); }
html.dark-mode .inv-speech { background: var(--white); }
html.dark-mode .panel-menu-btn { background: #1f2233; }
html.dark-mode .invcat-count { background: #1f2233; }
html.dark-mode .sticky-note { background: #332c16; color: #d9ac5e; }
html.dark-mode .status-tersedia { background: #123324; color: #83d9a6; }
html.dark-mode .status-dipinjam { background: #332c16; color: #d9ac5e; }
html.dark-mode .status-rusak { background: #351720; color: #e08fa0; }
html.dark-mode .status-baik { background: #123324; color: #83d9a6; }
html.dark-mode .status-perlu { background: #332c16; color: #d9ac5e; }
html.dark-mode .asset-icon { background: #142238; }
html.dark-mode .op-table-wrap { border: 1.5px solid rgba(255,255,255,.08); }
html.dark-mode .op-table-wrap tbody td { border-bottom: 1px solid rgba(255,255,255,.08); }
html.dark-mode .op-table-scroll::-webkit-scrollbar-thumb { background: #3a3f5c; }
html.dark-mode .op-table-scroll::-webkit-scrollbar-thumb:hover { background: #4a5075; }
html.dark-mode .op-table-wrap.op-blue .op-table-scroll::-webkit-scrollbar-thumb { background: #3a4a6b; }
html.dark-mode .op-table-wrap.op-pink .op-table-scroll::-webkit-scrollbar-thumb { background: #4a3550; }
html.dark-mode .op-table-wrap.op-yellow .op-table-scroll::-webkit-scrollbar-thumb { background: #4a4230; }
html.dark-mode .chip-amber { background: #3a3119; color: #e6c07d; }
html.dark-mode .op-table-wrap.op-pink tbody tr:nth-child(even) { background: rgba(255,255,255,.035); }
html.dark-mode .op-table-wrap.op-pink tbody tr:hover { background: rgba(255,255,255,.06); }
html.dark-mode .op-table-wrap.op-blue tbody tr:nth-child(even) { background: rgba(255,255,255,.035); }
html.dark-mode .op-table-wrap.op-blue tbody tr:hover { background: rgba(255,255,255,.06); }
html.dark-mode .op-table-wrap.op-yellow tbody tr:nth-child(even) { background: rgba(255,255,255,.035); }
html.dark-mode .op-table-wrap.op-yellow tbody tr:hover { background: rgba(255,255,255,.06); }
html.dark-mode .op-table-wrap.op-pink thead th { background: linear-gradient(180deg,#231f34,#1d1a2c); color: #dd8fb5; }
html.dark-mode .op-table-wrap.op-blue thead th { background: linear-gradient(180deg,#1c2436,#171d2c); color: #8fb2e6; }
html.dark-mode .op-table-wrap.op-yellow thead th { background: linear-gradient(180deg,#26221b,#201d17); color: #d9ac5e; }
html.dark-mode .op-actbtn.yes { background: #123324; color: #83d9a6; }
html.dark-mode .op-actbtn.no { background: #351720; color: #e08fa0; }
html.dark-mode .op-range-toggle { background: rgba(255,255,255,.06); }
html.dark-mode .op-range-btn.active { background: #232849; color: #e8b855; box-shadow: 0 3px 10px rgba(0,0,0,.3); }
html.dark-mode .op-date-group td { background: rgba(255,255,255,.045) !important; color: #b9ac86; border-bottom: 1px solid rgba(255,255,255,.08) !important; }
html.dark-mode .op-date-count { color: #8b8265; }
html.dark-mode .op-row-conflict { background: rgba(233,76,97,.08) !important; }
html.dark-mode .op-row-conflict td { border-bottom-color: rgba(233,76,97,.25) !important; }
html.dark-mode .op-cardlist-datehead { color: #8b8265; }
html.dark-mode .op-cardlist-empty { background: rgba(255,255,255,.03); border-color: rgba(255,255,255,.08); }
html.dark-mode .op-cardlist-scroll::-webkit-scrollbar-thumb { background: #3a3f5c; }
html.dark-mode .op-event-card { box-shadow: none; }
html.dark-mode .op-event-yellow { background: linear-gradient(150deg,#26221b,#201d17); border-color: rgba(244,183,58,.25); }
html.dark-mode .op-event-yellow:hover { border-color: rgba(244,183,58,.5); }
html.dark-mode .op-event-time-yellow { background: linear-gradient(135deg,#4a3f1e,#5c4a1a); color: #f4d089; }
html.dark-mode .op-event-editbtn-yellow { background: #3a3119; color: #e6c07d; }
html.dark-mode .op-event-blue { background: linear-gradient(150deg,#1c2436,#171d2c); border-color: rgba(86,156,230,.25); }
html.dark-mode .op-event-blue:hover { border-color: rgba(86,156,230,.5); }
html.dark-mode .op-event-time-blue { background: linear-gradient(135deg,#1e3358,#234270); color: #a8cdf5; }
html.dark-mode .op-event-editbtn-blue { background: #142238; color: #8fb2e6; }
html.dark-mode .op-event-pink { background: linear-gradient(150deg,#231f34,#1d1a2c); border-color: rgba(238,128,172,.25); }
html.dark-mode .op-event-pink:hover { border-color: rgba(238,128,172,.5); }
html.dark-mode .op-event-time-pink { background: linear-gradient(135deg,#4a2138,#5c2846); color: #f0a8ce; }
html.dark-mode .op-event-editbtn-pink { background: #3a2338; color: #dd8fb5; }
html.dark-mode .op-event-conflict { border-color: rgba(239,92,114,.55) !important; }
html.dark-mode .op-event-meta-item { color: #8b96b8; }
html.dark-mode .op-event-title { color: #dfe4f5; }
html.dark-mode .op-table-wrap.op-red { border: 1.5px solid rgba(233,76,97,.3); }
html.dark-mode .op-table-wrap.op-red thead th { background: linear-gradient(180deg,#3a1c22,#2c161b); color: #ef95a3; }
html.dark-mode .op-table-wrap.op-red tbody tr:nth-child(even) { background: rgba(255,255,255,.035); }
html.dark-mode .op-table-wrap.op-red tbody tr:hover { background: rgba(255,255,255,.06); }
html.dark-mode .op-bentrok-aman { background: rgba(47,157,94,.12); border-color: rgba(47,157,94,.3); }
html.dark-mode .op-bentrok-aman-text b { color: #7bd9a0; }
html.dark-mode .op-bentrok-aman-text span { color: #6dc298; }
html.dark-mode .op-bentrok-card { background: linear-gradient(150deg,#2c1a1e,#26161a); border-color: rgba(239,92,114,.28); }
html.dark-mode .op-bentrok-card:hover { border-color: rgba(239,92,114,.5); }
html.dark-mode .op-bentrok-date { color: #ef95a3; }
html.dark-mode .op-bentrok-list::-webkit-scrollbar-thumb { background: #4a2a30; }
html.dark-mode .op-bentrok-chip.op-bentrok-rapat { background: #332c16; color: #d9ac5e; }
html.dark-mode .op-bentrok-chip.op-bentrok-agenda { background: #142238; color: #8fb2e6; }
html.dark-mode .op-bentrok-chip.op-bentrok-tamu { background: #2c1826; color: #e08fc0; }
html.dark-mode .qm-panel-head-text p { color: #9aa3c7; }
html.dark-mode .qm-tile-violet { background: linear-gradient(160deg,#2a2340,#221d38); }
html.dark-mode .qm-tile-violet .qm-icon { background: linear-gradient(135deg,#4a3d7a,#3a2f66); color: #cbb8f7; }
html.dark-mode .qm-tile-violet .qm-label { color: #cbb8f7; }
html.dark-mode .qm-tile-gold { background: linear-gradient(160deg,#332c16,#2a2412); }
html.dark-mode .qm-tile-gold .qm-icon { background: linear-gradient(135deg,#6b551f,#54430f); color: #e8c877; }
html.dark-mode .qm-tile-gold .qm-label { color: #e8c877; }
html.dark-mode .qm-tile-indigo { background: linear-gradient(160deg,#1c2340,#181d34); }
html.dark-mode .qm-tile-indigo .qm-icon { background: linear-gradient(135deg,#2f3f77,#25335f); color: #a9bdf2; }
html.dark-mode .qm-tile-indigo .qm-label { color: #a9bdf2; }
html.dark-mode .qm-tile-mint { background: linear-gradient(160deg,#17402d,#123324); }
html.dark-mode .qm-tile-mint .qm-icon { background: linear-gradient(135deg,#1f5c3f,#194a33); color: #83d9a6; }
html.dark-mode .qm-tile-mint .qm-label { color: #83d9a6; }
html.dark-mode .qm-tile-pink { background: linear-gradient(160deg,#3a2338,#311e2f); }
html.dark-mode .qm-tile-pink .qm-icon { background: linear-gradient(135deg,#6b3856,#552b45); color: #ef95a3; }
html.dark-mode .qm-tile-pink .qm-label { color: #ef95a3; }
html.dark-mode .qm-tile-red { background: linear-gradient(160deg,#3a2338,#311e2f); }
html.dark-mode .qm-tile-red .qm-icon { background: linear-gradient(135deg,#6b3838,#552b2b); color: #ef9d9d; }
html.dark-mode .qm-tile-red .qm-label { color: #ef9d9d; }
html.dark-mode .qm-tile:hover { box-shadow: 0 10px 20px rgba(0,0,0,.35); }
html.dark-mode .btn-audit { background: linear-gradient(135deg,#17402d,#123324); color: #83d9a6; }
html.dark-mode .btn-audit-icon { background: rgba(255,255,255,.08); }
html.dark-mode .btn-audit-arrow { background: #0e2a1c; color: #83d9a6; }
html.dark-mode .bigstat-card .bs-icon { background: rgba(255,255,255,.08); }
html.dark-mode .total-data-panel { background: linear-gradient(160deg,#17402d,#123324); }
html.dark-mode .total-data-panel svg circle:first-child { stroke: rgba(255,255,255,.08); }
html.dark-mode .total-data-panel circle[cx="80"][cy="80"][r="40"] { fill: #0e2a1c; }
html.dark-mode .total-data-panel text[y="94"] { fill: #9fd6b6; }
html.dark-mode .op-compare-panel { background: linear-gradient(165deg,#2a2140,#251d3a 55%,#301f2c); border-color: rgba(255,255,255,.08); }
html.dark-mode .opc-row-top b { color: #eafcf0; }
html.dark-mode .opc-bar-track { background: rgba(255,255,255,.08); }
html.dark-mode .opc-caption { background: linear-gradient(#241b38,#241b38) padding-box, linear-gradient(120deg,#ffd3e8,#d9c9f7,#c8f2d7) border-box; color: #d9c9f7; }
html.dark-mode .opc-pod-num, html.dark-mode .opc-pod-name { color: #eafcf0; }
html.dark-mode .opc-pod-floor { background: linear-gradient(90deg,transparent,rgba(255,255,255,.12),transparent); }
html.dark-mode .opc-caption-top { background: rgba(255,255,255,.08); border-color: rgba(255,255,255,.1); color: #cfd6f0; }
html.dark-mode .opc-row { background: rgba(255,255,255,.05); border-color: rgba(255,255,255,.08); }
html.dark-mode .opc-pod-num { background: rgba(255,255,255,.1); }
html.dark-mode .opc-pod-medal { background: rgba(255,255,255,.14); }
html.dark-mode .cal-month.active { background: #142238; color: #b6a3ec; }
html.dark-mode .cal-day:hover { background: #232636; }
html.dark-mode .cal-day.selected { color: #8fb2e6; }
html.dark-mode .agenda-card h3 { color: #b6a3ec; }
html.dark-mode .agenda-item { background: #1e1a33; }
html.dark-mode .agenda-item b { color: #b6a3ec; }
html.dark-mode .agenda-item[data-type="agenda"] { background: #1c2436; }
html.dark-mode .agenda-item[data-type="rapat"] { background: #26221b; }
html.dark-mode .agenda-item[data-type="tamu"] { background: #231f34; }
html.dark-mode .agenda-item[data-type="agenda"] .agenda-item-badge { background: #142238; color: #8fb2e6; }
html.dark-mode .agenda-item[data-type="rapat"] .agenda-item-badge { background: #3a3119; color: #e6c07d; }
html.dark-mode .agenda-item[data-type="tamu"] .agenda-item-badge { background: #3a2338; color: #dd8fb5; }
html.dark-mode .agenda-form { background: #1e1a33; border: 1.5px dashed #26213f; }
html.dark-mode .agenda-form input { border: 1.5px solid rgba(255,255,255,.08); }
html.dark-mode .agenda-form-cancel { background: #1f2233; }
html.dark-mode .cal-day.today.has-event::after, html.dark-mode .cal-day.selected.has-event::after { background: var(--white); }
html.dark-mode .todo-head h3 { color: #83d9a6; }
html.dark-mode .todo-item { border-bottom: 1px solid rgba(255,255,255,.08); }
html.dark-mode .todo-item span[contenteditable="true"]:hover { background: #232636; }
html.dark-mode .todo-item span[contenteditable="true"]:focus { background: #1e1a33; }
html.dark-mode .notes-card h3 { color: #d9ac5e; }
html.dark-mode .notes-card textarea { background: #1f2233; }
html.dark-mode .deadline-head { background: #1f2233; }
html.dark-mode .deadline-input-row input { border: 1.5px solid rgba(255,255,255,.08); }
html.dark-mode .deadline-list .todo-item { border-bottom: 1px dashed rgba(255,255,255,.10); }
html.dark-mode .footer-mid span:hover { color: #b6a3ec; }
html.dark-mode .astc-pink { background: #301c30; border: 1.5px solid #3a2338; }
html.dark-mode .astc-pink .astc-icon { color: #dd8fb5; }
html.dark-mode .astc-pink .astc-num, html.dark-mode .astc-pink .astc-link { color: #dd8fb5; }
html.dark-mode .astc-orange { background: #2f2814; }
html.dark-mode .astc-orange .astc-icon { color: #d9ac5e; }
html.dark-mode .astc-orange .astc-num, html.dark-mode .astc-orange .astc-link { color: #d9ac5e; }
html.dark-mode .astc-blue { background: #142238; border: 1.5px solid #1a2c46; }
html.dark-mode .astc-blue .astc-icon { color: #8fb2e6; }
html.dark-mode .astc-blue .astc-num, html.dark-mode .astc-blue .astc-link { color: #8fb2e6; }
html.dark-mode .astc-green { background: #17402d; }
html.dark-mode .astc-green .astc-icon { color: #83d9a6; }
html.dark-mode .astc-green .astc-num, html.dark-mode .astc-green .astc-link { color: #83d9a6; }
html.dark-mode .astc-cloud { background: #142238; border: 1.5px solid #1a2c46; }
html.dark-mode .astc-cloud .astc-icon { color: #8fb2e6; }
html.dark-mode .astc-cloud .astc-num, html.dark-mode .astc-cloud .astc-link { color: #8fb2e6; }
html.dark-mode .atk-panel-title .apt-icon { background: #142238; color: #8fb2e6; }
html.dark-mode .atk-pill { background: var(--white); border: 1.5px solid rgba(255,255,255,.08); }
html.dark-mode .atk-item-card { background: #1e1a33; border: 1.5px solid #26213f; }
html.dark-mode .atk-qty { border: 1.5px solid #26213f; background: var(--white); }
html.dark-mode .atk-qty button { background: var(--white); }
html.dark-mode .atk-special-banner { background: linear-gradient(120deg,#301c30,#301c30); border: 1.5px solid #3a2338; }
html.dark-mode .atk-special-left .asl-title { color: #dd8fb5; }
html.dark-mode .atk-btn-special { background: var(--white); color: #dd8fb5; }
html.dark-mode .atk-feature-card .afc-link { color: #b6a3ec; }
html.dark-mode .atk-rank-row { border-bottom: 1px dashed rgba(255,255,255,.10); }
html.dark-mode .atk-rank-num { background: #3d341b; color: #d9ac5e; }
html.dark-mode .atk-hist-row { border-bottom: 1px dashed rgba(255,255,255,.10); }
html.dark-mode .atk-hist-code { color: #dd8fb5; }
html.dark-mode .status-atk-disetujui { background: #123324; color: #83d9a6; }
html.dark-mode .status-atk-menunggu { background: #332c16; color: #d9ac5e; }
html.dark-mode .status-atk-selesai { background: #142238; color: #8fb2e6; }
html.dark-mode .status-atk-ditolak { background: #351720; color: #e08fa0; }
html.dark-mode .atk-info-row .air-icon { background: #1e1a33; color: #b6a3ec; }
html.dark-mode .atk-info-row .air-arrow { color: #b6a3ec; }
html.dark-mode .doc-hero { background: linear-gradient(120deg,#2f2814,#2f2814); }
html.dark-mode .quickaction-btn { border: 1.5px solid rgba(255,255,255,.08); background: var(--white); }
html.dark-mode .quickaction-btn.primary { border-color: #26213f; background: #1e1a33; color: #b6a3ec; }
html.dark-mode .btn-outline { border: 1.5px solid #26213f; background: var(--white); color: #b6a3ec; }
html.dark-mode .storage-panel .storage-icon { background: #1e1a33; color: #b6a3ec; }
html.dark-mode .storage-bar-track { background: #1f2233; }
html.dark-mode .storage-upsell { background: #332c16; color: #d9ac5e; }
html.dark-mode .table-card.pink-head thead tr { background: linear-gradient(120deg,#301c30,#301c30); }
html.dark-mode .table-card.pink-head thead th { color: #dd8fb5; }
html.dark-mode .pg-btn { background: var(--white); }
html.dark-mode .pg-perpage { border: 1.5px solid rgba(255,255,255,.08); background: var(--white); }
html.dark-mode .cat-link-all { color: #b6a3ec; }
html.dark-mode .tm-wrap .hero { background: linear-gradient(120deg, #1e1a33 0%, #301c30 100%); }
html.dark-mode .tm-wrap .hero h1 { color: #b6a3ec; }
html.dark-mode .tm-wrap .hero p { color: #b6a3ec; }
html.dark-mode .tm-wrap .tm-stat-card.pink { background: linear-gradient(150deg, #301c30, #3a2338); color: #dd8fb5; }
html.dark-mode .tm-wrap .tm-stat-card.orange { background: linear-gradient(150deg, #2f2814, #3a3119); color: #d9ac5e; }
html.dark-mode .tm-wrap .tm-stat-card.green { background: linear-gradient(150deg, #17402d, #17402d); color: #83d9a6; }
html.dark-mode .tm-wrap .tm-stat-card.blue { background: linear-gradient(150deg, #142238, #142238); color: #8fb2e6; }
html.dark-mode .tm-wrap .tm-stat-card.purple { background: linear-gradient(150deg, #142238, #142238); color: #b6a3ec; }
html.dark-mode .tm-wrap .section-header h2 { color: #b6a3ec; }
html.dark-mode .tm-wrap .filter-btn { background: var(--white); color: #b6a3ec; }
html.dark-mode .tm-wrap .search-box { background: var(--white); }
html.dark-mode .tm-wrap .tm-table-card { background: var(--white); }
html.dark-mode .tm-wrap thead th { border-bottom: 2px solid #26213f; }
html.dark-mode .tm-wrap tbody td { border-bottom: 1px solid #26213f; }
html.dark-mode .tm-wrap .user-cell .av { background: linear-gradient(135deg,#142238,#3a2338); }
html.dark-mode .tm-wrap .action-btn.view { background: #142238; }
html.dark-mode .tm-wrap .action-btn.edit { background: #3d341b; color: #d9ac5e; }
html.dark-mode .tm-wrap .action-btn.del { background: #351720; }
html.dark-mode .tm-wrap .page-btn { color: #b6a3ec; background: #1e1a33; }
html.dark-mode .tm-wrap .rcard { background: var(--white); }
html.dark-mode .tm-wrap .reminder-clear-btn { background: #351720; }
html.dark-mode .tm-wrap .reminder-clear-btn:hover { background: #351720; }
html.dark-mode .tm-wrap .reminder-list::-webkit-scrollbar-thumb { background: #142238; }
html.dark-mode .tm-wrap .reminder-list::-webkit-scrollbar-thumb:hover { background: #142238; }
html.dark-mode #todoList::-webkit-scrollbar-thumb{ background: #1a2c46; }
html.dark-mode #todoList::-webkit-scrollbar-thumb:hover{ background: #8fb2e6; }
html.dark-mode #agendaList::-webkit-scrollbar-thumb{ background: #1a2c46; }
html.dark-mode #agendaList::-webkit-scrollbar-thumb:hover{ background: #8fb2e6; }
html.dark-mode .tm-wrap .reminder-item:hover { background: #1e1a33; }
html.dark-mode .tm-wrap .chk-box { border: 1.5px solid #1a2c46; background: var(--white); }
html.dark-mode .tm-wrap .reminder-del { background: #351720; }
html.dark-mode .tm-wrap .reminder-del:hover { background: #351720; }
html.dark-mode .tm-wrap .reminder-add-row { border-top: 1px dashed #1a2c46; }
html.dark-mode .tm-wrap .upcoming-widget { background: linear-gradient(160deg, #2f2814, #3a3119 55%, #3f2a19); }
html.dark-mode .tm-wrap .upcoming-name { color: #b6a3ec; }
html.dark-mode .tm-wrap .upcoming-role { color: #d9ac5e; }
html.dark-mode .tm-wrap .upcoming-time { color: #d9ac5e; }
html.dark-mode .tm-wrap .upcoming-countdown { color: #d9ac5e; background: #2f2814; }
html.dark-mode .stat-purple { background: linear-gradient(155deg,#1e1a33,#1e1a33); color: #b6a3ec; }
html.dark-mode .peg-gender-box.male { background: linear-gradient(155deg,#142238,#142238); }
html.dark-mode .peg-gender-box.female { background: linear-gradient(155deg,#351720,#351720); }
html.dark-mode .peg-gender-avatar { background: var(--white); }
html.dark-mode .peg-gender-label { color: #8fb2e6; }
html.dark-mode .peg-gender-box.female .peg-gender-label { color: #dd8fb5; }
html.dark-mode .peg-gender-num { color: #8fb2e6; }
html.dark-mode .peg-gender-box.female .peg-gender-num { color: #dd8fb5; }
html.dark-mode .peg-gender-pct { color: #8fb2e6; }
html.dark-mode .peg-gender-box.female .peg-gender-pct { color: #dd8fb5; }
html.dark-mode .peg-gender-bar { background: #2f2814; }
html.dark-mode .peg-status-icon.tetap { background: #142238; color: #b6a3ec; }
html.dark-mode .peg-status-icon.kontrak { background: #3d341b; color: #d9ac5e; }
html.dark-mode .peg-status-icon.magang { background: #142238; color: #8fb2e6; }
html.dark-mode .peg-status-icon.lainnya { background: #2f2814; }
html.dark-mode .peg-lihat-semua { background: #301c30; color: #dd8fb5; }
html.dark-mode .peg-list-row { border-bottom: 1px dashed rgba(255,255,255,.10); }
html.dark-mode .chip-tetap { background: #142238; color: #b6a3ec; }
html.dark-mode .chip-kontrak { background: #3d341b; color: #d9ac5e; }
html.dark-mode .chip-magang { background: #142238; color: #8fb2e6; }
html.dark-mode .chip-laki { background: #142238; color: #8fb2e6; }
html.dark-mode .chip-perempuan { background: #3a2338; color: #dd8fb5; }
html.dark-mode .peg-page-btn { color: #b6a3ec; }
html.dark-mode .modal-head { background: linear-gradient(135deg,#1e1a33,#142238); }
html.dark-mode .modal-head-icon svg { color: #b6a3ec; }
html.dark-mode .modal-close { background: var(--white); }
html.dark-mode .toggle-row { border-bottom: 1px solid rgba(255,255,255,.08); }
html.dark-mode .toggle-switch .slider { background: #232636; }
html.dark-mode .toggle-switch .slider::before { background: var(--white); }
html.dark-mode .option-row { border: 1.5px solid rgba(255,255,255,.08); }
html.dark-mode .option-row:hover { background: #232636; }
html.dark-mode .option-row.active { background: #1e1a33; }
html.dark-mode .form-control { border: 1.5px solid rgba(255,255,255,.08); }
html.dark-mode .btn-secondary { background: #1e1a33; color: #b6a3ec; }
html.dark-mode .profil-banner { background: linear-gradient(120deg,#142238 0%,#1e1a33 48%,#301c30 100%); }
html.dark-mode .avatar-cam-btn { background: var(--white); border: 2px solid #26213f; }
html.dark-mode .profil-info-row { border-bottom: 1px dashed rgba(255,255,255,.10); }
html.dark-mode .check-row svg { color: #83d9a6; }
html.dark-mode .mc-deco.mint1 { background: linear-gradient(160deg,var(--mint),#17402d); }
html.dark-mode .mc-deco.blue1 { background: linear-gradient(160deg,var(--blue),#142238); }
html.dark-mode .mc-deco.lav1 { background: linear-gradient(160deg,var(--lavender),#1e1a33); }
html.dark-mode .chip-pill.mint { background: #17402d; color: #83d9a6; }
html.dark-mode .chip-pill.blue { background: #142238; color: #8fb2e6; }
html.dark-mode .mc-highlight.warm { background: linear-gradient(120deg,#332c16,#3d341b); }
html.dark-mode .mc-highlight.cool { background: linear-gradient(120deg,#123324,#123324); }
html.dark-mode .global-teddy-mascot { background: linear-gradient(150deg,#1e1a33,#1e1a33); }
html.dark-mode .mst-blue { background: #142238; }
html.dark-mode .mst-blue .mst-icon { background: #142238; color: #8fb2e6; }
html.dark-mode .mst-yellow { background: #2f2814; }
html.dark-mode .mst-yellow .mst-icon { background: #2f2814; color: #d9ac5e; }
html.dark-mode .mst-purple { background: #1e1a33; }
html.dark-mode .mst-purple .mst-icon { background: #1e1a33; color: #b6a3ec; }
html.dark-mode .mst-pink { background: #301c30; }
html.dark-mode .mst-pink .mst-icon { background: #301c30; color: #dd8fb5; }
html.dark-mode .pref-row { border-bottom: 1px dashed rgba(255,255,255,.10); }
html.dark-mode .pref-row .pref-icon { background: #1e1a33; color: #b6a3ec; }
html.dark-mode .cursor-swatch{background:#26213f; color:#c9b6f2;}
html.dark-mode .achv-gold { background: #2f2814; }
html.dark-mode .achv-gold .achv-icon { background: #2f2814; }
html.dark-mode .achv-blue { background: #142238; }
html.dark-mode .achv-blue .achv-icon { background: #142238; }
html.dark-mode .achv-green { background: #17402d; }
html.dark-mode .achv-green .achv-icon { background: #17402d; }
html.dark-mode .achv-pink { background: #301c30; }
html.dark-mode .achv-pink .achv-icon { background: #301c30; }
html.dark-mode .file-drop { border: 2px dashed #26213f; background: #1e1a33; }
html.dark-mode .day-count-pill { background: #332c16; color: #d9ac5e; }
html.dark-mode .status-menunggu { background: #332c16; color: #d9ac5e; }
html.dark-mode .status-ditolak { background: #351720; color: #e08fa0; }
html.dark-mode .status-disetujui-cuti { background: #123324; color: #83d9a6; }
html.dark-mode .chip-tahunan { background: #142238; color: #8fb2e6; }
html.dark-mode .chip-sakit { background: #351720; color: #e08fa0; }
html.dark-mode .chip-melahirkan { background: #3a2338; color: #dd8fb5; }
html.dark-mode .chip-penting { background: #1e1a33; color: #b6a3ec; }
html.dark-mode .chip-lain-cuti { background: #142238; }
html.dark-mode .cuti-days-pill { background: #142238; color: #8fb2e6; }
html.dark-mode .reject-btn { background: #351720; color: #e08fa0; }
html.dark-mode .cuti-quota-bar { background: #1f2233; }
html.dark-mode .cuti-page-hero { background: linear-gradient(120deg,#301c30 0%, #1e1a33 48%, #142238 100%); }
html.dark-mode .cuti-page-hero .breadcrumb { color: #b6a3ec; }
html.dark-mode .cuti-page-hero .breadcrumb .current { color: #dd8fb5; }
html.dark-mode .cuti-page-hero .page-title { color: #b6a3ec; }
html.dark-mode .cuti-page-hero .page-subtitle { color: #b6a3ec; }
html.dark-mode .cuti-hero-pill { color: #b6a3ec; }
html.dark-mode .cuti-leave-card { border-left: 7px solid #26213f; }
html.dark-mode .audit-action-icon.create { background: #123324; color: #83d9a6; }
html.dark-mode .audit-action-icon.update { background: #332c16; color: #d9ac5e; }
html.dark-mode .audit-action-icon.delete { background: #351720; color: #e08fa0; }
html.dark-mode .audit-action-icon.login { background: #142238; color: #8fb2e6; }
html.dark-mode .audit-action-icon.export { background: #1e1a33; color: #b6a3ec; }
html.dark-mode .audit-ip { background: #1f2233; }
html.dark-mode .status-berhasil { background: #123324; color: #83d9a6; }
html.dark-mode .status-gagal { background: #351720; color: #e08fa0; }
html.dark-mode .audit-detail-box { background: #1e1a33; }
html.dark-mode .audit-detail-box .diff-old { color: #e08fa0; }
html.dark-mode .audit-detail-box .diff-new { color: #83d9a6; }
html.dark-mode .audit-toggle-btn { color: #b6a3ec; }

/* highlight sidebar-user saat berada di halaman profil (ditambahkan saat pemecahan multi-halaman) */
.sidebar-user.active-profile{
  box-shadow: 0 0 0 2px #a98ee8, var(--shadow);
}

/* ================= CUTE FLOATING BUDDY ================= */
.cute-buddy{position:fixed; right:26px; bottom:26px; width:76px; height:76px; z-index:150;}
.cute-buddy-float{cursor:pointer; animation:buddyFloat 3.4s ease-in-out infinite; filter:drop-shadow(0 10px 14px rgba(150,130,180,.35)); transition:transform .2s ease;}
.cute-buddy-float:hover{animation-play-state:paused; transform:scale(1.1) rotate(0deg) !important;}
@keyframes buddyFloat{0%,100%{transform:translateY(0) rotate(-4deg);} 50%{transform:translateY(-14px) rotate(4deg);}}
.cute-buddy-shadow{position:absolute; left:50%; bottom:-2px; width:34px; height:8px; background:rgba(140,120,180,.28); border-radius:50%; transform:translateX(-50%); animation:buddyShadow 3.4s ease-in-out infinite; z-index:-1;}
@keyframes buddyShadow{0%,100%{width:34px; opacity:.5;} 50%{width:20px; opacity:.25;}}
.cute-buddy-close{position:absolute; top:-4px; right:-4px; width:20px; height:20px; border-radius:50%; background:#fff; box-shadow:var(--shadow); display:flex; align-items:center; justify-content:center; cursor:pointer; color:var(--ink-soft); font-size:11px; font-weight:800; opacity:0; transition:opacity .2s ease; z-index:2;}
.cute-buddy:hover .cute-buddy-close{opacity:1;}
html.dark-mode .cute-buddy-close{background:#142238; color:#8fb2e6;}
@media (max-width:640px){ .cute-buddy{right:14px; bottom:14px; width:58px; height:58px;} }
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
      <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#6b5aa8" stroke-width="1.8">
        <path d="M12 3l9 4-9 4-9-4 9-4z"/><path d="M6 10.5v4c0 1.5 2.7 3 6 3s6-1.5 6-3v-4"/><path d="M21 7v6"/>
      </svg>
    </div>
  </div>
  <div class="splash-title">Sekretariat</div>
  <div class="splash-sub">Admin Panel</div>
  <div class="splash-dots"><span></span><span></span><span></span></div>
</div>

<div class="app">

  <!-- ============ SIDEBAR ============ -->
  <aside class="sidebar">
    <div class="brand">
      <div class="brand-badge">
        <span class="brand-sparkle b1">✨</span>
        <span class="brand-sparkle b2">⭐</span>
        <img src="assets/logo-sekretariat.jpg" alt="Logo Sekretariat" class="brand-badge-img">
      </div>
      <div>
        <div class="brand-name">Sekretariat <span class="heart">💗</span></div>
        <div class="brand-sub">ADMIN PANEL</div>
      </div>
    </div>

    <div class="nav-label">UTAMA</div>
    <div class="nav-item active" data-page="dashboard" onclick="goTo('dashboard')">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="9" rx="2"/><rect x="14" y="3" width="7" height="5" rx="2"/><rect x="14" y="12" width="7" height="9" rx="2"/><rect x="3" y="16" width="7" height="5" rx="2"/></svg>
      Dashboard
    </div>

    <div class="nav-label">PERSURATAN &amp; ARSIP</div>
    <div class="nav-item" data-page="surat_masuk" onclick="goTo('surat_masuk')">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16v16H4z"/><path d="M4 6l8 7 8-7"/></svg>
      Surat Masuk
      <?php if ($badgeSuratMasukHariIni > 0): ?><span class="badge"><?php echo $badgeSuratMasukHariIni; ?></span><?php endif; ?>
    </div>
    <div class="nav-item" data-page="surat_keluar" onclick="goTo('surat_keluar')">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><path d="M22 2l-7 20-4-9-9-4 20-7z"/></svg>
      Surat Keluar
      <?php if ($badgeSuratKeluarHariIni > 0): ?><span class="badge"><?php echo $badgeSuratKeluarHariIni; ?></span><?php endif; ?>
    </div>
    <div class="nav-item" data-page="arsip" onclick="goTo('arsip')">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 8v13H3V8"/><path d="M1 3h22v5H1z"/><path d="M10 12h4"/></svg>
      Arsip Surat
    </div>


    <div class="nav-label">AKTIVITAS &amp; ACARA</div>
    <div class="nav-item" data-page="agenda" onclick="goTo('agenda')">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
      Agenda
    </div>
    <div class="nav-item" data-page="rapat" onclick="goTo('rapat')">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
      Manajemen Rapat
    </div>
    <div class="nav-item" data-page="tamu" onclick="goTo('tamu')">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
      Buku Tamu
    </div>
    <div class="nav-item" data-page="perjanjian_kerjasama" onclick="goTo('perjanjian_kerjasama')">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><path d="M9 15l2 2 4-4"/></svg>
      Perjanjian Kerjasama
    </div>

    <div class="nav-label">PENGELOLAAN INTERNAL</div>
    <div class="nav-item" data-page="dokumen" onclick="goTo('dokumen')">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
      Manajemen Dokumen
    </div>
    <div class="nav-item" data-page="inventaris" onclick="goTo('inventaris')">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
      Inventaris &amp; Aset
    </div>
    <div class="nav-item" data-page="atk" onclick="goTo('atk')">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z"/></svg>
      Manajemen ATK
    </div>

    <div class="nav-label">SISTEM</div>
    <div class="nav-item" data-page="audit" onclick="goTo('audit')">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><polyline points="12 7 12 12 15.5 14"/></svg>
      Audit Trail Log
    </div>

    <div class="nav-label">BANTUAN</div>
    <div class="nav-item" data-page="panduan" onclick="goTo('panduan')">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
      Panduan Penggunaan
    </div>

    
    <div class="nav-item" onclick="confirmLogout()" style="color:#e46a9b;">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
      Keluar
    </div>

    <div class="sidebar-user" data-page="profil" onclick="goTo('profil')" style="cursor:pointer;">
      <div class="av" style="overflow:hidden;">
        <img alt="Foto profil" src="<?= htmlspecialchars($cuFotoUrl) ?>" style="<?= $cuFotoUrl ? 'width:100%; height:100%; object-fit:cover;' : 'display:none; width:100%; height:100%; object-fit:cover;' ?>">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#6b5aa8" stroke-width="2" style="<?= $cuFotoUrl ? 'display:none;' : '' ?>"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
      </div>
      <div>
        <div class="name"><?= htmlspecialchars($cuNama ?: 'Admin Sekretariat') ?></div>
        <div class="role"><?= htmlspecialchars($cuBio) ?></div>
      </div>
    </div>
  </aside>

  <!-- ============ MAIN ============ -->
  <main class="main">

    <div class="topbar">
      <div class="search-pill" id="globalSearchWrap" style="position:relative;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input type="text" id="globalSearchInput" placeholder="Cari surat, dokumen, agenda, tamu..." autocomplete="off" oninput="globalSearchOnInput()" onfocus="globalSearchOnInput()" onkeydown="globalSearchKeydown(event)">
        <div class="global-search-results" id="globalSearchResults"></div>
      </div>
      <div class="topbar-spacer"></div>
      <div class="icon-chip" onclick="openPrefListModal()" title="Preferensi">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
      </div>
      <div class="avatar-chip" onclick="goTo('profil')" style="cursor:pointer; overflow:hidden;">
        <img alt="Foto profil" src="<?= htmlspecialchars($cuFotoUrl) ?>" style="<?= $cuFotoUrl ? 'width:100%; height:100%; object-fit:cover;' : 'display:none; width:100%; height:100%; object-fit:cover;' ?>">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#4d5f8f" stroke-width="2" style="<?= $cuFotoUrl ? 'display:none;' : '' ?>"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
      </div>
    </div>

    <section class="page active" id="page-dashboard">
      <div class="breadcrumb">Sekretariat <span class="sep">/</span> <span class="current">Dashboard</span></div>
      <div class="page-title-row">
        <h1 class="page-title">Dashboard</h1>
      </div>

      <!-- ===== BIG STAT CARDS ===== -->
      <div class="bigstat-grid">
        <div class="bigstat-card bs-teal">
          <div class="bs-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16v16H4z"/><path d="M4 6l8 7 8-7"/></svg></div>
          <div class="bs-num" id="bsNumSuratMasuk"><?= $totalSuratMasukAktif; ?></div>
          <div class="bs-lbl">Surat Masuk Aktif</div>
          <div class="bs-sub">Surat belum diarsipkan</div>
          <div class="bs-more" onclick="goTo('surat_masuk')">Selengkapnya <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><path d="M9 8l4 4-4 4"/></svg></div>
        </div>
        <div class="bigstat-card bs-green">
          <div class="bs-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><path d="M22 2l-7 20-4-9-9-4 20-7z"/></svg></div>
          <div class="bs-num" id="bsNumSuratKeluar"><?= $totalSuratKeluarMenungguApproval; ?></div>
          <div class="bs-lbl">Persetujuan Surat Keluar</div>
          <div class="bs-sub">Butuh persetujuan</div>
          <div class="bs-more" onclick="goTo('surat_keluar')">Selengkapnya <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><path d="M9 8l4 4-4 4"/></svg></div>
        </div>
        <div class="bigstat-card bs-lavender">
          <div class="bs-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></div>
          <div class="bs-num" id="bsNumAgenda"><?= $totalAgendaAktif ?></div>
          <div class="bs-lbl">Agenda Hari ini</div>
          <div class="bs-more" onclick="goTo('agenda')">Selengkapnya <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><path d="M9 8l4 4-4 4"/></svg></div>
        </div>
        <div class="bigstat-card bs-pink">
          <div class="bs-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg></div>
          <div class="bs-num" id="bsNumTamu"><?= $totalTamuHariIni ?></div>
          <div class="bs-lbl">Kunjungan Tamu Hari Ini</div>
          <div class="bs-more" onclick="goTo('tamu')">Selengkapnya <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><path d="M9 8l4 4-4 4"/></svg></div>
        </div>
      </div>

      <!-- ===== OPERASIONAL HARIAN + SIDE COLUMN ===== -->
      <div class="dash-grid dash-grid-op" style="align-items:start;">
        <div class="op-panel">
          <div class="op-panel-head">
            <div class="op-panel-title">
              <span class="op-panel-icon">🗂️</span>
              <h2>Operasional Harian</h2>
            </div>
            <div class="op-range-toggle" role="tablist" aria-label="Rentang tampilan Operasional Harian">
              <button type="button" class="op-range-btn active" data-op-target="minggu" data-op-caption="<?= htmlspecialchars($opCaptionMinggu) ?>" onclick="idxSwitchOpRange('minggu')">📅 Minggu Ini</button>
              <button type="button" class="op-range-btn" data-op-target="bulan" data-op-caption="<?= htmlspecialchars($opCaptionBulan) ?>" onclick="idxSwitchOpRange('bulan')">🗓️ Bulan Ini</button>
            </div>
          </div>
          <div class="op-range-caption" id="opRangeCaption">
            <span class="op-range-caption-icon">✨</span>
            <span>Menampilkan jadwal <b><?= htmlspecialchars($opCaptionMinggu) ?></b> — direncanakan, bukan cuma hari ini.</span>
          </div>

<?= idxRenderPanelOperasional('minggu', 'minggu', $opDataMinggu, $opBentrokMinggu, $opIdBentrokMinggu, $todayStr, true) ?>
<?= idxRenderPanelOperasional('bulan', 'bulan', $opDataBulan, $opBentrokBulan, $opIdBentrokBulan, $todayStr, false) ?>
        </div>

        <div class="side-col">
          <div class="side-panel op-bentrok-panel">
            <div class="op-bentrok-head">
              <div class="op-bentrok-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
              </div>
              <div class="op-bentrok-head-text">
                <h3>Jadwal Bentrok</h3>
                <p>Jam saling tumpang tindih, cek &amp; atur ulang</p>
              </div>
            </div>
<?= idxRenderPanelBentrok('minggu', 'minggu', $opBentrokMinggu, true) ?>
<?= idxRenderPanelBentrok('bulan', 'bulan', $opBentrokBulan, false) ?>
          </div>

          <div class="op-cal-combo">
            <div class="calendar-card" id="calendarCard">
              <div class="cal-head">
                <svg onclick="changeCalYear(-1)" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
                <span id="calYear">2026</span>
                <svg onclick="changeCalYear(1)" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
              </div>
              <div class="cal-months" id="calMonths"></div>
              <div class="cal-dow"><span>SUN</span><span>MON</span><span>TUE</span><span>WED</span><span>THU</span><span>FRI</span><span>SAT</span></div>
              <div class="cal-days" id="calDays"></div>
            </div>

            <div class="agenda-card" id="agendaCard">
              <div class="agenda-head">
                <h3 id="agendaTitle">Jadwal untuk -</h3>
              </div>

              <div id="agendaList"></div>
            </div>
          </div>

          <div class="side-panel">
            <div class="qm-panel-head">
              <div class="qm-panel-head-icon">🌟</div>
              <div class="qm-panel-head-text">
                <h3>Pintasan Menu Cepat</h3>
                <p>Akses fitur utama dengan cepat dan mudah ✨</p>
              </div>
              <div class="qm-panel-head-decor">☁️</div>
            </div>
            <div class="quickmenu-grid">
              <div class="qm-tile qm-tile-violet" onclick="goTo('surat_masuk')">
                <span class="qm-tile-sparkle qm-tile-sparkle-1">✦</span>
                <span class="qm-tile-sparkle qm-tile-sparkle-2">✧</span>
                <div class="qm-icon-wrap"><div class="qm-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg></div></div>
                <div class="qm-label">Surat Masuk</div>
              </div>
              <div class="qm-tile qm-tile-gold" onclick="goTo('surat_keluar')">
                <span class="qm-tile-sparkle qm-tile-sparkle-1">✦</span>
                <span class="qm-tile-sparkle qm-tile-sparkle-2">✧</span>
                <div class="qm-icon-wrap"><div class="qm-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg></div></div>
                <div class="qm-label">Surat Keluar</div>
              </div>
              <div class="qm-tile qm-tile-indigo" onclick="goTo('tamu')">
                <span class="qm-tile-sparkle qm-tile-sparkle-1">✦</span>
                <span class="qm-tile-sparkle qm-tile-sparkle-2">✧</span>
                <div class="qm-icon-wrap"><div class="qm-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg></div></div>
                <div class="qm-label">Buku Tamu</div>
              </div>
              <div class="qm-tile qm-tile-mint" onclick="goTo('agenda')">
                <span class="qm-tile-sparkle qm-tile-sparkle-1">✦</span>
                <span class="qm-tile-sparkle qm-tile-sparkle-2">✧</span>
                <div class="qm-icon-wrap"><div class="qm-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></div></div>
                <div class="qm-label">Buat Agenda</div>
              </div>
              <div class="qm-tile qm-tile-pink" onclick="goTo('rapat')">
                <span class="qm-tile-sparkle qm-tile-sparkle-1">✦</span>
                <span class="qm-tile-sparkle qm-tile-sparkle-2">✧</span>
                <div class="qm-icon-wrap"><div class="qm-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg></div></div>
                <div class="qm-label">Manajemen Rapat</div>
              </div>
              <div class="qm-tile qm-tile-red" onclick="goTo('dokumen')">
                <span class="qm-tile-sparkle qm-tile-sparkle-1">✦</span>
                <span class="qm-tile-sparkle qm-tile-sparkle-2">✧</span>
                <div class="qm-icon-wrap"><div class="qm-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></div></div>
                <div class="qm-label">Manajemen Dokumen</div>
              </div>
            </div>
            <button class="btn-audit" onclick="goTo('audit')">
              <span class="btn-audit-icon">🔍</span>
              <span class="btn-audit-text"><b>Lihat Audit Trail Log</b><small>Pantau jejak aktivitas sistem dengan mudah</small></span>
              <span class="btn-audit-arrow">›</span>
            </button>
          </div>

          <div class="side-panel op-compare-panel" id="opCompareCard"
               data-rapat="<?= (int)$opCountRapatMinggu ?>"
               data-agenda="<?= (int)$opCountAgendaMinggu ?>"
               data-tamu="<?= (int)$opCountTamuMinggu ?>">
            <div class="opc-sparkle opc-sparkle-1">✦</div>
            <div class="opc-sparkle opc-sparkle-2">✦</div>
            <div class="opc-sparkle opc-sparkle-3">✧</div>
            <div class="opc-decor opc-decor-cloud1">☁️</div>
            <div class="opc-decor opc-decor-cloud2">☁️</div>
            <div class="opc-decor opc-decor-star">⭐</div>
            <h3>Perbandingan Operasional</h3>
            <p class="opc-caption-top">🗓️ Rapat &bull; Agenda &bull; Tamu — <?= htmlspecialchars($opCaptionMinggu) ?></p>

            <div class="opc-podium-wrap">
              <div class="opc-podium" id="opcPodium">
                <div class="opc-pod-col" data-key="rapat" id="opcPodRapat">
                  <div class="opc-pod-medal" id="opcMedalRapat">🥇</div>
                  <div class="opc-pod-avatar-wrap"><div class="opc-pod-avatar" style="background:#ffeaf3;">🤝</div></div>
                  <div class="opc-pod-num" id="opcPodNumRapat">0</div>
                  <div class="opc-pod-bar" id="opcPodBarRapat" style="background:linear-gradient(180deg,#ffc9de,#ff9dc0);"></div>
                  <div class="opc-pod-name">Rapat</div>
                </div>
                <div class="opc-pod-col" data-key="agenda" id="opcPodAgenda">
                  <div class="opc-pod-medal" id="opcMedalAgenda">🥈</div>
                  <div class="opc-pod-avatar-wrap"><div class="opc-pod-avatar" style="background:#e4f9ec;">🗓️</div></div>
                  <div class="opc-pod-num" id="opcPodNumAgenda">0</div>
                  <div class="opc-pod-bar" id="opcPodBarAgenda" style="background:linear-gradient(180deg,#c8f2d7,#8fdcae);"></div>
                  <div class="opc-pod-name">Agenda</div>
                </div>
                <div class="opc-pod-col" data-key="tamu" id="opcPodTamu">
                  <div class="opc-pod-medal" id="opcMedalTamu">🥉</div>
                  <div class="opc-pod-avatar-wrap"><div class="opc-pod-avatar" style="background:#eee8fc;">🎟️</div></div>
                  <div class="opc-pod-num" id="opcPodNumTamu">0</div>
                  <div class="opc-pod-bar" id="opcPodBarTamu" style="background:linear-gradient(180deg,#e2d6fb,#bba3ef);"></div>
                  <div class="opc-pod-name">Tamu</div>
                </div>
              </div>
              <div class="opc-pod-floor"></div>
            </div>

            <div class="opc-legend">
              <div class="opc-row">
                <span class="opc-icon" style="background:#ffeaf3;">🤝</span>
                <div class="opc-row-body">
                  <div class="opc-row-top"><span>Rapat</span><b id="opcNumRapat">0</b></div>
                  <div class="opc-bar-track"><div class="opc-bar-fill" id="opcBarRapat" style="background:linear-gradient(90deg,#ffc9de,#ff9dc0);"></div></div>
                </div>
                <span class="opc-animal">🐰</span>
              </div>
              <div class="opc-row">
                <span class="opc-icon" style="background:#e4f9ec;">🗓️</span>
                <div class="opc-row-body">
                  <div class="opc-row-top"><span>Agenda</span><b id="opcNumAgenda">0</b></div>
                  <div class="opc-bar-track"><div class="opc-bar-fill" id="opcBarAgenda" style="background:linear-gradient(90deg,#c8f2d7,#8fdcae);"></div></div>
                </div>
                <span class="opc-animal">🦕</span>
              </div>
              <div class="opc-row">
                <span class="opc-icon" style="background:#eee8fc;">🎟️</span>
                <div class="opc-row-body">
                  <div class="opc-row-top"><span>Tamu</span><b id="opcNumTamu">0</b></div>
                  <div class="opc-bar-track"><div class="opc-bar-fill" id="opcBarTamu" style="background:linear-gradient(90deg,#e2d6fb,#bba3ef);"></div></div>
                </div>
                <span class="opc-animal">🐻</span>
              </div>
            </div>

            <div class="opc-caption"><span class="opc-caption-star">⭐</span><span id="opcCaption">Menghitung minggu ini…</span></div>
          </div>
        </div>
      </div>

      <!-- ===== TUGAS DEADLINE & STATISTIK SURAT ===== -->
      <div class="bottom-grid" style="grid-template-columns:45% 1fr; justify-content:start;">
        <div class="chart-card" id="deadlineCard" style="display:flex; flex-direction:column; overflow:hidden;">
          <div class="deadline-head">
            <h3>Tugas Deadline</h3>
            <p>Otomatis dari rapat, agenda, surat & tamu yang deadline-nya dekat</p>
          </div>
          <div class="deadline-list" id="deadlineList">
            <?php if (empty($autoDeadlineItems)): ?>
              <div class="deadline-empty">Belum ada tugas deadline ✨</div>
            <?php else: ?>
              <?php $dlIdx = 0; foreach ($autoDeadlineItems as $dlItem): $dlIdx++; ?>
                <div class="todo-item" id="gsDeadlineItem-<?= $dlIdx ?>" style="justify-content:space-between;">
                  <span style="display:flex; align-items:center; gap:8px; min-width:0;">
                    <span style="flex:0 0 auto;"><?= $dlItem['icon'] ?></span>
                    <span style="overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"><?= htmlspecialchars($dlItem['text']) ?></span>
                  </span>
                  <span style="flex:0 0 auto; font-size:10.5px; font-weight:800; color:#c4507e; background:#fdedf3; padding:4px 9px; border-radius:999px; white-space:nowrap;"><?= htmlspecialchars($dlItem['due_label']) ?></span>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>

        <div class="chart-card" id="chartCardSurat" style="border:1.5px solid #ece3f7; box-shadow:0 14px 32px rgba(150,130,180,.22);">
          <div class="surat-chart-head">
            <h3>💌 Statistik Surat Mingguan</h3>
            <div class="surat-chart-sub">Ringkasan surat masuk &amp; keluar selama seminggu</div>
          </div>
          <div class="surat-chart-legend">
            <span class="surat-legend-chip masuk"><i style="background:#569ce6;"></i>Surat Masuk</span>
            <span class="surat-legend-chip keluar"><i style="background:#ee80ac;"></i>Surat Keluar</span>
          </div>
          <svg id="suratChartSvg" viewBox="0 0 600 210" width="100%" style="display:block; height:210px;">
            <defs>
              <linearGradient id="suratGradMasuk" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0%" stop-color="#9fcdf7"/>
                <stop offset="100%" stop-color="#569ce6"/>
              </linearGradient>
              <linearGradient id="suratGradKeluar" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0%" stop-color="#f8b9d3"/>
                <stop offset="100%" stop-color="#ee80ac"/>
              </linearGradient>
            </defs>
            <line id="suratAxisVLine" x1="22" y1="10" x2="22" y2="180" stroke="#efe4d0" stroke-width="1"/>
            <line id="suratAxisHLine" x1="22" y1="180" x2="600" y2="180" stroke="#efe4d0" stroke-width="1"/>
            <g font-family="Nunito" font-size="9" fill="#8b96b8" text-anchor="end">
              <text id="suratAxisL1" x="18" y="183">2</text><text id="suratAxisL2" x="18" y="149">6</text><text id="suratAxisL3" x="18" y="115">10</text><text id="suratAxisL4" x="18" y="81">14</text><text id="suratAxisL5" x="18" y="47">18</text>
            </g>
            <rect id="suratBarMasuk0" x="33" y="98" width="12" height="82" rx="4" fill="url(#suratGradMasuk)"/>
            <rect id="suratBarKeluar0" x="49" y="126" width="12" height="54" rx="4" fill="url(#suratGradKeluar)"/>
            <rect id="suratBarMasuk1" x="83" y="19" width="12" height="161" rx="4" fill="url(#suratGradMasuk)"/>
            <rect id="suratBarKeluar1" x="99" y="61" width="12" height="119" rx="4" fill="url(#suratGradKeluar)"/>
            <rect id="suratBarMasuk2" x="133" y="163" width="12" height="17" rx="4" fill="url(#suratGradMasuk)"/>
            <rect id="suratBarKeluar2" x="149" y="129" width="12" height="51" rx="4" fill="url(#suratGradKeluar)"/>
            <rect id="suratBarMasuk3" x="183" y="129" width="12" height="51" rx="4" fill="url(#suratGradMasuk)"/>
            <rect id="suratBarKeluar3" x="199" y="180" width="12" height="0" rx="4" fill="url(#suratGradKeluar)"/>
            <rect id="suratBarMasuk4" x="233" y="180" width="12" height="0" rx="4" fill="url(#suratGradMasuk)"/>
            <rect id="suratBarKeluar4" x="249" y="61" width="12" height="119" rx="4" fill="url(#suratGradKeluar)"/>
            <rect id="suratBarMasuk5" x="283" y="163" width="12" height="17" rx="4" fill="url(#suratGradMasuk)"/>
            <rect id="suratBarKeluar5" x="299" y="146" width="12" height="34" rx="4" fill="url(#suratGradKeluar)"/>
            <rect id="suratBarMasuk6" x="333" y="112" width="12" height="68" rx="4" fill="url(#suratGradMasuk)"/>
            <rect id="suratBarKeluar6" x="349" y="146" width="12" height="34" rx="4" fill="url(#suratGradKeluar)"/>
            <g id="suratValueLabels" font-family="Nunito" font-size="9.5" font-weight="800" text-anchor="middle"></g>
            <g font-family="Nunito" font-size="10" font-weight="800" fill="#8b96b8" text-anchor="middle">
              <text id="suratDayLabel0" x="47" y="196">Sen</text><text id="suratDayLabel1" x="97" y="196">Sel</text><text id="suratDayLabel2" x="147" y="196">Rab</text>
              <text id="suratDayLabel3" x="197" y="196">Kam</text><text id="suratDayLabel4" x="247" y="196">Jum</text><text id="suratDayLabel5" x="297" y="196">Sab</text><text id="suratDayLabel6" x="347" y="196">Min</text>
            </g>
          </svg>
        </div>
      </div>

      <!-- ===== TODO / NOTES ===== -->
      <div class="dash-grid" style="grid-template-columns:1fr 1fr; align-items:stretch;">
        <div class="todo-card" id="todoCard" style="align-self:start;">
          <div class="todo-head">
            <h3>To-Do List</h3>
            <button class="todo-add" onclick="addTodo()"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg></button>
          </div>
          <div id="todoList">
          </div>
        </div>

        <div class="notes-card" id="notesCard" style="align-self:start;">
          <h3>Catatan</h3>
          <textarea id="notesTextarea" placeholder="Tulis catatan di sini..."></textarea>
        </div>
      </div>

    </section>

  </main>
</div>

<div id="toast"></div>

<!-- ============ CUTE FLOATING BUDDY ============ -->
<div class="cute-buddy" id="cuteBuddy">
  <div class="cute-buddy-close" onclick="buddyDismiss(event)">&times;</div>
  <div class="cute-buddy-float" onclick="buddyPoke()" title="Semangat!">
    <div class="cute-buddy-shadow"></div>
    <svg viewBox="0 0 100 100" width="76" height="76" xmlns="http://www.w3.org/2000/svg">
      <defs>
        <linearGradient id="buddyGrad" x1="0" y1="0" x2="0.3" y2="1">
          <stop offset="0%" stop-color="#eee4fc"/>
          <stop offset="100%" stop-color="#bcd7f7"/>
        </linearGradient>
      </defs>
      <path d="M13 20l1.3 3.3 3.3 1.3-3.3 1.3-1.3 3.3-1.3-3.3-3.3-1.3 3.3-1.3z" fill="#ffe6a0" opacity=".9"/>
      <path d="M88 62l1 2.6 2.6 1-2.6 1-1 2.6-1-2.6-2.6-1 2.6-1z" fill="#f8c9dc" opacity=".9"/>
      <path d="M85 16l0.8 2 2 0.8-2 .8-.8 2-.8-2-2-.8 2-.8z" fill="#bdeccb" opacity=".9"/>
      <circle cx="35" cy="46" r="14" fill="url(#buddyGrad)"/>
      <circle cx="65" cy="46" r="14" fill="url(#buddyGrad)"/>
      <circle cx="50" cy="36" r="17" fill="url(#buddyGrad)"/>
      <rect x="21" y="46" width="58" height="26" rx="13" fill="url(#buddyGrad)"/>
      <path d="M35 32a14 14 0 0 0-14 14 13 13 0 0 0 .3 13A13 13 0 0 0 34 72h32a13 13 0 0 0 12.7-12.7A13 13 0 0 0 79 46a14 14 0 0 0-14-14 16.9 16.9 0 0 0-1.6.1 17 17 0 0 0-26.8 0A16.9 16.9 0 0 0 35 32Z"
        fill="none" stroke="#8a6fce" stroke-width="1.6" stroke-linejoin="round"/>
      <ellipse cx="38" cy="56" rx="3.6" ry="2.3" fill="#f8c9dc" opacity="0.85"/>
      <ellipse cx="62" cy="56" rx="3.6" ry="2.3" fill="#f8c9dc" opacity="0.85"/>
      <path d="M33 50q3.4-4.2 6.8 0" fill="none" stroke="#5c4a8a" stroke-width="2.1" stroke-linecap="round">
        <animate attributeName="d" values="M33 50q3.4-4.2 6.8 0;M33 51.5q3.4-0.6 6.8 0;M33 50q3.4-4.2 6.8 0" keyTimes="0;0.5;1" dur="3.6s" repeatCount="indefinite" begin="0.4s"/>
      </path>
      <path d="M60 50q3.4-4.2 6.8 0" fill="none" stroke="#5c4a8a" stroke-width="2.1" stroke-linecap="round">
        <animate attributeName="d" values="M60 50q3.4-4.2 6.8 0;M60 51.5q3.4-0.6 6.8 0;M60 50q3.4-4.2 6.8 0" keyTimes="0;0.5;1" dur="3.6s" repeatCount="indefinite" begin="0.4s"/>
      </path>
      <path d="M44 60q6 4.4 12 0" fill="none" stroke="#5c4a8a" stroke-width="2.1" stroke-linecap="round"/>
    </svg>
  </div>
</div>

<!-- ===== MODAL: DAFTAR PREFERENSI (akses cepat dari Dashboard) ===== -->
<div class="modal-overlay" id="prefListModalOverlay" onclick="if(event.target===this) closePrefListModal()">
  <div class="modal-box" style="max-width:380px;">
    <div class="modal-head">
      <div class="modal-head-icon">⚙️</div>
      <div>
        <div class="modal-title">Preferensi</div>
        <div class="modal-sub">Atur pengalaman akunmu</div>
      </div>
      <button class="modal-close" onclick="closePrefListModal()">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <div class="modal-body">
      <div class="pref-quick-list">
        <div class="pref-row" onclick="closePrefListModal(); openPrefModal('darkmode');">
          <div class="pref-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg></div>
          <div class="pref-label">Dark Mode</div>
          <svg class="chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
        </div>
        <div class="pref-row" onclick="closePrefListModal(); openPrefModal('tema');">
          <div class="pref-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="13.5" cy="6.5" r=".5"/><circle cx="17.5" cy="10.5" r=".5"/><circle cx="8.5" cy="7.5" r=".5"/><circle cx="6.5" cy="12.5" r=".5"/><path d="M12 2a10 10 0 1 0 10 10 4 4 0 0 1-5-5 4 4 0 0 1-5-5z"/></svg></div>
          <div class="pref-label">Tema</div>
          <svg class="chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
        </div>
        <div class="pref-row" onclick="closePrefListModal(); openPrefModal('sparkle');">
          <div class="pref-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3l1.7 5.3L19 10l-5.3 1.7L12 17l-1.7-5.3L5 10l5.3-1.7L12 3z"/></svg></div>
          <div class="pref-label">Efek Sparkle</div>
          <svg class="chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
        </div>
        <div class="pref-row" onclick="closePrefListModal(); openPrefModal('suara');">
          <div class="pref-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 5 6 9H2v6h4l5 4V5z"/><path d="M15.5 8.5a5 5 0 0 1 0 7"/><path d="M18.5 5.5a9 9 0 0 1 0 13"/></svg></div>
          <div class="pref-label">Efek Suara</div>
          <svg class="chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
        </div>
        <div class="pref-row" onclick="closePrefListModal(); openPrefModal('font');">
          <div class="pref-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="4 7 4 4 20 4 20 7"/><line x1="9" y1="20" x2="15" y2="20"/><line x1="12" y1="4" x2="12" y2="20"/></svg></div>
          <div class="pref-label">Gaya Teks</div>
          <svg class="chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
        </div>
        <div class="pref-row" onclick="closePrefListModal(); openPrefModal('cursor');">
          <div class="pref-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 3l7.07 16.97 2.51-7.39 7.39-2.51z"/></svg></div>
          <div class="pref-label">Gaya Kursor</div>
          <svg class="chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn-secondary" onclick="closePrefListModal()">Tutup</button>
    </div>
  </div>
</div>

<!-- ===== MODAL: DETAIL PREFERENSI (dark mode / tema / gaya kursor) ===== -->
<div class="modal-overlay" id="prefModalOverlay" onclick="if(event.target===this) closePrefModal()">
  <div class="modal-box" style="max-width:420px;">
    <div class="modal-head">
      <div class="modal-head-icon" id="prefModalIcon"></div>
      <div>
        <div class="modal-title" id="prefModalTitle">Preferensi</div>
        <div class="modal-sub" id="prefModalSub">Atur pengalaman akunmu</div>
      </div>
      <button class="modal-close" onclick="closePrefModal()">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <div class="modal-body" id="prefModalBody"></div>
    <div class="modal-footer">
      <button class="btn-secondary" onclick="closePrefModal()">Tutup</button>
    </div>
  </div>
</div>

<script>
// ====== Script Aplikasi (digabung inline) ======
// Fungsi & state bersama untuk semua halaman.

  // ---------- PREFERENSI (Dark Mode / Notifikasi / Bahasa / Tema) ----------
  const prefState = {
    darkMode: false,
    notif: { email: true, push: true, suara: false },
    tema: 'ungu',
    sparkle: true,
    sound: true,
    fontStyle: 'default',
    cursorStyle: 'default'
  };

  const temaPalettes = {
    ungu:  { lavender:'#dcd0f5', blue:'#bcd7f7', label:'Ungu Lembut',  swatch:'linear-gradient(135deg,#e6dbfc,#a98ee8)' },
    biru:  { lavender:'#bcd7f7', blue:'#a9c9f7', label:'Biru Langit',  swatch:'linear-gradient(135deg,#dbeafd,#5b93e0)' },
    pink:  { lavender:'#f8c9dc', blue:'#f4b8d0', label:'Pink Manis',   swatch:'linear-gradient(135deg,#fde1ec,#ef9dbf)' },
    mint:  { lavender:'#bdeccb', blue:'#a7e6c0', label:'Hijau Mint',   swatch:'linear-gradient(135deg,#ddf6e6,#4fbf82)' }
  };

  const fontStyles = {
    default: { body:"'Nunito',sans-serif",   heading:"'Baloo 2',sans-serif",    label:'Bulat Manis' },
    ceria:   { body:"'Fredoka',sans-serif",   heading:"'Fredoka',sans-serif",    label:'Ceria & Bulat' },
    komik:   { body:"'Comic Neue',cursive",   heading:"'Comic Neue',cursive",    label:'Komik Lucu' },
    tulisan: { body:"'Patrick Hand',cursive", heading:"'Patrick Hand',cursive",  label:'Tulisan Tangan' }
  };

  // Kursor custom dibuat dari SVG inline (data URI), sama seperti di profil.php,
  // jadi pilihan Gaya Kursor yang diatur di halaman Profil ikut kepakai di sini juga.
  function svgCursor(svgInner){
    const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24">${svgInner}</svg>`;
    return `url("data:image/svg+xml,${encodeURIComponent(svg)}") 2 2, pointer`;
  }
  const cursorStyles = {
    default: { css: 'auto', label: 'Bawaan Sistem', preview: '➤' },
    heart:   { css: svgCursor('<path fill="#ff6fa5" stroke="#ffffff" stroke-width="1" d="M12 21s-7-4.4-9.5-8.6C.4 8.4 3 4 7 4c2 0 3.6 1.1 5 3.1C13.4 5.1 15 4 17 4c4 0 6.6 4.4 4.5 8.4C19 16.6 12 21 12 21z"/>'), label: 'Hati Manis', preview: '💗' },
    star:    { css: svgCursor('<path fill="#a98ee8" stroke="#ffffff" stroke-width="1" d="M12 2l2.4 6.2 6.6.6-5 4.3 1.6 6.5L12 16.4 6.4 19.6 8 13.1 3 8.8l6.6-.6z"/>'), label: 'Bintang Kerlip', preview: '⭐' },
    paw:     { css: svgCursor('<circle cx="12" cy="15.5" r="5.2" fill="#f4c765"/><circle cx="5.2" cy="8.6" r="2.6" fill="#f4c765"/><circle cx="11.4" cy="4.6" r="2.8" fill="#f4c765"/><circle cx="17.8" cy="8.2" r="2.6" fill="#f4c765"/>'), label: 'Jejak Kaki', preview: '🐾' },
    flower:  { css: svgCursor('<circle cx="12" cy="6.4" r="3.3" fill="#ff9fc7"/><circle cx="17.4" cy="9.2" r="3.3" fill="#ff9fc7"/><circle cx="17.4" cy="14.8" r="3.3" fill="#ff9fc7"/><circle cx="12" cy="17.6" r="3.3" fill="#ff9fc7"/><circle cx="6.6" cy="14.8" r="3.3" fill="#ff9fc7"/><circle cx="6.6" cy="9.2" r="3.3" fill="#ff9fc7"/><circle cx="12" cy="12" r="3" fill="#ffcf6b" stroke="#ffffff" stroke-width="1"/>'), label: 'Bunga Mekar', preview: '🌸' },
    cloud:   { css: svgCursor('<path fill="#bfe3fb" stroke="#ffffff" stroke-width="1" d="M6.2 17a4 4 0 0 1 .2-8 5.2 5.2 0 0 1 9.9-1.6A4.6 4.6 0 0 1 16.8 17H6.2z"/>'), label: 'Awan Empuk', preview: '☁️' },
    donut:   { css: svgCursor('<circle cx="12" cy="12" r="8.2" fill="#e0a072" stroke="#ffffff" stroke-width="1"/><circle cx="12" cy="12" r="3.1" fill="#fffaf5"/><circle cx="8.1" cy="8.6" r="1.1" fill="#ff6fa5"/><circle cx="15.4" cy="7.8" r="1.1" fill="#a98ee8"/><circle cx="16.2" cy="14.4" r="1.1" fill="#4fbf82"/><circle cx="8.6" cy="15.8" r="1.1" fill="#f4c765"/>'), label: 'Donat Manis', preview: '🍩' }
  };

  function applyCursorStyle(){
    let styleTag = document.getElementById('prefCursorStyleTag');
    if(!styleTag){
      styleTag = document.createElement('style');
      styleTag.id = 'prefCursorStyleTag';
      document.head.appendChild(styleTag);
    }
    const c = cursorStyles[prefState.cursorStyle] || cursorStyles.default;
    styleTag.textContent = c.css === 'auto' ? '' : `*{cursor:${c.css} !important;}`;
  }

  function loadPrefState(){
    try{
      const raw = localStorage.getItem('bkjl_pref_state');
      if(raw){
        const parsed = JSON.parse(raw);
        Object.assign(prefState, parsed);
      }
    }catch(e){}
  }

  function savePrefState(){
    try{ localStorage.setItem('bkjl_pref_state', JSON.stringify(prefState)); }catch(e){}
  }

  function applyPrefState(){
    document.documentElement.classList.toggle('dark-mode', prefState.darkMode);
    const palette = temaPalettes[prefState.tema] || temaPalettes.ungu;
    document.documentElement.style.setProperty('--lavender', palette.lavender);
    document.documentElement.style.setProperty('--blue', palette.blue);
    const font = fontStyles[prefState.fontStyle] || fontStyles.default;
    document.documentElement.style.setProperty('--font-body', font.body);
    document.documentElement.style.setProperty('--font-heading', font.heading);
    applyCursorStyle();
  }

  function openPrefModal(type){
    const iconEl = document.getElementById('prefModalIcon');
    const titleEl = document.getElementById('prefModalTitle');
    const subEl = document.getElementById('prefModalSub');
    const bodyEl = document.getElementById('prefModalBody');

    if(type === 'darkmode'){
      iconEl.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>';
      titleEl.textContent = 'Dark Mode';
      subEl.textContent = 'Nyalakan tampilan gelap untuk mata yang lebih nyaman';
      bodyEl.innerHTML = `
        <div class="toggle-row">
          <div>
            <div class="toggle-row-label">Aktifkan Dark Mode</div>
            <div class="toggle-row-sub">Berlaku untuk semua halaman, tersimpan otomatis</div>
          </div>
          <label class="toggle-switch">
            <input type="checkbox" id="prefDarkModeToggle" ${prefState.darkMode ? 'checked' : ''} onchange="onToggleDarkMode(this.checked)">
            <span class="slider"></span>
          </label>
        </div>`;
    }

    if(type === 'notif'){
      iconEl.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>';
      titleEl.textContent = 'Notifikasi';
      subEl.textContent = 'Pilih notifikasi apa saja yang ingin kamu terima';
      bodyEl.innerHTML = `
        <div class="toggle-row">
          <div>
            <div class="toggle-row-label">Notifikasi Email</div>
            <div class="toggle-row-sub">Ringkasan aktivitas dikirim ke email</div>
          </div>
          <label class="toggle-switch">
            <input type="checkbox" ${prefState.notif.email ? 'checked' : ''} onchange="onToggleNotif('email', this.checked)">
            <span class="slider"></span>
          </label>
        </div>
        <div class="toggle-row">
          <div>
            <div class="toggle-row-label">Notifikasi Push</div>
            <div class="toggle-row-sub">Muncul di ikon lonceng saat ada aktivitas baru</div>
          </div>
          <label class="toggle-switch">
            <input type="checkbox" ${prefState.notif.push ? 'checked' : ''} onchange="onToggleNotif('push', this.checked)">
            <span class="slider"></span>
          </label>
        </div>
        <div class="toggle-row">
          <div>
            <div class="toggle-row-label">Suara Notifikasi</div>
            <div class="toggle-row-sub">Bunyikan suara saat ada notifikasi masuk</div>
          </div>
          <label class="toggle-switch">
            <input type="checkbox" ${prefState.notif.suara ? 'checked' : ''} onchange="onToggleNotif('suara', this.checked)">
            <span class="slider"></span>
          </label>
        </div>`;
    }

    if(type === 'tema'){
      iconEl.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="13.5" cy="6.5" r=".5"/><circle cx="17.5" cy="10.5" r=".5"/><circle cx="8.5" cy="7.5" r=".5"/><circle cx="6.5" cy="12.5" r=".5"/><path d="M12 2a10 10 0 1 0 10 10 4 4 0 0 1-5-5 4 4 0 0 1-5-5z"/></svg>';
      titleEl.textContent = 'Tema';
      subEl.textContent = 'Pilih palet warna aksen favoritmu';
      bodyEl.innerHTML = `<div class="option-list" id="temaOptionList"></div>`;
      renderTemaOptions();
    }

    if(type === 'sparkle'){
      iconEl.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3l1.7 5.3L19 10l-5.3 1.7L12 17l-1.7-5.3L5 10l5.3-1.7L12 3z"/></svg>';
      titleEl.textContent = 'Efek Sparkle';
      subEl.textContent = 'Kerlip kecil setiap kamu sentuh/klik layar';
      bodyEl.innerHTML = `
        <div class="toggle-row">
          <div>
            <div class="toggle-row-label">Aktifkan Efek Sparkle</div>
            <div class="toggle-row-sub">Muncul di mana aja saat kamu sentuh/klik, tersimpan otomatis</div>
          </div>
          <label class="toggle-switch">
            <input type="checkbox" ${prefState.sparkle ? 'checked' : ''} onchange="onToggleSparkle(this.checked)">
            <span class="slider"></span>
          </label>
        </div>`;
    }

    if(type === 'suara'){
      iconEl.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 5 6 9H2v6h4l5 4V5z"/><path d="M15.5 8.5a5 5 0 0 1 0 7"/><path d="M18.5 5.5a9 9 0 0 1 0 13"/></svg>';
      titleEl.textContent = 'Efek Suara';
      subEl.textContent = 'Bunyi klik/pop lembut tiap kamu sentuh tombol';
      bodyEl.innerHTML = `
        <div class="toggle-row">
          <div>
            <div class="toggle-row-label">Aktifkan Efek Suara</div>
            <div class="toggle-row-sub">Bunyi pop pelan tiap tap tombol, tersimpan otomatis</div>
          </div>
          <label class="toggle-switch">
            <input type="checkbox" ${prefState.sound ? 'checked' : ''} onchange="onToggleSound(this.checked)">
            <span class="slider"></span>
          </label>
        </div>`;
    }

    if(type === 'font'){
      iconEl.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="4 7 4 4 20 4 20 7"/><line x1="9" y1="20" x2="15" y2="20"/><line x1="12" y1="4" x2="12" y2="20"/></svg>';
      titleEl.textContent = 'Gaya Teks';
      subEl.textContent = 'Pilih gaya huruf yang paling kamu suka';
      bodyEl.innerHTML = `<div class="option-list" id="fontOptionList"></div>`;
      renderFontOptions();
    }

    if(type === 'cursor'){
      iconEl.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 3l7.07 16.97 2.51-7.39 7.39-2.51z"/></svg>';
      titleEl.textContent = 'Gaya Kursor';
      subEl.textContent = 'Ganti bentuk kursor mouse sesuai gaya kamu';
      bodyEl.innerHTML = `<div class="option-list" id="cursorOptionList"></div>`;
      renderCursorOptions();
    }

    document.getElementById('prefModalOverlay').classList.add('show');
  }

  function closePrefModal(){
    document.getElementById('prefModalOverlay').classList.remove('show');
  }

  function openPrefListModal(){
    document.getElementById('prefListModalOverlay').classList.add('show');
  }

  function closePrefListModal(){
    document.getElementById('prefListModalOverlay').classList.remove('show');
  }

  function onToggleDarkMode(checked){
    prefState.darkMode = checked;
    savePrefState();
    applyPrefState();
    showToast(checked ? 'Dark Mode diaktifkan 🌙' : 'Dark Mode dimatikan ☀️');
  }

  function onToggleNotif(key, checked){
    prefState.notif[key] = checked;
    savePrefState();
    showToast('Pengaturan notifikasi disimpan');
  }

  function onToggleSparkle(checked){
    prefState.sparkle = checked;
    savePrefState();
    showToast(checked ? 'Efek Sparkle diaktifkan ✨' : 'Efek Sparkle dimatikan');
  }

  function onToggleSound(checked){
    prefState.sound = checked;
    savePrefState();
    showToast(checked ? 'Efek Suara diaktifkan 🔊' : 'Efek Suara dimatikan');
    if(checked) playClickSound();
  }

  function renderFontOptions(){
    const listEl = document.getElementById('fontOptionList');
    if(!listEl) return;
    listEl.innerHTML = Object.keys(fontStyles).map(key => {
      const f = fontStyles[key];
      return `<div class="option-row${prefState.fontStyle === key ? ' active' : ''}" onclick="selectFontStyle('${key}')">
        <div class="option-row-left">
          <span class="option-row-swatch" style="background:#f3eefc; color:#7c5fce; font-family:${f.body}; font-weight:700; font-size:13px; display:flex; align-items:center; justify-content:center;">Aa</span>
          ${f.label}
        </div>
        <svg class="option-row-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
      </div>`;
    }).join('');
  }

  function selectFontStyle(key){
    prefState.fontStyle = key;
    savePrefState();
    applyPrefState();
    renderFontOptions();
    showToast(`Gaya teks diubah ke ${fontStyles[key].label}`);
  }

  function renderTemaOptions(){
    const listEl = document.getElementById('temaOptionList');
    if(!listEl) return;
    listEl.innerHTML = Object.keys(temaPalettes).map(key => {
      const p = temaPalettes[key];
      return `<div class="option-row${prefState.tema === key ? ' active' : ''}" onclick="selectTema('${key}')">
        <div class="option-row-left"><span class="option-row-swatch" style="background:${p.swatch};"></span>${p.label}</div>
        <svg class="option-row-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
      </div>`;
    }).join('');
  }

  function selectTema(key){
    prefState.tema = key;
    savePrefState();
    applyPrefState();
    renderTemaOptions();
    showToast(`Tema diubah ke ${temaPalettes[key].label}`);
  }

  function renderCursorOptions(){
    const listEl = document.getElementById('cursorOptionList');
    if(!listEl) return;
    listEl.innerHTML = Object.keys(cursorStyles).map(key => {
      const c = cursorStyles[key];
      return `<div class="option-row${prefState.cursorStyle === key ? ' active' : ''}" onclick="selectCursorStyle('${key}')">
        <div class="option-row-left">
          <span class="option-row-swatch cursor-swatch">${c.preview}</span>
          ${c.label}
        </div>
        <svg class="option-row-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
      </div>`;
    }).join('');
  }

  function selectCursorStyle(key){
    prefState.cursorStyle = key;
    savePrefState();
    applyPrefState();
    renderCursorOptions();
    showToast(`Gaya kursor diubah ke ${cursorStyles[key].label}`);
  }

  loadPrefState();
  applyPrefState();
  // ---------- EFEK SPARKLE SAAT SENTUH/KLIK (bisa dimatikan di Preferensi) ----------
  // Nyala di mana aja pas layar disentuh/diklik. Ikut prefState.sparkle -- dicek
  // ulang tiap sentuhan, jadi begitu dimatikan di Preferensi langsung berhenti
  // tanpa perlu reload halaman.
  (function(){
    const style = document.createElement('style');
    style.textContent = `
      .sparkle-particle{
        position:fixed; left:0; top:0; pointer-events:none; z-index:99999;
        transform:translate(-50%,-50%); line-height:1;
        filter:drop-shadow(0 0 3px rgba(169,142,232,.7));
        animation:sparklePop .65s ease-out forwards;
      }
      @keyframes sparklePop{
        0%{transform:translate(-50%,-50%) scale(.4) rotate(0deg); opacity:1;}
        55%{transform:translate(calc(-50% + var(--dx)*.7),calc(-50% + var(--dy)*.7)) scale(1.15) rotate(160deg); opacity:1;}
        100%{transform:translate(calc(-50% + var(--dx)),calc(-50% + var(--dy))) scale(.2) rotate(300deg); opacity:0;}
      }
    `;
    document.head.appendChild(style);
  })();
  const SPARKLE_CHARS = ['✨', '⭐', '✦', '✧', '💫'];
  function spawnSparkle(x, y){
    const jumlah = 3 + Math.floor(Math.random() * 2); // 3-4 partikel per sentuhan
    for (let i = 0; i < jumlah; i++) {
      const el = document.createElement('span');
      el.className = 'sparkle-particle';
      el.textContent = SPARKLE_CHARS[Math.floor(Math.random() * SPARKLE_CHARS.length)];
      const sudut = Math.random() * Math.PI * 2;
      const jarak = 22 + Math.random() * 34;
      el.style.left = x + 'px';
      el.style.top = y + 'px';
      el.style.setProperty('--dx', (Math.cos(sudut) * jarak) + 'px');
      el.style.setProperty('--dy', (Math.sin(sudut) * jarak) + 'px');
      el.style.fontSize = (11 + Math.random() * 9) + 'px';
      el.style.animationDelay = (i * 30) + 'ms';
      document.body.appendChild(el);
      el.addEventListener('animationend', () => el.remove());
    }
  }

  document.addEventListener('pointerdown', (e) => {
    if (!prefState.sparkle) return;
    spawnSparkle(e.clientX, e.clientY);
  });
  // ---------- EFEK SUARA SAAT KLIK TOMBOL (bisa dimatikan di Preferensi) ----------
  // Bunyi "pop" pelan disintesis langsung di browser (tanpa file audio), jadi
  // ringan dan nggak perlu asset tambahan. Cuma bunyi pas nyentuh elemen yang
  // interaktif (tombol, link, toggle, dll), bukan pas scroll biasa.
  let _sparkleAudioCtx = null;
  function playClickSound(){
    try{
      if(!_sparkleAudioCtx) _sparkleAudioCtx = new (window.AudioContext || window.webkitAudioContext)();
      const ctx = _sparkleAudioCtx;
      if(ctx.state === 'suspended') ctx.resume();
      const now = ctx.currentTime;
      const osc = ctx.createOscillator();
      const gain = ctx.createGain();
      osc.type = 'sine';
      osc.frequency.setValueAtTime(680, now);
      osc.frequency.exponentialRampToValueAtTime(180, now + 0.08);
      gain.gain.setValueAtTime(0.0001, now);
      gain.gain.exponentialRampToValueAtTime(0.16, now + 0.008);
      gain.gain.exponentialRampToValueAtTime(0.0001, now + 0.11);
      osc.connect(gain);
      gain.connect(ctx.destination);
      osc.start(now);
      osc.stop(now + 0.12);
    }catch(e){}
  }

  document.addEventListener('click', (e) => {
    if (!prefState.sound) return;
    const target = e.target.closest('button, a, input, select, label, .pref-row, .toggle-switch, [onclick], [role="button"]');
    if (!target) return;
    playClickSound();
  });



  const pageNames = {
    dashboard: 'Dashboard',
    surat_masuk: 'Surat Masuk',
    surat_keluar: 'Surat Keluar',
    arsip: 'Arsip Surat',
    agenda: 'Agenda',
    rapat: 'Manajemen Rapat',
    tamu: 'Buku Tamu',
    dokumen: 'Manajemen Dokumen',
    atk: 'Manajemen ATK',
    audit: 'Audit Trail Log',
    profil: 'Profil Saya'
  };
  const builtPages = ['dashboard', 'surat_masuk', 'surat_keluar', 'arsip', 'agenda', 'rapat', 'dokumen', 'atk', 'tamu', 'audit', 'profil'];



  function goTo(pageId){
    if(pageId === 'dashboard'){
      window.location.href = 'index.php';
      return;
    }
    window.location.href = pageId + '.php';
  }

  // ================= KOTAK PENCARIAN DASHBOARD =================
  // Kotak pencarian di topbar sekarang bisa mencari SEMUA hal yang tampil
  // di halaman Dashboard ini: menu sidebar, kartu statistik, tabel Rapat/
  // Agenda/Tamu Hari Ini, dan daftar Tugas (To-Do). Murni cari di halaman
  // yang sedang terbuka (tidak perlu request ke server), jadi hasilnya
  // instan sambil mengetik. Klik menu -> pindah halaman. Klik hasil lain
  // -> otomatis discroll & disorot ke baris/kartu terkait di dashboard ini.
  let gsActiveIndex = -1;
  let gsCurrentResults = [];

  function gsBuildIndex(){
    const items = [];

    // 1) Menu di sidebar
    document.querySelectorAll('.nav-item[data-page]').forEach(el => {
      const label = el.textContent.trim();
      if(!label) return;
      items.push({
        type: 'nav',
        icon: '📁',
        title: label,
        sub: 'Buka menu',
        page: el.getAttribute('data-page'),
        haystack: label.toLowerCase()
      });
    });

    // 2) Kartu statistik besar
    document.querySelectorAll('.bigstat-card').forEach(card => {
      const lbl = card.querySelector('.bs-lbl');
      const num = card.querySelector('.bs-num');
      if(!lbl) return;
      const moreBtn = card.querySelector('.bs-more');
      let page = null;
      if(moreBtn){
        const m = (moreBtn.getAttribute('onclick') || '').match(/goTo\('([^']+)'\)/);
        if(m) page = m[1];
      }
      const titleText = lbl.textContent.trim() + (num ? ' — ' + num.textContent.trim() : '');
      items.push({
        type: page ? 'nav' : 'info',
        icon: '📊',
        title: titleText,
        sub: 'Statistik Dashboard',
        page: page,
        haystack: (lbl.textContent.trim() + ' ' + (num ? num.textContent.trim() : '')).toLowerCase()
      });
    });

    // 3) Kartu Rapat / Agenda / Tamu di panel Operasional Harian -- dibatasi
    // ke set data yang sedang AKTIF ditampilkan (Minggu Ini / Bulan Ini) saja,
    // supaya tidak dobel dengan baris di set data yang lagi disembunyikan.
    const rowGroups = [
      { sel: '.op-range-view.active [id^="gsRapatRow-"]',  icon: '🗓️', sub: 'Rapat Terjadwal' },
      { sel: '.op-range-view.active [id^="gsAgendaRow-"]', icon: '🗓️', sub: 'Agenda Terjadwal' },
      { sel: '.op-range-view.active [id^="gsTamuRow-"]',   icon: '📖', sub: 'Tamu Terjadwal' }
    ];
    rowGroups.forEach(g => {
      document.querySelectorAll(g.sel).forEach(row => {
        const fullText = row.textContent.replace(/\s+/g, ' ').trim();
        if(!fullText) return;
        const titleEl = row.querySelector('.op-event-title');
        const title = titleEl ? titleEl.textContent.replace(/\s+/g, ' ').trim() : fullText;
        items.push({
          type: 'row',
          icon: g.icon,
          title: title,
          sub: g.sub,
          rowId: row.id,
          haystack: fullText.toLowerCase()
        });
      });
    });

    // 4) Daftar Tugas (To-Do)
    document.querySelectorAll('#todoList .todo-item').forEach(el => {
      const span = el.querySelector('span[contenteditable]');
      const text = (span ? span.textContent : el.textContent).trim();
      if(!text) return;
      items.push({
        type: 'todo',
        icon: '📝',
        title: text,
        sub: el.classList.contains('done') ? 'Tugas — Selesai' : 'Tugas — Belum Selesai',
        dataId: el.getAttribute('data-id'),
        haystack: text.toLowerCase()
      });
    });

    // 5) Tugas Deadline (otomatis dari rapat/agenda/surat/tamu yang deadline-nya dekat)
    document.querySelectorAll('#deadlineList .todo-item[id^="gsDeadlineItem-"]').forEach(el => {
      const fullText = el.textContent.replace(/\s+/g, ' ').trim();
      if(!fullText) return;
      const topSpans = el.querySelectorAll(':scope > span');
      let title = fullText;
      if(topSpans[0]){
        const innerSpans = topSpans[0].querySelectorAll(':scope > span');
        const textSpanEl = innerSpans[innerSpans.length - 1];
        if(textSpanEl) title = textSpanEl.textContent.trim();
      }
      const dueLabel = topSpans[1] ? topSpans[1].textContent.trim() : '';
      items.push({
        type: 'row',
        icon: '⏰',
        title: title,
        sub: dueLabel ? `Tugas Deadline — ${dueLabel}` : 'Tugas Deadline',
        rowId: el.id,
        haystack: fullText.toLowerCase()
      });
    });

    // 6) Kegiatan di widget Kalender (gabungan Agenda + Rapat + Tamu per tanggal,
    // dikirim dari PHP lewat calEventsData -- lihat deklarasinya di bawah)
    if(typeof calEventsData !== 'undefined'){
      const calTypeSub = { agenda: 'Kalender — Agenda', rapat: 'Kalender — Rapat', tamu: 'Kalender — Tamu' };
      const calTypeIcon = { agenda: '🗓️', rapat: '🤝', tamu: '📖' };
      Object.keys(calEventsData).forEach(key => {
        const parts = key.split('-').map(Number);
        const y = parts[0], m = parts[1], d = parts[2];
        (calEventsData[key] || []).forEach(ev => {
          const title = ev.title || '(tanpa judul)';
          const metaParts = [ev.time, ev.detail, ev.status].filter(Boolean);
          const haystack = [title, ...metaParts, key].join(' ').toLowerCase();
          items.push({
            type: 'calendar',
            icon: calTypeIcon[ev.type] || '🗓️',
            title: title,
            sub: calTypeSub[ev.type] || 'Kalender',
            calYear: y,
            calMonth: m,
            calDay: d,
            haystack: haystack
          });
        });
      });
    }

    return items;
  }

  function gsEscapeHtml(str){
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
  }

  function gsHighlight(text, query){
    const safe = gsEscapeHtml(text);
    if(!query) return safe;
    const idx = safe.toLowerCase().indexOf(query.toLowerCase());
    if(idx === -1) return safe;
    return safe.slice(0, idx) + '<mark>' + safe.slice(idx, idx + query.length) + '</mark>' + safe.slice(idx + query.length);
  }

  let gsDebounceTimer = null;
  function globalSearchOnInput(){
    clearTimeout(gsDebounceTimer);
    gsDebounceTimer = setTimeout(globalSearchRender, 120);
  }

  function globalSearchRender(){
    const input = document.getElementById('globalSearchInput');
    const box = document.getElementById('globalSearchResults');
    if(!input || !box) return;
    const query = input.value.trim();
    gsActiveIndex = -1;

    if(query.length < 1){
      box.classList.remove('show');
      box.innerHTML = '';
      gsCurrentResults = [];
      return;
    }

    const q = query.toLowerCase();
    const all = gsBuildIndex();
    const matched = all.filter(it => it.haystack.includes(q)).slice(0, 30);
    gsCurrentResults = matched;

    if(matched.length === 0){
      box.innerHTML = '<div class="gs-empty">Tidak ada hasil untuk "' + gsEscapeHtml(query) + '" 🌸</div>';
      box.classList.add('show');
      return;
    }

    // Kelompokkan berdasarkan kategori (sub) supaya rapi
    const groups = [];
    const groupIndexBySub = {};
    matched.forEach((item, i) => {
      if(!(item.sub in groupIndexBySub)){
        groupIndexBySub[item.sub] = groups.length;
        groups.push({ sub: item.sub, items: [] });
      }
      groups[groupIndexBySub[item.sub]].items.push({ ...item, _idx: i });
    });

    box.innerHTML = groups.map(g => `
      <div class="gs-group-label">${gsEscapeHtml(g.sub)}</div>
      ${g.items.map(item => `
        <div class="gs-item" data-gs-idx="${item._idx}" onmousedown="event.preventDefault(); gsSelectResult(${item._idx})">
          <div class="gs-item-ico">${item.icon}</div>
          <div class="gs-item-text">
            <div class="gs-item-title">${gsHighlight(item.title, query)}</div>
          </div>
        </div>
      `).join('')}
    `).join('');

    box.classList.add('show');
  }

  function gsSelectResult(idx){
    const item = gsCurrentResults[idx];
    if(!item) return;
    const input = document.getElementById('globalSearchInput');
    const box = document.getElementById('globalSearchResults');

    if(item.type === 'nav' && item.page){
      goTo(item.page);
      return;
    }

    if(item.type === 'calendar'){
      if(box){ box.classList.remove('show'); }
      if(input){ input.blur(); }
      goToCalendarDate(item.calYear, item.calMonth, item.calDay);
      return;
    }

    // untuk baris tabel / tugas: scroll & sorot di halaman ini, lalu tutup dropdown
    let target = null;
    if(item.type === 'row' && item.rowId){
      target = document.getElementById(item.rowId);
    } else if(item.type === 'todo' && item.dataId){
      target = document.querySelector(`#todoList .todo-item[data-id="${item.dataId}"]`);
    }

    if(box){ box.classList.remove('show'); }
    if(input){ input.blur(); }

    if(target){
      target.scrollIntoView({ behavior: 'smooth', block: 'center' });
      target.classList.remove('gs-hit-flash');
      // trigger reflow supaya animasi bisa diulang walau elemen yang sama diklik lagi
      void target.offsetWidth;
      target.classList.add('gs-hit-flash');
      setTimeout(() => target.classList.remove('gs-hit-flash'), 1900);
    } else {
      showToast('Item ditemukan: ' + item.title);
    }
  }

  function globalSearchKeydown(e){
    const box = document.getElementById('globalSearchResults');
    if(!box || !box.classList.contains('show')) {
      if(e.key === 'Escape'){
        e.target.value = '';
        globalSearchRender();
        e.target.blur();
      }
      return;
    }
    const nodes = Array.from(box.querySelectorAll('.gs-item'));
    if(e.key === 'ArrowDown'){
      e.preventDefault();
      gsActiveIndex = Math.min(gsActiveIndex + 1, nodes.length - 1);
      gsUpdateActiveHighlight(nodes);
    } else if(e.key === 'ArrowUp'){
      e.preventDefault();
      gsActiveIndex = Math.max(gsActiveIndex - 1, 0);
      gsUpdateActiveHighlight(nodes);
    } else if(e.key === 'Enter'){
      e.preventDefault();
      if(gsActiveIndex >= 0 && nodes[gsActiveIndex]){
        const idx = parseInt(nodes[gsActiveIndex].getAttribute('data-gs-idx'), 10);
        gsSelectResult(idx);
      } else if(gsCurrentResults.length > 0){
        gsSelectResult(0);
      }
    } else if(e.key === 'Escape'){
      e.target.value = '';
      globalSearchRender();
      e.target.blur();
    }
  }

  function gsUpdateActiveHighlight(nodes){
    nodes.forEach(n => n.classList.remove('gs-active'));
    if(nodes[gsActiveIndex]){
      nodes[gsActiveIndex].classList.add('gs-active');
      nodes[gsActiveIndex].scrollIntoView({ block: 'nearest' });
    }
  }

  // Tutup dropdown saat klik di luar kotak pencarian
  document.addEventListener('click', (e) => {
    const wrap = document.getElementById('globalSearchWrap');
    if(wrap && !wrap.contains(e.target)){
      const box = document.getElementById('globalSearchResults');
      if(box) box.classList.remove('show');
    }
  });

  // ---------- LOGOUT ----------
  function confirmLogout(){
    if(confirm('Yakin ingin keluar dari akun ini?')){
      window.location.href = 'logout.php';
    }
  }

  // ---------- CUTE BUDDY ----------
  var buddyMessages = [
    'Semangat kerja hari ini! 🌸',
    'Jangan lupa istirahat sebentar ya~',
    'Kamu keren, terus semangat! ✨',
    'Yuk cek agenda hari ini~',
    'Minum air putih dulu yuk 💧',
    'Kerja bagus sejauh ini!',
    'Halo! Ada yang bisa dibantu?',
    'Tetap semangat sampai selesai! 🎀'
  ];
  function buddyPoke(){
    var msg = buddyMessages[Math.floor(Math.random() * buddyMessages.length)];
    showToast(msg);
  }
  function buddyDismiss(evt){
    evt.stopPropagation();
    var el = document.getElementById('cuteBuddy');
    if(el) el.style.display = 'none';
  }


  function todoEditKeydown(e){
    // Enter menyelesaikan pengeditan (tanpa membuat baris baru), Escape membatalkan fokus
    if(e.key === 'Enter' || e.key === 'Escape'){
      e.preventDefault();
      e.target.blur();
    }
  }

  function selectAllText(el){
    const range = document.createRange();
    range.selectNodeContents(el);
    const sel = window.getSelection();
    sel.removeAllRanges();
    sel.addRange(range);
  }

  // ---------- TO-DO LIST DATA (tersimpan di localStorage) ----------
  function todoEscapeHtml(str){
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
  }

  function loadTodoData(){
    try{
      const raw = localStorage.getItem('bkjl_todo_data');
      if(raw) return JSON.parse(raw);
    }catch(e){}
    // data contoh awal, hanya dipakai jika belum pernah ada data tersimpan
    return [
      { id: 't1', text: 'Beli buku', done: false },
      { id: 't2', text: 'Buat email', done: false }
    ];
  }

  function saveTodoData(){
    try{ localStorage.setItem('bkjl_todo_data', JSON.stringify(todoData)); }catch(e){}
  }

  const todoData = loadTodoData();

  function renderTodoList(){
    const list = document.getElementById('todoList');
    if(!list) return;

    if(todoData.length === 0){
      list.innerHTML = '<div class="todo-empty">Belum ada tugas ✨</div>';
      return;
    }

    list.innerHTML = todoData.map(item => `
      <div class="todo-item${item.done ? ' done' : ''}" data-id="${item.id}">
        <input type="checkbox" ${item.done ? 'checked' : ''} onchange="toggleTodoItem('${item.id}', this.checked)">
        <span contenteditable="true" onkeydown="todoEditKeydown(event)" onblur="saveTodoText('${item.id}', this)">${todoEscapeHtml(item.text)}</span>
        <button class="todo-del" onclick="deleteTodoItem('${item.id}')"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg></button>
      </div>
    `).join('');
  }

  function toggleTodoItem(id, checked){
    const item = todoData.find(t => t.id === id);
    if(!item) return;
    item.done = checked;
    saveTodoData();
    const row = document.querySelector(`#todoList .todo-item[data-id="${id}"]`);
    if(row) row.classList.toggle('done', checked);
  }

  function saveTodoText(id, el){
    const item = todoData.find(t => t.id === id);
    if(!item) return;
    const text = el.textContent.trim();
    if(!text){
      // jangan biarkan teks kosong, kembalikan ke teks sebelumnya
      el.textContent = item.text;
      return;
    }
    item.text = text;
    saveTodoData();
  }

  function deleteTodoItem(id){
    const idx = todoData.findIndex(t => t.id === id);
    if(idx === -1) return;
    todoData.splice(idx, 1);
    saveTodoData();
    renderTodoList();
    showToast('Tugas dihapus 🗑️');
  }

  function addTodo(){
    const newItem = { id: 'todo' + Date.now(), text: 'Tugas baru', done: false };
    todoData.push(newItem);
    saveTodoData();
    renderTodoList();

    const row = document.querySelector(`#todoList .todo-item[data-id="${newItem.id}"]`);
    const newSpan = row ? row.querySelector('span') : null;
    if(newSpan){
      newSpan.focus();
      selectAllText(newSpan);
    }
  }

  // ---------- TUGAS DEADLINE ----------
  // Sekarang sudah otomatis & dirender langsung dari PHP (lihat query
  // $autoDeadlineItems di atas file ini), jadi tidak perlu lagi fungsi
  // JS buat nambah/toggle/hapus/bersihkan manual di localStorage.

  // ---------- CALENDAR WIDGET (dashboard) ----------
  const calMonthNamesShort = ['JAN','FEB','MAR','APR','MAY','JUN','JUL','AUG','SEP','OCT','NOV','DEC'];
  const calMonthNamesFull  = ['January','February','March','April','May','June','July','August','September','October','November','December'];
  const calToday = new Date();
  const calState = { year: calToday.getFullYear(), month: calToday.getMonth(), selectedDay: calToday.getDate() };

  // ---------- CATATAN (tersimpan di localStorage) ----------
  function loadNotesData(){
    try{
      const raw = localStorage.getItem('bkjl_notes_data');
      if(raw !== null) return raw;
    }catch(e){}
    return '';
  }

  function saveNotesData(){
    const ta = document.getElementById('notesTextarea');
    if(!ta) return;
    try{ localStorage.setItem('bkjl_notes_data', ta.value); }catch(e){}
  }

  (function initNotes(){
    const ta = document.getElementById('notesTextarea');
    if(!ta) return;
    ta.value = loadNotesData();
    ta.addEventListener('input', () => {
      saveNotesData();
      autoResizeNotes();
    });
  })();

  // ---------- CALENDAR WIDGET (read-only, gabungan Agenda + Rapat + Tamu dari database) ----------
  function dateKey(y, m, d){ return `${y}-${m}-${d}`; }

  // Data jadwal per tanggal dikirim langsung dari PHP (gabungan tabel agenda,
  // rapat, dan tamu yang sudah di-query di atas), jadi tidak perlu localStorage
  // ataupun tabel database tambahan.
  const calEventsData = <?= json_encode($calEventsByDate, JSON_UNESCAPED_UNICODE) ?>;

  function renderAgendaList(){
    const listEl = document.getElementById('agendaList');
    if(!listEl) return;
    const key = dateKey(calState.year, calState.month, calState.selectedDay);
    const items = calEventsData[key] || [];

    if(items.length === 0){
      listEl.innerHTML = '<div class="agenda-empty">Belum ada jadwal (agenda/rapat/tamu) untuk tanggal ini.</div>';
      return;
    }

    const typeLabels = { agenda: 'Agenda', rapat: 'Rapat', tamu: 'Tamu' };

    listEl.innerHTML = items.map(item => {
      const meta = [item.time, item.detail, item.status].filter(Boolean).join(' &middot; ');
      const safeTitle = (item.title || '(tanpa judul)').replace(/</g, '&lt;');
      const safeMeta = meta.replace(/</g, '&lt;');
      const type = item.type || '';
      const badgeText = typeLabels[type] || '';
      const badgeHtml = badgeText ? `<span class="agenda-item-badge">${badgeText}</span>` : '';
      return `<div class="agenda-item" data-type="${type}">
        <div class="agenda-item-titlerow">${badgeHtml}<b>${safeTitle}</b></div>
        ${safeMeta}
      </div>`;
    }).join('');
  }

  function renderCalendar(){
    const yearEl   = document.getElementById('calYear');
    const monthsEl = document.getElementById('calMonths');
    const daysEl   = document.getElementById('calDays');
    const titleEl  = document.getElementById('agendaTitle');
    if(!yearEl || !monthsEl || !daysEl) return;

    yearEl.textContent = calState.year;

    // months row
    monthsEl.innerHTML = calMonthNamesShort.map((m, i) =>
      `<span class="cal-month${i === calState.month ? ' active' : ''}" data-month="${i}">${m}</span>`
    ).join('');
    monthsEl.querySelectorAll('.cal-month').forEach(el => {
      el.addEventListener('click', () => {
        calState.month = parseInt(el.getAttribute('data-month'), 10);
        calState.selectedDay = null;
        renderCalendar();
      });
    });

    // days grid
    const firstDayOfWeek = new Date(calState.year, calState.month, 1).getDay();
    const totalDays = new Date(calState.year, calState.month + 1, 0).getDate();
    let html = '';
    for(let i = 0; i < firstDayOfWeek; i++){
      html += '<div class="cal-day empty"></div>';
    }
    for(let d = 1; d <= totalDays; d++){
      const isToday = (d === calToday.getDate() && calState.month === calToday.getMonth() && calState.year === calToday.getFullYear());
      const isSelected = (d === calState.selectedDay);
      const hasEvent = !!(calEventsData[dateKey(calState.year, calState.month, d)] && calEventsData[dateKey(calState.year, calState.month, d)].length);
      html += `<div class="cal-day${isToday ? ' today' : ''}${isSelected ? ' selected' : ''}${hasEvent ? ' has-event' : ''}" data-day="${d}">${d}</div>`;
    }
    daysEl.innerHTML = html;

    daysEl.querySelectorAll('.cal-day:not(.empty)').forEach(el => {
      el.addEventListener('click', () => {
        calState.selectedDay = parseInt(el.getAttribute('data-day'), 10);
        renderCalendar();
        if(titleEl){
          titleEl.textContent = `Jadwal untuk ${calMonthNamesFull[calState.month]} ${calState.selectedDay}`;
        }
        renderAgendaList();
      });
    });

    if(titleEl && calState.selectedDay){
      titleEl.textContent = `Jadwal untuk ${calMonthNamesFull[calState.month]} ${calState.selectedDay}`;
    }

    requestAnimationFrame(syncCardHeights);
  }

  function changeCalYear(delta){
    calState.year += delta;
    renderCalendar();
  }

  // Dipanggil dari hasil pencarian global (tipe 'calendar') supaya widget
  // Kalender langsung pindah ke bulan/tanggal kegiatan yang dicari, lalu
  // menyorotnya sebentar biar kelihatan.
  function goToCalendarDate(y, m, d){
    calState.year = y;
    calState.month = m;
    calState.selectedDay = d;
    renderCalendar();
    const titleEl = document.getElementById('agendaTitle');
    if(titleEl){ titleEl.textContent = `Jadwal untuk ${calMonthNamesFull[m]} ${d}`; }
    renderAgendaList();

    ['calendarCard', 'agendaCard'].forEach(id => {
      const el = document.getElementById(id);
      if(!el) return;
      el.scrollIntoView({ behavior: 'smooth', block: 'center' });
      el.classList.remove('gs-hit-flash');
      void el.offsetWidth;
      el.classList.add('gs-hit-flash');
      setTimeout(() => el.classList.remove('gs-hit-flash'), 1900);
    });
  }

  renderCalendar();
  renderAgendaList();


  // ---------- SAMAKAN TINGGI KARTU KALENDER / AGENDA / TO-DO ----------
  // Kartu agenda & to-do mengikuti tinggi kontennya sendiri (kecil kalau isinya sedikit,
  // memanjang kalau isinya banyak), tapi dibatasi maksimal setinggi kartu kalender.
  // Bagian header/form tetap utuh (tidak ikut mengecil), hanya daftarnya yang menyesuaikan
  // dan discroll kalau kontennya melebihi ruang yang tersisa.

  function syncCardHeights(){
    const calCard = document.getElementById('calendarCard');
    const agendaCard = document.getElementById('agendaCard');
    const todoCard = document.getElementById('todoCard');
    const notesCard = document.getElementById('notesCard');
    const notesTextarea = document.getElementById('notesTextarea');
    if(!calCard || !agendaCard || !todoCard || !notesCard || !notesTextarea) return;

    // di layar sempit (kartu ditumpuk vertikal), biarkan tinggi alami sepenuhnya
    if(window.innerWidth <= 900){
      agendaCard.style.maxHeight = '';
      todoCard.style.maxHeight = '';
      notesTextarea.style.maxHeight = '';
      autoResizeNotes();
      return;
    }

    const calHeight = calCard.getBoundingClientRect().height;
    if(calHeight <= 0) return;

    agendaCard.style.maxHeight = calHeight + 'px';
    todoCard.style.maxHeight = calHeight + 'px';

    // catatan: textarea auto-grow mengikuti isi, dibatasi maksimal setinggi kartu kalender
    const notesHead = notesCard.querySelector('h3');
    const cardStyles = getComputedStyle(notesCard);
    const paddingTop = parseFloat(cardStyles.paddingTop) || 0;
    const paddingBottom = parseFloat(cardStyles.paddingBottom) || 0;
    const headHeight = notesHead ? notesHead.getBoundingClientRect().height : 0;
    const headMarginBottom = notesHead ? (parseFloat(getComputedStyle(notesHead).marginBottom) || 0) : 0;
    const availableForNotes = calHeight - paddingTop - paddingBottom - headHeight - headMarginBottom;

    notesTextarea.style.maxHeight = Math.max(availableForNotes, 150) + 'px';
    autoResizeNotes();
  }

  // textarea catatan tumbuh mengikuti isinya (kecil kalau isinya sedikit,
  // memanjang kalau isinya banyak), lalu discroll setelah mencapai max-height di atas
  function autoResizeNotes(){
    const ta = document.getElementById('notesTextarea');
    if(!ta) return;
    ta.style.height = 'auto';
    ta.style.height = ta.scrollHeight + 'px';
  }

  let syncCardHeightsTimer = null;
  window.addEventListener('resize', () => {
    clearTimeout(syncCardHeightsTimer);
    syncCardHeightsTimer = setTimeout(syncCardHeights, 120);
  });

  let toastTimer = null;
  function showToast(msg){
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.classList.add('show');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => t.classList.remove('show'), 2200);
  }

  // ---------- AGENDA PAGE ----------
  function agnFilterTable(){
    const q = (document.getElementById('agnSearch')?.value || '').toLowerCase().trim();
    const cat = document.getElementById('agnCategoryFilter')?.value || '';
    const date = document.getElementById('agnDateFilter')?.value || '';
    document.querySelectorAll('#agnTable tbody tr').forEach(row => {
      const text = row.textContent.toLowerCase();
      const matchQ = !q || text.includes(q);
      const matchCat = !cat || row.getAttribute('data-cat') === cat;
      const matchDate = !date || row.getAttribute('data-date') === date;
      row.style.display = (matchQ && matchCat && matchDate) ? '' : 'none';
    });
  }

  function agnToggleDone(btn){
    const row = btn.closest('tr');
    row.classList.toggle('agn-done');
    showToast(row.classList.contains('agn-done') ? 'Agenda ditandai selesai ✅' : 'Agenda ditandai belum selesai');
  }

  function agnDeleteRow(btn){
    const row = btn.closest('tr');
    row.style.transition = 'opacity .25s ease';
    row.style.opacity = '0';
    setTimeout(() => row.remove(), 220);
    showToast('Agenda dihapus 🗑️');
  }

  // ---------- MANAJEMEN RAPAT PAGE ----------
  function rptFilterList(){
    const q = (document.getElementById('rptSearch')?.value || '').toLowerCase().trim();
    document.querySelectorAll('#rptMeetingList .rpt-meeting-item').forEach(li => {
      const text = li.textContent.toLowerCase();
      li.style.display = (!q || text.includes(q)) ? '' : 'none';
    });
  }

  function rptSelectMeeting(el, judul, pic, tanggal, waktu, lokasi){
    document.querySelectorAll('#rptMeetingList .rpt-meeting-item').forEach(li => li.classList.remove('active'));
    el.classList.add('active');
    document.getElementById('rptDJudul').textContent = judul;
    document.getElementById('rptDPic').textContent = pic;
    document.getElementById('rptDTanggal').textContent = tanggal;
    document.getElementById('rptDWaktu').textContent = waktu;
    document.getElementById('rptDLokasi').textContent = lokasi;
  }

  function rptDeleteMeeting(btn){
    const li = btn.closest('li');
    li.classList.add('rpt-fade-out');
    setTimeout(() => li.remove(), 220);
    showToast('Rapat dihapus 🗑️');
  }

  function agnBindReminderItem(li){
    // handled via inline onclick (agnToggleReminder / agnDelReminder); kept for future dynamic items
  }

  function agnToggleReminder(btn){
    const li = btn.closest('li');
    li.classList.toggle('done');
  }

  function agnDelReminder(btn){
    const li = btn.closest('li');
    const list = li.parentElement;
    li.remove();
    if(list && list.children.length === 0){
      list.innerHTML = '<div class="agn-empty">Belum ada catatan reminder 🌸</div>';
    }
  }

  function agnAddReminder(){
    const task = prompt('Apa pengingat barumu?');
    if(task === null || task.trim() === '') return;
    const list = document.getElementById('agnReminderList');
    if(list.querySelector('.agn-empty')) list.innerHTML = '';
    const li = document.createElement('li');
    li.innerHTML = `<span class="rtext"></span><div class="actions"><button class="check-btn" onclick="agnToggleReminder(this)">✔</button><button class="del-btn" onclick="agnDelReminder(this)">✕</button></div>`;
    li.querySelector('.rtext').textContent = task.trim();
    list.appendChild(li);
    showToast('Reminder ditambahkan ✨');
  }


  // ---------- AMBIL ATK (tabel dashboard, tersimpan di localStorage) ----------
  // Setiap data baru yang ditambahkan lewat form akan langsung tersimpan
  // dan tampil di tabel "Ambil ATK" pada halaman Dashboard ini.
  // Warna chip disesuaikan otomatis berdasarkan nama divisi
  const atkAmbilDivisiStyle = {
    'PEMASARAN'      : { cls:'chip-amber',     dot:'#b5791b' },
    'TALLY KEY'       : { cls:'chip-undangan',  dot:'#92400e' },
    'STAFF'           : { cls:'chip-grey',      dot:'#7c8496' },
    'HRD'             : { cls:'chip-permohonan',dot:'#065f46' },
    'MANAJER'         : { cls:'chip-red',       dot:'#c9455f' },
    'WAKIL MANAJER'   : { cls:'chip-lainnya',   dot:'#5b21b6' },
    'IT'              : { cls:'chip-blue',      dot:'#3d72c4' }
  };
  function atkAmbilDivisiInfo(divisi){
    return atkAmbilDivisiStyle[(divisi || '').toUpperCase()] || { cls:'chip-eksternal', dot:'#9d174d' };
  }

  function atkAmbilEsc(str){
    const div = document.createElement('div');
    div.textContent = str == null ? '' : String(str);
    return div.innerHTML;
  }

  // ---- Daftar Inventaris ATK (data ASLI barang, dibaca dari kunci localStorage yang
  // sama dipakai halaman Manajemen ATK / atk.php, sehingga tabel ini selalu real-time
  // sinkron: barang, kategori, kode, stok, satuan & status persis seperti di atk.php) ----
  const idxAtkCategoryLabel = { alat_tulis:'Alat Tulis', perabotan:'Perabotan', elektronik:'Elektronik', lainnya:'Lainnya' };
  const idxAtkCategoryMigrateMap = { kertas_dokumen:'alat_tulis', perlengkapan_kantor:'alat_tulis', tinta_toner:'alat_tulis' };
  const idxAtkCatPillPalette = {
    alat_tulis: { bg:'#efe6fb', color:'#8a5fce' },
    perabotan:  { bg:'#fff2cf', color:'#a97a13' },
    elektronik: { bg:'#e2f7e8', color:'#2f9d61' },
    lainnya:    { bg:'#fde3ec', color:'#c4507e' }
  };
  const idxAtkCatPillPaletteDark = {
    alat_tulis: { bg:'#26213f', color:'#b6a3ec' },
    perabotan:  { bg:'#332c16', color:'#d9ac5e' },
    elektronik: { bg:'#123324', color:'#83d9a6' },
    lainnya:    { bg:'#3a2338', color:'#dd8fb5' }
  };
  function idxAtkCatPillStyle(slug){
    const dark = document.documentElement.classList.contains('dark-mode');
    if(dark) return idxAtkCatPillPaletteDark[slug] || { bg:'#1f2233', color:'#98a0bb' };
    return idxAtkCatPillPalette[slug] || { bg:'#f0ecf9', color:'#7a6fa0' };
  }

  function idxAtkLoadCategories(){
    try{
      const raw = localStorage.getItem('bkjl_atk_manage_categories');
      if(raw){
        const arr = JSON.parse(raw);
        if(Array.isArray(arr) && arr.length){
          const seen = {}; const out = [];
          arr.forEach(c => {
            const slug = idxAtkCategoryMigrateMap[c.slug] || c.slug;
            if(seen[slug]) return;
            seen[slug] = true;
            out.push({ slug, label: idxAtkCategoryLabel[slug] || c.label });
          });
          return out;
        }
      }
    }catch(e){}
    return Object.keys(idxAtkCategoryLabel).map(slug => ({ slug, label: idxAtkCategoryLabel[slug] }));
  }

  // Menggabungkan 3 sumber data barang ATK persis seperti cara atk.php membangun
  // "atkProducts": barang tambahan (custom), lalu override stok terbaru, lalu meta
  // (kode/lokasi/nama/satuan/kategori) — supaya hasilnya identik dengan atk.php.
  function idxAtkLoadProducts(){
    const products = {};
    try{
      const raw = localStorage.getItem('bkjl_atk_manage_custom_products');
      if(raw){
        const custom = JSON.parse(raw);
        Object.keys(custom).forEach(id => {
          products[id] = { ...custom[id] };
          if(idxAtkCategoryMigrateMap[products[id].category]) products[id].category = idxAtkCategoryMigrateMap[products[id].category];
        });
      }
    }catch(e){}
    try{
      const raw = localStorage.getItem('bkjl_atk_stock');
      if(raw){
        const overrides = JSON.parse(raw);
        Object.keys(overrides).forEach(id => { if(products[id]) products[id].stock = overrides[id]; });
      }
    }catch(e){}
    try{
      const raw = localStorage.getItem('bkjl_atk_manage_meta');
      if(raw){
        const meta = JSON.parse(raw);
        Object.keys(meta).forEach(id => {
          if(products[id]){
            products[id].code = meta[id].code;
            products[id].location = meta[id].location;
            if(meta[id].name) products[id].name = meta[id].name;
            if(meta[id].unit) products[id].unit = meta[id].unit;
            if(meta[id].category) products[id].category = idxAtkCategoryMigrateMap[meta[id].category] || meta[id].category;
          }
        });
      }
    }catch(e){}
    return products;
  }

  function idxAtkLowStockThreshold(){
    try{
      const raw = localStorage.getItem('bkjl_atk_manage_settings');
      if(raw){ const s = JSON.parse(raw); return s.minStock || 20; }
    }catch(e){}
    return 20;
  }

  function idxAtkStatusInfo(stock, min){
    const dark = document.documentElement.classList.contains('dark-mode');
    if(stock === 0) return dark ? { color:'#e08fa0', bg:'#351720', label:'Habis' } : { color:'#c9455f', bg:'#ffd9de', label:'Habis' };
    if(stock < min) return dark ? { color:'#d9ac5e', bg:'#332c16', label:'Menipis' } : { color:'#92400e', bg:'#fff3c9', label:'Menipis' };
    return dark ? { color:'#83d9a6', bg:'#123324', label:'Tersedia' } : { color:'#2f9d61', bg:'#d3f5df', label:'Tersedia' };
  }

  function idxAtkRenderInventory(){
    const body = document.getElementById('idxAtkInvTableBody');
    if(!body) return;

    const products = idxAtkLoadProducts();
    const min = idxAtkLowStockThreshold();
    const allRows = Object.keys(products).map(id => ({ id, ...products[id] }));

    body.innerHTML = allRows.length ? allRows.map(p => {
      const st = idxAtkStatusInfo(p.stock, min);
      const catStyle = idxAtkCatPillStyle(p.category);
      return `<tr>
        <td>${atkAmbilEsc(p.name)}</td>
        <td><span class="status-chip" style="background:${catStyle.bg}; color:${catStyle.color};">${atkAmbilEsc(idxAtkCategoryLabel[p.category] || p.category)}</span></td>
        <td>${p.stock}</td>
        <td>${atkAmbilEsc(p.unit)}</td>
        <td><span class="status-chip" style="background:${st.bg}; color:${st.color};">${st.label}</span></td>
        <td>
          <button class="idxatk-pgbtn" style="color:#b5791b; background:#fff3c9;" onclick="goTo('atk')" title="Kelola barang ini di halaman Manajemen ATK">✏️</button>
          <button class="idxatk-pgbtn" style="color:#c9455f; background:#ffd9de;" onclick="idxAtkDeleteItem('${p.id}', '${atkAmbilEsc(p.name).replace(/'/g, "\\'")}')" title="Hapus barang">🗑️</button>
        </td>
      </tr>`;
    }).join('') : `<tr><td colspan="6" style="text-align:center; padding:16px; color:var(--ink-soft); font-weight:700;">Belum ada barang ATK 🌸</td></tr>`;
  }

  // ---- Hapus barang langsung dari dashboard (hanya barang tambahan/custom yang bisa
  // dihapus, sama seperti aturan di halaman Manajemen ATK — barang bawaan sistem hanya
  // bisa diedit lewat halaman itu, bukan dihapus) ----
  function idxAtkIsCustomId(id){
    try{
      const raw = localStorage.getItem('bkjl_atk_manage_custom_products');
      if(raw){
        const custom = JSON.parse(raw);
        return Object.prototype.hasOwnProperty.call(custom, id);
      }
    }catch(e){}
    return false;
  }
  function idxAtkDeleteItem(id, name){
    if(!idxAtkIsCustomId(id)){
      showToast('Barang bawaan sistem hanya bisa diedit di halaman Manajemen ATK 🌸');
      return;
    }
    if(!confirm(`Hapus barang "${name}"?`)) return;
    try{
      const raw = localStorage.getItem('bkjl_atk_manage_custom_products');
      const custom = raw ? JSON.parse(raw) : {};
      delete custom[id];
      localStorage.setItem('bkjl_atk_manage_custom_products', JSON.stringify(custom));
    }catch(e){}
    showToast(`Barang "${name}" dihapus 🗑️`);
    idxAtkRenderInventory();
  }

  // Kalau data ATK diubah dari tab/halaman lain (mis. atk.php dibuka di tab lain),
  // tabel di dashboard ini otomatis ikut diperbarui berkat event "storage".
  window.addEventListener('storage', (e) => {
    const watchedKeys = ['bkjl_atk_manage_custom_products','bkjl_atk_stock','bkjl_atk_manage_meta','bkjl_atk_manage_categories','bkjl_atk_manage_settings'];
    if(watchedKeys.includes(e.key)) idxAtkRenderInventory();
  });



  // ---------- HITUNG OTOMATIS: ANGKA RINGKASAN DASHBOARD ----------
  // Angka-angka ini dihitung otomatis dari data asli di halaman
  // Surat Masuk / Surat Keluar / Inventaris / Pegawai (disimpan ke
  // localStorage oleh masing-masing halaman saat dibuka), lalu dibaca
  // di sini. Kalau halaman terkait belum pernah dibuka sama sekali,
  // nilai default di HTML tetap ditampilkan sebagai fallback.
  function updateBigStatCards(){
    // "Surat Masuk Aktif" dan "Persetujuan Surat Keluar" sekarang sama-sama
    // dirender langsung dari PHP/database (lihat $totalSuratMasukAktif dan
    // $totalSuratKeluarMenungguApproval di atas), jadi TIDAK lagi ditimpa
    // dari localStorage.
  }
  updateBigStatCards();

  // ---------- KARTU "AGENDA AKTIF" REAL-TIME ----------
  // Kartu ungu di dashboard menghitung agenda yang AKAN DILAKSANAKAN HARI INI
  // dan statusnya masih "belum". Nilainya di-poll berkala ke server (bukan
  // dari localStorage seperti kartu lain), jadi begitu sebuah agenda ditandai
  // "selesai" (baik dari tab/halaman Agenda ini atau tab lain), angkanya
  // otomatis berkurang sendiri di dashboard tanpa perlu reload halaman.
  function refreshAgendaAktifCard(){
    fetch('agenda_api.php?action=stat_dashboard', { credentials: 'same-origin' })
      .then(res => res.json())
      .then(data => {
        if(!data || data.ok !== true) return;
        const el = document.getElementById('bsNumAgenda');
        if(el) el.textContent = data.total;
        // sinkronkan ulang donut "Total Seluruh Data" karena salah satu
        // angkanya (Agenda Aktif) baru saja berubah.
        if(typeof updateTotalDataDonut === 'function') updateTotalDataDonut();
      })
      .catch(() => { /* diamkan kalau gagal fetch, angka lama tetap tampil */ });
  }
  refreshAgendaAktifCard();
  setInterval(refreshAgendaAktifCard, 15000); // refresh tiap 15 detik
  // refresh langsung juga begitu user balik ke tab ini, biar nggak nunggu 15 detik
  document.addEventListener('visibilitychange', function(){
    if(document.visibilityState === 'visible') refreshAgendaAktifCard();
  });

  // ---------- KARTU "PERBANDINGAN OPERASIONAL" (podium chart) ----------
  // Membandingkan (bukan menjumlahkan) 3 kategori operasional minggu ini:
  // Rapat, Agenda, Tamu -- ditata seperti podium juara: yang nilainya
  // PALING BESAR otomatis pindah ke tengah & jadi paling tinggi (juara 1),
  // sisanya di kiri/kanan sesuai peringkat. Angka sumbernya dari data-*
  // attribute yang dirender PHP (rentang Minggu Ini), sinkron dengan
  // panel Operasional Harian di atasnya.
  function updateTotalDataDonut(){
    const card = document.getElementById('opCompareCard');
    if(!card) return;

    const values = {
      rapat: parseInt(card.dataset.rapat, 10) || 0,
      agenda: parseInt(card.dataset.agenda, 10) || 0,
      tamu: parseInt(card.dataset.tamu, 10) || 0,
    };
    const labels = { rapat: 'Rapat', agenda: 'Agenda', tamu: 'Tamu' };
    const emojis = { rapat: '🤝', agenda: '🗓️', tamu: '🎟️' };

    // angka di legend bawah
    const numRapatEl = document.getElementById('opcNumRapat');
    const numAgendaEl = document.getElementById('opcNumAgenda');
    const numTamuEl = document.getElementById('opcNumTamu');
    if(numRapatEl) numRapatEl.textContent = values.rapat;
    if(numAgendaEl) numAgendaEl.textContent = values.agenda;
    if(numTamuEl) numTamuEl.textContent = values.tamu;

    const maxVal = Math.max(values.rapat, values.agenda, values.tamu, 1);

    // isi bar horizontal di legend bawah podium
    const bars = { rapat: 'opcBarRapat', agenda: 'opcBarAgenda', tamu: 'opcBarTamu' };
    Object.keys(bars).forEach(key => {
      const bar = document.getElementById(bars[key]);
      if(bar) bar.style.width = `${Math.round((values[key] / maxVal) * 100)}%`;
    });

    // --- ranking buat podium: urut dari nilai terbesar, dasi (tie) dipecah
    // berdasarkan urutan tetap Rapat > Agenda > Tamu supaya stabil ---
    const order = ['rapat', 'agenda', 'tamu'];
    const ranked = [...order].sort((a, b) => values[b] - values[a]);
    const rankOf = {}; ranked.forEach((key, i) => { rankOf[key] = i + 1; });
    const medals = ['🥇', '🥈', '🥉'];

    // tinggi batang podium: proporsional ke nilai vs nilai terbesar,
    // dengan tinggi dasar biar tetap kelihatan meski nilainya 0
    const podEls = {
      rapat: { col: 'opcPodRapat', bar: 'opcPodBarRapat', num: 'opcPodNumRapat', medal: 'opcMedalRapat' },
      agenda: { col: 'opcPodAgenda', bar: 'opcPodBarAgenda', num: 'opcPodNumAgenda', medal: 'opcMedalAgenda' },
      tamu: { col: 'opcPodTamu', bar: 'opcPodBarTamu', num: 'opcPodNumTamu', medal: 'opcMedalTamu' },
    };

    order.forEach(key => {
      const els = podEls[key];
      const colEl = document.getElementById(els.col);
      const numEl = document.getElementById(els.num);
      const medalEl = document.getElementById(els.medal);
      if(numEl) numEl.textContent = values[key];
      if(medalEl) medalEl.textContent = medals[rankOf[key] - 1];

      if(colEl){
        colEl.classList.remove('rank-1', 'rank-2', 'rank-3');
        colEl.classList.add(`rank-${rankOf[key]}`);
        // juara 1 (nilai terbesar) diletakkan di TENGAH podium (order 2),
        // peringkat 2 di kiri (order 1), peringkat 3 di kanan (order 3)
        colEl.style.order = rankOf[key] === 1 ? 2 : (rankOf[key] === 2 ? 1 : 3);
      }
    });

    // animasi batang tumbuh + medali muncul, dikasih jeda dikit biar kelihatan hidup
    requestAnimationFrame(() => {
      setTimeout(() => {
        order.forEach(key => {
          const els = podEls[key];
          const barEl = document.getElementById(els.bar);
          const medalEl = document.getElementById(els.medal);
          const heightPx = 26 + Math.round((values[key] / maxVal) * 68);
          if(barEl) barEl.style.height = `${heightPx}px`;
          if(medalEl) medalEl.classList.add('show');
        });
      }, 120);
    });

    // caption di bawah podium
    const total = values.rapat + values.agenda + values.tamu;
    const captionEl = document.getElementById('opcCaption');
    if(!captionEl) return;
    if(total <= 0){
      captionEl.textContent = '✨ Belum ada aktivitas tercatat minggu ini.';
      return;
    }
    const leaderKey = ranked[0];
    const isTie = (values.rapat === values.agenda && values.agenda === values.tamu);
    captionEl.innerHTML = isTie
      ? '⚖️ Rapat, Agenda &amp; Tamu <b>sama-sama sibuk</b> minggu ini!'
      : `${labels[leaderKey]} paling <b>mendominasi</b> jadwal minggu ini.`;
  }
  updateTotalDataDonut();

  // ---------- CHART "STATISTIK SURAT MINGGUAN" ----------
  // Menggambar ulang bar chart (Surat Masuk vs Surat Keluar, Senin-Minggu)
  // berdasarkan data asli dari halaman Surat Masuk & Surat Keluar (disimpan
  // ke localStorage oleh masing-masing halaman itu setiap kali dibuka).
  // Skala sumbu Y dihitung otomatis mengikuti angka terbesar minggu ini.
  function updateSuratMingguanChart(){
    function readWeekArray(key, fallback){
      try{
        const raw = localStorage.getItem(key);
        if(!raw) return fallback;
        const arr = JSON.parse(raw);
        if(!Array.isArray(arr) || arr.length !== 7) return fallback;
        return arr.map(n => { const v = parseInt(n, 10); return isNaN(v) ? 0 : v; });
      }catch(e){ return fallback; }
    }

    // fallback: angka contoh (dipakai kalau halaman Surat Masuk/Keluar belum pernah dibuka)
    const masuk = readWeekArray('bkjl_tren_surat_masuk_minggu', [12, 22, 4, 8, 0, 4, 10]);
    const keluar = readWeekArray('bkjl_tren_surat_keluar_minggu', [8, 16, 8, 0, 16, 6, 6]);

    // ---- Susun ulang sumbu X berdasar lebar ASLI svg (px), lalu set viewBox
    // selebar itu juga (1 unit = 1 px). Skala X dan Y jadi sama-sama 1:1,
    // jadi garis bisa mentok ke kanan tanpa bikin bar/teks gepeng/melar. ----
    const svgEl = document.getElementById('suratChartSvg');
    const leftPad = 26; // ruang buat label angka sumbu Y + garis vertikal
    let vbW = 600;
    if(svgEl){
      const measuredW = svgEl.getBoundingClientRect().width;
      if(measuredW > 0) vbW = measuredW;
      svgEl.setAttribute('viewBox', `0 0 ${vbW.toFixed(1)} 210`);
    }
    const usableW = Math.max(vbW - leftPad, 10);
    const slotW = usableW / 7;
    const barW = Math.max(Math.min(slotW * 0.24, 16), 8);

    const vLine = document.getElementById('suratAxisVLine');
    if(vLine){ vLine.setAttribute('x1', leftPad); vLine.setAttribute('x2', leftPad); }
    const hLine = document.getElementById('suratAxisHLine');
    if(hLine){ hLine.setAttribute('x1', leftPad); hLine.setAttribute('x2', vbW.toFixed(1)); }
    for(let i = 1; i <= 5; i++){
      const axisLbl = document.getElementById(`suratAxisL${i}`);
      if(axisLbl) axisLbl.setAttribute('x', leftPad - 4);
    }

    const barCenters = [];
    for(let i = 0; i < 7; i++){
      const center = leftPad + slotW * (i + 0.5);
      barCenters.push(center);
      const masukEl = document.getElementById(`suratBarMasuk${i}`);
      if(masukEl){ masukEl.setAttribute('x', (center - barW - 2).toFixed(1)); masukEl.setAttribute('width', barW.toFixed(1)); }
      const keluarEl = document.getElementById(`suratBarKeluar${i}`);
      if(keluarEl){ keluarEl.setAttribute('x', (center + 2).toFixed(1)); keluarEl.setAttribute('width', barW.toFixed(1)); }
      const dayLbl = document.getElementById(`suratDayLabel${i}`);
      if(dayLbl) dayLbl.setAttribute('x', center.toFixed(1));
    }

    const baseline = 180, top = 20; // batas bawah & atas area bar, sama seperti garis sumbu di SVG
    const usableH = baseline - top;

    // --- skala sumbu Y tetap: 20 / 40 / 60 / 80 / 100 (tidak lagi otomatis) ---
    const scaleMax = 100;
    const step = scaleMax / 5; // 20
    const pxPerUnit = usableH / scaleMax;

    for(let i = 1; i <= 5; i++){
      const label = document.getElementById(`suratAxisL${i}`);
      if(label){
        const y = baseline - (step * i) * pxPerUnit;
        label.setAttribute('y', (y + 3).toFixed(1));
        label.textContent = Math.round(step * i);
      }
    }

    // ---- label angka kecil di atas tiap bar yang nilainya > 0, biar gampang dibaca sekilas ----
    const labelGroup = document.getElementById('suratValueLabels');
    if(labelGroup) labelGroup.innerHTML = '';

    for(let i = 0; i < 7; i++){
      const barMasuk = document.getElementById(`suratBarMasuk${i}`);
      if(barMasuk){
        const h = Math.max(masuk[i], 0) * pxPerUnit;
        const y = baseline - h;
        barMasuk.setAttribute('y', y.toFixed(1));
        barMasuk.setAttribute('height', h.toFixed(1));
        if(labelGroup && masuk[i] > 0){
          const x = parseFloat(barMasuk.getAttribute('x')) + parseFloat(barMasuk.getAttribute('width')) / 2;
          const t = document.createElementNS('http://www.w3.org/2000/svg', 'text');
          t.setAttribute('x', x.toFixed(1));
          t.setAttribute('y', (y - 5).toFixed(1));
          t.setAttribute('fill', '#3d7dc4');
          t.textContent = masuk[i];
          labelGroup.appendChild(t);
        }
      }
      const barKeluar = document.getElementById(`suratBarKeluar${i}`);
      if(barKeluar){
        const h = Math.max(keluar[i], 0) * pxPerUnit;
        const y = baseline - h;
        barKeluar.setAttribute('y', y.toFixed(1));
        barKeluar.setAttribute('height', h.toFixed(1));
        if(labelGroup && keluar[i] > 0){
          const x = parseFloat(barKeluar.getAttribute('x')) + parseFloat(barKeluar.getAttribute('width')) / 2;
          const t = document.createElementNS('http://www.w3.org/2000/svg', 'text');
          t.setAttribute('x', x.toFixed(1));
          t.setAttribute('y', (y - 5).toFixed(1));
          t.setAttribute('fill', '#d1568b');
          t.textContent = keluar[i];
          labelGroup.appendChild(t);
        }
      }
    }
  }
  updateSuratMingguanChart();
  let suratResizeTimer = null;
  window.addEventListener('resize', () => {
    clearTimeout(suratResizeTimer);
    suratResizeTimer = setTimeout(updateSuratMingguanChart, 150);
  });

  // ---------- ARSIP CEPAT DARI PANEL OPERASIONAL HARIAN ----------
  // Begitu Rapat/Agenda/Tamu berstatus "Selesai" (atau "Lewat" khusus rapat),
  // tombol "🗂️ Arsip" otomatis muncul di sebelah kanan chip status (lihat
  // idxRenderPanelOperasional() di atas). Klik tombol ini langsung memanggil
  // endpoint proses yang SAMA dengan yang dipakai halaman aslinya masing-masing
  // (proses_rapat.php / agenda_proses.php / tamu_proses.php), jadi datanya
  // benar-benar pindah ke tabel arsip -- bukan cuma disembunyikan di sisi UI.
  // Kartu yang sama bisa muncul 2x di DOM (di view "Minggu Ini" & "Bulan Ini"
  // sekaligus), makanya penghapusan kartu dicari lewat data-*-id supaya kedua
  // salinannya ikut hilang begitu berhasil diarsipkan.
  const IDX_OP_ARSIP_CFG = {
    rapat:  { url: 'proses_rapat.php',  aksi: 'arsipkan_rapat', attr: 'data-rapat-id',  label: 'Rapat',  konfirmasi: 'Arsipkan rapat ini? Nanti akan muncul di halaman Arsip Rapat.' },
    agenda: { url: 'agenda_proses.php', aksi: 'arsipkan',       attr: 'data-agenda-id', label: 'Agenda', konfirmasi: 'Arsipkan agenda ini? Nanti akan muncul di halaman Arsip Agenda.' },
    tamu:   { url: 'tamu_proses.php',   aksi: 'arsip',          attr: 'data-tamu-id',   label: 'Tamu',   konfirmasi: 'Pindahkan data tamu ini ke Arsip Tamu?' },
  };

  function idxOpArchive(jenis, id, btnEl){
    const cfg = IDX_OP_ARSIP_CFG[jenis];
    if(!cfg || !id) return;
    if(!confirm(cfg.konfirmasi)) return;

    if(btnEl) btnEl.disabled = true;
    const fd = new FormData();
    fd.append('aksi', cfg.aksi);
    fd.append('id', id);

    fetch(cfg.url, { method: 'POST', body: fd })
      .then(res => res.json())
      .then(data => {
        const sukses = !!(data && (data.sukses || data.status === 'ok'));
        if(!sukses){
          showToast((data && data.pesan) ? data.pesan : ('Gagal mengarsipkan ' + cfg.label.toLowerCase() + ' ⚠️'));
          if(btnEl) btnEl.disabled = false;
          return;
        }
        document.querySelectorAll('[' + cfg.attr + '="' + id + '"]').forEach(idxOpRemoveCard);
        showToast(cfg.label + ' berhasil diarsipkan 📂');
      })
      .catch(() => {
        showToast('Tidak bisa terhubung ke server ⚠️');
        if(btnEl) btnEl.disabled = false;
      });
  }

  // Hapus satu kartu dari panel Operasional Harian dengan animasi fade-out,
  // lalu perbarui angka "N kegiatan" di header tanggal yang menaunginya (atau
  // hapus header itu kalau kartunya habis), dan munculkan placeholder kosong
  // kalau seluruh blok Rapat/Agenda/Tamu jadi tidak ada isinya lagi.
  function idxOpRemoveCard(card){
    if(!card) return;
    const list = card.closest('.op-cardlist');
    let header = card.previousElementSibling;
    while(header && !header.classList.contains('op-cardlist-datehead')) header = header.previousElementSibling;

    card.style.transition = 'opacity .25s ease, transform .25s ease';
    card.style.opacity = '0';
    card.style.transform = 'scale(.97)';
    setTimeout(() => {
      card.remove();

      if(header){
        let sisa = 0, sib = header.nextElementSibling;
        while(sib && !sib.classList.contains('op-cardlist-datehead')){
          if(sib.classList.contains('op-event-card')) sisa++;
          sib = sib.nextElementSibling;
        }
        if(sisa <= 0){
          header.remove();
        } else {
          const countEl = header.querySelector('.op-date-count');
          if(countEl) countEl.textContent = sisa + ' kegiatan';
        }
      }

      if(list && !list.querySelector('.op-event-card') && !list.querySelector('.op-cardlist-empty')){
        const empty = document.createElement('div');
        empty.className = 'op-cardlist-empty';
        empty.innerHTML = '<span class="op-cardlist-empty-icon">📂</span><span>Semua sudah diarsipkan</span>';
        list.appendChild(empty);
      }
      if(typeof idxSyncOpCardlistHeights === 'function') idxSyncOpCardlistHeights();
    }, 220);
  }

  // ---------- TOGGLE "Minggu Ini" / "Bulan Ini" — PANEL OPERASIONAL HARIAN ----------
  // Kedua set data (rentang minggu & rentang bulan) sudah sama-sama dirender
  // dari PHP sekaligus saat halaman dimuat (lihat idxRenderPanelOperasional()
  // di index.php), jadi switch di sini murni tampil/sembunyi via class
  // ".active" -- instan, tanpa reload / tanpa fetch ulang ke server.
  function idxSwitchOpRange(target){
    document.querySelectorAll('.op-range-view').forEach(el => {
      el.classList.toggle('active', el.getAttribute('data-op-range') === target);
    });
    document.querySelectorAll('.op-range-btn').forEach(btn => {
      const isTarget = btn.getAttribute('data-op-target') === target;
      btn.classList.toggle('active', isTarget);
      if(isTarget){
        const caption = document.getElementById('opRangeCaption');
        if(caption) caption.innerHTML = '<span class="op-range-caption-icon">✨</span><span>Menampilkan jadwal <b>' + btn.getAttribute('data-op-caption') + '</b> — direncanakan, bukan cuma hari ini.</span>';
      }
    });
    idxSyncOpCardlistHeights();
  }

  // ---------- SAMAKAN TINGGI PANEL "Operasional Harian" DENGAN KOLOM SAMPING ----------
  // Supaya ujung bawah panel kiri (Rapat/Agenda/Tamu) sejajar rapi dengan ujung
  // bawah kolom kanan (Jadwal Bentrok + Kalender + Menu Cepat + Perbandingan
  // Operasional) SAAT ketiga daftar (Rapat/Agenda/Tamu) memang sudah cukup
  // panjang untuk masuk mode scroll. Kalau isinya sedikit, kotak dibiarkan
  // rapat/compact apa adanya (tidak dipaksa melar, biar tidak ada ruang kosong).
  //
  // Caranya: ukur dulu tinggi asli tiap kotak (tanpa batas), ukur tinggi kolom
  // kanan, lalu kalau totalnya kepanjangan, batasi (max-height) tiap kotak
  // secukupnya biar totalnya pas menyamai kolom kanan -- tidak kurang dari
  // tinggi minimum supaya tidak kelihatan kekecilan/gepeng.
  function idxSyncOpCardlistHeights(){
    const activeView = document.querySelector('.op-range-view.active');
    const sideCol = document.querySelector('.dash-grid-op .side-col');
    const opPanel = document.querySelector('.dash-grid-op .op-panel');
    if(!activeView || !sideCol || !opPanel) return;

    const boxes = Array.from(activeView.querySelectorAll(':scope > .op-block > .op-cardlist-scroll'));
    if(boxes.length === 0) return;

    const MIN_BOX_HEIGHT = 180;   // batas bawah biar kotak tidak kelihatan kekecilan/gepeng
    const DEFAULT_MAX = 352;      // fallback kalau pengukuran gagal / kolom kanan lebih pendek

    // reset dulu ke natural (tanpa cap) supaya bisa diukur tinggi aslinya
    boxes.forEach(box => { box.style.maxHeight = 'none'; });

    const naturalHeights = boxes.map(box => box.scrollHeight);
    const naturalPanelHeight = opPanel.offsetHeight;
    const sideColHeight = sideCol.offsetHeight;

    const excess = naturalPanelHeight - sideColHeight;

    if(!(excess > 0) || !isFinite(sideColHeight) || sideColHeight <= 0){
      // Kolom kiri sudah muat / lebih pendek dari kolom kanan -- biarkan
      // rapat/compact apa adanya, tidak perlu dipotong sama sekali.
      boxes.forEach(box => { box.style.maxHeight = DEFAULT_MAX + 'px'; box.style.height = ''; });
      return;
    }

    // Bagi rata kelebihan tinggi ke 3 kotak, tapi jangan sampai ada yang
    // di bawah MIN_BOX_HEIGHT -- sisa kelebihannya dilempar ke kotak lain
    // yang masih punya ruang (biar total tetap sejajar semaksimal mungkin).
    let remainingExcess = excess;
    const caps = naturalHeights.map(h => h);
    let pool = boxes.map((_, i) => i);

    while(remainingExcess > 0.5 && pool.length > 0){
      const share = remainingExcess / pool.length;
      const nextPool = [];
      let reducedThisRound = 0;
      pool.forEach(i => {
        const proposed = caps[i] - share;
        if(proposed < MIN_BOX_HEIGHT){
          reducedThisRound += Math.max(0, caps[i] - MIN_BOX_HEIGHT);
          caps[i] = MIN_BOX_HEIGHT;
        } else {
          caps[i] = proposed;
          reducedThisRound += share;
          nextPool.push(i);
        }
      });
      remainingExcess -= reducedThisRound;
      if(nextPool.length === pool.length) break; // hindari loop tak berujung
      pool = nextPool;
    }

    boxes.forEach((box, i) => {
      box.style.height = '';
      box.style.maxHeight = Math.max(MIN_BOX_HEIGHT, Math.round(caps[i])) + 'px';
    });
  }

  window.addEventListener('load', idxSyncOpCardlistHeights);
  window.addEventListener('resize', () => {
    clearTimeout(window.__opSyncResizeT);
    window.__opSyncResizeT = setTimeout(idxSyncOpCardlistHeights, 150);
  });

  // ---------- INIT (aman dipanggil di semua halaman) ----------
  idxAtkRenderInventory();
  if(document.getElementById('todoList')) renderTodoList();
  if(document.querySelector('.op-panel')) setTimeout(idxSyncOpCardlistHeights, 0);

  // ---------- SPLASH / INTRO ----------
  window.addEventListener('load', () => {
    setTimeout(() => {
      const splash = document.getElementById('splash');
      const app = document.querySelector('.app');
      document.documentElement.classList.remove('loading');
      app.classList.add('show');
      splash.classList.add('hide');
      setTimeout(() => splash.remove(), 600);
      requestAnimationFrame(syncCardHeights);
    }, 1300);
  });

</script>
</body>
</html>