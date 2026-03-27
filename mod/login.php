<?php
// Version: 1.1.0
// Last Updated: 2026-03-27

// mod/login.php
require_once '../db.php';
require_once '../auth.php';

$auth = new Auth();
$error = '';

if ($auth->checkSession()) {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($auth->login($username, $password)) {
        header("Location: index.php");
        exit;
    } else {
        $error = "ACCESS DENIED.";
        sleep(1); 
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>hQDB :: Auth</title>
    <style>
        body { font-family: 'Courier New', Courier, monospace; background: #000000; color: #cccccc; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .login-box { background: #050505; padding: 30px; border: 1px solid #333; width: 300px; box-shadow: 0 0 10px #000; }
        .login-box h2 { margin-top: 0; text-align: center; color: #ffffff; border-bottom: 1px dashed #333; padding-bottom: 10px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; color: #888; }
        .form-group input { width: 100%; padding: 8px; box-sizing: border-box; background: #000; border: 1px solid #333; color: #00ff00; font-family: monospace; }
        .form-group input:focus { outline: none; border-color: #00ff00; }
        
        button { width: 100%; padding: 10px; background: #003300; color: #00ff00; border: 1px solid #00ff00; font-weight: bold; cursor: pointer; font-family: monospace; margin-top: 10px; }
        button:hover { background: #00ff00; color: #000000; }
        
        .error { color: #ff0000; background: #220000; padding: 10px; margin-bottom: 15px; border: 1px solid #ff0000; font-weight: bold; text-align: center; }
    </style>
</head>
<body>

<div class="login-box">
    <h2>[ ADMIN LOGIN ]</h2>
    <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="POST" action="login.php">
        <div class="form-group">
            <label for="username">USER:</label>
            <input type="text" id="username" name="username" required autofocus autocomplete="off">
        </div>
        <div class="form-group">
            <label for="password">PASS:</label>
            <input type="password" id="password" name="password" required>
        </div>
        <button type="submit">AUTHENTICATE</button>
    </form>
</div>

</body>
</html>
