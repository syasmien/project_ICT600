<?php
// 1. Database Connection Configuration
$host = "localhost";
$username = "root"; 
$password = "";     
$dbname = "event_hall_booking"; 

$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}

// Fixed mock account customer identifier matching system testing profiles
$current_cust_id = 1; 

// 2. Query Statistics Counters from target database structures
// Total Bookings Metric
$total_query = "SELECT COUNT(*) as total FROM booking WHERE cust_id = ?";
$stmt = $conn->prepare($total_query);
$stmt->bind_param("i", $current_cust_id);
$stmt->execute();
$total_result = $stmt->get_result()->fetch_assoc();
$total_bookings = $total_result['total'] ?? 0;
$stmt->close();

// Pending Bookings Metric
$pending_query = "SELECT COUNT(*) as total FROM booking WHERE cust_id = ? AND status = 'Pending'";
$stmt = $conn->prepare($pending_query);
$stmt->bind_param("i", $current_cust_id);
$stmt->execute();
$pending_result = $stmt->get_result()->fetch_assoc();
$pending_bookings = $pending_result['total'] ?? 0;
$stmt->close();

// Approved Bookings Metric
$approved_query = "SELECT COUNT(*) as total FROM booking WHERE cust_id = ? AND status = 'Approved'";
$stmt = $conn->prepare($approved_query);
$stmt->bind_param("i", $current_cust_id);
$stmt->execute();
$approved_result = $stmt->get_result()->fetch_assoc();
$approved_bookings = $approved_result['total'] ?? 0;
$stmt->close();

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Dashboard - Event Hall Booking System</title>
    
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body>

<div class="app-container">
    
    <!-- LEFT SIDEBAR COMPONENT-->
    <aside class="sidebar">
        <div class="profile-section">
            <div class="avatar-placeholder">
                <!-- Clean profile vector icon -->
                <svg viewBox="0 0 24 24">
                    <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5-4-8-4z"/>
                </svg>
            </div>
            <div class="profile-role">Customer</div>
        </div>
        
        <nav class="nav-menu">
            <ul>
                <li class="nav-item active">
                    <a href="dashboard.php">
                        <!-- Dashboard Icon SVG -->
                        <svg viewBox="0 0 24 24">
                            <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/>
                        </svg>
                        DASHBOARD
                    </a>
                </li>
                <li class="nav-item">
                    <a href="add_booking.php">
                        <!-- Add Booking Icon SVG -->
                        <svg viewBox="0 0 24 24">
                            <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/>
                        </svg>
                        ADD BOOKING
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#">
                        <!-- Booking Status Icon SVG -->
                        <svg viewBox="0 0 24 24">
                            <path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/>
                        </svg>
                        BOOKING STATUS
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#">
                        <!-- Profile Icon SVG -->
                        <svg viewBox="0 0 24 24">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/>
                        </svg>
                        PROFILE
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#">
                        <!-- Logout Icon SVG -->
                        <svg viewBox="0 0 24 24">
                            <path d="M13 3h-2v10h2V3zm4.83 2.17l-1.42 1.42C17.99 7.86 19 9.81 19 12c0 3.87-3.13 7-7 7s-7-3.13-7-7c0-2.19 1.01-4.14 2.58-5.42L6.17 5.17C4.23 6.82 3 9.26 3 12c0 4.97 4.03 9 9 9s9-4.03 9-9c0-2.74-1.23-5.18-3.17-6.83z"/>
                        </svg>
                        LOGOUT
                    </a>
                </li>
            </ul>
        </nav>
    </aside>
    
    <!-- MAIN APPLICATION WORKSPACE -->
    <main class="main-content">
        
        <h2 class="section-title">Dashboard</h2>
        
        <!-- COUNTER METRICS COMPONENT ROW -->
        <div class="stats-row">
            <!-- Card 1: Calendar -->
            <div class="stat-card">
                <div class="stat-icon-wrapper">
                    <svg viewBox="0 0 24 24">
                        <path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11z"/>
                    </svg>
                    <div class="stat-value"><?php echo $total_bookings; ?></div>
                </div>
                <div class="stat-label">Total Bookings</div>
            </div>
            
            <!-- Card 2: Clock -->
            <div class="stat-card">
                <div class="stat-icon-wrapper">
                    <svg viewBox="0 0 24 24">
                        <path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z"/>
                    </svg>
                    <div class="stat-value"><?php echo $pending_bookings; ?></div>
                </div>
                <div class="stat-label">Pending Bookings</div>
            </div>
            
            <!-- Card 3: Checkmark -->
            <div class="stat-card">
                <div class="stat-icon-wrapper">
                    <svg viewBox="0 0 24 24">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                    </svg>
                    <div class="stat-value"><?php echo $approved_bookings; ?></div>
                </div>
                <div class="stat-label">Approved Bookings</div>
            </div>
        </div>
        
        <h2 class="section-title">Quick Actions</h2>
        
        <!-- QUICK ACTION ACTIONABLE CARDS -->
        <div class="actions-row">
            <!-- Add Booking Action -->
            <a href="add_booking.php" class="action-card">
                <div class="action-icon">
                    <svg viewBox="0 0 24 24">
                        <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/>
                    </svg>
                </div>
                <div class="action-title">Add Booking</div>
                <div class="action-desc">Book a new event hall</div>
            </a>
            
            <!-- View Status Action -->
            <a href="booking_status.php" class="action-card">
                <div class="action-icon">
                    <svg viewBox="0 0 24 24">
                        <path d="M4 6H2v14c0 1.1.9 2 2 2h14v-2H4V6zm16-4H8c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm0 14H8V4h12v12z"/>
                    </svg>
                </div>
                <div class="action-title">View Booking Status</div>
                <div class="action-desc">Check your booking status</div>
            </a>
        </div>
        
    </main>
</div>

</body>
</html>