<?php
/**
 * components/dashboard/charts.php — Grafik & persebaran data kuesioner SMD.
 *
 * Dipanggil dari dashboard.php (setelah stats.php) dan memakai variabel di sana:
 *   $rows        data yang SUDAH terfilter (grafik ikut berubah mengikuti filter)
 *   $selectedKel kelurahan yang sedang difilter
 *
 * Murni PHP + CSS: tanpa library JavaScript dan tanpa internet.
 * Semua angka tampil sebagai teks, jadi warna tidak pernah jadi satu-satunya petunjuk.
 */

if (!function_exists('dc_h')) {
    function dc_h($s): string
    {
        return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
    }
    function dc_val(array $r, string $f): string
    {
        $v = $r['data'][$f] ?? '';
        return is_array($v) ? '' : trim((string)$v);
    }
    /** [jumlah jawaban yang cocok, jumlah yang menjawab] */
    function dc_rate(array $rows, string $f, array $match): array
    {
        $hit = 0;
        $n = 0;
        foreach ($rows as $r) {
            $v = dc_val($r, $f);
            if ($v === '') {
                continue;
            }
            $n++;
            if (in_array($v, $match, true)) {
                $hit++;
            }
        }
        return [$hit, $n];
    }
    function dc_pct(int $a, int $b): float
    {
        return $b > 0 ? $a * 100 / $b : 0.0;
    }
    function dc_url(array $over): string
    {
        $q = [];
        foreach ($_GET as $k => $v) {
            if (is_string($v) && $v !== '' && !in_array($k, ['msg', 'err'], true)) {
                $q[$k] = $v;
            }
        }
        foreach ($over as $k => $v) {
            if ($v === '') {
                unset($q[$k]);
            } else {
                $q[$k] = $v;
            }
        }
        return 'dashboard.php' . ($q ? '?' . http_build_query($q) : '');
    }
    function dc_heat_step(float $p): string
    {
        if ($p < 10) return 's1';
        if ($p < 25) return 's2';
        if ($p < 50) return 's3';
        if ($p < 75) return 's4';
        return 's5';
    }
    /** Hitung sebaran satu isian: [label => jumlah] (hanya yang menjawab). */
    function dc_dist(array $rows, string $f, array $order = []): array
    {
        $out = [];
        foreach ($order as $o) {
            $out[$o] = 0;
        }
        foreach ($rows as $r) {
            $v = dc_val($r, $f);
            if ($v === '') {
                continue;
            }
            $out[$v] = ($out[$v] ?? 0) + 1;
        }
        return $out;
    }
    /**
     * Satu baris batang. $w = lebar batang 0–100.
     * $href kosong = bukan tautan.
     */
    function dc_row(string $label, float $w, string $num, string $note = '', string $tone = 'a', string $href = '', bool $active = false, string $tag = ''): string
    {
        $w = max(0, min(100, $w));
        $fill = '<span class="dc-track"><span class="dc-fill dc-' . $tone . '" style="width:' . round($w, 1) . '%"></span></span>';
        $lab = '<span class="dc-lab">' . dc_h($label) . ($tag !== '' ? ' <span class="dc-tag">' . dc_h($tag) . '</span>' : '') . '</span>';
        $val = '<span class="dc-num"><b>' . dc_h($num) . '</b>' . ($note !== '' ? '<small>' . dc_h($note) . '</small>' : '') . '</span>';
        $inner = $lab . $fill . $val;
        if ($href !== '') {
            return '<li class="dc-row' . ($active ? ' is-active' : '') . '"><a class="dc-line" href="' . dc_h($href) . '" title="' . ($active ? 'Klik untuk menghapus filter' : 'Klik untuk menyaring data ke ' . dc_h($label)) . '">' . $inner . '</a></li>';
        }
        return '<li class="dc-row"><div class="dc-line">' . $inner . '</div></li>';
    }
    /** Daftar batang dari sebaran jumlah. */
    function dc_dist_list(array $dist, int $total): string
    {
        $max = $dist ? max($dist) : 0;
        $h = '<ul class="dc-list">';
        foreach ($dist as $label => $c) {
            $h .= dc_row((string)$label, $max > 0 ? $c * 100 / $max : 0, (string)$c, $total > 0 ? round($c * 100 / $total) . '%' : '');
        }
        return $h . '</ul>';
    }
    /** Daftar indikator persentase. $items = [[label, field, [nilai cocok]], ...]. $low = true: persentase kecil = perlu perhatian. */
    function dc_rate_list(array $rows, array $items, string $tone, bool $lowIsBad): array
    {
        $calc = [];
        foreach ($items as $it) {
            [$hit, $n] = dc_rate($rows, $it[1], $it[2]);
            $calc[] = ['label' => $it[0], 'hit' => $hit, 'n' => $n, 'pct' => dc_pct($hit, $n)];
        }
        // yang paling perlu perhatian di atas; yang belum ada datanya di bawah
        usort($calc, function ($a, $b) use ($lowIsBad) {
            if (($a['n'] > 0) !== ($b['n'] > 0)) {
                return $a['n'] > 0 ? -1 : 1;
            }
            return $lowIsBad ? $a['pct'] <=> $b['pct'] : $b['pct'] <=> $a['pct'];
        });
        $h = '<ul class="dc-list">';
        foreach ($calc as $c) {
            if ($c['n'] === 0) {
                $h .= dc_row($c['label'], 0, '–', 'belum ada data', $tone);
                continue;
            }
            $note = $c['hit'] . ' dari ' . $c['n'] . ($c['n'] < 5 ? ' · data sedikit' : '');
            $tag = ($lowIsBad && $c['pct'] < 50 && $c['n'] >= 5) ? 'Perlu perhatian' : '';
            $h .= dc_row($c['label'], $c['pct'], round($c['pct']) . '%', $note, $tone, '', false, $tag);
        }
        return ['html' => $h . '</ul>', 'calc' => $calc];
    }
}

