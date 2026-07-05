<?php
session_start();
include('../config/db.php');

if (!isset($_SESSION['staff_id'])) {
    echo "Unauthorized access.";
    exit();
}

if (isset($_GET['id'])) {
    $cust_id = mysqli_real_escape_string($conn, $_GET['id']);

    // Retrieve Profile Core Info
    $user_res = mysqli_query($conn, "SELECT * FROM customer WHERE cust_id = '$cust_id'");
    $user = mysqli_fetch_assoc($user_res);

    // Retrieve Aggregated System Metrics from Booking tracking configurations
    // Reuses column identifiers 'total_price' from your own staff_dashboard file definitions
    $stats_res = mysqli_query($conn, "SELECT COUNT(*) AS total_bookings, SUM(total_price) AS total_spent FROM booking WHERE cust_id = '$cust_id'");
    $stats = mysqli_fetch_assoc($stats_res);

    $total_bookings = $stats['total_bookings'] ?? 0;
    $total_spent = $stats['total_spent'] ?? 0.00;

    if ($user) {
        ?>
        <table class="info-table">
            <tr><th>Customer ID</th><td>#<?php echo $user['cust_id']; ?></td></tr>
            <tr><th>Full Name</th><td><?php echo htmlspecialchars($user['full_name']); ?></td></tr>
            <tr><th>Username</th><td><?php echo htmlspecialchars($user['username']); ?></td></tr>
            <tr><th>Email</th><td><?php echo htmlspecialchars($user['email']); ?></td></tr>
            <tr><th>Phone Number</th><td><?php echo htmlspecialchars($user['phone_num']); ?></td></tr>
            <tr><th>Account Status</th><td><?php echo htmlspecialchars($user['status']); ?></td></tr>
            <tr><th>Total Bookings</th><td><?php echo $total_bookings; ?> bookings</td></tr>
            <tr><th>Total Investment</th><td>RM <?php echo number_format($total_spent, 2); ?></td></tr>
        </table>
        <?php
    } else {
        echo "<p style='color:var(--danger);'>Record parameters mismatch error.</p>";
    }
}
?>