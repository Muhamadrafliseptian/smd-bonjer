<?php
/**
 * config.php — konfigurasi umum aplikasi Kuisioner SMD Puskemas Kebon Jeruk 2026
 * PHP native + flat-file JSON (tidak butuh database server).
 *
 * Akses:
 *   - form.php  (public, tanpa login)
 *   - save.php  (public, tanpa login)
 *   - dashboard.php (admin login saja)
 */

// ---------------- Nama aplikasi ----------------
define('APP_NAME', 'Kuesioner Survei Berbasis SMD');
define('APP_SUB', 'Puskesmas Kebon Jeruk — Tahun 2026');
define('APP_LABEL', 'SMD');

// ---------------- Folder penyimpanan data ----------------
define('DATA_DIR', __DIR__ . '/data');
define('DATA_FILE', __DIR__ . '/data/kuesioner.json');

// ---------------- Akun admin (untuk dashboard/view data) ----------------
define('ADMIN_USER', 'adminsmd');
define('ADMIN_PASS', 'smdB0nj4r!');

// ---------------- Opsi hubungkan ke spreadsheet ----------------
// Default aman: false → hanya simpan di JSON (lebih pribadi).
// Set true + isi SHEET_API_URL supaya otomatis kirim ke Google Sheets.
define('USE_SPREADSHEET', true);
define('SHEET_API_URL', '');
define('SHEET_CREDS', __DIR__ . '/data/service-account.json');

// ---------------- Unique keys untuk hidden-field (hide & seek) ----------------
// Formats:
//   exp_<key>   → explanation/textarea yang tampil hanya saat <key> diisi / dijawab "Ya"
//   show_<key>  → elemen div yang tampil saat <key> memiliki nilai (radio Y / checklist Y)
//   hide_<key>  → elemen div yang disembunyikan mula-mula, muncul kalau <key> = "Ya"
define('STATE_PREFIX', 'state_');
