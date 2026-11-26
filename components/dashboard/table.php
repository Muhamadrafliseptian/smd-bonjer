<?php
/**
 * components/dashboard/table.php
 * Komponen tabel data kuesioner menggunakan DataTables.
 * Menerima variabel $rows dari dashboard.php yang sudah terfilter.
 *
 * Hapus data:
 *  - Hapus satu data  : tombol "Hapus" di tiap baris (POST ke proses.php).
 *  - Hapus banyak data: centang baris (atau "pilih semua"), lalu "Hapus yang dipilih".
 *  Keduanya memakai token CSRF dan konfirmasi sebelum dihapus.
 */
$rows = $rows ?? [];

if (empty($_SESSION['csrf_hapus'])) {
    $_SESSION['csrf_hapus'] = bin2hex(random_bytes(16));
}
$csrfHapus = (string)$_SESSION['csrf_hapus'];
$backQs    = (string)($_SERVER['QUERY_STRING'] ?? ''); // supaya filter tidak hilang setelah menghapus
?>

<?php if (empty($rows)): ?>
    <div class="empty-state">
        <h3>Data tidak ditemukan</h3>
        <p>Tidak ada entri kuesioner yang sesuai dengan filter atau kata kunci pencarian Anda.</p>
        <a class="btn btn-outline" href="dashboard.php" style="margin-top: 10px;">Reset Filter</a>
    </div>
<?php else: ?>
    <style>
    .bulk-bar{display:flex;flex-wrap:wrap;align-items:center;gap:10px;margin:16px 16px 0;padding:10px 14px;border:1px solid #f1c3b0;background:#fdf1ea;border-radius:8px;font-size:14px}
    .bulk-bar[hidden]{display:none}
    .bulk-bar .bulk-sp{flex:1}
    .data-table input.row-chk,.data-table #chkAll{width:18px;height:18px;cursor:pointer}
    .data-table tr.is-sel td{background:#eaf2fc}
    </style>

    <!-- Form kosong untuk hapus banyak. Kotak centang di tabel terhubung lewat atribut form="bulkForm",
         jadi tidak ada form di dalam form. -->
    <form id="bulkForm" method="post" action="proses.php">
        <input type="hidden" name="aksi" value="hapus">
        <input type="hidden" name="csrf" value="<?= esc($csrfHapus); ?>">
        <input type="hidden" name="back" value="<?= esc($backQs); ?>">
    </form>

    <div class="bulk-bar" id="bulkBar" hidden role="region" aria-label="Aksi untuk data yang dipilih">
        <span><b id="bulkCount">0</b> data dipilih</span>
        <span class="bulk-sp"></span>
        <button type="button" class="btn-xs" id="bulkClear">Batal pilih</button>
        <button type="submit" class="btn-xs danger" form="bulkForm" id="bulkDelete">Hapus yang dipilih</button>
    </div>

    <div class="table-responsive" style="padding: 16px;">
        <table id="dataTable" class="data-table" style="width:100%">
            <thead>
                <tr>
                    <th width="36" data-orderable="false"><input type="checkbox" id="chkAll" aria-label="Pilih semua data yang tampil sesuai pencarian" title="Pilih semua"></th>
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
                    <td><input type="checkbox" class="row-chk" name="ids[]" value="<?= esc($id); ?>" form="bulkForm" aria-label="Pilih data <?= esc($ini); ?>"></td>
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
                            <?php if ($st === 'draft'): ?>
                                <form method="post" action="dashboard.php" style="display:inline;">
                                    <input type="hidden" name="action" value="valid">
                                    <input type="hidden" name="id" value="<?= esc($id); ?>">
                                    <button class="btn-xs approve" type="submit">Validasi</button>
                                </form>
                            <?php endif; ?>
                            <a class="btn-xs" href="export.php?id=<?= urlencode($id); ?>">Export</a>
                            <form method="post" action="proses.php" style="display:inline;"
                                  onsubmit="return confirm('Hapus data kuesioner ini? Tindakan ini tidak bisa dibatalkan.');">
                                <input type="hidden" name="aksi" value="hapus">
                                <input type="hidden" name="csrf" value="<?= esc($csrfHapus); ?>">
                                <input type="hidden" name="back" value="<?= esc($backQs); ?>">
                                <input type="hidden" name="id" value="<?= esc($id); ?>">
                                <button class="btn-xs danger" type="submit">Hapus</button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <script>
    (function () {
        var table = document.getElementById('dataTable');
        var bulk = document.getElementById('bulkForm');
        if (!table || !bulk) { return; }
        var bar = document.getElementById('bulkBar');
        var cnt = document.getElementById('bulkCount');
        var all = document.getElementById('chkAll');
        var sel = {}; // id -> true; tetap tersimpan walau pindah halaman DataTables

        function size() { return Object.keys(sel).length; }
        function dt() {
            if (window.jQuery && jQuery.fn.dataTable && jQuery.fn.dataTable.isDataTable(table)) {
                return jQuery(table).DataTable();
            }
            return null;
        }
        /* Semua id yang sesuai pencarian saat ini (lintas halaman). */
        function scopeIds() {
            var ids = [], api = dt();
            if (api) {
                api.rows({ search: 'applied' }).nodes().each(function (tr) {
                    var c = tr.querySelector('input.row-chk');
                    if (c) { ids.push(c.value); }
                });
            } else {
                Array.prototype.forEach.call(table.querySelectorAll('input.row-chk'), function (c) { ids.push(c.value); });
            }
            return ids;
        }
        function sync() {
            Array.prototype.forEach.call(table.querySelectorAll('input.row-chk'), function (c) {
                var on = !!sel[c.value];
                c.checked = on;
                var tr = c.closest('tr');
                if (tr) { tr.classList.toggle('is-sel', on); }
            });
            var n = size();
            cnt.textContent = n;
            bar.hidden = n === 0;
            var ids = scopeIds(), hit = 0;
            ids.forEach(function (i) { if (sel[i]) { hit++; } });
            all.checked = ids.length > 0 && hit === ids.length;
            all.indeterminate = hit > 0 && hit < ids.length;
        }

        table.addEventListener('change', function (e) {
            var t = e.target;
            if (t === all) {
                scopeIds().forEach(function (i) { if (all.checked) { sel[i] = true; } else { delete sel[i]; } });
            } else if (t.classList && t.classList.contains('row-chk')) {
                if (t.checked) { sel[t.value] = true; } else { delete sel[t.value]; }
            } else { return; }
            sync();
        });
        document.getElementById('bulkClear').addEventListener('click', function () { sel = {}; sync(); });

        bulk.addEventListener('submit', function (e) {
            var n = size();
            if (n === 0) { e.preventDefault(); return; }
            if (!confirm('Hapus ' + n + ' data kuesioner yang dipilih? Tindakan ini tidak bisa dibatalkan.')) {
                e.preventDefault();
                return;
            }
            // pastikan data di halaman lain (tidak ada di DOM) ikut terkirim
            Array.prototype.forEach.call(bulk.querySelectorAll('input.bulk-h'), function (x) { x.remove(); });
            Object.keys(sel).forEach(function (id) {
                var h = document.createElement('input');
                h.type = 'hidden'; h.name = 'ids[]'; h.value = id; h.className = 'bulk-h';
                bulk.appendChild(h);
            });
        });

        if (window.jQuery) { jQuery(table).on('draw.dt', sync); }
        sync();
    })();
    </script>
<?php endif; ?>