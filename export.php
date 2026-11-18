<?php
/**
 * export.php — Unduh data kuesioner ke Excel (.xlsx). Khusus admin.
 *
 *   export.php                     semua data
 *   export.php?status=valid        hanya yang valid  (atau draft)
 *   export.php?search=kata         cari kata kunci, sama seperti di dashboard
 *   export.php?id=smd_xxx          satu responden saja
 *   export.php?format=csv          paksa CSV (otomatis dipakai jika server tidak punya ZipArchive)
 *
 * File .xlsx dibuat langsung tanpa library tambahan; butuh ekstensi PHP "zip".
 * Setiap pertanyaan menjadi satu kolom, setiap responden satu baris.
 */
session_start();
ob_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/view.php';
require_once __DIR__ . '/database.php';
ob_end_clean();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

/* ---------- Kolom: urutan sama dengan form ---------- */
$SECTIONS = [
    'A' => [
        'inisial' => 'Inisial responden',
        'alamat' => 'Alamat',
        'rt' => 'RT',
        'rw' => 'RW',
        'kelurahan' => 'Kelurahan',
        'usia' => 'Usia (tahun)',
        'jenis_kelamin' => 'Jenis kelamin',
        'pendidikan' => 'Pendidikan terakhir',
        'pekerjaan' => 'Pekerjaan',
        'pekerjaan_lain' => 'Pekerjaan (lain-lain)',
        'tgl_wawancara' => 'Tanggal wawancara',
    ],
    'B' => [
        'hamil' => 'Pernah hamil / sedang hamil?',
        'hamil_cek' => 'Memeriksakan kehamilan ke fasilitas kesehatan?',
        'hamil_alas' => 'Alasan tidak memeriksakan kehamilan',
        'melahirkan' => 'Sudah pernah melahirkan?',
        'melahirkan_tempat' => 'Melahirkan di fasilitas kesehatan?',
        'melahirkan_alas' => 'Alasan tidak melahirkan di fasilitas kesehatan',
        'iva_papsmear' => 'Pernah deteksi dini kanker serviks (IVA / Pap Smear / HPV DNA)?',
        'iva_saat' => 'Terakhir kali periksa kanker serviks',
        'iva_tempat' => 'Tempat pemeriksaan kanker serviks',
        'iva_tempat_lain' => 'Tempat pemeriksaan (lainnya)',
        'iva_alas' => 'Alasan belum pernah periksa kanker serviks',
    ],
    'C' => [
        'idl' => 'Imunisasi dasar lengkap (IDL) usia 0–24 bulan?',
        'idl_alas' => 'Alasan tidak imunisasi lengkap',
        'asi' => 'ASI eksklusif usia 0–6 bulan?',
        'asi_alas' => 'Alasan tidak ASI eksklusif',
        'pantau' => 'Tumbuh kembang dipantau usia 2–59 bulan?',
        'pantau_alas' => 'Alasan tidak dipantau',
        'imunisasi_sekolah' => 'Imunisasi MR / Dt / Td usia sekolah dasar',
        'imunisasi_alas' => 'Alasan belum imunisasi',
    ],
    'D' => [
        'usia_subur' => 'Termasuk usia subur (20–45 tahun)?',
        'kontrasepsi' => 'Menggunakan alat kontrasepsi?',
        'subur_alas' => 'Alasan tidak menggunakan kontrasepsi',
        'diagnosa_tb' => 'Pernah didiagnosis TB?',
        'tb_rutin' => 'Berobat TB rutin?',
        'tb_alas' => 'Alasan tidak berobat TB rutin',
        'masker' => 'Memakai masker saat batuk / gejala ISPA',
        'masker_alas' => 'Alasan jarang / tidak memakai masker',
        'hipertensi' => 'Pernah / sedang menderita hipertensi?',
        'hipertensi_siapa' => 'Penderita hipertensi',
        'ht_rutin' => 'Berobat hipertensi rutin?',
        'hipertensi_alas' => 'Alasan tidak berobat hipertensi rutin',
        'diabetes' => 'Pernah / sedang menderita diabetes melitus?',
        'diabetes_siapa' => 'Penderita diabetes melitus',
        'dm_rutin' => 'Berobat diabetes rutin?',
        'diabetes_alas' => 'Alasan tidak berobat diabetes rutin',
        'kejiwaan' => 'Pernah / sedang mengalami masalah kejiwaan?',
        'kejiwaan_siapa' => 'Penderita masalah kejiwaan',
        'jiwa_rutin' => 'Berobat kejiwaan rutin?',
        'kejiwaan_alas' => 'Alasan tidak berobat kejiwaan rutin',
        'merokok' => 'Memiliki kebiasaan merokok?',
        'tempat_smoke' => 'Tempat merokok',
    ],
    'E' => [
        'jaminan' => 'Seluruh anggota keluarga punya JKN / BPJS?',
        'jaminan_alas' => 'Alasan belum semua punya jaminan kesehatan',
        'air_bersih' => 'Memiliki sarana air bersih?',
        'air_alas' => 'Alasan tidak punya air bersih',
        'jamban' => 'Memiliki jamban sehat?',
        'jamban_alas' => 'Alasan tidak punya jamban sehat',
        'cuci_tangan' => 'Terbiasa cuci tangan pakai sabun (CTPS)',
        'cuci_alas' => 'Alasan belum terbiasa CTPS',
        'fisik' => 'Terbiasa aktivitas fisik sehari-hari',
        'fisik_alas' => 'Alasan belum terbiasa aktivitas fisik',
        'buah' => 'Terbiasa makan buah & sayur',
        'buah_alas' => 'Alasan belum terbiasa makan buah & sayur',
        'toaga' => 'Memiliki tanaman obat keluarga (TOGA)?',
        'toaga_jenis' => 'Jenis TOGA',
    ],
    'F' => [
        'ckg_tau' => 'Tahu tempat Cek Kesehatan Gratis (CKG)?',
        'ckg_tau_tempat' => 'Tempat CKG yang diketahui',
        'ckg_lakukan' => 'Sudah melakukan CKG?',
        'ckg_alas' => 'Alasan belum CKG',
        'kekerasan' => 'Pernah mengalami kekerasan dalam rumah tangga?',
        'kekerasan_lapor' => 'Pernah melaporkan kekerasan?',
        'kekerasan_bantuan' => 'Membutuhkan bantuan instansi?',
    ],
    'G' => [
        'skpk' => 'Tahu kewajiban lapor kematian wajar ke Puskesmas (SKPK)?',
        'skmk' => 'Tahu ketentuan SKMK?',
        'disabilitas' => 'Penyandang disabilitas mendapat kemudahan layanan?',
        'dis_alas' => 'Kendala akses layanan disabilitas',
        'ilp_tahu' => 'Pernah mendengar Puskesmas ILP?',
        'ilp_kinerja' => 'Puskesmas sudah menerapkan ILP dengan baik?',
        'ilp_alas' => 'Alasan ILP belum baik',
        'saran' => 'Masalah kesehatan sekitar, kritik & saran',
    ],
];

