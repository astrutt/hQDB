<?php
// index.php
// Version: 1.1.6
// Last Updated: 2026-03-27

require_once 'db.php';
require_once 'auth.php'; // Properly load the existing Auth class

$db = DB::getInstance();
$ipAddress = $_SERVER['REMOTE_ADDR'];

// --- 1. Moderator Authentication Check ---
$auth = new Auth();
$isMod = $auth->checkSession();

// --- 2. Handle Voting Logic ---
if (isset($_GET['vote']) && isset($_GET['id'])) {
    $quoteId = (int)$_GET['id'];
    $voteType = $_GET['vote'] === 'up' ? 'upvote' : 'downvote';
    $scoreChange = $voteType === 'upvote' ? 1 : -1;

    $stmt = $db->prepare("SELECT COUNT(*) FROM ip_tracking WHERE ip_address = :ip AND target_id = :id AND action IN ('upvote', 'downvote')");
    $stmt->execute([':ip' => $ipAddress, ':id' => $quoteId]);
    
    if (!$stmt->fetchColumn()) {
        $db->beginTransaction();
        try {
            $stmt = $db->prepare("INSERT INTO ip_tracking (ip_address, action, target_id) VALUES (:ip, :action, :id)");
            $stmt->execute([':ip' => $ipAddress, ':action' => $voteType, ':id' => $quoteId]);

            $stmt = $db->prepare("UPDATE quotes SET score = score + :change WHERE id = :id AND status = 'approved'");
            $stmt->execute([':change' => $scoreChange, ':id' => $quoteId]);
            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
        }
    }
    header("Location: " . ($_SERVER['HTTP_REFERER'] ?? '?v=latest'));
    exit;
}

// --- 3. Handle Flagging (MODERATORS ONLY) ---
// Bypasses the 3-flag threshold since the request comes from an authenticated mod
if ($isMod && isset($_GET['flag']) && isset($_GET['id'])) {
    $quoteId = (int)$_GET['id'];
    
    $stmt = $db->prepare("UPDATE quotes SET status = 'pending' WHERE id = :id AND status = 'approved'");
    $stmt->execute([':id' => $quoteId]);
    
    header("Location: " . ($_SERVER['HTTP_REFERER'] ?? '?v=latest'));
    exit;
}

