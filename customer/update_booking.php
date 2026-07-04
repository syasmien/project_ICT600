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

    // Fetch halls including status attributes
    $stmt_halls = $conn->query("SELECT hall_id AS id, hall_name AS name, hall_capacity AS capacity, price, status FROM hall");
    $halls = $stmt_halls->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $db_connected = false;
    $db_error_message = $e->getMessage();
}

// --- AJAX INTERFACE EXTENSION: VERIFY OVERLAPPING EXCLUSIONS ---
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    if ($_GET['action'] === 'get_booked_halls' && isset($_GET['start_date']) && isset($_GET['end_date']) && isset($_GET['current_booking_id'])) {
        $start = $_GET['start_date'];
        $end = $_GET['end_date'];
        $b_id = $_GET['current_booking_id'];
        try {
            // Overlapping validation rule ignoring the booking entry currently being updated
            $stmt = $conn->prepare("SELECT DISTINCT hall_id FROM booking 
                                    WHERE (:start_date <= end_date AND :end_date >= start_date) 
                                    AND booking_id != :b_id
                                    AND status IN ('Pending', 'Approved')");
            $stmt->execute([':start_date' => $start, ':end_date' => $end, ':b_id' => $b_id]);
            echo json_encode($stmt->fetchAll(PDO::FETCH_COLUMN));
        } catch (PDOException $e) {
            echo json_encode([]);
        }
        exit();
    }
}

// Extract specific targeted parameters safely
if (isset($_GET['id'])) {
    $booking_id = intval($_GET['id']);
}

// Fetch primary targeted row validation details
if ($db_connected && $booking_id > 0) {
    try {
        $stmt_load = $conn->prepare("SELECT * FROM booking WHERE booking_id = :booking_id AND cust_id = :cust_id");
        $stmt_load->execute([':booking_id' => $booking_id, ':cust_id' => $current_cust_id]);
        $booking_data = $stmt_load->fetch(PDO::FETCH_ASSOC);
        
        // Block adjustment access rules if item parameter returns clean status discrepancies
        if (!$booking_data) {
            header("Location: booking_status.php");
            exit();
        }
        if ($booking_data['status'] !== 'Pending') {
            header("Location: booking_status.php?msg=only_pending_editable");
            exit();
        }
    } catch(PDOException $eload) {
        $db_error_message = $eload->getMessage();
    }
} else {
    header("Location: booking_status.php");
    exit();
}

$success_msg = "";
$error_msg = "";

