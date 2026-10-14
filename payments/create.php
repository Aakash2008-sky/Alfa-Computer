
<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../index.php");
    exit;
}

require_once "../config/database.php";

function goBack(int $studentId, string $message, bool $success = false): void
{
    $page = $success ? "../view-student.php" : "../add-payment.php";
    $key = $success ? "success" : "error";

    header(
        "Location: " . $page . "?id=" . $studentId .
        "&" . $key . "=" . urlencode($message)
    );
    exit;
}

$student_id = (int)($_POST["student_id"] ?? 0);
$amount = (float)($_POST["amount"] ?? 0);
$payment_date = $_POST["payment_date"] ?? "";
$payment_method = $_POST["payment_method"] ?? "";
$note = trim($_POST["note"] ?? "");

$give_discount = isset($_POST["give_discount"]);
$discount_type = $_POST["discount_type"] ?? "amount";
$discount_value = (float)($_POST["discount_value"] ?? 0);

if ($student_id <= 0) {
    header("Location: ../students.php");
    exit;
}

if ($amount <= 0) {
    goBack($student_id, "Enter a valid payment amount.");
}

if (
    !in_array($payment_method, ["CASH", "UPI"], true) ||
    !preg_match('/^\d{4}-\d{2}-\d{2}$/', $payment_date) ||
    !checkdate(
        (int)substr($payment_date, 5, 2),
        (int)substr($payment_date, 8, 2),
        (int)substr($payment_date, 0, 4)
    )
) {
    goBack($student_id, "Invalid payment date or payment method.");
}

if (
    $give_discount &&
    (
        !in_array($discount_type, ["amount", "percent"], true) ||
        $discount_value <= 0 ||
        ($discount_type === "percent" && $discount_value > 100)
    )
) {
    goBack($student_id, "Enter a valid discount.");
}

$conn->begin_transaction();

try {
    // Lock student record while calculating fees.
    $stmt = $conn->prepare(
        "SELECT total_fees FROM students WHERE id = ? FOR UPDATE"
    );
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $student = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$student) {
        throw new Exception("Student not found.");
    }

    $total_fees = (float)$student["total_fees"];

    // Calculate total payments.
    $stmt = $conn->prepare(
        "SELECT COALESCE(SUM(amount), 0) AS total_paid
         FROM payments WHERE student_id = ?"
    );
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $paidRow = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $total_paid = (float)$paidRow["total_paid"];

    // Calculate discounts already saved.
    $stmt = $conn->prepare(
        "SELECT COALESCE(SUM(discount_amount), 0) AS total_discount
         FROM student_discounts WHERE student_id = ?"
    );
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $discountRow = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $existing_discount = (float)$discountRow["total_discount"];

    $remaining = max(
        0,
        $total_fees - $total_paid - $existing_discount
    );

    // Calculate the new discount.
    $new_discount = 0.00;

    if ($give_discount) {
        if ($discount_type === "percent") {
            $new_discount = $remaining * $discount_value / 100;
        } else {
            $new_discount = $discount_value;
        }

        $new_discount = min($remaining, $new_discount);
        $new_discount = round($new_discount, 2);
    }

    $balance_after_discount = max(0, $remaining - $new_discount);

    // A discount cannot be greater than the remaining fees.
    if ($amount > $balance_after_discount + 0.001) {
        throw new Exception(
            "Payment exceeds the balance after discount."
        );
    }

    // Save discount first.
    if ($new_discount > 0) {
        $discount_note = $note !== "" ? $note : null;

        $stmt = $conn->prepare(
            "INSERT INTO student_discounts
             (student_id, discount_amount, discount_type,
              discount_value, note, discount_date)
             VALUES (?, ?, ?, ?, ?, ?)"
        );

        $stmt->bind_param(
            "idsdss",
            $student_id,
            $new_discount,
            $discount_type,
            $discount_value,
            $discount_note,
            $payment_date
        );

        $stmt->execute();
        $stmt->close();
    }

    // Save actual money received separately.
    $stmt = $conn->prepare(
        "INSERT INTO payments
         (student_id, amount, payment_date, payment_method, note)
         VALUES (?, ?, ?, ?, ?)"
    );

    $stmt->bind_param(
        "idsss",
        $student_id,
        $amount,
        $payment_date,
        $payment_method,
        $note
    );

    $stmt->execute();
    $stmt->close();

    $conn->commit();

    goBack(
        $student_id,
        "Payment and discount saved successfully.",
        true
    );

} catch (Throwable $e) {
    $conn->rollback();

    error_log("Payment processing error: " . $e->getMessage());

    goBack(
        $student_id,
        "Could not save payment or discount. Please check the entered amount and database."
    );
}
