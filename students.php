
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

$query = "
    SELECT
        s.id,
        s.first_name,
        s.middle_name,
        s.last_name,
        s.mobile,
        s.joining_date,
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

$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ALFA COMPUTER — Students List</title>
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

        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success);
            padding: 0.75rem 1rem;
            border-radius: var(--radius-md);
            font-size: 0.85rem;
            margin-bottom: 1.5rem;
            border: 1px solid rgba(16, 185, 129, 0.2);
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
            padding: 1.5rem;
            box-shadow: var(--shadow-sm);
        }

        .table-container {
            width: 100%;
            overflow-x: auto;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 0.875rem;
        }

        .data-table th {
            padding: 0.75rem 1rem;
            background: var(--bg-main);
            color: var(--text-muted);
            font-weight: 600;
            border-bottom: 1px solid var(--border);
            white-space: nowrap;
        }

        .data-table td {
            padding: 0.875rem 1rem;
            border-bottom: 1px solid var(--border);
            color: var(--text-main);
            white-space: nowrap;
        }

        .data-table tr:last-child td {
            border-bottom: none;
        }

        .actions-cell {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-action {
            padding: 0.35rem 0.65rem;
            font-size: 0.75rem;
            font-weight: 600;
            border-radius: var(--radius-sm);
            text-decoration: none;
            cursor: pointer;
            border: 1px solid transparent;
            transition: var(--transition);
        }

        .btn-view {
            background: rgba(79, 70, 229, 0.08);
            color: var(--primary);
            border-color: rgba(79, 70, 229, 0.2);
        }

        .btn-view:hover {
            background: var(--primary);
            color: #fff;
        }

        .btn-edit {
            background: rgba(245, 158, 11, 0.08);
            color: #d97706;
            border-color: rgba(245, 158, 11, 0.2);
        }

        .btn-edit:hover {
            background: #d97706;
            color: #fff;
        }

        .btn-remove {
            background: rgba(239, 68, 68, 0.08);
            color: var(--danger);
            border-color: rgba(239, 68, 68, 0.2);
        }

        .btn-remove:hover {
            background: var(--danger);
            color: #fff;
        }

        /* MODAL STYLES */
        .modal-overlay {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 100;
            opacity: 0;
            visibility: hidden;
            transition: var(--transition);
        }

        .modal-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        .modal-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            width: 100%;
            max-width: 420px;
            padding: 1.5rem;
            box-shadow: var(--shadow-md);
            transform: scale(0.95);
            transition: var(--transition);
        }

        .modal-overlay.active .modal-card {
            transform: scale(1);
        }

        .modal-title {
            font-size: 1.125rem;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 0.5rem;
        }

        .modal-body {
            font-size: 0.875rem;
            color: var(--text-muted);
            margin-bottom: 1.5rem;
            line-height: 1.4;
        }

        .modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 0.75rem;
        }

        .btn-cancel {
            padding: 0.65rem 1.25rem;
            background: transparent;
            border: 1px solid var(--border);
            color: var(--text-main);
            font-weight: 600;
            border-radius: var(--radius-md);
            cursor: pointer;
            font-size: 0.875rem;
        }

        .btn-danger-confirm {
            padding: 0.65rem 1.25rem;
            background: var(--danger);
            color: #fff;
            font-weight: 600;
            border: none;
            border-radius: var(--radius-md);
            cursor: pointer;
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

            <?php if (!empty($success)): ?>
                <div class="alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <div class="page-header-row">
                <h1 class="page-title">Students List</h1>
                <a href="add-student.php" class="btn-primary">➕ Add New Student</a>
            </div>

            <div class="card">
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Joining Date</th>
                                <th>Student Name</th>
                                <th>Mobile</th>
                                <th>Course</th>
                                <th>Total Fees</th>
                                <th>Paid Fees</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result && $result->num_rows > 0): ?>
                                <?php while ($student = $result->fetch_assoc()): ?>
                                    <?php 
                                        $full_name = trim($student['first_name'] . ' ' . $student['middle_name'] . ' ' . $student['last_name']);
                                    ?>
                                    <tr>
                                        <td>
    <?php
    if (!empty($student['joining_date'])) {
        echo date(
            'd/m/y',
            strtotime($student['joining_date'])
        );
    } else {
        echo 'N/A';
    }
    ?>
</td>
                                        <td><strong><?php echo htmlspecialchars($full_name); ?></strong></td>
                                        <td><?php echo htmlspecialchars($student['mobile']); ?></td>
                                        <td><?php echo htmlspecialchars($student['course']); ?></td>
                                        <td>₹<?php echo number_format($student['total_fees'], 2); ?></td>
                                        <td>₹<?php echo number_format($student['total_paid'], 2); ?></td>
                                        <td>
                                            <div class="actions-cell">
                                                <a href="view-student.php?id=<?php echo $student['id']; ?>" class="btn-action btn-view">View</a>
                                                <a href="edit-student.php?id=<?php echo $student['id']; ?>" class="btn-action btn-edit">Edit</a>
                                                <button 
                                                    type="button" 
                                                    class="btn-action btn-remove"
                                                    onclick="openDeleteModal(<?php echo $student['id']; ?>, '<?php echo htmlspecialchars(addslashes($full_name)); ?>')"
                                                >
                                                    Remove
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 2rem;">
                                        No students found.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>

    <!-- REMOVE CONFIRMATION MODAL -->
    <div class="modal-overlay" id="deleteModal">
        <div class="modal-card">
            <h3 class="modal-title">Remove Student</h3>
            <p class="modal-body">
                Are you sure you want to remove <strong id="deleteStudentName">this student</strong>?<br>
                This action cannot be undone.
            </p>
            <form id="deleteForm" method="POST" action="students/delete.php">
                <input type="hidden" name="student_id" id="deleteStudentId" value="">
                <div class="modal-actions">
                    <button type="button" class="btn-cancel" onclick="closeDeleteModal()">Cancel</button>
                    <button type="submit" class="btn-danger-confirm">Remove Student</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openDeleteModal(studentId, studentName) {
            document.getElementById('deleteStudentId').value = studentId;
            document.getElementById('deleteStudentName').innerText = studentName;
            document.getElementById('deleteModal').classList.add('active');
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.remove('active');
        }
    </script>
</body>
</html>
<?php
$conn->close();
?>