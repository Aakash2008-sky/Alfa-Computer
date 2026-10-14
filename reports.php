<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit;
}

require_once "config/database.php";

$username = $_SESSION["username"] ?? "Admin";


/* =========================================
   MONTH VALIDATION
========================================= */

$current_month = date("Y-m");

$selected_month = $_GET["month"] ?? $current_month;

if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $selected_month)) {
    $selected_month = $current_month;
}


/* =========================================
   PREVENT FUTURE MONTH
========================================= */

if ($selected_month > $current_month) {
    $selected_month = $current_month;
}


/* =========================================
   DATE RANGE
========================================= */

$start_date = $selected_month . "-01";

$end_date = date(
    "Y-m-d",
    strtotime($start_date . " +1 month")
);


/* =========================================
   MONTH DISPLAY
========================================= */

$formatted_month_display = date(
    "F Y",
    strtotime($start_date)
);


/* =========================================
   PREVIOUS MONTH
========================================= */

$previous_month = date(
    "Y-m",
    strtotime($start_date . " -1 month")
);


/* =========================================
   NEXT MONTH
========================================= */

$next_month = date(
    "Y-m",
    strtotime($start_date . " +1 month")
);


/* =========================================
   NEXT MONTH DISABLED
========================================= */

$is_next_disabled = ($selected_month >= $current_month);


/* =========================================
   COURSE-WISE REPORT
========================================= */

$report_query = "

    SELECT

        s.course,

        COALESCE(SUM(p.amount), 0) AS total_payment

    FROM payments p

    INNER JOIN students s
        ON p.student_id = s.id

    WHERE
        p.payment_date >= ?
        AND p.payment_date < ?

    GROUP BY s.course

    ORDER BY total_payment DESC

";


$stmt = $conn->prepare($report_query);

$courses_report = [];


if ($stmt) {

    $stmt->bind_param(
        "ss",
        $start_date,
        $end_date
    );

    $stmt->execute();

    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {

        $courses_report[] = $row;

    }

    $stmt->close();

}


/* =========================================
   HIGHEST PAYMENT COURSE
========================================= */

$highest_course = null;

if (!empty($courses_report)) {

    $highest_course = $courses_report[0];

}


/* =========================================
   TOTAL PAYMENT RECEIVED
========================================= */

$total_payment = 0;

$total_query = "

    SELECT

        COALESCE(SUM(amount), 0) AS total_payment

    FROM payments

    WHERE
        payment_date >= ?
        AND payment_date < ?

";


$stmt = $conn->prepare($total_query);


if ($stmt) {

    $stmt->bind_param(
        "ss",
        $start_date,
        $end_date
    );

    $stmt->execute();

    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {

        $total_payment = (float)$row["total_payment"];

    }

    $stmt->close();

}


/* =========================================
   ACTIVE COURSES
========================================= */

$active_courses = count($courses_report);

