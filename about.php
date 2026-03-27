<?php
// Version: 1.1.6
// Last Updated: 2026-03-27

function getFileContent($filename) {
    if (file_exists($filename)) {
        return htmlspecialchars(file_get_contents($filename));
    }
    return "Error: $filename not found on the local filesystem.";
}

$changelogContent = getFileContent('CHANGELOG.md');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>System Info - hQDB</title>
    <style>
        body { font-family: 'Courier New', Courier, monospace; background: #000000; color: #cccccc; margin: 0; padding: 20px; line-height: 1.6; }
        a { color: #00ff00; text-decoration: none; }
        a:hover { color: #ffffff; background: #005500; }
        .container { max-width: 900px; margin: 0 auto; border: 1px solid #333; padding: 30px; background: #050505; }
        .nav { margin-bottom: 30px; font-weight: bold; border-bottom: 1px dashed #333; padding-bottom: 10px; }
        h1, h2 { color: #ffffff; margin-top: 0; }
        h2 { border-bottom: 1px solid #333; padding-bottom: 5px; margin-top: 0; font-size: 1.2em; }
        
        .info-block { background: #0a0a0a; padding: 15px; border: 1px dashed #333; margin-bottom: 25px; border-left: 2px solid #555; }
        .info-block p { margin-top: 10px; }
        .info-block ul { margin: 10px 0 0 0; padding-left: 20px; color: #ccc; }
        .info-block li { margin-bottom: 5px; }
        .info-block code { background: #000; padding: 2px 5px; border: 1px solid #222; color: #00ff00; }
        
        .markdown-window { background: #000000; padding: 15px; border: 1px solid #333; border-left: 2px solid #00ff00; margin-bottom: 30px; overflow-x: auto; }
        .markdown-window pre { margin: 0; white-space: pre-wrap; font-family: 'Courier New', Courier, monospace; color: #e0e0e0; }
        .window-title { font-size: 0.85em; background: #111; color: #888; padding: 2px 10px; display: inline-block; margin-bottom: -1px; border: 1px solid #333; border-bottom: none; }
    </style>
</head>
<body>

<div class="container">
    <div class="nav">
        [ <a href="index.php">Return to Main Database</a> ]
    </div>

    <h1>System Diagnostics & Info</h1>

    <div class="info-block">
        <h2>What is this?</h2>
        <p><strong>hQDB (hacker Quote Database)</strong> is a lightweight, SQLite-backed archive designed to preserve network history, out-of-context IRC highlights, and terminal output. It serves as a decentralized repository for the community's collective memory.</p>
    </div>

    <div class="info-block">
        <h2>Usage Parameters</h2>
        <p><strong>Web Interface:</strong></p>
        <ul>
            <li>Browse the active database using the top navigation (Latest, Top, Bottom, Random).</li>
            <li>Vote on quotes using the <code>[ + | - ]</code> operators.</li>
            <li>Submit new entries to the moderation buffer via the <a href="submit.php">Submit form</a>.</li>
        </ul>
        <br>
        <p><strong>IRC Integration:</strong></p>
        <ul>
            <li><code>!hqdb</code> - Pulls a random approved quote into the channel.</li>
            <li><code>!hqdb &lt;id&gt;</code> - Fetches a specific quote by its numerical ID.</li>
            <li><code>!hqdb add &lt;text&gt;</code> - Transmits a new quote directly from IRC to the web moderation queue.</li>
        </ul>
    </div>

    <div class="info-block">
        <h2>Contact & Network Admin</h2>
        <p>This web node is hosted on <strong>scuttled.net/hqdb</strong>. The bot daemon runs on <strong>rodent.2600.chat</strong>.</p>
        <ul>
            <li><strong>Email:</strong> r0d3nt@gmail.com</li>
            <li><strong>IRC:</strong> Drop into <code>#help</code> on 2600net and ping an op.</li>
        </ul>
    </div>

    <div class="window-title">cat CHANGELOG.md</div>
    <div class="markdown-window">
        <pre><?= $changelogContent ?></pre>
    </div>

</div>

</body>
</html>
