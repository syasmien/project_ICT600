<?php
include('../config/db.php');
session_start();

$error = "";

if (isset($_POST['login'])) {

    $userType = $_POST['user_type']; // customer or staff
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password']; // Get raw password for verification checks

    if ($userType == "customer") {
        // Matches your database structure: email, username, full_name, password, cust_id
        $sql = "SELECT * FROM customer WHERE email='$username' OR username='$username'";
        $result = mysqli_query($conn, $sql);

        if ($result && mysqli_num_rows($result) == 1) {
            $row = mysqli_fetch_assoc($result);

            // Verify password: Check both securely hashed passwords and plain text fallbacks
            if (password_verify($password, $row['password']) || $password === $row['password']) {
                
                // Set identical session keys expected by customer pages
                $_SESSION['user_id'] = $row['cust_id'];
                // Set display name to full_name, fall back to username if full_name is empty
                $_SESSION['user_name'] = !empty($row['full_name']) ? $row['full_name'] : $row['username']; 
                $_SESSION['user_email'] = $row['email'];
                $_SESSION['user_role'] = "Customer";

                header("Location: ../customer/dashboard.php");
                exit();
            } else {
                $error = "Invalid password details!";
            }
        } else {
            $error = "Invalid customer username or email!";
        }

    } else if ($userType == "staff") {
        // Find staff by email or name (Matches event_hall_booking.sql schema for staff: staff_id, staff_name, staff_email, password)
        $sql = "SELECT * FROM staff WHERE staff_email='$username' OR staff_name='$username'";
        $result = mysqli_query($conn, $sql);

        if ($result && mysqli_num_rows($result) == 1) {
            $row = mysqli_fetch_assoc($result);

            // Verify password: Check both securely hashed passwords and plain text fallbacks
            if (password_verify($password, $row['password']) || $password === $row['password']) {
                
                $_SESSION['staff_id'] = $row['staff_id'];
                $_SESSION['name'] = $row['staff_name'];
                $_SESSION['user_role'] = "Staff";

                header("Location: ../staff/staff_dashboard.php");
                exit();
            } else {
                $error = "Invalid password details!";
            }
        } else {
            $error = "Invalid staff login details!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - HallBook</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #3b82f6;
            --primary-hover: #2563eb;
            --bg-color: #f4f7f6;
            --text-main: #334155;
            --white: #ffffff;
            --border-color: #e2e8f0;
            --danger: #ef4444;
            --text-muted: #64748b;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Inter', sans-serif;
        }

        body {
            background-color: var(--bg-color);
            color: var(--text-main);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
        }

        .login-card {
            background: var(--white);
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            max-width: 420px;
            width: 100%;
            border: 1px solid var(--border-color);
        }

        .brand-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .brand-logo {
            background-color: var(--primary);
            width: 36px;
            height: 36px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            color: white;
            transform: rotate(45deg);
            margin-bottom: 12px;
        }

        .brand-logo span {
            transform: rotate(-45deg);
        }

        .brand-header h2 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1e293b;
        }

        .brand-header p {
            font-size: 0.9rem;
            color: var(--text-muted);
            margin-top: 4px;
        }

        .alert-error {
            background-color: #fee2e2;
            color: var(--danger);
            border: 1px solid #fca5a5;
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 500;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 0.85rem;
            color: var(--text-muted);
            font-weight: 600;
        }

        .radio-group {
            display: flex;
            gap: 16px;
            margin-top: 4px;
        }

        .radio-label {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 500;
            color: #475569;
        }

        .radio-label input {
            accent-color: var(--primary);
            width: 16px;
            height: 16px;
        }

        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 0.95rem;
            color: #334155;
            background-color: #f8fafc;
            outline: none;
            transition: all 0.2s ease;
        }

        .form-control:focus {
            border-color: var(--primary);
            background-color: #ffffff;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .btn-login {
            width: 100%;
            background-color: var(--primary);
            color: white;
            border: none;
            padding: 14px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            transition: background-color 0.2s ease;
            margin-top: 10px;
            margin-bottom: 20px;
        }

        .btn-login:hover {
            background-color: var(--primary-hover);
        }

        /* Added Styling for Registration Link */
        .register-link {
            text-align: center;
            font-size: 0.875rem;
            color: var(--text-muted);
        }

        .register-link a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
        }

        .register-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="brand-header">
        <div class="brand-logo"><span>♦</span></div>
        <h2>Welcome to HallBook</h2>
        <p>Log in to access your dashboard</p>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="form-group">
            <label>Login As</label>
            <div class="radio-group">
                <label class="radio-label">
                    <input type="radio" name="user_type" value="customer" checked required> Customer
                </label>
                <label class="radio-label">
                    <input type="radio" name="user_type" value="staff" required> Staff
                </label>
            </div>
        </div>

        <div class="form-group">
            <label for="username">Email / Username</label>
            <input type="text" id="username" name="username" class="form-control" placeholder="Enter your email or username" required>
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" class="form-control" placeholder="Enter password" required>
        </div>

        <button type="submit" name="login" class="btn-login">Sign In</button>
        
        <div class="register-link">
            Don't have an account? <a href="register.php">Register here</a>
        </div>
    </form>
</div>

</body>
</html>