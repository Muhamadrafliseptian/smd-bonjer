<!-- <div class="summary-grid">
    <a class="summary-item <?= $status === '' ? 'active' : ''; ?>" href="<?= esc(build_url('dashboard.php', ['status' => '', 'search' => $search])); ?>">
        <div class="summary-label">Total Pengisian</div>
        <div class="summary-value"><?= $counts['total']; ?></div>
    </a>
    <a class="summary-item <?= $status === 'valid' ? 'active' : ''; ?>" href="<?= esc(build_url('dashboard.php', ['status' => 'valid', 'search' => $search])); ?>">
        <div class="summary-label">Tervalidasi</div>
        <div class="summary-value"><?= $counts['valid']; ?></div>
    </a>
    <a class="summary-item <?= $status === 'draft' ? 'active' : ''; ?>" href="<?= esc(build_url('dashboard.php', ['status' => 'draft', 'search' => $search])); ?>">
        <div class="summary-label">Perlu Dicek (Draft)</div>
        <div class="summary-value"><?= $counts['draft']; ?></div>
    </a>
    <div class="summary-item">
        <div class="summary-label">Input Hari Ini</div>
        <div class="summary-value"><?= $counts['today']; ?></div>
    </div>
</div> -->