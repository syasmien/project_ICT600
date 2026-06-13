<?php
include('../config/db.php');
session_start();

if (!isset($_SESSION['staff_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

/* =========================
   ADD HALL
========================= */
if (isset($_POST['add_hall'])) {

    $hall_name = $_POST['hall_name'];
    $hall_capacity = $_POST['hall_capacity'];
    $price = $_POST['price'];
    $status = $_POST['status'];

    $sql = "INSERT INTO hall (hall_name, hall_capacity, price, status)
            VALUES ('$hall_name', '$hall_capacity', '$price', '$status')";

    mysqli_query($conn, $sql);
}

/* =========================
   DELETE HALL
========================= */
if (isset($_GET['delete'])) {

    $id = $_GET['delete'];

    mysqli_query($conn, "DELETE FROM hall WHERE hall_id=$id");
    header("Location: manage_hall.php");
    exit();
}

/* =========================
   UPDATE HALL (simple version)
========================= */
if (isset($_POST['update_hall'])) {

    $id = $_POST['hall_id'];
    $hall_name = $_POST['hall_name'];
    $hall_capacity = $_POST['hall_capacity'];
    $price = $_POST['price'];
    $status = $_POST['status'];

    $sql = "UPDATE hall SET 
            hall_name='$hall_name',
            hall_capacity='$hall_capacity',
            price='$price',
            status='$status'
            WHERE hall_id=$id";

    mysqli_query($conn, $sql);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Hall</title>

    <style>
        body { font-family: Arial; background:#f4f6f9; margin:0; }
        .container { padding:30px; }

        table {
            width:100%;
            border-collapse: collapse;
            background:white;
        }

        th, td {
            padding:10px;
            border:1px solid #ddd;
            text-align:center;
        }

        th { background:#2c3e50; color:white; }

        .form-box {
            background:white;
            padding:20px;
            margin-bottom:20px;
        }

        input, select {
            padding:8px;
            margin:5px;
        }

        button {
            padding:8px 12px;
            background:#3498db;
            color:white;
            border:none;
            cursor:pointer;
        }

        .delete {
            background:red;
        }

    </style>

</head>
<body>

<div class="container">

<h2>Manage Halls</h2>

<!-- ADD FORM -->
<div class="form-box">

    <h3>Add New Hall</h3>

    <form method="POST">

        <input type="text" name="hall_name" placeholder="Hall Name" required>

        <input type="number" name="hall_capacity" placeholder="Capacity" required>

        <input type="text" name="price" placeholder="Price" required>

        <select name="status">
            <option value="Available">Available</option>
            <option value="Unavailable">Unavailable</option>
            <option value="Maintenance">Maintenance</option>
        </select>

        <button type="submit" name="add_hall">Add Hall</button>

    </form>

</div>

<!-- TABLE -->
<table>

    <tr>
        <th>ID</th>
        <th>Hall Name</th>
        <th>Capacity</th>
        <th>Price</th>
        <th>Status</th>
        <th>Action</th>
    </tr>

    <?php
    $result = mysqli_query($conn, "SELECT * FROM hall");

    while ($row = mysqli_fetch_assoc($result)) {
    ?>

    <tr>
        <td><?php echo $row['hall_id']; ?></td>
        <td><?php echo $row['hall_name']; ?></td>
        <td><?php echo $row['hall_capacity']; ?></td>
        <td><?php echo $row['price']; ?></td>
        <td><?php echo $row['status']; ?></td>

        <td>
            <a href="manage_hall.php?delete=<?php echo $row['hall_id']; ?>">
                <button class="delete">Delete</button>
            </a>
        </td>
    </tr>

    <?php } ?>

</table>

</div>

</body>
</html>