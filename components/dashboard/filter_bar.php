<?php
/**
 * components/dashboard/filter_bar.php
 * Toolbar pencarian & filter data spesifik operasional Puskesmas.
 */
$kelurahanList = ['Kebon Jeruk', 'Duri Kepa', 'Kedoya Selatan', 'Kedoya Utara', 'Sukabumi Selatan', 'Sukabumi Utara', 'Kelapa Dua'];
$selectedKel   = $_GET['kelurahan'] ?? '';
$selectedRisk  = $_GET['risiko'] ?? '';
?>

<div class="toolbar" style="flex-direction: column; align-items: stretch; gap: 10px; padding: 16px;">
    <!-- Baris 1: Pencarian utama & Tab Status -->
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
        <form class="search-form" method="get" action="dashboard.php" style="display: flex; gap: 8px;">
            <?php if ($status !== ''): ?>
                <input type="hidden" name="status" value="<?= esc($status); ?>">
            <?php endif; ?>
            <?php if ($selectedKel !== ''): ?>
                <input type="hidden" name="kelurahan" value="<?= esc($selectedKel); ?>">
            <?php endif; ?>
            <?php if ($selectedRisk !== ''): ?>
                <input type="hidden" name="risiko" value="<?= esc($selectedRisk); ?>">
            <?php endif; ?>
            
            <input type="search" name="search" placeholder="Cari inisial, alamat, pekerjaan..." value="<?= esc($search); ?>" style="padding: 6px 12px; border: 1px solid var(--border); border-radius: 6px; font-size: 13.5px;">
            <button class="btn btn-outline" type="submit">Cari</button>
        </form>

        <div class="filter-tabs" style="display: flex; gap: 4px;">
            <a class="btn-xs <?= $status === '' ? 'btn-primary' : 'btn-outline'; ?>" href="<?= esc(build_url('dashboard.php', ['status' => '', 'search' => $search, 'kelurahan' => $selectedKel, 'risiko' => $selectedRisk])); ?>">Semua</a>
            <a class="btn-xs <?= $status === 'valid' ? 'btn-primary' : 'btn-outline'; ?>" href="<?= esc(build_url('dashboard.php', ['status' => 'valid', 'search' => $search, 'kelurahan' => $selectedKel, 'risiko' => $selectedRisk])); ?>">Valid</a>
            <a class="btn-xs <?= $status === 'draft' ? 'btn-primary' : 'btn-outline'; ?>" href="<?= esc(build_url('dashboard.php', ['status' => 'draft', 'search' => $search, 'kelurahan' => $selectedKel, 'risiko' => $selectedRisk])); ?>">Draft</a>
        </div>
    </div>

    <!-- Baris 2: Filter Tambahan (Kelurahan & Kondisi Kesehatan) -->
    <form method="get" action="dashboard.php" id="filterForm" style="display: flex; gap: 10px; flex-wrap: wrap; align-items: center; padding-top: 10px; border-top: 1px dashed var(--border-light);">
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

        <!-- Filter Kondisi / Risiko Kesehatan (PERBAIKAN TYPO PADA VALUE) -->
        <select name="risiko" class="form-control" style="width: auto; padding: 6px 10px; font-size: 13px;" onchange="document.getElementById('filterForm'].submit()">
            <option value="">— Kondisi / Riwayat Penyakit —</option>
            <option value="hipertensi_Ya" <?= $selectedRisk === 'hipertensi_Ya' ? 'selected' : ''; ?>>Riwayat Hipertensi (Ya)</option>
            <option value="diabetes_Ya" <?= $selectedRisk === 'diabetes_Ya' ? 'selected' : ''; ?>>Riwayat Diabetes (Ya)</option>
            <option value="hamil_Ya" <?= $selectedRisk === 'hamil_Ya' ? 'selected' : ''; ?>>Pernah / Sedang Hamil (Ya)</option>
            <option value="merokok_Ya" <?= $selectedRisk === 'merokok_Ya' ? 'selected' : ''; ?>>Kebiasaan Merokok (Ya)</option>
        </select>

        <?php if ($search !== '' || $status !== '' || $selectedKel !== '' || $selectedRisk !== ''): ?>
            <a href="dashboard.php" class="btn-xs" style="padding: 6px 12px; color: #dc2626; border-color: #fca5a5; text-decoration: none; border-radius: 6px; border: 1px solid #fca5a5;">Reset Filter</a>
        <?php endif; ?>
    </form>
</div>

<?php if ($search !== '' || $status !== '' || $selectedKel !== '' || $selectedRisk !== ''): ?>
    <div class="filter-status" style="padding: 8px 16px; font-size: 13px; background: var(--bg-subtle); border-top: 1px solid var(--border-light);">
        Menampilkan data terfilter 
        <?= $selectedKel !== '' ? ' · Kelurahan: <strong>' . esc($selectedKel) . '</strong>' : ''; ?>
        <?= $selectedRisk !== '' ? ' · Kondisi: <strong>' . esc(str_replace('_', ' ', ucfirst($selectedRisk))) . '</strong>' : ''; ?>
        <a href="dashboard.php" style="margin-left: 8px; color: var(--primary);">Reset Semua</a>
    </div>
<?php endif; ?>