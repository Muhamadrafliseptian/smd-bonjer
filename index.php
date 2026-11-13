<?php
/**
 * form.php — Form pengisian kuesioner SMD (publik, tanpa login).
 * - Disimpan ke data/kuesioner.json lewat db_all()/db_save().
 * - Satu bagian (A–G) per layar, dengan tombol Kembali / Lanjut.
 * - Hide & seek: pertanyaan lanjutan muncul begitu pertanyaan pemicunya dijawab.
 *   Pertanyaan yang tersembunyi tidak ikut terkirim.
 * - Tanpa JavaScript, semua bagian tampil berurutan dan form tetap bisa dikirim.
 * - Mengisi ulang / mengubah data lama (edit_id) hanya untuk admin yang sudah login.
 */
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/view.php';
require_once __DIR__ . '/database.php';

$isAdmin = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;

$err = '';
$saved = false;
$savedNew = true;
$kurang = [];
$wajib = ['inisial', 'alamat', 'jenis_kelamin', 'pendidikan', 'pekerjaan', 'tgl_wawancara'];
$wajibLabel = [
    'inisial' => 'inisial',
    'alamat' => 'alamat',
    'jenis_kelamin' => 'jenis kelamin',
    'pendidikan' => 'pendidikan',
    'pekerjaan' => 'pekerjaan',
    'tgl_wawancara' => 'tanggal wawancara',
];

$D = [];       // nilai untuk mengisi ulang form
$formId = '';  // id record yang sedang diubah (hanya admin)

/* ---------- Simpan ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $now = date('Y-m-d H:i:s');
    $clean = [];
    foreach ($_POST as $k => $v) {
        if ($k === 'aksi') {
            continue;
        }
        if (is_array($v)) {
            $vals = array_values(array_filter(array_map('trim', $v), 'strlen'));
            if (count($vals) === 0) {
                continue;
            }
            $clean[$k] = $vals;
        } else {
            $s = trim((string)$v);
            if ($s === '') {
                continue;
            }
            $clean[$k] = $s;
        }
    }
    // Hanya admin yang boleh menimpa data lama; pengunjung selalu membuat data baru.
    $id = ($isAdmin && !empty($clean['id'])) ? (string)$clean['id'] : 'smd_' . uniqid('', true);
    unset($clean['id']);

    $kurang = array_values(array_filter($wajib, function ($f) use ($clean) {
        return !isset($clean[$f]) || $clean[$f] === '';
    }));
    $status = $kurang ? 'draft' : 'valid';

    $record = [
        'id' => $id,
        'waktu' => $now,
        'status' => $status,
        'data' => $clean,
    ];

    $all = db_all();
    $found = false;
    $out = [];
    foreach ($all as $r) {
        if (isset($r['id']) && $r['id'] === $id) {
            $rec = $record;
            $rec['waktu'] = $r['waktu'] ?? $now;
            $rec['diubah'] = $now;
            $out[] = $rec;
            $found = true;
        } else {
            $out[] = $r;
        }
    }
    if (!$found) {
        $out[] = $record;
    }
    $ok = db_save(['surveys' => $out]);

    $D = $clean;
    $formId = $isAdmin ? $id : '';
    if ($ok) {
        $saved = true;
        $savedNew = !$found;
        log_aksi("Simpan kuesioner ID=$id dari " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    } else {
        $err = 'Jawaban belum bisa disimpan. Coba tekan Simpan jawaban sekali lagi. Jika tetap gagal, hubungi petugas.';
    }
} elseif ($isAdmin && isset($_GET['edit_id'])) {
    $eid = (string)$_GET['edit_id'];
    foreach (db_all() as $r) {
        if (isset($r['id']) && $r['id'] === $eid) {
            $D = $r['data'] ?? [];
            $formId = $eid;
            break;
        }
    }
}

if (empty($D['tgl_wawancara'])) {
    $D['tgl_wawancara'] = date('Y-m-d');
}

/* ---------- Helper tampilan (prefix smd_ agar tidak bentrok dengan view.php) ---------- */
function smd_h($s)
{
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}
function smd_v($k, $def = '')
{
    global $D;
    return isset($D[$k]) && !is_array($D[$k]) ? $D[$k] : $def;
}
/** Opsi bisa 'Ya' atau ['nilai_tersimpan', 'Label tampil'] */
function smd_opt($o)
{
    return is_array($o) ? [$o[0], $o[1]] : [$o, $o];
}
function smd_radio($name, $opts)
{
    $cur = smd_v($name);
    $h = '<div class="opts">';
    foreach ($opts as $o) {
        list($val, $lab) = smd_opt($o);
        $chk = ((string)$cur === (string)$val) ? ' checked' : '';
        $h .= '<label class="opt"><input type="radio" name="' . smd_h($name) . '" value="' . smd_h($val) . '"' . $chk . '><span>' . smd_h($lab) . '</span></label>';
    }
    return $h . '</div>';
}
function smd_select($name, $opts)
{
    $cur = smd_v($name);
    $h = '<select class="ctl" id="f_' . smd_h($name) . '" name="' . smd_h($name) . '"><option value="">Pilih salah satu</option>';
    foreach ($opts as $o) {
        list($val, $lab) = smd_opt($o);
        $sel = ((string)$cur === (string)$val) ? ' selected' : '';
        $h .= '<option value="' . smd_h($val) . '"' . $sel . '>' . smd_h($lab) . '</option>';
    }
    return $h . '</select>';
}
function smd_area($name, $ph)
{
    return '<textarea class="ctl" id="f_' . smd_h($name) . '" name="' . smd_h($name) . '" rows="3" placeholder="' . smd_h($ph) . '">' . smd_h(smd_v($name)) . '</textarea>';
}
function smd_input($name, $type, $ph, $extra = '')
{
    return '<input class="ctl" id="f_' . smd_h($name) . '" type="' . $type . '" name="' . smd_h($name) . '" placeholder="' . smd_h($ph) . '" value="' . smd_h(smd_v($name)) . '" ' . $extra . '>';
}
/**
 * Satu pertanyaan.
 * opt: main (bernomor, default true), req (wajib), hint, optional (tidak dihitung "belum dijawab").
 */