// --- 4. Fetch Statistics ---
$statsStmt = $db->query("
    SELECT 
        (SELECT COUNT(*) FROM quotes WHERE status = 'approved') as approved_count,
        (SELECT COUNT(*) FROM quotes WHERE status = 'pending') as pending_count
");
$stats = $statsStmt->fetch();

// --- 5. Handle Routing & Pagination ---
$view = $_GET['v'] ?? 'latest';
$page = max(1, (int)($_GET['p'] ?? 1));
$perPage = 50;
$offset = ($page - 1) * $perPage;

$query = "SELECT id, quote_text, score, created_at FROM quotes WHERE status = 'approved' ";
$countQuery = "SELECT COUNT(*) FROM quotes WHERE status = 'approved' ";
$orderBy = "ORDER BY created_at DESC";

$qs = "?v=" . urlencode($view);

switch ($view) {
    case 'top': $orderBy = "ORDER BY score DESC, created_at DESC"; break;
    case 'bottom': $orderBy = "ORDER BY score ASC, created_at DESC"; break;
    case 'random': $orderBy = "ORDER BY RANDOM()"; $perPage = 15; break;
    case 'view': 
        $id = (int)($_GET['id'] ?? 0);
        $query .= "AND id = :id ";
        break;
    case 'search':
        $search = $_GET['q'] ?? '';
        $query .= "AND quote_text LIKE :search ";
        $countQuery .= "AND quote_text LIKE :search ";
        $qs .= "&q=" . urlencode($search); 
        break;
    case 'latest':
    default: $orderBy = "ORDER BY id DESC"; break;
}

if ($view === 'search') {
    $countStmt = $db->prepare($countQuery);
    $countStmt->bindValue(':search', '%' . $search . '%');
    $countStmt->execute();
    $totalQuotes = $countStmt->fetchColumn();
} elseif ($view === 'view' || $view === 'random') {
    $totalQuotes = 0; 
} else {
    $totalQuotes = $db->query($countQuery)->fetchColumn();
}

$totalPages = max(1, ceil($totalQuotes / $perPage));

$stmt = $db->prepare($query . $orderBy . " LIMIT :limit OFFSET :offset");

if ($view === 'search') { 
    $stmt->bindValue(':search', '%' . $search . '%'); 
} elseif ($view === 'view') {
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
}

$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$quotes = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>hQDB - hacker Quote Database</title>
    <style>
        body { font-family: 'Courier New', Courier, monospace; background: #000000; color: #cccccc; margin: 0; padding: 20px; }
        a { color: #00ff00; text-decoration: none; }
        a:hover { color: #ffffff; background: #005500; }
        .container { max-width: 900px; margin: 0 auto; border: 1px solid #333; padding: 20px; background: #050505; }
        h1 { color: #ffffff; text-align: center; border-bottom: 2px solid #333; padding-bottom: 10px; margin-top: 0; }
        
        .nav { text-align: center; margin-bottom: 20px; font-weight: bold; }
        .nav a { margin: 0 10px; }
        .search-box { margin-top: 15px; text-align: center; }
        .search-box input { background: #000; border: 1px solid #00ff00; color: #00ff00; font-family: monospace; padding: 5px; }
        .search-box button { background: #003300; border: 1px solid #00ff00; color: #00ff00; cursor: pointer; padding: 5px 10px; font-family: monospace; }
        .search-box button:hover { background: #00ff00; color: #000; }

        .stats-bar { font-size: 0.85em; text-align: center; color: #888; margin-bottom: 25px; padding-bottom: 15px; border-bottom: 1px dashed #333; }
        .stats-bar span { color: #00ff00; }

        .quote-block { margin-bottom: 30px; border-left: 2px solid #333; }
        .quote-header { background: #111; padding: 5px 10px; font-size: 0.9em; display: flex; gap: 15px; border-top: 1px solid #222; border-right: 1px solid #222; }
        .quote-body { background: #000; padding: 10px; border-bottom: 1px solid #222; border-right: 1px solid #222; overflow-x: auto; }
        .quote-body pre { margin: 0; white-space: pre-wrap; color: #e0e0e0; }
        .score { font-weight: bold; color: #ffffff; }

        .pagination { margin-top: 30px; text-align: center; font-weight: bold; }
        .pagination a { margin: 0 3px; padding: 3px 7px; border: 1px solid #333; }
        .pagination a:hover { background: #00ff00; color: #000; border-color: #00ff00; }
        .pagination .current-page { margin: 0 3px; padding: 3px 7px; color: #ffffff; background: #003300; border: 1px solid #00ff00; }
        .pagination .dots { color: #666; margin: 0 5px; }
        
        .footer { margin-top: 40px; border-top: 1px dashed #333; padding-top: 15px; text-align: center; font-size: 0.85em; color: #666; }
        .footer a { color: #888; }
        .footer a:hover { color: #00ff00; background: none; }
        
        /* Mod specific styling */
        .mod-flag { color: #ff5555; }
        .mod-flag:hover { background: #ff0000; color: #ffffff; }
    </style>
</head>
<body>

<div class="container">
    <h1>hQDB :: hacker Quote Database</h1>
    
    <div class="nav">
        [ <a href="?v=latest">Latest</a> ]
        [ <a href="?v=top">Top</a> ]
        [ <a href="?v=bottom">Bottom</a> ]
        [ <a href="?v=random">Random</a> ]
        [ <a href="submit.php">Submit</a> ]
    </div>

    <div class="search-box">
        <form action="index.php" method="GET">
            <input type="hidden" name="v" value="search">
            <input type="text" name="q" placeholder="grep database..." value="<?= htmlspecialchars($_GET['q'] ?? '') ?>" required>
            <button type="submit">Search</button>
        </form>
    </div>

    <div class="stats-bar">
        Network Stats: <span><?= $stats['approved_count'] ?></span> Approved Quotes | 
        <span><?= $stats['pending_count'] ?></span> in Moderation Queue
    </div>

    <?php if (empty($quotes)): ?>
        <p style="text-align: center;">EOF. No quotes found.</p>
    <?php else: ?>
        <?php foreach ($quotes as $q): ?>
            <div class="quote-block">
                <div class="quote-header">
                    <a href="?v=view&id=<?= $q['id'] ?>">#<?= $q['id'] ?></a>
                    <span>[ <a href="?vote=up&id=<?= $q['id'] ?>">+</a> | <a href="?vote=down&id=<?= $q['id'] ?>">-</a> 
                    <?php if ($isMod): ?>
                        | <a href="?flag=1&id=<?= $q['id'] ?>" class="mod-flag" title="Mod Action: Send back to queue" onclick="return confirm('MOD: Execute DROP? Send quote #<?= $q['id'] ?> back to moderation queue?');">x</a> 
                    <?php endif; ?>
                    ]</span>
                    <span class="score"><?= $q['score'] ?></span>
                </div>
                <div class="quote-body"><pre><?= htmlspecialchars($q['quote_text']) ?></pre></div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <?php if ($view !== 'random' && $view !== 'search' && $view !== 'view' || ($view === 'search' && $totalPages > 1)): ?>
        <div class="pagination">
            <?php 
            $range = 3; 
            $start = max(1, $page - $range);
            $end = min($totalPages, $page + $range);
            ?>

            <?php if ($page > 1): ?>
                <a href="<?= $qs ?>&p=<?= $page - 1 ?>">&laquo; Prev</a>
            <?php endif; ?>

            <?php if ($start > 1): ?>
                <a href="<?= $qs ?>&p=1">1</a>
                <?php if ($start > 2): ?><span class="dots">...</span><?php endif; ?>
            <?php endif; ?>

            <?php for ($i = $start; $i <= $end; $i++): ?>
                <?php if ($i == $page): ?>
                    <span class="current-page"><?= $i ?></span>
                <?php else: ?>
                    <a href="<?= $qs ?>&p=<?= $i ?>"><?= $i ?></a>
                <?php endif; ?>
            <?php endfor; ?>

            <?php if ($end < $totalPages): ?>
                <?php if ($end < $totalPages - 1): ?><span class="dots">...</span><?php endif; ?>
                <a href="<?= $qs ?>&p=<?= $totalPages ?>"><?= $totalPages ?></a>
            <?php endif; ?>

            <?php if ($page < $totalPages): ?>
                <a href="<?= $qs ?>&p=<?= $page + 1 ?>">Next &raquo;</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="footer">
        scuttled.net — part of the <a href="https://scuttled.net">2600net</a> IRC network | 
        <a href="about.php">About</a> | 
        <a href="mod/index.php">Moderation</a>
    </div>
</div>

</body>
</html>
