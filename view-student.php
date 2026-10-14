<?php
// view-student.php
// ALFA COMPUTER Fees Management System

session_start();

// Session protection
if (!isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit;
}

require_once "config/database.php";

$user_id = (int)$_SESSION["user_id"];

// Get username
$username = $_SESSION["username"] ?? "";

if (empty($username)) {
    $user_stmt = $conn->prepare(
        "SELECT username FROM users WHERE id = ? LIMIT 1"
    );

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


// ======================================================
// STUDENT ID
// ======================================================

$student_id = isset($_GET["id"])
    ? (int)$_GET["id"]
    : 0;

if ($student_id <= 0) {
    header("Location: students.php");
    exit;
}


// ======================================================
// FETCH STUDENT
// ======================================================

$stmt = $conn->prepare(
    "SELECT * FROM students WHERE id = ? LIMIT 1"
);

if (!$stmt) {
    die("Database error: " . $conn->error);
}

$stmt->bind_param("i", $student_id);
$stmt->execute();

$result = $stmt->get_result();

$student = $result->fetch_assoc();

$stmt->close();


if (!$student) {
    header("Location: students.php");
    exit;
}


// ======================================================
// STUDENT NAME
// ======================================================

$name_parts = array_filter([
    trim($student["first_name"] ?? ""),
    trim($student["middle_name"] ?? ""),
    trim($student["last_name"] ?? "")
]);

$full_name = implode(" ", $name_parts);

if ($full_name === "") {
    $full_name = "Student";
}


// ======================================================
// BASIC STUDENT DATA
// ======================================================

$course = $student["course"] ?? "N/A";

$mobile =
    $student["mobile"] ??
    $student["mobile_number"] ??
    $student["phone"] ??
    $student["contact_number"] ??
    "N/A";

$email =
    $student["email"] ??
    $student["email_address"] ??
    "N/A";


// ======================================================
// JOINING / ADMISSION DATE
// ======================================================

// Use existing joining/admission field if available.
// Do NOT use created_at unless no joining field exists.

$joining_date = "";

$possible_date_fields = [
    "joining_date",
    "admission_date",
    "date_of_joining",
    "date_of_admission"
];

foreach ($possible_date_fields as $field) {

    if (
        isset($student[$field]) &&
        !empty($student[$field]) &&
        $student[$field] !== "0000-00-00"
    ) {
        $joining_date = $student[$field];
        break;
    }
}

if ($joining_date !== "") {

    $timestamp = strtotime($joining_date);

    if ($timestamp !== false) {
        $joining_date_display =
            date("d M Y", $timestamp);
    } else {
        $joining_date_display =
            htmlspecialchars($joining_date);
    }
} else {

    $joining_date_display = "N/A";
}


// ======================================================
// FEES
// ======================================================

$total_fees = (float)($student["total_fees"] ?? 0);


// ======================================================
// TOTAL PAID
// ======================================================

$stmt_paid = $conn->prepare(
    "SELECT COALESCE(SUM(amount), 0) AS total_paid
     FROM payments
     WHERE student_id = ?"
);

if (!$stmt_paid) {
    die("Database error: " . $conn->error);
}

$stmt_paid->bind_param("i", $student_id);
$stmt_paid->execute();

$paid_data = $stmt_paid->get_result()->fetch_assoc();
$stmt_paid->close();

$total_paid = (float)($paid_data["total_paid"] ?? 0);


// ======================================================
// TOTAL DISCOUNT
// ======================================================

$total_discount = 0.00;

$stmt_discount = $conn->prepare(
    "SELECT COALESCE(SUM(discount_amount), 0) AS total_discount
     FROM student_discounts
     WHERE student_id = ?"
);

if (!$stmt_discount) {
    die("Database error: " . $conn->error);
}

$stmt_discount->bind_param("i", $student_id);
$stmt_discount->execute();

$discount_data = $stmt_discount->get_result()->fetch_assoc();
$stmt_discount->close();

$total_discount = (float)($discount_data["total_discount"] ?? 0);


// ======================================================
// FINAL REMAINING BALANCE
// ======================================================

$remaining_fees = max(
    0,
    $total_fees - $total_paid - $total_discount
);


// ======================================================
// PAYMENT HISTORY
// ======================================================

$stmt_payments = $conn->prepare(
    "SELECT *
     FROM payments
     WHERE student_id = ?
     ORDER BY created_at DESC"
);

if (!$stmt_payments) {
    die("Database error: " . $conn->error);
}

$stmt_payments->bind_param("i", $student_id);
$stmt_payments->execute();

$payment_result = $stmt_payments->get_result();

$payments = [];

while ($payment_row = $payment_result->fetch_assoc()) {
    $payments[] = $payment_row;
}

$stmt_payments->close();


// ======================================================
// PAYMENT MODE HELPER
// ======================================================

function getPaymentMode($payment)
{
    $possible_fields = [
        "payment_mode",
        "mode",
        "payment_method",
        "method"
    ];

    foreach ($possible_fields as $field) {

        if (
            isset($payment[$field]) &&
            $payment[$field] !== ""
        ) {
            return strtoupper(
                trim($payment[$field])
            );
        }
    }

    return "N/A";
}

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>
        Student Details — ALFA COMPUTER
    </title>

    <link rel="icon" type="image/x-icon" href="assets/images/alfa_logo.png">

    <!-- Same font as Dashboard -->
    <link
        rel="preconnect"
        href="https://fonts.googleapis.com">

    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin>

    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">

    <!-- Existing application CSS -->
    <link
        rel="stylesheet"
        href="assets/css/style.css">

    <style>
        :root {
            --sidebar-width: 210px;
        }


        /* =========================================
           SIDEBAR
        ========================================= */

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


        /* =========================================
           MAIN
        ========================================= */

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


        /* =========================================
           PAGE HEADER
        ========================================= */

        .page-header {

            display: flex;

            align-items: flex-start;

            justify-content: space-between;

            gap: 1rem;

            margin-bottom: 2rem;
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

            margin: 0;
        }


        .header-actions {

            display: flex;

            gap: 0.75rem;

            flex-wrap: wrap;
        }


        .btn-ui {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            gap: 0.4rem;

            padding: 0.65rem 1rem;

            border-radius: var(--radius-md);

            text-decoration: none;

            font-size: 0.8125rem;

            font-weight: 600;

            border: 1px solid var(--border);

            background: var(--surface);

            color: var(--text-main);

            transition: var(--transition);
        }


        .btn-ui:hover {

            border-color: var(--primary);

            color: var(--primary);
        }


        .btn-primary-ui {

            background: var(--primary);

            border-color: var(--primary);

            color: white;
        }


        .btn-primary-ui:hover {

            background: #4338ca;

            color: white;
        }


        /* =========================================
           STUDENT PROFILE CARD
        ========================================= */

        .profile-card {

            background: var(--surface);

            border: 1px solid var(--border);

            border-radius: var(--radius-lg);

            padding: 1.5rem;

            box-shadow: var(--shadow-sm);

            margin-bottom: 1.5rem;
        }


        .profile-top {

            display: flex;

            align-items: center;

            gap: 1rem;

            padding-bottom: 1.25rem;

            border-bottom: 1px solid var(--border);

            margin-bottom: 1.25rem;
        }


        .student-avatar {

            width: 54px;

            height: 54px;

            border-radius: 50%;

            display: flex;

            align-items: center;

            justify-content: center;

            background: rgba(79, 70, 229, 0.08);

            color: var(--primary);

            font-size: 1.25rem;

            font-weight: 700;

            border: 1px solid rgba(79, 70, 229, 0.12);
        }


        .student-name {

            font-size: 1.35rem;

            font-weight: 700;

            color: var(--text-main);

            margin: 0 0 0.35rem;
        }


        .student-id {

            display: inline-block;

            background: rgba(79, 70, 229, 0.08);

            color: var(--primary);

            padding: 0.25rem 0.55rem;

            border-radius: 6px;

            font-size: 0.75rem;

            font-weight: 600;
        }


        .profile-info {

            display: grid;

            grid-template-columns:
                repeat(4, 1fr);

            gap: 1.5rem;
        }


        .info-label {

            font-size: 0.7rem;

            font-weight: 600;

            color: var(--text-muted);

            text-transform: uppercase;

            letter-spacing: 0.04em;

            margin-bottom: 0.35rem;
        }


        .info-value {

            color: var(--text-main);

            font-size: 0.9rem;

            font-weight: 600;
        }


        /* =========================================
           FEE CARDS
        ========================================= */

        .fees-grid {

            display: grid;

            grid-template-columns:
                repeat(3, 1fr);

            gap: 1.5rem;

            margin-bottom: 1.5rem;
        }


        .fee-card {

            background: var(--surface);

            border: 1px solid var(--border);

            border-radius: var(--radius-lg);

            padding: 1.5rem;

            box-shadow: var(--shadow-sm);
        }


        .fee-title {

            font-size: 0.8rem;

            font-weight: 600;

            color: var(--text-muted);

            margin-bottom: 0.55rem;
        }


        .fee-value {

            font-size: 1.75rem;

            font-weight: 800;

            color: var(--text-main);
        }


        .fee-paid {

            color: #059669;
        }


        .fee-remaining {

            color: #dc2626;
        }


        .fee-cleared {

            color: #059669;
        }


        .fee-footer {

            margin-top: 1rem;

            padding-top: 0.85rem;

            border-top: 1px solid var(--border);

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 0.75rem;

            font-size: 0.8rem;

            font-weight: 600;
        }


        .paid-badge {

            padding: 0.35rem 0.65rem;

            background: rgba(16, 185, 129, 0.1);

            color: #059669;

            border-radius: var(--radius-md);

            font-size: 0.75rem;

        }


        /* =========================================
           PAYMENT CARD
        ========================================= */

        .payment-card {

            background: var(--surface);

            border: 1px solid var(--border);

            border-radius: var(--radius-lg);

            box-shadow: var(--shadow-sm);

            overflow: hidden;
        }


        .payment-header {

            padding: 1.5rem;

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 1rem;

            border-bottom: 1px solid var(--border);
        }


        .payment-title {

            font-size: 1.125rem;

            font-weight: 700;

            color: var(--text-main);

            margin: 0;
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

            padding: 0.85rem 1rem;

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


        .amount-paid {

            color: #059669;

            font-weight: 700;
        }


        .payment-badge {

            display: inline-block;

            padding: 0.3rem 0.65rem;

            border-radius: 7px;

            background: rgba(79, 70, 229, 0.1);

            color: var(--primary);

            font-size: 0.75rem;

            font-weight: 600;
        }


        .empty-state {

            padding: 2.5rem 1rem;

            text-align: center;

            color: var(--text-muted);

            font-size: 0.875rem;
        }


        /* =========================================
           RESPONSIVE
        ========================================= */

        @media (max-width: 900px) {

            .profile-info {

                grid-template-columns:
                    repeat(2, 1fr);
            }

            .fees-grid {

                grid-template-columns:
                    1fr;
            }
        }


        @media (max-width: 700px) {

            :root {

                --sidebar-width: 0px;
            }

            .sidebar {

                display: none;
            }

            .main-wrapper {

                margin-left: 0;
            }

            .content {

                padding: 1rem;
            }

            .navbar {

                padding: 0 1rem;
            }

            .page-header {

                flex-direction: column;
            }

            .profile-info {

                grid-template-columns:
                    1fr;
            }

            .header-actions {

                width: 100%;
            }

            .btn-ui {

                flex: 1;
            }
        }
    </style>

</head>


<body>


    <!-- ==================================================
     SIDEBAR
================================================== -->

    <aside class="sidebar">

        <div class="sidebar-header">

            <div class="brand-logo-text">

                ALFA

                <span class="sub-brand">
                    COMPUTER
                </span>

            </div>

        </div>


        <nav class="sidebar-menu">

            <a
                href="dashboard.php"
                class="menu-item">
                📊 Dashboard
            </a>


            <a
                href="students.php"
                class="menu-item active">
                👥 Students
            </a>


            <a
                href="add-student.php"
                class="menu-item">
                ➕ Add Student
            </a>


            <a
                href="reports.php"
                class="menu-item">
                📄 Reports
            </a>

            <a href="add-course.php" class="menu-item">📚 Add Course</a>

            <a
                href="settings.php"
                class="menu-item">
                ⚙️ Settings
            </a>

        </nav>


        <div class="sidebar-footer">

            <a
                href="auth/logout.php"
                class="btn-logout">
                🚪 Logout
            </a>

        </div>

    </aside>



    <!-- ==================================================
     MAIN WRAPPER
================================================== -->

    <div class="main-wrapper">


        <!-- TOP NAVBAR -->

        <header class="navbar">

            <div></div>

            <div class="user-profile">

                <span class="user-name">

                    Welcome,
                    <?php
                    echo htmlspecialchars($username);
                    ?>

                </span>

            </div>

        </header>



        <!-- CONTENT -->

        <main class="content">


            <!-- PAGE HEADER -->

            <div class="page-header">

                <div>

                    <h1 class="page-title">
                        Student Details
                    </h1>

                    <p class="page-subtitle">
                        Overview, profile metrics, and complete payment ledger.
                    </p>

                </div>


                <div class="header-actions">

                    <a
                        href="students.php"
                        class="btn-ui">
                        ← Back to Students
                    </a>


                    <a
                        href="edit-student.php?id=<?php echo $student_id; ?>"
                        class="btn-ui btn-primary-ui">
                        ✏ Edit Profile
                    </a>

                </div>

            </div>



            <!-- ==================================================
             STUDENT PROFILE
        ================================================== -->

            <div class="profile-card">


                <div class="profile-top">


                    <div class="student-avatar">

                        <?php
                        echo strtoupper(
                            substr($full_name, 0, 1)
                        );
                        ?>

                    </div>


                    <div>

                        <h2 class="student-name">

                            <?php
                            echo htmlspecialchars($full_name);
                            ?>

                        </h2>


                        <span class="student-id">

                            Student ID:
                            #<?php echo $student_id; ?>

                        </span>

                    </div>

                </div>



                <div class="profile-info">


                    <!-- COURSE -->

                    <div>

                        <div class="info-label">
                            Enrolled Course
                        </div>

                        <div class="info-value">
                            📖
                            <?php
                            echo htmlspecialchars($course);
                            ?>
                        </div>

                    </div>


                    <!-- MOBILE -->

                    <div>

                        <div class="info-label">
                            Mobile Number
                        </div>

                        <div class="info-value">
                            📞
                            <?php
                            echo htmlspecialchars($mobile);
                            ?>
                        </div>

                    </div>


                    <!-- EMAIL -->

                    <div>

                        <div class="info-label">
                            Email Address
                        </div>

                        <div class="info-value">
                            ✉️
                            <?php
                            echo htmlspecialchars($email);
                            ?>
                        </div>

                    </div>


                    <!-- JOINING DATE -->

                    <div>

                        <div class="info-label">
                            Joining Date
                        </div>

                        <div class="info-value">
                            📅
                            <?php
                            echo htmlspecialchars(
                                $joining_date_display
                            );
                            ?>
                        </div>

                    </div>


                </div>

            </div>



            <!-- ==================================================
             FEES
        ================================================== -->

            <div class="fees-grid">


                <!-- TOTAL -->

                <div class="fee-card">

                    <div class="fee-title">
                        Total Course Fees
                    </div>

                    <div class="fee-value">

                        ₹<?php
                            echo number_format(
                                $total_fees,
                                2
                            );
                            ?>

                    </div>

                </div>



                <!-- PAID -->

                <div class="fee-card">

                    <div class="fee-title">
                        Total Fees Paid
                    </div>

                    <div class="fee-value fee-paid">

                        ₹<?php
                            echo number_format(
                                $total_paid,
                                2
                            );
                            ?>

                    </div>

                </div>


                <!-- DISCOUNT -->
                <div class="fee-card">
                    <div class="fee-title">Total Discount</div>

                    <div class="fee-value fee-paid">
                        ₹<?php echo number_format($total_discount, 2); ?>
                    </div>
                </div>


                <!-- REMAINING -->

                <div class="fee-card">

                    <div class="fee-title">
                        Remaining Balance
                    </div>

                    <div
                        class="fee-value <?php
                                            echo $remaining_fees > 0
                                                ? 'fee-remaining'
                                                : 'fee-cleared';
                                            ?>">

                        ₹<?php
                            echo number_format(
                                $remaining_fees,
                                2
                            );
                            ?>

                    </div>


                    <div class="fee-footer">


                        <?php if ($remaining_fees > 0): ?>


                            <span>
                                Payment Pending
                            </span>


                            <a
                                href="add-payment.php?id=<?php echo $student_id; ?>"
                                class="btn-ui btn-primary-ui">
                                💰 Add Payment
                            </a>


                        <?php else: ?>


                            <span class="paid-badge">
                                ✓ Account Cleared
                            </span>


                            <span class="paid-badge">
                                ✓ Fully Paid
                            </span>


                        <?php endif; ?>


                    </div>

                </div>

            </div>



            <!-- ==================================================
             PAYMENT HISTORY
        ================================================== -->

            <div class="payment-card">


                <div class="payment-header">

                    <h2 class="payment-title">
                        Payment History
                    </h2>


                    <?php if ($remaining_fees > 0): ?>

                        <a
                            href="add-payment.php?id=<?php echo $student_id; ?>"
                            class="btn-ui btn-primary-ui">
                            💰 Add Payment
                        </a>

                    <?php endif; ?>

                </div>



                <?php if (!empty($payments)): ?>


                    <div class="table-responsive">

                        <table class="data-table">


                            <thead>

                                <tr>

                                    <th>
                                        #
                                    </th>

                                    <th>
                                        Amount Paid
                                    </th>

                                    <th>
                                        Payment Mode
                                    </th>

                                    <th>
                                        Transaction ID / Reference
                                    </th>

                                    <th>
                                        Date
                                    </th>

                                </tr>

                            </thead>


                            <tbody>


                                <?php foreach (
                                    $payments
                                    as $index => $payment
                                ): ?>


                                    <?php
                                    $payment_mode =
                                        getPaymentMode(
                                            $payment
                                        );

                                    $transaction_id =
                                        $payment["transaction_id"]
                                        ?? $payment["reference"]
                                        ?? "—";
                                    ?>


                                    <tr>


                                        <td>
                                            <?php echo $index + 1; ?>
                                        </td>

                                        <td class="amount-paid">
                                            ₹<?php echo number_format((float)($payment["amount"] ?? 0), 2); ?>
                                        </td>

                                        <td>
                                            <span class="payment-badge">
                                                <?php echo htmlspecialchars($payment_mode); ?>
                                            </span>
                                        </td>

                                        <td>
                                            <?php echo htmlspecialchars($transaction_id); ?>
                                        </td>

                                        <td>
                                            <?php
                                            if (!empty($payment['payment_date'])) {

                                                $timestamp = strtotime($payment['payment_date']);

                                                echo $timestamp !== false
                                                    ? date('d M Y', $timestamp)
                                                    : htmlspecialchars($payment['payment_date']);
                                            } else {
                                                echo "—";
                                            }
                                            ?>
                                        </td>


                                    </tr>


                                <?php endforeach; ?>


                            </tbody>

                        </table>

                    </div>


                <?php else: ?>


                    <div class="empty-state">

                        No payment records available
                        for this student.

                    </div>


                <?php endif; ?>


            </div>


        </main>

    </div>


</body>

</html>