<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/layout.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$search = trim((string)($_GET['search'] ?? ''));
$status = in_array($_GET['status'] ?? '', ['', 'valid', 'draft'], true) ? (string)($_GET['status'] ?? '') : '';

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
        log_aksi("Validasi kuesioner ID=$targetId");
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

// Filter Data
$filtered = $allRows;
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

if ($status !== '') {
    $filtered = array_values(array_filter($filtered, static fn($r) => ($r['status'] ?? 'draft') === $status));
}

// Pagination
$perPage    = 20;
$totalItems = count($filtered);
$totalPages = max(1, (int)ceil($totalItems / $perPage));
$page       = min(max(1, (int)($_GET['page'] ?? 1)), $totalPages);
$offset     = ($page - 1) * $perPage;
$rows       = array_slice($filtered, $offset, $perPage);

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