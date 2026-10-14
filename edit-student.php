<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit;
}

require_once "config/database.php";

$username = $_SESSION["username"] ?? "";
$error = $_GET["error"] ?? "";
$success = $_GET["success"] ?? "";

// Validate Student ID from URL
$student_id = $_GET["id"] ?? "";

if (empty($student_id) || !is_numeric($student_id)) {
    header("Location: students.php");
    exit;
}

$student_id = (int)$student_id;

// Fetch Student details along with Total Paid amount to compute minimum allowable Total Fees
$student_query = "
    SELECT 
        s.id,
        s.first_name,
        s.middle_name,
        s.last_name,
        s.mobile,
        s.joining_date,
        s.course,
        s.total_fees,
        COALESCE(SUM(p.amount), 0) AS total_paid
    FROM students s
    LEFT JOIN payments p ON s.id = p.student_id
    WHERE s.id = ?
    GROUP BY s.id
";

$stmt = $conn->prepare($student_query);
if (!$stmt) {
    header("Location: students.php");
    exit;
}

$stmt->bind_param("i", $student_id);
$stmt->execute();
$student_result = $stmt->get_result();

if ($student_result->num_rows === 0) {
    $stmt->close();
    header("Location: students.php");
    exit;
}

$student = $student_result->fetch_assoc();
$stmt->close();

$total_paid = (float)$student['total_paid'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ALFA COMPUTER — Edit Student</title>
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
            max-width: 800px;
        }

        .page-header-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem;
            gap: 1rem;
        }

        .page-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-main);
        }

        .btn-secondary {
            padding: 0.6rem 1.1rem;
            background: transparent;
            border: 1px solid var(--border);
            color: var(--text-main);
            font-weight: 600;
            border-radius: var(--radius-md);
            cursor: pointer;
            font-size: 0.875rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            transition: var(--transition);
        }

        .btn-secondary:hover {
            background: var(--bg-main);
        }

        .btn-primary {
            padding: 0.65rem 1.25rem;
            background: var(--primary);
            color: #fff;
            font-weight: 600;
            border: none;
            border-radius: var(--radius-md);
            cursor: pointer;
            font-size: 0.875rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            transition: var(--transition);
        }

        .btn-primary:hover {
            opacity: 0.92;
        }

        .alert-danger {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger);
            padding: 0.75rem 1rem;
            border-radius: var(--radius-md);
            font-size: 0.85rem;
            margin-bottom: 1.5rem;
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 1.75rem;
            box-shadow: var(--shadow-sm);
            margin-bottom: 1.5rem;
        }

        .section-header {
            font-size: 1.125rem;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 1.25rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid var(--border);
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.25rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.4rem;
        }

        .form-group.full-width {
            grid-column: span 2;
        }

        .form-label {
            font-size: 0.8125rem;
            font-weight: 600;
            color: var(--text-main);
        }

        .form-control {
            width: 100%;
            padding: 0.625rem 0.875rem;
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            font-size: 0.875rem;
            color: var(--text-main);
            background: var(--surface);
            transition: var(--transition);
            box-sizing: border-box;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }

        .info-note {
            font-size: 0.75rem;
            color: var(--text-muted);
            margin-top: 0.25rem;
        }

        @media (max-width: 640px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            .form-group.full-width {
                grid-column: span 1;
            }
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
            <a href="students.php" class="menu-item active">👥 Students</a>
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

            <?php if (!empty($error)): ?>
                <div class="alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <div class="page-header-row">
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <a href="view-student.php?id=<?php echo $student['id']; ?>" class="btn-secondary">← Back to View Student</a>
                    <h1 class="page-title">Edit Student Details</h1>
                </div>
            </div>

            <!-- EDIT STUDENT FORM CARD -->
            <div class="card">
                <h2 class="section-header">Student Information</h2>
                
                <form action="students/update.php" method="POST">
                    <input type="hidden" name="student_id" value="<?php echo $student['id']; ?>">

                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label" for="first_name">First Name *</label>
                            <input 
                                type="text" 
                                id="first_name" 
                                name="first_name" 
                                class="form-control" 
                                value="<?php echo htmlspecialchars($student['first_name']); ?>" 
                                required
                            >
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="middle_name">Middle Name (Optional)</label>
                            <input 
                                type="text" 
                                id="middle_name" 
                                name="middle_name" 
                                class="form-control" 
                                value="<?php echo htmlspecialchars($student['middle_name'] ?? ''); ?>"
                            >
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="last_name">Last Name *</label>
                            <input 
                                type="text" 
                                id="last_name" 
                                name="last_name" 
                                class="form-control" 
                                value="<?php echo htmlspecialchars($student['last_name']); ?>" 
                                required
                            >
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="mobile">Mobile Number *</label>
                            <input 
                                type="text" 
                                id="mobile" 
                                name="mobile" 
                                class="form-control" 
                                value="<?php echo htmlspecialchars($student['mobile']); ?>" 
                                required
                            >
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="course">Course *</label>
                            <input 
                                type="text" 
                                id="course" 
                                name="course" 
                                class="form-control" 
                                value="<?php echo htmlspecialchars($student['course']); ?>" 
                                required
                            >
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="joining_date">Joining Date *</label>
                            <input 
                                type="date" 
                                id="joining_date" 
                                name="joining_date" 
                                class="form-control" 
                                value="<?php echo htmlspecialchars($student['joining_date']); ?>" 
                                required
                            >
                        </div>

                        <div class="form-group full-width">
                            <label class="form-label" for="total_fees">Total Fees (₹) *</label>
                            <input 
                                type="number" 
                                step="0.01" 
                                min="<?php echo $total_paid; ?>" 
                                id="total_fees" 
                                name="total_fees" 
                                class="form-control" 
                                value="<?php echo htmlspecialchars($student['total_fees']); ?>" 
                                required
                            >
                            <span class="info-note">
                                Current total paid: <strong>₹<?php echo number_format($total_paid, 2); ?></strong>. Total fees cannot be reduced below this amount.
                            </span>
                        </div>

                        <div class="form-group full-width" style="margin-top: 0.5rem; display: flex; gap: 0.75rem;">
                            <button type="submit" class="btn-primary">💾 Update Student</button>
                            <a href="view-student.php?id=<?php echo $student['id']; ?>" class="btn-secondary">Cancel</a>
                        </div>
                    </div>
                </form>
            </div>

        </main>
    </div>

</body>
</html>
<?php
$conn->close();
?>