$total = count($rows);
$validN = 0;
foreach ($rows as $r) {
    if (($r['status'] ?? '') === 'valid') {
        $validN++;
    }
}

/* ---------- Definisi indikator ---------- */
$KEL_ORDER = ['Kebon Jeruk', 'Duri Kepa', 'Kedoya Selatan', 'Kedoya Utara', 'Sukabumi Selatan', 'Sukabumi Utara', 'Kelapa Dua'];

$KONDISI = [
    ['Hipertensi (darah tinggi)', 'hipertensi', ['Ya']],
    ['Diabetes melitus (kencing manis)', 'diabetes', ['Ya']],
    ['Masalah kejiwaan', 'kejiwaan', ['Ya']],
    ['TB (tuberkulosis)', 'diagnosa_tb', ['Ya']],
    ['Ada kebiasaan merokok di keluarga', 'merokok', ['Ya']],
    ['Pernah mengalami kekerasan di rumah tangga', 'kekerasan', ['Pernah']],
];
$IBU_ANAK = [
    ['Periksa kehamilan ke fasilitas kesehatan', 'hamil_cek', ['Ya']],
    ['Melahirkan di fasilitas kesehatan', 'melahirkan_tempat', ['Ya']],
    ['Pernah deteksi dini kanker serviks', 'iva_papsmear', ['Ya']],
    ['Usia subur yang memakai KB', 'kontrasepsi', ['Ya']],
    ['Anak imunisasi dasar lengkap', 'idl', ['Ya']],
    ['Anak ASI eksklusif', 'asi', ['Ya']],
    ['Tumbuh kembang anak dipantau', 'pantau', ['Ya']],
    ['Anak sudah imunisasi MR/Dt/Td di SD', 'imunisasi_sekolah', ['Sudah']],
];
$PHBS = [
    ['Semua anggota keluarga punya JKN/BPJS', 'jaminan', ['Ya']],
    ['Punya sarana air bersih', 'air_bersih', ['Ya']],
    ['Punya jamban sehat', 'jamban', ['Ya']],
    ['Terbiasa cuci tangan pakai sabun', 'cuci_tangan', ['Sudah']],
    ['Terbiasa aktivitas fisik', 'fisik', ['Sudah']],
    ['Terbiasa makan buah dan sayur', 'buah', ['Sudah']],
    ['Memakai masker saat batuk/sakit', 'masker', ['Selalu', 'Sering']],
];
$LAYANAN = [
    ['Tahu tempat Cek Kesehatan Gratis (CKG)', 'ckg_tau', ['Tahu']],
    ['Sudah melakukan CKG', 'ckg_lakukan', ['Ya']],
    ['Tahu kematian wajar harus dilapor (SKPK)', 'skpk', ['Tahu']],
    ['Tahu syarat Surat Melapor Kematian (SKMK)', 'skmk', ['Tahu']],
    ['Sudah pernah dengar Puskesmas ILP', 'ilp_tahu', ['Sudah']],
    ['Menilai Puskesmas sudah menerapkan ILP dengan baik', 'ilp_kinerja', ['Sudah']],
    ['Menilai penyandang disabilitas mudah dilayani', 'disabilitas', ['Ya']],
];
/* Kolom peta persebaran: persentase warga yang BERMASALAH (makin tinggi = makin perlu perhatian) */
$HEAT = [
    ['Hipertensi', 'hipertensi', ['Ya']],
    ['Diabetes', 'diabetes', ['Ya']],
    ['TB', 'diagnosa_tb', ['Ya']],
    ['Masalah jiwa', 'kejiwaan', ['Ya']],
    ['Merokok', 'merokok', ['Ya']],
    ['Belum punya JKN', 'jaminan', ['Tidak']],
    ['Tanpa jamban sehat', 'jamban', ['Tidak']],
    ['Belum CKG', 'ckg_lakukan', ['Belum']],
    ['Belum cuci tangan', 'cuci_tangan', ['Belum']],
];