function smd_q($label, $control, $opt = [])
{
    static $n = 0, $k = 0;
    $k++;
    $main = $opt['main'] ?? true;
    $req = !empty($opt['req']);
    $hint = $opt['hint'] ?? '';
    $lid = 'l' . $k;

    $name = '';
    if (preg_match('/name="([^"]+)"/', $control, $m)) {
        $name = $m[1];
    }
    $num = '';
    if ($main) {
        $n++;
        $num = '<span class="qn">' . $n . '.</span>';
    }
    $text = '<span>' . $label . ($req ? '<span class="req">(wajib)</span>' : '') . '</span>';

    if (strpos($control, 'type="radio"') !== false) {
        $control = str_replace('class="opts"', 'class="opts" role="radiogroup" aria-labelledby="' . $lid . '"' . ($req ? ' aria-required="true"' : ''), $control);
        $lab = '<div class="q-label" id="' . $lid . '">' . $num . $text . '</div>';
    } else {
        if ($req) {
            $control = preg_replace('/^<(input|select|textarea)/', '<$1 aria-required="true"', $control);
        }
        $lab = '<label class="q-label" id="' . $lid . '" for="f_' . smd_h($name) . '">' . $num . $text . '</label>';
    }

    return '<div class="q' . ($main ? ' main' : '') . '"' . ($req ? ' data-req="1"' : '') . (!empty($opt['optional']) ? ' data-opt="1"' : '') . '>'
        . $lab
        . ($hint !== '' ? '<div class="q-hint">' . $hint . '</div>' : '')
        . $control
        . '<p class="q-err">Wajib diisi.</p></div>';
}
/** Blok pertanyaan lanjutan: muncul bila field $name bernilai salah satu dari $vals (dipisah |). */
function smd_cond_open($name, $vals)
{
    return '<div class="cond" data-when="' . smd_h($name . '=' . $vals) . '"><div class="cond-in"><div class="cond-box">';
}
function smd_cond_close()
{
    return '</div></div></div>';
}
function smd_section($title, $lead = '')
{
    return '<section class="sec"><h2 class="sec-t" tabindex="-1">' . $title . '</h2>'
        . ($lead !== '' ? '<p class="sec-lead">' . $lead . '</p>' : '');
}
function smd_section_end()
{
    return '</section>';
}

$YT = ['Ya', 'Tidak'];
$SB = ['Sudah', 'Belum'];
$TT = ['Tahu', 'Tidak tahu'];
$ALASAN = 'Tulis alasannya';

layout_head('Isi Kuesioner SMD — ' . APP_NAME);
?>
<style>
.smd{--ink:#14181d;--mut:#4a545e;--rule:#c9ced4;--edge:#6b7580;--ac:#1a3f91;--ac-bg:#e8eefb;--bad:#a61b1b;--bad-bg:#fdeeee;
  max-width:760px;margin:0 auto 40px;background:#fff;border:1px solid var(--rule);border-radius:6px;color:var(--ink);
  font-family:"Segoe UI",system-ui,-apple-system,Roboto,"Helvetica Neue",Arial,sans-serif;font-size:19px;line-height:1.5}
.smd[data-size="1"]{font-size:22px}
.smd[data-size="2"]{font-size:26px}
.smd *{box-sizing:border-box}
.smd-in{padding:1.1em 1.4em 0}
.smd :focus-visible{outline:3px solid var(--ac);outline-offset:2px}

.smd-top{display:flex;justify-content:space-between;align-items:flex-start;gap:.8em 1.2em;flex-wrap:wrap;padding-bottom:1em;border-bottom:1px solid var(--rule)}
.smd-name{margin:0;font-weight:700;line-height:1.3}
.smd-name span{display:block;font-weight:400;color:var(--mut);font-size:.9em}
.size{display:flex;align-items:center;gap:.4em;flex-wrap:wrap;font-size:.8em}
.size-l{color:var(--mut)}
.size button{min-height:2.9em;padding:.2em .8em;border:2px solid var(--edge);border-radius:6px;background:#fff;color:var(--ink);font:inherit;cursor:pointer}
.size button[aria-pressed="true"]{border-color:var(--ac);background:var(--ac-bg);font-weight:700}

.stepinfo{margin:1.1em 0 0}
.stepinfo p{margin:0 0 .4em;font-size:.9em;font-weight:600;color:var(--mut)}
.segs{display:flex;gap:.25em}
.segs i{flex:1;height:.4em;border-radius:99px;background:#d3d8de}
.segs i.on{background:var(--ac)}

.step-err{margin:1em 0 0;padding:.7em 1em;border-left:5px solid var(--bad);background:var(--bad-bg);font-weight:600}
.note{margin:1em 0 0;padding:.7em 1em;border-left:5px solid var(--bad);background:var(--bad-bg);font-weight:600}

.sec{padding-top:1.2em}
.sec+.sec{margin-top:1.6em;border-top:2px solid var(--ink)}
.sec-t{margin:0 0 .3em;font-size:1.45em;line-height:1.25;font-weight:700}
.sec-t:focus{outline:none}
.sec-lead{margin:0 0 1.2em;color:var(--mut)}
.js .sec{display:none}
.js .sec.on{display:block}
.js .sec+.sec{margin-top:0;border-top:0}

.q{margin-bottom:1.5em}
.q-label{display:flex;gap:.5em;margin:0 0 .5em;font-size:1.05em;font-weight:600;line-height:1.4}
.qn{flex:none;min-width:1.7em;color:var(--ac);font-weight:700}
.req{margin-left:.35em;font-size:.85em;font-weight:400;color:var(--mut)}
.q-hint{margin:-.2em 0 .6em;font-size:.9em;color:var(--mut)}
.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(13em,1fr));gap:1.3em 1em}
.grid .q{margin-bottom:0}
.grid .full{grid-column:1/-1}

