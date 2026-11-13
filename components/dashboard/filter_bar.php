<?php if ($search !== '' || $status !== ''): ?>
    <div class="filter-status">
        Menampilkan <strong><?= $totalItems; ?></strong> dari <?= $counts['total']; ?> data
        <?= $search !== '' ? ' · Pencarian: "' . esc($search) . '"' : ''; ?>
        <?= $status !== '' ? ' · Status: ' . esc(ucfirst($status)) : ''; ?>
        <a href="dashboard.php">Reset</a>
    </div>
<?php endif; ?>