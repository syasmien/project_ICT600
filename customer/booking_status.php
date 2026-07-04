<?php
// Start session to manage user login state
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// STRICT AUTHENTICATION GUARD: Redirect user to login page if session is not active
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$current_cust_id = $_SESSION['user_id'];

// Database Connection & Configuration Block (Matches event_hall_booking)
$host = "localhost";
$username = "root";
$password = "";
$dbname = "event_hall_booking";

$db_connected = false;
$db_error_message = "";
$bookings_list = [];

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db_connected = true;
} catch(PDOException $e) {
    $db_connected = false;
    $db_error_message = $e->getMessage();
}

// Handle Cancel Action securely via POST
$success_msg = "";
$error_msg = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['cancel_booking'])) {
    $booking_id = intval($_POST['booking_id']);
    
    if ($db_connected) {
        try {
            // Verify ownership first before altering status parameters
            $verify_stmt = $conn->prepare("SELECT status FROM booking WHERE booking_id = :booking_id AND cust_id = :cust_id");
            $verify_stmt->execute([':booking_id' => $booking_id, ':cust_id' => $current_cust_id]);
            $current_booking = $verify_stmt->fetch(PDO::FETCH_ASSOC);

            if ($current_booking) {
                if ($current_booking['status'] === 'Pending') {
                    $cancel_stmt = $conn->prepare("UPDATE booking SET status = 'Cancelled' WHERE booking_id = :booking_id");
                    $cancel_stmt->execute([':booking_id' => $booking_id]);
                    $success_msg = "Booking successfully cancelled.";
                } else {
                    $error_msg = "Only Pending bookings can be cancelled.";
                }
            } else {
                $error_msg = "Booking selection target was invalid.";
            }
        } catch(PDOException $ex) {
            $error_msg = "Execution Error: " . $ex->getMessage();
        }
    }
}

