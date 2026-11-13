<?php
/**
 * view.php — helper UI: head, footer, alert, dan small bits untuk aplikasi SMD.
 * Pakai di form.php (public), dashboard.php (admin), detail/export/sinkron.
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';

const ALERT_SUCCESS = 'alert alert-success';
const ALERT_ERROR   = 'alert alert-error';
const ALERT_WARNING = 'alert alert-warning';
const ALERT_INFO    = 'alert alert-info';

const ALERT_MAP = [
    'success' => ALERT_SUCCESS,
    'error'   => ALERT_ERROR,
    'err'     => ALERT_ERROR,
    'fail'    => ALERT_ERROR,
    'gagal'   => ALERT_ERROR,
    'warn'    => ALERT_WARNING,
    'warning' => ALERT_WARNING,
    'info'    => ALERT_INFO,
];

/**
 * Tampilkan flash message dari query (?msg=...).
 * ID halaman berbeda lalu data invalid, pesan tetap tampil sekali.
 */
function flash(string $tag, string $text): void
{
    if (empty($text)) {
        return;
    }
    $c = ALERT_MAP[$tag] ?? ALERT_SUCCESS;
    echo '<div class="' . $c . '">' . htmlspecialchars($text, ENT_QUOTES, 'UTF-8') . '</div>';
}

/**
 * Buat elemen <select> dengan list opsi (array label/value).
 * $selected = nilai yang dipilih, boleh null / array.
 */
function select(string $name, array $options, string $selected = null, string $empty = '-- Pilih --', string $labelShow = '%s'): string
{
    $html = '<select name="' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '" id="' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '">';
    if ($empty !== '') {
        $html .= '<option value="">' . htmlspecialchars($empty, ENT_QUOTES, 'UTF-8') . '</option>';
    }
    foreach ($options as $v) {
        if (is_scalar($v)) {
            $txt = $v;
            $val = $v;
        } else {
            $txt = $v['label'] ?? '';
            $val = $v['value'] ?? '';
        }
        $sel = (string)$val === (string)$selected ? ' selected' : '';
        $html .= '<option value="' . htmlspecialchars($val, ENT_QUOTES, 'UTF-8') . '"' . $sel . '>'
            . sprintf($labelShow, htmlspecialchars($txt, ENT_QUOTES, 'UTF-8')) . '</option>';
    }
    $html .= '</select>';
    return $html;
}

/**
 * Buat radio-group inline.
 */
function radio_group(string $name, array $options, string $selected = null, string $inlineLabel = '%s'): string
{
    $html = '';
    foreach ($options as $v) {
        if (is_scalar($v)) {
            $val = $v;
            $txt = $v;
        } else {
            $val = $v['value'] ?? $v['label'] ?? '';
            $txt = $v['label'] ?? $v['value'] ?? '';
        }
        $checked = (string)$val === (string)$selected ? ' checked' : '';
        $html .= '<label style="display:inline-flex;align-items:center;gap:7px;font-size:14px;color:#334155;">'
            . '<input type="radio" name="' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '" value="' . htmlspecialchars($val, ENT_QUOTES, 'UTF-8') . '"' . $checked . '> '
            . sprintf($inlineLabel, htmlspecialchars($txt, ENT_QUOTES, 'UTF-8')) . '</label>';
    }
    return $html;
}

/**
 * Buat checkbox-group inline (multiple).
 */
function checkbox_group(string $name, array $options, array $selected = []): string
{
    $html = '';
    foreach ($options as $v) {
        if (is_scalar($v)) {
            $val = $v;
            $txt = $v;
        } else {
            $val = $v['value'] ?? $v['label'] ?? '';
            $txt = $v['label'] ?? $v['value'] ?? '';
        }
        $checked = in_array((string)$val, $selected, true) ? ' checked' : '';
        $html .= '<label style="display:inline-flex;align-items:center;gap:7px;font-size:14px;">'
            . '<input type="checkbox" name="' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '[]" value="' . htmlspecialchars($val, ENT_QUOTES, 'UTF-8') . '"' . $checked . '> '
            . htmlspecialchars($txt, ENT_QUOTES, 'UTF-8') . '</label>';
    }
    return $html;
}

