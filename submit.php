<?php
// Version: 1.1.2
// Last Updated: 2026-03-27
// submit.php

require_once 'db.php';

$db = DB::getInstance();
$ipAddress = $_SERVER['REMOTE_ADDR'];
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $quoteText = trim($_POST['quote_text'] ?? '');
    
    if (empty($quoteText)) {
        $message = "Error: Quote cannot be empty.";
        $messageType = "error";
    } else {
        $stmt = $db->prepare("SELECT COUNT(*) FROM ip_tracking WHERE ip_address = :ip AND action = 'submit' AND timestamp > datetime('now', '-3 minutes')");
        $stmt->execute([':ip' => $ipAddress]);
        
        if ($stmt->fetchColumn() > 0) {
            $message = "Anti-flood triggered. Please wait a few minutes.";
            $messageType = "error";
        } else {
            $db->beginTransaction();
            try {
                $stmt = $db->prepare("INSERT INTO quotes (quote_text, submitted_by_ip) VALUES (:text, :ip)");
                $stmt->execute([':text' => $quoteText, ':ip' => $ipAddress]);
                
                $stmt = $db->prepare("INSERT INTO ip_tracking (ip_address, action) VALUES (:ip, 'submit')");
                $stmt->execute([':ip' => $ipAddress]);
                
                $db->commit();
                $message = "ACK. Quote submitted successfully. Awaiting moderation.";
                $messageType = "success";
            } catch (Exception $e) {
                $db->rollBack();
                $message = "System fault while saving quote.";
                $messageType = "error";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Submit Quote - hQDB</title>
    <style>
        body { font-family: 'Courier New', Courier, monospace; background: #000000; color: #cccccc; margin: 0; padding: 20px; }
        a { color: #00ff00; text-decoration: none; }
        a:hover { color: #ffffff; background: #005500; }
        .container { max-width: 900px; margin: 0 auto; background: #050505; padding: 20px; border: 1px solid #333; }
        .nav { margin-bottom: 20px; padding-bottom: 10px; border-bottom: 1px dashed #333; font-weight: bold; }
        h1 { color: #ffffff; margin-top: 0; }
        
        textarea { width: 100%; height: 250px; background: #000000; color: #00ff00; border: 1px solid #333; font-family: 'Courier New', Courier, monospace; padding: 10px; box-sizing: border-box; margin-bottom: 10px; resize: vertical; }
        textarea:focus { outline: none; border-color: #00ff00; }
        
        button { background: #003300; color: #00ff00; border: 1px solid #00ff00; padding: 10px 20px; font-family: monospace; font-weight: bold; cursor: pointer; }
        button:hover { background: #00ff00; color: #000000; }
        
        .msg { padding: 10px; margin-bottom: 15px; font-weight: bold; }
        .msg.success { background: #002200; color: #00ff00; border: 1px solid #00ff00; }
        .msg.error { background: #220000; color: #ff0000; border: 1px solid #ff0000; }
        
        .guidelines { font-size: 0.9em; color: #888; margin-bottom: 15px; background: #111; padding: 10px; border-left: 4px solid #333; }
        .guidelines strong { color: #ccc; }
    </style>
</head>
<body>

<div class="container">
    <div class="nav">
        [ <a href="index.php">Back to Database</a> ]
    </div>

    <h1>Input Stream :: Submit Quote</h1>

    <?php if ($message): ?>
        <div class="msg <?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <div class="guidelines">
        <strong>Submission Parameters:</strong>
        <ul style="margin-top: 5px; margin-bottom: 0;">
            <li>Copy/paste directly from terminal or client.</li>
            <li>Strip excessive timestamps if irrelevant, preserve `<nickname>` format.</li>
            <li>Do not modify payload for comedic effect.</li>
            <li>Source IP is logged. Spam will result in network blacklisting.</li>
        </ul>
    </div>

    <form method="POST" action="submit.php">
        <textarea name="quote_text" placeholder="<root> rm -rf /&#10;<sysadmin> wait no&#10;* root has quit (Connection reset by peer)" required></textarea>
        <br>
        <button type="submit">TRANSMIT</button>
    </form>
</div>

</body>
</html>
