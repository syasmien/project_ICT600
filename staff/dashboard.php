<?php
session_start();
include('../config/db.php');

// check if staff logged in
if (!isset($_SESSION['staff_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$staff_name = $_SESSION['name'];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Staff Dashboard</title>

    <style>

        body{
            font-family: Arial, sans-serif;
            margin: 0;
            background-color: #f4f6f9;
        }

        .navbar{
            background-color: #2c3e50;
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .container{
            padding: 40px;
        }

        .welcome{
            font-size: 28px;
            margin-bottom: 20px;
        }

        .card-container{
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }

        .card{
            background-color: white;
            padding: 25px;
            border-radius: 10px;
            width: 250px;
            box-shadow: 0px 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }

        .card h3{
            margin-bottom: 10px;
        }

        .card a{
            text-decoration: none;
            display: inline-block;
            margin-top: 10px;
            padding: 10px 15px;
            background-color: #3498db;
            color: white;
            border-radius: 5px;
        }

        .logout{
            background-color: #e74c3c;
            padding: 8px 12px;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }

    </style>

</head>
<body>

<!-- NAVBAR -->
<div class="navbar">

    <div>
        <h3>Staff Dashboard</h3>
    </div>

    <div>
        Welcome, <?php echo $staff_name; ?> |
        <a class="logout" href="../auth/logout.php">Logout</a>
    </div>

</div>

<!-- MAIN CONTENT -->
<div class="container">

    <div class="welcome">
        Hello Staff 👋
    </div>

    <p>Manage event halls and booking approvals from here.</p>

    <div class="card-container">

        <div class="card">
            <h3>Manage Halls</h3>
            <p>Add, update or delete event halls</p>
            <a href="manage_hall.php">Go</a>
        </div>

        <div class="card">
            <h3>Manage Bookings</h3>
            <p>Approve or reject customer bookings</p>
            <a href="manage_bookings.php">Go</a>
        </div>

    </div>

</div>

</body>
</html>