/**
 * Tandai elemen HTML yang tersembunyi (hide-and-seek) kalau kondisi true.
 * Bila $on tida true, elemen dibuat class="hidden" / style="display:none".
 * @param bool $show
 * @param string $inner HTML yang ingin disembunyikan/tampilkan
 */
function show_when(bool $show, string $inner): string
{
    $cls = $show ? '' : 'hidden';
    $style = $show ? '' : 'display:none';
    return '<div class="' . $cls . '" style="' . $style . '">' . $inner . '</div>';
}

/**
 * Output head + open body.
 */
function layout_head(string $title): void
{
    ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?> · <?php echo APP_NAME; ?></title>
    <style>
        :root{
            --primary:#0f766e; --primary-dark:#115e59; --primary-soft:#ccfbf1;
            --bg:#f1f5f4; --card:#ffffff; --line:#e2e8e6; --text:#0f172a;
            --muted:#64748b; --danger:#b91c1c; --danger-soft:#fdeaea;
            --warn:#b45309; --warn-soft:#fef3c7; --ok:#15803d; --ok-soft:#dcfce7;
            --info:#1d4ed8; --info-soft:#e0f2fe;
            --radius:12px; --shadow:0 1px 3px rgba(15,23,42,.07),0 4px 14px rgba(15,23,42,.05);
        }
        *{box-sizing:border-box}
        body{font-family:'Segoe UI',Roboto,Arial,sans-serif;background:var(--bg);color:var(--text);margin:0}
        a{color:inherit;text-decoration:none}
        .wrap{max-width:1100px;margin:0 auto;padding:0 16px}
        .topbar{display:flex;align-items:center;gap:12px;padding:14px 16px;background:var(--card);border:1px solid var(--line);border-radius:var(--radius);box-shadow:var(--shadow);margin-bottom:16px}
        .topbar .logo{font-weight:800;font-size:16px;color:var(--primary)}
        .topbar .sub{color:var(--muted);font-size:12px;flex:1;text-align:right}
        .topbar .spacer{flex:1}
        .tabrow{display:flex;gap:8px;overflow-x:auto;padding:6px 0;border-bottom:1px solid var(--line)}
        .tabrow a{display:inline-block;padding:8px 14px;border-radius:999px;font-size:13px;font-weight:600;background:var(--card);border:1px solid var(--line);color:var(--muted)}
        .tabrow a.active{background:var(--primary);border-color:var(--primary);color:#fff}
        .tabrow a:hover{background:#e0f2f1}
        .card{background:var(--card);border:1px solid var(--line);border-radius:var(--radius);box-shadow:var(--shadow);padding:20px;margin-bottom:16px}
        .card h1,.card h2,.card h3{font-weight:800;color:var(--primary-dark);margin:0 0 6px}
        .card h1{font-size:20px;margin-bottom:4px}
        .muted{color:var(--muted)}
        .row{display:grid;grid-template-columns:repeat(auto-fit,minmax(230px,1fr));gap:14px}
        .grid2{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:14px}
        .grid3{display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:14px}
        .form-group{margin-bottom:14px}
        .form-group label{display:block;font-size:13px;font-weight:700;color:#334155;margin-bottom:5px}
        .form-group .sub{color:var(--muted);font-size:11.5px;font-weight:400;margin-top:3px}
        .form-control{width:100%;padding:10px 12px;border:1px solid var(--line);border-radius:9px;font-size:14px;background:#fff;color:var(--text);transition:border-color .15s,box-shadow .15s}
        .form-control:focus{outline:none;border-color:var(--primary);box-shadow:0 0 0 3px rgba(15,118,110,.18)}
        textarea.form-control{resize:vertical;min-height:70px}
        .btn{display:inline-flex;align-items:center;gap:8px;padding:11px 16px;border-radius:9px;font-weight:700;font-size:14px;cursor:pointer;border:none;transition:background .15s}
        .btn:active{transform:translateY(1px)}
        .btn-primary{background:var(--primary);color:#fff}
        .btn-primary:hover{background:var(--primary-dark)}
        .btn-secondary{background:#e2e8e6;color:#334155}
        .btn-secondary:hover{background:#cbd5d0}
        .btn-danger{background:#fee2e2;color:#b91c1c}
        .btn-danger:hover{background:#fed7d7}
        .btn-sm{padding:7px 12px;font-size:12.5px;border-radius:7px}
        .btn-outline{background:transparent;border:1px solid var(--line);color:var(--muted)}
        .btn-outline:hover{background:#f1f5f4}
        .alert{padding:12px 14px;border-radius:9px;margin-bottom:14px;display:flex;gap:10px;font-size:14px}
        .alert-success{background:var(--ok-soft);color:#14532d;border:1px solid #bbf7d0}
        .alert-error{background:var(--danger-soft);color:#7f1d1d;border:1px solid #fecaca}
        .alert-warning{background:var(--warn-soft);color:#78350f;border:1px solid #fde68a}
        .alert-info{background:var(--info-soft);color:#1e3a8a;border:1px solid #bfdbfe}
        .stat{display:flex;flex-direction:column;align-items:center;justify-content:center;padding:14px;border-radius:10px;text-align:center}
        .stat .num{font-size:22px;font-weight:800;color:var(--primary)}
        .stat .lbl{font-size:12px;color:var(--muted)}
        .stat.suc .num{color:var(--ok)}
        .stat.danger .num{color:#b91c1c}
        .stat.warn .num{color:var(--warn)}
        .table-wrap{max-height:380px;overflow:auto;border:1px solid var(--line);border-radius:9px}
        table{width:100%;border-collapse:collapse;font-size:13px}
        th,td{padding:9px 10px;text-align:left;border-bottom:1px solid var(--line)}
        th{background:#f8faf9;color:#334155;font-weight:700}
        tr:last-child td{border-bottom:none}
        .tag{display:inline-block;padding:3px 9px;border-radius:999px;font-size:11.5px;font-weight:700}
        .tag-ok{background:var(--ok-soft);color:#14532d}
        .tag-warn{background:var(--warn-soft);color:#78350f}
        .tag-danger{background:var(--danger-soft);color:#7f1d1d}
        .tag-info{background:var(--info-soft);color:#1e3a8a}
        .pill{display:inline-block;padding:2px 8px;border-radius:999px;font-size:11.5px;font-weight:700;background:#e2e8e6;color:#334155}
        .btn-row{display:flex;gap:8px;flex-wrap:wrap;align-items:center}
        .hidden{display:none}
        .flex{display:flex;gap:10px;align-items:center;flex-wrap:wrap}
        .section-box{padding:16px;margin-bottom:14px;border:1px solid var(--line);border-radius:10px}
        .section-title{display:inline-block;background:var(--primary);color:#fff;padding:6px 12px;border-radius:7px;font-weight:800;font-size:14px}
        @media(max-width:720px){ .grid2,.grid3{grid-template-columns:1fr} .topbar .sub,.topbar .spacer{display:none} }
    </style>
</head>
<body>
<div class="wrap">
    <?php
}

/**
 * Footer + close body.
 */
function layout_tail(): void
{
    ?>
</div>
</body>
</html>
<?php
}

/**
 * Note kecil di halaman (info/tautan).
 */
function note(string $text, string $cls = 'muted'): void
{
    echo '<p class="' . $cls . '" style="font-size:13px;">' . $text . '</p>';
}

/**
 * Tulis label kecil (badge/tag).
 */
function badge(string $label, string $cls): string
{
    return '<span class="tag ' . $cls . '">' . $label . '</span>';
}

/**
 * Log aksi ke data/log_aksi.log (tracing ringan).
 */
function log_aksi(string $msg): void
{
    $line = date('Y-m-d H:i:s') . "\t" . $msg . "\n";
    @file_put_contents(__DIR__ . '/data/log_aksi.log', $line, FILE_APPEND | LOCK_EX);
}
