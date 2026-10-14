<?php
session_start();

// 1. Session Protection
if (!isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit;
}

require_once "config/database.php";

$user_id = (int)$_SESSION["user_id"];

// 2. Fetch logged-in username
$username = $_SESSION["username"] ?? "";

if (empty($username)) {
    $user_stmt = $conn->prepare("SELECT username FROM users WHERE id = ? LIMIT 1");
    if ($user_stmt) {
        $user_stmt->bind_param("i", $user_id);
        $user_stmt->execute();
        $user_res = $user_stmt->get_result();
        if ($row = $user_res->fetch_assoc()) {
            $username = $row["username"];
            $_SESSION["username"] = $username;
        }
        $user_stmt->close();
    }
}

// Flash Messages
$successMsg = $_GET['success'] ?? '';
$errorMsg   = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ALFA COMPUTER — Settings</title>
    <link rel="icon" type="image/x-icon" href="assets/images/alfa_logo.png">
    <!-- Inter Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">

    <style>
        :root {
            --sidebar-width: 210px;
        }

        .sidebar {
            width: var(--sidebar-width);
            background: var(--surface);
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0; bottom: 0; left: 0;
            z-index: 50;
            transition: var(--transition);
        }

        .sidebar-header {
            padding: 1rem 0.875rem;
            border-bottom: 1px solid var(--border);
        }

        .brand-logo-text {
            font-weight: 800;
            font-size: 1.25rem;
            letter-spacing: -0.04em;
            color: var(--text-main);
            display: inline-flex;
            flex-direction: column;
            line-height: 1;
        }

        .brand-logo-text .sub-brand {
            font-size: 0.55rem;
            font-weight: 600;
            letter-spacing: 0.22em;
            color: var(--primary);
            margin-top: 3px;
        }

        .sidebar-menu {
            padding: 0.75rem 0.5rem;
            display: flex;
            flex-direction: column;
            gap: 0.2rem;
            flex: 1;
        }

        .menu-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.6rem 0.75rem;
            color: var(--text-muted);
            text-decoration: none;
            font-weight: 500;
            font-size: 0.8125rem;
            border-radius: var(--radius-md);
            transition: var(--transition);
            white-space: nowrap;
        }

        .menu-item:hover, .menu-item.active {
            color: var(--primary);
            background: rgba(79, 70, 229, 0.08);
        }

        .sidebar-footer {
            padding: 0.75rem 0.5rem;
            border-top: 1px solid var(--border);
        }

        .btn-logout {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            width: 100%;
            padding: 0.6rem 0.75rem;
            background: transparent;
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            color: var(--text-muted);
            font-weight: 600;
            font-size: 0.8125rem;
            text-decoration: none;
            justify-content: center;
            transition: var(--transition);
        }

        .btn-logout:hover {
            background: rgba(239, 68, 68, 0.08);
            color: var(--danger);
            border-color: rgba(239, 68, 68, 0.2);
        }

        .main-wrapper {
            margin-left: var(--sidebar-width);
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .navbar {
            height: 70px;
            background: var(--surface);
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 2rem;
            position: sticky;
            top: 0;
            z-index: 40;
        }

        .user-name {
            font-weight: 600;
            font-size: 0.875rem;
            color: var(--text-main);
        }

        .content {
            padding: 2rem;
            flex: 1;
        }

        .page-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 0.25rem;
        }

        .page-subtitle {
            font-size: 0.875rem;
            color: var(--text-muted);
            margin-bottom: 2rem;
        }

        /* Alert Box Styles */
        .alert {
            padding: 0.875rem 1rem;
            border-radius: var(--radius-md);
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 1.5rem;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            color: #065f46;
            border: 1px solid rgba(16, 185, 129, 0.2);
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            color: #991b1b;
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        /* Settings Grid & Cards */
        .settings-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 1.5rem;
        }

        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 1.5rem;
            box-shadow: var(--shadow-sm);
        }

        .card-header-title {
            font-size: 1.125rem;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 1.25rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid var(--border);
        }

        /* Form Controls */
        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-label {
            display: block;
            font-size: 0.8125rem;
            font-weight: 600;
            color: var(--text-muted);
            margin-bottom: 0.5rem;
        }

        .form-control {
            width: 100%;
            padding: 0.625rem 0.875rem;
            font-size: 0.875rem;
            font-family: inherit;
            color: var(--text-main);
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            transition: var(--transition);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }

        .form-control[readonly] {
            background: var(--bg-main);
            color: var(--text-muted);
            cursor: not-allowed;
        }

        .btn-primary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.625rem 1.25rem;
            font-size: 0.875rem;
            font-weight: 600;
            font-family: inherit;
            color: #ffffff;
            background: var(--primary);
            border: none;
            border-radius: var(--radius-md);
            cursor: pointer;
            transition: var(--transition);
        }

        .btn-primary:hover {
            opacity: 0.9;
        }
    </style>
