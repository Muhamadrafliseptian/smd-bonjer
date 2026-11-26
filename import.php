<?php
/**
 * import.php — Pulihkan / gabungkan data kuesioner dari file hasil export.php (.xlsx atau .csv).
 * Khusus admin. Alur aman dua langkah:
 *   1) Unggah file  -> pratinjau (belum ada yang disimpan)
 *   2) Konfirmasi   -> salinan cadangan otomatis -> gabungkan -> simpan -> cek hasil
 *
 * Membaca kolom persis seperti yang ditulis export.php: No, Waktu kirim, Status, ID,
 * lalu satu kolom per pertanyaan ("A. Inisial responden", dst).
 * Jika daftar pertanyaan di export.php diubah, samakan $SECTIONS di bawah.
 */
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/layout.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}
@set_time_limit(180);
@ini_set('memory_limit', '512M');

/* ---------- Kolom: sama dengan export.php ---------- */
$SECTIONS = [
    'A' => [
        'inisial' => 'Inisial responden', 'alamat' => 'Alamat', 'rt' => 'RT', 'rw' => 'RW', 'kelurahan' => 'Kelurahan',
        'usia' => 'Usia (tahun)', 'jenis_kelamin' => 'Jenis kelamin', 'pendidikan' => 'Pendidikan terakhir',
        'pekerjaan' => 'Pekerjaan', 'pekerjaan_lain' => 'Pekerjaan (lain-lain)', 'tgl_wawancara' => 'Tanggal wawancara',
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
        'idl' => 'Imunisasi dasar lengkap (IDL) usia 0–24 bulan?', 'idl_alas' => 'Alasan tidak imunisasi lengkap',
        'asi' => 'ASI eksklusif usia 0–6 bulan?', 'asi_alas' => 'Alasan tidak ASI eksklusif',
        'pantau' => 'Tumbuh kembang dipantau usia 2–59 bulan?', 'pantau_alas' => 'Alasan tidak dipantau',
        'imunisasi_sekolah' => 'Imunisasi MR / Dt / Td usia sekolah dasar', 'imunisasi_alas' => 'Alasan belum imunisasi',
    ],
    'D' => [
        'usia_subur' => 'Termasuk usia subur (20–45 tahun)?', 'kontrasepsi' => 'Menggunakan alat kontrasepsi?',
        'subur_alas' => 'Alasan tidak menggunakan kontrasepsi', 'diagnosa_tb' => 'Pernah didiagnosis TB?',
        'tb_siapa' => 'Penderita TB', 'tb_rutin' => 'Status pengobatan TB (rutin / tidak rutin)',
        'tb_alas' => 'Alasan tidak berobat TB rutin', 'masker' => 'Memakai masker saat batuk / gejala ISPA',
        'masker_alas' => 'Alasan jarang / tidak memakai masker', 'hipertensi' => 'Pernah / sedang menderita hipertensi?',
        'hipertensi_siapa' => 'Penderita hipertensi', 'ht_rutin' => 'Status pengobatan hipertensi (rutin / tidak rutin)',
        'hipertensi_alas' => 'Alasan tidak berobat hipertensi rutin', 'diabetes' => 'Pernah / sedang menderita diabetes melitus?',
        'diabetes_siapa' => 'Penderita diabetes melitus', 'dm_rutin' => 'Status pengobatan diabetes (rutin / tidak rutin)',
        'diabetes_alas' => 'Alasan tidak berobat diabetes rutin', 'kejiwaan' => 'Pernah / sedang mengalami masalah kejiwaan?',
        'kejiwaan_siapa' => 'Penderita masalah kejiwaan', 'jiwa_rutin' => 'Status pengobatan kejiwaan (rutin / tidak rutin)',
        'kejiwaan_alas' => 'Alasan tidak berobat kejiwaan rutin', 'merokok' => 'Memiliki kebiasaan merokok?',
        'tempat_smoke' => 'Tempat merokok',
    ],
    'E' => [
        'jaminan' => 'Seluruh anggota keluarga punya JKN / BPJS?', 'jaminan_alas' => 'Alasan belum semua punya jaminan kesehatan',
        'air_bersih' => 'Memiliki sarana air bersih?', 'air_alas' => 'Alasan tidak punya air bersih',
        'jamban' => 'Memiliki jamban sehat?', 'jamban_alas' => 'Alasan tidak punya jamban sehat',
        'cuci_tangan' => 'Terbiasa cuci tangan pakai sabun (CTPS)', 'cuci_alas' => 'Alasan belum terbiasa CTPS',
        'fisik' => 'Terbiasa aktivitas fisik sehari-hari', 'fisik_alas' => 'Alasan belum terbiasa aktivitas fisik',
        'buah' => 'Terbiasa makan buah & sayur', 'buah_alas' => 'Alasan belum terbiasa makan buah & sayur',
        'toaga' => 'Memiliki tanaman obat keluarga (TOGA)?', 'toaga_jenis' => 'Jenis TOGA',
    ],
    'F' => [
        'ckg_tau' => 'Tahu tempat Cek Kesehatan Gratis (CKG)?', 'ckg_tau_tempat' => 'Tempat CKG yang diketahui',
        'ckg_lakukan' => 'Sudah melakukan CKG?', 'ckg_alas' => 'Alasan belum CKG',
        'kekerasan' => 'Pernah mengalami kekerasan dalam rumah tangga?', 'kekerasan_lapor' => 'Pernah melaporkan kekerasan?',
        'kekerasan_bantuan' => 'Membutuhkan bantuan instansi?',
    ],
    'G' => [
        'skpk' => 'Tahu kewajiban lapor kematian wajar ke Puskesmas (SKPK)?', 'skmk' => 'Tahu ketentuan SKMK?',
        'disabilitas' => 'Penyandang disabilitas mendapat kemudahan layanan?', 'dis_alas' => 'Kendala akses layanan disabilitas',
        'ilp_tahu' => 'Pernah mendengar Puskesmas ILP?', 'ilp_kinerja' => 'Puskesmas sudah menerapkan ILP dengan baik?',
        'ilp_alas' => 'Alasan ILP belum baik', 'saran' => 'Masalah kesehatan sekitar, kritik & saran',
    ],
];

