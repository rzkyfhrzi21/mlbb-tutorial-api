<?php
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');

// ===============================
// KONFIGURASI REPO GITHUB KAMU
// ===============================
$githubUser = 'rzkyfhrzi21';
$repoName   = 'mlbb-tutorial-api';
$branch     = 'master';

// Sekarang baru ada resource "items"
$resource = isset($_GET['resource']) ? strtolower(trim($_GET['resource'])) : 'items';

$resourceMap = [
    'items' => 'data/items.json', // path file JSON items di repo kamu
];

if (!isset($resourceMap[$resource])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Resource tidak dikenali. Gunakan ?resource=items',
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

$filePath     = $resourceMap[$resource];
$githubApiUrl = "https://raw.githubusercontent.com/{$githubUser}/{$repoName}/{$branch}/{$filePath}";


// ===============================
// FUNGSI AMBIL DATA DARI GITHUB
// ===============================
function fetch_from_github($url)
{
    if (ini_get('allow_url_fopen')) {
        $result = @file_get_contents($url);
        if ($result === false) {
            return null;
        }
        return $result;
    }

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 10,
        ]);

        $result = curl_exec($ch);

        if (curl_errno($ch)) {
            curl_close($ch);
            return null;
        }

        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($statusCode !== 200) {
            return null;
        }

        return $result;
    }

    return null;
}


// ===============================
// AMBIL JSON DARI GITHUB
// ===============================
$jsonString = fetch_from_github($githubApiUrl);

if ($jsonString === null) {
    http_response_code(502);
    echo json_encode([
        'success'    => false,
        'message'    => 'Gagal mengambil data dari GitHub. Cek URL atau koneksi server.',
        'github_url' => $githubApiUrl,
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

$data = json_decode($jsonString, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Format JSON dari GitHub tidak valid: ' . json_last_error_msg(),
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// Struktur: { meta: {...}, data: [...] }
$meta  = isset($data['meta']) ? $data['meta'] : null;
$items = isset($data['data']) && is_array($data['data']) ? $data['data'] : [];


// ===============================
// FILTER: ?name= (HANYA BERDASARKAN NAMA, CONTAINS)
// ===============================
$nameQuery = isset($_GET['name']) ? strtolower(trim($_GET['name'])) : null;

if ($resource === 'items' && $nameQuery !== null && $nameQuery !== '') {
    $items = array_filter($items, function ($item) use ($nameQuery) {
        $itemName = isset($item['name']) ? strtolower($item['name']) : '';
        // contains, case-insensitive
        return stripos($itemName, $nameQuery) !== false;
    });

    // Reset index array
    $items = array_values($items);
}

// Simpan total baris asli setelah filter (kalau ada)
$totalRows = count($items);


// ===============================
// GROUPING BERDASARKAN "name"
// ===============================

$groupByName = [];

foreach ($items as $item) {
    if (!isset($item['name'])) {
        continue;
    }

    // Normalisasi name untuk grouping
    $normalized = strtolower(trim($item['name']));
    if ($normalized === '') {
        continue;
    }

    if (!isset($groupByName[$normalized])) {
        $groupByName[$normalized] = [];
    }

    $groupByName[$normalized][] = $item;
}

// Sekarang kita bentuk:
// - $uniqueItems      → 1 entry per name (hanya name saja)
// - $duplicates       → list name yang punya lebih dari 1 item
// - $categoryCounts   → hitung berapa nama per kategori
$uniqueItems    = [];
$duplicates     = [];
$categoryCounts = [];

foreach ($groupByName as $normalizedName => $itemsWithSameName) {
    // Ambil 1 item yang akan kita pakai sebagai "utama"
    $chosen = $itemsWithSameName[0];
    $name   = $chosen['name'] ?? null;

    // DATA UTAMA: hanya name (sesuai permintaan)
    $uniqueItems[] = [
        'name' => $name,
    ];

    // ---- HITUNG PER KATEGORI ----
    // Kumpulkan kategori untuk nama ini (unik, dan string saja)
    $seenCategories = [];

    foreach ($itemsWithSameName as $it) {
        if (!isset($it['category'])) {
            continue;
        }

        $catField = $it['category'];

        // CASE 1: category adalah array
        if (is_array($catField)) {
            foreach ($catField as $c) {
                if (!is_string($c)) {
                    continue;
                }
                $ck = trim($c);
                if ($ck === '') {
                    continue;
                }
                $seenCategories[$ck] = true;
            }
        }
        // CASE 2: category single value string
        elseif (is_string($catField)) {
            $ck = trim($catField);
            if ($ck !== '') {
                $seenCategories[$ck] = true;
            }
        }
        // tipe lain (number/object) di-skip saja atau bisa kamu handle kalau perlu
    }

    // Kalau sama sekali tidak ada kategori, anggap "Unknown"
    if (empty($seenCategories)) {
        $seenCategories['Unknown'] = true;
    }

    // Tambah hitungan kategori (1 per nama per kategori)
    foreach ($seenCategories as $catName => $_) {
        if (!isset($categoryCounts[$catName])) {
            $categoryCounts[$catName] = 0;
        }
        $categoryCounts[$catName]++;
    }

    // DUPLIKAT: kalau lebih dari 1 item dengan nama sama
    if (count($itemsWithSameName) > 1) {
        $duplicates[] = [
            'name'  => $name,
            'count' => count($itemsWithSameName),
            'items' => $itemsWithSameName, // semua versi item dengan nama ini
        ];
    }
}

$uniqueNamesCount = count($uniqueItems);


// ===============================
// RESPONSE KE CLIENT
// ===============================
echo json_encode([
    'success'            => true,
    'resource'           => $resource,
    'source'             => $githubApiUrl,
    'meta'               => $meta,

    // total baris data setelah filter (kalau ada)
    'total_rows'         => $totalRows,

    // jumlah nama unik
    'unique_names_count' => $uniqueNamesCount,

    // ringkasan jumlah nama per kategori
    'category_counts'    => $categoryCounts,

    // info duplikat: nama apa saja yang muncul lebih dari sekali
    'duplicates'         => $duplicates,

    // data utama: hanya name, 1 per nama
    'data'               => $uniqueItems,
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
