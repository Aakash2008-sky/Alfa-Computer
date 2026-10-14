<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../index.php");
    exit;
}

require_once "../config/database.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../students.php");
    exit;
}

// Get POST Data
$student_id   = $_POST["student_id"] ?? "";
$first_name   = trim($_POST["first_name"] ?? "");
$middle_name  = trim($_POST["middle_name"] ?? "");
$last_name    = trim($_POST["last_name"] ?? "");
$mobile       = trim($_POST["mobile"] ?? "");
$joining_date = $_POST["joining_date"] ?? "";
$course       = trim($_POST["course"] ?? "");
$total_fees   = $_POST["total_fees"] ?? "";

// 1. Validate Input Structure
if (empty($student_id) || !is_numeric($student_id)) {
    header("Location: ../students.php");
    exit;
}

$student_id = (int)$student_id;

if (empty($first_name) || empty($last_name) || empty($mobile) || empty($joining_date) || empty($course)) {
    header("Location: ../edit-student.php?id=" . $student_id . "&error=" . urlencode("Please fill in all required fields."));
    exit;
}

if (!is_numeric($total_fees) || (float)$total_fees <= 0) {
    header("Location: ../edit-student.php?id=" . $student_id . "&error=" . urlencode("Total fees must be a valid number greater than 0."));
    exit;
}

$total_fees = (float)$total_fees;

// 2. Database Validation - Total Fees vs Total Paid
$stmt_paid = $conn->prepare("SELECT COALESCE(SUM(amount), 0) AS total_paid FROM payments WHERE student_id = ?");
if (!$stmt_paid) {
    header("Location: ../edit-student.php?id=" . $student_id . "&error=" . urlencode("Database validation failed."));
    exit;
}

$stmt_paid->bind_param("i", $student_id);
$stmt_paid->execute();
$res_paid = $stmt_paid->get_result();
$paid_data = $res_paid->fetch_assoc();
$total_paid = (float)$paid_data["total_paid"];
$stmt_paid->close();

if (round($total_fees, 2) < round($total_paid, 2)) {
    header("Location: ../edit-student.php?id=" . $student_id . "&error=" . urlencode("Total fees cannot be less than the amount already paid."));
    exit;
}

// 3. Update Student Details
$update_query = "
    UPDATE students 
    SET 
        first_name = ?,
        middle_name = ?,
        last_name = ?,
        mobile = ?,
        joining_date = ?,
        course = ?,
        total_fees = ?
    WHERE id = ?
";

$stmt_update = $conn->prepare($update_query);
if (!$stmt_update) {
    header("Location: ../edit-student.php?id=" . $student_id . "&error=" . urlencode("Failed to prepare update query."));
    exit;
}

$stmt_update->bind_param(
    "ssssssdi",
    $first_name,
    $middle_name,
    $last_name,
    $mobile,
    $joining_date,
    $course,
    $total_fees,
    $student_id
);

if ($stmt_update->execute()) {
    $stmt_update->close();
    $conn->close();
    header("Location: ../view-student.php?id=" . $student_id . "&success=" . urlencode("Student updated successfully"));
    exit;
} else {
    $stmt_update->close();
    $conn->close();
    header("Location: ../edit-student.php?id=" . $student_id . "&error=" . urlencode("Failed to update student. Please try again."));
    exit;
}