$cols = [];   // [key => header]
foreach ($SECTIONS as $kode => $fields) {
    foreach ($fields as $k => $label) {
        $cols[$k] = $kode . '. ' . $label;
    }
}

/* ---------- Ambil data (urutan: terlama dulu, nomor urut mengikuti waktu kirim) ---------- */
$rows = db_all();
$id = trim((string)($_GET['id'] ?? ''));
$search = trim((string)($_GET['search'] ?? ''));
$status = (string)($_GET['status'] ?? '');

if ($id !== '') {
    $rows = array_values(array_filter($rows, function ($r) use ($id) {
        return isset($r['id']) && $r['id'] === $id;
    }));
} else {
    if ($search !== '') {
        $needle = mb_strtolower($search, 'UTF-8');
        $rows = array_values(array_filter($rows, function ($r) use ($needle) {
            foreach ($r['data'] ?? [] as $v) {
                $s = mb_strtolower(is_array($v) ? implode(' ', $v) : (string)$v, 'UTF-8');
                if (strpos($s, $needle) !== false) {
                    return true;
                }
            }
            return false;
        }));
    }
    $kel = trim((string)($_GET['kelurahan'] ?? ''));
    if ($kel !== '') {
        $rows = array_values(array_filter($rows, function ($r) use ($kel) {
            return trim((string)($r['data']['kelurahan'] ?? '')) === $kel;
        }));
    }
    $risiko = (string)($_GET['risiko'] ?? '');
    if ($risiko !== '' && ($pos = strrpos($risiko, '_')) !== false) {
        $rf = substr($risiko, 0, $pos);
        $rv = substr($risiko, $pos + 1);
        $rows = array_values(array_filter($rows, function ($r) use ($rf, $rv) {
            $v = $r['data'][$rf] ?? '';
            return is_array($v) ? in_array($rv, $v, true) : (string)$v === $rv;
        }));
    }
    if (in_array($status, ['valid', 'draft'], true)) {
        $rows = array_values(array_filter($rows, function ($r) use ($status) {
            return ($r['status'] ?? 'draft') === $status;
        }));
    }
}
$rows = array_reverse($rows);

