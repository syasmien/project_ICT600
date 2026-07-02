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
$user_data = [];

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db_connected = true;

    // Fetch existing customer details dynamically (profile_pic removed)
    $stmt = $conn->prepare("SELECT username, full_name, email, phone_num FROM customer WHERE cust_id = :cust_id");
    $stmt->execute([':cust_id' => $current_cust_id]);
    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user_data) {
        // Safe default fallback array structure
        $user_data = [
            'username' => 'customer_username',
            'full_name' => 'Customer',
            'email' => 'customer@hallbook.com',
            'phone_num' => ''
        ];
    }
} catch(PDOException $e) {
    $db_connected = false;
    $db_error_message = $e->getMessage();
}

// Handle Profile Updates Form Submission
$success_msg = "";
$error_msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_profile'])) {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone_num = trim($_POST['phone'] ?? '');
    $username_val = trim($_POST['username'] ?? '');

    if (!empty($full_name) && !empty($email) && !empty($username_val)) {
        if ($db_connected) {
            try {
                // Update database details securely (profile_pic removed from the query)
                $update_stmt = $conn->prepare("UPDATE customer 
                                               SET full_name = :full_name, email = :email, phone_num = :phone_num, username = :username 
                                               WHERE cust_id = :cust_id");
                $update_stmt->execute([
                    ':full_name' => $full_name,
                    ':email' => $email,
                    ':phone_num' => $phone_num,
                    ':username' => $username_val,
                    ':cust_id' => $current_cust_id
                ]);

                // Dynamically synchronize the active session variables to update the sidebar instantly
                $_SESSION['user_name'] = !empty($full_name) ? $full_name : $username_val;
                $_SESSION['user_email'] = $email;

                // Refetch updated details
                $stmt = $conn->prepare("SELECT username, full_name, email, phone_num FROM customer WHERE cust_id = :cust_id");
                $stmt->execute([':cust_id' => $current_cust_id]);
                $user_data = $stmt->fetch(PDO::FETCH_ASSOC);

                $success_msg = "🎉 Profile updated successfully!";
            } catch (PDOException $ex) {
                $error_msg = "Database Update Error: " . $ex->getMessage();
            }
        } else {
            $error_msg = "Database connection offline. Unable to update.";
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
    <title>My Profile - HallBook</title>
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
            overflow: hidden;
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
        }

        .page-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: #1e293b;
        }

        /* Profile Centered Single Column Layout Container */
        .profile-container {
            max-width: 650px;
            margin: 0 auto;
        }

        .booking-card {
            background: var(--white);
            padding: 35px;
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

        /* Form Controls Styling */
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

        /* Alert styling */
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

        /* Interactive Logout Confirmation Overlay */
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
            <a href="booking_status.php" class="nav-item">Booking Status</a>
            <a href="profile.php" class="nav-item active">Profile</a>
        </nav>
        
        <!-- Sidebar User Badge Footer -->
        <div class="sidebar-footer">
            <div class="staff-info">
                <div class="staff-avatar">
                    <?= strtoupper(substr($_SESSION['user_name'] ?? 'C', 0, 1)) ?>
                </div>
                <div class="staff-details">
                    <span class="staff-name"><?= htmlspecialchars($_SESSION['user_name'] ?? 'Customer') ?></span>
                </div>
            </div>
            <button class="logout-btn" onclick="openLogoutModal()">Logout</button>
        </div>
    </aside>

    <!-- Main Content Panel Area -->
    <main class="main-content">
        <div class="topbar">
            <h1 class="page-title">My Profile</h1>
        </div>

        <!-- Connection Error Diagnostics Alert -->
        <?php if (!$db_connected && !empty($db_error_message)): ?>
            <div class="alert alert-error">
                <strong>Database Error Diagnostic Alert:</strong> Connection offline.<br>
                <code style="font-family: monospace; display: block; margin-top: 8px; font-size: 0.85rem; color: #7f1d1d;">Error: <?= htmlspecialchars($db_error_message) ?></code>
            </div>
        <?php endif; ?>

        <?php if (!empty($success_msg)): ?>
            <div class="alert alert-success"><?= $success_msg ?></div>
        <?php endif; ?>
        <?php if (!empty($error_msg)): ?>
            <div class="alert alert-error"><?= $error_msg ?></div>
        <?php endif; ?>

        <!-- Centered Profile Layout -->
        <div class="profile-container">
            <div class="booking-card">
                <h3>Account Details</h3>
                
                <form action="" method="POST">
                    <!-- Full Name -->
                    <div class="form-group">
                        <label for="full_name">Full Name</label>
                        <input type="text" id="full_name" name="full_name" class="form-control" placeholder="e.g. John Doe" value="<?= htmlspecialchars($user_data['full_name']) ?>" required>
                    </div>

                    <!-- Email Address -->
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" class="form-control" placeholder="e.g. johndoe@example.com" value="<?= htmlspecialchars($user_data['email']) ?>" required>
                    </div>

                    <!-- Phone Number -->
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="text" id="phone" name="phone" class="form-control" placeholder="e.g. +6012345678" value="<?= htmlspecialchars($user_data['phone_num']) ?>">
                    </div>

                    <!-- Username -->
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" class="form-control" placeholder="e.g. johndoe99" value="<?= htmlspecialchars($user_data['username']) ?>" required>
                    </div>

                    <button type="submit" name="save_profile" class="btn-submit">Save Changes</button>
                </form>
            </div>
        </div>
    </main>
</div>

<!-- Beautiful Logout Confirmation Modal -->
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
// Modal Toggle Functions
function openLogoutModal() {
    document.getElementById('logoutModal').classList.add('open');
}

function closeLogoutModal() {
    document.getElementById('logoutModal').classList.remove('open');
}

function confirmLogout() {
    window.location.href = "../auth/logout.php";
}
</script>
</body>
</html>