<?php
/**
 * components/dashboard/table.php
 * Komponen tabel data kuesioner menggunakan DataTables.
 * Menerima variabel $rows dari dashboard.php yang sudah terfilter.
 */
$rows = $rows ?? [];
?>

<?php if (empty($rows)): ?>
    <div class="empty-state">
        <h3>Data tidak ditemukan</h3>
        <p>Tidak ada entri kuesioner yang sesuai dengan filter atau kata kunci pencarian Anda.</p>
        <a class="btn btn-outline" href="dashboard.php" style="margin-top: 10px;">Reset Filter</a>
    </div>
<?php else: ?>
    <div class="table-responsive" style="padding: 16px;">
        <table id="dataTable" class="data-table" style="width:100%">
            <thead>
                <tr>
                    <th width="40">No</th>
                    <th>Responden & Wilayah</th>
                    <th>Usia / JK</th>
                    <th>Status</th>
                    <th>Waktu Input</th>
                    <th style="text-align:right;" data-orderable="false">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $idx => $r):
                    $d       = $r['data'] ?? [];
                    $id      = (string)($r['id'] ?? '');
                    $st      = ($r['status'] ?? 'draft') === 'valid' ? 'valid' : 'draft';
                    $ini     = trim((string)($d['inisial'] ?? '')) ?: 'Tanpa Inisial';
                    $rt      = trim((string)($d['rt'] ?? ''));
                    $rw      = trim((string)($d['rw'] ?? ''));
                    $wilayah = implode(' · ', array_filter([
                        trim((string)($d['alamat'] ?? '')),
                        ($rt !== '' || $rw !== '') ? "RT " . ($rt ?: '-') . "/RW " . ($rw ?: '-') : '',
                        trim((string)($d['kelurahan'] ?? '')),
                    ])) ?: 'Alamat belum diisi';

                    $usia = ($d['usia'] ?? '') !== '' ? $d['usia'] . ' th' : '';
                    $demo = implode(' / ', array_filter([$usia, (string)($d['jenis_kelamin'] ?? '')])) ?: '-';
                ?>
                <tr>
                    <td><?= $idx + 1; ?></td>
                    <td>
                        <span class="resp-name"><?= esc($ini); ?></span>
                        <span class="resp-addr"><?= esc($wilayah); ?></span>
                    </td>
                    <td><?= esc($demo); ?></td>
                    <td>
                        <span class="badge <?= $st === 'valid' ? 'badge-valid' : 'badge-draft'; ?>">
                            <?= $st === 'valid' ? 'Valid' : 'Draft'; ?>
                        </span>
                    </td>
                    <td><?= esc(format_waktu($r['waktu'] ?? '')); ?></td>
                    <td>
                        <div class="action-group">
                            <a class="btn-xs" href="detail.php?id=<?= urlencode($id); ?>">Detail</a>
                            <a class="btn-xs" href="form.php?edit_id=<?= urlencode($id); ?>">Edit</a>
                            <?php if ($st === 'draft'): ?>
                                <form method="post" action="dashboard.php" style="display:inline;">
                                    <input type="hidden" name="action" value="valid">
                                    <input type="hidden" name="id" value="<?= esc($id); ?>">
                                    <button class="btn-xs approve" type="submit">Validasi</button>
                                </form>
                            <?php endif; ?>
                            <a class="btn-xs" href="export.php?id=<?= urlencode($id); ?>">Export</a>
                            <a class="btn-xs danger" href="proses.php?action=hapus&id=<?= urlencode($id); ?>" onclick="return confirm('Hapus data kuesioner ini?');">Hapus</a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>