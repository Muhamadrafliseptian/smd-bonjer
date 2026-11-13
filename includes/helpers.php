<?php
function esc($s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

function build_url(string $base, array $overrides = []): string
{
    $params = array_merge([
        'search' => $_GET['search'] ?? '',
        'status' => $_GET['status'] ?? '',
        'page'   => 1,
    ], $overrides);

    $params = array_filter($params, static fn($v) => $v !== '' && $v !== null && $v !== 1);
    return $base . ($params ? '?' . http_build_query($params) : '');
}

function format_waktu($raw): string
{
    $ts = strtotime((string)$raw);
    if (!$ts) return '-';
    $bulan = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    return date('d', $ts) . ' ' . $bulan[(int)date('n', $ts)] . ' ' . date('Y, H:i', $ts);
}