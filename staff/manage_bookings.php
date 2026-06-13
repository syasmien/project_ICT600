<?php
include('../config/db.php');
session_start();

if (!isset($_SESSION['staff_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

/* =========================
   APPROVE BOOKING
========================= */
if (isset($_GET['approve'])) {

    $id = $_GET['approve'];

    mysqli_query($conn, "UPDATE booking SET status='Approved' WHERE booking_id=$id");
    header("Location: manage_bookings.php");
    exit();
}

/* =========================
   REJECT BOOKING
========================= */
if (isset($_GET['reject'])) {

    $id = $_GET['reject'];

    mysqli_query($conn, "UPDATE booking SET status='Rejected' WHERE booking_id=$id");
    header("Location: manage_bookings.php");
    exit();
}

/* =========================
   FILTER STATUS
========================= */
$statusFilter = "";

if (isset($_GET['status']) && $_GET['status'] != "") {
    $statusFilter = $_GET['status'];
    $sql = "SELECT * FROM booking WHERE status='$statusFilter' ORDER BY booking_id DESC";
} else {
    $sql = "SELECT * FROM booking ORDER BY booking_id DESC";
}

$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Bookings</title>

    <style>

        body{
            font-family: Arial;
            background:#f4f6f9;
            margin:0;
        }

        .container{
            padding:30px;
        }

        table{
            width:100%;
            border-collapse: collapse;
            background:white;
        }

        th, td{
            padding:10px;
            border:1px solid #ddd;
            text-align:center;
        }

        th{
            background:#2c3e50;
            color:white;
        }

        .btn{
            padding:6px 10px;
            text-decoration:none;
            border-radius:5px;
            color:white;
        }

        .approve{
            background:green;
        }

        .reject{
            background:red;
        }

        .filter-box{
            margin-bottom:20px;
        }

        select{
            padding:8px;
        }

        button{
            padding:8px 12px;
            cursor:pointer;
        }

    </style>

</head>
<body>

<div class="container">

<h2>Manage Bookings</h2>

<!-- FILTER -->
<div class="filter-box">

    <form method="GET">

        <select name="status">
            <option value="">All</option>
            <option value="Pending" <?php if($statusFilter=="Pending") echo "selected"; ?>>Pending</option>
            <option value="Approved" <?php if($statusFilter=="Approved") echo "selected"; ?>>Approved</option>
            <option value="Rejected" <?php if($statusFilter=="Rejected") echo "selected"; ?>>Rejected</option>
        </select>

        <button type="submit">Filter</button>

    </form>

</div>

<!-- TABLE -->
<table>

    <tr>
        <th>ID</th>
        <th>Customer ID</th>
        <th>Hall ID</th>
        <th>Event Type</th>
        <th>Date</th>
        <th>Guest</th>
        <th>Status</th>
        <th>Action</th>
    </tr>

    <?php while($row = mysqli_fetch_assoc($result)) { ?>

    <tr>
        <td><?php echo $row['booking_id']; ?></td>
        <td><?php echo $row['cust_id']; ?></td>
        <td><?php echo $row['hall_id']; ?></td>
        <td><?php echo $row['event_type']; ?></td>
        <td><?php echo $row['event_date']; ?></td>
        <td><?php echo $row['guest_count']; ?></td>
        <td><?php echo $row['status']; ?></td>

        <td>

            <a class="btn approve" href="manage_bookings.php?approve=<?php echo $row['booking_id']; ?>">
                Approve
            </a>

            <a class="btn reject" href="manage_bookings.php?reject=<?php echo $row['booking_id']; ?>">
                Reject
            </a>

        </td>
    </tr>

    <?php } ?>

</table>

</div>

</body>
</html>