?>
<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        ALFA COMPUTER — Real-Time Reports
    </title>


    <link rel="icon" type="image/x-icon" href="assets/images/alfa_logo.png">


    <!-- Google Font -->

    <link
        rel="preconnect"
        href="https://fonts.googleapis.com"
    >

    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin
    >

    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
        rel="stylesheet"
    >


    <!-- Existing CSS -->

    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >


    <style>

        :root {

            --sidebar-width: 210px;

        }


        /* =====================================
           SIDEBAR
        ===================================== */

        .sidebar {

            width: var(--sidebar-width);

            background: var(--surface);

            border-right:
                1px solid var(--border);

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

            padding:
                1rem 0.875rem;

            border-bottom:
                1px solid var(--border);

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

            padding:
                0.75rem 0.5rem;

            display: flex;

            flex-direction: column;

            gap: 0.2rem;

            flex: 1;

        }


        .menu-item {

            display: flex;

            align-items: center;

            gap: 0.5rem;

            padding:
                0.6rem 0.75rem;

            color:
                var(--text-muted);

            text-decoration: none;

            font-weight: 500;

            font-size: 0.8125rem;

            border-radius:
                var(--radius-md);

            transition:
                var(--transition);

            white-space: nowrap;

        }


        .menu-item:hover,
        .menu-item.active {

            color:
                var(--primary);

            background:
                rgba(79, 70, 229, 0.08);

        }


        .sidebar-footer {

            padding:
                0.75rem 0.5rem;

            border-top:
                1px solid var(--border);

        }


        .btn-logout {

            display: flex;

            align-items: center;

            gap: 0.5rem;

            width: 100%;

            padding:
                0.6rem 0.75rem;

            background: transparent;

            border:
                1px solid var(--border);

            border-radius:
                var(--radius-md);

            color:
                var(--text-muted);

            font-weight: 600;

            font-size: 0.8125rem;

            text-decoration: none;

            justify-content: center;

            transition:
                var(--transition);

        }


        .btn-logout:hover {

            background:
                rgba(239, 68, 68, 0.08);

            color:
                var(--danger);

            border-color:
                rgba(239, 68, 68, 0.2);

        }


        /* =====================================
           MAIN WRAPPER
        ===================================== */

        .main-wrapper {

            margin-left:
                var(--sidebar-width);

            flex: 1;

            display: flex;

            flex-direction: column;

            min-height: 100vh;

        }


        .navbar {

            height: 70px;

            background:
                var(--surface);

            border-bottom:
                1px solid var(--border);

            display: flex;

            align-items: center;

            justify-content: space-between;

            padding:
                0 2rem;

            position: sticky;

            top: 0;

            z-index: 40;

        }


        .user-name {

            font-weight: 600;

            font-size: 0.875rem;

            color:
                var(--text-main);

        }


        .content {

            padding: 2rem;

            flex: 1;

            max-width: 1100px;

        }


        /* =====================================
           PAGE HEADER
        ===================================== */

        .page-header-row {

            display: flex;

            align-items: center;

            justify-content: space-between;

            margin-bottom: 1.5rem;

            gap: 1rem;

            flex-wrap: wrap;

        }


        .page-title {

            font-size: 1.5rem;

            font-weight: 700;

            color:
                var(--text-main);

        }


        /* =====================================
           CONTROLS
        ===================================== */

        .controls-card {

            background:
                var(--surface);

            border:
                1px solid var(--border);

            border-radius:
                var(--radius-lg);

            padding:
                1.25rem 1.5rem;

            margin-bottom:
                1.5rem;

            box-shadow:
                var(--shadow-sm);

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 1rem;

            flex-wrap: wrap;

        }


        .month-filter-form {

            display: flex;

            align-items: center;

            gap: 0.75rem;

            flex-wrap: wrap;

        }


        .form-label {

            font-size: 0.875rem;

            font-weight: 600;

            color:
                var(--text-main);

        }


        .form-input-month {

            padding:
                0.5rem 0.75rem;

            border:
                1px solid var(--border);

            border-radius:
                var(--radius-md);

            font-family: inherit;

            font-size: 0.875rem;

            color:
                var(--text-main);

            background:
                var(--surface);

            outline: none;

            transition:
                var(--transition);

        }


        .form-input-month:focus {

            border-color:
                var(--primary);

            box-shadow:
                0 0 0 3px
                rgba(79, 70, 229, 0.1);

        }


        .btn-action {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            gap: 0.5rem;

            padding:
                0.55rem 1rem;

            border-radius:
                var(--radius-md);

            font-weight: 600;

            font-size: 0.875rem;

            cursor: pointer;

            text-decoration: none;

            transition:
                var(--transition);

            border: none;

        }


        .btn-primary {

            background:
                var(--primary);

            color: #ffffff;

        }


        .btn-primary:hover {

            opacity: 0.92;

        }


        .btn-excel {

            background: #107c41;

            color: #ffffff;

        }


        .btn-excel:hover {

            background: #0b5c30;

        }


        /* =====================================
           MONTH NAVIGATION
        ===================================== */

        .month-navigation {

            display: flex;

            gap: 0.75rem;

            margin-bottom: 1.5rem;

            flex-wrap: wrap;

        }


        .month-nav-btn {

            padding:
                0.5rem 0.85rem;

            border:
                1px solid var(--border);

            background:
                var(--surface);

            border-radius:
                var(--radius-md);

            color:
                var(--text-main);

            font-size: 0.8125rem;

            font-weight: 600;

            text-decoration: none;

            transition:
                var(--transition);

        }


        .month-nav-btn:hover {

            border-color:
                var(--primary);

            color:
                var(--primary);

        }


        .month-nav-btn.disabled {

            opacity: 0.5;

            cursor: not-allowed;

            pointer-events: none;

        }


        /* =====================================
           SUMMARY CARDS
        ===================================== */

        .summary-grid {

            display: grid;

            grid-template-columns:
                repeat(3, minmax(0, 1fr));

            gap: 1rem;

            margin-bottom: 1.5rem;

        }


        .summary-card {

            background:
                var(--surface);

            border:
                1px solid var(--border);

            border-radius:
                var(--radius-lg);

            padding: 1.25rem;

            box-shadow:
                var(--shadow-sm);

        }


        .summary-label {

            font-size: 0.75rem;

            font-weight: 700;

            color:
                var(--text-muted);

            text-transform: uppercase;

            letter-spacing: 0.04em;

            margin-bottom: 0.5rem;

        }


        .summary-value {

            font-size: 1.35rem;

            font-weight: 800;

            color:
                var(--text-main);

            word-break: break-word;

        }


        .summary-subvalue {

            margin-top: 0.35rem;

            font-size: 0.875rem;

            font-weight: 600;

            color:
                var(--success);

        }


        /* =====================================
           HIGHEST CARD
        ===================================== */

        .highest-card {

            background:
                linear-gradient(
                    135deg,
                    rgba(79, 70, 229, 0.05) 0%,
                    rgba(79, 70, 229, 0.02) 100%
                );

            border:
                1px solid rgba(79, 70, 229, 0.2);

            border-radius:
                var(--radius-lg);

            padding: 1.5rem;

            margin-bottom: 1.5rem;

        }


        .highest-header {

            font-size: 0.875rem;

            font-weight: 700;

            color:
                var(--primary);

            text-transform: uppercase;

            letter-spacing: 0.05em;

            margin-bottom: 0.5rem;

        }


        .highest-course-name {

            font-size: 1.75rem;

            font-weight: 800;

            color:
                var(--text-main);

            margin-bottom: 0.25rem;

        }


        .highest-amount {

            font-size: 1.25rem;

            font-weight: 700;

            color:
                var(--success);

        }


        .empty-highest {

            font-size: 1rem;

            color:
                var(--text-muted);

            font-weight: 500;

        }


        /* =====================================
           REPORT CARD
        ===================================== */

        .card {

            background:
                var(--surface);

            border:
                1px solid var(--border);

            border-radius:
                var(--radius-lg);

            padding: 1.5rem;

            box-shadow:
                var(--shadow-sm);

            margin-bottom: 1.5rem;

        }


        .section-header {

            font-size: 1.125rem;

            font-weight: 700;

            color:
                var(--text-main);

            margin-bottom: 1rem;

        }


        /* =====================================
           TABLE
        ===================================== */

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

            padding:
                0.75rem 1rem;

            background:
                var(--bg-main);

            color:
                var(--text-muted);

            font-weight: 600;

            border-bottom:
                1px solid var(--border);

            white-space: nowrap;

        }


        .data-table td {

            padding:
                0.875rem 1rem;

            border-bottom:
                1px solid var(--border);

            color:
                var(--text-main);

            white-space: nowrap;

        }


        .data-table tr:last-child td {

            border-bottom: none;

        }


        .amount-cell {

            font-weight: 700;

            color:
                var(--text-main);

        }


        .rank-cell {

            font-size: 1rem;

            font-weight: 700;

        }


        /* =====================================
           EMPTY STATE
        ===================================== */

        .empty-state {

            text-align: center;

            padding: 2rem;

            color:
                var(--text-muted);

        }


        .empty-state-icon {

            font-size: 2rem;

            margin-bottom: 0.5rem;

        }


        .empty-state-title {

            font-size: 1rem;

            font-weight: 700;

            color:
                var(--text-main);

            margin-bottom: 0.5rem;

        }


        /* =====================================
           REPORT INFO
        ===================================== */

        .report-info {

            background:
                rgba(79, 70, 229, 0.05);

            border:
                1px solid rgba(79, 70, 229, 0.15);

            border-radius:
                var(--radius-lg);

            padding: 1.25rem;

            margin-bottom: 1.5rem;

        }


        .report-info-title {

            font-weight: 700;

            color:
                var(--primary);

            margin-bottom: 0.75rem;

        }


        .report-info ul {

            margin: 0;

            padding-left: 1.25rem;

            color:
                var(--text-muted);

            font-size: 0.875rem;

            line-height: 1.8;

        }


        /* =====================================
           MOBILE
        ===================================== */

        @media (max-width: 900px) {

            .summary-grid {

                grid-template-columns:
                    1fr;

            }

        }


        @media (max-width: 768px) {

            .sidebar {

                position: relative;

                width: 100%;

                min-height: auto;

            }


            .main-wrapper {

                margin-left: 0;

            }


            .navbar {

                padding:
                    0 1rem;

            }


            .content {

                padding:
                    1rem;

            }


            .controls-card {

                align-items: stretch;

            }


            .month-filter-form {

                width: 100%;

            }


            .btn-excel {

                width: 100%;

            }

        }

    </style>