.ctl{display:block;width:100%;min-height:2.8em;padding:.5em .7em;border:2px solid var(--edge);border-radius:6px;background:#fff;color:var(--ink);font:inherit}
textarea.ctl{min-height:5.5em;resize:vertical;line-height:1.4}
.ctl:focus{border-color:var(--ac)}
.opts{display:grid;grid-template-columns:repeat(auto-fit,minmax(9em,1fr));gap:.6em}
.opt{display:flex;align-items:center;gap:.65em;min-height:3em;padding:.5em .9em;border:2px solid var(--edge);border-radius:6px;background:#fff;cursor:pointer}
.opt input{flex:none;width:1.35em;height:1.35em;margin:0;accent-color:var(--ac)}
.opt:hover{border-color:var(--ac)}
.opt:has(input:checked){border-color:var(--ac);background:var(--ac-bg);font-weight:700}

.q-err{display:none;margin:.4em 0 0;font-size:.9em;font-weight:600;color:var(--bad)}
.q.err .q-err{display:block}
.q.err .ctl,.q.err .opt{border-color:var(--bad)}
.q.err>.q-label{color:var(--bad)}

.cond{display:grid;grid-template-rows:0fr;opacity:0;transition:grid-template-rows .25s ease,opacity .2s ease}
.cond.open{grid-template-rows:1fr;opacity:1;margin-top:-.7em}
.cond-in{overflow:hidden;min-height:0}
.cond-box{margin:0 0 1.5em .6em;padding:.2em 0 0 1.1em;border-left:3px solid #9aa6b5}
.cond-box>.q:last-child{margin-bottom:0}

.review{margin:1.5em 0 0;padding:.8em 1em;border-left:5px solid var(--ac);background:var(--ac-bg)}

.smd-nav{position:sticky;bottom:0;z-index:30;display:flex;flex-wrap:wrap;gap:.6em;margin:1.8em -1.4em 0;padding:.8em 1.4em;background:#fff;border-top:1px solid var(--rule);border-radius:0 0 6px 6px}
.smd-btn{display:inline-flex;align-items:center;justify-content:center;min-height:3em;padding:.5em 1em;border:2px solid var(--ac);border-radius:6px;background:var(--ac);color:#fff;font:inherit;font-weight:700;text-align:center;text-decoration:none;cursor:pointer}
.smd-btn:hover{background:#142f6b;border-color:#142f6b}
.smd-btn.ghost{background:#fff;color:var(--ac)}
.smd-btn.ghost:hover{background:var(--ac-bg)}
.smd-btn[hidden]{display:none}
.smd-nav .smd-btn{flex:1 1 8em;min-width:0}
.smd-nav .smd-btn.ghost{flex:0 0 auto}

.done{padding:1.4em 0 1.8em}
.done h1{margin:0 0 .4em;font-size:1.6em;line-height:1.25}
.done p{margin:.5em 0;max-width:34em}
.done-act{display:flex;flex-wrap:wrap;gap:.7em;margin-top:1.4em}

@media(max-width:640px){
  .smd{border-left:0;border-right:0;border-radius:0}
  .smd-in{padding:1em 1em 0}
  .smd-nav{margin:1.6em -1em 0;padding:.7em 1em;border-radius:0}
}
@media(prefers-reduced-motion:reduce){.cond{transition:none}}
</style>

<div class="">
    <main class="smd" data-size="0">
        <div class="smd-in">
            <div class="smd-top">
                <p class="smd-name">Survei Mawas Diri<span>Puskesmas Kebon Jeruk 2026</span></p>
                <div class="size" role="group" aria-label="Ukuran tulisan">
                    <span class="size-l">Ukuran tulisan</span>
                    <button type="button" data-v="0" aria-pressed="true">Biasa</button>
                    <button type="button" data-v="1" aria-pressed="false">Besar</button>
                    <button type="button" data-v="2" aria-pressed="false">Sangat besar</button>
                </div>
            </div>

<?php if ($saved): ?>
            <div class="done" role="status">
                <h1><?php echo $savedNew ? 'Jawaban sudah tersimpan' : 'Perubahan sudah tersimpan'; ?></h1>
                <p>Terima kasih sudah mengisi survei ini.</p>
                <?php if ($kurang): ?>
                    <p class="note">Data ini disimpan sebagai draft karena <?php echo smd_h(implode(', ', array_map(function ($f) use ($wajibLabel) {
                        return $wajibLabel[$f];
                    }, $kurang))); ?> belum diisi.</p>
                <?php endif; ?>
                <div class="done-act">
                    <a class="smd-btn" href="index.php">Isi untuk responden berikutnya</a>
                    <?php if ($isAdmin): ?><a class="smd-btn ghost" href="dashboard.php">Kembali ke dashboard</a><?php endif; ?>
                </div>
            </div>
<?php else: ?>
            <?php if ($err !== ''): ?><p class="note" role="alert"><?php echo smd_h($err); ?></p><?php endif; ?>

            <div class="stepinfo" id="stepinfo" hidden>
                <p id="stepTxt"></p>
                <div class="segs" id="segs"></div>
            </div>

            <form method="post" action="ubdex.php" id="form_kuesioner" novalidate data-start="<?php echo $err !== '' ? 'last' : '0'; ?>">
                <input type="hidden" name="aksi" value="simpan">
                <?php if ($formId !== ''): ?>
                    <input type="hidden" name="id" value="<?php echo smd_h($formId); ?>">
                <?php endif; ?>
                <p class="step-err" id="stepErr" role="alert" hidden></p>

                <?php
                /* ================= Bagian 1 ================= */
                echo smd_section('Tentang Anda', 'Isian yang bertanda (wajib) harus diisi. Yang lain boleh dikosongkan kalau Anda tidak tahu.');
                ?>
                <div class="grid">
                    <div class="full"><?php echo smd_q('Inisial responden', smd_input('inisial', 'text', 'Contoh: FI', 'maxlength="20" autocomplete="off"'), ['main' => false, 'req' => true]); ?></div>
                    <div class="full"><?php echo smd_q('Alamat', smd_input('alamat', 'text', 'Nama jalan dan nomor rumah', 'maxlength="200" autocomplete="off"'), ['main' => false, 'req' => true]); ?></div>
                    <?php
                    echo smd_q('RT', smd_input('rt', 'text', 'Contoh: 01', 'maxlength="10" inputmode="numeric" autocomplete="off"'), ['main' => false]);
                    echo smd_q('RW', smd_input('rw', 'text', 'Contoh: 01', 'maxlength="10" inputmode="numeric" autocomplete="off"'), ['main' => false]);
                    echo smd_q('Kelurahan', smd_select('kelurahan', ['Kebon Jeruk', 'Duri Kepa', 'Kedoya Selatan', 'Kedoya Utara', 'Sukabumi Selatan', 'Sukabumi Utara', 'Kelapa Dua']), ['main' => false]);
                    echo smd_q('Usia (tahun)', smd_input('usia', 'number', 'Contoh: 35', 'min="0" max="120" inputmode="numeric"'), ['main' => false]);
                    ?>
                    <div class="full"><?php echo smd_q('Jenis kelamin', smd_radio('jenis_kelamin', ['Laki-Laki', 'Perempuan']), ['main' => false, 'req' => true]); ?></div>
                    <?php
                    echo smd_q('Pendidikan terakhir', smd_select('pendidikan', ['Tidak Sekolah', 'SD', 'SMP', 'SMA', 'DIII', 'S1', 'S2', 'S3']), ['main' => false, 'req' => true]);
                    echo smd_q('Pekerjaan', smd_select('pekerjaan', ['Pelajar', 'ASN–PNS–P3K', 'Polisi–TNI', 'Karyawan swasta', 'Wiraswasta', 'Ibu rumah tangga', 'Lain-lain']), ['main' => false, 'req' => true]);
                    ?>
                    <div class="full"><?php echo smd_q('Tanggal wawancara', smd_input('tgl_wawancara', 'date', ''), ['main' => false, 'req' => true, 'hint' => 'Sudah terisi tanggal hari ini. Ubah jika berbeda.']); ?></div>
                </div>
                <?php
                echo smd_section_end();

                /* ================= Bagian 2 ================= */
                echo smd_section('Kehamilan dan persalinan');

                echo smd_q('Apakah Anda atau pasangan Anda pernah hamil / sedang hamil saat ini?', smd_radio('hamil', $YT));
                echo smd_cond_open('hamil', 'Ya');
                echo smd_q('Apakah Anda memeriksakan kehamilan ke Puskesmas / Bidan Swasta / Rumah Sakit / Klinik?', smd_radio('hamil_cek', $YT), ['main' => false]);
                echo smd_cond_open('hamil_cek', 'Tidak');
                echo smd_q('Sebutkan alasannya', smd_area('hamil_alas', $ALASAN), ['main' => false]);
                echo smd_cond_close();
                echo smd_cond_close();

                echo smd_q('Apakah Anda atau pasangan Anda sudah pernah melahirkan?', smd_radio('melahirkan', $YT));
                echo smd_cond_open('melahirkan', 'Ya');
                echo smd_q('Apakah Anda melahirkan di Puskesmas / Bidan Swasta / Klinik / Rumah Sakit?', smd_radio('melahirkan_tempat', $YT), ['main' => false]);
                echo smd_cond_open('melahirkan_tempat', 'Tidak');
                echo smd_q('Sebutkan alasannya', smd_area('melahirkan_alas', $ALASAN), ['main' => false]);
                echo smd_cond_close();
                echo smd_cond_close();

                echo smd_q('Apakah Anda atau pasangan Anda pernah melakukan pemeriksaan deteksi dini kanker leher rahim (serviks) seperti IVA Test / Pap Smear / HPV DNA?', smd_radio('iva_papsmear', $YT));
                echo smd_cond_open('iva_papsmear', 'Ya');
                echo smd_q('Kapan terakhir kali Anda melakukan pemeriksaan tersebut?', smd_select('iva_saat', ['Kurang dari 1 tahun lalu', '1–3 tahun lalu', 'Lebih dari 3 tahun lalu', 'Tidak ingat']), ['main' => false]);
                echo smd_q('Di mana Anda melakukan pemeriksaan tersebut?', smd_select('iva_tempat', ['Puskesmas', 'Klinik', 'Rumah Sakit', 'Posyandu', 'Lainnya (sebutkan)']), ['main' => false]);
                echo smd_cond_open('iva_tempat', 'Lainnya (sebutkan)');
                echo smd_q('Sebutkan tempatnya', smd_input('iva_tempat_lain', 'text', 'Nama tempat'), ['main' => false]);
                echo smd_cond_close();
                echo smd_cond_close();
                echo smd_cond_open('iva_papsmear', 'Tidak');
                echo smd_q('Sebutkan alasannya', smd_area('iva_alas', $ALASAN), ['main' => false]);
                echo smd_cond_close();

                echo smd_section_end();

                /* ================= Bagian 3 ================= */
                echo smd_section('Kesehatan anak usia 0–59 bulan');

                echo smd_q('Apakah anak Anda mendapatkan imunisasi lengkap (IDL) saat usia 0–24 bulan?', smd_radio('idl', $YT));
                echo smd_cond_open('idl', 'Tidak');
                echo smd_q('Sebutkan alasannya', smd_area('idl_alas', $ALASAN), ['main' => false]);
                echo smd_cond_close();

                echo smd_q('Apakah anak Anda mendapatkan ASI eksklusif saat usia 0–6 bulan?', smd_radio('asi', $YT));
                echo smd_cond_open('asi', 'Tidak');
                echo smd_q('Sebutkan alasannya', smd_area('asi_alas', $ALASAN), ['main' => false]);
                echo smd_cond_close();

                echo smd_q('Apakah anak Anda di usia 2–59 bulan selalu dipantau tumbuh kembangnya di Posyandu / Puskesmas / RS?', smd_radio('pantau', $YT));
                echo smd_cond_open('pantau', 'Tidak');
                echo smd_q('Sebutkan alasannya', smd_area('pantau_alas', $ALASAN), ['main' => false]);
                echo smd_cond_close();

                echo smd_q('Apakah Anda atau keluarga Anda sudah mendapatkan imunisasi MR / Dt / Td saat usia sekolah dasar?', smd_radio('imunisasi_sekolah', $SB));
                echo smd_cond_open('imunisasi_sekolah', 'Belum');
                echo smd_q('Sebutkan alasannya', smd_area('imunisasi_alas', $ALASAN), ['main' => false]);
                echo smd_cond_close();

                echo smd_section_end();

                /* ================= Bagian 4 ================= */
                echo smd_section('Kesehatan keluarga dan kebiasaan sehari-hari');

                echo smd_q('Apakah saat ini Anda atau pasangan Anda termasuk kategori usia subur (20–45 tahun)?', smd_radio('usia_subur', $YT));
                echo smd_cond_open('usia_subur', 'Ya');
                echo smd_q('Apakah Anda sudah menggunakan alat kontrasepsi?', smd_radio('kontrasepsi', $YT), ['main' => false, 'hint' => 'Misalnya pil KB, suntik, implan, kondom, IUD, atau KB alami.']);
                echo smd_cond_open('kontrasepsi', 'Tidak');
                echo smd_q('Sebutkan alasannya', smd_area('subur_alas', $ALASAN), ['main' => false]);
                echo smd_cond_close();
                echo smd_cond_close();

                echo smd_q('Apakah Anda pernah didiagnosis menderita penyakit TB (Tuberkulosis)?', smd_radio('diagnosa_tb', $YT));
                echo smd_cond_open('diagnosa_tb', 'Ya');
                echo smd_q('Apakah Anda menjalani pengobatan TB secara rutin di Puskesmas / Klinik / RS?', smd_radio('tb_rutin', $YT), ['main' => false]);
                echo smd_cond_open('tb_rutin', 'Tidak');
                echo smd_q('Sebutkan alasannya', smd_area('tb_alas', $ALASAN), ['main' => false]);
                echo smd_cond_close();
                echo smd_cond_close();

                echo smd_q('Apakah Anda menggunakan masker saat batuk atau mengalami gejala infeksi saluran pernapasan, terutama di dekat orang lain?', smd_radio('masker', ['Selalu', 'Sering', 'Kadang-kadang', 'Tidak pernah']));
                echo smd_cond_open('masker', 'Kadang-kadang|Tidak pernah');
                echo smd_q('Sebutkan alasannya', smd_area('masker_alas', $ALASAN), ['main' => false]);
                echo smd_cond_close();

                echo smd_q('Apakah Anda atau keluarga pernah / saat ini didiagnosis menderita hipertensi (darah tinggi)?', smd_radio('hipertensi', $YT));
                echo smd_cond_open('hipertensi', 'Ya');
                echo smd_q('Apakah Anda menjalani pengobatan secara rutin di Puskesmas / RS / Klinik / Praktik Dokter Mandiri?', smd_radio('ht_rutin', $YT), ['main' => false]);
                echo smd_cond_open('ht_rutin', 'Tidak');
                echo smd_q('Sebutkan alasannya', smd_area('hipertensi_alas', $ALASAN), ['main' => false]);
                echo smd_cond_close();
                echo smd_cond_close();

                echo smd_q('Apakah Anda atau keluarga pernah / saat ini didiagnosis menderita diabetes melitus (kencing manis)?', smd_radio('diabetes', $YT));
                echo smd_cond_open('diabetes', 'Ya');
                echo smd_q('Apakah Anda menjalani pengobatan secara rutin di Puskesmas / RS / Klinik / Praktik Dokter Mandiri?', smd_radio('dm_rutin', $YT), ['main' => false]);
                echo smd_cond_open('dm_rutin', 'Tidak');
                echo smd_q('Sebutkan alasannya', smd_area('diabetes_alas', $ALASAN), ['main' => false]);
                echo smd_cond_close();
                echo smd_cond_close();

                echo smd_q('Apakah Anda atau keluarga pernah / saat ini didiagnosis menderita masalah kejiwaan?', smd_radio('kejiwaan', $YT));
                echo smd_cond_open('kejiwaan', 'Ya');
                echo smd_q('Apakah Anda menjalani pengobatan secara rutin di Puskesmas / RS?', smd_radio('jiwa_rutin', $YT), ['main' => false]);
                echo smd_cond_open('jiwa_rutin', 'Tidak');
                echo smd_q('Sebutkan alasannya', smd_area('kejiwaan_alas', $ALASAN), ['main' => false]);
                echo smd_cond_close();
                echo smd_cond_close();

                echo smd_q('Apakah Anda memiliki kebiasaan merokok?', smd_radio('merokok', $YT));
                echo smd_cond_open('merokok', 'Ya');
                echo smd_q('Di mana aktivitas merokok dilakukan?', smd_radio('tempat_smoke', ['Dalam rumah', 'Luar rumah', 'Dalam & luar rumah']), ['main' => false]);
                echo smd_cond_close();

                echo smd_section_end();

                /* ================= Bagian 5 ================= */
                echo smd_section('Jaminan kesehatan, kebersihan, dan pola hidup sehat');

                echo smd_q('Apakah semua anggota keluarga sudah memiliki jaminan kesehatan (JKN / BPJS)?', smd_radio('jaminan', $YT));
                echo smd_cond_open('jaminan', 'Tidak');
                echo smd_q('Sebutkan alasannya', smd_area('jaminan_alas', $ALASAN), ['main' => false]);
                echo smd_cond_close();

                echo smd_q('Apakah Anda memiliki sarana air bersih di rumah?', smd_radio('air_bersih', $YT));
                echo smd_cond_open('air_bersih', 'Tidak');
                echo smd_q('Sebutkan alasannya', smd_area('air_alas', $ALASAN), ['main' => false]);
                echo smd_cond_close();

                echo smd_q('Apakah Anda memiliki jamban sehat di rumah?', smd_radio('jamban', $YT), ['hint' => 'Jamban sehat adalah jamban leher angsa yang mengalir ke septic tank.']);
                echo smd_cond_open('jamban', 'Tidak');
                echo smd_q('Sebutkan alasannya', smd_area('jamban_alas', $ALASAN), ['main' => false]);
                echo smd_cond_close();

                echo smd_q('Apakah Anda sudah terbiasa cuci tangan pakai sabun di bawah air mengalir sebelum / sesudah beraktivitas sehari-hari?', smd_radio('cuci_tangan', $SB));
                echo smd_cond_open('cuci_tangan', 'Belum');
                echo smd_q('Sebutkan alasannya', smd_area('cuci_alas', $ALASAN), ['main' => false]);
                echo smd_cond_close();

                echo smd_q('Apakah Anda sudah terbiasa melakukan aktivitas fisik sehari-hari?', smd_radio('fisik', $SB), ['hint' => 'Misalnya mencuci, menyapu, berkebun, atau naik tangga.']);
                echo smd_cond_open('fisik', 'Belum');
                echo smd_q('Sebutkan alasannya', smd_area('fisik_alas', $ALASAN), ['main' => false]);
                echo smd_cond_close();

                echo smd_q('Apakah Anda sudah terbiasa makan buah dan sayur setiap hari?', smd_radio('buah', $SB));
                echo smd_cond_open('buah', 'Belum');
                echo smd_q('Sebutkan alasannya', smd_area('buah_alas', $ALASAN), ['main' => false]);
                echo smd_cond_close();

                echo smd_q('Apakah di lingkungan rumah Anda ada tanaman obat keluarga (TOGA)?', smd_radio('toaga', $YT));
                echo smd_cond_open('toaga', 'Ya');
                echo smd_q('Ada berapa jenis TOGA? Sebutkan jenis tanamannya', smd_area('toaga_jenis', 'Contoh: jahe, kunyit, serai'), ['main' => false]);
                echo smd_cond_close();

                echo smd_section_end();

                /* ================= Bagian 6 ================= */
                echo smd_section('Cek Kesehatan Gratis dan keamanan di rumah');

                echo smd_q('Tahukah Anda di mana untuk mendapatkan Cek Kesehatan Gratis (CKG)?', smd_radio('ckg_tau', $TT));
                echo smd_cond_open('ckg_tau', 'Tahu');
                echo smd_q('Sebutkan di mana saja', smd_area('ckg_tau_tempat', 'Nama tempat'), ['main' => false]);
                echo smd_cond_close();

                echo smd_q('Apakah Anda sudah melakukan Cek Kesehatan Gratis (CKG)?', smd_radio('ckg_lakukan', [['Ya', 'Sudah'], ['Belum', 'Belum']]));
                echo smd_cond_open('ckg_lakukan', 'Belum');
                echo smd_q('Sebutkan alasannya', smd_area('ckg_alas', $ALASAN), ['main' => false]);
                echo smd_cond_close();

                echo smd_q('Apakah Anda atau anggota keluarga pernah mengalami kekerasan secara fisik, verbal, ataupun seksual di dalam rumah tangga?', smd_radio('kekerasan', ['Pernah', 'Tidak pernah']));
                echo smd_cond_open('kekerasan', 'Pernah');
                echo smd_q('Pernahkah Anda melaporkannya ke pihak berwajib / P2TP2A atau ke Puskesmas?', smd_radio('kekerasan_lapor', ['Pernah', 'Tidak pernah']), ['main' => false]);
                echo smd_cond_open('kekerasan_lapor', 'Tidak pernah');
                echo smd_q('Apakah Anda membutuhkan bantuan dari instansi di atas?', smd_radio('kekerasan_bantuan', $YT), ['main' => false]);
                echo smd_cond_close();
                echo smd_cond_close();

                echo smd_section_end();

                /* ================= Bagian 7 ================= */
                echo smd_section('Layanan Puskesmas, kritik, dan saran');

                echo smd_q('Apakah Anda mengetahui bahwa bila ada anggota keluarga yang meninggal dunia secara wajar di rumah, kematian tersebut perlu segera dilaporkan ke Puskesmas Kebon Jeruk pada hari yang sama untuk pemeriksaan jenazah dan pengurusan Surat Keterangan Penyebab Kematian (SKPK)?', smd_radio('skpk', $TT));

                echo smd_q('Apakah Anda mengetahui bahwa Surat Keterangan Melapor Kematian (SKMK) di Puskesmas Kebon Jeruk hanya dapat diurus untuk kematian yang terjadi secara wajar, belum pernah dibuatkan Surat Kematian, dan dilaporkan dalam waktu maksimal 1 tahun sejak tanggal kematian?', smd_radio('skmk', $TT));

                echo smd_q('Apakah penyandang disabilitas mendapatkan kemudahan dalam pelayanan di Puskesmas Kebon Jeruk?', smd_radio('disabilitas', ['Ya', 'Tidak', 'Tidak tahu']));
                echo smd_cond_open('disabilitas', 'Tidak');
                echo smd_q('Apa kendala yang dialami saat mengakses pelayanan?', smd_area('dis_alas', 'Tulis kendalanya'), ['main' => false]);
                echo smd_cond_close();

                echo smd_q('Apakah Anda sudah pernah mendengar tentang Puskesmas ILP (Integrasi Pelayanan Kesehatan Primer)?', smd_radio('ilp_tahu', $SB));
                echo smd_cond_open('ilp_tahu', 'Sudah');
                echo smd_q('Menurut Anda, apakah Puskesmas Kebon Jeruk sudah menerapkan Puskesmas ILP dengan baik?', smd_radio('ilp_kinerja', $SB), ['main' => false]);
                echo smd_cond_open('ilp_kinerja', 'Belum');
                echo smd_q('Sebutkan alasannya', smd_area('ilp_alas', $ALASAN), ['main' => false]);
                echo smd_cond_close();
                echo smd_cond_close();

                echo smd_q('Sebutkan masalah kesehatan lain yang ditemukan di sekitar tempat tinggal Anda, serta kritik dan saran untuk layanan Puskesmas Kebon Jeruk.', smd_area('saran', 'Boleh dikosongkan'), ['optional' => true]);

                echo smd_section_end();
                ?>

                <p class="review" id="review" role="status" hidden></p>

                <div class="smd-nav">
                    <button class="smd-btn ghost" type="button" id="btnPrev" hidden>Kembali</button>
                    <button class="smd-btn" type="button" id="btnNext" hidden>Lanjut</button>
                    <button class="smd-btn" type="submit" id="btnSubmit">Simpan jawaban</button>
                </div>
            </form>
<?php endif; ?>
        </div>
    </main>
</div>

<script>
(function () {
    var root = document.querySelector('.smd');
    var KEY = 'smd_size';

    /* ---------- Ukuran tulisan ---------- */
    var sizeBtns = Array.prototype.slice.call(root.querySelectorAll('.size button'));
    function setSize(v, save) {
        root.setAttribute('data-size', v);
        sizeBtns.forEach(function (b) {
            b.setAttribute('aria-pressed', b.getAttribute('data-v') === String(v) ? 'true' : 'false');
        });
        if (save) { try { localStorage.setItem(KEY, v); } catch (e) {} }
    }
    sizeBtns.forEach(function (b) {
        b.addEventListener('click', function () { setSize(b.getAttribute('data-v'), true); });
    });
    var remembered = '0';
    try { remembered = localStorage.getItem(KEY) || '0'; } catch (e) {}
    if (['0', '1', '2'].indexOf(remembered) === -1) { remembered = '0'; }
    setSize(remembered, false);

    var form = document.getElementById('form_kuesioner');
    if (!form) { return; }
    root.classList.add('js');

    var secs = Array.prototype.slice.call(form.querySelectorAll('.sec'));
    var conds = Array.prototype.slice.call(form.querySelectorAll('.cond'));
    var total = secs.length;
    var cur = 0;

    var info = document.getElementById('stepinfo');
    var infoTxt = document.getElementById('stepTxt');
    var segsBox = document.getElementById('segs');
    var stepErr = document.getElementById('stepErr');
    var review = document.getElementById('review');
    var prev = document.getElementById('btnPrev');
    var next = document.getElementById('btnNext');
    var submit = document.getElementById('btnSubmit');
    var segs = [];
    for (var s = 0; s < total; s++) {
        var seg = document.createElement('i');
        segsBox.appendChild(seg);
        segs.push(seg);
    }

    /* ---------- Membaca jawaban ---------- */
    function getVal(name) {
        var els = form.querySelectorAll('[name="' + name + '"]');
        for (var i = 0; i < els.length; i++) {
            var e = els[i];
            if (e.disabled) continue;
            if (e.type === 'radio') { if (e.checked) return e.value; }
            else if (e.value) return e.value;
        }
        return '';
    }
    function hasAnswer(q) {
        var els = q.querySelectorAll('input:not(:disabled), select:not(:disabled), textarea:not(:disabled)');
        for (var i = 0; i < els.length; i++) {
            var e = els[i];
            if (e.type === 'radio') { if (e.checked) return true; }
            else if (String(e.value).trim() !== '') return true;
        }
        return false;
    }
    function isEnabledQ(q) {
        return q.querySelector('input:not(:disabled), select:not(:disabled), textarea:not(:disabled)') !== null;
    }
    function fields(root2) { return root2.querySelectorAll('input, select, textarea'); }

    /* ---------- Hide & seek ---------- */
    function updateConds() {
        // urutan DOM: induk selalu dievaluasi sebelum anak
        conds.forEach(function (c) {
            var parts = c.getAttribute('data-when').split('=');
            var vals = parts.slice(1).join('=').split('|');
            var show = vals.indexOf(getVal(parts[0])) !== -1;
            c.classList.toggle('open', show);
            Array.prototype.forEach.call(fields(c), function (f) { f.disabled = !show; });
        });
        // anak yang syaratnya tidak terpenuhi tetap tertutup (pemicunya ikut ter-disable oleh induk)
        conds.forEach(function (c) {
            var parts = c.getAttribute('data-when').split('=');
            var vals = parts.slice(1).join('=').split('|');
            if (vals.indexOf(getVal(parts[0])) === -1) {
                c.classList.remove('open');
                Array.prototype.forEach.call(fields(c), function (f) { f.disabled = true; });
            }
        });
    }

    /* ---------- Validasi isian wajib ---------- */
    function validate(i) {
        var bad = [];
        Array.prototype.forEach.call(secs[i].querySelectorAll('.q[data-req]'), function (q) {
            q.classList.remove('err');
            Array.prototype.forEach.call(fields(q), function (f) { f.removeAttribute('aria-invalid'); });
            if (!hasAnswer(q)) {
                q.classList.add('err');
                var f = q.querySelector('input, select, textarea');
                if (f) { f.setAttribute('aria-invalid', 'true'); }
                bad.push(q);
            }
        });
        return bad;
    }
    function showErr(bad) {
        stepErr.textContent = bad.length === 1
            ? 'Ada 1 isian wajib yang belum diisi. Lengkapi dulu, lalu lanjut.'
            : 'Ada ' + bad.length + ' isian wajib yang belum diisi. Lengkapi dulu, lalu lanjut.';
        stepErr.hidden = false;
        var first = bad[0];
        first.scrollIntoView({ block: 'center' });
        var f = first.querySelector('input, select, textarea');
        if (f) { f.focus({ preventScroll: true }); }
    }

    /* ---------- Ringkasan di bagian terakhir ---------- */
    function updateReview() {
        var open = 0;
        Array.prototype.forEach.call(form.querySelectorAll('.q'), function (q) {
            if (isEnabledQ(q) && !q.hasAttribute('data-opt') && !hasAnswer(q)) { open++; }
        });
        review.textContent = open === 0
            ? 'Semua pertanyaan sudah dijawab. Periksa kembali jika perlu, lalu simpan.'
            : 'Pertanyaan yang belum dijawab: ' + open + '. Tidak semua pertanyaan berlaku untuk setiap orang, jadi Anda boleh langsung menyimpan.';
    }

    /* ---------- Pindah bagian ---------- */
    function show(i, scroll) {
        cur = i;
        secs.forEach(function (sec, k) { sec.classList.toggle('on', k === i); });
        segs.forEach(function (sg, k) { sg.classList.toggle('on', k <= i); });
        info.hidden = false;
        infoTxt.textContent = 'Bagian ' + (i + 1) + ' dari ' + total;
        prev.hidden = i === 0;
        next.hidden = i === total - 1;
        submit.hidden = i !== total - 1;
        review.hidden = i !== total - 1;
        if (i === total - 1) { updateReview(); }
        stepErr.hidden = true;
        if (scroll) {
            window.scrollTo(0, Math.max(0, root.getBoundingClientRect().top + window.pageYOffset - 8));
            var h = secs[i].querySelector('.sec-t');
            if (h) { h.focus({ preventScroll: true }); }
        }
    }

    next.addEventListener('click', function () {
        var bad = validate(cur);
        if (bad.length) { showErr(bad); return; }
        show(cur + 1, true);
    });
    prev.addEventListener('click', function () { show(cur - 1, true); });

    form.addEventListener('submit', function (e) {
        var bad = [], firstStep = -1;
        for (var k = 0; k < total; k++) {
            var b = validate(k);
            if (b.length) {
                if (firstStep === -1) { firstStep = k; }
                bad = bad.concat(b);
            }
        }
        if (bad.length) {
            e.preventDefault();
            show(firstStep, false);
            showErr(validate(firstStep));
        }
    });

    // Enter tidak mengirim form secara tidak sengaja
    form.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && e.target.tagName === 'INPUT') {
            e.preventDefault();
            if (cur < total - 1) { next.click(); }
        }
    });

    form.addEventListener('change', function (e) {
        var q = e.target.closest ? e.target.closest('.q') : null;
        if (q) {
            q.classList.remove('err');
            Array.prototype.forEach.call(fields(q), function (f) { f.removeAttribute('aria-invalid'); });
        }
        updateConds();
    });

    updateConds();
    show(form.getAttribute('data-start') === 'last' ? total - 1 : 0, false);
})();
</script>
<?php
layout_tail();