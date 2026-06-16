<?php
// 1. Database Connection Configuration
$host = "localhost";
$username = "root"; 
$password = "";     
$dbname = "event_hall_booking"; 

$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}

// Fixed mock account customer identifier matching system testing profiles
$current_cust_id = 1; 

// 2. Handle Action: Cancel Booking (Only allowed if status is Pending)
$message = "";
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['cancel_booking'])) {
    $booking_id = intval($_POST['booking_id']);
    
    // Prepared statement to safely update status to 'Cancelled'
    $stmt = $conn->prepare("UPDATE booking SET status = 'Cancelled' WHERE booking_id = ? AND cust_id = ? AND status = 'Pending'");
    $stmt->bind_param("ii", $booking_id, $current_cust_id);
    
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        $message = "<div class='alert success'>🎉 Booking #$booking_id cancelled successfully.</div>";
    } else {
        $message = "<div class='alert error'>❌ Could not cancel booking. (Note: Only pending bookings can be cancelled).</div>";
    }
    $stmt->close();
}

// 3. Query all historical bookings for current user joined with hall name
$bookings_query = "SELECT b.booking_id, h.hall_name, b.event_date, b.status 
                   FROM booking b 
                   JOIN hall h ON b.hall_id = h.hall_id 
                   WHERE b.cust_id = ?
                   ORDER BY b.event_date DESC";
$stmt = $conn->prepare($bookings_query);
$stmt->bind_param("i", $current_cust_id);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking History - Event Hall Booking System</title>
    <!-- Relative link pointing back to assets/css/style.css -->
    <link rel="stylesheet" href="../assets/css/style.css">
    
    <!-- SAFEGUARD: Inline style rules to prevent SVGs from expanding if style.css fails to load -->
    <style>
        svg {
            max-width: 50px !important;
            max-height: 50px !important;
            display: inline-block;
        }
        .avatar-placeholder svg {
            max-width: 45px !important;
            max-height: 45px !important;
        }
        .nav-item a svg {
            max-width: 20px !important;
            max-height: 20px !important;
        }
    </style>
</head>
<body>