/* ---------- Hitungan ---------- */
$kelDist = dc_dist($rows, 'kelurahan', $KEL_ORDER);
$kelTotalAnswered = array_sum($kelDist);

$gender = dc_dist($rows, 'jenis_kelamin', ['Laki-Laki', 'Perempuan']);
$genderTotal = array_sum($gender);

$ageBuckets = ['Di bawah 18' => 0, '18–29' => 0, '30–44' => 0, '45–59' => 0, '60 ke atas' => 0];
foreach ($rows as $r) {
    $u = dc_val($r, 'usia');
    if ($u === '' || !ctype_digit($u)) {
        continue;
    }
    $u = (int)$u;
    if ($u < 18) {
        $ageBuckets['Di bawah 18']++;
    } elseif ($u < 30) {
        $ageBuckets['18–29']++;
    } elseif ($u < 45) {
        $ageBuckets['30–44']++;
    } elseif ($u < 60) {
        $ageBuckets['45–59']++;
    } else {
        $ageBuckets['60 ke atas']++;
    }
}
$ageTotal = array_sum($ageBuckets);

$edu = dc_dist($rows, 'pendidikan', ['Tidak Sekolah', 'SD', 'SMP', 'SMA', 'DIII', 'S1', 'S2', 'S3']);
$job = dc_dist($rows, 'pekerjaan');
arsort($job);

/* Pengisian per hari (14 hari terakhir) */
$days = [];
for ($i = 13; $i >= 0; $i--) {
    $days[date('Y-m-d', strtotime("-$i day"))] = 0;
}
foreach ($rows as $r) {
    $d = substr((string)($r['waktu'] ?? ''), 0, 10);
    if (isset($days[$d])) {
        $days[$d]++;
    }
}
$dayMax = max($days) ?: 0;
$dayTotal = array_sum($days);

