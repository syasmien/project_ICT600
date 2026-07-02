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
$halls = [];
$booking_data = null;
$booking_id = 0;

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db_connected = true;

    // Retrieve Halls from Database using safe dynamic discovery
    try {
        $stmt_halls = $conn->query("SELECT hall_id AS id, hall_name AS name, hall_capacity AS capacity FROM hall");
        $halls = $stmt_halls->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $ehall) {
        $stmt_halls = $conn->query("SELECT hall_id AS id, hall_name AS name, capacity FROM hall");
        $halls = $stmt_halls->fetchAll(PDO::FETCH_ASSOC);
    }

} catch(PDOException $e) {
    $db_connected = false;
    $db_error_message = $e->getMessage();
}

// 2. Retrieve Booking ID from URL GET parameter
if (isset($_GET['booking_id'])) {
    $booking_id = intval($_GET['booking_id']);
    
    if ($db_connected) {
        try {
            // Fetch current booking details securely
            // Dynamic enhancement: We query all statuses, so the page can open even if the item is approved/cancelled,
            // but we'll conditionally disable the "Save Changes" form button if it is not 'Pending'.
            $fetch_query = "SELECT booking_id AS id, hall_id, event_type, event_date AS date, guest_count AS guests, status 
                            FROM booking 
                            WHERE booking_id = :booking_id AND cust_id = :cust_id";
            $stmt = $conn->prepare($fetch_query);
            $stmt->execute([
                ':booking_id' => $booking_id,
                ':cust_id' => $current_cust_id
            ]);
            $booking_data = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $ex) {
            $db_error_message = $ex->getMessage();
        }
    }
}

