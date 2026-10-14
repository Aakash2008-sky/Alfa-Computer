<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit;
}

require_once "config/database.php";

$message = "";
$message_type = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $course_name = trim($_POST["course_name"] ?? "");

    if ($course_name === "") {
        $message = "Please enter a course name.";
        $message_type = "error";
    } elseif (mb_strlen($course_name) > 150) {
        $message = "Course name cannot exceed 150 characters.";
        $message_type = "error";
    } else {
        $stmt = $conn->prepare(
            "INSERT INTO courses (course_name) VALUES (?)"
        );

        if (!$stmt) {
            $message = "Unable to prepare the database query.";
            $message_type = "error";
        } else {
            $stmt->bind_param("s", $course_name);

            if ($stmt->execute()) {
                $message = "Course added successfully!";
                $message_type = "success";
            } elseif ($stmt->errno === 1062) {
                $message = "This course already exists.";
                $message_type = "error";
            } else {
                $message = "Unable to add course. Please try again.";
                $message_type = "error";
            }

            $stmt->close();
        }
    }
}

$courses = [];
$result = $conn->query(
    "SELECT id, course_name, created_at
     FROM courses
     ORDER BY course_name ASC"
);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $courses[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Course | ALFA COMPUTER</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 30px 16px;
            font-family: Arial, sans-serif;
            background: #f4f7fb;
            color: #1f2937;
        }

        .container {
            max-width: 760px;
            margin: 0 auto;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 22px;
        }

        h1 {
            margin: 0;
            font-size: 26px;
        }

        .subtitle {
            color: #6b7280;
            margin-top: 7px;
        }

        .card {
            background: #fff;
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 3px 14px rgba(0, 0, 0, .06);
            margin-bottom: 22px;
        }

        label {
            display: block;
            margin-bottom: 9px;
            font-weight: 600;
        }

        input {
            width: 100%;
            padding: 12px;
            border: 1px solid #d1d5db;
            border-radius: 7px;
            font-size: 15px;
            margin-bottom: 15px;
        }

        button,
        .link {
            display: inline-block;
            border: 0;
            border-radius: 7px;
            padding: 11px 17px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
        }

        button {
            background: #2563eb;
            color: white;
        }

        .link {
            background: #e5e7eb;
            color: #1f2937;
        }

        .message {
            padding: 12px;
            border-radius: 7px;
            margin-bottom: 16px;
        }

        .success {
            background: #dcfce7;
            color: #166534;
        }

        .error {
            background: #fee2e2;
            color: #991b1b;
        }

        .course-row {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            padding: 13px 0;
            border-bottom: 1px solid #e5e7eb;
        }

        .course-row:last-child {
            border-bottom: 0;
        }

        .count {
            color: #6b7280;
            font-size: 14px;
            margin-top: 7px;
        }

        .course-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            padding: 13px 0;
            border-bottom: 1px solid #e5e7eb;
        }

        .course-actions {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .action-btn {
            display: inline-block;
            padding: 8px 11px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            white-space: nowrap;
        }

        .edit-btn {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .remove-btn {
            background: #fee2e2;
            color: #b91c1c;
        }

        button.remove-btn {
            border: none;
            cursor: pointer;
            font-family: inherit;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <div>
                <h1>Add Course</h1>
                <div class="subtitle">ALFA COMPUTER — Course Management</div>
            </div>
            <a class="link" href="dashboard.php">Back to Dashboard</a>
        </div>

        <div class="card">
            <h2>Add New Course</h2>

            <?php if ($message !== ""): ?>
                <div class="message <?php echo htmlspecialchars($message_type); ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="add-course.php">
                <label for="course_name">Course Name</label>
                <input
                    type="text"
                    name="course_name"
                    id="course_name"
                    maxlength="150"
                    placeholder="Enter course name"
                    required>
                <button type="submit">Add Course</button>
            </form>
        </div>

        <div class="card">
            <h2>Available Courses</h2>
            <div class="count">
                Total Courses: <?php echo count($courses); ?>
            </div>

            <?php if (count($courses) > 0): ?>
                <?php foreach ($courses as $course): ?>
                    <div class="course-row">

                        <div class="course-info">
                            <strong>
                                <?php echo htmlspecialchars($course["course_name"]); ?>
                            </strong>
                        </div>

                        <div class="course-actions">
                            <a
                                class="action-btn edit-btn"
                                href="edit-course.php?id=<?php echo (int)$course["id"]; ?>">✏️ Edit</a>


                            <form
                                method="POST"
                                action="remove-course.php"
                                onsubmit="return confirm('Are you sure you want to remove this course?');"
                                style="display:inline;">
                                <input
                                    type="hidden"
                                    name="id"
                                    value="<?php echo (int)$course['id']; ?>">

                                <button
                                    type="submit"
                                    class="action-btn remove-btn">🗑️ Remove</button>
                            </form>

                        </div>

                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No courses have been added yet.</p>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>