/* ---------- Pembaca file ---------- */
function imp_norm($s)
{
    return mb_strtolower(trim((string)preg_replace('/\s+/u', ' ', (string)$s)), 'UTF-8');
}
function imp_col_index($ref)
{
    $n = 0;
    foreach (str_split(strtoupper(preg_replace('/[^A-Za-z]/', '', $ref))) as $ch) {
        $n = $n * 26 + (ord($ch) - 64);
    }
    return $n - 1;
}
function imp_excel_serial($n, $withTime)
{
    $ts = (int)round(((float)$n - 25569) * 86400);
    return gmdate($withTime ? 'Y-m-d H:i:s' : 'Y-m-d', $ts);
}
function imp_cell_text(DOMElement $c)
{
    $s = '';
    foreach ($c->getElementsByTagName('t') as $t) {
        $p = $t->parentNode ? $t->parentNode->localName : '';
        if ($p === 'is' || $p === 'r' || $p === 'si') {
            $s .= $t->textContent;
        }
    }
    return $s;
}
/** Baca .xlsx (inlineStr buatan export.php maupun sharedStrings bila sudah disimpan ulang di Excel). */
function imp_read_xlsx($path)
{
    $zip = new ZipArchive();
    if ($zip->open($path) !== true) {
        throw new RuntimeException('File .xlsx tidak bisa dibuka. Pakai file hasil Export dari dashboard.');
    }
    $sheet = 'xl/worksheets/sheet1.xml';
    $wb = $zip->getFromName('xl/workbook.xml');
    $rels = $zip->getFromName('xl/_rels/workbook.xml.rels');
    if ($wb !== false && $rels !== false && preg_match('/<sheet\b[^>]*\br:id="([^"]+)"/', $wb, $m)) {
        if (preg_match_all('/<Relationship\b[^>]*>/', $rels, $all)) {
            foreach ($all[0] as $tag) {
                if (preg_match('/\bId="' . preg_quote($m[1], '/') . '"/', $tag) && preg_match('/\bTarget="([^"]+)"/', $tag, $t)) {
                    $target = ltrim($t[1], '/');
                    $sheet = strpos($target, 'xl/') === 0 ? $target : 'xl/' . $target;
                }
            }
        }
    }
    if ($zip->locateName($sheet) === false) {
        $zip->close();
        throw new RuntimeException('Lembar data tidak ditemukan di dalam file Excel.');
    }
    $hasShared = $zip->locateName('xl/sharedStrings.xml') !== false;
    $zip->close();

    $shared = [];
    if ($hasShared) {
        $r = new XMLReader();
        $r->open('zip://' . $path . '#xl/sharedStrings.xml');
        while ($r->read()) {
            if ($r->nodeType === XMLReader::ELEMENT && $r->localName === 'si') {
                $d = new DOMDocument();
                $d->loadXML($r->readOuterXml());
                $txt = '';
                foreach ($d->getElementsByTagName('t') as $t) {
                    $p = $t->parentNode ? $t->parentNode->localName : '';
                    if ($p === 'si' || $p === 'r') {
                        $txt .= $t->textContent;
                    }
                }
                $shared[] = $txt;
            }
        }
        $r->close();
    }

    $rows = [];
    $r = new XMLReader();
    $r->open('zip://' . $path . '#' . $sheet);
    while ($r->read()) {
        if ($r->nodeType === XMLReader::ELEMENT && $r->localName === 'row') {
            break;
        }
    }
    $ok = ($r->nodeType === XMLReader::ELEMENT && $r->localName === 'row');
    while ($ok) {
        $d = new DOMDocument();
        $d->loadXML($r->readOuterXml());
        $line = [];
        $pos = 0;
        foreach ($d->documentElement->childNodes as $c) {
            if (!($c instanceof DOMElement) || $c->localName !== 'c') {
                continue;
            }
            $ci = $c->hasAttribute('r') ? imp_col_index($c->getAttribute('r')) : $pos;
            $pos = $ci + 1;
            $t = $c->getAttribute('t');
            $v = '';
            if ($t === 'inlineStr') {
                $v = imp_cell_text($c);
            } else {
                $vn = $c->getElementsByTagName('v')->item(0);
                $raw = $vn ? $vn->textContent : '';
                if ($t === 's') {
                    $v = $shared[(int)$raw] ?? '';
                } elseif ($t === 'b') {
                    $v = $raw === '1' ? 'TRUE' : 'FALSE';
                } elseif ($t === 'str' || $t === 'e') {
                    $v = $raw;
                } else { // angka
                    $v = $raw;
                    if (preg_match('/^-?\d+(\.\d+)?([eE][+-]?\d+)?$/', $raw)) {
                        $f = (float)$raw;
                        if (floor($f) == $f && abs($f) < 1e15) {
                            $v = (string)(int)$f;
                        }
                    }
                }
            }
            $line[$ci] = $v;
        }
        $rows[] = $line;
        $ok = $r->next('row');
    }
    $r->close();
    return $rows;
}
function imp_read_csv($path)
{
    $h = fopen($path, 'r');
    if (!$h) {
        throw new RuntimeException('File CSV tidak bisa dibuka.');
    }
    $bom = fread($h, 3);
    if ($bom !== "\xEF\xBB\xBF") {
        rewind($h);
    }
    $first = (string)fgets($h);
    rewind($h);
    if ($bom === "\xEF\xBB\xBF") {
        fread($h, 3);
    }
    $delim = ';';
    $best = substr_count($first, ';');
    foreach ([',', "\t"] as $c) {
        if (substr_count($first, $c) > $best) {
            $delim = $c;
            $best = substr_count($first, $c);
        }
    }
    $rows = [];
    while (($row = fgetcsv($h, 0, $delim, '"', '')) !== false) {
        if ($row === [null]) {
            continue;
        }
        foreach ($row as $i => $v) {
            $v = (string)$v;
            if (!mb_check_encoding($v, 'UTF-8')) {
                $v = mb_convert_encoding($v, 'UTF-8', 'Windows-1252');
            }
            $row[$i] = $v;
        }
        $rows[] = array_values($row);
    }
    fclose($h);
    return $rows;
}

