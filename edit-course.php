
<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit;
}

require_once "config/database.php";

$message = "";
$message_type = "";
$course_id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $course_id = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);
    $course_name = trim($_POST["course_name"] ?? "");

    if (!$course_id || $course_id < 1) {
        header("Location: add-course.php");
        exit;
    }

    if ($course_name === "") {
        $message = "Please enter a course name.";
        $message_type = "error";
    } elseif (mb_strlen($course_name) > 150) {
        $message = "Course name cannot exceed 150 characters.";
        $message_type = "error";
    } else {
        $stmt = $conn->prepare(
            "UPDATE courses SET course_name = ? WHERE id = ?"
        );

        if (!$stmt) {
            $message = "Unable to prepare the database query.";
            $message_type = "error";
        } else {
            $stmt->bind_param("si", $course_name, $course_id);

            if ($stmt->execute()) {
                if ($stmt->affected_rows >= 0) {
                    $stmt->close();
                    header("Location: add-course.php?updated=1");
                    exit;
                }
            }

            if ($stmt->errno === 1062) {
                $message = "This course name already exists.";
            } else {
                $message = "Unable to update the course.";
            }

            $message_type = "error";
            $stmt->close();
        }
    }
}

if (!$course_id || $course_id < 1) {
    header("Location: add-course.php");
    exit;
}

$stmt = $conn->prepare(
    "SELECT course_name FROM courses WHERE id = ? LIMIT 1"
);
$stmt->bind_param("i", $course_id);
$stmt->execute();
$result = $stmt->get_result();
$course = $result->fetch_assoc();
$stmt->close();

if (!$course) {
    header("Location: add-course.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Course | ALFA COMPUTER</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            padding: 30px 16px;
            font-family: Arial, sans-serif;
            background: #f4f7fb;
            color: #1f2937;
        }
        .container { max-width: 600px; margin: 0 auto; }
        .card {
            background: #fff;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 3px 14px rgba(0,0,0,.06);
        }
        h1 { margin-top: 0; font-size: 25px; }
        label { display: block; margin-bottom: 8px; font-weight: 600; }
        input {
            width: 100%;
            padding: 12px;
            border: 1px solid #d1d5db;
            border-radius: 7px;
            font-size: 15px;
            margin-bottom: 16px;
        }
        button, .back {
            display: inline-block;
            padding: 11px 16px;
            border: 0;
            border-radius: 7px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
        }
        button { background: #2563eb; color: #fff; }
        .back { background: #e5e7eb; color: #1f2937; margin-left: 6px; }
        .message { padding: 12px; border-radius: 7px; margin-bottom: 16px; }
        .error { background: #fee2e2; color: #991b1b; }
    </style>
</head>
<body>
<div class="container">
    <div class="card">
        <h1>✏️ Edit Course</h1>
        <p>ALFA COMPUTER — Course Management</p>

        <?php if ($message !== ""): ?>
            <div class="message error">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="edit-course.php">
            <input type="hidden" name="id"
                   value="<?php echo (int)$course_id; ?>">

            <label for="course_name">Course Name</label>
            <input
                type="text"
                id="course_name"
                name="course_name"
                maxlength="150"
                required
                value="<?php
                    echo htmlspecialchars(
                        $_SERVER["REQUEST_METHOD"] === "POST"
                            ? ($_POST["course_name"] ?? "")
                            : $course["course_name"]
                    );
                ?>"
            >

            <button type="submit">💾 Update Course</button>
            <a class="back" href="add-course.php">Cancel</a>
        </form>
    </div>
</div>
</body>
</html>
