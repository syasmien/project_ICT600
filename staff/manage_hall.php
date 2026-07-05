<?php
include('../config/db.php');
session_start();

if (!isset($_SESSION['staff_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$staff_name = $_SESSION['name'];

/* DELETE HALL */
if (isset($_GET['delete'])) {

    $id = $_GET['delete'];

    mysqli_query($conn, "DELETE FROM hall WHERE hall_id=$id");

    header("Location: manage_hall.php");
    exit();
}

$result = mysqli_query($conn, "SELECT * FROM hall ORDER BY hall_id DESC");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Hall</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/staff_style.css">
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
            <a href="manage_hall.php" class="nav-item active">
                <span class="nav-icon">&#9962;</span>
                <span class="nav-label">Manage Halls</span>
            </a>
            <a href="manage_bookings.php" class="nav-item">
                <span class="nav-icon">&#128203;</span>
                <span class="nav-label">Manage Bookings</span>
            </a>
            <a href="manage_users.php" class="nav-item">
                <span class="nav-icon">👤</span>
                <span class="nav-label">Manage Users</span>
            </a>
        </nav>

        <div class="sidebar-footer">
            <div class="staff-info">
                <div class="staff-avatar"><?php echo strtoupper(substr($staff_name, 0, 1)); ?></div>
                <div class="staff-details">
                    <span class="staff-name"><?php echo $staff_name; ?></span>
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
                    <h1 class="page-title">Manage Halls</h1>
                    <p class="page-subtitle">Add, update or delete halls</p>
                </div>
                <a href="add_hall.php" class="btn-primary">+ Add New Hall</a>
            </div>
        </div>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Hall Name</th>
                        <th>Capacity</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = mysqli_fetch_assoc($result)) { ?>
                    <tr>
                        <td>
                            <div class="hall-name-cell">
                                <div class="hall-icon">&#9962;</div>
                                <?php echo $row['hall_name']; ?>
                            </div>
                        </td>
                        <td>
                            <span class="capacity-text">&#128101; <?php echo number_format($row['hall_capacity']); ?> pax</span>
                        </td>
                        <td>
                            <span class="price-text">RM <?php echo number_format($row['price']); ?></span>
                        </td>
                        <td>
                            <?php
                                $status = $row['status'];
                                $badge_class = 'badge--default';
                                if ($status === 'Available')   $badge_class = 'badge--success';
                                if ($status === 'Unavailable') $badge_class = 'badge--danger';
                                if ($status === 'Maintenance') $badge_class = 'badge--warning';
                            ?>
                            <span class="badge <?php echo $badge_class; ?>"><?php echo $status; ?></span>
                        </td>
                        <td>
                            <div class="action-btns">
                                <a href="edit_hall.php?id=<?php echo $row['hall_id']; ?>" class="action-btn action-btn--edit">
                                    ✏️ Edit
                                </a>
                                <a href="#" 
                                    class="action-btn action-btn--delete"
                                    onclick="openDeleteModal(<?php echo $row['hall_id']; ?>)">
                                    🗑️ Delete
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
            <div class="modal" id="deleteModal">
                <div class="modal-box">
                    <h3>Confirm Delete</h3>
                    <p>Are you sure want to delete this hall?</p>

                    <div class="modal-actions">
                        <button class="btn cancel" onclick="closeDeleteModal()">Cancel</button>
                        <a id="confirmDeleteBtn" class="btn danger" href="#">Yes, Delete</a>
            </div>
    </div>
</div>
        </div>

    </main>
</div>

<script>
let deleteId = null;

function openDeleteModal(id) {
    deleteId = id;
    document.getElementById("deleteModal").style.display = "flex";

    document.getElementById("confirmDeleteBtn").href =
        "manage_hall.php?delete=" + id;
}

function closeDeleteModal() {
    document.getElementById("deleteModal").style.display = "none";
}

// close bila click luar box
window.onclick = function(e) {
    let modal = document.getElementById("deleteModal");
    if (e.target === modal) {
        closeDeleteModal();
    }
}
</script>
</body>
</html>