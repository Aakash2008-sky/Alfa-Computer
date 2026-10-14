<?php
session_start();

if (isset($_SESSION["user_id"])) {
    header("Location: dashboard.php");
    exit;
}

// Session security check
if (!isset($_SESSION["reset_user_id"])) {
    header("Location: forgot-password.php");
    exit;
}

require_once "config/database.php";

$errorMsg   = $_GET['error'] ?? '';
$successMsg = $_GET['success'] ?? '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $user_id          = (int)$_SESSION["reset_user_id"];
    $new_password     = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($new_password) || empty($confirm_password)) {
        $errorMsg = "Please fill in all fields.";
    } elseif ($new_password !== $confirm_password) {
        $errorMsg = "Passwords do not match.";
    } elseif (strlen($new_password) < 6) {
        $errorMsg = "Password must be at least 6 characters long.";
    } else {
        // Hash and Update password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        $upd_stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        if ($upd_stmt) {
            $upd_stmt->bind_param("si", $hashed_password, $user_id);
            if ($upd_stmt->execute()) {
                $upd_stmt->close();

                // Clear OTP record
                $del_otp = $conn->prepare("DELETE FROM password_resets WHERE user_id = ?");
                if ($del_otp) {
                    $del_otp->bind_param("i", $user_id);
                    $del_otp->execute();
                    $del_otp->close();
                }

                // Destroy reset session parameters
                unset($_SESSION["reset_email"]);
                unset($_SESSION["reset_user_id"]);

                header("Location: index.php?success=" . urlencode("Password reset successfully. Please login."));
                exit;
            } else {
                $upd_stmt->close();
                $errorMsg = "Failed to update password. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ALFA COMPUTER — Set New Password</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            background-color: var(--bg-main, #f8fafc);
            font-family: 'Inter', sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            padding: 1rem;
        }

        .auth-card {
            background: var(--surface, #ffffff);
            border: 1px solid var(--border, #e2e8f0);
            border-radius: var(--radius-lg, 12px);
            padding: 2.5rem;
            width: 100%;
            max-width: 420px;
            box-shadow: var(--shadow-sm, 0 1px 3px rgba(0,0,0,0.1));
        }

        .auth-brand {
            text-align: center;
            margin-bottom: 1.5rem;
        }

        .brand-logo-text {
            font-weight: 800;
            font-size: 1.5rem;
            letter-spacing: -0.04em;
            color: var(--text-main, #0f172a);
            display: inline-flex;
            flex-direction: column;
            line-height: 1;
        }

        .brand-logo-text .sub-brand {
            font-size: 0.65rem;
            font-weight: 600;
            letter-spacing: 0.22em;
            color: var(--primary, #4f46e5);
            margin-top: 4px;
        }

        .auth-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-main, #0f172a);
            text-align: center;
            margin-bottom: 0.25rem;
        }

        .auth-subtitle {
            font-size: 0.875rem;
            color: var(--text-muted, #64748b);
            text-align: center;
            margin-bottom: 1.5rem;
        }

        .alert {
            padding: 0.75rem 1rem;
            border-radius: var(--radius-md, 6px);
            font-size: 0.8125rem;
            font-weight: 500;
            margin-bottom: 1.25rem;
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            color: #991b1b;
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            color: #065f46;
            border: 1px solid rgba(16, 185, 129, 0.2);
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-label {
            display: block;
            font-size: 0.8125rem;
            font-weight: 600;
            color: var(--text-muted, #64748b);
            margin-bottom: 0.5rem;
        }

        .form-control {
            width: 100%;
            padding: 0.625rem 0.875rem;
            font-size: 0.875rem;
            font-family: inherit;
            color: var(--text-main, #0f172a);
            background: var(--surface, #ffffff);
            border: 1px solid var(--border, #e2e8f0);
            border-radius: var(--radius-md, 6px);
            box-sizing: border-box;
            transition: var(--transition, all 0.2s);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary, #4f46e5);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }

        .btn-primary {
            width: 100%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.625rem 1.25rem;
            font-size: 0.875rem;
            font-weight: 600;
            font-family: inherit;
            color: #ffffff;
            background: var(--primary, #4f46e5);
            border: none;
            border-radius: var(--radius-md, 6px);
            cursor: pointer;
            transition: var(--transition, all 0.2s);
        }

        .btn-primary:hover {
            opacity: 0.9;
        }
    </style>
</head>
<body>

    <div class="auth-card">
        <div class="auth-brand">
            <div class="brand-logo-text">
                ALFA
                <span class="sub-brand">COMPUTER</span>
            </div>
        </div>

        <h1 class="auth-title">New Password</h1>
        <p class="auth-subtitle">Create a new password for your account</p>

        <?php if (!empty($errorMsg)): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($errorMsg); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($successMsg)): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($successMsg); ?>
            </div>
        <?php endif; ?>

        <form action="reset-password.php" method="POST">
            <div class="form-group">
                <label for="new_password" class="form-label">New Password</label>
                <input 
                    type="password" 
                    id="new_password" 
                    name="new_password" 
                    class="form-control" 
                    placeholder="Minimum 6 characters" 
                    required
                >
            </div>

            <div class="form-group">
                <label for="confirm_password" class="form-label">Confirm New Password</label>
                <input 
                    type="password" 
                    id="confirm_password" 
                    name="confirm_password" 
                    class="form-control" 
                    placeholder="Re-enter new password" 
                    required
                >
            </div>

            <button type="submit" class="btn-primary">Reset Password</button>
        </form>
    </div>

</body>
</html>