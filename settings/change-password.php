<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../index.php");
    exit;
}

require_once "../config/database.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $user_id = (int)$_SESSION["user_id"];
    $current_password = $_POST["current_password"] ?? "";
    $new_password = $_POST["new_password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";

    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        header("Location: ../settings.php?error=" . urlencode("All password fields are required"));
        exit;
    }

    if ($new_password !== $confirm_password) {
        header("Location: ../settings.php?error=" . urlencode("Passwords do not match"));
        exit;
    }

    if (strlen($new_password) < 6) {
        header("Location: ../settings.php?error=" . urlencode("New password must be at least 6 characters long"));
        exit;
    }

    // 1. Fetch existing user record
    $sql = "SELECT password FROM users WHERE id = ? LIMIT 1";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            $hash = $user["password"];

            // 2. Verify Current Password
            if (password_verify($current_password, $hash)) {
                $stmt->close();

                // 3. Hash New Password securely
                $new_hash = password_hash($new_password, PASSWORD_BCRYPT);

                $update_sql = "UPDATE users SET password = ? WHERE id = ?";
                if ($update_stmt = $conn->prepare($update_sql)) {
                    $update_stmt->bind_param("si", $new_hash, $user_id);

                    if ($update_stmt->execute()) {
                        $update_stmt->close();
                        $conn->close();
                        header("Location: ../settings.php?success=" . urlencode("Password changed successfully"));
                        exit;
                    } else {
                        $update_stmt->close();
                        $conn->close();
                        header("Location: ../settings.php?error=" . urlencode("Unable to update password"));
                        exit;
                    }
                }
            } else {
                $stmt->close();
                $conn->close();
                header("Location: ../settings.php?error=" . urlencode("Current password is incorrect"));
                exit;
            }
        } else {
            $stmt->close();
            $conn->close();
            header("Location: ../settings.php?error=" . urlencode("User record not found"));
            exit;
        }
    } else {
        $conn->close();
        header("Location: ../settings.php?error=" . urlencode("Database query error"));
        exit;
    }
} else {
    header("Location: ../settings.php");
    exit;
}
