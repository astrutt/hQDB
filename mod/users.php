<?php
// Version: 1.1.0
// Last Updated: 2026-03-27

// mod/users.php
require_once '../db.php';
require_once '../auth.php';

$auth = new Auth();
$currentUser = $auth->checkSession();

if (!$currentUser) { header("Location: login.php"); exit; }

if ($currentUser['role'] !== 'admin') {
    die("<div style='background:#000; color:#ff0000; font-family:monospace; padding:50px; text-align:center;'><h2>FATAL ERROR: EPERM</h2><p>Operation not permitted. Root/Admin privileges required.</p></div>");
}

$db = DB::getInstance();
$message = '';
$messageType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    try {
        if ($action === 'add_user') {
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            $role = $_POST['role'] === 'admin' ? 'admin' : 'mod';

            if (empty($username) || empty($password)) throw new Exception("Params cannot be null.");

            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO moderators (username, password_hash, role) VALUES (:username, :hash, :role)");
            $stmt->execute([':username' => $username, ':hash' => $hash, ':role' => $role]);
            
            $message = "User record created: $username";
        } elseif ($action === 'change_password') {
            $userId = (int)$_POST['user_id'];
            $newPassword = $_POST['new_password'] ?? '';
            if (empty($newPassword)) throw new Exception("Password cannot be null.");

            $hash = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE moderators SET password_hash = :hash WHERE id = :id");
            $stmt->execute([':hash' => $hash, ':id' => $userId]);
            
            $stmt = $db->prepare("DELETE FROM sessions WHERE moderator_id = :id");
            $stmt->execute([':id' => $userId]);

            $message = "Key updated. Existing session tokens invalidated.";
        } elseif ($action === 'delete_user') {
            $userId = (int)$_POST['user_id'];
            if ($userId === (int)$currentUser['id']) throw new Exception("Self-termination blocked.");

            $stmt = $db->prepare("DELETE FROM moderators WHERE id = :id");
            $stmt->execute([':id' => $userId]);
            $message = "User record purged.";
        }
    } catch (PDOException $e) {
        $message = ($e->getCode() == 23000) ? "Collision: Username exists." : "DB Fault: " . $e->getMessage();
        $messageType = 'error';
    } catch (Exception $e) {
        $message = $e->getMessage();
        $messageType = 'error';
    }
}

$stmt = $db->query("SELECT id, username, role, created_at FROM moderators ORDER BY role ASC, username ASC");
$users = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User DB - hQDB</title>
    <style>
        body { font-family: 'Courier New', Courier, monospace; background: #000000; color: #cccccc; margin: 0; padding: 20px; }
        a { color: #00ff00; text-decoration: none; }
        a:hover { color: #ffffff; background: #005500; }
        .container { max-width: 1000px; margin: 0 auto; background: #050505; padding: 20px; border: 1px solid #333; }
        
        .nav { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 1px dashed #333; font-weight: bold; }
        h1 { color: #ffffff; margin-top: 0; }
        
        .msg { padding: 10px; margin-bottom: 15px; font-weight: bold; }
        .msg.success { background: #002200; color: #00ff00; border: 1px solid #00ff00; }
        .msg.error { background: #220000; color: #ff0000; border: 1px solid #ff0000; }
        
        table { width: 100%; border-collapse: collapse; margin-bottom: 30px; font-size: 0.9em; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px dashed #333; }
        th { color: #888; text-transform: uppercase; }
        
        .admin-forms { display: flex; gap: 20px; flex-wrap: wrap; }
        .form-box { background: #0a0a0a; padding: 15px; border: 1px solid #333; flex: 1; min-width: 300px; }
        .form-box h3 { margin-top: 0; color: #ccc; border-bottom: 1px solid #222; padding-bottom: 5px; }
        
        .form-group { margin-bottom: 10px; }
        .form-group label { display: block; margin-bottom: 5px; font-size: 0.9em; color: #888; }
        .form-group input, .form-group select { width: 100%; padding: 8px; box-sizing: border-box; background: #000; border: 1px solid #333; color: #00ff00; font-family: monospace; }
        .form-group input:focus, .form-group select:focus { outline: none; border-color: #00ff00; }
        
        button { padding: 8px 15px; font-weight: bold; cursor: pointer; font-family: monospace; }
        .btn-primary { background: #003300; color: #00ff00; border: 1px solid #00ff00; width: 100%; margin-top: 10px; }
        .btn-primary:hover { background: #00ff00; color: #000; }
        
        .btn-danger { background: #330000; color: #ff0000; border: 1px solid #ff0000; padding: 4px 8px; font-size: 0.8em; }
        .btn-danger:hover { background: #ff0000; color: #000; }
    </style>
</head>
<body>

<div class="container">
    <div class="nav">
        <div>ROOT: <span style="color:#00ff00;"><?= htmlspecialchars($currentUser['username']) ?></span></div>
        <div>
            [ <a href="index.php">Return to Queue</a> ] | 
            [ <a href="?action=logout">SIGTERM</a> ]
        </div>
    </div>

    <h1>Sysadmin :: User Control</h1>

    <?php if ($message): ?>
        <div class="msg <?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <table>
        <thead>
            <tr>
                <th>UID</th>
                <th>User</th>
                <th>Role</th>
                <th>Init_Time</th>
                <th>Ops</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $u): ?>
                <tr>
                    <td><?= $u['id'] ?></td>
                    <td style="color:#fff;"><strong><?= htmlspecialchars($u['username']) ?></strong></td>
                    <td>
                        <span style="color: <?= $u['role'] === 'admin' ? '#ff0000' : '#00ff00' ?>;">
                            <?= htmlspecialchars(strtoupper($u['role'])) ?>
                        </span>
                    </td>
                    <td><?= htmlspecialchars($u['created_at']) ?></td>
                    <td>
                        <?php if ($u['id'] !== $currentUser['id']): ?>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="delete_user">
                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                <button type="submit" class="btn-danger" onclick="return confirm('Execute rm on user <?= htmlspecialchars($u['username']) ?>?');">PURGE</button>
                            </form>
                        <?php else: ?>
                            <em style="color: #666;">(Active)</em>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="admin-forms">
        <div class="form-box">
            <h3>Append User</h3>
            <form method="POST">
                <input type="hidden" name="action" value="add_user">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" required autocomplete="off">
                </div>
                <div class="form-group">
                    <label>Init Password</label>
                    <input type="password" name="password" required>
                </div>
                <div class="form-group">
                    <label>Privilege Level</label>
                    <select name="role">
                        <option value="mod">MOD (Queue Ops)</option>
                        <option value="admin">ADMIN (Queue + Root Ops)</option>
                    </select>
                </div>
                <button type="submit" class="btn-primary">EXECUTE</button>
            </form>
        </div>

        <div class="form-box">
            <h3>Rotate Keys</h3>
            <form method="POST">
                <input type="hidden" name="action" value="change_password">
                <div class="form-group">
                    <label>Target User</label>
                    <select name="user_id" required>
                        <?php foreach ($users as $u): ?>
                            <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['username']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>New Passphrase</label>
                    <input type="password" name="new_password" required>
                </div>
                <button type="submit" class="btn-primary">OVERWRITE</button>
            </form>
        </div>
    </div>
</div>

</body>
</html>
