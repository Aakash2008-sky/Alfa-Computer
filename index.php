<?php
session_start();

// If already logged in, redirect straight to dashboard
if (isset($_SESSION["user_id"])) {
    header("Location: dashboard.php");
    exit;
}

$error = $_GET["error"] ?? "";
$success = $_GET["success"] ?? "";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ALFA COMPUTER — Login</title>
    <link rel="icon" type="image/x-icon" href="assets/images/alfa_logo.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body { background-color: var(--bg-main); display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
        .auth-card { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 2.5rem; width: 100%; max-width: 400px; box-shadow: var(--shadow-md); }
        .brand { text-align: center; font-weight: 800; font-size: 1.5rem; color: var(--text-main); margin-bottom: 1.5rem; }
        .brand span { display: block; font-size: 0.6rem; letter-spacing: 0.25em; color: var(--primary); }
        .title { font-size: 1.25rem; font-weight: 700; text-align: center; margin-bottom: 1.5rem; }
        .form-group { margin-bottom: 1.25rem; }
        .form-group label { display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.5rem; }
        .form-control { width: 100%; padding: 0.75rem 1rem; border: 1px solid var(--border); border-radius: var(--radius-md); font-size: 0.875rem; outline: none; background: var(--bg-main); }
        .form-control:focus { border-color: var(--primary); }
        .btn-primary { width: 100%; padding: 0.75rem; background: var(--primary); color: #fff; font-weight: 600; border: none; border-radius: var(--radius-md); cursor: pointer; font-size: 0.875rem; margin-top: 40px; }
        .btn-primary:hover { opacity: 0.95; }
        .alert-error { background: rgba(239, 68, 68, 0.1); color: var(--danger); padding: 0.75rem; border-radius: var(--radius-md); font-size: 0.85rem; margin-bottom: 1rem; text-align: center; border: 1px solid rgba(239, 68, 68, 0.2); }
        .alert-success { background: rgba(16, 185, 129, 0.1); color: #10B981; padding: 0.75rem; border-radius: var(--radius-md); font-size: 0.85rem; margin-bottom: 1rem; text-align: center; border: 1px solid rgba(16, 185, 129, 0.2); }
        .forgot-pass { text-align: right; margin-top: 0.5rem; margin-bottom: 1.25rem; }
        .forgot-pass a { font-size: 0.8rem; color: var(--primary); text-decoration: none; font-weight: 500; }
    </style>
</head>
<body>
    <div class="auth-card">
        <div class="brand">ALFA <span>COMPUTER</span></div>
        <h1 class="title">Sign In</h1>

        <?php if (!empty($error)): ?>
            <div class="alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <form action="auth/login.php" method="POST">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" class="form-control" required autofocus autocomplete="off">
            </div>

            <div class="form-group" style="margin-bottom: 0;">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control" required>
            </div>

            <button type="submit" class="btn-primary">Login</button>
        </form>
    </div>
</body>
</html>