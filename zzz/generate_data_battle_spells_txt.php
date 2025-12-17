<?php
// generate_data_battle_spells_txt.php
// Script untuk membentuk file data_battle_spells.txt dari data/battle_spells.json

// Path ke battle_spells.json
$jsonFile = '../battle_spells/battle_spells.json';

// Cek file
if (!file_exists($jsonFile)) {
    die("File battle_spells.json tidak ditemukan di: {$jsonFile}\n");
}

// Baca & decode JSON
$jsonString = file_get_contents($jsonFile);
$payload    = json_decode($jsonString, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    die("Gagal decode JSON: " . json_last_error_msg() . "\n");
}

$meta   = isset($payload['meta']) ? $payload['meta'] : [];
$spells = isset($payload['data']) && is_array($payload['data']) ? $payload['data'] : [];

// ====== KUMPUL DATA & HITUNG ======
$uniqueNames = [];

foreach ($spells as $spell) {
    if (!isset($spell['name'])) {
        continue;
    }

    $name = $spell['name'];
    $uniqueNames[$name] = true;
}

$totalSpells      = count($spells);
$totalUniqueNames = count($uniqueNames);

// meta
$patchNotes = isset($meta['patch_notes']) ? $meta['patch_notes'] : '-';
$sourceRepo = isset($meta['source']) ? $meta['source'] : '';

// ====== BANGUN ISI FILE TXT ======
$lines = [];

// Header mirip format items, tapi khusus battle_spells
$lines[] = "README ini dibuat secara otomatis dari battle_spells.json`.";
$lines[] = "";

if ($sourceRepo !== '') {
    $lines[] = "- Source repo: " . $sourceRepo;
}
$lines[] = "- Patch notes: *" . $patchNotes . "*";
$lines[] = "- Total baris battle spell (data): *" . $totalSpells . "*";
$lines[] = "- Total nama unik battle spell: *" . $totalUniqueNames . "*";
$lines[] = "";
$lines[] = "## Daftar Battle Spell";
$lines[] = "";

// Daftar battle spell (urut nama biar rapi)
usort($spells, function ($a, $b) {
    $na = isset($a['name']) ? $a['name'] : '';
    $nb = isset($b['name']) ? $b['name'] : '';
    return strcasecmp($na, $nb);
});

foreach ($spells as $spell) {
    $name   = isset($spell['name']) ? $spell['name'] : '(tanpa nama)';
    $cd     = isset($spell['cooldown']) ? $spell['cooldown'] : null;
    $unlock = isset($spell['unlocked_at_level']) ? $spell['unlocked_at_level'] : null;

    // Baris utama nama spell
    $lines[] = $name;

    // Info tambahan di bawahnya (opsional)
    $infoParts = [];

    if ($cd !== null && $cd !== '') {
        $infoParts[] = "Cooldown: {$cd}s";
    }
    if ($unlock !== null && $unlock !== '') {
        $infoParts[] = "Unlock: Lv {$unlock}";
    }

    if (!empty($infoParts)) {
        $lines[] = "  - " . implode(" | ", $infoParts);
    }

    $lines[] = ""; // pemisah antar spell
}

$output = implode(PHP_EOL, $lines) . PHP_EOL;

// Simpan ke data_battle_spells.txt di folder yang sama dengan script
$outFile = __DIR__ . '/data_battle_spells.txt';
file_put_contents($outFile, $output);

// Output juga ke browser/CLI
header('Content-Type: text/plain; charset=utf-8');
echo $output;
