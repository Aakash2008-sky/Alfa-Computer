<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit;
}

require_once "config/database.php";


$courses = [];

$course_result = $conn->query(
    "SELECT course_name FROM courses ORDER BY course_name ASC"
);

if ($course_result) {
    while ($course_row = $course_result->fetch_assoc()) {
        $courses[] = $course_row["course_name"];
    }
}


$username = $_SESSION["username"] ?? "";
$error = $_GET["error"] ?? "";
$success = $_GET["success"] ?? "";
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ALFA COMPUTER — Add Student</title>
    <link rel="icon" type="image/x-icon" href="assets/images/alfa_logo.png">
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

        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 1.75rem;
            box-shadow: var(--shadow-sm);
            max-width: 800px;
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

        .form-grid.three-col {
            grid-template-columns: repeat(3, 1fr);
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.375rem;
        }

        .form-group.full-width {
            grid-column: span 2;
        }

        .form-group label {
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
            outline: none;
            background: var(--bg-main);
            color: var(--text-main);
            transition: var(--transition);
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }

        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 2rem;
            padding-top: 1.25rem;
            border-top: 1px solid var(--border);
        }

        .btn-primary {
            padding: 0.625rem 1.25rem;
            background: var(--primary);
            color: #fff;
            font-weight: 600;
            border: none;
            border-radius: var(--radius-md);
            cursor: pointer;
            font-size: 0.875rem;
            transition: var(--transition);
        }

        .btn-primary:hover {
            opacity: 0.92;
        }

        .btn-secondary {
            padding: 0.625rem 1.25rem;
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
            transition: var(--transition);
        }

        .btn-secondary:hover {
            background: var(--bg-main);
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger);
            padding: 0.75rem 1rem;
            border-radius: var(--radius-md);
            font-size: 0.85rem;
            margin-bottom: 1.5rem;
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            color: #10B981;
            padding: 0.75rem 1rem;
            border-radius: var(--radius-md);
            font-size: 0.85rem;
            margin-bottom: 1.5rem;
            border: 1px solid rgba(16, 185, 129, 0.2);
        }

        .discount-toggle-card {
            margin-top: 1.25rem;
            padding: 1rem;
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            background: var(--bg-main);
        }

        .discount-toggle-label {
            display: flex;
            align-items: center;
            gap: 0.625rem;
            font-weight: 600;
            color: var(--text-main);
            cursor: pointer;
        }

        .discount-toggle-label input {
            width: 17px;
            height: 17px;
            accent-color: var(--primary);
        }

        .discount-fields {
            margin-top: 1.25rem;
            padding-top: 1.25rem;
            border-top: 1px solid var(--border);
        }

        .discount-preview {
            margin-top: 1rem;
            padding: 1rem;
            background: rgba(16, 185, 129, 0.08);
            border: 1px solid rgba(16, 185, 129, 0.2);
            border-radius: var(--radius-md);
        }

        .discount-preview-row {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            margin: 0.35rem 0;
            font-size: 0.875rem;
        }

        .discount-preview-total {
            padding-top: 0.65rem;
            margin-top: 0.65rem;
            border-top: 1px solid var(--border);
            font-weight: 700;
        }

        @media (max-width: 640px) {

            .form-grid,
            .form-grid.three-col {
                grid-template-columns: 1fr;
            }

            .form-group.full-width {
                grid-column: span 1;
            }

            .content {
                padding: 1rem;
            }

            .card {
                padding: 1rem;
            }
        }
    </style>
</head>