</head>
<body>

    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="brand-logo-text">
                ALFA
                <span class="sub-brand">COMPUTER</span>
            </div>
        </div>

        <nav class="sidebar-menu">
            <a href="dashboard.php" class="menu-item">📊 Dashboard</a>
            <a href="students.php" class="menu-item">👥 Students</a>
            <a href="add-student.php" class="menu-item">➕ Add Student</a>
            <a href="reports.php" class="menu-item">📄 Reports</a>
            <a href="add-course.php" class="menu-item">📚 Add Course</a>
            <a href="settings.php" class="menu-item active">⚙️ Settings</a>
        </nav>

        <div class="sidebar-footer">
            <a href="auth/logout.php" class="btn-logout">🚪 Logout</a>
        </div>
    </aside>

    <!-- MAIN WRAPPER -->
    <div class="main-wrapper">
        <header class="navbar">
            <div></div>
            <div class="user-profile">
                <span class="user-name">Welcome, <?php echo htmlspecialchars($username); ?></span>
            </div>
        </header>

        <main class="content">
            <h1 class="page-title">Settings</h1>
            <p class="page-subtitle">Manage your admin profile and security credentials.</p>

            <!-- ALERT MESSAGES -->
            <?php if (!empty($successMsg)): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($successMsg); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($errorMsg)): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($errorMsg); ?>
                </div>
            <?php endif; ?>

            <!-- SETTINGS GRID -->
            <div class="settings-grid">

                <!-- 1. ADMIN PROFILE CARD -->
                <div class="card">
                    <h2 class="card-header-title">Admin Profile</h2>

                    <form action="settings/update.php" method="POST">
                        <input type="hidden" name="action" value="username">

                        <div class="form-group">
                            <label class="form-label">Current Username</label>
                            <input 
                                type="text" 
                                class="form-control" 
                                value="<?php echo htmlspecialchars($username); ?>" 
                                readonly
                            >
                        </div>

                        <div class="form-group">
                            <label for="new_username" class="form-label">New Username</label>
                            <input 
                                type="text" 
                                id="new_username" 
                                name="new_username" 
                                class="form-control" 
                                placeholder="Enter new username"
                                required
                            >
                        </div>

                        <button type="submit" class="btn-primary">Update Username</button>
                    </form>
                </div>

                <!-- 2. SECURITY CARD -->
                <div class="card">
                    <h2 class="card-header-title">Security</h2>

                    <form action="settings/update.php" method="POST">
                        <input type="hidden" name="action" value="password">

                        <div class="form-group">
                            <label for="current_password" class="form-label">Current Password</label>
                            <input 
                                type="password" 
                                id="current_password" 
                                name="current_password" 
                                class="form-control" 
                                placeholder="Enter current password"
                                required
                            >
                        </div>

                        <div class="form-group">
                            <label for="new_password" class="form-label">New Password</label>
                            <input 
                                type="password" 
                                id="new_password" 
                                name="new_password" 
                                class="form-control" 
                                placeholder="Min. 6 characters"
                                required
                            >
                        </div>

                        <div class="form-group">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <input 
                                type="password" 
                                id="confirm_password" 
                                name="confirm_password" 
                                class="form-control" 
                                placeholder="Re-enter new password"
                                required
                            >
                        </div>

                        <button type="submit" class="btn-primary">Update Password</button>
                    </form>
                </div>

            </div>
        </main>
    </div>

</body>
</html>