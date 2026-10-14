<?php
session_start();

// 1. Session & Auth Check
if (!isset($_SESSION["user_id"])) {
    header("Location: ../index.php");
    exit;
}

// Direct access prevention
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../settings.php");
    exit;
}

require_once __DIR__ . "/../config/database.php";

$user_id = (int)$_SESSION["user_id"];
$action  = $_POST['action'] ?? '';

// -------------------------------------------------------------------------
// 2. UPDATE USERNAME ACTION
// -------------------------------------------------------------------------
if ($action === 'username') {
    $new_username = trim($_POST['new_username'] ?? '');

    // Check empty
    if (empty($new_username)) {
        header("Location: ../settings.php?error=" . urlencode("Username cannot be empty."));
        exit;
    }

    // Check unique username (excluding current user)
    $check_stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ? LIMIT 1");
    if ($check_stmt) {
        $check_stmt->bind_param("si", $new_username, $user_id);
        $check_stmt->execute();
        $check_stmt->store_result();
        
        if ($check_stmt->num_rows > 0) {
            $check_stmt->close();
            header("Location: ../settings.php?error=" . urlencode("Username is already taken by another user."));
            exit;
        }
        $check_stmt->close();
    }

    // Perform Update
    $update_stmt = $conn->prepare("UPDATE users SET username = ? WHERE id = ?");
    if ($update_stmt) {
        $update_stmt->bind_param("si", $new_username, $user_id);
        
        if ($update_stmt->execute()) {
            $_SESSION["username"] = $new_username;
            $update_stmt->close();
            header("Location: ../settings.php?success=" . urlencode("Username updated successfully."));
            exit;
        } else {
            $update_stmt->close();
            header("Location: ../settings.php?error=" . urlencode("Failed to update username. Please try again."));
            exit;
        }
    }
}

// -------------------------------------------------------------------------
// 3. UPDATE PASSWORD ACTION
// -------------------------------------------------------------------------
if ($action === 'password') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password     = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Field validation
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        header("Location: ../settings.php?error=" . urlencode("All password fields are required."));
        exit;
    }

    if ($new_password !== $confirm_password) {
        header("Location: ../settings.php?error=" . urlencode("New password and confirm password do not match."));
        exit;
    }

    if (strlen($new_password) < 6) {
        header("Location: ../settings.php?error=" . urlencode("New password must be at least 6 characters long."));
        exit;
    }

    // Fetch existing password hash
    $pwd_stmt = $conn->prepare("SELECT password FROM users WHERE id = ? LIMIT 1");
    if ($pwd_stmt) {
        $pwd_stmt->bind_param("i", $user_id);
        $pwd_stmt->execute();
        $res = $pwd_stmt->get_result();
        
        if ($row = $res->fetch_assoc()) {
            $hashed_password = $row['password'];

            // Verify current password
            if (!password_verify($current_password, $hashed_password)) {
                $pwd_stmt->close();
                header("Location: ../settings.php?error=" . urlencode("Current password is incorrect."));
                exit;
            }
        } else {
            $pwd_stmt->close();
            header("Location: ../settings.php?error=" . urlencode("User record not found."));
            exit;
        }
        $pwd_stmt->close();
    }

    // Update password hash
    $new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    $update_pwd_stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
    
    if ($update_pwd_stmt) {
        $update_pwd_stmt->bind_param("si", $new_hashed_password, $user_id);
        
        if ($update_pwd_stmt->execute()) {
            $update_pwd_stmt->close();
            header("Location: ../settings.php?success=" . urlencode("Password updated successfully."));
            exit;
        } else {
            $update_pwd_stmt->close();
            header("Location: ../settings.php?error=" . urlencode("Failed to update password. Please try again."));
            exit;
        }
    }
}

// Fallback redirect
header("Location: ../settings.php");
exit;