// --- UPDATE FORM ACTION ROUTINE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_booking_submit'])) {
    $hall_id = $_POST['hall_id'] ?? '';
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';
    $event_type = $_POST['event_type'] ?? '';
    $guest_count = intval($_POST['guest_count'] ?? 0);
    $today = date('Y-m-d');

    if (!empty($hall_id) && !empty($start_date) && !empty($end_date) && !empty($event_type) && $guest_count > 0) {
        if ($start_date < $today || $end_date < $start_date) {
            $error_msg = "Error: Invalid date matrix parameters specified.";
        } else {
            try {
                // Strict overlapping validation excluding self index position
                $stmt_check = $conn->prepare("SELECT COUNT(*) FROM booking 
                                               WHERE hall_id = :hall_id 
                                               AND (:start_date <= end_date AND :end_date >= start_date) 
                                               AND booking_id != :booking_id
                                               AND status IN ('Pending', 'Approved')");
                $stmt_check->execute([
                    ':hall_id' => $hall_id,
                    ':start_date' => $start_date,
                    ':end_date' => $end_date,
                    ':booking_id' => $booking_id
                ]);
                $conflict = $stmt_check->fetchColumn();

                // Check structural capacity rules
                $stmt_hall = $conn->prepare("SELECT price, status, hall_capacity FROM hall WHERE hall_id = :hall_id");
                $stmt_hall->execute([':hall_id' => $hall_id]);
                $hall_data = $stmt_hall->fetch(PDO::FETCH_ASSOC);

                if ($hall_data && $hall_data['status'] !== 'Available') {
                    $error_msg = "This hall choice is completely " . htmlspecialchars($hall_data['status']) . ".";
                } elseif ($hall_data && $hall_data['hall_capacity'] < $guest_count) {
                    $error_msg = "This hall capacity threshold is insufficient to host your guests.";
                } elseif ($conflict > 0) {
                    $error_msg = "This hall is already booked during the selected dates.";
                } else {
                    // Recalculate duration matrix allocations
                    $days = (strtotime($end_date) - strtotime($start_date)) / (60 * 60 * 24) + 1;
                    $total_price = $days * ($hall_data['price'] ?? 0);

                    $stmt_update = $conn->prepare("UPDATE booking 
                                                   SET hall_id = :hall_id, event_type = :event_type, start_date = :start_date, 
                                                       end_date = :end_date, guest_count = :guest_count, total_days = :total_days, 
                                                       total_price = :total_price 
                                                   WHERE booking_id = :booking_id AND cust_id = :cust_id");
                    $stmt_update->execute([
                        ':hall_id' => $hall_id,
                        ':event_type' => $event_type,
                        ':start_date' => $start_date,
                        ':end_date' => $end_date,
                        ':guest_count' => $guest_count,
                        ':total_days' => $days,
                        ':total_price' => $total_price,
                        ':booking_id' => $booking_id,
                        ':cust_id' => $current_cust_id
                    ]);

                    $success_msg = "🎉 Booking parameters updated successfully!";
                    
                    // Reload instance values variables mapping layout profiles
                    $booking_data['hall_id'] = $hall_id;
                    $booking_data['event_type'] = $event_type;
                    $booking_data['start_date'] = $start_date;
                    $booking_data['end_date'] = $end_date;
                    $booking_data['guest_count'] = $guest_count;
                }
            } catch(PDOException $ex) {
                $error_msg = "System Adjustment Error: " . $ex->getMessage();
            }
        }
    } else {
        $error_msg = "Please fill in all mandatory input elements.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modify Booking - HallBook</title>
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
            --success: #10b981;
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

        .booking-page { display: grid; grid-template-columns: 1fr 380px; gap: 30px; }
        .booking-card { background: var(--white); padding: 30px; border-radius: 12px; border: 1px solid var(--border-color); box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); margin-bottom: 25px; }
        .booking-card h3 { font-size: 1.1rem; margin-bottom: 24px; border-bottom: 1px solid var(--border-color); padding-bottom: 12px; }

        /* Progress Steps Styling */
        .progress-indicator { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; background: var(--white); padding: 15px 20px; border-radius: 10px; border: 1px solid var(--border-color); }
        .step { display: flex; align-items: center; gap: 8px; font-size: 0.85rem; font-weight: 600; color: #94a3b8; transition: color 0.3s; }
        .step-num { width: 22px; height: 22px; border-radius: 50%; background: #e2e8f0; color: #64748b; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; }
        .step.active { color: var(--primary); }
        .step.active .step-num { background: var(--primary); color: white; }
        .step.completed { color: var(--success); }
        .step.completed .step-num { background: var(--success); color: white; }
        .step-divider { flex: 1; height: 2px; background: #e2e8f0; margin: 0 10px; }

        /* Summary Panel Styling */
        .summary-list { display: flex; flex-direction: column; gap: 12px; }
        .summary-item { display: flex; justify-content: space-between; font-size: 0.9rem; padding-bottom: 8px; border-bottom: 1px dashed var(--border-color); }
        .summary-item.total { border-bottom: none; font-weight: 700; font-size: 1.1rem; color: var(--primary); padding-top: 5px; }

        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-size: 0.85rem; color: #64748b; font-weight: 600; }
        .form-control { width: 100%; padding: 12px 16px; border: 1px solid var(--border-color); border-radius: 8px; }
        .form-control:focus { border-color: var(--primary); outline: none; }

        .btn-submit { width: 100%; background: var(--primary); color: white; border: none; padding: 14px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: background 0.2s; }
        .btn-submit:hover { background: var(--primary-hover); }

        .alert { padding: 16px 20px; border-radius: 10px; margin-bottom: 24px; }
        .alert-success { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
        .alert-error { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }
        .warning-box { background: #fffbeb; color: #b45309; border: 1px solid #fef3c7; display: none; padding: 12px; border-radius: 6px; margin-bottom: 15px; font-size: 0.85rem; font-weight: 500; }

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
            <h1 class="page-title">Modify Booking Details</h1>
        </div>

        <div class="progress-indicator">
            <div class="step" id="step_1"><span class="step-num">1</span> Event Details</div>
            <div class="step-divider"></div>
            <div class="step" id="step_2"><span class="step-num">2</span> Hall Selection</div>
            <div class="step-divider"></div>
            <div class="step" id="step_3"><span class="step-num">3</span> Booking Summary</div>
            <div class="step-divider"></div>
            <div class="step" id="step_4"><span class="step-num">4</span> Update Booking</div>
        </div>

        <?php if (!empty($success_msg)): ?>
            <div class="alert alert-success"><?= $success_msg ?></div>
        <?php endif; ?>
        <?php if (!empty($error_msg)): ?>
            <div class="alert alert-error"><?= $error_msg ?></div>
        <?php endif; ?>

        <div class="booking-page">
            <div class="booking-card">
                <h3>Edit Booking Data Matrix</h3>
                <div class="warning-box" id="warningText"></div>
                
                <form action="" method="POST" id="updateBookingForm">
                    <div class="form-group">
                        <label for="event_type">Select Event Type</label>
                        <select id="event_type" name="event_type" class="form-control" onchange="evaluateProgress()" required>
                            <option value="Wedding" <?= ($booking_data['event_type'] === 'Wedding') ? 'selected' : '' ?>>Wedding</option>
                            <option value="Corporate" <?= ($booking_data['event_type'] === 'Corporate') ? 'selected' : '' ?>>Corporate</option>
                            <option value="Seminar" <?= ($booking_data['event_type'] === 'Seminar') ? 'selected' : '' ?>>Seminar</option>
                            <option value="Party" <?= ($booking_data['event_type'] === 'Party') ? 'selected' : '' ?>>Party</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="start_date">Start Date</label>
                        <input type="date" id="start_date" name="start_date" class="form-control" min="<?= date('Y-m-d') ?>" value="<?= htmlspecialchars($booking_data['start_date']) ?>" onchange="onDateOrGuestChange()" required>
                    </div>

                    <div class="form-group">
                        <label for="end_date">End Date</label>
                        <input type="date" id="end_date" name="end_date" class="form-control" min="<?= date('Y-m-d') ?>" value="<?= htmlspecialchars($booking_data['end_date']) ?>" onchange="onDateOrGuestChange()" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="guest_count">Enter Number of Guests</label>
                        <input type="number" id="guest_count" name="guest_count" class="form-control" min="1" value="<?= htmlspecialchars($booking_data['guest_count']) ?>" oninput="onDateOrGuestChange()" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="hall_select">Select Hall</label>
                        <select id="hall_select" name="hall_id" class="form-control" onchange="onHallChange()" required>
                            <?php foreach ($halls as $hall): ?>
                                <option value="<?= htmlspecialchars($hall['id']) ?>" 
                                        data-capacity="<?= htmlspecialchars($hall['capacity']) ?>" 
                                        data-price="<?= htmlspecialchars($hall['price']) ?>"
                                        data-status="<?= htmlspecialchars($hall['status']) ?>"
                                        data-original-name="<?= htmlspecialchars($hall['name']) ?>"
                                        <?= ($booking_data['hall_id'] == $hall['id']) ? 'selected' : '' ?>>
                                    <?php 
                                    if ($hall['status'] === 'Available') {
                                        echo htmlspecialchars($hall['name']) . " (Max: " . htmlspecialchars($hall['capacity']) . ")";
                                    } else {
                                        echo htmlspecialchars($hall['name']) . " (" . htmlspecialchars($hall['status']) . ")";
                                    }
                                    ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <button type="submit" id="btnSubmit" name="update_booking_submit" class="btn-submit">Save Structural Changes</button>
                </form>
            </div>

            <div class="booking-card" style="height: fit-content;">
                <h3>Live Price Calculations</h3>
                <div class="summary-list">
                    <div class="summary-item">
                        <span>Selected Hall:</span>
                        <span id="summary_hall">-</span>
                    </div>
                    <div class="summary-item">
                        <span>Hall Capacity:</span>
                        <span id="summary_capacity">-</span>
                    </div>
                    <div class="summary-item">
                        <span>Price Per Day:</span>
                        <span id="summary_price_day">-</span>
                    </div>
                    <div class="summary-item">
                        <span>Booking Duration:</span>
                        <span id="summary_duration">-</span>
                    </div>
                    <div class="summary-item total">
                        <span>Total Price:</span>
                        <span id="summary_total_price">RM 0.00</span>
                    </div>
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
const currentBookingId = <?= $booking_id ?>;

function onDateOrGuestChange() {
    calculateDurationAndPrice();
    checkConflictingBookings();
    evaluateProgress();
}

function onHallChange() {
    calculateDurationAndPrice();
    evaluateProgress();
}

function evaluateProgress() {
    const step1 = document.getElementById('step_1');
    const step2 = document.getElementById('step_2');
    const step3 = document.getElementById('step_3');
    const step4 = document.getElementById('step_4');

    const eventType = document.getElementById('event_type').value;
    const start = document.getElementById('start_date').value;
    const end = document.getElementById('end_date').value;
    const guests = document.getElementById('guest_count').value;
    const hallSelected = document.getElementById('hall_select').value;

    [step1, step2, step3, step4].forEach(s => s.className = 'step');

    if (eventType && start && end && guests) {
        step1.className = 'step completed';
        step2.className = 'step active';
    } else {
        step1.className = 'step active';
        return;
    }

    if (hallSelected) {
        step2.className = 'step completed';
        step3.className = 'step active';
    } else {
        return;
    }

    const startTimestamp = new Date(start).getTime();
    const endTimestamp = new Date(end).getTime();
    if (endTimestamp >= startTimestamp && parseInt(guests) > 0) {
        step3.className = 'step completed';
        step4.className = 'step active';
    }
}

function calculateDurationAndPrice() {
    const startStr = document.getElementById('start_date').value;
    const endStr = document.getElementById('end_date').value;
    const hallSelect = document.getElementById('hall_select');
    const selectedOption = hallSelect.options[hallSelect.selectedIndex];

    let totalDays = 0;
    let pricePerDay = 0;
    let totalCost = 0;

    if (startStr && endStr) {
        const start = new Date(startStr);
        const end = new Date(endStr);
        if (end >= start) {
            const diffTime = Math.abs(end - start);
            totalDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
        }
    }

    if (hallSelect.value && selectedOption) {
        pricePerDay = parseFloat(selectedOption.getAttribute('data-price')) || 0;
        document.getElementById('summary_hall').textContent = selectedOption.getAttribute('data-original-name');
        document.getElementById('summary_capacity').textContent = selectedOption.getAttribute('data-capacity') + " pax";
        document.getElementById('summary_price_day').textContent = "RM " + pricePerDay.toFixed(2);
    } else {
        document.getElementById('summary_hall').textContent = "-";
        document.getElementById('summary_capacity').textContent = "-";
        document.getElementById('summary_price_day').textContent = "-";
    }

    if (totalDays > 0) {
        document.getElementById('summary_duration').textContent = totalDays + " Day(s)";
        totalCost = totalDays * pricePerDay;
    } else {
        document.getElementById('summary_duration').textContent = "-";
    }

    document.getElementById('summary_total_price').textContent = "RM " + totalCost.toFixed(2);
}

function checkConflictingBookings() {
    const start = document.getElementById('start_date').value;
    const end = document.getElementById('end_date').value;
    const guestInput = document.getElementById('guest_count');
    const guestCount = parseInt(guestInput.value) || 0;
    const hallSelect = document.getElementById('hall_select');
    const options = hallSelect.options;
    const warningText = document.getElementById('warningText');

    let availableHalls = 0;

    for (let i = 0; i < options.length; i++) {
        const status = options[i].getAttribute('data-status');
        const cap = parseInt(options[i].getAttribute('data-capacity')) || 0;
        const baseName = options[i].getAttribute('data-original-name');

        if (status === 'Unavailable' || status === 'Maintenance') {
            options[i].disabled = true;
            options[i].style.color = '#ef4444';
            options[i].textContent = `${baseName} (${status})`;
            continue;
        }

        if (cap < guestCount) {
            options[i].disabled = true;
            options[i].style.color = '#cbd5e1';
            options[i].textContent = `${baseName} (Insufficient Capacity)`;
            continue;
        }

        options[i].disabled = false;
        options[i].style.color = '';
        options[i].textContent = `${baseName} (Max: ${cap})`;
        availableHalls++;
    }

    // Capacity structural warnings management handler
    if (guestCount > 0 && availableHalls === 0) {
        warningText.innerHTML = "❌ No halls are spacious enough to fit " + guestCount + " guests!";
        warningText.style.display = 'block';
        document.getElementById('btnSubmit').disabled = true;
        document.getElementById('btnSubmit').style.opacity = '0.5';
    } else {
        warningText.style.display = 'none';
        document.getElementById('btnSubmit').disabled = false;
        document.getElementById('btnSubmit').style.opacity = '';
    }

    if (!start || !end || (new Date(end) < new Date(start))) return;

    // Run overlapping checks via AJAX action queries
    fetch(`update_booking.php?id=${currentBookingId}&action=get_booked_halls&start_date=${start}&end_date=${end}&current_booking_id=${currentBookingId}`)
        .then(response => response.json())
        .then(bookedIds => {
            for (let i = 0; i < options.length; i++) {
                if (options[i].disabled && options[i].getAttribute('data-status') !== 'Available') continue;
                
                const currentCap = parseInt(options[i].getAttribute('data-capacity')) || 0;
                if (currentCap < guestCount) continue;

                const hallId = options[i].value;
                const baseName = options[i].getAttribute('data-original-name');

                if (bookedIds.includes(hallId)) {
                    options[i].disabled = true;
                    options[i].style.color = '#f59e0b';
                    options[i].textContent = `${baseName} (Booked)`;
                    
                    // If current chosen option is now dynamically booked, force selection update trigger validation rules
                    if (hallSelect.value === hallId) {
                        warningText.innerHTML = "⚠️ Warning: The selected hall is already booked for these dates.";
                        warningText.style.display = 'block';
                    }
                }
            }
        });
}

function openLogoutModal() { document.getElementById('logoutModal').classList.add('open'); }
function closeLogoutModal() { document.getElementById('logoutModal').classList.remove('open'); }
function confirmLogout() { window.location.href = "../auth/logout.php"; }

window.onload = function() {
    calculateDurationAndPrice();
    checkConflictingBookings();
    evaluateProgress();
};
</script>
</body>
</html>