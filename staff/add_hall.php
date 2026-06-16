<?php
include('../config/db.php');
session_start();

if (!isset($_SESSION['staff_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

if (isset($_POST['add_hall'])) {

    $hall_name = $_POST['hall_name'];
    $hall_capacity = $_POST['hall_capacity'];
    $price = $_POST['price'];
    $status = $_POST['status'];

    mysqli_query($conn, "
        INSERT INTO hall (hall_name, hall_capacity, price, status)
        VALUES ('$hall_name','$hall_capacity','$price','$status')
    ");

    header("Location: manage_hall.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Hall</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>

<div class="layout">

    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="sidebar-brand">
            <div class="brand-icon">◆</div>
            <span class="brand-name">HallBook</span>
        </div>

        <nav class="sidebar-nav">
            <a href="staff_dashboard.php" class="nav-item">
                <span>📊</span> Dashboard
            </a>
            <a href="manage_hall.php" class="nav-item active">
                <span>🏛️</span> Manage Halls
            </a>
            <a href="manage_bookings.php" class="nav-item">
                <span>📋</span> Manage Bookings
            </a>
        </nav>

        <div class="sidebar-footer">
            <div class="staff-info">
                <div class="staff-avatar"><?php echo strtoupper(substr($_SESSION['name'],0,1)); ?></div>
                <div>
                    <div class="staff-name"><?php echo $_SESSION['name']; ?></div>
                    <div class="staff-role">Staff</div>
                </div>
            </div>

            <a class="logout-btn" href="../auth/logout.php">Logout</a>
        </div>
    </aside>

    <!-- MAIN -->
    <main class="main-content">

        <div class="topbar">
            <h1 class="page-title">Add New Hall</h1>
            <p class="page-subtitle">Fill in hall details below</p>
        </div>

        <div class="form-wrapper">

            <form method="POST" class="form-card">

                <label>Hall Name<span class="required">*</span></label>
                <input type="text" name="hall_name" placeholder="e.g Grand Ballroom" required>

                <label>Capacity<span class="required">*</span></label>
                <input type="number" name="hall_capacity" placeholder="e.g 300" required>

                <label>Price (RM)<span class="required">*</span></label>
                <input type="number" name="price" placeholder="e.g 1500" required>

                <label>Status<span class="required">*</span></label>
                <select name="status">
                    <option value="Available">Available</option>
                    <option value="Unavailable">Unavailable</option>
                    <option value="Maintenance">Maintenance</option>
                </select>

                <button type="submit" name="add_hall" class="btn-primary full-btn">
                    Save Hall
                </button>

                <a href="manage_hall.php" class="back-link">← Back</a>

            </form>

        </div>

    </main>
</div>

</body>
</html>