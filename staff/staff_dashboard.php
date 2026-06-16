<?php
session_start();
include('../config/db.php');

if (!isset($_SESSION['staff_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$staff_name = $_SESSION['name'];

/* =========================
   DASHBOARD DATA
========================= */

$total_booking = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COUNT(*) AS total FROM booking")
);

$pending = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COUNT(*) AS total FROM booking WHERE status='Pending'")
);

/* =========================
   RECENT BOOKINGS
========================= */

$recent = mysqli_query($conn, "
    SELECT booking.booking_id,
           customer.full_name AS customer_name,
           hall.hall_name,
           booking.event_date,
           booking.status
    FROM booking
    JOIN customer ON booking.cust_id = customer.cust_id
    JOIN hall ON booking.hall_id = hall.hall_id
    ORDER BY booking.booking_id DESC
    LIMIT 5
");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Staff Dashboard</title>
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
            <div class="brand-icon">&#9670;</div>
            <span class="brand-name">HallBook</span>
        </div>

        <nav class="sidebar-nav">
            <a href="staff_dashboard.php" class="nav-item active">
                <span class="nav-icon">&#9783;</span>
                <span class="nav-label">Dashboard</span>
            </a>
            <a href="manage_hall.php" class="nav-item">
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
                <div class="staff-avatar"><?php echo strtoupper(substr($staff_name, 0, 1)); ?></div>
                <div class="staff-details">
                    <span class="staff-name"><?php echo $staff_name; ?></span>
                    <span class="staff-role">Staff</span>
                </div>
            </div>
            <a href="#" class="logout-btn" onclick="openLogoutModal()">Logout</a>
        </div>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="main-content">

        <!-- TOP BAR -->
        <div class="topbar">
            <div>
                <h1 class="page-title">Dashboard</h1>
                <p class="page-subtitle">Welcome back, <?php echo $staff_name; ?></p>
            </div>
        </div>

        <!-- SUMMARY CARDS -->
        <div class="stats">

            <div class="box">
                <div class="box-icon box-icon--blue">&#128202;</div>
                <div class="box-info">
                    <h2><?php echo $total_booking['total']; ?></h2>
                    <p>Total Booking</p>
                </div>
            </div>

            <div class="box">
                <div class="box-icon box-icon--amber">&#9203;</div>
                <div class="box-info">
                    <h2><?php echo $pending['total']; ?></h2>
                    <p>Pending Approval</p>
                </div>
            </div>

        </div>

        <!-- RECENT BOOKING TABLE -->
        <div class="section-header">
            <h3>Recent Bookings</h3>
            <a href="manage_bookings.php" class="view-all-link">View all &rarr;</a>
        </div>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Customer Name</th>
                        <th>Hall</th>
                        <th>Event Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = mysqli_fetch_assoc($recent)) { ?>
                    <tr>
                        <td>
                            <div class="customer-cell">
                                <div class="customer-avatar"><?php echo strtoupper(substr($row['customer_name'], 0, 1)); ?></div>
                                <?php echo $row['customer_name']; ?>
                            </div>
                        </td>
                        <td><?php echo $row['hall_name']; ?></td>
                        <td><?php echo date("j F Y", strtotime($row['event_date'])); ?></td>
                        <td>
                            <?php
                                $status = $row['status'];
                                $badge_class = 'badge--default';
                                if ($status === 'Pending')  $badge_class = 'badge--warning';
                                if ($status === 'Approved') $badge_class = 'badge--success';
                                if ($status === 'Rejected') $badge_class = 'badge--danger';
                            ?>
                            <span class="badge <?php echo $badge_class; ?>"><?php echo $status; ?></span>
                        </td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<!-- LOGOUT MODAL -->
<div class="modal" id="logoutModal">
    <div class="modal-box">
        <h3>Logout Confirmation</h3>
        <p>Are you sure you want to logout?</p>

        <div class="modal-actions">
            <button class="btn cancel" onclick="closeLogoutModal()">Cancel</button>
            <a href="../auth/logout.php" class="btn danger">Yes, Logout</a>
        </div>
    </div>
</div>
<script>
function openLogoutModal() {
    document.getElementById("logoutModal").style.display = "flex";
}

function closeLogoutModal() {
    document.getElementById("logoutModal").style.display = "none";
}

// close kalau klik luar box
window.onclick = function(e) {
    let modal = document.getElementById("logoutModal");
    if (e.target === modal) {
        closeLogoutModal();
    }
}
</script>
</body>
</html>