<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/view.php';

if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: dashboard.php');
    exit;
}

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $u = trim($_POST['user'] ?? '');
    $p = $_POST['password'] ?? '';
    if ($u === ADMIN_USER && $p === ADMIN_PASS) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_user'] = $u;
        log_aksi("Login admin dari " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
        header('Location: dashboard.php');
        exit;
    }
    $msg = '<div class="alert alert-error">Login gagal. Cek user &amp; password.</div>';
}

layout_head('Login Dashboard — ' . APP_NAME);
?>
<div class="wrap" style="max-width:420px;margin:60px auto;">
    <div class="card" style="padding:24px;">
        <div style="text-align:center;margin-bottom:16px;">
            <div style="font-size:28px;font-weight:800;color:var(--primary);"><?php echo APP_LABEL; ?></div>
            <div style="color:var(--muted);font-size:13px;margin-top:4px;"><?php echo APP_SUB; ?></div>
        </div>
        <p style="font-size:13px;color:var(--muted);margin-top:0;">Login untuk mengelola kuesioner dan melihat hasil.</p>
        <?php echo $msg; ?>
        <form method="post" action="" style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
            <div class="form-group" style="grid-column:1/-1;">
                <label for="user">User / Nama Login</label>
                <input class="form-control" id="user" name="user" placeholder="contoh: NRK" required autofocus>
            </div>
            <div class="form-group" style="grid-column:1/-1;">
                <label for="password">Password</label>
                <input class="form-control" id="password" name="password" type="password" placeholder="••••••••" required>
            </div>
            <div class="form-group" style="grid-column:1/-1;display:flex;gap:10px;align-items:center;">
                <input type="checkbox" id="remember" name="remember" value="1">
                <label for="remember" style="margin:0;font-size:13px;color:#475569;">Ingat saya</label>
            </div>
            <div class="form-group" style="grid-column:1/-1;margin-top:6px;">
                <button class="btn btn-primary" style="width:100%;">Masuk Dashboard</button>
            </div>
        </form>
        <p style="font-size:12px;color:var(--muted);margin-top:12px;text-align:center;">
            Default:<br>
            <code style="background:#eef2f0;padding:2px 6px;border-radius:5px;">NRK</code><br>
            <code style="background:#eef2f0;padding:2px 6px;border-radius:5px;">Adm#Sikda2026!rm</code>
        </p>
    </div>
</div>
<?php
layout_tail();
