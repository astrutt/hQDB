<?php
// cleanup_legacy.php
// Decodes double-escaped HTML and <br> tags from imported archives.

require_once '/var/www/wpm.2600.chat/hqdb/db.php';

if (php_sapi_name() !== 'cli') {
    die("FATAL: Execute this script from the terminal.\n");
}

$db = DB::getInstance();

echo "Scanning database for legacy HTML artifacts...\n";

// Fetch all quotes
$stmt = $db->query("SELECT id, quote_text FROM quotes");
$quotes = $stmt->fetchAll();

$db->beginTransaction();
$updateStmt = $db->prepare("UPDATE quotes SET quote_text = :text WHERE id = :id");

$updatedCount = 0;

foreach ($quotes as $q) {
    $originalText = $q['quote_text'];
    $cleanText = $originalText;

    // 1. Convert all variations of <br>, <br/>, <br /> to standard newlines (\n)
    $cleanText = preg_replace('/<br\s*\/?>/i', "\n", $cleanText);

    // 2. Decode HTML entities (&lt; to <, &quot; to ", &#x27; to ')
    // ENT_QUOTES handles both single and double quotes, ENT_HTML5 ensures modern decoding
    $cleanText = html_entity_decode($cleanText, ENT_QUOTES | ENT_HTML5, 'UTF-8');

    // Only execute an UPDATE if the text actually changed to save disk I/O
    if ($cleanText !== $originalText) {
        $updateStmt->execute([':text' => $cleanText, ':id' => $q['id']]);
        $updatedCount++;
    }
}

$db->commit();

echo "========================================\n";
echo "Scrub Complete.\n";
echo "Sanitized $updatedCount quotes.\n";
echo "========================================\n";
