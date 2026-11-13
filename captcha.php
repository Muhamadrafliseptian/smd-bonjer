<?php
/**
 * captcha.php — Menghasilkan gambar verifikasi (CAPTCHA) dinamis.
 */
session_start();

// Buat string acak 5 karakter (huruf besar dan angka, hindari karakter mirip seperti 0, O, 1, I)
$characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
$captchaText = '';
for ($i = 0; $i < 5; $i++) {
    $captchaText .= $characters[random_int(0, strlen($characters) - 1)];
}

// Simpan teks asli ke session admin
$_SESSION['captcha_code'] = $captchaText;

// Buat gambar dengan GD Library
$image = imagecreatetruecolor(130, 42);

// Warna (Tema Teal & Abu-abu lembut)
$bgColor = imagecolorallocate($image, 241, 245, 249);    // #f1f5f9 (bg-body)
$textColor = imagecolorallocate($image, 15, 118, 110);   // #0f766e (primary)
$lineColor = imagecolorallocate($image, 203, 213, 225);   // #cbd5e1 (border)
$dotColor = imagecolorallocate($image, 148, 163, 184);    // #94a3b8

// Beri background
imagefilledrectangle($image, 0, 0, 130, 42, $bgColor);

// Tambahkan garis gangguan acak agar tidak mudah dibaca OCR bot
for ($i = 0; $i < 4; $i++) {
    imageline($image, random_int(0, 130), random_int(0, 42), random_int(0, 130), random_int(0, 42), $lineColor);
}

// Tambahkan titik-titik gangguan
for ($i = 0; $i < 40; $i++) {
    imagesetpixel($image, random_int(0, 130), random_int(0, 42), $dotColor);
}

// Tulis karakter CAPTCHA (menggunakan font bawaan GD)
$fontSize = 5;
$x = 22;
for ($i = 0; $i < strlen($captchaText); $i++) {
    $y = random_int(8, 14);
    imagestring($image, $fontSize, $x, $y, $captchaText[$i], $textColor);
    $x += 20;
}

// Render output sebagai gambar PNG
header('Content-Type: image/png');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
imagepng($image);
imagedestroy($image);