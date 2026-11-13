<?php
/**
 * components/dashboard/filter_bar.php
 * Toolbar pencarian & filter data spesifik operasional Puskesmas.
 */
$kelurahanList = ['Kebon Jeruk', 'Duri Kepa', 'Kedoya Selatan', 'Kedoya Utara', 'Sukabumi Selatan', 'Sukabumi Utara', 'Kelapa Dua'];
$selectedKel   = $_GET['kelurahan'] ?? '';
$selectedRisk  = $_GET['risiko'] ?? '';
?>

<div class="toolbar" style="flex-direction: column; align-items: stretch; gap: 10px;">
    <!-- Baris 1: Pencarian utama & Tab Status -->
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
        <form class="search-form" method="get" action="dashboard.php">
            <?php if ($status !== ''): ?>
                <input type="hidden" name="status" value="<?= esc($status); ?>">
            <?php endif; ?>
            <?php if ($selectedKel !== ''): ?>
                <input type="hidden" name="kelurahan" value="<?= esc($selectedKel); ?>">
            <?php endif; ?>
            <?php if ($selectedRisk !== ''): ?>
                <input type="hidden" name="risiko" value="<?= esc($selectedRisk); ?>">
            <?php endif; ?>
            
            <input type="search" name="search" placeholder="Cari inisial, alamat, pekerjaan..." value="<?= esc($search); ?>">
            <button class="btn btn-outline" type="submit">Cari</button>
        </form>

        <div class="filter-tabs">
            <a class="filter-tab <?= $status === '' ? 'active' : ''; ?>" href="<?= esc(build_url('dashboard.php', ['status' => '', 'search' => $search, 'kelurahan' => $selectedKel, 'risiko' => $selectedRisk])); ?>">Semua</a>
            <a class="filter-tab <?= $status === 'valid' ? 'active' : ''; ?>" href="<?= esc(build_url('dashboard.php', ['status' => 'valid', 'search' => $search, 'kelurahan' => $selectedKel, 'risiko' => $selectedRisk])); ?>">Valid</a>
            <a class="filter-tab <?= $status === 'draft' ? 'active' : ''; ?>" href="<?= esc(build_url('dashboard.php', ['status' => 'draft', 'search' => $search, 'kelurahan' => $selectedKel, 'risiko' => $selectedRisk])); ?>">Draft</a>
        </div>
    </div>

    <!-- Baris 2: Filter Tambahan yang Penting (Kelurahan & Kondisi Kesehatan) -->
    <form method="get" action="dashboard.php" id="filterForm" style="display: flex; gap: 10px; flex-wrap: wrap; align-items: center; padding-top: 8px; border-top: 1px dashed var(--border-light);">
        <?php if ($search !== ''): ?>
            <input type="hidden" name="search" value="<?= esc($search); ?>">
        <?php endif; ?>
        <?php if ($status !== ''): ?>
            <input type="hidden" name="status" value="<?= esc($status); ?>">
        <?php endif; ?>

        <div style="font-size: 13px; font-weight: 600; color: var(--text-muted);">Filter Cepat:</div>

        <!-- Filter Kelurahan -->
        <select name="kelurahan" class="form-control" style="width: auto; padding: 6px 10px; font-size: 13px;" onchange="document.getElementById('filterForm').submit()">
            <option value="">— Semua Kelurahan —</option>
            <?php foreach ($kelurahanList as $kel): ?>
                <option value="<?= esc($kel); ?>" <?= $selectedKel === $kel ? 'selected' : ''; ?>><?= esc($kel); ?></option>
            <?php endforeach; ?>
        </select>

        <!-- Filter Kondisi / Risiko Kesehatan -->
        <select name="risiko" class="form-control" style="width: auto; padding: 6px 10px; font-size: 13px;" onchange="document.getElementById('filterForm').submit()">
            <option value="">— Kondisi / Riwayat Penyakit —</option>
            <option value="hipertensi_ ya" <?= $selectedRisk === 'hipertensi_ya' ? 'selected' : ''; ?>>Riwayat Hipertensi (Ya)</option>
            <option value="diabetes_ya" <?= $selectedRisk === 'diabetes_ya' ? 'selected' : ''; ?>>Riwayat Diabetes (Ya)</option>
            <option value="hamil_ya" <?= $selectedRisk === 'hamil_ya' ? 'selected' : ''; ?>>Sedang / Pernah Hamil</option>
            <option value="merokok_ya" <?= $selectedRisk === 'merokok_ya' ? 'selected' : ''; ?>>Kebiasaan Merokok (Ya)</option>
        </select>

        <?php if ($search !== '' || $status !== '' || $selectedKel !== '' || $selectedRisk !== ''): ?>
            <a href="dashboard.php" class="btn-xs" style="padding: 6px 12px; color: #dc2626; border-color: #fca5a5;">Reset Filter</a>
        <?php endif; ?>
    </form>
</div>

<?php if ($search !== '' || $status !== '' || $selectedKel !== '' || $selectedRisk !== ''): ?>
    <div class="filter-status">
        Menampilkan data terfilter 
        <?= $selectedKel !== '' ? ' · Kelurahan: <strong>' . esc($selectedKel) . '</strong>' : ''; ?>
        <?= $selectedRisk !== '' ? ' · Kondisi: <strong>' . esc(str_replace('_', ' ', ucfirst($selectedRisk))) . '</strong>' : ''; ?>
        <a href="dashboard.php">Reset Semua</a>
    </div>
<?php endif; ?>