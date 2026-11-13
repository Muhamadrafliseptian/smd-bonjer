<div class="toolbar">
    <form class="search-form" method="get" action="dashboard.php">
        <?php if ($status !== ''): ?>
            <input type="hidden" name="status" value="<?= esc($status); ?>">
        <?php endif; ?>
        <input type="search" name="search" placeholder="Cari inisial, alamat, kelurahan..." value="<?= esc($search); ?>">
        <button class="btn btn-outline" type="submit">Cari</button>
    </form>
<!-- 
    <div class="filter-tabs">
        <a class="filter-tab <?= $status === '' ? 'active' : ''; ?>" href="<?= esc(build_url('dashboard.php', ['status' => '', 'search' => $search])); ?>">Semua</a>
        <a class="filter-tab <?= $status === 'valid' ? 'active' : ''; ?>" href="<?= esc(build_url('dashboard.php', ['status' => 'valid', 'search' => $search])); ?>">Valid</a>
        <a class="filter-tab <?= $status === 'draft' ? 'active' : ''; ?>" href="<?= esc(build_url('dashboard.php', ['status' => 'draft', 'search' => $search])); ?>">Draft</a>
    </div> -->
</div>

<?php if ($search !== '' || $status !== ''): ?>
    <div class="filter-status">
        Menampilkan <strong><?= $totalItems; ?></strong> dari <?= $counts['total']; ?> data
        <?= $search !== '' ? ' · Pencarian: "' . esc($search) . '"' : ''; ?>
        <?= $status !== '' ? ' · Status: ' . esc(ucfirst($status)) : ''; ?>
        <a href="dashboard.php">Reset</a>
    </div>
<?php endif; ?>