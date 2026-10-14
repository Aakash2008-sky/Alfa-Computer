<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../signup.php");
    exit;
}

require_once __DIR__ . "/../config/database.php";

$username       = trim($_POST['username'] ?? '');
$email          = trim($_POST['email'] ?? '');
$password       = $_POST['password'] ?? '';
$confirm_pwd    = $_POST['confirm_password'] ?? '';
$security_code  = $_POST['security_code'] ?? '';

// Basic validations
if (empty($username) || empty($email) || empty($password) || empty($confirm_pwd) || empty($security_code)) {
    header("Location: ../signup.php?error=" . urlencode("All fields are required."));
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: ../signup.php?error=" . urlencode("Invalid email address format."));
    exit;
}

if ($password !== $confirm_pwd) {
    header("Location: ../signup.php?error=" . urlencode("Password confirmation does not match."));
    exit;
}

if (strlen($password) < 6) {
    header("Location: ../signup.php?error=" . urlencode("Password must be at least 6 characters long."));
    exit;
}

// Check Security Code (Optional: Adjust or set matching code if defined in system)
// Define your security code if needed or change condition. Assuming 'alfa123' as default or custom check.
// If you have a specific system security check, adjust here.

// 1. Check Username Uniqueness
$check_user = $conn->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
if ($check_user) {
    $check_user->bind_param("s", $username);
    $check_user->execute();
    $check_user->store_result();
    if ($check_user->num_rows > 0) {
        $check_user->close();
        header("Location: ../signup.php?error=" . urlencode("Username is already taken."));
        exit;
    }
    $check_user->close();
}

// 2. Check Email Uniqueness
$check_email = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
if ($check_email) {
    $check_email->bind_param("s", $email);
    $check_email->execute();
    $check_email->store_result();
    if ($check_email->num_rows > 0) {
        $check_email->close();
        header("Location: ../signup.php?error=" . urlencode("Email address is already registered."));
        exit;
    }
    $check_email->close();
}

// Hash password and insert user
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

$stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
if ($stmt) {
    $stmt->bind_param("sss", $username, $email, $hashed_password);
    if ($stmt->execute()) {
        $stmt->close();
        header("Location: ../index.php?success=" . urlencode("Account created successfully. Please log in."));
        exit;
    } else {
        $stmt->close();
        header("Location: ../signup.php?error=" . urlencode("Registration failed. Please try again."));
        exit;
    }
}