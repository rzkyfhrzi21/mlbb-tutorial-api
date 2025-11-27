<?php
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');

// ===============================
// KONFIGURASI REPO GITHUB KAMU
// ===============================
$githubUser = 'rzkyfhrzi21';
$repoName   = 'mlbb-tutorial-api';
$branch     = 'master';

// Sekarang baru ada resource "spells"
$resource = isset($_GET['resource']) ? strtolower(trim($_GET['resource'])) : 'spells';

$resourceMap = [
    'spells' => 'sample/spells.json', // path file JSON spells di repo kamu
];

if (!isset($resourceMap[$resource])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Resource tidak dikenali. Gunakan ?resource=spells',
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
// FILTER: ?name= (HANYA BERDASARKAN NAMA)
// ===============================
$nameQuery = isset($_GET['name']) ? strtolower(trim($_GET['name'])) : null;

if ($resource === 'spells' && $nameQuery !== null && $nameQuery !== '') {
    $items = array_filter($items, function ($spell) use ($nameQuery) {
        $spellName = isset($spell['name']) ? strtolower($spell['name']) : '';
        // contains, case-insensitive
        return stripos($spellName, $nameQuery) !== false;
    });

    // Reset index array
    $items = array_values($items);
}


// ===============================
// RESPONSE KE CLIENT
// ===============================
echo json_encode([
    'success'  => true,
    'resource' => $resource,
    'source'   => $githubApiUrl,
    'meta'     => $meta,
    'count'    => count($items),
    'data'     => $items,
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
