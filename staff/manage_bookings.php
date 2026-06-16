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

    $id = $_GET['approve'];
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

    $id = $_GET['reject'];
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
               hall.hall_name AS hall_name
        FROM booking
        JOIN customer ON booking.cust_id = customer.cust_id
        JOIN hall ON booking.hall_id = hall.hall_id
        WHERE 1=1 ";

if (isset($_GET['status']) && $_GET['status'] != "") {
    $statusFilter = $_GET['status'];
    $sql .= " AND booking.status='$statusFilter' ";
}

if (isset($_GET['search']) && $_GET['search'] != "") {
    $search = $_GET['search'];
    $sql .= " AND (customer.full_name LIKE '%$search%'
                OR hall.hall_name LIKE '%$search%'
                OR booking.event_type LIKE '%$search%') ";
}

$sql .= " ORDER BY booking.booking_id DESC";

$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Bookings</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/staff_style.css">
</head>

<body>

<div class="layout">

    <!-- SIDEBAR -->
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

            <a href="manage_bookings.php" class="nav-item active">
                <span class="nav-icon">&#128203;</span>
                <span class="nav-label">Manage Bookings</span>
            </a>
        </nav>

        <div class="sidebar-footer">
            <div class="staff-info">
                <div class="staff-avatar">
                    <?php echo strtoupper(substr($staff_name, 0, 1)); ?>
                </div>
                <div class="staff-details">
                    <span class="staff-name"><?php echo $staff_name; ?></span>
                    <span class="staff-role">Staff</span>
                </div>
            </div>

            <a class="logout-btn" href="../auth/logout.php">Logout</a>
        </div>

    </aside>

    <!-- MAIN -->
    <main class="main-content">

        <!-- TOP -->
        <div class="topbar">
            <div class="topbar-inner">
                <div>
                    <h1 class="page-title">Manage Bookings</h1>
                    <p class="page-subtitle">Approve or reject customer bookings</p>
                </div>
            </div>
        </div>

        <!-- FILTER + SEARCH -->
        <div class="table-wrapper" style="padding:15px; margin-bottom:18px;">

            <form method="GET" class="filter-bar" style="display:flex; gap:10px; flex-wrap:wrap;">

                <input type="text"
                       name="search"
                       value="<?php echo $search; ?>"
                       placeholder="Search (customer / hall / event)">

                <select name="status">
                    <option value="">All Status</option>

                    <option value="Pending" <?php if($statusFilter=="Pending") echo "selected"; ?>>
                        Pending
                    </option>

                    <option value="Approved" <?php if($statusFilter=="Approved") echo "selected"; ?>>
                        Approved
                    </option>

                    <option value="Rejected" <?php if($statusFilter=="Rejected") echo "selected"; ?>>
                        Rejected
                    </option>
                </select>

                <button type="submit" class="btn-primary" style="border:none; cursor:pointer;">
                    Apply
                </button>

            </form>

        </div>

        <!-- TABLE -->
        <div class="table-wrapper">

            <table>

                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Hall</th>
                        <th>Event</th>
                        <th>Date</th>
                        <th>Days</th>
                        <th>Guest</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>

                <?php while($row = mysqli_fetch_assoc($result)) { ?>

                    <tr>

                        <td>
                            <div class="customer-cell">
                                <div class="customer-avatar">
                                    <?php echo strtoupper(substr($row['customer_name'],0,1)); ?>
                                </div>
                                <?php echo $row['customer_name']; ?>
                            </div>
                        </td>

                        <td><?php echo $row['hall_name']; ?></td>

                        <td><?php echo $row['event_type']; ?></td>

                        <td>
                            <?php echo date("j F Y", strtotime($row['event_date'])); ?>
                        </td>

                        <td><?php echo $row['total_days']; ?></td>

                        <td><?php echo $row['guest_count']; ?></td>

                        <td>
                            RM <?php echo number_format($row['total_price']); ?>
                        </td>

                        <td>
                            <?php
                                $status = $row['status'];
                                $badge = 'badge--default';

                                if ($status == 'Pending') $badge = 'badge--warning';
                                if ($status == 'Approved') $badge = 'badge--success';
                                if ($status == 'Rejected') $badge = 'badge--danger';
                            ?>
                            <span class="badge <?php echo $badge; ?>">
                                <?php echo $status; ?>
                            </span>
                        </td>

                        <td>

                            <?php if ($row['status'] == 'Pending') { ?>

                                <a class="btn approve"
                                    href="#"
                                    onclick="openModal('approve', <?php echo $row['booking_id']; ?>)">
                                    Approve
                                </a>

                                <a class="btn reject"
                                    href="#"
                                    onclick="openModal('reject', <?php echo $row['booking_id']; ?>)">
                                    Reject
                                </a>

                            <?php } else { ?>

                                -

                            <?php } ?>

                        </td>

                    </tr>

                <?php } ?>

                </tbody>

            </table>

            <!-- CONFIRM MODAL -->
            <div id="confirmModal" class="modal">

                <div class="modal-box">

                    <h3 id="modalTitle">Confirm Action</h3>
                    <p id="modalText">Are you sure?</p>

                    <div class="modal-actions">

                        <a id="confirmBtn" href="#" class="btn danger">Confirm</a>

                        <button onclick="closeModal()" class="btn cancel">
                            Cancel
                        </button>

                    </div>

                </div>

            </div>

        </div>

    </main>

</div>
    <script>
    function openModal(action, id){

        let modal = document.getElementById('confirmModal');
        let confirmBtn = document.getElementById('confirmBtn');

        if(action === 'approve'){
            document.getElementById('modalTitle').innerText = "Approve Booking";
            document.getElementById('modalText').innerText = "Confirm approve this booking?";
            confirmBtn.href = "manage_bookings.php?approve=" + id;
            confirmBtn.className = "btn approve";
            confirmBtn.innerText = "Approve";
        }

        if(action === 'reject'){
            document.getElementById('modalTitle').innerText = "Reject Booking";
            document.getElementById('modalText').innerText = "Are you sure to reject this booking?";
            confirmBtn.href = "manage_bookings.php?reject=" + id;
            confirmBtn.className = "btn danger";
            confirmBtn.innerText = "Reject";
        }

        modal.style.display = "flex";
    }

    function closeModal(){
        document.getElementById('confirmModal').style.display = "none";
    }

    // click outside modal
    window.onclick = function(event){
        let modal = document.getElementById('confirmModal');
        if(event.target == modal){
            modal.style.display = "none";
        }
    }
    </script>
</body>
</html>