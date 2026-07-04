<?php
session_start();

// Redirect to login if not authenticated
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$current_cust_id = $_SESSION['user_id'];

// Database Connection
$host = "localhost";
$username = "root"; 
$password = "";     
$dbname = "event_hall_booking"; 

$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}

// Query Statistics for CURRENT user
$total_query = $conn->prepare("SELECT COUNT(*) as total FROM booking WHERE cust_id = ?");
$total_query->bind_param("i", $current_cust_id);
$total_query->execute();
$total_bookings = $total_query->get_result()->fetch_assoc()['total'];

$pending_query = $conn->prepare("SELECT COUNT(*) as total FROM booking WHERE cust_id = ? AND status = 'Pending'");
$pending_query->bind_param("i", $current_cust_id);
$pending_query->execute();
$pending_bookings = $pending_query->get_result()->fetch_assoc()['total'];

$approved_query = $conn->prepare("SELECT COUNT(*) as total FROM booking WHERE cust_id = ? AND status = 'Approved'");
$approved_query->bind_param("i", $current_cust_id);
$approved_query->execute();
$approved_bookings = $approved_query->get_result()->fetch_assoc()['total'];

// FETCH THE MOST RECENT 3 BOOKING ENTRIES WITH THE NEW SCHEMA COLUMNS
$recent_bookings_query = $conn->prepare("SELECT b.booking_id, h.hall_name, b.start_date, b.end_date, b.status 
                                        FROM booking b 
                                        JOIN hall h ON b.hall_id = h.hall_id 
                                        WHERE b.cust_id = ? 
                                        ORDER BY b.booking_id DESC LIMIT 3");
$recent_bookings_query->bind_param("i", $current_cust_id);
$recent_bookings_query->execute();
$recent_bookings_result = $recent_bookings_query->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Dashboard - HallBook</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    
    <style>
        :root {
            --primary: #3b82f6;
            --primary-hover: #2563eb;
            --sidebar-bg: #1e293b;
            --bg-color: #f4f7f6;
            --text-main: #334155;
            --white: #ffffff;
            --border-color: #e2e8f0;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', sans-serif; }
        body { background-color: var(--bg-color); color: var(--text-main); }
        .layout { display: flex; min-height: 100vh; }

        .sidebar {
            width: 260px;
            background: var(--sidebar-bg);
            color: white;
            display: flex;
            flex-direction: column;
            padding: 24px 20px;
            position: fixed;
            height: 100vh;
            left: 0;
            top: 0;
        }

        .sidebar-brand { font-weight: 700; font-size: 1.25rem; margin-bottom: 40px; display: flex; align-items: center; gap: 10px; color: white; text-decoration: none; }
        .sidebar-brand-icon { background-color: var(--primary); width: 28px; height: 28px; border-radius: 6px; display: flex; align-items: center; justify-content: center; font-size: 0.9rem; transform: rotate(45deg); }
        .sidebar-brand-icon span { transform: rotate(-45deg); }
        
        .sidebar-nav { flex: 1; display: flex; flex-direction: column; gap: 8px; }
        .nav-item { color: #94a3b8; text-decoration: none; padding: 12px 16px; border-radius: 8px; font-weight: 500; }
        .nav-item:hover, .nav-item.active { background-color: rgba(59, 130, 246, 0.15); color: #60a5fa; }

        .sidebar-footer { margin-top: auto; border-top: 1px solid rgba(255, 255, 255, 0.1); padding-top: 20px; }
        .staff-info { display: flex; align-items: center; gap: 12px; margin-bottom: 20px; }
        .staff-avatar { width: 40px; height: 40px; background: #2563eb; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; }
        .staff-name { font-weight: 600; color: white; }

        .logout-btn { width: 100%; padding: 12px; background: rgba(239, 68, 68, 0.15); color: #f87171; border-radius: 8px; border: 1px solid rgba(239, 68, 68, 0.2); cursor: pointer; font-weight: 600; }
        .logout-btn:hover { background: #ef4444; color: white; }

        .main-content { margin-left: 260px; flex: 1; padding: 40px; }
        .topbar { margin-bottom: 30px; }
        .page-title { font-size: 1.75rem; font-weight: 700; }

        .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; margin-bottom: 32px; }
        .stat-card { background: white; padding: 24px; border-radius: 12px; border: 1px solid var(--border-color); box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); }
        .stat-card h3 { font-size: 0.9rem; color: #64748b; text-transform: uppercase; margin-bottom: 8px; }
        .stat-card p { font-size: 2rem; font-weight: 700; color: #1e293b; }

        .dashboard-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 32px; }
        .grid-card { background: white; padding: 24px; border-radius: 12px; border: 1px solid var(--border-color); box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); }
        .grid-card h3 { font-size: 1.1rem; margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 12px; }

        table { width: 100%; border-collapse: collapse; }
        th { background-color: #f8fafc; color: #64748b; font-weight: 600; padding: 12px; text-transform: uppercase; border-bottom: 2px solid var(--border-color); font-size: 0.8rem; }
        td { padding: 12px; border-bottom: 1px solid var(--border-color); font-size: 0.9rem; }

        .badge { padding: 4px 10px; border-radius: 20px; font-weight: 600; font-size: 0.75rem; }
        .badge-approved { background: #dcfce7; color: #15803d; }
        .badge-pending { background: #fef3c7; color: #b45309; }
        .badge-cancelled { background: #fee2e2; color: #b91c1c; }

        .quick-actions { display: flex; flex-direction: column; gap: 12px; }
        .action-link { display: flex; align-items: center; gap: 12px; padding: 14px; background: #f8fafc; border-radius: 8px; border: 1px solid var(--border-color); text-decoration: none; color: #1e293b; font-weight: 600; transition: all 0.2s; }
        .action-link:hover { background: #f1f5f9; border-color: #cbd5e1; }
        .action-icon { background: var(--primary); color: white; width: 32px; height: 32px; border-radius: 6px; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; }

        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.4); backdrop-filter: blur(4px); align-items: center; justify-content: center; z-index: 1000; }
        .modal.open { display: flex; }
        .modal-box { background: white; padding: 32px; border-radius: 12px; text-align: center; max-width: 400px; width: 90%; }
        .modal-actions { display: flex; gap: 12px; margin-top: 20px; }
        .btn { padding: 12px; border-radius: 8px; cursor: pointer; flex: 1; border: none; }
        .btn-secondary { background: #f1f5f9; color: #475569; }
        .btn-danger { background: #ef4444; color: white; }
    </style>
</head>
<body>

<div class="layout">
    <aside class="sidebar">
        <a href="dashboard.php" class="sidebar-brand">
            <div class="sidebar-brand-icon"><span>♦</span></div>
            HallBook
        </a>
        <nav class="sidebar-nav">
            <a href="dashboard.php" class="nav-item active">Dashboard</a>
            <a href="add_booking.php" class="nav-item">Add Booking</a>
            <a href="booking_status.php" class="nav-item">Booking Status</a>
            <a href="profile.php" class="nav-item">Profile</a>
        </nav>
        <div class="sidebar-footer">
            <div class="staff-info">
                <div class="staff-avatar">C</div>
                <div class="staff-details">
                    <span class="staff-name"><?= htmlspecialchars($_SESSION['user_name']) ?></span>
                </div>
            </div>
            <button class="logout-btn" onclick="openLogoutModal()">Logout</button>
        </div>
    </aside>

    <main class="main-content">
        <div class="topbar">
            <h1 class="page-title">Welcome back, <?= htmlspecialchars($_SESSION['user_name']) ?>!</h1>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Bookings</h3>
                <p><?= htmlspecialchars($total_bookings) ?></p>
            </div>
            <div class="stat-card">
                <h3>Pending Approval</h3>
                <p><?= htmlspecialchars($pending_bookings) ?></p>
            </div>
            <div class="stat-card">
                <h3>Approved Bookings</h3>
                <p><?= htmlspecialchars($approved_bookings) ?></p>
            </div>
        </div>

        <div class="dashboard-grid">
            <div class="grid-card">
                <h3>Recent Bookings</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Hall Name</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($recent_bookings_result->num_rows > 0): ?>
                            <?php while ($row = $recent_bookings_result->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['hall_name']) ?></td>
                                    <td><?= htmlspecialchars(date("j F Y", strtotime($row['start_date']))) ?></td>
                                    <td><?= htmlspecialchars(date("j F Y", strtotime($row['end_date']))) ?></td>
                                    <td>
                                        <?php 
                                        $statusClass = 'badge-pending';
                                        $statusText = strtolower($row['status']);
                                        if ($statusText === 'approved' || $statusText === 'success') $statusClass = 'badge-approved';
                                        if ($statusText === 'rejected' || $statusText === 'cancelled') $statusClass = 'badge-cancelled';
                                        ?>
                                        <span class="badge <?= $statusClass ?>"><?= htmlspecialchars($row['status'] ?: 'Pending') ?></span>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="4" style="text-align: center; color: #94a3b8; padding: 20px;">No bookings found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="grid-card">
                <h3>Quick Actions</h3>
                <div class="quick-actions">
                    <a href="add_booking.php" class="action-link">
                        <span class="action-icon">+</span>
                        Book a Hall
                    </a>
                    <a href="booking_status.php" class="action-link">
                        <span class="action-icon">📋</span>
                        View History
                    </a>
                </div>
            </div>
        </div>
    </main>
</div>

<div class="modal" id="logoutModal">
    <div class="modal-box">
        <h4 class="modal-title">Confirm Logout</h4>
        <p class="modal-message">Are you sure you want to end your current session?</p>
        <div class="modal-actions">
            <button class="btn btn-secondary" onclick="closeLogoutModal()">Cancel</button>
            <button class="btn btn-danger" onclick="confirmLogout()">Logout</button>
        </div>
    </div>
</div>

<script>
function openLogoutModal() { document.getElementById('logoutModal').classList.add('open'); }
function closeLogoutModal() { document.getElementById('logoutModal').classList.remove('open'); }
function confirmLogout() { window.location.href = "../auth/logout.php"; }
</script>
</body>
</html>