</head>


<body>


<!-- =====================================
     SIDEBAR
===================================== -->

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
            class="menu-item"
        >
            📊 Dashboard
        </a>


        <a
            href="students.php"
            class="menu-item"
        >
            👥 Students
        </a>


        <a
            href="add-student.php"
            class="menu-item"
        >
            ➕ Add Student
        </a>


        <a
            href="reports.php"
            class="menu-item active"
        >
            📄 Reports
        </a>

        <a href="add-course.php" class="menu-item">📚 Add Course</a>

        <a
            href="settings.php"
            class="menu-item"
        >
            ⚙️ Settings
        </a>

    </nav>


    <div class="sidebar-footer">

        <a
            href="auth/logout.php"
            class="btn-logout"
        >
            🚪 Logout
        </a>

    </div>

</aside>



<!-- =====================================
     MAIN WRAPPER
===================================== -->

<div class="main-wrapper">


    <!-- NAVBAR -->

    <header class="navbar">

        <div></div>

        <div class="user-profile">

            <span class="user-name">

                Welcome,
                <?php echo htmlspecialchars($username); ?>

            </span>

        </div>

    </header>



    <!-- =====================================
         MAIN CONTENT
    ===================================== -->

    <main class="content">


        <!-- PAGE TITLE -->

        <div class="page-header-row">

            <h1 class="page-title">

                Monthly Course Payment Report

            </h1>

        </div>



        <!-- =====================================
             MONTH NAVIGATION
        ===================================== -->

        <div class="month-navigation">


            <!-- PREVIOUS MONTH -->

            <a
                href="reports.php?month=<?php echo urlencode($previous_month); ?>"
                class="month-nav-btn"
            >

                ← Previous Month

            </a>



            <!-- NEXT MONTH -->

            <?php if ($is_next_disabled): ?>

                <span
                    class="month-nav-btn disabled"
                >

                    Next Month →

                </span>

            <?php else: ?>

                <a
                    href="reports.php?month=<?php echo urlencode($next_month); ?>"
                    class="month-nav-btn"
                >

                    Next Month →

                </a>

            <?php endif; ?>


        </div>



        <!-- =====================================
             MONTH FILTER + EXPORT
        ===================================== -->

        <div class="controls-card">


            <!-- MONTH FORM -->

            <form
                action="reports.php"
                method="GET"
                class="month-filter-form"
            >


                <label
                    for="month"
                    class="form-label"
                >

                    Select Month:

                </label>


                <input
                    type="month"
                    id="month"
                    name="month"
                    value="<?php echo htmlspecialchars($selected_month); ?>"
                    max="<?php echo htmlspecialchars($current_month); ?>"
                    class="form-input-month"
                    required
                >


                <button
                    type="submit"
                    class="btn-action btn-primary"
                >

                    Filter Report

                </button>


            </form>



            <!-- EXCEL EXPORT -->

            <a
                href="reports/export-excel.php?month=<?php echo urlencode($selected_month); ?>"
                class="btn-action btn-excel"
            >

                📥 Export
                <?php echo htmlspecialchars($formatted_month_display); ?>
                Report

            </a>


        </div>



        <!-- =====================================
             SUMMARY CARDS
        ===================================== -->

        <div class="summary-grid">


            <!-- HIGHEST COURSE -->

            <div class="summary-card">

                <div class="summary-label">

                    🏆 Highest Payment Course

                </div>


                <?php if ($highest_course): ?>

                    <div class="summary-value">

                        <?php
                        echo htmlspecialchars(
                            $highest_course["course"]
                        );
                        ?>

                    </div>


                    <div class="summary-subvalue">

                        ₹<?php
                        echo number_format(
                            (float)$highest_course["total_payment"],
                            2
                        );
                        ?>

                    </div>


                <?php else: ?>

                    <div class="summary-value">

                        No data available

                    </div>

                <?php endif; ?>


            </div>



            <!-- TOTAL PAYMENT -->

            <div class="summary-card">

                <div class="summary-label">

                    💰 Total Payment Received

                </div>


                <div class="summary-value">

                    ₹<?php
                    echo number_format(
                        $total_payment,
                        2
                    );
                    ?>

                </div>


            </div>



            <!-- ACTIVE COURSES -->

            <div class="summary-card">

                <div class="summary-label">

                    📚 Active Courses

                </div>


                <div class="summary-value">

                    <?php echo $active_courses; ?>

                </div>


            </div>


        </div>



        <!-- =====================================
             HIGHEST PAYMENT COURSE
        ===================================== -->

        <div class="highest-card">


            <div class="highest-header">

                🏆 Highest Payment Course

                (
                <?php
                echo htmlspecialchars(
                    $formatted_month_display
                );
                ?>
                )

            </div>


            <?php if ($highest_course): ?>


                <div class="highest-course-name">

                    <?php
                    echo htmlspecialchars(
                        $highest_course["course"]
                    );
                    ?>

                </div>


                <div class="highest-amount">

                    ₹<?php
                    echo number_format(
                        (float)$highest_course["total_payment"],
                        2
                    );
                    ?>

                </div>


            <?php else: ?>


                <div class="empty-highest">

                    No data available.

                </div>


            <?php endif; ?>


        </div>



        <!-- =====================================
             COURSE-WISE TABLE
        ===================================== -->

        <div class="card">


            <h2 class="section-header">

                Fee Collection Summary —

                <?php
                echo htmlspecialchars(
                    $formatted_month_display
                );
                ?>

            </h2>



            <?php if (!empty($courses_report)): ?>


                <div class="table-container">


                    <table class="data-table">


                        <thead>

                            <tr>

                                <th>

                                    Rank

                                </th>


                                <th>

                                    Course Name

                                </th>


                                <th>

                                    Total Payment Received

                                </th>

                            </tr>

                        </thead>



                        <tbody>


                            <?php

                            $rank = 1;

                            foreach ($courses_report as $row):


                                if ($rank == 1) {

                                    $rank_display = "🥇";

                                }

                                elseif ($rank == 2) {

                                    $rank_display = "🥈";

                                }

                                elseif ($rank == 3) {

                                    $rank_display = "🥉";

                                }

                                else {

                                    $rank_display = $rank;

                                }

                            ?>


                                <tr>


                                    <td class="rank-cell">

                                        <?php
                                        echo $rank_display;
                                        ?>

                                    </td>


                                    <td>

                                        <strong>

                                            <?php
                                            echo htmlspecialchars(
                                                $row["course"]
                                            );
                                            ?>

                                        </strong>

                                    </td>


                                    <td class="amount-cell">

                                        ₹<?php
                                        echo number_format(
                                            (float)$row["total_payment"],
                                            2
                                        );
                                        ?>

                                    </td>


                                </tr>


                            <?php

                                $rank++;

                            endforeach;

                            ?>


                        </tbody>


                    </table>


                </div>


            <?php else: ?>


                <div class="empty-state">


                    <div class="empty-state-icon">

                        📊

                    </div>


                    <div class="empty-state-title">

                        No Payment Records

                    </div>


                    <p>

                        No payment records were found for

                        <strong>

                            <?php
                            echo htmlspecialchars(
                                $formatted_month_display
                            );
                            ?>

                        </strong>

                        .

                    </p>


                    <p>

                        Try selecting another month.

                    </p>


                </div>


            <?php endif; ?>


        </div>



        <!-- =====================================
             REPORT INFORMATION
        ===================================== -->

        <div class="report-info">


            <div class="report-info-title">

                ℹ️ Report Information

            </div>


            <ul>

                <li>
                    Report is generated from actual payment records.
                </li>

                <li>
                    Joining payments are included automatically.
                </li>

                <li>
                    Report data is based on Payment Date.
                </li>

                <li>
                    Course payments are calculated in real time.
                </li>

                <li>
                    Previous monthly reports remain available.
                </li>

                <li>
                    Excel reports can be exported anytime.
                </li>

            </ul>


        </div>


    </main>


</div>


</body>

</html>


<?php

$conn->close();

?>