// Handle Update Request Form Submission
$success_msg = "";
$error_msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_booking'])) {
    $hall_id = $_POST['hall_id'] ?? '';
    $booking_date = $_POST['booking_date'] ?? '';
    $event_type = $_POST['event_type'] ?? '';
    $guests = intval($_POST['guests'] ?? 0);
    
    $today = date('Y-m-d');

    if (!empty($hall_id) && !empty($booking_date) && !empty($event_type)) {
        if ($booking_date < $today) {
            $error_msg = "Error: You cannot select a date in the past. Please select today's date or a future date.";
        } else {
            if ($db_connected) {
                try {
                    // Double check pending status on backend update attempt as validation constraint
                    if ($booking_data && strtolower($booking_data['status']) !== 'pending') {
                        $error_msg = "Error: You can only update bookings that are in a 'Pending' status.";
                    } else {
                        // Secure prepared query updating database parameters
                        $update_query = "UPDATE booking 
                                         SET hall_id = :hall_id, event_date = :event_date, event_type = :event_type, guest_count = :guests 
                                         WHERE booking_id = :booking_id AND cust_id = :cust_id AND status = 'Pending'";
                        $stmt_update = $conn->prepare($update_query);
                        $stmt_update->execute([
                            ':hall_id' => $hall_id,
                            ':event_date' => $booking_date,
                            ':event_type' => $event_type,
                            ':guests' => $guests,
                            ':booking_id' => $booking_id,
                            ':cust_id' => $current_cust_id
                        ]);

                        if ($stmt_update->rowCount() > 0) {
                            $success_msg = "🎉 Booking #$booking_id updated successfully!";
                            
                            // Refetch fresh details
                            $stmt_fresh = $conn->prepare("SELECT booking_id AS id, hall_id, event_type, event_date AS date, guest_count AS guests, status 
                                                          FROM booking 
                                                          WHERE booking_id = :booking_id");
                            $stmt_fresh->execute([':booking_id' => $booking_id]);
                            $booking_data = $stmt_fresh->fetch(PDO::FETCH_ASSOC);
                        } else {
                            $error_msg = "No changes were saved, or booking is no longer pending.";
                        }
                    }
                } catch (PDOException $ex) {
                    $error_msg = "Database Update Error: " . $ex->getMessage();
                }
            } else {
                $error_msg = "Database connection offline. Unable to update.";
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
    <title>Edit Booking - HallBook</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    
    <style>
        /* Modern Design System Tokens */
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

        /* Layout Container */
        .layout {
            display: flex;
            min-height: 100vh;
            width: 100%;
        }

        /* Sidebar Styling (Matches Dashboard) */
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

        /* Sidebar Footer components */
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

        /* Main Workspace Viewport */
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
            align-items: center;
            gap: 16px;
        }

        .btn-back {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 16px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.9rem;
            color: #475569;
            background-color: white;
            border: 1px solid var(--border-color);
            text-decoration: none;
            transition: all 0.2s;
        }

        .btn-back:hover {
            background-color: #f1f5f9;
            color: #1e293b;
        }

        .page-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: #1e293b;
        }

        /* Alert notifications styling */
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

        /* Centered form card layout structure */
        .editor-container {
            max-width: 600px;
            margin: 0 auto;
        }

        .booking-card {
            background: var(--white);
            padding: 32px;
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

        /* Form Controls */
        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 0.85rem;
            color: #64748b;
            font-weight: 600;
        }

        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 0.95rem;
            color: #334155;
            background-color: #f8fafc;
            outline: none;
            transition: all 0.2s ease;
        }

        .form-control:focus {
            border-color: var(--primary);
            background-color: #ffffff;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .form-control:disabled {
            background-color: #e2e8f0;
            color: #94a3b8;
            cursor: not-allowed;
        }

        .warning-text {
            display: none;
            color: var(--danger);
            font-size: 0.85rem;
            font-weight: 600;
            margin-top: 8px;
        }

        .btn-submit {
            width: 100%;
            background-color: var(--primary);
            color: white;
            border: none;
            padding: 14px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            transition: background-color 0.2s ease;
            margin-top: 10px;
        }

        .btn-submit:hover {
            background-color: var(--primary-hover);
        }

        /* Modal Dialog Styling */
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
    <!-- Sidebar Navigation panel -->
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

    <!-- Main Content Workspace Area -->
    <main class="main-content">
        <div class="topbar">
            <a href="booking_status.php" class="btn-back">← Back to History</a>
            <h1 class="page-title">Edit Booking</h1>
        </div>

        <!-- Connection Error Diagnostics Alert -->
        <?php if (!$db_connected && !empty($db_error_message)): ?>
            <div class="alert alert-error">
                <strong>Database Error Diagnostic Alert:</strong> Connection offline.<br>
                <code style="font-family: monospace; display: block; margin-top: 8px; font-size: 0.85rem; color: #7f1d1d;">Error: <?= htmlspecialchars($db_error_message) ?></code>
            </div>
        <?php endif; ?>

        <!-- Notification Response Alerts -->
        <?php if (!empty($success_msg)): ?>
            <div class="alert alert-success"><?= $success_msg ?></div>
        <?php endif; ?>
        <?php if (!empty($error_msg)): ?>
            <div class="alert alert-error"><?= $error_msg ?></div>
        <?php endif; ?>

        <div class="editor-container">
            <?php if ($booking_data): ?>
                <div class="booking-card">
                    <h3>Modify Booking #<?= htmlspecialchars($booking_id) ?></h3>
                    
                    <?php 
                    $isPending = (strtolower($booking_data['status']) === 'pending');
                    if (!$isPending): 
                    ?>
                        <div class="alert alert-error" style="background-color: #fee2e2; color: #b91c1c; border-color: #fecaca; margin-bottom: 20px;">
                            ℹ️ This booking is currently in <strong>"<?= htmlspecialchars($booking_data['status']) ?>"</strong> status and cannot be modified.
                        </div>
                    <?php endif; ?>

                    <form action="" method="POST" id="updateForm">
                        <input type="hidden" name="update_booking" value="1">
                        
                        <div class="form-group">
                            <label for="booking_date">Select Date</label>
                            <input type="date" id="booking_date" name="booking_date" class="form-control" 
                                   value="<?= htmlspecialchars($booking_data['date']) ?>" 
                                   min="<?= date('Y-m-d') ?>" <?= !$isPending ? 'disabled' : '' ?> required>
                        </div>
                        
                        <div class="form-group">
                            <label for="event_type">Select Event Type</label>
                            <select id="event_type" name="event_type" class="form-control" <?= !$isPending ? 'disabled' : '' ?> required>
                                <option value="">-- Choose Event --</option>
                                <option value="Wedding" <?= ($booking_data['event_type'] === 'Wedding') ? 'selected' : '' ?>>Wedding</option>
                                <option value="Corporate" <?= ($booking_data['event_type'] === 'Corporate') ? 'selected' : '' ?>>Corporate</option>
                                <option value="Seminar" <?= ($booking_data['event_type'] === 'Seminar') ? 'selected' : '' ?>>Seminar</option>
                                <option value="Party" <?= ($booking_data['event_type'] === 'Party') ? 'selected' : '' ?>>Party</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="guest_count">Enter Number of Guests</label>
                            <input type="number" id="guest_count" name="guests" class="form-control" 
                                   value="<?= htmlspecialchars($booking_data['guests']) ?>" 
                                   min="1" oninput="filterHallsByGuests()" <?= !$isPending ? 'disabled' : '' ?> required>
                        </div>
                        
                        <div class="form-group">
                            <label for="hall_id">Select Hall</label>
                            <select id="hall_id" name="hall_id" class="form-control" <?= !$isPending ? 'disabled' : '' ?> required>
                                <option value="">-- Choose Hall --</option>
                                <?php foreach ($halls as $hall): ?>
                                    <option value="<?= htmlspecialchars($hall['id']) ?>" 
                                            data-capacity="<?= htmlspecialchars($hall['capacity']) ?>"
                                            <?= ($booking_data['hall_id'] == $hall['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($hall['name']) ?> (Max Capacity: <?= htmlspecialchars($hall['capacity']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="warning-text" id="hall_warning"></div>
                        </div>

                        <?php if ($isPending): ?>
                            <button type="submit" class="btn-submit" id="btnSubmit">Save Changes</button>
                        <?php else: ?>
                            <button type="button" class="btn-submit" style="background-color: #cbd5e1; cursor: not-allowed;" disabled>Locked (Cannot Edit)</button>
                        <?php endif; ?>
                    </form>
                </div>
            <?php else: ?>
                <div class="booking-card" style="text-align: center; padding: 40px 20px;">
                    <span style="font-size: 3rem; display: block; margin-bottom: 16px;">🔍</span>
                    <h3 style="border-bottom: none; margin-bottom: 8px;">Booking Record Not Found</h3>
                    <p style="color: #64748b; font-size: 0.95rem; margin-bottom: 24px;">The specified booking does not belong to your account or does not exist.</p>
                    <a href="booking_status.php" class="btn-back" style="display: inline-flex;">Return to History</a>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<!-- Logout Confirmation Dialog Modal -->
<div class="modal" id="logoutModal">
    <div class="modal-box">
        <h4 class="modal-title">Confirm Logout</h4>
        <p class="modal-message">Are you sure you want to end your current session and exit the portal?</p>
        <div class="modal-actions">
            <button class="btn btn-secondary" onclick="closeLogoutModal()">Cancel</button>
            <button class="btn btn-danger" onclick="confirmLogout()">Logout</button>
        </div>
    </div>
</div>

<script>
    // Logic to dynamically filter available halls based on guest constraints
    function filterHallsByGuests() {
        const guestInput = document.getElementById('guest_count');
        const hallSelect = document.getElementById('hall_id');
        const warningText = document.getElementById('hall_warning');
        const guestCount = parseInt(guestInput.value) || 0;
        const options = hallSelect.options;
        
        let availableHalls = 0;
        let selectedStillValid = false;
        const currentSelected = hallSelect.value;

        for (let i = 1; i < options.length; i++) {
            const capacity = parseInt(options[i].getAttribute('data-capacity')) || 0;
            if (capacity < guestCount) {
                options[i].disabled = true;
                options[i].style.color = '#cbd5e1';
            } else {
                options[i].disabled = false;
                options[i].style.color = '';
                availableHalls++;
                if (options[i].value === currentSelected) {
                    selectedStillValid = true;
                }
            }
        }

        // Reset dropdown if previous selected hall is too small
        if (!selectedStillValid && availableHalls > 0) {
            hallSelect.value = "";
        }

        // Warn user if guests exceed all capacities
        if (guestCount > 0 && availableHalls === 0) {
            warningText.innerText = "❌ No halls are spacious enough to fit " + guestCount + " guests!";
            warningText.style.display = 'block';
            const btn = document.getElementById('btnSubmit');
            if (btn) {
                btn.disabled = true;
                btn.style.opacity = '0.5';
                btn.style.cursor = 'not-allowed';
            }
        } else {
            warningText.style.display = 'none';
            const btn = document.getElementById('btnSubmit');
            if (btn) {
                btn.disabled = false;
                btn.style.opacity = '';
                btn.style.cursor = '';
            }
        }
    }

    // Modal view controller routines
    function openLogoutModal() {
        document.getElementById('logoutModal').classList.add('open');
    }

    function closeLogoutModal() {
        document.getElementById('logoutModal').classList.remove('open');
    }

    // Redirect to Auth folder logout file
    function confirmLogout() {
        window.location.href = "../auth/logout.php";
    }

    // Trigger filter checks on load
    window.onload = function() {
        if (document.getElementById('guest_count')) {
            filterHallsByGuests();
        }
    };
</script>
</body>
</html>