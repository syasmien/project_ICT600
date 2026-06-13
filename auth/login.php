<?php
include('../config/db.php');
session_start();

$error = "";

if (isset($_POST['login'])) {

    $userType = $_POST['user_type']; // customer or staff
    $username = $_POST['username'];
    $password = $_POST['password'];

    if ($userType == "customer") {

        $sql = "SELECT * FROM customer WHERE (email='$username' OR username='$username') AND password='$password'";
        $result = mysqli_query($conn, $sql);

        if (mysqli_num_rows($result) == 1) {
            $row = mysqli_fetch_assoc($result);

            $_SESSION['cust_id'] = $row['cust_id'];
            $_SESSION['name'] = $row['full_name'];

            header("Location: ../customer/dashboard.php");
            exit();
        } else {
            $error = "Invalid customer login details!";
        }

    } else if ($userType == "staff") {

        $sql = "SELECT * FROM staff WHERE (staff_email='$username' OR username='$username') AND password='$password'";
        $result = mysqli_query($conn, $sql);

        if (mysqli_num_rows($result) == 1) {
            $row = mysqli_fetch_assoc($result);

            $_SESSION['staff_id'] = $row['staff_id'];
            $_SESSION['name'] = $row['staff_name'];

            header("Location: ../staff/dashboard.php");
            exit();
        } else {
            $error = "Invalid staff login details!";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
</head>
<body>

<h2>Login Page</h2>

<form method="POST">

    <label>User Type:</label><br>
    <input type="radio" name="user_type" value="customer" required> Customer
    <input type="radio" name="user_type" value="staff" required> Staff
    <br><br>

    <label>Email / Username:</label><br>
    <input type="text" name="username" required>
    <br><br>

    <label>Password:</label><br>
    <input type="password" name="password" required>
    <br><br>

    <button type="submit" name="login">Login</button>

</form>
<p>
    Don't have an account?
    <a href="register.php">Register here</a>
</p>

<p style="color:red;">
    <?php echo $error; ?>
</p>

</body>
</html>