<div class="app-container">
    
    <!-- LEFT SIDEBAR COMPONENT (Matches dashboard and booking layout) -->
    <aside class="sidebar">
        <div class="profile-section">
            <div class="avatar-placeholder">
                <svg viewBox="0 0 24 24">
                    <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5-4-8-4z"/>
                </svg>
            </div>
            <div class="profile-role">Customer</div>
        </div>
        
        <nav class="nav-menu">
            <ul>
                <li class="nav-item">
                    <a href="dashboard.php">
                        <svg viewBox="0 0 24 24">
                            <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/>
                        </svg>
                        DASHBOARD
                    </a>
                </li>
                <li class="nav-item">
                    <a href="add_booking.php">
                        <svg viewBox="0 0 24 24">
                            <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/>
                        </svg>
                        ADD BOOKING
                    </a>
                </li>
                <li class="nav-item active">
                    <a href="booking_status.php">
                        <svg viewBox="0 0 24 24">
                            <path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/>
                        </svg>
                        BOOKING STATUS
                    </a>
                </li>
                <li class="nav-item">
                    <a href="profile.php">
                        <svg viewBox="0 0 24 24">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/>
                        </svg>
                        PROFILE
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#">
                        <svg viewBox="0 0 24 24">
                            <path d="M13 3h-2v10h2V3zm4.83 2.17l-1.42 1.42C17.99 7.86 19 9.81 19 12c0 3.87-3.13 7-7 7s-7-3.13-7-7c0-2.19 1.01-4.14 2.58-5.42L6.17 5.17C4.23 6.82 3 9.26 3 12c0 4.97 4.03 9 9 9s9-4.03 9-9c0-2.74-1.23-5.18-3.17-6.83z"/>
                        </svg>
                        LOGOUT
                    </a>
                </li>
            </ul>
        </nav>
    </aside>
    
    <!-- MAIN APPLICATION DISPLAY CORE -->
    <main class="main-content">
        
        <div class="booking-history-header">
            <h2 class="section-title">Booking History</h2>
            
            <!-- Real-time Filter Bar Layout matching wireframe (image_de81e9.png) -->
            <div class="filter-wrapper">
                <label for="filter_input" class="filter-label">Filter By :</label>
                <input type="text" id="filter_input" placeholder="Search ID, hall name, status..." onkeyup="filterBookingsTable()">
            </div>
        </div>
        
        <?php echo $message; ?>
        
        <!-- Table Card Wrapper -->
        <div class="table-card">
            <div class="table-wrapper">
                <table id="bookings_table">
                    <thead>
                        <tr>
                            <th>Booking ID</th>
                            <th>Hall Name</th>
                            <th>Event Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="bookings_body">
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): 
                                // Dynamic coloring for status badges
                                $status_class = "status-badge-pending";
                                if ($row['status'] == 'Approved') { $status_class = "status-badge-approved"; }
                                elseif ($row['status'] == 'Cancelled') { $status_class = "status-badge-cancelled"; }
                            ?>
                                <tr class="booking-row">
                                    <td><strong>#<?php echo $row['booking_id']; ?></strong></td>
                                    <td class="hall-cell"><?php echo htmlspecialchars($row['hall_name']); ?></td>
                                    <td><?php echo date('d M Y', strtotime($row['event_date'])); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo $status_class; ?>">
                                            <?php echo htmlspecialchars($row['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($row['status'] == 'Pending'): ?>
                                            <!-- Cancellable form action -->
                                            <form action="booking_status.php" method="POST" onsubmit="return confirm('Are you sure you want to cancel booking #<?php echo $row['booking_id']; ?>?');">
                                                <input type="hidden" name="booking_id" value="<?php echo $row['booking_id']; ?>">
                                                <button type="submit" name="cancel_booking" class="btn-cancel-action">Cancel</button>
                                            </form>
                                        <?php else: ?>
                                            <span class="action-none">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr id="no_data_row">
                                <td colspan="5" class="no-data">No booking records found. Start creating one in Add Booking!</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Wireframe Navigation Pagination (image_de81e9.png) -->
            <div class="pagination-container" id="pagination_container">
                <button class="pagination-btn" onclick="goToPage('first')" title="First Page">&laquo;</button>
                <button class="pagination-btn" onclick="goToPage('prev')" title="Previous Page">&lsaquo;</button>
                <div id="page_numbers" class="page-numbers-group">
                    <!-- Page numbers will be dynamically injected here by Javascript -->
                </div>
                <button class="pagination-btn" onclick="goToPage('next')" title="Next Page">&rsaquo;</button>
                <button class="pagination-btn" onclick="goToPage('last')" title="Last Page">&raquo;</button>
            </div>
        </div>
        
    </main>
</div>

<!-- CLIENT JAVASCRIPT: Real-time filtering + Wireframe Client-Side Pagination -->
<script>
    const rowsPerPage = 5; // Configured row limit per page view
    let currentPage = 1;
    let filteredRows = [];

    // Initialize Page states on DOM Load
    document.addEventListener("DOMContentLoaded", function() {
        updateActiveRows();
    });

    function updateActiveRows() {
        const tableBody = document.getElementById("bookings_body");
        const allRows = Array.from(tableBody.querySelectorAll(".booking-row"));
        
        // Populate the active working index based on filter visibility
        filteredRows = allRows.filter(row => row.style.display !== 'none');
        
        currentPage = 1; // Reset page view back to index 1 on changes
        renderPaginationControls();
        displayPage(currentPage);
    }

    // Interactive client-side real-time filter matched to wireframe input
    function filterBookingsTable() {
        const input = document.getElementById("filter_input");
        const filter = input.value.toUpperCase();
        const tableBody = document.getElementById("bookings_body");
        const rows = tableBody.querySelectorAll(".booking-row");
        const noDataRow = document.getElementById("no_data_row");
        
        let hasVisibleRows = false;

        rows.forEach(row => {
            const textContent = row.innerText.toUpperCase();
            if (textContent.indexOf(filter) > -1) {
                row.style.display = "";
                hasVisibleRows = true;
            } else {
                row.style.display = "none";
            }
        });

        // Toggle custom placeholder row if filter results are empty
        if (noDataRow) {
            noDataRow.style.display = hasVisibleRows ? "none" : "";
        }

        updateActiveRows();
    }

    // Pagination display logic engine
    function displayPage(page) {
        currentPage = page;
        
        const start = (currentPage - 1) * rowsPerPage;
        const end = start + rowsPerPage;

        // Hide all rows in filtered stack except for the active page subset
        filteredRows.forEach((row, index) => {
            if (index >= start && index < end) {
                row.style.display = "";
            } else {
                row.style.display = "none";
            }
        });

        // Maintain active page button highlighted state
        const numBtns = document.querySelectorAll(".page-num-btn");
        numBtns.forEach(btn => {
            if (parseInt(btn.dataset.page) === currentPage) {
                btn.classList.add("active");
            } else {
                btn.classList.remove("active");
            }
        });
    }

    // Dynamic rendering matching the `<< < 1 2 3 > >>` wireframe block
    function renderPaginationControls() {
        const pageNumbersContainer = document.getElementById("page_numbers");
        pageNumbersContainer.innerHTML = "";

        const totalPages = Math.ceil(filteredRows.length / rowsPerPage);
        
        if (totalPages <= 1) {
            document.getElementById("pagination_container").style.display = "none";
            return;
        } else {
            document.getElementById("pagination_container").style.display = "flex";
        }

        // Render page buttons dynamically
        for (let i = 1; i <= totalPages; i++) {
            const btn = document.createElement("button");
            btn.className = "pagination-btn page-num-btn";
            btn.dataset.page = i;
            btn.innerText = i;
            btn.addEventListener("click", () => displayPage(i));
            pageNumbersContainer.appendChild(btn);
        }
    }

    // Wireframe directional arrows logic handler
    function goToPage(direction) {
        const totalPages = Math.ceil(filteredRows.length / rowsPerPage);
        
        if (direction === 'first') {
            displayPage(1);
        } else if (direction === 'prev' && currentPage > 1) {
            displayPage(currentPage - 1);
        } else if (direction === 'next' && currentPage < totalPages) {
            displayPage(currentPage + 1);
        } else if (direction === 'last') {
            displayPage(totalPages);
        }
    }
</script>

</body>
</html>
<?php
$stmt->close();
$conn->close();
?>