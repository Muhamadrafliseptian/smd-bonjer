<?php
function render_layout_start(string $title, string $activeMenu = 'dashboard'): void
{
    $menus = [
        'dashboard' => [
            'label' => 'Dashboard',
            'href'  => 'dashboard.php',
            'icon'  => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/></svg>'
        ],
        'sinkron' => [
            'label' => 'Sinkron ke Sheet',
            'href'  => 'sinkron.php',
            'icon'  => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg>'
        ],
    ];

    $adminName = $_SESSION['admin_name'] ?? 'Petugas Puskesmas';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title); ?></title>
    <link rel="stylesheet" href="assets/css/admin.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">
    <style>
        /* Penyesuaian ringan DataTables agar menyatu dengan tema admin.css */
        .dataTables_wrapper .dataTables_filter {
            margin-bottom: 14px;
            font-size: 13.5px;
            color: var(--text-muted);
        }
        .dataTables_wrapper .dataTables_filter input {
            padding: 6px 10px;
            border: 1px solid var(--border);
            border-radius: 6px;
            margin-left: 8px;
            font-size: 13.5px;
            color: var(--text-main);
            background: #fff;
        }
        .dataTables_wrapper .dataTables_filter input:focus {
            outline: none;
            border-color: var(--primary);
        }
        table.dataTable.no-footer {
            border-bottom: 1px solid var(--border-light);
        }
        table.dataTable thead th, table.dataTable thead td {
            border-bottom: 1px solid var(--border-light);
        }
        .dataTables_wrapper .dataTables_info {
            padding-top: 14px;
            font-size: 13px;
            color: var(--text-muted);
        }
        .dataTables_wrapper .dataTables_paginate {
            padding-top: 10px;
            font-size: 13px;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            padding: 4px 10px !important;
            border-radius: 5px !important;
            border: 1px solid var(--border) !important;
            background: #fff !important;
            color: var(--text-main) !important;
            margin-left: 4px !important;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button.current,
        .dataTables_wrapper .dataTables_paginate .paginate_button.current:hover {
            background: var(--primary) !important;
            border-color: var(--primary) !important;
            color: #fff !important;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button:hover:not(.disabled):not(.current) {
            background: var(--bg-subtle) !important;
            border-color: #94a3b8 !important;
            color: var(--text-main) !important;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button.disabled {
            opacity: .45;
            cursor: default;
        }
    </style>
</head>
<body>
<div class="app-shell">
    <div class="sidebar-backdrop" id="sidebarBackdrop" onclick="toggleSidebar(false)"></div>

    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="brand-mark">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 6v12M6 12h12"/></svg>
            </div>
            <div class="brand-info">
                <div class="brand-title"><?= APP_LABEL; ?></div>
                <div class="brand-sub"><?= APP_SUB; ?></div>
            </div>
        </div>

        <nav class="sidebar-nav">
            <div class="nav-label">Menu</div>
            <?php foreach ($menus as $key => $item): ?>
                <a href="<?= esc($item['href']); ?>" class="nav-item <?= $activeMenu === $key ? 'active' : ''; ?>">
                    <span class="nav-icon"><?= $item['icon']; ?></span>
                    <span><?= esc($item['label']); ?></span>
                </a>
            <?php endforeach; ?>
        </nav>

        <div class="sidebar-footer">
            <div class="user-box" style="margin-bottom: 8px;">
                <div class="user-meta">
                    <span class="user-name"><?= esc($adminName); ?></span>
                    <span class="user-role">Administrator</span>
                </div>
            </div>
            <a href="logout.php" class="nav-item" style="color: #dc2626;" onclick="return confirm('Keluar dari sistem?');">
                <span class="nav-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                </span>
                <span>Keluar</span>
            </a>
        </div>
    </aside>

    <div class="main-wrapper">
        <header class="mobile-bar">
            <button type="button" class="menu-toggle" onclick="toggleSidebar(true)" aria-label="Buka navigasi">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
            </button>
            <span class="mobile-title"><?= APP_LABEL; ?></span>
        </header>
        <main class="main-content">
<?php
}

function render_layout_end(): void
{
?>
        </main>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function () {
    const tableEl = document.getElementById('dataTable');
    if (tableEl) {
        $('#dataTable').DataTable({
            language: {
                search: "Cari:",
                lengthMenu: "Tampilkan _MENU_ data",
                info: "Menampilkan _START_–_END_ dari _TOTAL_ entri",
                infoEmpty: "Tidak ada data",
                infoFiltered: "(disaring dari _MAX_ total entri)",
                zeroRecords: "Tidak ada data yang cocok",
                paginate: {
                    first: "Awal",
                    last: "Akhir",
                    next: "Berikutnya",
                    previous: "Sebelumnya"
                }
            },
            pageLength: 20,
            lengthChange: false,
            autoWidth: false,
            columnDefs: [
                { orderable: false, targets: -1 }
            ]
        });
    }
});

function toggleSidebar(open) {
    document.getElementById('sidebar').classList.toggle('open', open);
    document.getElementById('sidebarBackdrop').classList.toggle('show', open);
}
</script>
</body>
</html>
<?php
}