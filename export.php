<?php
/**
 * export.php — Unduh 1 hasil kuesioner jadi file siap spreadsheet.
 * Format dipilih lewat ?format=csv|xls|ods.
 */

session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/view.php';
require_once __DIR__ . '/database.php';

// Boleh akses publik (admin biasanya lewat dashboard)
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$id = $_GET['id'] ?? '';
$record = db_find($id);
if ($record === null) {
    header('Location: dashboard.php?msg=Gagal: data tidak ditemukan');
    exit;
}
$d = $record['data'] ?? [];
$status = $record['status'] ?? 'draft';
$format = $_GET['format'] ?? 'csv';

function clean_header(string $k): string
{
    $s = strtolower($k);
    $s = preg_replace('/[^a-z0-9]+/', '_', $s);
    return trim($s, '_');
}

$headers = [];
$rows = [];
foreach ($d as $k => $v) {
    $h = clean_header($k);
    $headers[] = $h;
    if (is_array($v)) {
        $rows[$h] = '"' . implode('; ', array_map(fn($x) => htmlspecialchars((string)$x, ENT_QUOTES, 'UTF-8'), $v)) . '"';
    } else {
        $rows[$h] = htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
    }
}

$full = array_fill_keys($headers, '');
foreach ($rows as $k => $v) {
    $full[$k] = $v;
}
$records = [$full];

$title = 'Kuesioner SMD Puskemas Kebon Jeruk 2026';
$sub   = 'ID: ' . $record['id'] . ' | Status: ' . $status . ' | Dikirim: ' . $record['waktu'];
$filename = 'kuesioner_' . $record['id'] . '_' . $status . '.csv';

if ($format === 'ods') {
    // XML ODS ringan. Buka di LibreOffice / OpenOffice.
    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $xml .= '<office:document xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0" xmlns:table="urn:oasis:names:tc:opendocument:xmlns:table:1.0" xmlns:text="urn:oasis:names:tc:opendocument:xmlns:text:1.0" office:version="1.2" office:mimetype="application/vnd.oasis.opendocument.spreadsheet">' . "\n";
    $xml .= '<office:body><office:spreadsheet><table:table table:name="Sheet1">' . "\n";
    $xml .= '<table:table-row>';
    foreach ($headers as $h) {
        $xml .= '<table:table-cell><text:p>' . htmlspecialchars($h, ENT_QUOTES, 'UTF-8') . '</text:p></table:table-cell>';
    }
    $xml .= '</table:table-row>';
    $row = $full;
    $xml .= '<table:table-row>';
    foreach ($row as $v) {
        $xml .= '<table:table-cell><text:p>' . htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8') . '</text:p></table:table-cell>';
    }
    $xml .= '</table:table-row>';
    $xml .= '</table:table></office:spreadsheet></office:body></office:document>';
    header('Content-Type: application/vnd.oasis.opendocument.spreadsheet');
    header('Content-Disposition: attachment; filename="' . $record['id'] . '.ods"');
    echo $xml;
    exit;
}

// Default / xls: CSV (ringan, kompatibel Excel / Sheets)
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
$fh = fopen('php://output', 'w');
fputs($fh, "\xEF\xBB\xBF");
fputcsv($fh, [$title, $sub, 'Puskesmas Kebon Jeruk 2026']);
fputcsv($fh, $headers);
foreach ($records as $r) {
    fputcsv($fh, $r);
}
fclose($fh);
exit;
