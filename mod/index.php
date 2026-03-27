<?php
// Version: 1.1.0
// Last Updated: 2026-03-27

// mod/index.php
require_once '../db.php';
require_once '../auth.php';

$auth = new Auth();
$user = $auth->checkSession();

if (!$user) { header("Location: login.php"); exit; }

$db = DB::getInstance();
$message = '';

if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    $auth->logout();
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['quote_id'])) {
    $quoteId = (int)$_POST['quote_id'];
    $action = $_POST['action'];

    if ($action === 'approve') {
        $stmt = $db->prepare("UPDATE quotes SET status = 'approved' WHERE id = :id");
        $stmt->execute([':id' => $quoteId]);
        $message = "Quote #$quoteId moved to active database.";
    } elseif ($action === 'reject') {
        $stmt = $db->prepare("UPDATE quotes SET status = 'rejected' WHERE id = :id");
        $stmt->execute([':id' => $quoteId]);
        $message = "Quote #$quoteId dropped.";
    } elseif ($action === 'edit') {
        $newText = trim($_POST['quote_text'] ?? '');
        if (!empty($newText)) {
            $stmt = $db->prepare("UPDATE quotes SET quote_text = :text WHERE id = :id");
            $stmt->execute([':text' => $newText, ':id' => $quoteId]);
            $message = "Quote #$quoteId modified.";
        }
    }
}

$stmt = $db->prepare("SELECT * FROM quotes WHERE status = 'pending' ORDER BY created_at ASC");
$stmt->execute();
$pendingQuotes = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Mod Queue - hQDB</title>
    <style>
        body { font-family: 'Courier New', Courier, monospace; background: #000000; color: #cccccc; margin: 0; padding: 20px; }
        a { color: #00ff00; text-decoration: none; }
        a:hover { color: #ffffff; background: #005500; }
        .container { max-width: 1000px; margin: 0 auto; background: #050505; padding: 20px; border: 1px solid #333; }
        
        .nav { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 1px dashed #333; font-weight: bold; }
        h1 { color: #ffffff; margin-top: 0; }
        
        .msg { padding: 10px; margin-bottom: 15px; font-weight: bold; background: #002200; color: #00ff00; border: 1px solid #00ff00; }
        
        .quote-block { background: #0a0a0a; border: 1px solid #333; margin-bottom: 20px; padding: 15px; border-left: 2px solid #00ff00; }
        .meta { font-size: 0.85em; color: #888; margin-bottom: 10px; border-bottom: 1px solid #222; padding-bottom: 5px; }
        .meta strong { color: #ccc; }
        
        textarea { width: 100%; height: 120px; background: #000; color: #00ff00; border: 1px solid #333; font-family: monospace; padding: 10px; box-sizing: border-box; margin-bottom: 10px; resize: vertical; }
        textarea:focus { outline: none; border-color: #00ff00; }
        
        .actions { display: flex; gap: 10px; }
        button { padding: 8px 15px; font-weight: bold; cursor: pointer; font-family: monospace; }
        
        .btn-approve { background: #003300; color: #00ff00; border: 1px solid #00ff00; }
        .btn-approve:hover { background: #00ff00; color: #000; }
        
        .btn-reject { background: #330000; color: #ff0000; border: 1px solid #ff0000; }
        .btn-reject:hover { background: #ff0000; color: #000; }
        
        .btn-edit { background: #001133; color: #0088ff; border: 1px solid #0088ff; }
        .btn-edit:hover { background: #0088ff; color: #000; }
    </style>
</head>
<body>

<div class="container">
    <div class="nav">
        <div>
            UID: <span style="color:#00ff00;"><?= htmlspecialchars($user['username']) ?></span> 
            [<?= htmlspecialchars($user['role']) ?>]
        </div>
        <div>
            [ <a href="../index.php" target="_blank">Live Node</a> ] 
            <?php if($user['role'] === 'admin'): ?>[ <a href="users.php">Manage Users</a> ]<?php endif; ?>
            [ <a href="?action=logout">SIGTERM (Logout)</a> ]
        </div>
    </div>

    <h1>Buffer :: Pending Queue (<?= count($pendingQuotes) ?>)</h1>

    <?php if ($message): ?>
        <div class="msg"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <?php if (empty($pendingQuotes)): ?>
        <p>Buffer empty. System idle.</p>
    <?php else: ?>
        <?php foreach ($pendingQuotes as $q): ?>
            <div class="quote-block">
                <div class="meta">
                    ID: <strong>#<?= $q['id'] ?></strong> | 
                    TS: <?= htmlspecialchars($q['created_at']) ?> | 
                    SRC: <?= htmlspecialchars($q['submitted_by_ip']) ?>
                </div>
                
                <form method="POST" action="index.php">
                    <input type="hidden" name="quote_id" value="<?= $q['id'] ?>">
                    <textarea name="quote_text"><?= htmlspecialchars($q['quote_text']) ?></textarea>
                    
                    <div class="actions">
                        <button type="submit" name="action" value="approve" class="btn-approve">APPROVE</button>
                        <button type="submit" name="action" value="reject" class="btn-reject" onclick="return confirm('Confirm DROP operation for quote #<?= $q['id'] ?>?');">REJECT</button>
                        <button type="submit" name="action" value="edit" class="btn-edit">COMMIT EDIT</button>
                    </div>
                </form>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

</body>
</html>