// Kolom tambahan bila ada field yang tidak dikenal
foreach ($rows as $r) {
    foreach (array_keys($r['data'] ?? []) as $k) {
        if (!isset($cols[$k])) {
            $cols[$k] = $k;
        }
    }
}

$header = array_merge(['No', 'Waktu kirim', 'Status', 'ID'], array_values($cols));
$keys = array_keys($cols);
$table = [];
foreach ($rows as $i => $r) {
    $d = $r['data'] ?? [];
    $line = [$i + 1, (string)($r['waktu'] ?? ''), (string)($r['status'] ?? 'draft'), (string)($r['id'] ?? '')];
    foreach ($keys as $k) {
        $v = $d[$k] ?? '';
        if (is_array($v)) {
            $v = implode(', ', $v);
        }
        $v = (string)$v;
        if ($k === 'ckg_lakukan' && $v === 'Ya') {
            $v = 'Sudah';
        }
        $line[] = $v;
    }
    $table[] = $line;
}

if (function_exists('log_aksi')) {
    log_aksi('Export data (' . count($table) . ' baris) oleh admin');
}

$namaFile = 'smd-kebon-jeruk-' . date('Ymd-Hi');

/* ---------- CSV ---------- */
function exp_csv(array $header, array $table, $namaFile)
{
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $namaFile . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF"); // BOM agar Excel membaca UTF-8
    fputcsv($out, $header, ';', '"', '');
    foreach ($table as $line) {
        fputcsv($out, $line, ';', '"', '');
    }
    fclose($out);
    exit;
}

if (($_GET['format'] ?? '') === 'csv' || !class_exists('ZipArchive')) {
    exp_csv($header, $table, $namaFile);
}

/* ---------- XLSX ---------- */
function x_col($i)
{
    $s = '';
    $i++;
    while ($i > 0) {
        $m = ($i - 1) % 26;
        $s = chr(65 + $m) . $s;
        $i = intdiv($i - 1, 26);
    }
    return $s;
}
function x_esc($s)
{
    $s = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', (string)$s);
    return htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}

$nCol = count($header);
$lastCol = x_col($nCol - 1);

// lebar kolom dari isi (dibatasi)
$width = [];
for ($c = 0; $c < $nCol; $c++) {
    $max = 0;
    foreach ($table as $line) {
        $len = mb_strlen((string)$line[$c], 'UTF-8');
        if ($len > $max) {
            $max = $len;
        }
    }
    $width[$c] = max(14, min(45, $max + 2));
}
$width[0] = 6;