/* Sorotan otomatis */
$topKel = '';
$topKelN = 0;
foreach ($kelDist as $k => $c) {
    if ($c > $topKelN) {
        $topKel = $k;
        $topKelN = $c;
    }
}
$kondisiRes = dc_rate_list($rows, $KONDISI, 'b', false);
$ibuRes = dc_rate_list($rows, $IBU_ANAK, 'a', true);
$phbsRes = dc_rate_list($rows, $PHBS, 'a', true);
$layRes = dc_rate_list($rows, $LAYANAN, 'a', true);

$topKondisi = null;
foreach ($kondisiRes['calc'] as $c) {
    if ($c['n'] >= 5 && $c['hit'] > 0 && ($topKondisi === null || $c['pct'] > $topKondisi['pct'])) {
        $topKondisi = $c;
    }
}
$lowest = null;
foreach (array_merge($ibuRes['calc'], $phbsRes['calc'], $layRes['calc']) as $c) {
    if ($c['n'] >= 5 && ($lowest === null || $c['pct'] < $lowest['pct'])) {
        $lowest = $c;
    }
}

/* Peta persebaran per kelurahan */
$heatKels = $KEL_ORDER;
foreach ($kelDist as $k => $_c) {
    if (!in_array($k, $heatKels, true)) {
        $heatKels[] = $k;
    }
}
?>
<style>
.dc{--dc-a:#257de0;--dc-b:#f47230;--dc-ink:#191919;--dc-mut:#5b6470;--dc-line:#e3e7ec;--dc-bg:#f6f8fa;
  margin:0 0 20px;color:var(--dc-ink)}