// Gather customer items mapping using structural query bindings (Switched to start_date, end_date, total_days, total_price)
if ($db_connected) {
    try {
        $stmt = $conn->prepare("SELECT b.booking_id AS id, h.hall_name, b.event_type, b.start_date, b.end_date, b.guest_count, b.total_days, b.total_price, b.status 
                               FROM booking b 
                               JOIN hall h ON b.hall_id = h.hall_id 
                               WHERE b.cust_id = :cust_id 
                               ORDER BY b.booking_id DESC");
        $stmt->execute([':cust_id' => $current_cust_id]);
        $bookings_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $eb) {
        $db_error_message = $eb->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Status - HallBook</title>
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
            --danger: #ef4444;
            --danger-hover: #dc2626;
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
        .topbar { margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; }
        .page-title { font-size: 1.75rem; font-weight: 700; }

        .status-card { background: white; padding: 24px; border-radius: 12px; border: 1px solid var(--border-color); box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); }
        
        .filter-bar { display: flex; gap: 16px; margin-bottom: 24px; align-items: center; justify-content: space-between; }
        .search-input { padding: 10px 16px; border: 1px solid var(--border-color); border-radius: 8px; width: 300px; font-size: 0.9rem; }
        .filter-select { padding: 10px 16px; border: 1px solid var(--border-color); border-radius: 8px; font-size: 0.9rem; background: white; }

        table { width: 100%; border-collapse: collapse; }
        th { background-color: #f8fafc; color: #64748b; font-weight: 600; padding: 14px; text-transform: uppercase; border-bottom: 2px solid var(--border-color); font-size: 0.8rem; text-align: left; }
        td { padding: 14px; border-bottom: 1px solid var(--border-color); font-size: 0.9rem; color: #334155; }

        .badge { padding: 6px 12px; border-radius: 20px; font-weight: 600; font-size: 0.8rem; display: inline-block; }
        .badge-approved { background: #dcfce7; color: #15803d; }
        .badge-pending { background: #fef3c7; color: #b45309; }
        .badge-rejected { background: #fee2e2; color: #b91c1c; }
        .badge-cancelled { background: #e2e8f0; color: #475569; }

        .action-btn-group { display: flex; gap: 8px; }
        .btn-action { padding: 6px 12px; border-radius: 6px; text-decoration: none; font-size: 0.8rem; font-weight: 600; cursor: pointer; border: 1px solid transparent; }
        .btn-edit { background: #f0fdf4; color: #16a34a; border-color: #bbf7d0; }
        .btn-edit:hover { background: #dcfce7; }
        .btn-cancel { background: #fff5f5; color: #e53e3e; border-color: #fed7d7; }
        .btn-cancel:hover { background: #fee2e2; }

        .alert { padding: 16px 20px; border-radius: 10px; margin-bottom: 24px; }
        .alert-success { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
        .alert-error { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }

        .pagination { display: flex; justify-content: space-between; align-items: center; margin-top: 20px; padding-top: 20px; border-top: 1px solid var(--border-color); }
        .pagination-info { font-size: 0.85rem; color: #64748b; }
        .pagination-buttons { display: flex; gap: 8px; }
        .btn-page { padding: 8px 14px; border: 1px solid var(--border-color); background: white; border-radius: 6px; cursor: pointer; font-size: 0.85rem; font-weight: 500; }
        .btn-page:hover:not(:disabled) { background: #f8fafc; }
        .btn-page:disabled { opacity: 0.5; cursor: not-allowed; }

        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.4); backdrop-filter: blur(4px); align-items: center; justify-content: center; z-index: 1000; }
        .modal.open { display: flex; }
        .modal-box { background: white; padding: 32px; border-radius: 12px; text-align: center; max-width: 400px; width: 90%; }
        .modal-title { font-size: 1.2rem; font-weight: 700; margin-bottom: 10px; }
        .modal-message { color: #64748b; font-size: 0.95rem; margin-bottom: 24px; }
        .modal-actions { display: flex; gap: 12px; }
        .btn { padding: 12px; border-radius: 8px; cursor: pointer; flex: 1; border: none; font-weight: 600; }
        .btn-secondary { background: #f1f5f9; color: #475569; }
        .btn-danger { background: var(--danger); color: white; }
        .btn-danger:hover { background: var(--danger-hover); }
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
            <a href="dashboard.php" class="nav-item">Dashboard</a>
            <a href="add_booking.php" class="nav-item">Add Booking</a>
            <a href="booking_status.php" class="nav-item active">Booking Status</a>
            <a href="profile.php" class="nav-item">Profile</a>
        </nav>
        <div class="sidebar-footer">
            <div class="staff-info">
                <div class="staff-avatar">C</div>
                <div class="staff-details">
                    <span class="staff-name"><?= htmlspecialchars($_SESSION['user_name'] ?? 'Customer') ?></span>
                </div>
            </div>
            <button class="logout-btn" onclick="openLogoutModal()">Logout</button>
        </div>
    </aside>

    <main class="main-content">
        <div class="topbar">
            <h1 class="page-title">My Booking History</h1>
        </div>

        <?php if (!empty($success_msg)): ?>
            <div class="alert alert-success"><?= $success_msg ?></div>
        <?php endif; ?>
        <?php if (!empty($error_msg)): ?>
            <div class="alert alert-error"><?= $error_msg ?></div>
        <?php endif; ?>
        <?php if (!$db_connected): ?>
            <div class="alert alert-error">Database connection down: <?= htmlspecialchars($db_error_message) ?></div>
        <?php endif; ?>

        <div class="status-card">
            <div class="filter-bar">
                <input type="text" id="searchInput" class="search-input" placeholder="Search hall, event type..." oninput="onSearchChange(this.value)">
                
                <select id="statusFilter" class="filter-select" onchange="onStatusFilterChange(this.value)">
                    <option value="All">All Statuses</option>
                    <option value="Pending">Pending</option>
                    <option value="Approved">Approved</option>
                    <option value="Rejected">Rejected</option>
                    <option value="Cancelled">Cancelled</option>
                </select>
            </div>

            <table id="bookingsTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Hall Name</th>
                        <th>Event Type</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Days</th>
                        <th>Guests</th>
                        <th>Total Price</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                    </tbody>
            </table>

            <div class="pagination">
                <div class="pagination-info" id="paginationInfo">Showing 0 to 0 of 0 entries</div>
                <div class="pagination-buttons">
                    <button class="btn-page" id="btnPrev" onclick="changePage(-1)">Previous</button>
                    <button class="btn-page" id="btnNext" onclick="changePage(1)">Next</button>
                </div>
            </div>
        </div>
    </main>
</div>

<div class="modal" id="cancelModal">
    <div class="modal-box">
        <h4 class="modal-title">Cancel Booking Request</h4>
        <p class="modal-message">Are you sure you want to cancel this booking? This action cannot be undone.</p>
        <form action="" method="POST">
            <input type="hidden" id="cancel_booking_id" name="booking_id" value="">
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeCancelModal()">Dismiss</button>
                <button type="submit" name="cancel_booking" class="btn btn-danger">Confirm Cancel</button>
            </div>
        </form>
    </div>
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
    // Inject structural runtime configurations from PDO queries
    const bookingsData = <?= json_encode($bookings_list) ?>;
    
    let filteredData = [...bookingsData];
    let currentPage = 1;
    const rowsPerPage = 5;

    let searchQuery = "";
    let selectedStatusFilter = "All";

    function renderTable() {
        const tbody = document.getElementById('tableBody');
        tbody.innerHTML = "";

        if (filteredData.length === 0) {
            tbody.innerHTML = '<tr><td colspan="10" style="text-align:center; color:#94a3b8; padding:30px;">No matching booking history found.</td></tr>';
            document.getElementById('paginationInfo').textContent = "Showing 0 to 0 of 0 entries";
            document.getElementById('btnPrev').disabled = true;
            document.getElementById('btnNext').disabled = true;
            return;
        }

        const startIndex = (currentPage - 1) * rowsPerPage;
        const endIndex = Math.min(startIndex + rowsPerPage, filteredData.length);
        const paginatedItems = filteredData.slice(startIndex, endIndex);

        paginatedItems.forEach(item => {
            let badgeClass = 'badge-pending';
            if (item.status === 'Approved') badgeClass = 'badge-approved';
            if (item.status === 'Rejected') badgeClass = 'badge-rejected';
            if (item.status === 'Cancelled') badgeClass = 'badge-cancelled';

            // Show actions button mapping conditionally
            let actionsHtml = '-';
            if (item.status === 'Pending') {
                actionsHtml = `
                    <div class="action-btn-group">
                        <a href="update_booking.php?id=${item.id}" class="btn-action btn-edit">Edit</a>
                        <button type="button" class="btn-action btn-cancel" onclick="openCancelModal(${item.id})">Cancel</button>
                    </div>
                `;
            }

            // Beautiful Date conversions
            const formatOptions = { day: 'numeric', month: 'short', year: 'numeric' };
            const startFormatted = new Date(item.start_date).toLocaleDateString('en-GB', formatOptions);
            const endFormatted = new Date(item.end_date).toLocaleDateString('en-GB', formatOptions);

            const row = document.createElement('tr');
            row.innerHTML = `
                <td>#${item.id}</td>
                <td><strong>${escapeHtml(item.hall_name)}</strong></td>
                <td>${escapeHtml(item.event_type)}</td>
                <td>${startFormatted}</td>
                <td>${endFormatted}</td>
                <td>${item.total_days} Day(s)</td>
                <td>${item.guest_count} pax</td>
                <td><strong>RM ${parseFloat(item.total_price).toFixed(2)}</strong></td>
                <td><span class="badge ${badgeClass}">${item.status}</span></td>
                <td>${actionsHtml}</td>
            `;
            tbody.appendChild(row);
        });

        document.getElementById('paginationInfo').textContent = `Showing ${startIndex + 1} to ${endIndex} of ${filteredData.length} entries`;
        document.getElementById('btnPrev').disabled = (currentPage === 1);
        document.getElementById('btnNext').disabled = (currentPage * rowsPerPage >= filteredData.length);
    }

    function onSearchChange(val) {
        searchQuery = val.toLowerCase().trim();
        applyFilters();
    }

    function onStatusFilterChange(val) {
        selectedStatusFilter = val;
        applyFilters();
    }

    function applyFilters() {
        filteredData = bookingsData.filter(item => {
            const statusMatch = (selectedStatusFilter === 'All') || (item.status.toLowerCase() === selectedStatusFilter.toLowerCase());
            const searchMatch = (item.hall_name.toLowerCase().includes(searchQuery)) || 
                                (item.event_type.toLowerCase().includes(searchQuery)) || 
                                (item.start_date.includes(searchQuery)) ||
                                (item.end_date.includes(searchQuery)) ||
                                (String(item.id).includes(searchQuery));
            return statusMatch && searchMatch;
        });
        currentPage = 1;
        renderTable();
    }

    function changePage(direction) {
        currentPage += direction;
        renderTable();
    }

    function escapeHtml(str) {
        if (!str) return '';
        return str.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
    }

    function openLogoutModal() { document.getElementById('logoutModal').classList.add('open'); }
    function closeLogoutModal() { document.getElementById('logoutModal').classList.remove('open'); }
    function confirmLogout() { window.location.href = "../auth/logout.php"; }

    function openCancelModal(id) {
        document.getElementById('cancel_booking_id').value = id;
        document.getElementById('cancelModal').classList.add('open');
    }
    function closeCancelModal() { document.getElementById('cancelModal').classList.remove('open'); }

    // Initialize execution map on page draw setup
    document.addEventListener("DOMContentLoaded", () => {
        renderTable();
    });
</script>
</body>
</html>