<?php
// Set response jadi JSON
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');

// ===============================
// KONFIGURASI GITHUB REPO
// ===============================
$githubUser = 'rzkyfhrzi21';
$repoName   = 'mlbb-tutorial-api';
$branch     = 'main';

// Ubah sesuai lokasi file di repo-mu
// contoh: 'spells.json' atau 'data/spells-api.json'
$filePath   = 'spells.json';

// URL raw GitHub
$githubApiUrl = "https://raw.githubusercontent.com/{$githubUser}/{$repoName}/{$branch}/{$filePath}";

// ===============================
// FUNGSI AMBIL DATA DARI GITHUB
// ===============================
function fetch_from_github($url)
{
    // Kalau allow_url_fopen aktif, bisa pakai file_get_contents
    if (ini_get('allow_url_fopen')) {
        $result = @file_get_contents($url);
        if ($result === false) {
            return null;
        }
        return $result;
    }

    // Alternatif: pakai cURL
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

    // Kalau dua-duanya nggak ada
    return null;
}

// ===============================
// AMBIL DATA DARI GITHUB
// ===============================
$jsonString = fetch_from_github($githubApiUrl);

if ($jsonString === null) {
    http_response_code(502);
    echo json_encode([
        'success' => false,
        'message' => 'Gagal mengambil data dari GitHub. Cek URL atau koneksi server.',
        'github_url' => $githubApiUrl,
    ]);
    exit;
}

$data = json_decode($jsonString, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Format JSON dari GitHub tidak valid: ' . json_last_error_msg(),
    ]);
    exit;
}

// Pastikan struktur sesuai contoh kamu
$meta  = isset($data['meta']) ? $data['meta'] : null;
$items = isset($data['data']) && is_array($data['data']) ? $data['data'] : [];

// ===============================
// FILTER VIA QUERY STRING
// ===============================
// ?name=flicker (pencarian by nama, case-insensitive)
// ?min_level=5  (filter spell minimal level unlock)

$nameQuery     = isset($_GET['name']) ? strtolower(trim($_GET['name'])) : null;
$minLevelQuery = isset($_GET['min_level']) ? (int) $_GET['min_level'] : null;

$filtered = array_filter($items, function ($spell) use ($nameQuery, $minLevelQuery) {
    $pass = true;

    if ($nameQuery !== null && $nameQuery !== '') {
        $spellName = isset($spell['name']) ? strtolower($spell['name']) : '';
        // gunakan stripos agar bisa "contains"
        if (stripos($spellName, $nameQuery) === false) {
            $pass = false;
        }
    }

    if ($minLevelQuery !== null) {
        $level = isset($spell['unlocked_at_level']) ? (int) $spell['unlocked_at_level'] : 0;
        if ($level < $minLevelQuery) {
            $pass = false;
        }
    }

    return $pass;
});

// Reset index array
$filtered = array_values($filtered);

// ===============================
// RESPONSE KE CLIENT
// ===============================
echo json_encode([
    'success' => true,
    'source'  => 'github_raw',
    'meta'    => $meta,
    'count'   => count($filtered),
    'data'    => $filtered,
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