.dc *{box-sizing:border-box}
.dc>summary{list-style:none;cursor:pointer;display:flex;align-items:center;justify-content:space-between;gap:12px;
  padding:12px 16px;background:#fff;border:1px solid var(--dc-line);border-radius:10px;font-weight:700;font-size:15px}
.dc>summary::-webkit-details-marker{display:none}
.dc>summary small{font-weight:400;color:var(--dc-mut);font-size:13px}
.dc>summary::after{content:"Sembunyikan";font-size:12px;font-weight:600;color:var(--dc-a);border:1px solid var(--dc-a);border-radius:6px;padding:3px 10px}
.dc:not([open])>summary::after{content:"Tampilkan"}
.dc-body{margin-top:14px;display:grid;gap:16px}
.dc-howto{margin:0;font-size:13px;color:var(--dc-mut)}
.dc-howto b{color:var(--dc-ink)}

.dc-kpis{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px}
.dc-kpi{background:#fff;border:1px solid var(--dc-line);border-radius:10px;padding:14px 16px}
.dc-kpi .big{display:block;font-size:26px;font-weight:700;line-height:1.15}
.dc-kpi .lbl{display:block;margin-top:4px;font-size:13px;color:var(--dc-mut)}
.dc-kpi .sub{display:block;margin-top:6px;font-size:12px;color:var(--dc-mut)}

.dc-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(340px,1fr));gap:16px}
.dc-card{background:#fff;border:1px solid var(--dc-line);border-radius:10px;padding:16px;min-width:0}
.dc-card.wide{grid-column:1/-1}
.dc-card h3{margin:0;font-size:15px;font-weight:700}
.dc-card .why{margin:2px 0 14px;font-size:12.5px;color:var(--dc-mut)}
.dc-h2{margin:6px 0 -4px;font-size:13px;letter-spacing:.04em;text-transform:uppercase;color:var(--dc-mut);font-weight:700}

.dc-list{list-style:none;margin:0;padding:0;display:grid;gap:8px}
.dc-row .dc-line{display:grid;grid-template-columns:minmax(120px,1.25fr) minmax(70px,1fr) 84px;gap:6px 12px;align-items:center;
  padding:4px 6px;border-radius:6px;color:inherit;text-decoration:none}
.dc-row a.dc-line:hover{background:var(--dc-bg)}
.dc-row.is-active a{background:#eaf2fc;outline:2px solid var(--dc-a)}
.dc-lab{font-size:13.5px;line-height:1.3}
.dc-tag{display:inline-block;margin-left:4px;padding:1px 6px;border-radius:4px;background:#fdece3;color:#8a3a10;font-size:11px;font-weight:700;white-space:nowrap}
.dc-track{height:12px;background:#edf0f3;border-radius:3px;overflow:hidden}
.dc-fill{display:block;height:100%;border-radius:0 4px 4px 0;min-width:2px}
.dc-a{background:var(--dc-a)}
.dc-b{background:var(--dc-b)}
.dc-num{text-align:right;font-size:13.5px;white-space:nowrap}
.dc-num small{display:block;font-size:11px;color:var(--dc-mut)}
@media(max-width:520px){
  .dc-row .dc-line{grid-template-columns:1fr auto}
  .dc-track{grid-column:1/-1;order:3}
  .dc-grid{grid-template-columns:1fr}
}

.dc-stack{display:flex;gap:2px;height:30px;border-radius:6px;overflow:hidden}
.dc-stack span{display:flex;align-items:center;justify-content:center;color:#fff;font-size:13px;font-weight:700;min-width:0}
.dc-stack .g1{background:#1f69c2}
.dc-stack .g2{background:#b4470f}
.dc-legend{display:flex;flex-wrap:wrap;gap:6px 18px;margin:10px 0 0;padding:0;list-style:none;font-size:13px}
.dc-legend i{display:inline-block;width:10px;height:10px;border-radius:2px;margin-right:6px}

.dc-cols{display:flex;align-items:flex-end;gap:6px;height:150px;padding-top:18px}
.dc-col{flex:1;display:flex;flex-direction:column;justify-content:flex-end;align-items:center;height:100%;min-width:0}
.dc-col b{font-size:11px;margin-bottom:2px;font-weight:600}
.dc-col span{display:block;width:100%;max-width:28px;background:var(--dc-a);border-radius:4px 4px 0 0;min-height:2px}
.dc-col.zero span{background:#d5dae0}
.dc-days{display:flex;gap:6px;margin-top:4px}
.dc-days i{flex:1;text-align:center;font-size:10.5px;font-style:normal;color:var(--dc-mut);min-width:0}
.dc-days i.today{color:var(--dc-ink);font-weight:700}

.dc-scroll{overflow-x:auto}
.dc-heat{border-collapse:separate;border-spacing:2px;width:100%;min-width:640px;font-size:13px}
.dc-heat th,.dc-heat td{min-width:68px}
.dc-heat td.k{min-width:130px}
.dc-heat th{font-weight:600;font-size:12px;color:var(--dc-mut);padding:4px 6px;text-align:center;vertical-align:bottom}
.dc-heat th:first-child{text-align:left}
.dc-heat td{text-align:center;padding:8px 4px;border-radius:4px;font-weight:600}
.dc-heat td.k{text-align:left;font-weight:600;background:none;padding-left:6px;white-space:nowrap}
.dc-heat td.n{background:none;font-weight:400;color:var(--dc-mut)}
.dc-heat td.na{color:#8a929c;background:#f3f5f7;font-weight:400}
.dc-heat .s1{background:#eaf2fc;color:#191919}
.dc-heat .s2{background:#bcd8f6;color:#191919}
.dc-heat .s3{background:#74aeed;color:#0b1f33}
.dc-heat .s4{background:#1f69c2;color:#fff}
.dc-heat .s5{background:#164c8f;color:#fff}
.dc-heat td small{display:block;font-weight:400;font-size:10.5px;opacity:.85}
.dc-key{display:flex;flex-wrap:wrap;gap:6px 14px;align-items:center;margin:12px 0 0;font-size:12px;color:var(--dc-mut)}
.dc-key i{display:inline-block;width:28px;height:14px;border-radius:3px;vertical-align:middle;margin-right:4px}
.dc-empty{padding:28px;text-align:center;background:#fff;border:1px dashed var(--dc-line);border-radius:10px;color:var(--dc-mut)}
</style>

<details class="dc" open>
    <summary><span>Grafik &amp; persebaran data <small>— mengikuti filter di bawah</small></span></summary>

<?php if ($total === 0): ?>
    <div class="dc-body"><div class="dc-empty">Belum ada data untuk ditampilkan dengan filter ini. Ubah atau hapus filter di bawah.</div></div>
<?php else: ?>
    <div class="dc-body">
        <p class="dc-howto">Cara membaca: <b>batang panjang = persentase / jumlah besar</b>. Angka selalu ditulis di samping batang.
            Persentase dihitung dari warga yang <b>menjawab</b> pertanyaan itu, jadi jumlahnya bisa berbeda tiap pertanyaan.</p>

        <!-- ===== Sorotan ===== -->
        <div class="dc-kpis">
            <div class="dc-kpi"><span class="big"><?php echo $total; ?></span><span class="lbl">responden ditampilkan</span>
                <span class="sub"><?php echo $validN; ?> valid (<?php echo round(dc_pct($validN, $total)); ?>%) · <?php echo $total - $validN; ?> draft</span></div>
            <div class="dc-kpi"><span class="big"><?php echo $topKel !== '' ? dc_h($topKel) : '–'; ?></span><span class="lbl">kelurahan dengan responden terbanyak</span>
                <span class="sub"><?php echo $topKel !== '' ? $topKelN . ' responden (' . round(dc_pct($topKelN, $kelTotalAnswered)) . '%)' : 'Belum ada data kelurahan'; ?></span></div>
            <div class="dc-kpi"><span class="big"><?php echo $topKondisi ? dc_h(round($topKondisi['pct'])) . '%' : '–'; ?></span>
                <span class="lbl"><?php echo $topKondisi ? 'paling sering: ' . dc_h($topKondisi['label']) : 'kondisi kesehatan tersering'; ?></span>
                <span class="sub"><?php echo $topKondisi ? $topKondisi['hit'] . ' dari ' . $topKondisi['n'] . ' warga' : 'Belum cukup data (min. 5 jawaban)'; ?></span></div>
            <div class="dc-kpi"><span class="big"><?php echo $lowest ? dc_h(round($lowest['pct'])) . '%' : '–'; ?></span>
                <span class="lbl"><?php echo $lowest ? 'capaian terendah: ' . dc_h($lowest['label']) : 'indikator capaian terendah'; ?></span>
                <span class="sub"><?php echo $lowest ? $lowest['hit'] . ' dari ' . $lowest['n'] . ' warga' : 'Belum cukup data (min. 5 jawaban)'; ?></span></div>
        </div>

        <!-- ===== Persebaran wilayah ===== -->
        <h2 class="dc-h2">1. Persebaran wilayah</h2>
        <div class="dc-grid">
            <section class="dc-card">
                <h3>Responden per kelurahan</h3>
                <p class="why">Di mana data terkumpul. Klik sebuah kelurahan untuk menyaring tabel; klik lagi untuk melepas.</p>
                <ul class="dc-list">
                <?php
                $kMax = $kelDist ? max($kelDist) : 0;
                foreach ($kelDist as $k => $c) {
                    $active = strcasecmp($selectedKel, (string)$k) === 0;
                    echo dc_row((string)$k, $kMax > 0 ? $c * 100 / $kMax : 0, (string)$c, $kelTotalAnswered > 0 ? round($c * 100 / $kelTotalAnswered) . '%' : '', 'a', dc_url(['kelurahan' => $active ? '' : (string)$k]), $active);
                }
                ?>
                </ul>
            </section>

            <section class="dc-card">
                <h3>Pengisian 14 hari terakhir</h3>
                <p class="why">Untuk memantau kecepatan pendataan. Total <?php echo $dayTotal; ?> kuesioner pada periode ini.</p>
                <div class="dc-cols" role="img" aria-label="Jumlah kuesioner per hari selama 14 hari terakhir, total <?php echo $dayTotal; ?>">
                <?php foreach ($days as $d => $c): ?>
                    <div class="dc-col<?php echo $c === 0 ? ' zero' : ''; ?>" title="<?php echo dc_h(date('d/m/Y', strtotime($d))) . ': ' . $c; ?> kuesioner">
                        <b><?php echo $c > 0 ? $c : ''; ?></b>
                        <span style="height:<?php echo $dayMax > 0 ? max(2, round($c * 100 / $dayMax * 0.78)) : 2; ?>%"></span>
                    </div>
                <?php endforeach; ?>
                </div>
                <div class="dc-days" aria-hidden="true">
                <?php foreach ($days as $d => $c): ?>
                    <i class="<?php echo $d === date('Y-m-d') ? 'today' : ''; ?>"><?php echo date('d', strtotime($d)); ?></i>
                <?php endforeach; ?>
                </div>
                <p class="why" style="margin:8px 0 0">Angka di bawah batang = tanggal. Hari ini ditebalkan.</p>
            </section>

            <section class="dc-card wide">
                <h3>Peta persebaran masalah per kelurahan</h3>
                <p class="why">Setiap kotak = persentase warga di kelurahan itu yang <b>bermasalah</b> pada hal tersebut. <b>Makin gelap = makin perlu perhatian.</b>
                    Angka kecil di bawah persentase menunjukkan jumlah warga yang menjawab. Kotak abu-abu = belum ada jawaban.</p>
                <div class="dc-scroll">
                <table class="dc-heat">
                    <thead>
                        <tr><th scope="col">Kelurahan</th><th scope="col">Responden</th>
                        <?php foreach ($HEAT as $h): ?><th scope="col"><?php echo dc_h($h[0]); ?></th><?php endforeach; ?></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($heatKels as $kel):
                        $krows = array_values(array_filter($rows, function ($r) use ($kel) {
                            return strcasecmp(dc_val($r, 'kelurahan'), $kel) === 0;
                        })); ?>
                        <tr>
                            <td class="k"><?php echo dc_h($kel); ?></td>
                            <td class="n"><?php echo count($krows); ?></td>
                        <?php foreach ($HEAT as $h):
                            [$hit, $n] = dc_rate($krows, $h[1], $h[2]);
                            if ($n === 0): ?>
                            <td class="na" title="Belum ada jawaban">–</td>
                            <?php else:
                                $p = dc_pct($hit, $n); ?>
                            <td class="<?php echo dc_heat_step($p); ?>" title="<?php echo dc_h($h[0] . ' — ' . $kel . ': ' . $hit . ' dari ' . $n . ' warga'); ?>">
                                <?php echo round($p); ?>%<small>dari <?php echo $n; ?></small>
                            </td>
                            <?php endif;
                        endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
                <div class="dc-key" aria-hidden="true">
                    <span>Persentase bermasalah:</span>
                    <span><i style="background:#eaf2fc"></i>&lt;10%</span>
                    <span><i style="background:#bcd8f6"></i>10–24%</span>
                    <span><i style="background:#74aeed"></i>25–49%</span>
                    <span><i style="background:#1f69c2"></i>50–74%</span>
                    <span><i style="background:#164c8f"></i>≥75%</span>
                </div>
                <p class="why" style="margin:8px 0 0">Hati-hati membaca kelurahan dengan responden sedikit: satu orang bisa mengubah persentase sangat jauh.</p>
            </section>
        </div>

        <!-- ===== Profil responden ===== -->
        <h2 class="dc-h2">2. Siapa saja respondennya</h2>
        <div class="dc-grid">
            <section class="dc-card">
                <h3>Jenis kelamin</h3>
                <p class="why">Perbandingan responden laki-laki dan perempuan.</p>
                <?php if ($genderTotal > 0):
                    $lk = $gender['Laki-Laki'] ?? 0;
                    $pr = $gender['Perempuan'] ?? 0; ?>
                    <div class="dc-stack" role="img" aria-label="Laki-laki <?php echo $lk; ?>, perempuan <?php echo $pr; ?>">
                        <?php if ($lk > 0): ?><span class="g1" style="flex:<?php echo $lk; ?>"><?php echo round(dc_pct($lk, $genderTotal)); ?>%</span><?php endif; ?>
                        <?php if ($pr > 0): ?><span class="g2" style="flex:<?php echo $pr; ?>"><?php echo round(dc_pct($pr, $genderTotal)); ?>%</span><?php endif; ?>
                    </div>
                    <ul class="dc-legend">
                        <li><i style="background:#1f69c2"></i>Laki-laki: <b><?php echo $lk; ?></b> orang</li>
                        <li><i style="background:#b4470f"></i>Perempuan: <b><?php echo $pr; ?></b> orang</li>
                    </ul>
                <?php else: ?><p class="why">Belum ada data.</p><?php endif; ?>
            </section>

            <section class="dc-card">
                <h3>Kelompok usia</h3>
                <p class="why">Sebaran umur responden.</p>
                <?php echo dc_dist_list($ageBuckets, $ageTotal); ?>
            </section>

            <section class="dc-card">
                <h3>Pendidikan terakhir</h3>
                <p class="why">Urut dari rendah ke tinggi.</p>
                <?php echo dc_dist_list($edu, array_sum($edu)); ?>
            </section>

            <section class="dc-card">
                <h3>Pekerjaan</h3>
                <p class="why">Urut dari yang terbanyak.</p>
                <?php echo dc_dist_list($job, array_sum($job)); ?>
            </section>
        </div>

        <!-- ===== Kondisi kesehatan ===== -->
        <h2 class="dc-h2">3. Kondisi kesehatan warga</h2>
        <div class="dc-grid">
            <section class="dc-card wide">
                <h3>Masalah kesehatan yang dialami responden atau keluarganya</h3>
                <p class="why">Persentase yang menjawab "Ya" / "Pernah". Makin panjang batang, makin banyak keluarga yang mengalaminya.</p>
                <?php echo $kondisiRes['html']; ?>
            </section>
        </div>

        <!-- ===== Capaian ===== -->
        <h2 class="dc-h2">4. Capaian perilaku sehat &amp; layanan</h2>
        <p class="dc-howto">Di bagian ini <b>batang panjang = baik</b>. Indikator dengan capaian di bawah 50% diberi tanda <b>Perlu perhatian</b> dan diletakkan paling atas.</p>
        <div class="dc-grid">
            <section class="dc-card">
                <h3>Ibu dan anak</h3>
                <p class="why">Kehamilan, persalinan, imunisasi, dan tumbuh kembang.</p>
                <?php echo $ibuRes['html']; ?>
            </section>
            <section class="dc-card">
                <h3>Lingkungan &amp; pola hidup sehat</h3>
                <p class="why">Jaminan kesehatan, air, jamban, kebiasaan harian.</p>
                <?php echo $phbsRes['html']; ?>
            </section>
            <section class="dc-card wide">
                <h3>Layanan Puskesmas &amp; pengetahuan warga</h3>
                <p class="why">Sejauh mana warga tahu dan memakai layanan yang tersedia.</p>
                <?php echo $layRes['html']; ?>
            </section>
        </div>
    </div>
<?php endif; ?>
</details>