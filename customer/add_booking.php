<?php
// Start session to manage user login state
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// STRICT AUTHENTICATION GUARD
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

$db_connected = false;
$db_error_message = "";
$halls = [];
$bookings_list = [];

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db_connected = true;

    // Fetch halls
    $stmt = $conn->query("SELECT hall_id AS id, hall_name AS name, hall_capacity FROM hall");
    $halls = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch only logged-in user bookings
    $stmt_bookings = $conn->prepare("SELECT b.booking_id AS id, h.hall_name AS hall_name, b.event_date AS date, b.status 
                                   FROM booking b 
                                   JOIN hall h ON b.hall_id = h.hall_id 
                                   WHERE b.cust_id = :cust_id 
                                   ORDER BY b.booking_id DESC");
    $stmt_bookings->execute([':cust_id' => $current_cust_id]);
    $bookings_list = $stmt_bookings->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    $db_connected = false;
    $db_error_message = $e->getMessage();
}

$success_msg = "";
$error_msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $hall_id = $_POST['hall_id'] ?? '';
    $booking_date = $_POST['booking_date'] ?? '';
    $event_type = $_POST['event_type'] ?? '';
    $guests = $_POST['guests'] ?? '';
    $today = date('Y-m-d');

    if (!empty($hall_id) && !empty($booking_date) && !empty($event_type)) {
        if ($booking_date < $today) {
            $error_msg = "Error: You cannot book a past date.";
        } else {
            if ($db_connected) {
                try {
                    $stmt_insert = $conn->prepare("INSERT INTO booking (cust_id, hall_id, event_type, event_date, guest_count, status) 
                                                   VALUES (:cust_id, :hall_id, :event_type, :event_date, :guest_count, 'Pending')");
                    $stmt_insert->execute([
                        ':cust_id' => $current_cust_id,
                        ':hall_id' => $hall_id,
                        ':event_type' => $event_type,
                        ':event_date' => $booking_date,
                        ':guest_count' => $guests
                    ]);

                    // Refresh
                    $stmt_bookings = $conn->prepare("SELECT b.booking_id AS id, h.hall_name AS hall_name, b.event_date AS date, b.status 
                                                   FROM booking b 
                                                   JOIN hall h ON b.hall_id = h.hall_id 
                                                   WHERE b.cust_id = :cust_id 
                                                   ORDER BY b.booking_id DESC");
                    $stmt_bookings->execute([':cust_id' => $current_cust_id]);
                    $bookings_list = $stmt_bookings->fetchAll(PDO::FETCH_ASSOC);

                    $success_msg = "🎉 Booking request submitted successfully! Pending approval.";
                } catch (PDOException $ex) {
                    $error_msg = "Database Submission Error: " . $ex->getMessage();
                }
            }
        }
    } else {
        $error_msg = "Please fill in all required fields.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Booking - HallBook</title>
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

        .booking-page { display: grid; grid-template-columns: 350px 1fr; gap: 30px; }
        .booking-card { background: var(--white); padding: 30px; border-radius: 12px; border: 1px solid var(--border-color); box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); }
        .booking-card h3 { font-size: 1.1rem; margin-bottom: 24px; border-bottom: 1px solid var(--border-color); padding-bottom: 12px; }

        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-size: 0.85rem; color: #64748b; font-weight: 600; }
        .form-control { width: 100%; padding: 12px 16px; border: 1px solid var(--border-color); border-radius: 8px; }
        .form-control:focus { border-color: var(--primary); outline: none; }

        .btn-submit { width: 100%; background: var(--primary); color: white; border: none; padding: 14px; border-radius: 8px; font-weight: 600; cursor: pointer; }
        .btn-submit:hover { background: var(--primary-hover); }

        table { width: 100%; border-collapse: collapse; }
        th { background-color: #f8fafc; color: #64748b; font-weight: 600; padding: 16px; text-transform: uppercase; border-bottom: 2px solid var(--border-color); font-size: 0.85rem; }
        td { padding: 16px; border-bottom: 1px solid var(--border-color); }
        
        .badge { padding: 6px 12px; border-radius: 20px; font-weight: 600; font-size: 0.8rem; }
        .badge-approved { background: #dcfce7; color: #15803d; }
        .badge-pending { background: #fef3c7; color: #b45309; }
        .badge-cancelled { background: #fee2e2; color: #b91c1c; }

        .alert { padding: 16px 20px; border-radius: 10px; margin-bottom: 24px; }
        .alert-success { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
        .alert-error { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }

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
            <a href="dashboard.php" class="nav-item">Dashboard</a>
            <a href="add_booking.php" class="nav-item active">Add Booking</a>
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
            <h1 class="page-title">Add Booking</h1>
        </div>

        <?php if (!empty($success_msg)): ?>
            <div class="alert alert-success"><?= $success_msg ?></div>
        <?php endif; ?>
        <?php if (!empty($error_msg)): ?>
            <div class="alert alert-error"><?= $error_msg ?></div>
        <?php endif; ?>

        <div class="booking-page">
            <div class="booking-card">
                <h3>Add New Booking</h3>
                <form action="" method="POST">
                    <div class="form-group">
                        <label for="booking_date">Select Date</label>
                        <input type="date" id="booking_date" name="booking_date" class="form-control" min="<?= date('Y-m-d') ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="event_type">Select Event Type</label>
                        <select id="event_type" name="event_type" class="form-control" required>
                            <option value="">-- Choose Event --</option>
                            <option value="Wedding">Wedding</option>
                            <option value="Corporate">Corporate</option>
                            <option value="Seminar">Seminar</option>
                            <option value="Party">Party</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="guests">Enter Number of Guests</label>
                        <input type="number" id="guests" name="guests" class="form-control" min="1" placeholder="e.g. 150" oninput="filterHallsByGuests()" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="hall_select">Select Hall</label>
                        <select id="hall_select" name="hall_id" class="form-control" required>
                            <option value="">-- Choose Hall --</option>
                            <?php foreach ($halls as $hall): ?>
                                <option value="<?= htmlspecialchars($hall['id']) ?>" data-capacity="<?= htmlspecialchars($hall['hall_capacity']) ?>">
                                    <?= htmlspecialchars($hall['name']) ?> (Max: <?= htmlspecialchars($hall['hall_capacity']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <button type="submit" class="btn-submit">Submit Booking</button>
                </form>
            </div>

            <div class="booking-card">
                <h3>Manage Booking</h3>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Hall Name</th>
                                <th>Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($bookings_list) > 0): ?>
                                <?php foreach ($bookings_list as $booking): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($booking['hall_name']) ?></td>
                                        <td><?= htmlspecialchars($booking['date']) ?></td>
                                        <td>
                                            <?php 
                                            $statusClass = 'badge-pending';
                                            $statusText = strtolower($booking['status']);
                                            if ($statusText === 'approved' || $statusText === 'success') $statusClass = 'badge-approved';
                                            if ($statusText === 'rejected' || $statusText === 'cancelled') $statusClass = 'badge-cancelled';
                                            ?>
                                            <span class="badge <?= $statusClass ?>"><?= htmlspecialchars($booking['status'] ?: 'Pending') ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="3" style="text-align: center; color: #94a3b8; padding: 20px;">No bookings found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
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
function filterHallsByGuests() {
    const guestInput = document.getElementById('guests');
    const hallSelect = document.getElementById('hall_select');
    const guestCount = parseInt(guestInput.value) || 0;
    const options = hallSelect.options;

    for (let i = 1; i < options.length; i++) {
        const capacity = parseInt(options[i].getAttribute('data-capacity')) || 0;
        if (capacity < guestCount) {
            options[i].disabled = true;
            options[i].style.color = '#cbd5e1';
        } else {
            options[i].disabled = false;
            options[i].style.color = '';
        }
    }
}

function openLogoutModal() { document.getElementById('logoutModal').classList.add('open'); }
function closeLogoutModal() { document.getElementById('logoutModal').classList.remove('open'); }
function confirmLogout() { window.location.href = "../auth/logout.php"; }
</script>
</body>
</html>