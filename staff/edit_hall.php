<?php
include('../config/db.php');
session_start();

if (!isset($_SESSION['staff_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: manage_hall.php");
    exit();
}

$hall_id = $_GET['id'];

$result = mysqli_query($conn, "SELECT * FROM hall WHERE hall_id = $hall_id");
$hall = mysqli_fetch_assoc($result);

if (!$hall) {
    echo "Hall not found!";
    exit();
}

if (isset($_POST['update'])) {

    $hall_name = $_POST['hall_name'];
    $hall_capacity = $_POST['hall_capacity'];
    $price = $_POST['price'];
    $status = $_POST['status'];

    mysqli_query($conn, "
        UPDATE hall SET
            hall_name='$hall_name',
            hall_capacity='$hall_capacity',
            price='$price',
            status='$status'
        WHERE hall_id=$hall_id
    ");

    header("Location: manage_hall.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Hall</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="../assets/css/staff_style.css">
</head>

<body>

<div class="layout">

    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="sidebar-brand">
            <div class="brand-icon">&#9670;</div>
            <span class="brand-name">HallBook</span>
        </div>

        <nav class="sidebar-nav">
            <a href="staff_dashboard.php" class="nav-item">
                <span class="nav-icon">&#9783;</span>
                <span class="nav-label">Dashboard</span>
            </a>
            <a href="manage_hall.php" class="nav-item active">
                <span class="nav-icon">&#9962;</span>
                <span class="nav-label">Manage Halls</span>
            </a>
            <a href="manage_bookings.php" class="nav-item">
                <span class="nav-icon">&#128203;</span>
                <span class="nav-label">Manage Bookings</span>
            </a>
        </nav>

        <div class="sidebar-footer">
            <div class="staff-info">
                <div class="staff-avatar"><?php echo strtoupper(substr($_SESSION['name'], 0, 1)); ?></div>
                <div class="staff-details">
                    <span class="staff-name"><?php echo $_SESSION['name']; ?></span>
                    <span class="staff-role">Staff</span>
                </div>
            </div>
            <a class="logout-btn" href="../auth/logout.php">Logout</a>
        </div>
    </aside>

    <!-- MAIN -->
    <main class="main-content">

        <div class="topbar">
            <div>
                <h1 class="page-title">Edit Hall</h1>
                <p class="page-subtitle">Update hall information</p>
            </div>
        </div>

        <div class="form-wrapper">

            <form method="POST" class="form-card">

                <label>Hall Name</label>
                <input type="text" name="hall_name"
                       value="<?php echo $hall['hall_name']; ?>" required>

                <label>Capacity</label>
                <input type="number" name="hall_capacity"
                       value="<?php echo $hall['hall_capacity']; ?>" required>

                <label>Price (RM)</label>
                <input type="number" name="price"
                       value="<?php echo $hall['price']; ?>" required>

                <label>Status</label>
                <select name="status">
                    <option value="Available" <?php if($hall['status']=="Available") echo "selected"; ?>>Available</option>
                    <option value="Unavailable" <?php if($hall['status']=="Unavailable") echo "selected"; ?>>Unavailable</option>
                    <option value="Maintenance" <?php if($hall['status']=="Maintenance") echo "selected"; ?>>Maintenance</option>
                </select>

                <button type="submit" name="update" class="btn-primary" style="width:100%; margin-top:15px;">
                    Update Hall
                </button>

                <a href="manage_hall.php" class="back-link">← Back</a>

            </form>

        </div>

    </main>
</div>

</body>
</html>