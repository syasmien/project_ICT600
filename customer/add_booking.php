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

// 2. Handle Form Submission
$message = "";
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_booking'])) {
    $event_date = $_POST['event_date'];
    $event_type = mysqli_real_escape_string($conn, $_POST['event_type']);
    $guest_count = intval($_POST['guest_count']);
    $hall_id = intval($_POST['hall_id']);

    $today = date('Y-m-d');

    if (!empty($event_date) && $event_date >= $today && !empty($event_type) && $guest_count > 0 && $hall_id > 0) {
        
        // CRITICAL SAFEGUARD: Verify if this hall is already booked on this day by another reservation
        $check_stmt = $conn->prepare("SELECT COUNT(*) as count FROM booking WHERE hall_id = ? AND event_date = ? AND status != 'Cancelled'");
        $check_stmt->bind_param("is", $hall_id, $event_date);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result()->fetch_assoc();
        $check_stmt->close();

        if ($check_result['count'] > 0) {
            // Collision detected! Stop submission.
            $message = "<div class='alert error'>❌ Double-Booking Warning: This hall is already reserved on " . date('d M Y', strtotime($event_date)) . ". Please select a different date or hall.</div>";
        } else {
            $stmt = $conn->prepare("INSERT INTO booking (cust_id, hall_id, event_type, event_date, guest_count, status) VALUES (?, ?, ?, ?, ?, 'Pending')");
            $stmt->bind_param("iissi", $current_cust_id, $hall_id, $event_type, $event_date, $guest_count);

            if ($stmt->execute()) {
                $message = "<div class='alert success'>🎉 Booking request submitted successfully! Your request is pending staff approval.</div>";
            } else {
                $message = "<div class='alert error'>❌ Error saving booking: " . $conn->error . "</div>";
            }
            $stmt->close();
        }
    } else {
        $message = "<div class='alert error'>❌ Please fill in all fields correctly. (Note: Past dates are not allowed).</div>";
    }
}

// 3. Fetch All Available Halls for form dropdown
$halls_query = "SELECT hall_id, hall_name, hall_capacity FROM hall WHERE status = 'Available'";
$halls_result = $conn->query($halls_query);

// 4. Fetch Existing Bookings for Availability Column
$availability_query = "SELECT b.event_date, b.event_type, h.hall_name 
                       FROM booking b 
                       JOIN hall h ON b.hall_id = h.hall_id 
                       WHERE b.event_date >= CURDATE() AND b.status != 'Cancelled'
                       ORDER BY b.event_date ASC";
$availability_result = $conn->query($availability_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Booking - Event Hall Booking System</title>
    
    <link rel="stylesheet" href="../assets/css/style.css">

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
                <li class="nav-item active">
                    <a href="add_booking.php">
                        <svg viewBox="0 0 24 24">
                            <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/>
                        </svg>
                        ADD BOOKING
                    </a>
                </li>
                <li class="nav-item">
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
    
    <main class="main-content">
        
        <h2 class="section-title">Add Booking</h2>
        
        <?php echo $message; ?>

        <div class="booking-grid">
            
            <div class="form-column">
                <h3 class="column-subtitle">Create Reservation</h3>
                <form action="add_booking.php" method="POST">
                    
                    <div class="form-group">
                        <label for="event_date">Select Date:</label>
                        <input type="date" id="event_date" name="event_date" required min="<?php echo date('Y-m-d'); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="event_type">Select Event Type:</label>
                        <select id="event_type" name="event_type" required>
                            <option value="">-- Choose Event --</option>
                            <option value="Wedding">Wedding</option>
                            <option value="Corporate Seminar">Corporate Seminar</option>
                            <option value="Birthday Party">Birthday Party</option>
                            <option value="Concert/Exhibition">Concert / Exhibition</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="guest_count">Enter Number of Guests:</label>
                        <input type="number" id="guest_count" name="guest_count" min="1" placeholder="e.g. 150" required oninput="filterHallsByGuests()">
                    </div>
                    
                    <div class="form-group">
                        <label for="hall_id">Select Hall Name:</label>
                        <select id="hall_id" name="hall_id" required>
                            <option value="">-- Choose Venue Hall --</option>
                            <?php if ($halls_result && $halls_result->num_rows > 0): ?>
                                <?php while($hall = $halls_result->fetch_assoc()): ?>
                                    <option value="<?php echo $hall['hall_id']; ?>" data-capacity="<?php echo $hall['hall_capacity']; ?>">
                                        <?php echo htmlspecialchars($hall['hall_name']) . " (Max: " . $hall['hall_capacity'] . " pax)"; ?>
                                    </option>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <option value="" disabled>No halls available.</option>
                            <?php endif; ?>
                        </select>
                        <small id="hall_warning" class="warning-text"></small>
                    </div>
                    
                    <button type="submit" name="submit_booking" class="btn-submit">Submit Booking</button>
                </form>
            </div>
            
            <div class="table-column">
                <h3 class="column-subtitle">Current Venue Availability</h3>
                <p class="table-tip">Review booked dates below to avoid scheduling conflicts:</p>
                
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Hall Name</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($availability_result && $availability_result->num_rows > 0): ?>
                                <?php while($row = $availability_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><strong><?php echo date('d M Y', strtotime($row['event_date'])); ?></strong></td>
                                        <td><?php echo htmlspecialchars($row['hall_name']); ?></td>
                                        <td><span class="status-reserved">🔴 Reserved (<?php echo htmlspecialchars($row['event_type']); ?>)</span></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" class="no-data">All halls are currently free! Go ahead and make a reservation.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </main>
</div>

<script>
function filterHallsByGuests() {
    var guestInput = document.getElementById('guest_count').value;
    var guestCount = parseInt(guestInput) || 0;
    
    var hallSelect = document.getElementById('hall_id');
    var options = hallSelect.options;
    var warningText = document.getElementById('hall_warning');
    var visibleHallsCount = 0;
    
    hallSelect.value = "";

    for (var i = 1; i < options.length; i++) {
        var capacity = parseInt(options[i].getAttribute('data-capacity'));
        
        if (guestCount > capacity) {
            options[i].style.display = 'none';
        } else {
            options[i].style.display = 'block';
            visibleHallsCount++;
        }
    }
    
    if (guestCount > 0 && visibleHallsCount === 0) {
        warningText.innerText = "❌ No halls are large enough to fit " + guestCount + " guests!";
        warningText.style.display = 'block';
    } else {
        warningText.style.display = 'none';
    }
}
</script>

</body>
</html>
<?php
$conn->close();
?>