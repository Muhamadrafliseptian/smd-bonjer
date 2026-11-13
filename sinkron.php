<?php
/**
 * sinkron.php — Sinkronisasi data kuesioner ke Google Spreadsheet.
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

$msg      = '';
$err      = '';
$ready    = true;
$readyMsg = 'Konfigurasi siap untuk melakukan sinkronisasi.';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sinkronisasi'])) {
    $baris = [];
    foreach (db_all() as $r) {
        $baris[] = $r['data'] ?? [];
    }
    $total = count($baris);

    if (!USE_SPREADSHEET) {
        $ready    = false;
        $readyMsg = 'USE_SPREADSHEET bernilai false di config.php. Data saat ini hanya disimpan secara lokal di JSON.';
    }
    if (!defined('SHEET_API_URL') || SHEET_API_URL === '') {
        $ready    = false;
        $readyMsg .= ' Isi SHEET_API_URL (endpoint Google Sheets API) di config.php.';
    }
    if (!file_exists(DATA_DIR . '/service-account.json') && USE_SPREADSHEET) {
        $ready    = false;
        $readyMsg .= ' Pasang file service-account.json di folder data/ jika menggunakan Service Account.';
    }

    if (!$ready) {
        $err = $readyMsg;
    } elseif ($total === 0) {
        $err = 'Tidak ada data kuesioner untuk dikirim.';
    } else {
        $headers = array_keys($baris[0]);
        $payload = ['values' => array_merge([$headers], $baris)];
        $json    = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        $ch = curl_init(SHEET_API_URL);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $httpHeaders = [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($json),
        ];

        if (defined('SHEET_CREDS') && file_exists(SHEET_CREDS)) {
            $creds = json_decode(file_get_contents(SHEET_CREDS), true);
            $tok   = curl_init('https://oauth2.googleapis.com/token');
            curl_setopt($tok, CURLOPT_POST, true);
            curl_setopt($tok, CURLOPT_POSTFIELDS, http_build_query([
                'grant_type'    => 'client_credentials',
                'client_id'     => $creds['client_email'] ?? '',
                'client_secret' => $creds['client_secret'] ?? '',
            ]));
            curl_setopt($tok, CURLOPT_RETURNTRANSFER, true);
            $tokData = json_decode((string)curl_exec($tok), true);
            curl_close($tok);

            $token = $tokData['access_token'] ?? '';
            if ($token !== '') {
                array_unshift($httpHeaders, 'Authorization: Bearer ' . $token);
            }
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $httpHeaders);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        $res  = curl_exec($ch);
        $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $errC = curl_error($ch);
        curl_close($ch);

        if ($http >= 200 && $http < 300) {
            $logFile = DATA_DIR . '/log_sync_' . date('Ymd_His') . '.json';
            $syncLog = [
                'waktu'            => date('Y-m-d H:i:s'),
                'url'              => SHEET_API_URL,
                'total_baris'      => $total,
                'http'             => $http,
                'response_preview' => mb_substr($res ?? '', 0, 500),
                'error'            => $errC,
            ];
            file_put_contents($logFile, json_encode($syncLog, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n", FILE_APPEND | LOCK_EX);
            $msg = "Sinkronisasi berhasil. Sebanyak {$total} baris data telah dikirim ke Google Spreadsheet.";
            log_aksi("Sinkronisasi ke Google Sheet dari " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
        } else {
            $err = 'Gagal sinkronisasi (HTTP ' . $http . '). Periksa URL dan kredensial. ' . ($errC ?: '');
        }
    }
}

$rows = db_all();

render_layout_start('Sinkronisasi ke Spreadsheet — ' . APP_NAME, 'sinkron');
?>

<div class="page-header">
    <div>
        <h2>Sinkronisasi Google Spreadsheet</h2>
        <p>Kirim seluruh rekap data kuesioner lokal ke Google Sheets.</p>
    </div>
    <a class="btn btn-outline" href="dashboard.php">Kembali ke Dashboard</a>
</div>

<?php if ($msg !== ''): ?>
    <div class="notice notice-success"><?= esc($msg); ?></div>
<?php endif; ?>

<?php if ($err !== ''): ?>
    <div class="notice notice-error"><?= esc($err); ?></div>
<?php endif; ?>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 16px; margin-bottom: 20px;">
    <div class="data-card" style="padding: 18px;">
        <h3 style="margin: 0 0 8px; font-size: 15px; color: #0f172a;">Status Konfigurasi</h3>
        <p style="margin: 0 0 12px; font-size: 13.5px; color: var(--text-muted);"><?= esc($readyMsg); ?></p>
        <div style="font-size: 13px; color: var(--text-main);">
            Mode Spreadsheet:
            <?php if (defined('USE_SPREADSHEET') && USE_SPREADSHEET): ?>
                <span class="badge badge-valid">Aktif</span>
            <?php else: ?>
                <span class="badge badge-draft">Nonaktif (JSON Lokal)</span>
            <?php endif; ?>
        </div>
    </div>

    <div class="data-card" style="padding: 18px;">
        <h3 style="margin: 0 0 8px; font-size: 15px; color: #0f172a;">Panduan Pengaturan Kredensial</h3>
        <ol style="margin: 0; padding-left: 18px; font-size: 13px; color: var(--text-muted); line-height: 1.65;">
            <li>Buka <code>config.php</code> lalu atur <code>define('USE_SPREADSHEET', true);</code></li>
            <li>Isi konstanta <code>SHEET_API_URL</code> dengan endpoint Google Sheets API.</li>
            <li>Letakkan file <code>service-account.json</code> di folder <code>data/</code>.</li>
            <li>Klik tombol <strong>Sinkronisasi Sekarang</strong> di bawah.</li>
        </ol>
    </div>
</div>

<div class="data-card">
    <div class="toolbar">
        <div>
            <strong style="font-size: 14px; color: #0f172a;">Pratinjau Data Siap Kirim</strong>
            <span style="font-size: 13px; color: var(--text-muted); margin-left: 6px;">(<?= count($rows); ?> entri)</span>
        </div>

        <?php if (!empty($rows)): ?>
            <form method="post" action="sinkron.php" style="margin: 0;">
                <button class="btn btn-primary" type="submit" name="sinkronisasi">Sinkronisasi Sekarang</button>
            </form>
        <?php endif; ?>
    </div>

    <?php if (empty($rows)): ?>
        <div class="empty-state">
            <h3>Belum ada data kuesioner</h3>
            <p>Input data kuesioner terlebih dahulu sebelum melakukan sinkronisasi.</p>
            <a class="btn btn-primary" href="form.php">+ Isi Kuesioner</a>
        </div>
    <?php else: ?>
        <div class="table-responsive" style="max-height: 360px; overflow-y: auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th width="40">No</th>
                        <th>Waktu Input</th>
                        <th>Responden</th>
                        <th>Alamat</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $idx => $r):
                        $d   = $r['data'] ?? [];
                        $st  = ($r['status'] ?? 'draft') === 'valid' ? 'valid' : 'draft';
                        $ini = trim((string)($d['inisial'] ?? '')) ?: 'Tanpa Inisial';
                        $alm = trim((string)($d['alamat'] ?? '')) ?: '-';
                    ?>
                        <tr>
                            <td><?= $idx + 1; ?></td>
                            <td><?= esc(format_waktu($r['waktu'] ?? '')); ?></td>
                            <td><span class="resp-name"><?= esc($ini); ?></span></td>
                            <td><?= esc($alm); ?></td>
                            <td>
                                <span class="badge <?= $st === 'valid' ? 'badge-valid' : 'badge-draft'; ?>">
                                    <?= $st === 'valid' ? 'Valid' : 'Draft'; ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php render_layout_end(); ?>