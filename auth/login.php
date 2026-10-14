<?php
session_start();
require_once "../config/database.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";

    if (empty($username) || empty($password)) {
        header("Location: ../index.php?error=" . urlencode("Please fill in all fields."));
        exit;
    }

    // Prepared statement lookup for safe user retrieval
    $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE username = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            // Verify password hash
            if (password_verify($password, $user["password"])) {
                session_regenerate_id(true);
                $_SESSION["user_id"] = $user["id"];
                $_SESSION["username"] = $user["username"];

                $stmt->close();
                header("Location: ../dashboard.php");
                exit;
            }
        }
        $stmt->close();
    }

    // Generic error message for security
    header("Location: ../index.php?error=" . urlencode("Invalid username or password."));
    exit;
} else {
    header("Location: ../index.php");
    exit;
}
