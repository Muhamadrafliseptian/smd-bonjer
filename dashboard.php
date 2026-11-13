<?php
/**
 * dashboard.php — Halaman utama admin (rekapitulasi & manajemen data kuesioner).
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

$search       = trim((string)($_GET['search'] ?? ''));
$status       = in_array($_GET['status'] ?? '', ['', 'valid', 'draft'], true) ? (string)($_GET['status'] ?? '') : '';
$selectedKel  = trim((string)($_GET['kelurahan'] ?? ''));
$selectedRisk = trim((string)($_GET['risiko'] ?? ''));

// Proses Validasi (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'valid' && !empty($_POST['id'])) {
    $targetId = (string)$_POST['id'];
    $allData  = db_all();
    $found    = false;

    foreach ($allData as &$row) {
        if (($row['id'] ?? '') === $targetId) {
            $row['status'] = 'valid';
            $found = true;
            break;
        }
    }
    unset($row);

    if ($found && db_save(['surveys' => $allData])) {
        header('Location: ' . build_url('dashboard.php', ['msg' => 'Data kuesioner berhasil divalidasi.']));
    } else {
        header('Location: ' . build_url('dashboard.php', ['err' => 'Gagal memvalidasi data.']));
    }
    exit;
}

$msg = trim((string)($_GET['msg'] ?? ''));
$err = trim((string)($_GET['err'] ?? ''));

// Hitung Statistik & Urutkan Terbaru
$allRows = db_all();
usort($allRows, static fn($a, $b) => strcmp((string)($b['waktu'] ?? ''), (string)($a['waktu'] ?? '')));

$today  = date('Y-m-d');
$counts = ['total' => count($allRows), 'valid' => 0, 'draft' => 0, 'today' => 0];

foreach ($allRows as $r) {
    (($r['status'] ?? '') === 'valid') ? $counts['valid']++ : $counts['draft']++;
    if (strpos((string)($r['waktu'] ?? ''), $today) === 0) {
        $counts['today']++;
    }
}

// -------------------------------------------------------------
// FILTERING DATA
// -------------------------------------------------------------
$filtered = $allRows;

// 1. Filter Pencarian Teks Bebas
if ($search !== '') {
    $needle = mb_strtolower($search, 'UTF-8');
    $filtered = array_values(array_filter($filtered, static function ($r) use ($needle) {
        foreach ($r['data'] ?? [] as $val) {
            $str = mb_strtolower(is_array($val) ? implode(' ', $val) : (string)$val, 'UTF-8');
            if (strpos($str, $needle) !== false) return true;
        }
        return false;
    }));
}

// 2. Filter Berdasarkan Status (Valid / Draft)
if ($status !== '') {
    $filtered = array_values(array_filter($filtered, static fn($r) => ($r['status'] ?? 'draft') === $status));
}

// 3. Filter Berdasarkan Kelurahan
if ($selectedKel !== '') {
    $filtered = array_values(array_filter($filtered, static function ($r) use ($selectedKel) {
        $kel = trim((string)($r['data']['kelurahan'] ?? ''));
        return strcasecmp($kel, $selectedKel) === 0;
    }));
}

// 4. Filter Berdasarkan Kondisi Kesehatan / Risiko (Format: field_nilai, misal: hipertensi_Ya)
if ($selectedRisk !== '' && str_contains($selectedRisk, '_')) {
    [$field, $targetVal] = explode('_', $selectedRisk, 2);
    $filtered = array_values(array_filter($filtered, static function ($r) use ($field, $targetVal) {
        $val = trim((string)($r['data'][$field] ?? ''));
        return strcasecmp($val, $targetVal) === 0;
    }));
}

// Berikan data yang sudah terfilter ke variabel $rows agar dibaca oleh tabel
$rows       = $filtered;
$totalItems = count($filtered);

// Render Halaman
render_layout_start('Dashboard — ' . APP_NAME, 'dashboard');
?>

<div class="page-header">
    <div>
        <h2>Survei Mawas Diri — Puskesmas Kebon Jeruk</h2>
        <p>Rekapitulasi, verifikasi, dan pengelolaan data kuesioner warga.</p>
    </div>
</div>

<?php if ($msg !== ''): ?>
    <div class="notice notice-success"><?= esc($msg); ?></div>
<?php endif; ?>
<?php if ($err !== ''): ?>
    <div class="notice notice-error"><?= esc($err); ?></div>
<?php endif; ?>

<?php require __DIR__ . '/components/dashboard/stats.php'; ?>

<div class="data-card">
    <?php require __DIR__ . '/components/dashboard/filter_bar.php'; ?>
    <?php require __DIR__ . '/components/dashboard/table.php'; ?>
</div>

<?php render_layout_end(); ?>