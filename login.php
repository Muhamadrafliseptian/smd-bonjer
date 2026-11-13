<?php
/**
 * login.php — Halaman Login Admin dengan Gambar CAPTCHA & UI/UX Profesional.
 */
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/includes/helpers.php';

if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: dashboard.php');
    exit;
}

$errorMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user           = trim($_POST['user'] ?? '');
    $password       = $_POST['password'] ?? '';
    $inputCaptcha   = strtoupper(trim($_POST['captcha'] ?? ''));
    $sessionCaptcha = $_SESSION['captcha_code'] ?? '';

    // Validasi CAPTCHA terlebih dahulu
    if (empty($inputCaptcha) || $inputCaptcha !== $sessionCaptcha) {
        $errorMsg = 'Kode verifikasi (CAPTCHA) salah atau kedaluwarsa.';
    } elseif ($user === ADMIN_USER && $password === ADMIN_PASS) {
        // Regenerasi session ID untuk keamanan
        session_regenerate_id(true);
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_user'] = $user;
        $_SESSION['admin_name'] = 'Petugas Kebon Jeruk';

        header('Location: dashboard.php');
        exit;
    } else {
        $errorMsg = 'Nama pengguna atau kata sandi tidak valid.';
    }

    // Hapus kode captcha lama setelah digunakan
    unset($_SESSION['captcha_code']);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk Sistem — <?= esc(APP_NAME); ?></title>
    <link rel="stylesheet" href="assets/css/admin.css">
    <style>
        body {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
        }
        .login-wrapper {
            width: 100%;
            max-width: 400px;
            padding: 20px;
        }
        .login-card {
            background: #ffffff;
            border: 1px solid var(--border-light);
            border-radius: 12px;
            box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.08), 0 8px 10px -6px rgba(15, 23, 42, 0.08);
            padding: 32px;
        }
        .login-header {
            text-align: center;
            margin-bottom: 24px;
        }
        .login-logo-mark {
            width: 48px;
            height: 48px;
            background: var(--primary);
            color: #fff;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 12px;
            box-shadow: 0 4px 12px rgba(15, 118, 110, 0.25);
        }
        .login-header h1 {
            margin: 0 0 4px;
            font-size: 18px;
            font-weight: 700;
            color: #0f172a;
        }
        .login-header p {
            margin: 0;
            font-size: 13px;
            color: var(--text-muted);
        }
        .form-group {
            margin-bottom: 16px;
        }
        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #334155;
            margin-bottom: 6px;
        }
        .form-control {
            width: 100%;
            padding: 9px 12px;
            border: 1px solid var(--border);
            border-radius: 6px;
            font-size: 14px;
            color: var(--text-main);
            background: #fff;
            transition: border-color .15s, box-shadow .15s;
        }
        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(15, 118, 110, 0.15);
        }
        .captcha-container {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 8px;
        }
        .captcha-img-box {
            border: 1px solid var(--border);
            border-radius: 6px;
            overflow: hidden;
            background: #f1f5f9;
            flex: none;
        }
        .captcha-img-box img {
            display: block;
            height: 42px;
        }
        .btn-refresh {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 6px;
            padding: 0 10px;
            height: 42px;
            font-size: 13px;
            font-weight: 600;
            color: var(--text-muted);
            cursor: pointer;
            transition: all .15s;
        }
        .btn-refresh:hover {
            background: var(--bg-subtle);
            color: #0f172a;
            border-color: #94a3b8;
        }
        .btn-login {
            width: 100%;
            padding: 10px;
            background: var(--primary);
            color: #fff;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: background .15s;
            margin-top: 6px;
        }
        .btn-login:hover {
            background: var(--primary-hover);
        }
        .login-footer {
            margin-top: 24px;
            text-align: center;
            font-size: 12px;
            color: var(--text-muted);
            border-top: 1px solid var(--border-light);
            padding-top: 16px;
        }
    </style>
</head>
<body>

<div class="login-wrapper">
    <div class="login-card">
        <div class="login-header">
            <div class="login-logo-mark">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 6v12M6 12h12"/></svg>
            </div>
            <h1><?= esc(APP_LABEL); ?></h1>
            <p><?= esc(APP_SUB); ?></p>
        </div>

        <?php if ($errorMsg !== ''): ?>
            <div class="notice notice-error" style="margin-bottom: 16px;"><?= esc($errorMsg); ?></div>
        <?php endif; ?>

        <form method="post" action="">
            <div class="form-group">
                <label for="user">Nama Pengguna (User)</label>
                <input class="form-control" id="user" name="user" placeholder="Masukkan username admin" required autofocus autocomplete="username">
            </div>

            <div class="form-group">
                <label for="password">Kata Sandi (Password)</label>
                <input class="form-control" id="password" name="password" type="password" placeholder="••••••••" required autocomplete="current-password">
            </div>

            <div class="form-group">
                <label for="captcha">Kode Verifikasi</label>
                <div class="captcha-container">
                    <div class="captcha-img-box">
                        <img src="captcha.php?t=<?= time(); ?>" alt="CAPTCHA" id="captchaImage">
                    </div>
                    <button type="button" class="btn-refresh" onclick="refreshCaptcha()" title="Muat ulang kode">🔄 Ganti</button>
                </div>
                <input class="form-control" id="captcha" name="captcha" placeholder="Ketik 5 karakter di atas" required autocomplete="off" style="text-transform: uppercase;">
            </div>

            <button type="submit" class="btn-login">Masuk ke Sistem</button>
        </form>

        <div class="login-footer">
            Puskesmas Kebon Jeruk &middot; <?= date('Y'); ?>
        </div>
    </div>
</div>

<script>
function refreshCaptcha() {
    const img = document.getElementById('captchaImage');
    img.src = 'captcha.php?t=' + new Date().getTime();
    document.getElementById('captcha').value = '';
    document.getElementById('captcha').focus();
}
</script>

</body>
</html>