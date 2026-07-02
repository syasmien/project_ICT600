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
            // Prepared statement to safely update status to 'Cancelled'
            $stmt = $conn->prepare("UPDATE booking SET status = 'Cancelled' WHERE booking_id = :booking_id AND cust_id = :cust_id AND status = 'Pending'");
            $stmt->execute([
                ':booking_id' => $booking_id,
                ':cust_id' => $current_cust_id
            ]);
            
            if ($stmt->rowCount() > 0) {
                $success_msg = "🎉 Booking #$booking_id has been cancelled successfully.";
            } else {
                $error_msg = "❌ Could not cancel booking. (Note: Only pending bookings can be cancelled).";
            }
        } catch (PDOException $ex) {
            $error_msg = "Database Action Error: " . $ex->getMessage();
        }
    }
}

// Retrieve Dynamic Bookings data for the logged-in Customer ONLY
if ($db_connected) {
    try {
        // Query modified to use the correct 'guest_count' column name
        $stmt_bookings = $conn->prepare("SELECT b.booking_id AS id, h.hall_name AS hall_name, b.event_type, b.event_date AS date, b.guest_count AS guests, b.status 
                                       FROM booking b 
                                       JOIN hall h ON b.hall_id = h.hall_id 
                                       WHERE b.cust_id = :cust_id
                                       ORDER BY b.booking_id DESC");
        $stmt_bookings->execute([':cust_id' => $current_cust_id]);
        $bookings_list = $stmt_bookings->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $ex) {
        $db_error_message = $ex->getMessage();
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
            --warning: #f59e0b;
            --success: #10b981;
            --danger: #ef4444;
            --border-color: #e2e8f0;
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

        /* Layout Main Containers */
        .layout {
            display: flex;
            min-height: 100vh;
            width: 100%;
        }

        /* Sidebar Styling (Matches Add Booking) */
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
            z-index: 100;
        }

        .sidebar-brand {
            font-weight: 700;
            font-size: 1.25rem;
            margin-bottom: 40px;
            display: flex;
            align-items: center;
            gap: 10px;
            color: white;
            text-decoration: none;
        }

        .sidebar-brand-icon {
            background-color: var(--primary);
            width: 28px;
            height: 28px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            color: white;
            transform: rotate(45deg);
        }

        .sidebar-brand-icon span {
            transform: rotate(-45deg);
        }

        .sidebar-nav {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .nav-item {
            color: #94a3b8;
            text-decoration: none;
            padding: 12px 16px;
            border-radius: 8px;
            font-weight: 500;
            font-size: 0.95rem;
            transition: all 0.2s ease;
        }

        .nav-item:hover {
            background-color: rgba(255, 255, 255, 0.05);
            color: white;
        }

        .nav-item.active {
            background-color: rgba(59, 130, 246, 0.15);
            color: #60a5fa;
            font-weight: 600;
        }

        /* Sidebar Profile Badge footer */
        .sidebar-footer {
            margin-top: auto;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding-top: 20px;
        }

        .staff-info {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
        }

        .staff-avatar {
            width: 40px;
            height: 40px;
            background: #2563eb;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.95rem;
        }

        .staff-details {
            display: flex;
            flex-direction: column;
        }

        .staff-name {
            font-weight: 600;
            font-size: 0.95rem;
            color: white;
        }

        .logout-btn {
            display: block;
            width: 100%;
            padding: 12px;
            background: rgba(239, 68, 68, 0.15);
            color: #f87171;
            text-align: center;
            border-radius: 8px;
            border: 1px solid rgba(239, 68, 68, 0.2);
            cursor: pointer;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.2s ease;
        }

        .logout-btn:hover {
            background: var(--danger);
            color: white;
        }

        /* Main Viewport Content */
        .main-content {
            margin-left: 260px;
            flex: 1;
            padding: 40px;
            min-height: 100vh;
            overflow-y: auto;
        }

        .topbar {
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .page-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: #1e293b;
        }

        /* Status Filters & Search Bar Section */
        .filter-section {
            background: var(--white);
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 24px;
            border: 1px solid var(--border-color);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02);
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
        }

        .search-box {
            position: relative;
            flex: 1;
            min-width: 250px;
        }

        .search-control {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 0.9rem;
            outline: none;
            background-color: #f8fafc;
            transition: all 0.2s ease;
        }

        .search-control:focus {
            border-color: var(--primary);
            background-color: #ffffff;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .filter-buttons {
            display: flex;
            gap: 8px;
        }

        .filter-btn {
            padding: 10px 16px;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            border: 1px solid var(--border-color);
            background-color: var(--white);
            color: #64748b;
            transition: all 0.2s ease;
        }

        .filter-btn:hover {
            background-color: #f8fafc;
            color: #334155;
        }

        .filter-btn.active {
            background-color: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        /* Card Container Panel */
        .booking-card {
            background: var(--white);
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
            border: 1px solid var(--border-color);
        }

        .booking-card h3 {
            font-size: 1.1rem;
            color: #1e293b;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 24px;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 12px;
        }

        /* Custom Dynamic Table Styling */
        .table-wrapper {
            background: var(--white);
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid var(--border-color);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        th {
            background-color: #f8fafc;
            color: #64748b;
            font-weight: 600;
            font-size: 0.85rem;
            padding: 16px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid var(--border-color);
        }

        td {
            padding: 16px;
            border-bottom: 1px solid var(--border-color);
            font-size: 0.95rem;
            color: #334155;
        }

        tr:last-child td {
            border-bottom: none;
        }

        tr:hover td {
            background-color: #f8fafc;
        }

        /* Status & Alert Badges */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: capitalize;
        }

        .badge-approved {
            background: #dcfce7;
            color: #15803d;
        }

        .badge-pending {
            background: #fef3c7;
            color: #b45309;
        }

        .badge-cancelled, .badge-rejected {
            background: #fee2e2;
            color: #b91c1c;
        }

        /* Alert Notifications */
        .alert {
            padding: 16px 20px;
            border-radius: 10px;
            font-size: 0.95rem;
            font-weight: 500;
            margin-bottom: 24px;
        }

        .alert-success {
            background-color: #dcfce7;
            color: #15803d;
            border: 1px solid #bbf7d0;
        }

        .alert-error {
            background-color: #fee2e2;
            color: #b91c1c;
            border: 1px solid #fecaca;
        }

        /* Styled Functional Actions Button */
        .action-btns {
            display: flex;
            gap: 8px;
        }

        .action-btn {
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            border: 1px solid transparent;
            transition: all 0.15s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .action-edit {
            background-color: #f1f5f9;
            color: #475569;
            border-color: #cbd5e1;
        }

        .action-edit:hover {
            background-color: #e2e8f0;
            color: #1e293b;
        }

        .action-cancel {
            background-color: rgba(239, 68, 68, 0.08);
            color: #ef4444;
            border-color: rgba(239, 68, 68, 0.2);
        }

        .action-cancel:hover {
            background-color: #ef4444;
            color: white;
            border-color: #ef4444;
        }

        /* Action Buttons State Blockers */
        .action-disabled {
            background-color: #e2e8f0;
            color: #94a3b8;
            border-color: #cbd5e1;
            cursor: not-allowed;
            pointer-events: none;
            opacity: 0.5;
        }

        /* Styled Functional Pagination */
        .pagination-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 24px;
            padding-top: 16px;
            border-top: 1px solid var(--border-color);
        }

        .pagination-info {
            font-size: 0.95rem;
            color: #64748b;
        }

        .pagination-btn {
            padding: 8px 12px;
            border: 1px solid var(--border-color);
            background-color: white;
            color: #475569;
            border-radius: 6px;
            font-weight: 600;
            font-size: 0.85rem;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }

        .pagination-btn:hover {
            background-color: #f8fafc;
            color: #1e293b;
            border-color: #cbd5e1;
        }

        .pagination-btn.active {
            background-color: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        .pagination-btn:disabled {
            background-color: #f8fafc;
            color: #cbd5e1;
            cursor: not-allowed;
            border-color: #f1f5f9;
        }

        /* Modal Overlays styling */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(15, 23, 42, 0.4);
            backdrop-filter: blur(4px);
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }

        .modal.open {
            display: flex;
        }

        .modal-box {
            background: white;
            padding: 32px;
            border-radius: 12px;
            width: 90%;
            max-width: 400px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            text-align: center;
        }

        .modal-title {
            font-size: 1.2rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 12px;
        }

        .modal-message {
            font-size: 0.95rem;
            color: #64748b;
            margin-bottom: 24px;
            line-height: 1.5;
        }

        .modal-actions {
            display: flex;
            gap: 12px;
            justify-content: center;
        }

        .btn {
            flex: 1;
            padding: 12px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            border: none;
            transition: background-color 0.15s ease;
        }

        .btn-secondary {
            background-color: #f1f5f9;
            color: #475569;
        }

        .btn-secondary:hover {
            background-color: #e2e8f0;
        }

        .btn-danger {
            background-color: var(--danger);
            color: white;
        }

        .btn-danger:hover {
            background-color: #dc2626;
        }
    </style>
</head>
<body>

<div class="layout">
    <!-- Sidebar Navigation -->
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
        
        <!-- Sidebar User Badge Footer -->
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

    <!-- Main Content Panel Area -->
    <main class="main-content">
        <div class="topbar">
            <h1 class="page-title">Booking Status</h1>
        </div>

        <!-- Connection Error Diagnostics Alert -->
        <?php if (!$db_connected && !empty($db_error_message)): ?>
            <div class="alert alert-error">
                <strong>Database Error Diagnostic Alert:</strong> Connection offline.<br>
                <code style="font-family: monospace; display: block; margin-top: 8px; font-size: 0.85rem; color: #7f1d1d;">Error: <?= htmlspecialchars($db_error_message) ?></code>
            </div>
        <?php endif; ?>

        <?php if (!empty($success_msg)): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success_msg) ?></div>
        <?php endif; ?>
        <?php if (!empty($error_msg)): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error_msg) ?></div>
        <?php endif; ?>

        <!-- Search and Live Filtering Workspace Panel -->
        <div class="filter-section">
            <div class="search-box">
                <input type="text" id="searchInput" class="search-control" placeholder="Search by Hall Name, Event or Date..." onkeyup="filterBookings()">
            </div>
            <div class="filter-buttons">
                <button class="filter-btn active" id="btn_all" onclick="filterByStatus('All')">All</button>
                <button class="filter-btn" id="btn_pending" onclick="filterByStatus('Pending')">Pending</button>
                <button class="filter-btn" id="btn_approved" onclick="filterByStatus('Approved')">Approved</button>
                <button class="filter-btn" id="btn_cancelled" onclick="filterByStatus('Cancelled')">Cancelled</button>
            </div>
        </div>

        <!-- Bookings Overview Card -->
        <div class="booking-card">
            <h3>My Bookings History</h3>
            <div class="table-wrapper">
                <table id="bookingsTable">
                    <thead>
                        <tr>
                            <th>Booking ID</th>
                            <th>Hall Name</th>
                            <th>Event Type</th>
                            <th>Event Date</th>
                            <th>Guests</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="bookingsTableBody">
                    </tbody>
                </table>
            </div>

            <!-- Styled Pagination Controllers -->
            <div class="pagination-container" id="pagination_container">
                <div class="pagination-info" id="pagination_info">
                    Showing 1 to 5 entries
                </div>
                <div style="display:flex; gap:6px;">
                    <button class="pagination-btn" id="btn_prev" onclick="goToPage('prev')">Prev</button>
                    <span id="page_numbers" style="display: flex; gap: 4px;"></span>
                    <button class="pagination-btn" id="btn_next" onclick="goToPage('next')">Next</button>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- Logout confirmation modal -->
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

<!-- Cancel booking confirmation modal -->
<div class="modal" id="cancelModal">
    <div class="modal-box">
        <h4 class="modal-title" style="color: var(--danger);">Cancel Booking</h4>
        <p class="modal-message">Are you sure you want to cancel this booking request?</p>
        <form id="cancelForm" method="POST" action="">
            <input type="hidden" name="booking_id" id="cancel_booking_id">
            <input type="hidden" name="cancel_booking" value="1">
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeCancelModal()">Keep Booking</button>
                <button type="submit" class="btn btn-danger">Yes, Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
    // Map raw data safely into format JavaScript matches
    // Clean JSON output using helper flag constraints to prevent syntax-breaking character injection
    const rawData = <?= json_encode($bookings_list, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
    let bookingsData = [];
    
    if (Array.isArray(rawData)) {
        bookingsData = rawData.map(item => ({
            id: item.id,
            hall_name: item.hall_name,
            event_type: item.event_type || 'Custom Event',
            date: item.date,
            guests: item.guests || 0,
            status: item.status || 'Pending'
        }));
    }

    let filteredData = [...bookingsData];
    let selectedStatusFilter = 'All';
    let searchQuery = '';
    const rowsPerPage = 5;
    let currentPage = 1;

    function renderTable() {
        const tbody = document.getElementById("bookingsTableBody");
        tbody.innerHTML = "";

        if (filteredData.length === 0) {
            tbody.innerHTML = `<tr><td colspan="7" style="text-align: center; color: #94a3b8; padding: 40px;">No bookings found matching filters.</td></tr>`;
            document.getElementById("pagination_container").style.display = "none";
            return;
        }

        document.getElementById("pagination_container").style.display = "flex";

        const startIndex = (currentPage - 1) * rowsPerPage;
        const endIndex = Math.min(startIndex + rowsPerPage, filteredData.length);
        const paginatedSlice = filteredData.slice(startIndex, endIndex);

        paginatedSlice.forEach(row => {
            const tr = document.createElement("tr");
            const statusLower = row.status.toLowerCase();
            let statusClass = 'badge-pending';
            if (statusLower === 'approved' || statusLower === 'success') statusClass = 'badge-approved';
            if (statusLower === 'cancelled' || statusLower === 'rejected') statusClass = 'badge-cancelled';

            const isPending = (statusLower === 'pending');
            const editClass = isPending ? '' : 'action-disabled';
            const cancelClass = isPending ? 'action-cancel' : 'action-disabled';

            // Fixed conditional edit href so non-pending rows cannot open edit
            const editUrl = isPending ? `update_booking.php?booking_id=${row.id}` : '#';

            tr.innerHTML = `
                <td><strong>#${row.id}</strong></td>
                <td>${escapeHtml(row.hall_name)}</td>
                <td>${escapeHtml(row.event_type)}</td>
                <td>${escapeHtml(row.date)}</td>
                <td>${row.guests}</td>
                <td><span class="badge ${statusClass}">${escapeHtml(row.status)}</span></td>
                <td>
                    <div class="action-btns">
                        <a href="${editUrl}" class="action-btn action-edit ${editClass}">Edit</a>
                        <button class="action-btn ${cancelClass}" onclick="openCancelModal(${row.id})" ${isPending ? '' : 'disabled'}>Cancel</button>
                    </div>
                </td>
            `;
            tbody.appendChild(tr);
        });

        document.getElementById("pagination_info").innerText = `Showing ${startIndex + 1} to ${endIndex} of ${filteredData.length} entries`;
        
        // Generate page numbers dynamically
        renderPageNumbers();
    }

    function renderPageNumbers() {
        const container = document.getElementById("page_numbers");
        container.innerHTML = "";
        const totalPages = Math.ceil(filteredData.length / rowsPerPage);

        for (let i = 1; i <= totalPages; i++) {
            const btn = document.createElement("button");
            btn.className = `pagination-btn ${currentPage === i ? 'active' : ''}`;
            btn.innerText = i;
            btn.onclick = () => {
                currentPage = i;
                renderTable();
            };
            container.appendChild(btn);
        }

        document.getElementById("btn_prev").disabled = (currentPage === 1);
        document.getElementById("btn_next").disabled = (currentPage === totalPages || totalPages === 0);
    }

    function goToPage(direction) {
        const totalPages = Math.ceil(filteredData.length / rowsPerPage);
        if (direction === 'prev' && currentPage > 1) currentPage--;
        if (direction === 'next' && currentPage < totalPages) currentPage++;
        renderTable();
    }

    // Live search query matching on fields
    function filterBookings() {
        searchQuery = document.getElementById("searchInput").value.toLowerCase();
        applyFilters();
    }

    function filterByStatus(status) {
        selectedStatusFilter = status;
        document.querySelectorAll(".filter-btn").forEach(btn => btn.classList.remove("active"));
        document.getElementById(`btn_${status.toLowerCase()}`).classList.add("active");
        applyFilters();
    }

    function applyFilters() {
        filteredData = bookingsData.filter(item => {
            const statusMatch = (selectedStatusFilter === 'All') || (item.status.toLowerCase() === selectedStatusFilter.toLowerCase());
            const searchMatch = (item.hall_name.toLowerCase().includes(searchQuery)) || 
                                (item.event_type.toLowerCase().includes(searchQuery)) || 
                                (item.date.includes(searchQuery)) ||
                                (String(item.id).includes(searchQuery));
            return statusMatch && searchMatch;
        });
        currentPage = 1;
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

    window.onload = renderTable;
</script>
</body>
</html>