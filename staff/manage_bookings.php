<?php
include('../config/db.php');
session_start();

if (!isset($_SESSION['staff_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$staff_name = $_SESSION['name'];

/* =========================
   APPROVE BOOKING
========================= */
if (isset($_GET['approve'])) {
    $id = intval($_GET['approve']);
    $staff_id = $_SESSION['staff_id'];

    mysqli_query($conn, "
        UPDATE booking
        SET status='Approved',
            staff_id='$staff_id'
        WHERE booking_id=$id
    ");

    header("Location: manage_bookings.php");
    exit();
}

/* =========================
   REJECT BOOKING
========================= */
if (isset($_GET['reject'])) {
    $id = intval($_GET['reject']);
    $staff_id = $_SESSION['staff_id'];

    mysqli_query($conn, "
        UPDATE booking
        SET status='Rejected',
            staff_id='$staff_id'
        WHERE booking_id=$id
    ");

    header("Location: manage_bookings.php");
    exit();
}

/* =========================
   FILTER + SEARCH
========================= */
$statusFilter = "";
$search = "";

$sql = "SELECT booking.*,
               customer.full_name AS customer_name,
               hall.hall_name
        FROM booking
        JOIN customer ON booking.cust_id = customer.cust_id
        JOIN hall ON booking.hall_id = hall.hall_id";

$conditions = [];

if (isset($_GET['status']) && $_GET['status'] != "") {
    $statusFilter = mysqli_real_escape_string($conn, $_GET['status']);
    $conditions[] = "booking.status = '$statusFilter'";
}

if (isset($_GET['search']) && $_GET['search'] != "") {
    $search = mysqli_real_escape_string($conn, $_GET['search']);
    $conditions[] = "(customer.full_name LIKE '%$search%' 
                      OR hall.hall_name LIKE '%$search%' 
                      OR booking.event_type LIKE '%$search%'
                      OR booking.booking_id LIKE '%$search%')";
}

if (count($conditions) > 0) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}

$sql .= " ORDER BY booking.booking_id DESC";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Bookings - HallBook</title>
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

        /* Main Workspace Container */
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

        /* Modern Filtering Bar UI Fixes */
        .filter-card {
            background: var(--white); 
            border-radius: 12px; 
            border: 1px solid var(--border-color);
            margin-bottom: 24px; 
            padding: 16px 24px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
        }

        .filter-form {
            display: flex; 
            gap: 16px; 
            align-items: center;
            flex-wrap: wrap;
        }

        .form-control {
            padding: 10px 14px; 
            border: 1px solid #cbd5e1; 
            border-radius: 8px;
            font-size: 0.9rem;
            outline: none;
            background: #ffffff;
            color: var(--text-main);
            transition: border-color 0.15s;
        }

        .form-control:focus {
            border-color: var(--primary);
        }

        /* Clean Table Layouts */
        .content-card { 
            background: var(--white); 
            border-radius: 12px; 
            border: 1px solid var(--border-color); 
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
            overflow: hidden;
        }

        .data-table { 
            width: 100%; 
            border-collapse: collapse; 
            text-align: left;
        }

        .data-table th { 
            background-color: #f8fafc; 
            color: var(--text-muted); 
            font-weight: 600; 
            padding: 14px 20px; 
            font-size: 0.8rem; 
            text-transform: uppercase; 
            border-bottom: 1px solid var(--border-color); 
        }

        .data-table td { 
            padding: 16px 20px; 
            border-bottom: 1px solid var(--border-color); 
            font-size: 0.9rem; 
            vertical-align: middle;
        }

        .data-table tr:last-child td {
            border-bottom: none;
        }

        /* Status & Styling Components */
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

        /* Action Buttons Formatting */
        .btn { 
            padding: 8px 14px; 
            border-radius: 6px; 
            font-weight: 600; 
            cursor: pointer; 
            text-decoration: none;
            font-size: 0.85rem;
            border: none;
            transition: background 0.2s;
            display: inline-block;
        }
        
        .btn.btn-primary { background: var(--primary); color: white; }
        .btn.btn-primary:hover { background: var(--primary-hover); }
        .btn.cancel { background: #f1f5f9; color: #475569; }
        .btn.cancel:hover { background: #e2e8f0; }
        .btn.approve { background: var(--success); color: white; }
        .btn.approve:hover { background: #059669; }
        .btn.danger { background: var(--danger); color: white; }
        .btn.danger:hover { background: #dc2626; }

        .action-flex {
            display: flex; 
            gap: 8px; 
            justify-content: center;
        }

        /* Modal Layout */
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
            max-width: 420px; 
            width: 90%; 
            box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);
        }

        .modal-box h3 { font-size: 1.2rem; margin-bottom: 8px; color: #0f172a; }
        .modal-box p { color: var(--text-muted); font-size: 0.95rem; margin-bottom: 24px; line-height: 1.5; }

        .modal-actions { 
            display: flex; 
            gap: 12px; 
        }
        .modal-actions .btn {
            flex: 1;
            text-align: center;
        }
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
            <a href="staff_dashboard.php" class="menu-item">📊 Dashboard</a>
            <a href="manage_bookings.php" class="menu-item active">📅 Manage Bookings</a>
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
            <button class="btn-logout" onclick="window.location.href='../auth/logout.php'">🚪 Logout</button>
        </div>
    </aside>

    <main class="main-content">
        <header class="main-header">
            <h2>Manage Customer Bookings</h2>
            <p>Review, approve, or reject inbound event schedule reservations.</p>
        </header>

        <div class="filter-card">
            <form method="GET" action="manage_bookings.php" class="filter-form">
                <input type="text" name="search" placeholder="Search customer, hall, ID..." 
                       value="<?php echo htmlspecialchars($search); ?>" class="form-control" style="width:260px;">
                
                <select name="status" class="form-control" style="width:160px;">
                    <option value="">All Statuses</option>
                    <option value="Pending" <?php if($statusFilter=='Pending') echo 'selected'; ?>>Pending</option>
                    <option value="Approved" <?php if($statusFilter=='Approved') echo 'selected'; ?>>Approved</option>
                    <option value="Rejected" <?php if($statusFilter=='Rejected') echo 'selected'; ?>>Rejected</option>
                    <option value="Cancelled" <?php if($statusFilter=='Cancelled') echo 'selected'; ?>>Cancelled</option>
                </select>

                <button type="submit" class="btn btn-primary">Filter Results</button>
                <?php if($search != "" || $statusFilter != ""): ?>
                    <a href="manage_bookings.php" style="font-size:0.9rem; color: var(--text-muted); text-decoration:none; margin-left: 4px;">Clear Filters</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="content-card">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Customer</th>
                        <th>Hall Chosen</th>
                        <th>Event Type</th>
                        <th>Timeline Window</th>
                        <th>Days</th>
                        <th>Guests</th>
                        <th>Total Price</th>
                        <th>Status</th>
                        <th style="text-align: center;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if(mysqli_num_rows($result) == 0) {
                        echo '<tr><td colspan="10" style="text-align:center; color:#94a3b8; padding:40px;">No bookings found matching criteria.</td></tr>';
                    }
                    while ($row = mysqli_fetch_assoc($result)) { 
                        $b_id = $row['booking_id'];
                        $status = $row['status'];
                        $start = date('d M Y', strtotime($row['start_date']));
                        $end = date('d M Y', strtotime($row['end_date']));
                    ?>
                    <tr>
                        <td>#<?php echo $b_id; ?></td>
                        <td><strong><?php echo htmlspecialchars($row['customer_name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($row['hall_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['event_type']); ?></td>
                        <td><small style="color: #475569; font-weight: 500;"><?php echo "$start - $end"; ?></small></td>
                        <td><?php echo $row['total_days']; ?> Day(s)</td>
                        <td><?php echo $row['guest_count']; ?> pax</td>
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
                        <td style="text-align: center;">
                            <?php if ($status === 'Pending') { ?>
                                <div class="action-flex">
                                    <button class="btn approve" onclick="openModal('approve', <?php echo $b_id; ?>)">Approve</button>
                                    <button class="btn danger" onclick="openModal('reject', <?php echo $b_id; ?>)">Reject</button>
                                </div>
                            <?php } else { ?>
                                <span style="color:#94a3b8; font-size:0.85rem; font-weight: 500;">Processed</span>
                            <?php } ?>
                        </td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<div class="modal" id="confirmModal">
    <div class="modal-box">
        <h3 id="modalTitle">Process Booking</h3>
        <p id="modalText">Are you sure you want to proceed with this operation?</p>

        <div class="modal-actions">
            <button class="btn cancel" onclick="closeModal()">Dismiss</button>
            <a id="confirmBtn" class="btn" href="#">Confirm</a>
        </div>
    </div>
</div>

<script>
function openModal(action, id){
    let modal = document.getElementById('confirmModal');
    let confirmBtn = document.getElementById('confirmBtn');

    if(action === 'approve'){
        document.getElementById('modalTitle').innerText = "Approve Booking Request";
        document.getElementById('modalText').innerText = "Are you sure you want to Approve booking #" + id + "?";
        confirmBtn.href = "manage_bookings.php?approve=" + id;
        confirmBtn.className = "btn approve";
        confirmBtn.innerText = "Confirm Approve";
    }

    if(action === 'reject'){
        document.getElementById('modalTitle').innerText = "Reject Booking Request";
        document.getElementById('modalText').innerText = "Are you sure you want to Reject booking #" + id + "? This will release the schedule window.";
        confirmBtn.href = "manage_bookings.php?reject=" + id;
        confirmBtn.className = "btn danger";
        confirmBtn.innerText = "Confirm Reject";
    }

    modal.style.display = "flex";
}

function closeModal(){
    document.getElementById('confirmModal').style.display = "none";
}

window.onclick = function(event){
    let modal = document.getElementById('confirmModal');
    if(event.target == modal){
        modal.style.display = "none";
    }
}
</script>
</body>
</html>