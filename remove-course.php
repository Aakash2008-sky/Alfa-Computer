
<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit;
}

require_once "config/database.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: add-course.php");
    exit;
}

$course_id = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);

if (!$course_id || $course_id < 1) {
    header("Location: add-course.php");
    exit;
}

/* Get the selected course */
$stmt = $conn->prepare(
    "SELECT course_name FROM courses WHERE id = ? LIMIT 1"
);
$stmt->bind_param("i", $course_id);
$stmt->execute();
$result = $stmt->get_result();
$course = $result->fetch_assoc();
$stmt->close();

if (!$course) {
    header("Location: add-course.php?remove_error=not_found");
    exit;
}

/* Check whether any students use this course */
$stmt = $conn->prepare(
    "SELECT COUNT(*) AS total
     FROM students
     WHERE course = ?"
);
$stmt->bind_param("s", $course["course_name"]);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stmt->close();

if ((int)$row["total"] > 0) {
    header("Location: add-course.php?remove_error=in_use");
    exit;
}

/* Delete only if no students use this course */
$stmt = $conn->prepare(
    "DELETE FROM courses WHERE id = ?"
);
$stmt->bind_param("i", $course_id);

if ($stmt->execute() && $stmt->affected_rows === 1) {
    $stmt->close();
    header("Location: add-course.php?removed=1");
    exit;
}

$stmt->close();
header("Location: add-course.php?remove_error=failed");
exit;
