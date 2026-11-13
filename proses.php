<?php
/**
 * proses.php — Proses simpan / hapus kuesioner.
 * Public (tanpa login). Hanya butuh POST.
 */

session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/view.php';
require_once __DIR__ . '/database.php';

// Boleh akses dari public, tapi log akses
log_aksi("POST dari " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));

$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';

    if ($aksi === 'simpan' || $aksi === 'edit') {
        $raw = $_POST;
        $now = date('Y-m-d H:i:s');
        $result = [];
        foreach ($raw as $k => $v) {
            if ($k === 'aksi') {
                continue;
            }
            if (is_array($v)) {
                $vals = array_values(array_filter(array_map('trim', $v), 'strlen'));
                $result[$k] = $vals;
            } else {
                $s = trim((string)$v);
                $result[$k] = $s === '' ? '' : $s;
            }
        }
        unset($raw);

        $clean = [];
        foreach ($result as $k => $v) {
            if ($v === '' || (is_array($v) && count($v) === 0)) {
                continue;
            }
            $clean[$k] = $v;
        }

        $id = $clean['id'] ?? 'smd_' . uniqid('', true);
        unset($clean['id']);
        $wajib = ['inisial', 'alamat', 'jenis_kelamin', 'pendidikan', 'pekerjaan', 'tgl_wawancara'];
        $laki = array_filter($wajib, function ($f) use ($clean) {
            return !isset($clean[$f]) || $clean[$f] === '';
        });
        $status = $laki ? 'draft' : 'valid';

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
                $out[] = $record;
                $found = true;
            } else {
                $out[] = $r;
            }
        }
        if (!$found) {
            $out[] = $record;
        }
        $ok = db_save(['surveys' => $out]);

        if ($ok) {
            $lbl = $found ? 'diperbarui' : 'disimpan';
            $msg = '<div class="' . ALERT_SUCCESS . '">Data kuesioner berhasil ' . $lbl . '. Status: <strong>' . $status . '</strong>.</div>';
        } else {
            $err = '<div class="' . ALERT_ERROR . '">Gagal menyimpan data.</div>';
        }
    } elseif ($aksi === 'hapus') {
        $id = $_POST['id'] ?? '';
        if ($id !== '') {
            $ok = db_delete($id);
            if ($ok) {
                $msg = '<div class="' . ALERT_SUCCESS . '">Data berhasil dihapus.</div>';
            } else {
                $err = '<div class="' . ALERT_ERROR . '">Gagal menghapus data.</div>';
            }
        } else {
            $err = '<div class="' . ALERT_ERROR . '">ID tidak diberikan.</div>';
        }
    }
}

// Redirect belakang (avoid form resubmission) -> bawa msg/err dari GET
$msg = $_GET['msg'] ?? $msg;
$err = $_GET['err'] ?? $err;

layout_head('Proses — ' . APP_NAME);
?>
<div class="wrap" style="max-width:720px;">
    <div class="card" style="padding:24px;">
        <div style="text-align:center;margin-bottom:16px;">
            <div style="font-size:24px;font-weight:800;color:var(--primary);"><?php echo APP_LABEL; ?></div>
            <div style="color:var(--muted);font-size:13px;margin-top:4px;"><?php echo APP_SUB; ?></div>
        </div>
        <?php echo $msg; ?>
        <?php echo $err; ?>
        <div style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap;">
            <a class="btn btn-secondary" href="dashboard.php">← Kembali ke Dashboard</a>
        </div>
    </div>
</div>
<?php
layout_tail();
