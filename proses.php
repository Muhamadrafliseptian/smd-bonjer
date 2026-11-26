<?php
/**
 * proses.php — Proses hapus (satu / banyak) dan simpan/ubah kuesioner.
 * HANYA UNTUK ADMIN yang sudah login. Form publik (form.php) menyimpan lewat dirinya sendiri,
 * bukan lewat file ini, supaya captcha tidak bisa dilewati.
 */

session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/view.php';
require_once __DIR__ . '/database.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

log_aksi("POST dari " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));

/** Kembali ke dashboard dengan pesan; filter yang sedang dipakai ikut dibawa. */
function smd_back_to_dashboard(string $msg, string $err, string $back): void
{
    $q = [];
    parse_str($back, $b);
    foreach (['search', 'status', 'kelurahan', 'risiko'] as $k) {
        if (isset($b[$k]) && is_string($b[$k]) && $b[$k] !== '') {
            $q[$k] = $b[$k];
        }
    }
    if ($msg !== '') {
        $q['msg'] = $msg;
    }
    if ($err !== '') {
        $q['err'] = $err;
    }
    header('Location: dashboard.php' . ($q ? '?' . http_build_query($q) : ''));
    exit;
}

$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';

    if ($aksi === 'hapus') {
        $back  = (string)($_POST['back'] ?? '');
        $csrf  = (string)($_POST['csrf'] ?? '');
        $token = (string)($_SESSION['csrf_hapus'] ?? '');
        if ($token === '' || !hash_equals($token, $csrf)) {
            smd_back_to_dashboard('', 'Sesi tidak valid. Muat ulang halaman, lalu coba hapus lagi.', $back);
        }

        // Satu data (id) atau banyak data (ids[]); duplikat dibuang.
        $ids = [];
        foreach ((array)($_POST['ids'] ?? []) as $i) {
            if (is_string($i) && $i !== '') {
                $ids[] = $i;
            }
        }
        if (isset($_POST['id']) && is_string($_POST['id']) && $_POST['id'] !== '') {
            $ids[] = $_POST['id'];
        }
        $ids = array_values(array_unique($ids));
        if (!$ids) {
            smd_back_to_dashboard('', 'Tidak ada data yang dipilih untuk dihapus.', $back);
        }

        $set = array_flip($ids);
        $keep = [];
        $removed = 0;
        foreach (db_all() as $r) {
            if (isset($r['id']) && isset($set[(string)$r['id']])) {
                $removed++;
                continue;
            }
            $keep[] = $r;
        }
        if ($removed === 0) {
            smd_back_to_dashboard('', 'Data tidak ditemukan. Mungkin sudah dihapus sebelumnya.', $back);
        }

        $saveResult = db_save(['surveys' => $keep]);
        // Jangan hanya percaya nilai kembalian db_save(): cek langsung apakah datanya benar-benar sudah hilang.
        $masihAda = 0;
        foreach (db_all() as $r) {
            if (isset($r['id']) && isset($set[(string)$r['id']])) {
                $masihAda++;
            }
        }
        if ($saveResult === false || $masihAda > 0) {
            smd_back_to_dashboard('', 'Data gagal dihapus. Periksa apakah folder data/ dan file kuesioner.json bisa ditulis oleh server.', $back);
        }

        log_aksi('Hapus kuesioner (' . $removed . ' data) ID=' . implode(',', $ids) . ' oleh ' . ($_SESSION['admin_user'] ?? 'admin'));
        smd_back_to_dashboard($removed . ' data kuesioner berhasil dihapus.', '', $back);
    }

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
    }
}

// Pesan dari GET ditampilkan sebagai teks biasa (di-escape), bukan HTML.
if ($msg === '' && isset($_GET['msg'])) {
    $msg = '<div class="' . ALERT_SUCCESS . '">' . htmlspecialchars((string)$_GET['msg'], ENT_QUOTES, 'UTF-8') . '</div>';
}
if ($err === '' && isset($_GET['err'])) {
    $err = '<div class="' . ALERT_ERROR . '">' . htmlspecialchars((string)$_GET['err'], ENT_QUOTES, 'UTF-8') . '</div>';
}

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