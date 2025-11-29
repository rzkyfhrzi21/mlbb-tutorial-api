<?php
// generate_data_heroes_txt.php
// Script untuk membentuk file data_heroes.txt dari heroes.json

// Path ke heroes.json (sesuaikan kalau kamu taruh di /data/heroes.json)
$jsonFile = __DIR__ . '/heroes.json';

// Cek file
if (!file_exists($jsonFile)) {
    die("File heroes.json tidak ditemukan di: {$jsonFile}\n");
}

// Baca & decode JSON
$jsonString = file_get_contents($jsonFile);
$payload    = json_decode($jsonString, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    die("Gagal decode JSON: " . json_last_error_msg() . "\n");
}

$meta   = isset($payload['meta']) ? $payload['meta'] : [];
$heroes = isset($payload['data']) && is_array($payload['data']) ? $payload['data'] : [];

// ====== KUMPUL DATA & HITUNG ======
$uniqueNames   = [];
$roleCounts    = [];
$laneCounts    = [];

$heroesByRole = [];
$heroesByLane = [];
$allHeroes    = []; // untuk daftar hero urut nama

foreach ($heroes as $hero) {
    if (!isset($hero['hero_name'])) {
        continue;
    }

    $name = $hero['hero_name'];
    $id   = isset($hero['hero_id']) ? $hero['hero_id'] : '';
    $uniqueNames[$name] = true;

    // label standar untuk dipakai di beberapa daftar
    $label = $id !== '' ? "{$name} ({$id})" : $name;

    // simpan ke daftar global hero (nanti di-sort A–Z)
    $allHeroes[] = [
        'name'  => $name,
        'id'    => $id,
        'label' => $label,
    ];

    // -------- ROLE --------
    $roles = isset($hero['role']) ? $hero['role'] : [];
    if (!is_array($roles)) {
        $roles = [$roles];
    }

    foreach ($roles as $r) {
        if (!is_string($r) || $r === '') {
            continue;
        }

        if (!isset($roleCounts[$r])) {
            $roleCounts[$r] = 0;
        }
        $roleCounts[$r]++;

        if (!isset($heroesByRole[$r])) {
            $heroesByRole[$r] = [];
        }

        if (!in_array($label, $heroesByRole[$r], true)) {
            $heroesByRole[$r][] = $label;
        }
    }

    // -------- LANING --------
    $lanes = isset($hero['laning']) ? $hero['laning'] : [];
    if (!is_array($lanes)) {
        $lanes = [$lanes];
    }

    foreach ($lanes as $ln) {
        if (!is_string($ln) || $ln === '') {
            continue;
        }

        if (!isset($laneCounts[$ln])) {
            $laneCounts[$ln] = 0;
        }
        $laneCounts[$ln]++;

        if (!isset($heroesByLane[$ln])) {
            $heroesByLane[$ln] = [];
        }

        if (!in_array($label, $heroesByLane[$ln], true)) {
            $heroesByLane[$ln][] = $label;
        }
    }
}

$totalHeroes      = count($heroes);
$totalUniqueNames = count($uniqueNames);

// meta
$patchNotes = isset($meta['patch_notes']) ? $meta['patch_notes'] : '-';
$sourceRepo = isset($meta['source']) ? $meta['source'] : '';

// ====== BANGUN ISI FILE TXT ======
$lines = [];

$lines[] = "README ini dibuat secara otomatis dari `heroes.json`.";
$lines[] = "";

if ($sourceRepo !== '') {
    $lines[] = "- Source repo: " . $sourceRepo;
}
$lines[] = "- Patch notes: *" . $patchNotes . "*";
$lines[] = "- Total baris hero (data): *" . $totalHeroes . "*";
$lines[] = "- Total nama unik hero: *" . $totalUniqueNames . "*";
$lines[] = "";

// Ringkasan Role
$lines[] = "## Ringkasan Role";
$lines[] = "";

if (!empty($roleCounts)) {
    ksort($roleCounts, SORT_NATURAL | SORT_FLAG_CASE);
    foreach ($roleCounts as $role => $cnt) {
        $lines[] = "- *" . $role . "*: " . $cnt . " hero";
    }
} else {
    $lines[] = "- (tidak ada data role)";
}

$lines[] = "";

// Ringkasan Lane
$lines[] = "## Ringkasan Lane";
$lines[] = "";

if (!empty($laneCounts)) {
    ksort($laneCounts, SORT_NATURAL | SORT_FLAG_CASE);
    foreach ($laneCounts as $lane => $cnt) {
        $lines[] = "- *" . $lane . "*: " . $cnt . " hero";
    }
} else {
    $lines[] = "- (tidak ada data lane)";
}

$lines[] = "";

// ====== DAFTAR HERO URUT NAMA ======
$lines[] = "## Daftar Hero (urut nama)";
$lines[] = "";

// sort berdasarkan hero_name
usort($allHeroes, function ($a, $b) {
    return strcasecmp($a['name'], $b['name']);
});

foreach ($allHeroes as $h) {
    $label = $h['label'];
    $lines[] = $label;
}

$lines[] = "";

// ====== DAFTAR HERO PER ROLE ======
$lines[] = "## Daftar Hero per Role";
$lines[] = "";

// Daftar hero per role
if (!empty($heroesByRole)) {
    ksort($heroesByRole, SORT_NATURAL | SORT_FLAG_CASE);
    foreach ($heroesByRole as $role => $list) {
        $lines[] = '[' . $role . ']';
        $lines[] = '';

        sort($list, SORT_NATURAL | SORT_FLAG_CASE);
        foreach ($list as $label) {
            $lines[] = $label;
        }

        $lines[] = '';
    }
} else {
    $lines[] = "(tidak ada data role untuk ditampilkan)";
    $lines[] = "";
}

// ====== DAFTAR HERO PER LANE ======
$lines[] = "## Daftar Hero per Lane";
$lines[] = "";

if (!empty($heroesByLane)) {
    ksort($heroesByLane, SORT_NATURAL | SORT_FLAG_CASE);
    foreach ($heroesByLane as $lane => $list) {
        $lines[] = '[' . $lane . ']';
        $lines[] = '';

        sort($list, SORT_NATURAL | SORT_FLAG_CASE);
        foreach ($list as $label) {
            $lines[] = $label;
        }

        $lines[] = '';
    }
} else {
    $lines[] = "(tidak ada data lane untuk ditampilkan)";
    $lines[] = "";
}

$output = implode(PHP_EOL, $lines) . PHP_EOL;

// Simpan ke data_heroes.txt di folder yang sama
$outFile = __DIR__ . '/data_heroes.txt';
file_put_contents($outFile, $output);

// Output juga ke browser/CLI
header('Content-Type: text/plain; charset=utf-8');
echo $output;
