<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit;
}

require_once "config/database.php";

$username = $_SESSION["username"] ?? "";
$error = $_GET["error"] ?? "";

// Validate Student ID from URL
$student_id = $_GET["id"] ?? "";

if (empty($student_id) || !is_numeric($student_id)) {
    header("Location: students.php");
    exit;
}

$student_id = (int)$student_id;

// Fetch Student details and calculate Total Paid and Remaining Fees dynamically
$student_query = "
    SELECT 
        s.id,
        s.first_name,
        s.middle_name,
        s.last_name,
        s.course,
        s.total_fees,
        COALESCE(SUM(p.amount), 0) AS total_paid,
        (s.total_fees - COALESCE(SUM(p.amount), 0)) AS remaining_fees
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

// Build Full Name without extra spaces
$fullNameParts = array_filter([
    trim($student['first_name']),
    trim($student['middle_name'] ?? ''),
    trim($student['last_name'])
]);
$fullName = implode(' ', $fullNameParts);

$total_fees = (float)$student['total_fees'];
$total_paid = (float)$student['total_paid'];
$remaining_fees = (float)$student['remaining_fees'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ALFA COMPUTER — Add Payment</title>
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

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            background: var(--bg-main);
            padding: 1.25rem;
            border-radius: var(--radius-md);
            border: 1px solid var(--border);
            margin-bottom: 1.5rem;
        }

        .summary-item {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .summary-label {
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
        }

        .summary-value {
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--text-main);
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

        @media (max-width: 640px) {
            .summary-grid {
                grid-template-columns: 1fr;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .form-group.full-width {
                grid-column: span 1;
            }
        }


        /* Add Payment - Discount Section Like Add Student */

        .discount-toggle-card {
            width: 100%;
            box-sizing: border-box;
            background: #f8fafc;
            border: 1px solid #dbe5f1;
            border-radius: 12px;
            padding: 20px 22px;
            margin-top: 24px;
            margin-bottom: 24px;
            box-shadow: none;
        }

        /* Checkbox and Label */
        .discount-toggle-label {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 0;
            color: #0f172a;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
        }

        .discount-toggle-label input[type="checkbox"] {
            width: 22px;
            height: 22px;
            margin: 0;
            accent-color: #4f46e5;
            cursor: pointer;
            flex-shrink: 0;
        }

        /* Optional Description */
        .discount-description {
            margin: 10px 0 0 35px;
            color: #64748b;
            font-size: 15px;
            line-height: 1.5;
        }

        /* Discount Input Fields */
        #discount_fields {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 20px;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #dbe5f1;
        }

        #discount_fields[hidden] {
            display: none;
        }

        #discount_fields .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin: 0;
        }

        #discount_fields label {
            color: #0f172a;
            font-size: 15px;
            font-weight: 500;
        }

        #discount_fields .form-control {
            width: 100%;
            min-height: 48px;
            box-sizing: border-box;
            padding: 12px 14px;
            border: 1px solid #dbe5f1;
            border-radius: 10px;
            background: #fff;
            font-size: 15px;
            color: #0f172a;
        }

        #discount_fields .form-control:focus {
            outline: none;
            border-color: #4f46e5;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.10);
        }

        /* Remaining Fees Preview */
        .discount-preview {
            margin-top: 18px;
            padding: 15px 18px;
            background: #fff;
            border: 1px solid #dbe5f1;
            border-radius: 10px;
        }

        .discount-preview-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            padding: 12px 0;
            font-size: 15px;
            color: #475569;
        }

        .discount-preview-row+.discount-preview-row {
            border-top: 1px dashed #dbe5f1;
        }

        .discount-preview-row strong {
            color: #0f172a;
            text-align: right;
        }

        #preview_discount {
            color: #16a34a;
        }

        .discount-preview-row:last-child strong {
            color: #4f46e5;
            font-size: 17px;
        }

        @media (max-width: 600px) {
            .discount-toggle-card {
                padding: 16px;
            }

            #discount_fields {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .discount-description {
                margin-left: 34px;
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
                    <h1 class="page-title">Add Fee Payment</h1>
                </div>
            </div>

            <!-- STUDENT READ-ONLY INFO CARD -->
            <div class="card">
                <h2 class="section-header">Student Overview</h2>

                <div style="margin-bottom: 1rem; display: flex; flex-direction: column; gap: 0.25rem;">
                    <span style="font-size: 1.15rem; font-weight: 700; color: var(--text-main);">
                        <?php echo htmlspecialchars($fullName); ?>
                    </span>
                    <span style="font-size: 0.85rem; color: var(--text-muted); font-weight: 500;">
                        Course: <strong><?php echo htmlspecialchars($student['course']); ?></strong>
                    </span>
                </div>

                <div class="summary-grid">
                    <div class="summary-item">
                        <span class="summary-label">Total Fees</span>
                        <span class="summary-value">₹<?php echo number_format($total_fees, 2); ?></span>
                    </div>
                    <div class="summary-item">
                        <span class="summary-label">Total Paid</span>
                        <span class="summary-value" style="color: #10B981;">₹<?php echo number_format($total_paid, 2); ?></span>
                    </div>
                    <div class="summary-item">
                        <span class="summary-label">Remaining Fees</span>
                        <span class="summary-value" style="color: <?php echo ($remaining_fees > 0) ? 'var(--danger)' : '#10B981'; ?>;">
                            ₹<?php echo number_format($remaining_fees, 2); ?>
                        </span>
                    </div>
                </div>

                <!-- PAYMENT FORM -->
                <?php if ($remaining_fees <= 0): ?>
                    <div class="alert-danger" style="margin-bottom: 0;">
                        This student has already settled all remaining fees. No further payments can be recorded.
                    </div>
                <?php else: ?>
                    <form action="payments/create.php" method="POST">
                        <input type="hidden" name="student_id" value="<?php echo $student['id']; ?>">

                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label" for="amount">Payment Amount (₹) *</label>
                                <input
                                    type="number"
                                    step="0.01"
                                    min="0.01"
                                    max="<?php echo $remaining_fees; ?>"
                                    id="amount"
                                    name="amount"
                                    class="form-control"
                                    placeholder="Enter amount"
                                    required>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="payment_date">Payment Date *</label>
                                <input
                                    type="date"
                                    id="payment_date"
                                    name="payment_date"
                                    class="form-control"
                                    value="<?php echo date('Y-m-d'); ?>"
                                    required>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="payment_method">Payment Method *</label>
                                <select id="payment_method" name="payment_method" class="form-control" required>
                                    <option value="CASH">CASH</option>
                                    <option value="UPI">UPI</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="note">Note (Optional)</label>
                                <input
                                    type="text"
                                    id="note"
                                    name="note"
                                    class="form-control"
                                    placeholder="e.g., Installment 2 / GPay">
                            </div>


                            <!-- DISCOUNT SECTION -->
                            <div class="discount-toggle-card">
                                <label class="discount-toggle-label">
                                    <input
                                        type="checkbox"
                                        id="give_discount"
                                        name="give_discount"
                                        value="1">
                                    Give Discount
                                </label>


                                <p class="discount-description">
                                    Enable this option to give a discount to the student.
                                </p>


                                <div id="discount_fields" hidden>
                                    <div class="form-group">
                                        <label for="discount_type">Discount Type</label>
                                        <select
                                            id="discount_type"
                                            name="discount_type"
                                            class="form-control"
                                            disabled>
                                            <option value="amount">Fixed Amount (₹)</option>
                                            <option value="percent">Percentage (%)</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label for="discount_value">Discount Value</label>
                                        <input
                                            type="number"
                                            id="discount_value"
                                            name="discount_value"
                                            class="form-control"
                                            min="0"
                                            step="0.01"
                                            value="0"
                                            disabled>
                                    </div>
                                </div>

                                <div class="discount-preview">
                                    <div class="discount-preview-row">
                                        <span>Remaining Fees</span>
                                        <strong id="preview_remaining">₹0.00</strong>
                                    </div>

                                    <div class="discount-preview-row">
                                        <span>Discount</span>
                                        <strong id="preview_discount">₹0.00</strong>
                                    </div>

                                    <div class="discount-preview-row">
                                        <span>Balance After Discount</span>
                                        <strong id="preview_balance">₹0.00</strong>
                                    </div>
                                </div>
                            </div>


                            <div class="form-group full-width" style="margin-top: 0.5rem;">
                                <button type="submit" class="btn-primary">💾 Save Payment</button>
                            </div>
                        </div>
                    </form>
                <?php endif; ?>
            </div>

        </main>
    </div>


    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const discountCheckbox = document.getElementById("give_discount");
            const discountFields = document.getElementById("discount_fields");
            const discountType = document.getElementById("discount_type");
            const discountValue = document.getElementById("discount_value");

            const amountInput = document.getElementById("amount");

            const remainingFees = <?php echo json_encode((float)$remaining_fees); ?>;

            const previewRemaining = document.getElementById("preview_remaining");
            const previewDiscount = document.getElementById("preview_discount");
            const previewBalance = document.getElementById("preview_balance");

            function money(value) {
                return "₹" + value.toFixed(2);
            }

            function updatePreview() {
                const enabled = discountCheckbox.checked;

                discountFields.hidden = !enabled;
                discountType.disabled = !enabled;
                discountValue.disabled = !enabled;

                let discount = 0;

                if (enabled) {
                    const value = Math.max(0, Number(discountValue.value) || 0);

                    if (discountType.value === "percent") {
                        discount = remainingFees * Math.min(value, 100) / 100;
                    } else {
                        discount = value;
                    }
                }

                discount = Math.min(remainingFees, discount);

                const balance = Math.max(0, remainingFees - discount);

                previewRemaining.textContent = money(remainingFees);
                previewDiscount.textContent = money(discount);
                previewBalance.textContent = money(balance);

                amountInput.max = balance;
            }

            discountCheckbox.addEventListener("change", updatePreview);
            discountType.addEventListener("change", updatePreview);
            discountValue.addEventListener("input", updatePreview);

            updatePreview();
        });
    </script>


</body>

</html>
<?php
$conn->close();
?>