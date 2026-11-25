<?php
/**
 * captcha.php — Membuat gambar kode pengaman (PNG) untuk form.php.
 * Kode disimpan di sesi ($_SESSION['smd_captcha']) dan dicek ulang di server saat form dikirim.
 * Hanya butuh ekstensi PHP GD (tanpa file font).
 */
session_start();

header('Content-Type: image/png');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');

if (!function_exists('imagecreatetruecolor')) {
    http_response_code(500);
    exit;
}

// Tanpa huruf/angka yang mirip: 0 O 1 I L
$chars = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';
$len = 5;
$code = '';
for ($i = 0; $i < $len; $i++) {
    $code .= $chars[random_int(0, strlen($chars) - 1)];
}
$_SESSION['smd_captcha'] = ['code' => $code, 'time' => time()];
session_write_close();

$W = 230;
$H = 76;
$img = imagecreatetruecolor($W, $H);
$bg = imagecolorallocate($img, 244, 246, 249);
imagefill($img, 0, 0, $bg);

// Huruf: digambar kecil, diputar acak, lalu diperbesar agar bentuknya tidak seragam
$cell = 40;
$startX = (int)(($W - $cell * $len) / 2) + 2;
for ($i = 0; $i < $len; $i++) {
    $tmp = imagecreatetruecolor(16, 22);
    $tbg = imagecolorallocate($tmp, 244, 246, 249);
    imagefill($tmp, 0, 0, $tbg);
    $ink = imagecolorallocate($tmp, random_int(10, 60), random_int(20, 70), random_int(60, 130));
    imagestring($tmp, 5, 3, 3, $code[$i], $ink);
    imagestring($tmp, 5, 4, 3, $code[$i], $ink); // tebalkan huruf
    $rot = imagerotate($tmp, random_int(-18, 18), $tbg);
    $dw = random_int(34, 42);
    $dh = random_int(48, 58);
    imagecopyresampled(
        $img, $rot,
        $startX + $i * $cell + random_int(-2, 3), random_int(4, $H - $dh - 4),
        0, 0, $dw, $dh, imagesx($rot), imagesy($rot)
    );
    imagedestroy($tmp);
    imagedestroy($rot);
}

// Gangguan di atas huruf: garis dan titik
for ($i = 0; $i < 3; $i++) {
    $c = imagecolorallocate($img, random_int(150, 200), random_int(150, 200), random_int(150, 200));
    imagesetthickness($img, 1);
    imageline($img, random_int(0, $W), random_int(0, $H), random_int(0, $W), random_int(0, $H), $c);
}
imagesetthickness($img, 1);
for ($i = 0; $i < 160; $i++) {
    $c = imagecolorallocate($img, random_int(90, 200), random_int(90, 200), random_int(90, 200));
    imagesetpixel($img, random_int(0, $W - 1), random_int(0, $H - 1), $c);
}

imagepng($img);
imagedestroy($img);