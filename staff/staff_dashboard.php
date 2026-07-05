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
           booking.start_date,
           booking.end_date,
           booking.total_price,
           booking.status
    FROM booking
    JOIN customer ON booking.cust_id = customer.cust_id
    JOIN hall ON booking.hall_id = hall.hall_id
    ORDER BY booking.booking_id DESC
    LIMIT 5
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard - HallBook</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary: #2563eb;
            --primary-hover: #1d4ed8;
            --sidebar-bg: #0f172a;
            --bg-color: #f8fafc;
            --text-main: #334155;
            --text-muted: #64748b;
            --white: #ffffff;
            --border-color: #e2e8f0;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
        }

        * { 
            box-sizing: border-box; 
            margin: 0; 
            padding: 0; 
            font-family: 'Inter', sans-serif; 
        }

        body { 
            background-color: var(--bg-color); 
            color: var(--text-main); 
            min-height: 100vh;
        }

        .layout { 
            display: flex; 
            min-height: 100vh; 
        }

        /* Fixed Sidebar Layout */
        .sidebar {
            width: 260px;
            background: var(--sidebar-bg);
            color: #ffffff;
            display: flex;
            flex-direction: column;
            padding: 24px 20px;
            position: fixed;
            height: 100vh;
            left: 0;
            top: 0;
            z-index: 100;
        }

        .sidebar-brand { 
            font-weight: 700; 
            font-size: 1.2rem; 
            margin-bottom: 36px; 
            display: flex; 
            align-items: center; 
            gap: 10px; 
            color: #ffffff; 
            text-decoration: none; 
        }
        
        .brand-icon { 
            background-color: var(--primary); 
            width: 28px; 
            height: 28px; 
            border-radius: 6px; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            font-size: 0.9rem;
        }

        .sidebar-menu { 
            flex: 1; 
            display: flex; 
            flex-direction: column; 
            gap: 8px; 
        }

        .menu-item { 
            color: #94a3b8; 
            text-decoration: none; 
            padding: 12px 16px; 
            border-radius: 8px; 
            font-weight: 500; 
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: all 0.2s;
        }

        .menu-item:hover, .menu-item.active { 
            background-color: rgba(255, 255, 255, 0.08); 
            color: #ffffff; 
        }
        
        .menu-item.active {
            background-color: var(--primary);
        }

        .sidebar-footer { 
            margin-top: auto; 
            border-top: 1px solid rgba(255, 255, 255, 0.1); 
            padding-top: 20px; 
        }

        .user-info { 
            display: flex; 
            align-items: center; 
            gap: 12px; 
            margin-bottom: 16px; 
        }

        .user-avatar { 
            width: 38px; 
            height: 38px; 
            background: #3b82f6; 
            color: white; 
            border-radius: 50%; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            font-weight: 700; 
        }

        .user-name { 
            font-weight: 600; 
            font-size: 0.9rem;
            color: #ffffff; 
        }
        
        .user-role {
            font-size: 0.75rem;
            color: #94a3b8;
        }

        .btn-logout { 
            width: 100%; 
            padding: 10px; 
            background: rgba(239, 68, 68, 0.1); 
            color: #f87171; 
            border-radius: 8px; 
            border: 1px solid rgba(239, 68, 68, 0.2); 
            cursor: pointer; 
            font-weight: 600; 
            font-size: 0.85rem;
            text-align: center;
            transition: all 0.2s;
        }

        .btn-logout:hover { 
            background: var(--danger); 
            color: white; 
        }

        /* Main Content Panel Adjustment */
        .main-content { 
            margin-left: 260px; 
            flex: 1; 
            padding: 40px; 
            max-width: 1400px;
        }

        .main-header { 
            margin-bottom: 32px; 
        }

        .main-header h2 { 
            font-size: 1.75rem; 
            font-weight: 700; 
            color: #0f172a;
            margin-bottom: 4px;
        }

        .main-header p {
            color: var(--text-muted);
            font-size: 0.95rem;
        }

        /* Grid Allocation Formatting Fixes */
        .stats-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); 
            gap: 24px; 
            margin-bottom: 32px; 
        }

        .stat-card { 
            background: var(--white); 
            padding: 24px; 
            border-radius: 12px; 
            border: 1px solid var(--border-color); 
            display: flex; 
            justify-content: space-between; 
            align-items: center;
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
        }

        .stat-info h3 { 
            font-size: 2rem; 
            font-weight: 700; 
            color: #0f172a;
            margin-bottom: 4px;
        }

        .stat-info p { 
            color: var(--text-muted); 
            font-size: 0.9rem; 
            font-weight: 500;
        }

        .stat-icon { 
            font-size: 2rem; 
            background: #f1f5f9;
            width: 56px;
            height: 56px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
        }

        .content-card { 
            background: var(--white); 
            border-radius: 12px; 
            border: 1px solid var(--border-color); 
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
            overflow: hidden;
        }

        .card-header { 
            padding: 20px 24px; 
            border-bottom: 1px solid var(--border-color); 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
        }

        .card-header h3 { 
            font-size: 1.1rem; 
            font-weight: 600; 
        }

        .btn-primary {
            background-color: var(--primary);
            color: white;
            padding: 8px 16px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 0.85rem;
            text-decoration: none;
            transition: background 0.2s;
        }
        .btn-primary:hover {
            background-color: var(--primary-hover);
        }

        /* Clean Table Structures */
        .data-table { 
            width: 100%; 
            border-collapse: collapse; 
            text-align: left;
        }

        .data-table th { 
            background-color: #f8fafc; 
            color: var(--text-muted); 
            font-weight: 600; 
            padding: 14px 24px; 
            font-size: 0.8rem; 
            text-transform: uppercase; 
            border-bottom: 1px solid var(--border-color); 
        }

        .data-table td { 
            padding: 16px 24px; 
            border-bottom: 1px solid var(--border-color); 
            font-size: 0.9rem; 
        }

        .data-table tr:last-child td {
            border-bottom: none;
        }

        /* Badges Styling */
        .badge { 
            padding: 6px 12px; 
            border-radius: 50px; 
            font-weight: 600; 
            font-size: 0.8rem; 
            display: inline-block; 
        }
        
        .badge--warning { background: #fef3c7; color: #b45309; }
        .badge--success { background: #dcfce7; color: #15803d; }
        .badge--danger { background: #fee2e2; color: #b91c1c; }
        .badge--default { background: #f1f5f9; color: #475569; }

        /* Modal Overlays Layout */
        .modal { 
            display: none; 
            position: fixed; 
            top: 0; left: 0; width: 100%; height: 100%; 
            background: rgba(15, 23, 42, 0.3); 
            backdrop-filter: blur(4px); 
            align-items: center; 
            justify-content: center; 
            z-index: 1000; 
        }

        .modal-box { 
            background: white; 
            padding: 30px; 
            border-radius: 12px; 
            text-align: center; 
            max-width: 400px; 
            width: 90%; 
            box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);
        }

        .modal-box h3 { font-size: 1.2rem; margin-bottom: 8px; color: #0f172a; }
        .modal-box p { color: var(--text-muted); font-size: 0.95rem; margin-bottom: 24px; }

        .modal-actions { 
            display: flex; 
            gap: 12px; 
        }

        .btn { 
            padding: 10px 16px; 
            border-radius: 6px; 
            font-weight: 600; 
            cursor: pointer; 
            flex: 1; 
            text-decoration: none;
            font-size: 0.9rem;
            border: none;
        }
        
        .btn.cancel { background: #f1f5f9; color: #475569; }
        .btn.danger { background: var(--danger); color: white; }
        .btn.danger:hover { background: #dc2626; }
    </style>
</head>
<body>

<div class="layout">

    <aside class="sidebar">
        <div class="sidebar-brand">
            <div class="brand-icon">♦</div>
            <span class="brand-name">HallBook Staff</span>
        </div>

        <nav class="sidebar-menu">
            <a href="staff_dashboard.php" class="menu-item active">📊 Dashboard</a>
            <a href="manage_bookings.php" class="menu-item">📅 Manage Bookings</a>
            <a href="manage_hall.php" class="menu-item">🏢 Manage Halls</a>
            <a href="manage_users.php" class="menu-item">👤 Manage Users</a>
        </nav>

        <div class="sidebar-footer">
            <div class="user-info">
                <div class="user-avatar"><?php echo strtoupper(substr($staff_name, 0, 1)); ?></div>
                <div class="user-detail">
                    <div class="user-name"><?php echo htmlspecialchars($staff_name); ?></div>
                    <div class="user-role">Administrator</div>
                </div>
            </div>
            <button class="btn-logout" onclick="openLogoutModal()">🚪 Logout</button>
        </div>
    </aside>

    <main class="main-content">
        <header class="main-header">
            <h2>Welcome Back, <?php echo htmlspecialchars($staff_name); ?>!</h2>
            <p>Here is what's happening today.</p>
        </header>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-info">
                    <h3><?php echo $total_booking['total']; ?></h3>
                    <p>Total Bookings Placed</p>
                </div>
                <div class="stat-icon" style="color: #3b82f6;">📈</div>
            </div>

            <div class="stat-card">
                <div class="stat-info">
                    <h3><?php echo $pending['total']; ?></h3>
                    <p>Pending Approvals</p>
                </div>
                <div class="stat-icon" style="color: #f59e0b;">⏳</div>
            </div>
        </div>

        <div class="content-card">
            <div class="card-header">
                <h3>Latest Booking Submissions</h3>
                <a href="manage_bookings.php" class="btn-primary">View All</a>
            </div>

            <table class="data-table">
                <thead>
                    <tr>
                        <th>Booking ID</th>
                        <th>Customer</th>
                        <th>Hall Name</th>
                        <th>Duration Window</th>
                        <th>Total Cost</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if(mysqli_num_rows($recent) == 0) {
                        echo '<tr><td colspan="6" style="text-align:center; color:#94a3b8; padding:30px;">No bookings found.</td></tr>';
                    }
                    while($row = mysqli_fetch_assoc($recent)) { 
                        $status = $row['status'];
                        $start = date('d M Y', strtotime($row['start_date']));
                        $end = date('d M Y', strtotime($row['end_date']));
                    ?>
                    <tr>
                        <td>#<?php echo $row['booking_id']; ?></td>
                        <td><strong><?php echo htmlspecialchars($row['customer_name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($row['hall_name']); ?></td>
                        <td><small><?php echo "$start - $end"; ?></small></td>
                        <td><strong>RM <?php echo number_format($row['total_price'], 2); ?></strong></td>
                        <td>
                            <?php 
                                $badge_class = 'badge--default';
                                if ($status === 'Pending')   $badge_class = 'badge--warning';
                                if ($status === 'Approved')  $badge_class = 'badge--success';
                                if ($status === 'Rejected')  $badge_class = 'badge--danger';
                                if ($status === 'Cancelled') $badge_class = 'badge--default';
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

window.onclick = function(e) {
    let modal = document.getElementById("logoutModal");
    if (e.target === modal) {
        closeLogoutModal();
    }
}
</script>
</body>
</html>