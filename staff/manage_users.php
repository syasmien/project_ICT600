<?php
session_start();
include('../config/db.php'); // Reusing your project database connection

if (!isset($_SESSION['staff_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$staff_name = $_SESSION['name'];

/* =======================================
   ACTIONS: STATUS UPDATE & EDIT PROFILE
======================================= */
$msg = "";
if (isset($_POST['toggle_status'])) {
    $cust_id = mysqli_real_escape_string($conn, $_POST['cust_id']);
    $current_status = mysqli_real_escape_string($conn, $_POST['current_status']);
    $new_status = ($current_status === 'Active') ? 'Inactive' : 'Active';

    $update_query = "UPDATE customer SET status = '$new_status' WHERE cust_id = '$cust_id'";
    if (mysqli_query($conn, $update_query)) {
        header("Location: manage_users.php?msg=Status updated successfully");
        exit();
    }
}

if (isset($_POST['edit_user'])) {
    $cust_id = mysqli_real_escape_string($conn, $_POST['cust_id']);
    $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone_num = mysqli_real_escape_string($conn, $_POST['phone_num']);

    $edit_query = "UPDATE customer SET full_name = '$full_name', username = '$username', email = '$email', phone_num = '$phone_num' WHERE cust_id = '$cust_id'";
    if (mysqli_query($conn, $edit_query)) {
        header("Location: manage_users.php?msg=User details updated successfully");
        exit();
    }
}

/* =======================================
   SEARCH AND DATA RETRIEVAL
======================================= */
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, trim($_GET['search'])) : '';
if (!empty($search)) {
    $query = "SELECT * FROM customer WHERE full_name LIKE '%$search%' OR username LIKE '%$search%' OR email LIKE '%$search%' ORDER BY cust_id DESC";
} else {
    $query = "SELECT * FROM customer ORDER BY cust_id DESC";
}
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - HallBook</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/staff_style.css">
    
    <style>
        /* Maintaining specific page overrides missing from layout base definitions */
        .search-container { margin-bottom: 24px; display: flex; gap: 12px; align-items: center; }
        .search-input { padding: 10px 16px; border: 1px solid var(--border-color, #e2e8f0); border-radius: 8px; font-size: 0.9rem; width: 320px; outline: none; }
        .search-input:focus { border-color: var(--primary, #2563eb); }
        .btn-submit { background: var(--primary, #2563eb); padding: 10px 20px; border-radius: 6px; color: white; font-weight:600; cursor:pointer; border:none;}
        .btn-clear { background: #e2e8f0; color: #475569; padding: 10px 20px; border-radius: 6px; text-decoration: none; font-size: 0.9rem; font-weight:600;}
        .alert-banner { padding: 12px 20px; background-color: #dcfce7; color: #15803d; border-radius: 8px; margin-bottom: 20px; font-size: 0.9rem; font-weight: 500; }
        
        .badge--success { background: #dcfce7; color: #15803d; }
        .badge--danger { background: #fee2e2; color: #b91c1c; }

        /* Dialog UI Configurations */
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.3); backdrop-filter: blur(4px); align-items: center; justify-content: center; z-index: 1000; }
        .modal-box { background: white; padding: 30px; border-radius: 12px; max-width: 480px; width: 90%; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); position: relative; text-align: left; }
        .modal-box h3 { font-size: 1.25rem; margin-bottom: 16px; color: #0f172a; border-bottom: 1px solid var(--border-color, #e2e8f0); padding-bottom: 10px;}
        .modal-close { position: absolute; top: 20px; right: 20px; font-size: 1.2rem; cursor: pointer; color: #64748b; border: none; background: none; }
        
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-size: 0.85rem; font-weight: 600; color: #475569; margin-bottom: 6px; }
        .form-control { width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.9rem; outline: none; }
        .form-control:focus { border-color: var(--primary, #2563eb); }
        .modal-actions { display: flex; gap: 12px; margin-top: 24px; }
        
        .btn-modal { padding: 10px 16px; border-radius: 6px; font-weight: 600; cursor: pointer; flex: 1; font-size: 0.9rem; border: none; text-align: center; }
        .btn-modal.cancel { background: #f1f5f9; color: #475569; }
        .btn-modal.confirm { background: var(--primary, #2563eb); color: white; }
        
        .info-table { width: 100%; margin-bottom: 15px; border-collapse: collapse; }
        .info-table td, .info-table th { padding: 10px 0; border-bottom: 1px dashed #e2e8f0; font-size: 0.9rem; }
        .info-table th { text-align: left; color: #64748b; font-weight: 500; width: 45%; }
        .info-table td { color: #0f172a; font-weight: 600; }
    </style>
</head>
<body>

<div class="layout">

    <aside class="sidebar">
        <div class="sidebar-brand">
            <div class="brand-icon">&#9670;</div>
            <span class="brand-name">HallBook</span>
        </div>

        <nav class="sidebar-nav">
            <a href="staff_dashboard.php" class="nav-item">
                <span class="nav-icon">&#9783;</span>
                <span class="nav-label">Dashboard</span>
            </a>
            <a href="manage_hall.php" class="nav-item">
                <span class="nav-icon">&#9962;</span>
                <span class="nav-label">Manage Halls</span>
            </a>
            <a href="manage_bookings.php" class="nav-item">
                <span class="nav-icon">&#128203;</span>
                <span class="nav-label">Manage Bookings</span>
            </a>
            <a href="manage_users.php" class="nav-item active">
                <span class="nav-icon">👤</span>
                <span class="nav-label">Manage Users</span>
            </a>
        </nav>

        <div class="sidebar-footer">
            <div class="staff-info">
                <div class="staff-avatar"><?php echo strtoupper(substr($staff_name, 0, 1)); ?></div>
                <div class="staff-details">
                    <span class="staff-name"><?php echo htmlspecialchars($staff_name); ?></span>
                    <span class="staff-role">Staff</span>
                </div>
            </div>
            <a class="logout-btn" href="../auth/logout.php">Logout</a>
        </div>
    </aside>

    <main class="main-content">
        <div class="topbar">
            <div class="topbar-inner">
                <div>
                    <h1 class="page-title">Manage Users</h1>
                    <p class="page-subtitle">Track accounts, modify profile fields, or alter activation status parameters</p>
                </div>
            </div>
        </div>

        <?php if (isset($_GET['msg'])): ?>
            <div class="alert-banner"><?php echo htmlspecialchars($_GET['msg']); ?></div>
        <?php endif; ?>

        <form method="GET" action="manage_users.php" class="search-container">
            <input type="text" name="search" class="search-input" placeholder="Search by name, username, email..." value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit" class="btn-submit">Search</button>
            <?php if (!empty($search)): ?>
                <a href="manage_users.php" class="btn-clear">Clear</a>
            <?php endif; ?>
        </form>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Customer ID</th>
                        <th>Full Name</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Phone Number</th>
                        <th>Account Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($result) == 0): ?>
                        <tr><td colspan="7" style="text-align:center; color:#94a3b8; padding:30px;">No registered customers found.</td></tr>
                    <?php else: ?>
                        <?php while ($user = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td>#<?php echo $user['cust_id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($user['full_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo htmlspecialchars($user['phone_num']); ?></td>
                                <td>
                                    <span class="badge <?php echo ($user['status'] === 'Active') ? 'badge--success' : 'badge--danger'; ?>">
                                        <?php echo $user['status']; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-btns">
                                        <button class="action-btn" style="background:#06b6d4; color:white;" onclick="openViewModal(<?php echo $user['cust_id']; ?>)">👁 View</button>
                                        
                                        <button class="action-btn action-btn--edit" onclick="openEditModal(
                                            '<?php echo $user['cust_id']; ?>',
                                            '<?php echo mysqli_real_escape_string($conn, $user['full_name']); ?>',
                                            '<?php echo mysqli_real_escape_string($conn, $user['username']); ?>',
                                            '<?php echo mysqli_real_escape_string($conn, $user['email']); ?>',
                                            '<?php echo mysqli_real_escape_string($conn, $user['phone_num']); ?>'
                                        )">✏ Edit</button>

                                        <form method="POST" action="manage_users.php" style="display:inline;" onsubmit="return confirm('Are you sure you want to change this customer account state?');">
                                            <input type="hidden" name="cust_id" value="<?php echo $user['cust_id']; ?>">
                                            <input type="hidden" name="current_status" value="<?php echo $user['status']; ?>">
                                            <?php if ($user['status'] === 'Active'): ?>
                                                <button type="submit" name="toggle_status" class="action-btn action-btn--delete">🔒 Deactivate</button>
                                            <?php else: ?>
                                                <button type="submit" name="toggle_status" class="action-btn" style="background:#10b981; color:white;">🔓 Activate</button>
                                            <?php endif; ?>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<div class="modal" id="viewModal">
    <div class="modal-box">
        <button class="modal-close" onclick="closeViewModal()">&times;</button>
        <h3>Customer Full Snapshot</h3>
        <div id="viewModalContent" style="margin-top: 15px;">
            <p style="color:#64748b;">Loading details...</p>
        </div>
    </div>
</div>

<div class="modal" id="editModal">
    <div class="modal-box">
        <button class="modal-close" onclick="closeEditModal()">&times;</button>
        <h3>Modify Customer Profile</h3>
        <form method="POST" action="manage_users.php">
            <input type="hidden" name="cust_id" id="edit_cust_id">
            
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="full_name" id="edit_full_name" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" id="edit_username" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" id="edit_email" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Phone Number</label>
                <input type="text" name="phone_num" id="edit_phone_num" class="form-control" required>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn-modal cancel" onclick="closeEditModal()">Cancel</button>
                <button type="submit" name="edit_user" class="btn-modal confirm">Save Profile</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditModal(id, name, username, email, phone) {
    document.getElementById("edit_cust_id").value = id;
    document.getElementById("edit_full_name").value = name;
    document.getElementById("edit_username").value = username;
    document.getElementById("edit_email").value = email;
    document.getElementById("edit_phone_num").value = phone;
    document.getElementById("editModal").style.display = "flex";
}
function closeEditModal() { document.getElementById("editModal").style.display = "none"; }

function openViewModal(custId) {
    document.getElementById("viewModal").style.display = "flex";
    document.getElementById("viewModalContent").innerHTML = '<p style="color:#64748b;">Loading metrics parameters...</p>';
    
    fetch(`get_customer_stats.php?id=${custId}`)
        .then(res => res.text())
        .then(html => {
            document.getElementById("viewModalContent").innerHTML = html;
        })
        .catch(err => {
            document.getElementById("viewModalContent").innerHTML = '<p style="color:red;">Error fetching analytics metrics data.</p>';
        });
}
function closeViewModal() { document.getElementById("viewModal").style.display = "none"; }

window.onclick = function(e) {
    let edit = document.getElementById("editModal");
    let view = document.getElementById("viewModal");
    if (e.target === edit) closeEditModal();
    if (e.target === view) closeViewModal();
}
</script>
</body>
</html>