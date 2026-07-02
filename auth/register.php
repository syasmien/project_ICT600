<?php
include('../config/db.php');

$error = "";
$success = "";

if (isset($_POST['register'])) {

    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $phone_num = $_POST['phone_num'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // check password match
    if ($password != $confirm_password) {
        $error = "Password does not match!";
    } else {

        // check email exists
        $check = "SELECT * FROM customer WHERE email='$email'";
        $result = mysqli_query($conn, $check);

        if (mysqli_num_rows($result) > 0) {
            $error = "Email already exists! Please login.";
        } else {

            // insert data
            $sql = "INSERT INTO customer (full_name, email, username, password, phone_num)
                    VALUES ('$full_name', '$email', '$username', '$password', '$phone_num')";

            if (mysqli_query($conn, $sql)) {
                $success = "Registration successful! Redirecting to login...";
                header("refresh:2;url=login.php");
            } else {
                $error = "Error: " . mysqli_error($conn);
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
    <title>Register - HallBook</title>
    <style>
        :root {
            --primary-color: #3b82f6; /* Modern blue accent from your design */
            --text-main: #1e293b;
            --text-muted: #64748b;
            --bg-body: #f8fafc;
            --border-color: #e2e8f0;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background-color: var(--bg-body);
            color: var(--text-main);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
            box-sizing: border-box;
        }

        .register-container {
            background-color: #ffffff;
            padding: 40px 30px;
            border-radius: 12px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.05);
            width: 100%;
            max-width: 440px;
            box-sizing: border-box;
        }

        /* Logo Icon Styling based on your image */
        .logo-wrapper {
            text-align: center;
            margin-bottom: 24px;
        }

        .logo-icon {
            width: 48px;
            height: 48px;
            background-color: var(--primary-color);
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transform: rotate(45deg);
            margin-bottom: 16px;
        }

        .logo-icon::before {
            content: '';
            width: 12px;
            height: 12px;
            background-color: #ffffff;
            transform: rotate(-45deg);
            border-radius: 2px;
        }

        h2 {
            margin: 0 0 6px 0;
            font-size: 26px;
            font-weight: 700;
            color: var(--text-main);
            text-align: center;
        }

        .login-link {
            font-size: 14px;
            color: var(--text-muted);
            margin-bottom: 28px;
            text-align: center;
        }

        .login-link a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
        }

        .login-link a:hover {
            text-decoration: underline;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: #475569;
            margin-bottom: 8px;
        }

        input[type="text"],
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            box-sizing: border-box;
            font-size: 15px;
            background-color: #f8fafc;
            color: var(--text-main);
            transition: all 0.2s ease;
        }

        input[type="text"]:focus,
        input[type="email"]:focus,
        input[type="password"]:focus {
            outline: none;
            border-color: var(--primary-color);
            background-color: #ffffff;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
        }

        input::placeholder {
            color: #94a3b8;
        }

        button {
            width: 100%;
            padding: 14px;
            background-color: var(--primary-color);
            color: #ffffff;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            margin-top: 10px;
            transition: background-color 0.2s ease;
        }

        button:hover {
            background-color: #2563eb;
        }

        .message {
            margin-top: 15px;
            font-size: 14px;
            text-align: center;
            font-weight: 500;
        }
    </style>
</head>
<body>

<div class="register-container">

    <div class="logo-wrapper">
        <div class="logo-icon"></div>
        <h2>Create Account</h2>
    </div>
    
    <div class="login-link">
        Already Registered? <a href="login.php">Log in here</a>
    </div>

    <form method="POST">

        <div class="form-group">
            <label>Full Name</label>
            <input type="text" name="full_name" placeholder="Enter your full name" required>
        </div>

        <div class="form-group">
            <label>Email Address</label>
            <input type="email" name="email" placeholder="Enter your email" required>
        </div>

        <div class="form-group">
            <label>Phone Number</label>
            <input type="text" name="phone_num" placeholder="Enter your phone number" required>
        </div>

        <div class="form-group">
            <label>Username</label>
            <input type="text" name="username" placeholder="Choose a username" required>
        </div>

        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" placeholder="Create a password" required>
        </div>

        <div class="form-group">
            <label>Confirm Password</label>
            <input type="password" name="confirm_password" placeholder="Confirm your password" required>
        </div>

        <button type="submit" name="register">Sign Up</button>

    </form>

    <?php if (!empty($error)): ?>
        <p class="message" style="color: #ef4444;"><?php echo $error; ?></p>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <p class="message" style="color: #10b981;"><?php echo $success; ?></p>
    <?php endif; ?>

</div>

</body>
</html>