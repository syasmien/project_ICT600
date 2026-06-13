<?php
include('../config/db.php');

$error = "";
$success = "";

if (isset($_POST['register'])) {

    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $username = $_POST['username'];
    $phone_num = $_POST['phone_num'];
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
                $success = "Registration successful! You can now login.";
            } else {
                $error = "Error: " . mysqli_error($conn);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register</title>
</head>
<body>

<h2>Customer Registration</h2>

<form method="POST">

    <label>Full Name:</label><br>
    <input type="text" name="full_name" required>
    <br><br>

    <label>Email:</label><br>
    <input type="email" name="email" required>
    <br><br>

    <label>Username:</label><br>
    <input type="text" name="username" required>
    <br><br>

    <label>Phone Number:</label><br>
    <input type="text" name="phone_num" required>
    <br><br>

    <label>Password:</label><br>
    <input type="password" name="password" required>
    <br><br>

    <label>Confirm Password:</label><br>
    <input type="password" name="confirm_password" required>
    <br><br>

    <button type="submit" name="register">Register</button>

</form>



<p style="color:red;">
    <?php echo $error; ?>
</p>

<p style="color:green;">
    <?php echo $success; ?>
</p>

</body>
</html>