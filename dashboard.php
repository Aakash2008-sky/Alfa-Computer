<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit;
}

require_once "config/database.php";

$user_id = (int)$_SESSION["user_id"];
$username = $_SESSION["username"] ?? "";

if ($username === "") {
    $stmt = $conn->prepare(
        "SELECT username FROM users WHERE id = ? LIMIT 1"
    );

    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();

        if ($row) {
            $username = $row["username"];
            $_SESSION["username"] = $username;
        }

        $stmt->close();
    }
}

// Total Students
$total_students = 0;

$result = $conn->query(
    "SELECT COUNT(*) AS total FROM students"
);

if ($result && $row = $result->fetch_assoc()) {
    $total_students = (int)$row["total"];
}

// Total Pending Fees After Payments and Discounts
$total_pending_fees = 0.00;

$pending_query = "
    SELECT COALESCE(
        SUM(
            GREATEST(
                s.total_fees
                - COALESCE(p.total_paid, 0)
                - COALESCE(d.total_discount, 0),
                0
            )
        ),
        0
    ) AS total_pending
    FROM students s
    LEFT JOIN (
        SELECT student_id, SUM(amount) AS total_paid
        FROM payments
        GROUP BY student_id
    ) p ON p.student_id = s.id
    LEFT JOIN (
        SELECT student_id, SUM(discount_amount) AS total_discount
        FROM student_discounts
        GROUP BY student_id
    ) d ON d.student_id = s.id
";

$pending_result = $conn->query($pending_query);

if ($pending_result && $row = $pending_result->fetch_assoc()) {
    $total_pending_fees = (float)$row["total_pending"];
}


// Recent Students
$recent_students = [];

$recent_query = "
    SELECT
        s.id,
        s.first_name,
        s.middle_name,
        s.last_name,
        s.course,
        s.total_fees,
        COALESCE(p.total_paid, 0) AS total_paid,
        COALESCE(d.total_discount, 0) AS total_discount,
        GREATEST(
            s.total_fees
            - COALESCE(p.total_paid, 0)
            - COALESCE(d.total_discount, 0),
            0
        ) AS remaining_fees
    FROM students s
    LEFT JOIN (
        SELECT student_id, SUM(amount) AS total_paid
        FROM payments
        GROUP BY student_id
    ) p ON p.student_id = s.id
    LEFT JOIN (
        SELECT student_id, SUM(discount_amount) AS total_discount
        FROM student_discounts
        GROUP BY student_id
    ) d ON d.student_id = s.id
    ORDER BY s.id DESC
";

$recent_result = $conn->query($recent_query);

if ($recent_result) {
    while ($row = $recent_result->fetch_assoc()) {
        $recent_students[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ALFA COMPUTER — Dashboard</title>
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
            top: 0;
            bottom: 0;
            left: 0;
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

        .menu-item:hover,
        .menu-item.active {
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

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 1.5rem;
            box-shadow: var(--shadow-sm);
        }

        .stat-title {
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--text-muted);
            margin-bottom: 0.5rem;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 800;
            color: var(--text-main);
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

        .table-responsive {
            width: 100%;
            overflow-x: auto;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 0.875rem;
        }

        .data-table th,
        .data-table td {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid var(--border);
            white-space: nowrap;
        }

        .data-table th {
            font-weight: 600;
            color: var(--text-muted);
            background: var(--bg-main);
        }

        .data-table td {
            color: var(--text-main);
        }

        .empty-state {
            padding: 2.5rem 1rem;
            text-align: center;
            color: var(--text-muted);
            font-size: 0.875rem;
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
            <a href="dashboard.php" class="menu-item active">📊 Dashboard</a>
            <a href="students.php" class="menu-item">👥 Students</a>
            <a href="add-student.php" class="menu-item">➕ Add Student</a>
            <a href="reports.php" class="menu-item">📄 Reports</a>

            <a href="add-course.php" class="menu-item">📚 Add Course</a>

            <a href="settings.php" class="menu-item">⚙️ Settings</a>
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
            <h1 class="page-title">Dashboard</h1>
            <p class="page-subtitle">Welcome back, <?php echo htmlspecialchars($username); ?>! Here is an overview of your system.</p>

            <!-- STATS CARDS GRID -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-title">Total Students</div>
                    <div class="stat-value"><?php echo $total_students; ?></div>
                </div>

                <div class="stat-card">
                    <div class="stat-title">Total Pending Fees</div>
                    <div class="stat-value">₹<?php echo number_format($total_pending_fees, 2); ?></div>
                </div>
            </div>

            <!-- RECENT STUDENTS TABLE -->
            <div class="card">
                <h2 class="card-header-title">Recent Students</h2>

                <?php if (!empty($recent_students)): ?>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Student Name</th>
                                    <th>Course</th>
                                    <th>Total Fees</th>
                                    <th>Remaining Fees</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_students as $student): ?>
                                    <?php
                                    // Build full name cleanly (First + Middle + Last)
                                    $name_parts = array_filter([
                                        trim($student['first_name'] ?? ''),
                                        trim($student['middle_name'] ?? ''),
                                        trim($student['last_name'] ?? '')
                                    ]);
                                    $full_name = implode(' ', $name_parts);
                                    $remaining = max(0, (float)$student['remaining_fees']);
                                    ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($full_name); ?></strong></td>
                                        <td><?php echo htmlspecialchars($student['course']); ?></td>
                                        <td>₹<?php echo number_format((float)$student['total_fees'], 2); ?></td>
                                        <td>₹<?php echo number_format($remaining, 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        No student records available.
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

</body>

</html>