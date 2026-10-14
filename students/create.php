
<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../index.php");
    exit;
}

require_once "../config/database.php";

function backToForm(string $message): void
{
    header("Location: ../add-student.php?error=" . urlencode($message));
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../add-student.php");
    exit;
}

// Student details
$first_name = trim($_POST["first_name"] ?? "");
$middle_name = trim($_POST["middle_name"] ?? "");
$last_name = trim($_POST["last_name"] ?? "");
$mobile = trim($_POST["mobile"] ?? "");
$joining_date = $_POST["joining_date"] ?? "";
$course = trim($_POST["course"] ?? "");


$course_check = $conn->prepare(
    "SELECT id FROM courses WHERE course_name = ? LIMIT 1"
);

if (!$course_check) {
    backToForm("Unable to validate the selected course.");
}

$course_check->bind_param("s", $course);
$course_check->execute();

$valid_course = $course_check->get_result()->num_rows > 0;
$course_check->close();

if (!$valid_course) {
    backToForm("Please select a valid course.");
}


$total_fees = $_POST["total_fees"] ?? "";
$paid_at_joining = $_POST["paid_at_joining"] ?? "";
$payment_method = strtoupper(trim($_POST["payment_method"] ?? ""));

// Joining discount
$give_discount = isset($_POST["give_discount"]);
$discount_type = $_POST["discount_type"] ?? "amount";
$discount_value = (float)($_POST["discount_value"] ?? 0);

// Required fields
if (
    $first_name === "" ||
    $last_name === "" ||
    $mobile === "" ||
    $joining_date === "" ||
    $course === "" ||
    $total_fees === "" ||
    $paid_at_joining === ""
) {
    backToForm("Please fill in all required fields.");
}

// Validate joining date
if (
    !preg_match('/^\d{4}-\d{2}-\d{2}$/', $joining_date) ||
    !checkdate(
        (int)substr($joining_date, 5, 2),
        (int)substr($joining_date, 8, 2),
        (int)substr($joining_date, 0, 4)
    )
) {
    backToForm("Please enter a valid joining date.");
}

// Validate fees
if (!is_numeric($total_fees) || (float)$total_fees <= 0) {
    backToForm("Total Fees must be greater than 0.");
}

if (!is_numeric($paid_at_joining) || (float)$paid_at_joining < 0) {
    backToForm("Paid at Joining cannot be negative.");
}

$total_fees = round((float)$total_fees, 2);
$paid_at_joining = round((float)$paid_at_joining, 2);

// Validate discount
if ($give_discount) {
    if (
        !in_array($discount_type, ["amount", "percent"], true) ||
        $discount_value <= 0 ||
        ($discount_type === "percent" && $discount_value > 100)
    ) {
        backToForm("Please enter a valid joining discount.");
    }
} else {
    $discount_type = "amount";
    $discount_value = 0;
}

// Calculate joining discount
$discount_amount = 0.00;

if ($give_discount) {
    if ($discount_type === "percent") {
        $discount_amount = $total_fees * $discount_value / 100;
    } else {
        $discount_amount = $discount_value;
    }

    $discount_amount = round(
        min($total_fees, $discount_amount),
        2
    );
}

// Fees after discount
$final_fees = max(0, $total_fees - $discount_amount);

if ($paid_at_joining > $final_fees) {
    backToForm(
        "Paid at Joining cannot be greater than fees after discount."
    );
}

// Validate payment method
if ($paid_at_joining > 0) {
    if (!in_array($payment_method, ["CASH", "UPI"], true)) {
        backToForm("Select CASH or UPI for the joining payment.");
    }
}

$middle_name_val = $middle_name !== "" ? $middle_name : null;

$conn->begin_transaction();

try {
    // 1. Insert student
    $student_stmt = $conn->prepare(
        "INSERT INTO students
         (first_name, middle_name, last_name, mobile,
          joining_date, course, total_fees)
         VALUES (?, ?, ?, ?, ?, ?, ?)"
    );

    if (!$student_stmt) {
        throw new Exception("Could not prepare student insert.");
    }

    $student_stmt->bind_param(
        "ssssssd",
        $first_name,
        $middle_name_val,
        $last_name,
        $mobile,
        $joining_date,
        $course,
        $total_fees
    );

    $student_stmt->execute();
    $student_id = (int)$conn->insert_id;
    $student_stmt->close();

    // 2. Save joining discount
    if ($discount_amount > 0) {
        $discount_note = "Joining Discount";

        $discount_stmt = $conn->prepare(
            "INSERT INTO student_discounts
             (student_id, discount_amount, discount_type,
              discount_value, note, discount_date)
             VALUES (?, ?, ?, ?, ?, ?)"
        );

        if (!$discount_stmt) {
            throw new Exception("Could not prepare discount insert.");
        }

        $discount_stmt->bind_param(
            "isdsss",
            $student_id,
            $discount_amount,
            $discount_type,
            $discount_value,
            $discount_note,
            $joining_date
        );

        $discount_stmt->execute();
        $discount_stmt->close();
    }

    // 3. Save actual joining payment separately
    if ($paid_at_joining > 0) {
        $payment_note = "Joining Payment";

        $payment_stmt = $conn->prepare(
            "INSERT INTO payments
             (student_id, amount, payment_date, payment_method, note)
             VALUES (?, ?, ?, ?, ?)"
        );

        if (!$payment_stmt) {
            throw new Exception("Could not prepare joining payment.");
        }

        $payment_stmt->bind_param(
            "idsss",
            $student_id,
            $paid_at_joining,
            $joining_date,
            $payment_method,
            $payment_note
        );

        $payment_stmt->execute();
        $payment_stmt->close();
    }

    // 4. Commit all records together
    $conn->commit();
    $conn->close();

    header(
        "Location: ../view-student.php?id=" . $student_id .
        "&success=" . urlencode("Student added successfully.")
    );
    exit;

} catch (Throwable $e) {
    $conn->rollback();
    error_log("Add student error: " . $e->getMessage());
    $conn->close();

    backToForm(
        "Could not save student, joining payment or discount. Please check the database."
    );
}
