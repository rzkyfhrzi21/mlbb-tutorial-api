<?php
// generate_data_emblems_txt.php
// Script untuk membentuk file data_emblems.txt dari data/emblems.json

// Path ke emblems.json
$jsonFile = '../emblems/emblems.json';

// Cek file
if (!file_exists($jsonFile)) {
    die("File emblems.json tidak ditemukan di: {$jsonFile}\n");
}

// Baca & decode JSON
$jsonString = file_get_contents($jsonFile);
$payload    = json_decode($jsonString, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    die("Gagal decode JSON: " . json_last_error_msg() . "\n");
}

$meta  = isset($payload['meta']) ? $payload['meta'] : [];
$data  = isset($payload['data']) && is_array($payload['data']) ? $payload['data'] : [];

$mainEmblems    = isset($data['main_emblems']) && is_array($data['main_emblems']) ? $data['main_emblems'] : [];
$abilityEmblems = isset($data['ability_emblems']) && is_array($data['ability_emblems']) ? $data['ability_emblems'] : [];

// ====== HITUNG DATA ======
$totalMain    = count($mainEmblems);
$totalAbility = count($abilityEmblems);

// hitung ability per section
$sectionCounts = [];
foreach ($abilityEmblems as $ab) {
    $sec = isset($ab['section']) ? $ab['section'] : null;
    if ($sec === null || $sec === '') {
        continue;
    }
    if (!isset($sectionCounts[$sec])) {
        $sectionCounts[$sec] = 0;
    }
    $sectionCounts[$sec]++;
}

// meta
$patchNotes = isset($meta['patch_notes']) ? $meta['patch_notes'] : '-';
$sourceRepo = isset($meta['source']) ? $meta['source'] : '';

// ====== BANGUN ISI FILE TXT ======
$lines = [];

$lines[] = "README ini dibuat secara otomatis dari `emblems.json`.";
$lines[] = "";

if ($sourceRepo !== '') {
    $lines[] = "- Source repo: " . $sourceRepo;
}
$lines[] = "- Patch notes: *" . $patchNotes . "*";
$lines[] = "- Total main emblem: *" . $totalMain . "*";
$lines[] = "- Total ability emblem: *" . $totalAbility . "*";

if (!empty($sectionCounts)) {
    $lines[] = "";
    $lines[] = "## Ringkasan Ability per Section";
    $lines[] = "";
    ksort($sectionCounts, SORT_NUMERIC);
    foreach ($sectionCounts as $sec => $cnt) {
        $lines[] = "- Section " . $sec . ": " . $cnt . " ability";
    }
}

$lines[] = "";
$lines[] = "## Main Emblems";
$lines[] = "";

// List main emblems
foreach ($mainEmblems as $emblem) {
    $name  = isset($emblem['name']) ? $emblem['name'] : '(tanpa nama)';
    $attrs = isset($emblem['attributes']) && is_array($emblem['attributes'])
        ? $emblem['attributes']
        : [];

    $lines[] = $name;

    if (!empty($attrs)) {
        $lines[] = "  - Attributes:";
        foreach ($attrs as $attr) {
            $lines[] = "    • " . $attr;
        }
    }

    $lines[] = "";
}

$lines[] = "## Ability Emblems";
$lines[] = "";

// Kelompokkan ability berdasarkan section
$bySection = [];
foreach ($abilityEmblems as $ab) {
    $sec = isset($ab['section']) ? $ab['section'] : 0;
    if (!isset($bySection[$sec])) {
        $bySection[$sec] = [];
    }
    $bySection[$sec][] = $ab;
}

// Urutkan section
ksort($bySection, SORT_NUMERIC);

// Tulis per section
foreach ($bySection as $sec => $list) {
    $lines[] = "[Section " . $sec . "]";
    $lines[] = "";

    // urutkan nama ability biar rapi
    usort($list, function ($a, $b) {
        $na = isset($a['name']) ? $a['name'] : '';
        $nb = isset($b['name']) ? $b['name'] : '';
        return strcasecmp($na, $nb);
    });

    foreach ($list as $ab) {
        $name     = isset($ab['name']) ? $ab['name'] : '(tanpa nama)';
        $benefits = isset($ab['benefits']) ? $ab['benefits'] : '';
        $desc     = isset($ab['desc']) ? $ab['desc'] : null;
        $cd       = array_key_exists('cd', $ab) ? $ab['cd'] : null;

        $lines[] = $name;

        if ($benefits !== '') {
            $lines[] = "  - Benefit: " . $benefits;
        }
        if ($desc !== null && $desc !== '') {
            $lines[] = "  - Desc   : " . $desc;
        }
        if ($cd !== null && $cd !== '') {
            $lines[] = "  - CD     : " . $cd . "s";
        }

        $lines[] = "";
    }
}

$output = implode(PHP_EOL, $lines) . PHP_EOL;

// Simpan ke data_emblems.txt di folder yang sama
$outFile = __DIR__ . '/data_emblems.txt';
file_put_contents($outFile, $output);

// Output juga ke browser/CLI
header('Content-Type: text/plain; charset=utf-8');
echo $output;