$colsXml = '';
for ($c = 0; $c < $nCol; $c++) {
    $colsXml .= '<col min="' . ($c + 1) . '" max="' . ($c + 1) . '" width="' . $width[$c] . '" customWidth="1"/>';
}

$sheetRows = '<row r="1" ht="48" customHeight="1">';
foreach ($header as $c => $h) {
    $sheetRows .= '<c r="' . x_col($c) . '1" s="1" t="inlineStr"><is><t xml:space="preserve">' . x_esc($h) . '</t></is></c>';
}
$sheetRows .= '</row>';
foreach ($table as $ri => $line) {
    $rn = $ri + 2;
    $sheetRows .= '<row r="' . $rn . '">';
    foreach ($line as $c => $v) {
        $ref = x_col($c) . $rn;
        if ($v === '' || $v === null) {
            continue;
        }
        $isNum = ($c === 0) || ($header[$c] === 'A. Usia (tahun)' && ctype_digit((string)$v));
        if ($isNum) {
            $sheetRows .= '<c r="' . $ref . '" s="2"><v>' . (int)$v . '</v></c>';
        } else {
            $sheetRows .= '<c r="' . $ref . '" s="2" t="inlineStr"><is><t xml:space="preserve">' . x_esc($v) . '</t></is></c>';
        }
    }
    $sheetRows .= '</row>';
}

$ns = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
$xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
$sheet = $xml . '<worksheet xmlns="' . $ns . '">'
    . '<sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/><selection pane="bottomLeft"/></sheetView></sheetViews>'
    . '<cols>' . $colsXml . '</cols>'
    . '<sheetData>' . $sheetRows . '</sheetData>'
    . '<autoFilter ref="A1:' . $lastCol . '1"/>'
    . '</worksheet>';

$styles = $xml . '<styleSheet xmlns="' . $ns . '">'
    . '<fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font></fonts>'
    . '<fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill>'
    . '<fill><patternFill patternType="solid"><fgColor rgb="FF1A3F91"/><bgColor indexed="64"/></patternFill></fill></fills>'
    . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
    . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
    . '<cellXfs count="3">'
    . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
    . '<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1" applyAlignment="1"><alignment vertical="center" wrapText="1"/></xf>'
    . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0" applyAlignment="1"><alignment vertical="top" wrapText="1"/></xf>'
    . '</cellXfs>'
    . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
    . '</styleSheet>';

$workbook = $xml . '<workbook xmlns="' . $ns . '" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
    . '<sheets><sheet name="Data SMD" sheetId="1" r:id="rId1"/></sheets></workbook>';

$wbRels = $xml . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
    . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
    . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
    . '</Relationships>';

$rels = $xml . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
    . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
    . '</Relationships>';

$types = $xml . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
    . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
    . '<Default Extension="xml" ContentType="application/xml"/>'
    . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
    . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
    . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
    . '</Types>';

$tmp = tempnam(sys_get_temp_dir(), 'smd');
$zip = new ZipArchive();
if ($zip->open($tmp, ZipArchive::OVERWRITE) !== true) {
    @unlink($tmp);
    exp_csv($header, $table, $namaFile);
}
$zip->addFromString('[Content_Types].xml', $types);
$zip->addFromString('_rels/.rels', $rels);
$zip->addFromString('xl/workbook.xml', $workbook);
$zip->addFromString('xl/_rels/workbook.xml.rels', $wbRels);
$zip->addFromString('xl/styles.xml', $styles);
$zip->addFromString('xl/worksheets/sheet1.xml', $sheet);
$zip->close();

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $namaFile . '.xlsx"');
header('Content-Length: ' . filesize($tmp));
header('Cache-Control: no-store');
readfile($tmp);
@unlink($tmp);
exit;