<?php
/**
 * detail.php — Tampilan lengkap 1 kuesioner (admin).
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

function format_val(string $key, $val): string
{
    if (is_array($val)) {
        $val = implode(', ', array_filter($val, 'strlen'));
    }
    $val = trim((string)$val);
    if ($key === 'ckg_lakukan' && $val === 'Ya') {
        return 'Sudah';
    }
    return $val;
}

$id     = (string)($_GET['id'] ?? '');
$record = $id !== '' ? db_find($id) : null;
$d      = $record['data'] ?? [];

$IDENTITAS_FIELDS = [
    'inisial'       => 'Inisial Responden',
    'usia'          => 'Usia',
    'jenis_kelamin' => 'Jenis Kelamin',
    'pendidikan'    => 'Pendidikan Terakhir',
    'pekerjaan'     => 'Pekerjaan',
    'tgl_wawancara' => 'Tanggal Wawancara',
    'kelurahan'     => 'Kelurahan',
    'alamat'        => 'Alamat Lengkap',
];

$SECTIONS = [
    'B' => ['Kesehatan Reproduksi, Kehamilan & Persalinan', [
        'hamil'             => ['Pernah hamil / sedang hamil?', false],
        'hamil_cek'         => ['Memeriksakan kehamilan ke fasilitas kesehatan?', true],
        'hamil_alas'        => ['Alasan tidak memeriksakan kehamilan', true],
        'melahirkan'        => ['Sudah pernah melahirkan?', false],
        'melahirkan_tempat' => ['Melahirkan di fasilitas kesehatan?', true],
        'melahirkan_alas'   => ['Alasan tidak melahirkan di fasilitas kesehatan', true],
        'iva_papsmear'      => ['Pernah deteksi dini kanker serviks (IVA / Pap Smear / HPV DNA)?', false],
        'iva_saat'          => ['Terakhir kali periksa', true],
        'iva_tempat'        => ['Tempat pemeriksaan', true],
        'iva_tempat_lain'   => ['Tempat pemeriksaan (lainnya)', true],
        'iva_alas'          => ['Alasan belum pernah periksa', true],
    ]],
    'C' => ['Kesehatan Anak Usia 0–59 Bulan', [
        'idl'               => ['Imunisasi dasar lengkap (IDL) usia 0–24 bulan?', false],
        'idl_alas'          => ['Alasan tidak imunisasi lengkap', true],
        'asi'               => ['ASI eksklusif usia 0–6 bulan?', false],
        'asi_alas'          => ['Alasan tidak ASI eksklusif', true],
        'pantau'            => ['Tumbuh kembang dipantau usia 2–59 bulan?', false],
        'pantau_alas'       => ['Alasan tidak dipantau', true],
        'imunisasi_sekolah' => ['Imunisasi MR / Dt / Td usia sekolah dasar', false],
        'imunisasi_alas'    => ['Alasan belum imunisasi', true],
    ]],
    'D' => ['Kesehatan Keluarga, Lingkungan & Kebiasaan Sehat', [
        'usia_subur'      => ['Termasuk usia subur (20–45 tahun)?', false],
        'kontrasepsi'     => ['Menggunakan alat kontrasepsi?', true],
        'subur_alas'      => ['Alasan tidak menggunakan kontrasepsi', true],
        'diagnosa_tb'     => ['Pernah didiagnosis TB?', false],
        'tb_rutin'        => ['Berobat TB rutin?', true],
        'tb_alas'         => ['Alasan tidak berobat rutin (TB)', true],
        'masker'          => ['Memakai masker saat batuk / gejala ISPA', false],
        'masker_alas'     => ['Alasan jarang / tidak memakai masker', true],
        'hipertensi'      => ['Pernah / sedang menderita hipertensi?', false],
        'ht_rutin'        => ['Berobat hipertensi rutin?', true],
        'hipertensi_alas' => ['Alasan tidak berobat rutin (hipertensi)', true],
        'diabetes'        => ['Pernah / sedang menderita diabetes melitus?', false],
        'dm_rutin'        => ['Berobat diabetes rutin?', true],
        'diabetes_alas'   => ['Alasan tidak berobat rutin (diabetes)', true],
        'kejiwaan'        => ['Pernah / sedang mengalami masalah kejiwaan?', false],
        'jiwa_rutin'      => ['Berobat kejiwaan rutin?', true],
        'kejiwaan_alas'   => ['Alasan tidak berobat rutin (kejiwaan)', true],
        'merokok'         => ['Memiliki kebiasaan merokok?', false],
        'tempat_smoke'    => ['Tempat merokok', true],
    ]],
    'E' => ['Akses Pelayanan & Jaminan Kesehatan', [
        'jaminan'      => ['Seluruh anggota keluarga punya JKN / BPJS?', false],
        'jaminan_alas' => ['Alasan belum semua punya jaminan', true],
        'air_bersih'   => ['Memiliki sarana air bersih?', false],
        'air_alas'     => ['Alasan tidak punya air bersih', true],
        'jamban'       => ['Memiliki jamban sehat?', false],
        'jamban_alas'  => ['Alasan tidak punya jamban sehat', true],
        'cuci_tangan'  => ['Cuci tangan pakai sabun (CTPS)', false],
        'cuci_alas'    => ['Alasan belum CTPS', true],
        'fisik'        => ['Kebiasaan aktivitas fisik', false],
        'fisik_alas'   => ['Alasan belum aktivitas fisik', true],
        'buah'         => ['Kebiasaan makan buah & sayur', false],
        'buah_alas'    => ['Alasan belum makan buah & sayur', true],
        'toaga'        => ['Memiliki tanaman obat keluarga (TOGA)?', false],
        'toaga_jenis'  => ['Jenis TOGA', true],
    ]],
    'F' => ['CKG & Kekerasan dalam Rumah Tangga', [
        'ckg_tau'           => ['Tahu tempat Cek Kesehatan Gratis (CKG)?', false],
        'ckg_tau_tempat'    => ['Tempat CKG yang diketahui', true],
        'ckg_lakukan'       => ['Sudah melakukan CKG?', false],
        'ckg_alas'          => ['Alasan belum CKG', true],
        'kekerasan'         => ['Pernah mengalami kekerasan dalam rumah tangga?', false],
        'kekerasan_lapor'   => ['Pernah melaporkan?', true],
        'kekerasan_bantuan' => ['Membutuhkan bantuan instansi?', true],
    ]],
    'G' => ['Pengenalan Layanan & Lain-Lain', [
        'skpk'        => ['Tahu kewajiban lapor kematian wajar ke Puskesmas (SKPK)?', false],
        'skmk'        => ['Tahu ketentuan SKMK?', false],
        'disabilitas' => ['Penyandang disabilitas mendapat kemudahan layanan?', false],
        'dis_alas'    => ['Kendala akses layanan disabilitas', true],
        'ilp_tahu'    => ['Pernah mendengar Puskesmas ILP?', false],
        'ilp_kinerja' => ['Puskesmas sudah menerapkan ILP dengan baik?', true],
        'ilp_alas'    => ['Alasan ILP belum baik', true],
        'saran'       => ['Masalah kesehatan sekitar, kritik & saran', false],
    ]],
];

// Kumpulkan field tambahan di luar daftar
$knownKeys = array_flip(array_merge(array_keys($IDENTITAS_FIELDS), ['rt', 'rw']));
foreach ($SECTIONS as $sec) {
    foreach ($sec[1] as $k => $_) {
        $knownKeys[$k] = true;
    }
}
$extraFields = array_diff_key($d, $knownKeys);
$status      = ($record['status'] ?? 'draft') === 'valid' ? 'valid' : 'draft';

render_layout_start('Detail Kuesioner — ' . APP_NAME, 'dashboard');
?>

<style>
    .detail-wrap { max-width: 980px; }
    .detail-actions { display: flex; gap: 8px; flex-wrap: wrap; }

    /* Grid Identitas (Bagian A) */
    .profile-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 16px;
        padding: 18px;
    }
    @media (max-width: 768px) { .profile-grid { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 480px) { .profile-grid { grid-template-columns: 1fr; } }

    .profile-item.span-2 { grid-column: span 2; }
    @media (max-width: 480px) { .profile-item.span-2 { grid-column: span 1; } }

    .profile-label { font-size: 12px; color: var(--text-muted); margin-bottom: 2px; }
    .profile-value { font-size: 14px; font-weight: 600; color: #0f172a; }

    /* Daftar Pertanyaan & Jawaban */
    .section-block { margin-top: 20px; }
    .section-head {
        padding: 12px 18px;
        background: var(--bg-subtle);
        border-bottom: 1px solid var(--border-light);
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .section-tag {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 24px;
        height: 24px;
        border-radius: 5px;
        background: var(--primary-soft);
        color: var(--primary);
        font-weight: 700;
        font-size: 12.5px;
        border: 1px solid #99f6e4;
    }
    .section-title { font-weight: 700; font-size: 14px; color: #0f172a; }

    .qa-list { display: flex; flex-direction: column; }
    .qa-item {
        display: grid;
        grid-template-columns: minmax(0, 1.5fr) minmax(0, 1fr);
        gap: 20px;
        padding: 12px 18px;
        border-bottom: 1px solid var(--border-light);
        align-items: baseline;
    }
    .qa-item:last-child { border-bottom: none; }
    .qa-item:hover { background: #fafbfc; }

    .qa-item.is-sub {
        background: #f8fafc;
        padding-left: 34px;
    }
    .qa-item.is-sub .qa-q {
        color: var(--text-muted);
        position: relative;
    }
    .qa-item.is-sub .qa-q::before {
        content: "↳";
        position: absolute;
        left: -16px;
        color: #94a3b8;
    }

    .qa-q { font-size: 13.5px; color: #334155; line-height: 1.45; }
    .qa-a { font-size: 13.5px; font-weight: 600; color: #0f172a; white-space: pre-wrap; word-break: break-word; }
    .qa-a.is-empty { color: #94a3b8; font-weight: 400; font-style: italic; }

    .ans-badge {
        display: inline-block;
        padding: 2px 8px;
        border-radius: 4px;
        font-size: 12.5px;
        font-weight: 600;
    }
    .ans-ok { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
    .ans-warn { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }

    @media (max-width: 640px) {
        .qa-item { grid-template-columns: 1fr; gap: 4px; }
        .qa-item.is-sub { padding-left: 26px; }
    }
</style>

<div class="detail-wrap">
    <?php if (!$record): ?>
        <div class="data-card">
            <div class="empty-state">
                <h3>Data kuesioner tidak ditemukan</h3>
                <p>Entri mungkin sudah dihapus atau ID kuesioner tidak valid.</p>
                <a class="btn btn-primary" href="dashboard.php">Kembali ke Dashboard</a>
            </div>
        </div>
    <?php else:
        $inisial = trim((string)($d['inisial'] ?? '')) ?: 'Tanpa Inisial';
        $rt      = trim((string)($d['rt'] ?? ''));
        $rw      = trim((string)($d['rw'] ?? ''));
        $alamatFull = implode(', ', array_filter([
            trim((string)($d['alamat'] ?? '')),
            ($rt !== '' || $rw !== '') ? 'RT ' . ($rt ?: '-') . ' / RW ' . ($rw ?: '-') : '',
        ])) ?: '-';
    ?>
        <div class="page-header">
            <div>
                <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                    <h2>Detail Kuesioner — <?= esc($inisial); ?></h2>
                    <span class="badge <?= $status === 'valid' ? 'badge-valid' : 'badge-draft'; ?>">
                        <?= $status === 'valid' ? 'Tervalidasi' : 'Draft'; ?>
                    </span>
                </div>
                <p>Diinput pada <?= esc(format_waktu($record['waktu'] ?? '')); ?> · <?= count($d); ?> item terisi</p>
            </div>

            <div class="detail-actions">
                <a class="btn btn-outline" href="dashboard.php">Kembali</a>
                <a class="btn btn-primary" href="export.php?id=<?= urlencode($record['id']); ?>">Export</a>
            </div>
        </div>

        <!-- Bagian A: Profil / Identitas Responden dalam Grid Ringkas -->
        <div class="data-card">
            <div class="section-head">
                <span class="section-tag">A</span>
                <span class="section-title">Identitas Responden</span>
            </div>
            <div class="profile-grid">
                <?php foreach ($IDENTITAS_FIELDS as $key => $label):
                    $val = $key === 'alamat' ? $alamatFull : format_val($key, $d[$key] ?? '');
                    if ($key === 'usia' && $val !== '') {
                        $val .= ' tahun';
                    }
                    $spanClass = $key === 'alamat' ? ' span-2' : '';
                ?>
                    <div class="profile-item<?= $spanClass; ?>">
                        <div class="profile-label"><?= esc($label); ?></div>
                        <div class="profile-value"><?= $val !== '' ? esc($val) : '-'; ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Bagian B - G: Daftar Jawaban Kuesioner -->
        <?php foreach ($SECTIONS as $kode => [$judul, $fields]): ?>
            <div class="data-card section-block">
                <div class="section-head">
                    <span class="section-tag"><?= esc($kode); ?></span>
                    <span class="section-title"><?= esc($judul); ?></span>
                </div>
                <div class="qa-list">
                    <?php foreach ($fields as $key => [$label, $isSub]):
                        $val      = format_val($key, $d[$key] ?? '');
                        $hasValue = $val !== '';

                        if ($isSub && !$hasValue) {
                            continue;
                        }

                        // Tentukan highlight semantik untuk jawaban singkat
                        $isPositive = in_array($val, ['Ya', 'Sudah', 'Tahu', 'Selalu'], true);
                        $isNegative = in_array($val, ['Tidak', 'Belum', 'Tidak tahu', 'Tidak pernah'], true);
                    ?>
                        <div class="qa-item<?= $isSub ? ' is-sub' : ''; ?>">
                            <div class="qa-q"><?= esc($label); ?></div>
                            <div class="qa-a<?= !$hasValue ? ' is-empty' : ''; ?>">
                                <?php if (!$hasValue): ?>
                                    Tidak diisi
                                <?php elseif ($isPositive): ?>
                                    <span class="ans-badge ans-ok"><?= esc($val); ?></span>
                                <?php elseif ($isNegative): ?>
                                    <span class="ans-badge ans-warn"><?= esc($val); ?></span>
                                <?php else: ?>
                                    <?= esc($val); ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <?php if ($extraFields): ?>
            <div class="data-card section-block">
                <div class="section-head">
                    <span class="section-tag">+</span>
                    <span class="section-title">Data Tambahan</span>
                </div>
                <div class="qa-list">
                    <?php foreach ($extraFields as $key => $val): ?>
                        <div class="qa-item">
                            <div class="qa-q"><?= esc($key); ?></div>
                            <div class="qa-a"><?= esc(format_val($key, $val)); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php render_layout_end(); ?>