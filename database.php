<?php
/**
 * database.php — manajer data kuesioner flat-file JSON tanpa database server.
 *
 * Struktur file data/kuesioner.json:
 *   {
 *     "surveys": [
 *       {
 *         "id": "smd_xxxx",
 *         "waktu": "Y-m-d H:i:s",
 *         "status": "draft|valid",
 *         "data": { ... jawaban dari form ... }
 *       }
 *     ]
 *   }
 *
 * Fitur:
 *   - db_all()     : ambil semua (sort terbaru duluan)
 *   - db_find($id) : cari 1 record
 *   - db_save($data) : atomic write (tmp + rename)
 *   - db_delete($id) : hapus 1
 *   - db_count()   : jumlah record
 */

require_once __DIR__ . '/config.php';

function db_load(): array
{
    $file = DATA_FILE;
    if (!file_exists($file)) {
        if (!is_dir(DATA_DIR)) {
            @mkdir(DATA_DIR, 0777, true);
        }
        file_put_contents($file, json_encode(['surveys' => []], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        return ['surveys' => []];
    }
    $raw = file_get_contents($file);
    $d = json_decode($raw, true);
    if (!is_array($d)) {
        return ['surveys' => []];
    }
    return $d;
}

function db_save(array $data): bool
{
    $file = DATA_FILE;
    $dir = DATA_DIR;
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }
    $tmp = $file . '.tmp';
    $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    if (file_put_contents($tmp, $json) === false) {
        return false;
    }
    // atomic rename
    $ok = @rename($tmp, $file);
    if (!$ok) {
        @copy($tmp, $file);
        @unlink($tmp);
    }
    return $ok;
}

function db_all(): array
{
    $d = db_load();
    $rows = $d['surveys'] ?? [];
    usort($rows, function ($a, $b) {
        $ta = (isset($a['waktu']) ? $a['waktu'] : '') ?: '0000-00-00';
        $tb = (isset($b['waktu']) ? $b['waktu'] : '') ?: '0000-00-00';
        return $tb <=> $ta;
    });
    return $rows;
}

function db_find(string $id): ?array
{
    foreach (db_all() as $r) {
        if (isset($r['id']) && $r['id'] === $id) {
            return $r;
        }
    }
    return null;
}

function db_delete(string $id): bool
{
    $rows = db_all();
    $kept = array_values(array_filter($rows, function ($r) use ($id) {
        return (!isset($r['id']) || $r['id'] !== $id);
    }));
    return db_save(['surveys' => $kept]);
}

function db_count(): int
{
    return count(db_all());
}

function db_valid_count(): int
{
    return count(array_filter(db_all(), function ($r) {
        return ($r['status'] ?? '') === 'valid';
    }));
}

function db_draft_count(): int
{
    return count(array_filter(db_all(), function ($r) {
        return ($r['status'] ?? '') === 'draft';
    }));
}
