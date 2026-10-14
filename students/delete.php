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

$student_id = $_POST["student_id"] ?? "";

// Validate student_id
if (empty($student_id) || !is_numeric($student_id) || (int)$student_id <= 0) {
    header("Location: ../students.php?error=" . urlencode("Invalid student selection."));
    exit;
}

$student_id = (int)$student_id;

// Check if student exists
$check_stmt = $conn->prepare("SELECT id FROM students WHERE id = ?");
if (!$check_stmt) {
    header("Location: ../students.php?error=" . urlencode("Unable to remove student"));
    exit;
}

$check_stmt->bind_param("i", $student_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows === 0) {
    $check_stmt->close();
    header("Location: ../students.php?error=" . urlencode("Student does not exist."));
    exit;
}
$check_stmt->close();

// Delete Student (Cascades automatically to payments via MySQL DB relation)
$delete_stmt = $conn->prepare("DELETE FROM students WHERE id = ?");
if (!$delete_stmt) {
    header("Location: ../students.php?error=" . urlencode("Unable to remove student"));
    exit;
}

$delete_stmt->bind_param("i", $student_id);

if ($delete_stmt->execute()) {
    $delete_stmt->close();
    $conn->close();
    header("Location: ../students.php?success=" . urlencode("Student removed successfully"));
    exit;
} else {
    $delete_stmt->close();
    $conn->close();
    header("Location: ../students.php?error=" . urlencode("Unable to remove student"));
    exit;
}