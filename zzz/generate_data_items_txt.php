<?php
// generate_data_items_txt.php
// Script untuk membentuk file data_items.txt dari data/items.json

// Path ke items.json
$jsonFile = '../items/items.json';

// Cek file
if (!file_exists($jsonFile)) {
    die("File items.json tidak ditemukan di: {$jsonFile}\n");
}

// Baca & decode JSON
$jsonString = file_get_contents($jsonFile);
$payload    = json_decode($jsonString, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    die("Gagal decode JSON: " . json_last_error_msg() . "\n");
}

$meta  = isset($payload['meta']) ? $payload['meta'] : [];
$items = isset($payload['data']) && is_array($payload['data']) ? $payload['data'] : [];

// ====== KUMPUL DATA & HITUNG ======
$grouped        = [];
$categoryCounts = [];
$uniqueNames    = [];

foreach ($items as $item) {
    if (!isset($item['name'])) {
        continue;
    }

    $name = $item['name'];
    $cats = isset($item['category']) ? $item['category'] : [];

    // pastikan array
    if (!is_array($cats)) {
        $cats = [$cats];
    }

    // koleksi nama unik
    $uniqueNames[$name] = true;

    foreach ($cats as $cat) {
        if (!is_string($cat) || $cat === '') {
            continue;
        }

        if (!isset($grouped[$cat])) {
            $grouped[$cat] = [];
        }

        // hindari duplikat di kategori yang sama
        if (!in_array($name, $grouped[$cat], true)) {
            $grouped[$cat][] = $name;
        }

        if (!isset($categoryCounts[$cat])) {
            $categoryCounts[$cat] = 0;
        }
        $categoryCounts[$cat]++;
    }
}

$totalItems       = count($items);
$totalUniqueNames = count($uniqueNames);

// meta
$patchNotes = isset($meta['patch_notes']) ? $meta['patch_notes'] : '-';
$sourceRepo = isset($meta['source']) ? $meta['source'] : '';

// ====== SUSUN URUTAN KATEGORI ======
$preferredOrder = ['Attack', 'Defense', 'Jungling', 'Magic', 'Movement', 'Roaming'];

$ordered = [];
foreach ($preferredOrder as $cat) {
    if (isset($grouped[$cat])) {
        $ordered[$cat] = $grouped[$cat];
        unset($grouped[$cat]);
    }
}

// kategori lain (kalau ada) urut alfabet
if (!empty($grouped)) {
    ksort($grouped, SORT_NATURAL | SORT_FLAG_CASE);
    foreach ($grouped as $cat => $names) {
        $ordered[$cat] = $names;
    }
}

// ====== BANGUN ISI FILE TXT ======
$lines = [];

// Header mirip README versi teks
$lines[] = "README ini dibuat secara otomatis dari `data/items.json`.";
$lines[] = "";

if ($sourceRepo !== '') {
    $lines[] = "- Source repo: " . $sourceRepo;
}
$lines[] = "- Patch notes: *" . $patchNotes . "*";
$lines[] = "- Total baris item (data): *" . $totalItems . "*";
$lines[] = "- Total nama unik item: *" . $totalUniqueNames . "*";
$lines[] = "";
$lines[] = "## Ringkasan Category";
$lines[] = "";

// ringkasan kategori (urut alfabet biar konsisten)
ksort($categoryCounts, SORT_NATURAL | SORT_FLAG_CASE);
foreach ($categoryCounts as $cat => $cnt) {
    $lines[] = "- *" . $cat . "*: " . $cnt . " item";
}

$lines[] = "";
$lines[] = "## Daftar Item per Category";
$lines[] = "";

// daftar item per kategori
foreach ($ordered as $cat => $names) {
    $lines[] = '[' . $cat . ']';
    $lines[] = '';

    sort($names, SORT_NATURAL | SORT_FLAG_CASE);

    foreach ($names as $n) {
        $lines[] = $n;
    }

    $lines[] = ''; // pemisah antar kategori
}

$output = implode(PHP_EOL, $lines) . PHP_EOL;

// Simpan ke data_items.txt di root project
$outFile = __DIR__ . '/data_items.txt';
file_put_contents($outFile, $output);

// Output juga ke browser/CLI
header('Content-Type: text/plain; charset=utf-8');
echo $output;