/* ---------- Ubah baris tabel -> rekaman database ---------- */
function imp_build($table, $SECTIONS)
{
    if (count($table) < 2) {
        throw new RuntimeException('File kosong atau hanya berisi judul kolom.');
    }
    // peta judul kolom -> kunci field
    $byHead = [];
    $keys = [];
    foreach ($SECTIONS as $kode => $fields) {
        foreach ($fields as $k => $label) {
            $byHead[imp_norm($kode . '. ' . $label)] = $k;
            $byHead[imp_norm($label)] = $k;
            $keys[$k] = true;
        }
    }
    $meta = ['no' => 'no', 'waktu kirim' => 'waktu', 'status' => 'status', 'id' => 'id'];

    $head = $table[0];
    $cmap = [];
    $skipped = [];
    foreach ($head as $ci => $h) {
        $n = imp_norm($h);
        if ($n === '') {
            continue;
        }
        if (isset($meta[$n])) {
            $cmap[$ci] = ['m', $meta[$n]];
        } elseif (isset($byHead[$n])) {
            $cmap[$ci] = ['f', $byHead[$n]];
        } elseif (isset($keys[$n]) || preg_match('/^[a-z][a-z0-9_]*$/', $n)) {
            $cmap[$ci] = ['f', $n]; // kolom tambahan yang judulnya memang nama field
        } else {
            $skipped[] = trim((string)$h);
        }
    }
    $nField = 0;
    foreach ($cmap as $m) {
        if ($m[0] === 'f') {
            $nField++;
        }
    }
    if ($nField < 3) {
        throw new RuntimeException('Judul kolom tidak dikenali. Pakai file hasil Export dari dashboard tanpa mengubah baris judul.');
    }

    $stat = ['baris' => 0, 'kosong' => 0, 'ganda' => 0, 'waktu_salah' => 0, 'tanpa_id' => 0, 'dilewati_kolom' => $skipped];
    $records = [];
    $seen = [];
    $now = date('Y-m-d H:i:s');
    for ($ri = 1; $ri < count($table); $ri++) {
        $row = $table[$ri];
        $id = '';
        $waktu = '';
        $status = '';
        $data = [];
        foreach ($cmap as $ci => $m) {
            $v = trim((string)($row[$ci] ?? ''));
            if ($v === '') {
                continue;
            }
            if ($m[0] === 'm') {
                if ($m[1] === 'id') {
                    $id = preg_replace('/[\x00-\x1F]/', '', $v);
                } elseif ($m[1] === 'waktu') {
                    $waktu = $v;
                } elseif ($m[1] === 'status') {
                    $status = strtolower($v);
                }
                continue;
            }
            $k = $m[1];
            if ($k === 'ckg_lakukan' && $v === 'Sudah') {
                $v = 'Ya'; // export.php menampilkan "Ya" sebagai "Sudah"
            }
            if ($k === 'tgl_wawancara' && preg_match('/^\d{5}(\.\d+)?$/', $v)) {
                $v = imp_excel_serial($v, false);
            }
            $data[$k] = $v;
        }
        $stat['baris']++;
        if (!$data) {
            $stat['kosong']++;
            continue;
        }
        if ($id === '') {
            $id = 'smd_' . uniqid('', true);
            $stat['tanpa_id']++;
        }
        if (isset($seen[$id])) {
            $stat['ganda']++;
            continue;
        }
        $seen[$id] = true;

        if (preg_match('/^\d{5}(\.\d+)?$/', $waktu)) {
            $waktu = imp_excel_serial($waktu, true);
        }
        if (preg_match('/^(\d{4}-\d{2}-\d{2})[ T](\d{2}:\d{2})(:\d{2})?$/', $waktu, $mm)) {
            $waktu = $mm[1] . ' ' . $mm[2] . ($mm[3] ?? ':00');
        } else {
            $waktu = $now;
            $stat['waktu_salah']++;
        }
        if ($status !== 'valid' && $status !== 'draft') {
            $status = 'draft';
        }
        $records[] = ['id' => $id, 'waktu' => $waktu, 'status' => $status, 'data' => $data];
    }
    return [$records, $stat];
}

