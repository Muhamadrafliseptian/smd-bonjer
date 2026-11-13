<?php
/**
 * components/dashboard/table.php
 * Komponen tabel data kuesioner menggunakan DataTables.
 */
$allRows = db_all(); // Mengambil seluruh data tanpa batasan pagination manual PHP
?>

<?php if (empty($allRows)): ?>
    <div class="empty-state">
        <h3>Belum ada data kuesioner</h3>
        <p>Silakan mulai menginput data kuesioner pertama melalui menu di samping.</p>
    </div>
<?php else: ?>
    <div class="table-responsive" style="padding: 16px;">
        <table id="dataTable" class="data-table" style="width:100%">
            <thead>
                <tr>
                    <th width="40">No</th>
                    <th>Responden & Wilayah</th>
                    <th>Usia / JK</th>
                    <th>Waktu Input</th>
                    <th style="text-align:right;" data-orderable="false">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($allRows as $idx => $r):
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
                <td><?= esc(format_waktu($r['waktu'] ?? '')); ?></td>
                    <td>
                        <div class="action-group">
                            <a class="btn-xs" href="detail.php?id=<?= urlencode($id); ?>">Detail</a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>