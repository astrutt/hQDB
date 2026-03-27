<?php
// import_legacy.php
// Version: 1.0
// Execute via CLI only to preserve database integrity

require_once '/var/www/wpm.2600.chat/hqdb/db.php';

if (php_sapi_name() !== 'cli') {
    die("FATAL: Execute this script from the terminal.\n");
}

if ($argc < 2) {
    die("Usage: php import_legacy.php <path_to_json>\n");
}

$file = $argv[1];
if (!file_exists($file)) {
    die("FATAL: File not found -> $file\n");
}

echo "Reading JSON payload...\n";
$json = file_get_contents($file);
$data = json_decode($json, true);

if (!$data) {
    die("FATAL: Invalid JSON format or memory limit exceeded.\n");
}

// Handle variations in JSON structure from different archive sources
if (isset($data['quotes'])) {
    $data = $data['quotes'];
}

$db = DB::getInstance();

// Use a transaction so all 8,000+ quotes are written in a single disk I/O operation
$db->beginTransaction();

$count = 0;
$skipped = 0;

$stmtInsertWithId = $db->prepare("INSERT INTO quotes (id, quote_text, score, status, submitted_by_ip) VALUES (:id, :text, :score, 'approved', '127.0.0.1')");
$stmtInsertAuto = $db->prepare("INSERT INTO quotes (quote_text, score, status, submitted_by_ip) VALUES (:text, :score, 'approved', '127.0.0.1')");

echo "Initiating bulk database insertion...\n";

foreach ($data as $row) {
    // Defensively locate the text payload depending on the archive schema
    $text = $row['quote_text'] ?? $row['quote'] ?? $row['text'] ?? $row['lines'] ?? '';
    
    // Some JSON dumps format lines as an array rather than a single string with \n
    if (is_array($text)) {
        $text = implode("\n", $text);
    }
    
    $text = trim($text);
    
    if (empty($text)) {
        $skipped++;
        continue;
    }

    $score = (int)($row['score'] ?? $row['votes'] ?? 0);
    $id = isset($row['id']) ? (int)$row['id'] : null;

    try {
        if ($id) {
            $stmtInsertWithId->execute([':id' => $id, ':text' => $text, ':score' => $score]);
        } else {
            // Fallback if the archive doesn't have explicit IDs
            $stmtInsertAuto->execute([':text' => $text, ':score' => $score]);
        }
        $count++;
    } catch (PDOException $e) {
        // Error code 23000 means the ID already exists in your SQLite file.
        // We catch it and skip to avoid crashing the whole 8,000-row transaction.
        $skipped++;
    }
}

$db->commit();

echo "========================================\n";
echo "Import Complete.\n";
echo "Successfully written: $count quotes\n";
echo "Collisions/Skipped:   $skipped\n";
echo "========================================\n";