/* ---------- Proses ---------- */
if (empty($_SESSION['csrf_import'])) {
    $_SESSION['csrf_import'] = bin2hex(random_bytes(16));
}
$csrf = (string)$_SESSION['csrf_import'];
$error = '';
$preview = null;
$tmpDir = sys_get_temp_dir();

foreach (glob($tmpDir . '/smd_import_*.json') ?: [] as $old) { // bersihkan sisa pratinjau > 1 jam
    if (@filemtime($old) < time() - 3600) {
        @unlink($old);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = (string)($_POST['aksi'] ?? '');
    if (!hash_equals($csrf, (string)($_POST['csrf'] ?? ''))) {
        $error = 'Sesi tidak valid. Muat ulang halaman, lalu coba lagi.';
    } elseif ($aksi === 'pratinjau') {
        $f = $_FILES['berkas'] ?? null;
        if (!$f || $f['error'] === UPLOAD_ERR_NO_FILE) {
            $error = 'Pilih file Excel (.xlsx) atau CSV hasil export dulu.';
        } elseif ($f['error'] === UPLOAD_ERR_INI_SIZE || $f['error'] === UPLOAD_ERR_FORM_SIZE) {
            $error = 'File terlalu besar untuk batas server (upload_max_filesize = ' . ini_get('upload_max_filesize') . '). Naikkan batas itu di php.ini, atau unggah CSV.';
        } elseif ($f['error'] !== UPLOAD_ERR_OK) {
            $error = 'Unggahan gagal (kode ' . (int)$f['error'] . '). Coba lagi.';
        } else {
            $ext = strtolower(pathinfo((string)$f['name'], PATHINFO_EXTENSION));
            try {
                if ($ext === 'xlsx') {
                    if (!class_exists('ZipArchive')) {
                        throw new RuntimeException('Server tidak punya ekstensi zip. Unduh export dalam bentuk CSV (export.php?format=csv) lalu unggah CSV-nya.');
                    }
                    $table = imp_read_xlsx($f['tmp_name']);
                } elseif ($ext === 'csv') {
                    $table = imp_read_csv($f['tmp_name']);
                } else {
                    throw new RuntimeException('Format file harus .xlsx atau .csv.');
                }
                [$records, $stat] = imp_build($table, $SECTIONS);
                if (!$records) {
                    throw new RuntimeException('Tidak ada baris data yang bisa dibaca dari file ini.');
                }
                $cur = [];
                foreach (db_all() as $r) {
                    if (isset($r['id'])) {
                        $cur[(string)$r['id']] = true;
                    }
                }
                $nBaru = 0;
                foreach ($records as $r) {
                    if (!isset($cur[$r['id']])) {
                        $nBaru++;
                    }
                }
                $token = bin2hex(random_bytes(16));
                file_put_contents($tmpDir . '/smd_import_' . $token . '.json', json_encode(['nama' => (string)$f['name'], 'records' => $records], JSON_UNESCAPED_UNICODE));
                $_SESSION['import_token'] = $token;
                $preview = [
                    'token' => $token, 'nama' => (string)$f['name'], 'records' => $records, 'stat' => $stat,
                    'baru' => $nBaru, 'ada' => count($records) - $nBaru, 'sekarang' => count($cur),
                    'valid' => count(array_filter($records, function ($r) { return $r['status'] === 'valid'; })),
                ];
            } catch (Throwable $e) {
                $error = $e->getMessage();
            }
        }
    } elseif ($aksi === 'simpan') {
        $token = (string)($_POST['token'] ?? '');
        $mode = ($_POST['mode'] ?? '') === 'timpa' ? 'timpa' : 'lewati';
        $file = $tmpDir . '/smd_import_' . $token . '.json';
        if (!preg_match('/^[a-f0-9]{32}$/', $token) || !hash_equals((string)($_SESSION['import_token'] ?? ''), $token) || !is_file($file)) {
            $error = 'Data pratinjau sudah kedaluwarsa. Unggah ulang file-nya.';
        } else {
            $payload = json_decode((string)file_get_contents($file), true);
            $import = is_array($payload['records'] ?? null) ? $payload['records'] : [];
            $cur = db_all();

            // 1) cadangan otomatis sebelum menyentuh data
            $backupRel = '';
            if ($cur) {
                $dir = __DIR__ . '/data/backup';
                if (!is_dir($dir)) {
                    @mkdir($dir, 0755, true);
                }
                if (is_dir($dir) && !is_file($dir . '/.htaccess')) {
                    @file_put_contents($dir . '/.htaccess', "<IfModule mod_authz_core.c>\nRequire all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\nDeny from all\n</IfModule>\n");
                }
                $backupRel = 'data/backup/kuesioner-sebelum-impor-' . date('Ymd-His') . '.json';
                $okB = is_dir($dir) && @file_put_contents(__DIR__ . '/' . $backupRel, json_encode(['surveys' => $cur], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)) !== false;
                if (!$okB) {
                    $error = 'Cadangan data saat ini gagal dibuat (folder data/backup tidak bisa ditulis). Impor dibatalkan demi keamanan; tidak ada data yang diubah.';
                }
            }

            if ($error === '') {
                // 2) gabungkan
                $pos = [];
                foreach ($cur as $i => $r) {
                    if (isset($r['id'])) {
                        $pos[(string)$r['id']] = $i;
                    }
                }
                $tambah = 0;
                $timpa = 0;
                $lewat = 0;
                foreach ($import as $r) {
                    $id = (string)$r['id'];
                    if (isset($pos[$id])) {
                        if ($mode === 'timpa') {
                            $cur[$pos[$id]] = $r;
                            $timpa++;
                        } else {
                            $lewat++;
                        }
                    } else {
                        $cur[] = $r;
                        $pos[$id] = count($cur) - 1;
                        $tambah++;
                    }
                }
                usort($cur, function ($a, $b) { // urutan terlama -> terbaru, seperti saat data masuk
                    return strcmp((string)($a['waktu'] ?? ''), (string)($b['waktu'] ?? ''));
                });

                // 3) simpan lalu cek langsung isi database
                $res = db_save(['surveys' => $cur]);
                $ids = [];
                foreach (db_all() as $r) {
                    $ids[(string)($r['id'] ?? '')] = true;
                }
                $hilang = 0;
                foreach ($import as $r) {
                    if (!isset($ids[(string)$r['id']])) {
                        $hilang++;
                    }
                }
                if ($res === false || $hilang > 0 || count($ids) !== count($cur)) {
                    $error = 'Penyimpanan tidak berhasil sepenuhnya. Periksa izin tulis folder data/ dan file kuesioner.json.'
                        . ($backupRel !== '' ? ' Data sebelum impor aman di ' . $backupRel . '.' : '');
                } else {
                    @unlink($file);
                    unset($_SESSION['import_token']);
                    if (function_exists('log_aksi')) {
                        log_aksi("Impor data: +$tambah baru, $timpa ditimpa, $lewat dilewati");
                    }
                    $msg = "Impor selesai: $tambah data baru ditambahkan"
                        . ($timpa ? ", $timpa ditimpa" : '')
                        . ($lewat ? ", $lewat dilewati karena ID sudah ada" : '')
                        . '. Total sekarang ' . count($cur) . ' data.'
                        . ($backupRel !== '' ? " Cadangan sebelum impor: $backupRel." : '');
                    header('Location: dashboard.php?' . http_build_query(['msg' => $msg]));
                    exit;
                }
            }
        }
    }
}

render_layout_start('Impor Data — ' . APP_NAME, 'dashboard');
?>
<style>
.imp{max-width:880px}
.imp .card{background:#fff;border:1px solid #e3e7ec;border-radius:10px;padding:20px;margin-bottom:16px}
.imp h3{margin:0 0 6px;font-size:16px}
.imp p,.imp li{font-size:14px;line-height:1.55;color:#3d4650}
.imp .stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:10px;margin:14px 0}
.imp .stat{border:1px solid #e3e7ec;border-radius:8px;padding:12px}
.imp .stat b{display:block;font-size:24px;color:#191919}
.imp .stat span{font-size:12.5px;color:#5b6470}
.imp .opt{display:flex;gap:10px;align-items:flex-start;padding:10px 12px;border:2px solid #cbd2da;border-radius:8px;margin:8px 0;cursor:pointer}
.imp .opt input{margin-top:4px}
.imp .opt:has(input:checked){border-color:#257de0;background:#eaf2fc}
.imp table{width:100%;border-collapse:collapse;font-size:13px;margin-top:8px}
.imp th,.imp td{padding:6px 8px;border-bottom:1px solid #e3e7ec;text-align:left}
.imp .warn{background:#fdf1ea;border:1px solid #f1c3b0;border-radius:8px;padding:10px 12px;font-size:13.5px;margin-top:10px}
.imp .row-btn{display:flex;gap:10px;flex-wrap:wrap;margin-top:14px}
</style>

<div class="page-header">
    <div>
        <h2>Impor Data Kuesioner</h2>
        <p>Pulihkan atau gabungkan data dari file hasil Export (Excel / CSV).</p>
    </div>
    <div><a class="btn btn-outline" href="dashboard.php">← Kembali ke Dashboard</a></div>
</div>

<div class="imp">
<?php if ($error !== ''): ?>
    <div class="notice notice-error" style="margin-bottom:16px;"><?= esc($error); ?></div>
<?php endif; ?>

<?php if ($preview): ?>
    <div class="card">
        <h3>Pratinjau — belum ada yang disimpan</h3>
        <p>File <b><?= esc($preview['nama']); ?></b> terbaca. Periksa angka di bawah, lalu tekan <b>Simpan ke database</b>.</p>
        <div class="stats">
            <div class="stat"><b><?= count($preview['records']); ?></b><span>data terbaca dari file</span></div>
            <div class="stat"><b><?= $preview['baru']; ?></b><span>data baru (ID belum ada di database)</span></div>
            <div class="stat"><b><?= $preview['ada']; ?></b><span>ID sudah ada di database</span></div>
            <div class="stat"><b><?= $preview['sekarang']; ?></b><span>data di database sekarang</span></div>
            <div class="stat"><b><?= $preview['valid']; ?></b><span>berstatus valid (sisanya draft)</span></div>
        </div>
        <?php
        $catatan = [];
        if ($preview['stat']['kosong']) { $catatan[] = $preview['stat']['kosong'] . ' baris kosong dilewati'; }
        if ($preview['stat']['ganda']) { $catatan[] = $preview['stat']['ganda'] . ' baris dengan ID kembar dalam file dilewati'; }
        if ($preview['stat']['tanpa_id']) { $catatan[] = $preview['stat']['tanpa_id'] . ' baris tanpa ID diberi ID baru'; }
        if ($preview['stat']['waktu_salah']) { $catatan[] = $preview['stat']['waktu_salah'] . ' baris waktu kirimnya tidak terbaca, diisi waktu impor'; }
        if ($preview['stat']['dilewati_kolom']) { $catatan[] = 'kolom tidak dikenali dan dilewati: ' . implode('; ', array_slice($preview['stat']['dilewati_kolom'], 0, 6)); }
        if ($catatan): ?>
            <div class="warn"><b>Catatan:</b> <?= esc(implode(' · ', $catatan)); ?>.</div>
        <?php endif; ?>

        <table>
            <thead><tr><th>Inisial</th><th>Kelurahan</th><th>Usia</th><th>Status</th><th>Waktu kirim</th></tr></thead>
            <tbody>
            <?php foreach (array_slice($preview['records'], 0, 5) as $r): ?>
                <tr>
                    <td><?= esc($r['data']['inisial'] ?? '-'); ?></td>
                    <td><?= esc($r['data']['kelurahan'] ?? '-'); ?></td>
                    <td><?= esc($r['data']['usia'] ?? '-'); ?></td>
                    <td><?= esc($r['status']); ?></td>
                    <td><?= esc($r['waktu']); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <p style="font-size:12.5px;color:#5b6470;margin:6px 0 0">Menampilkan 5 dari <?= count($preview['records']); ?> data.</p>
    </div>

    <form method="post" class="card">
        <input type="hidden" name="aksi" value="simpan">
        <input type="hidden" name="csrf" value="<?= esc($csrf); ?>">
        <input type="hidden" name="token" value="<?= esc($preview['token']); ?>">
        <h3>Jika ID sudah ada di database</h3>
        <label class="opt"><input type="radio" name="mode" value="lewati" checked>
            <span><b>Lewati (disarankan)</b><br>Data yang sudah ada tidak diubah. Aman untuk menambah data yang hilang.</span></label>
        <label class="opt"><input type="radio" name="mode" value="timpa">
            <span><b>Timpa dengan isi file</b><br>Data dengan ID yang sama diganti isi file. Perubahan yang dibuat setelah export akan hilang.</span></label>
        <p style="margin-top:12px">Sebelum menyimpan, sistem otomatis membuat <b>cadangan</b> data yang ada sekarang di folder <code>data/backup/</code>.
            Jika cadangan gagal dibuat, impor dibatalkan.</p>
        <div class="row-btn">
            <button class="btn" type="submit" onclick="return confirm('Simpan <?= count($preview['records']); ?> data dari file ke database sekarang?');">Simpan ke database</button>
            <a class="btn btn-outline" href="import.php">Batal</a>
        </div>
    </form>

<?php else: ?>
    <form method="post" enctype="multipart/form-data" class="card">
        <input type="hidden" name="aksi" value="pratinjau">
        <input type="hidden" name="csrf" value="<?= esc($csrf); ?>">
        <h3>1. Pilih file hasil Export</h3>
        <p>Pakai file <b>.xlsx</b> atau <b>.csv</b> yang diunduh dari tombol Export di dashboard (data semua responden), jangan ubah baris judul kolom.
            Tahap ini hanya membaca file dan menampilkan pratinjau, belum ada data yang diubah.</p>
        <p><input type="file" name="berkas" accept=".xlsx,.csv" required></p>
        <div class="row-btn"><button class="btn" type="submit">Baca file &amp; lihat pratinjau</button></div>
    </form>
    <div class="card">
        <h3>Yang dipulihkan</h3>
        <ul>
            <li>Semua jawaban, <b>ID</b>, <b>waktu kirim</b>, dan <b>status</b> (valid / draft) sama seperti saat di-export.</li>
            <li>Data yang sudah ada di database tidak hilang: file digabung, bukan menggantikan seluruh database.</li>
            <li>Tidak ikut kembali: informasi "diubah pada" (waktu edit terakhir) karena tidak ada di file Export.</li>
        </ul>
    </div>
<?php endif; ?>
</div>

<?php render_layout_end(); ?>