<body>

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
            <a href="add-student.php" class="menu-item active">➕ Add Student</a>
            <a href="reports.php" class="menu-item">📄 Reports</a>
            <a href="add-course.php" class="menu-item">📚 Add Course</a>
            <a href="settings.php" class="menu-item">⚙️ Settings</a>
        </nav>

        <div class="sidebar-footer">
            <a href="auth/logout.php" class="btn-logout">🚪 Logout</a>
        </div>
    </aside>

    <div class="main-wrapper">
        <header class="navbar">
            <div></div>
            <div class="user-profile">
                <span class="user-name">
                    Welcome, <?php echo htmlspecialchars($username); ?>
                </span>
            </div>
        </header>

        <main class="content">
            <h1 class="page-title">Add Student</h1>
            <p class="page-subtitle">
                Register a new student into the management system.
            </p>

            <?php if (!empty($error)): ?>
                <div class="alert-error" style="max-width: 800px;">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert-success" style="max-width: 800px;">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <div class="card">
                <form action="students/create.php" method="POST">

                    <h2 class="section-header">Student Details</h2>

                    <div class="form-grid three-col" style="margin-bottom: 1.75rem;">
                        <div class="form-group">
                            <label for="first_name">First Name *</label>
                            <input type="text" id="first_name" name="first_name"
                                class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label for="middle_name">
                                Middle Name
                                <span style="font-weight: 400; color: var(--text-muted);">
                                    (Optional)
                                </span>
                            </label>
                            <input type="text" id="middle_name" name="middle_name"
                                class="form-control">
                        </div>

                        <div class="form-group">
                            <label for="last_name">Last Name *</label>
                            <input type="text" id="last_name" name="last_name"
                                class="form-control" required>
                        </div>
                    </div>

                    <div class="form-grid" style="margin-bottom: 2rem;">
                        <div class="form-group">
                            <label for="mobile">Mobile Number *</label>
                            <input type="tel" id="mobile" name="mobile"
                                class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label for="joining_date">Joining Date *</label>

                            <input
                                type="date"
                                id="joining_date"
                                name="joining_date"
                                class="form-control"
                                value="<?php echo date('Y-m-d'); ?>"
                                max="<?php echo date('Y-m-d'); ?>"
                                required>

                        </div>



                        <div class="form-group">
                            <label for="course">Course <span class="required">*</span></label>

                            <select name="course" id="course" class="form-control" required>
                                <option value="">Select Course</option>

                                <?php foreach ($courses as $course_name): ?>
                                    <option value="<?php echo htmlspecialchars($course_name); ?>">
                                        <?php echo htmlspecialchars($course_name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>


                    </div>

                    <h2 class="section-header">Fees Details</h2>

                    <div class="form-grid three-col">
                        <div class="form-group">
                            <label for="total_fees">Total Fees *</label>
                            <input type="number" min="0.01" step="0.01"
                                id="total_fees" name="total_fees"
                                class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label for="paid_at_joining">Paid at Joining *</label>
                            <input type="number" min="0" step="0.01"
                                id="paid_at_joining" name="paid_at_joining"
                                class="form-control" value="0" required>
                        </div>

                        <div class="form-group">
                            <label for="payment_method">Payment Method</label>
                            <select id="payment_method" name="payment_method"
                                class="form-control">
                                <option value="">Select Method</option>
                                <option value="CASH">CASH</option>
                                <option value="UPI">UPI</option>
                            </select>
                        </div>
                    </div>

                    <div class="discount-toggle-card">
                        <label for="give_discount" class="discount-toggle-label">
                            <input type="checkbox" id="give_discount"
                                name="give_discount" value="1">
                            <span>Give Discount</span>
                        </label>

                        <p style="font-size: 0.8125rem; color: var(--text-muted);
                                  margin: 0.5rem 0 0 1.65rem;">
                            Enable this option to give a discount to the student.
                        </p>

                        <div id="discount_fields" class="discount-fields" hidden>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="discount_type">Discount Type</label>
                                    <select id="discount_type" name="discount_type"
                                        class="form-control" disabled>
                                        <option value="amount">Amount (₹)</option>
                                        <option value="percent">Percentage (%)</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="discount_value">Discount Value</label>
                                    <input type="number" min="0" step="0.01"
                                        id="discount_value" name="discount_value"
                                        class="form-control" value="0" disabled>
                                </div>
                            </div>

                            <div class="discount-preview" aria-live="polite">
                                <div class="discount-preview-row">
                                    <span>Original Fees</span>
                                    <strong id="preview_original">₹0.00</strong>
                                </div>

                                <div class="discount-preview-row">
                                    <span>Discount</span>
                                    <strong id="preview_discount">− ₹0.00</strong>
                                </div>

                                <div class="discount-preview-row discount-preview-total">
                                    <span>Final Fees</span>
                                    <strong id="preview_final">₹0.00</strong>
                                </div>

                                <p style="font-size: 0.75rem; color: var(--text-muted);
                                          margin: 0.65rem 0 0;">
                                    This is a preview only. Saving the discount requires
                                    backend and database changes.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <a href="students.php" class="btn-secondary">Cancel</a>
                        <button type="submit" class="btn-primary">Save Student</button>
                    </div>

                </form>
            </div>
        </main>
    </div>

    <script>
        (function() {
            const toggle = document.getElementById('give_discount');
            const fields = document.getElementById('discount_fields');
            const type = document.getElementById('discount_type');
            const value = document.getElementById('discount_value');
            const fees = document.getElementById('total_fees');

            const originalOutput = document.getElementById('preview_original');
            const discountOutput = document.getElementById('preview_discount');
            const finalOutput = document.getElementById('preview_final');

            const money = (amount) => new Intl.NumberFormat('en-IN', {
                style: 'currency',
                currency: 'INR',
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }).format(amount);

            function updateDiscountPreview() {
                const originalFees = Math.max(0, Number(fees.value) || 0);
                const enteredValue = Math.max(0, Number(value.value) || 0);
                let discountAmount = 0;

                if (toggle.checked) {
                    if (type.value === 'percent') {
                        discountAmount =
                            originalFees * Math.min(enteredValue, 100) / 100;
                    } else {
                        discountAmount = enteredValue;
                    }
                }

                discountAmount = Math.min(originalFees, discountAmount);

                const finalFees = Math.max(0, originalFees - discountAmount);

                originalOutput.textContent = money(originalFees);
                discountOutput.textContent = '− ' + money(discountAmount);
                finalOutput.textContent = money(finalFees);
            }

            function updateDiscountVisibility() {
                const enabled = toggle.checked;

                fields.hidden = !enabled;
                type.disabled = !enabled;
                value.disabled = !enabled;

                if (!enabled) {
                    value.value = '0';
                    type.value = 'amount';
                }

                updateDiscountPreview();
            }

            toggle.addEventListener('change', updateDiscountVisibility);
            type.addEventListener('change', updateDiscountPreview);
            value.addEventListener('input', updateDiscountPreview);
            fees.addEventListener('input', updateDiscountPreview);

            updateDiscountVisibility();
        })();
    </script>

</body>

</html>