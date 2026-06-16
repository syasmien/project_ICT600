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

$booking_id = 0;
$booking_data = null;
$message = "";

// 2. Retrieve Booking ID from URL GET request
if (isset($_GET['booking_id'])) {
    $booking_id = intval($_GET['booking_id']);
    
    // Fetch current booking details (Only allow editing if it's PENDING and belongs to this user)
    $fetch_query = "SELECT b.*, h.hall_name FROM booking b 
                    JOIN hall h ON b.hall_id = h.hall_id 
                    WHERE b.booking_id = ? AND b.cust_id = ? AND b.status = 'Pending'";
    $stmt = $conn->prepare($fetch_query);
    $stmt->bind_param("ii", $booking_id, $current_cust_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $booking_data = $result->fetch_assoc();
    $stmt->close();
}

// Redirect or show error if booking is not found or is already Approved/Cancelled
if (!$booking_data) {
    $message = "<div class='alert error'>❌ Booking not found, or it has already been approved/cancelled and cannot be modified.</div>";
}

// 3. Handle Form Submissions (Update or Cancel)
if ($_SERVER["REQUEST_METHOD"] == "POST" && $booking_data) {
    if (isset($_POST['update_booking'])) {
        // --- UPDATE ROUTINE ---
        $event_date = $_POST['event_date'];
        $event_type = mysqli_real_escape_string($conn, $_POST['event_type']);
        $guest_count = intval($_POST['guest_count']);
        $hall_id = intval($_POST['hall_id']);
        
        $today = date('Y-m-d');
        
        // Basic validation
        if (!empty($event_date) && $event_date >= $today && !empty($event_type) && $guest_count > 0 && $hall_id > 0) {
            
            // CRITICAL SAFEGUARD: Verify if this hall is already booked on this day by another reservation
            $check_stmt = $conn->prepare("SELECT COUNT(*) as count FROM booking WHERE hall_id = ? AND event_date = ? AND booking_id != ? AND status != 'Cancelled'");
            $check_stmt->bind_param("isi", $hall_id, $event_date, $booking_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result()->fetch_assoc();
            $check_stmt->close();

            if ($check_result['count'] > 0) {
                // Collision detected! Stop submission.
                $message = "<div class='alert error'>❌ Double-Booking Warning: This hall is already reserved on " . date('d M Y', strtotime($event_date)) . ". Please select a different date or hall.</div>";
            } else {
                // Prepared statement to save updates safely
                $update_stmt = $conn->prepare("UPDATE booking SET event_date = ?, event_type = ?, guest_count = ?, hall_id = ? WHERE booking_id = ? AND cust_id = ?");
                $update_stmt->bind_param("ssiiii", $event_date, $event_type, $guest_count, $hall_id, $booking_id, $current_cust_id);
                
                if ($update_stmt->execute()) {
                    $message = "<div class='alert success'>🎉 Booking updated successfully!</div>";
                    // Refresh updated data for display
                    $booking_data['event_date'] = $event_date;
                    $booking_data['event_type'] = $event_type;
                    $booking_data['guest_count'] = $guest_count;
                    $booking_data['hall_id'] = $hall_id;
                } else {
                    $message = "<div class='alert error'>❌ Error updating reservation: " . $conn->error . "</div>";
                }
                $update_stmt->close();
            }
        } else {
            $message = "<div class='alert error'>❌ Please fill out all fields correctly. Past dates are not permitted.</div>";
        }
        
    } elseif (isset($_POST['cancel_booking'])) {
        // --- CANCEL ROUTINE ---
        $cancel_stmt = $conn->prepare("UPDATE booking SET status = 'Cancelled' WHERE booking_id = ? AND cust_id = ?");
        $cancel_stmt->bind_param("ii", $booking_id, $current_cust_id);
        
        if ($cancel_stmt->execute()) {
            // Redirect back to status panel upon cancellation
            header("Location: booking_status.php");
            exit();
        } else {
            $message = "<div class='alert error'>❌ Error cancelling reservation: " . $conn->error . "</div>";
        }
        $cancel_stmt->close();
    }
}

// 4. Fetch All Available Halls for dropdown list
$halls_query = "SELECT hall_id, hall_name, hall_capacity FROM hall WHERE status = 'Available'";
$halls_result = $conn->query($halls_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Booking - Event Hall Booking System</title>
    <!-- Use your main style sheet -->
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

        /* Centered form card styling matching wireframe structures */
        .centered-form-container {
            display: flex;
            justify-content: center;
            align-items: center;
            width: 100%;
            padding: 10px 0 30px 0;
        }
        .update-booking-card {
            background-color: #ffffff;
            border: 2px solid #0f172a;
            border-radius: 12px;
            padding: 40px;
            width: 100%;
            max-width: 600px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            position: relative;
        }
        .update-card-title {
            color: #0f172a;
            font-size: 20px;
            font-weight: 800;
            margin-bottom: 30px;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Form Controls styling */
        .form-group {
            margin-bottom: 22px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 700;
            font-size: 14px;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .form-group input[type="date"], 
        .form-group input[type="number"], 
        .form-group select {
            width: 100%;
            padding: 14px;
            border: 2px solid #0f172a;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            background-color: #f8fafc;
            color: #0f172a;
            outline: none;
            transition: all 0.2s ease;
        }
        .form-group input:focus, 
        .form-group select:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
        }

        /* Back Button Container and Custom Style */
        .back-btn-container {
            margin-bottom: 25px;
            display: flex;
            justify-content: flex-start;
        }
        .btn-back {
            display: inline-flex;
            align-items: center;
            background-color: #ffffff;
            color: #0f172a;
            border: 2px solid #0f172a;
            border-radius: 6px;
            padding: 10px 20px;
            font-weight: 800;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 0.5px;
            text-decoration: none;
            transition: all 0.2s ease;
            cursor: pointer;
        }
        .btn-back:hover {
            background-color: #f1f5f9;
            transform: translateY(-2px);
        }

        /* Action Buttons Row with styling to match EDIT button */
        .action-btn-row {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            width: 100%;
        }
        .btn-update-booking {
            flex: 1.2;
            background-color: #e0f2fe; /* Bright light-blue matching EDIT button */
            color: #0369a1;
            border: 2px solid #0f172a;
            border-radius: 6px;
            padding: 14px 20px;
            font-weight: 800;
            text-transform: uppercase;
            font-size: 13px;
            letter-spacing: 0.5px;
            cursor: pointer;
            transition: all 0.2s ease;
            text-align: center;
        }
        .btn-update-booking:hover {
            background-color: #bae6fd;
            transform: translateY(-2px);
        }
        .btn-cancel-booking {
            flex: 1;
            background-color: #fee2e2; /* Crimson hue */
            color: #b91c1c;
            border: 2px solid #0f172a;
            border-radius: 6px;
            padding: 14px 20px;
            font-weight: 800;
            text-transform: uppercase;
            font-size: 13px;
            letter-spacing: 0.5px;
            cursor: pointer;
            transition: all 0.2s ease;
            text-align: center;
        }
        .btn-cancel-booking:hover {
            background-color: #fca5a5;
            transform: translateY(-2px);
        }
        .warning-text {
            color: #dc2626;
            display: none;
            font-weight: 700;
            margin-top: 8px;
            font-size: 13px;
        }
    </style>
</head>
<body>

<div class="app-container">
    
    <!-- LEFT SIDEBAR COMPONENT (Consistent on all customer pages) -->
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
    
    <!-- MAIN APPLICATION WORKSPACE -->
    <main class="main-content">
        
        <h2 class="section-title">Modify Reservation</h2>
        
        <?php echo $message; ?>
        
        <?php if ($booking_data): ?>
            <!-- Wireframe Navigation Back Button (Consistent layout) -->
            <div class="back-btn-container">
                <a href="booking_status.php" class="btn-back">
                    <svg viewBox="0 0 24 24" style="width: 16px; height: 16px; margin-right: 8px; fill: #0f172a; vertical-align: middle;">
                        <path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/>
                    </svg>
                    Back to Booking History
                </a>
            </div>

            <!-- Central Update Form Wrapper Card (Exactly like image_de647c.png) -->
            <div class="centered-form-container">
                <div class="update-booking-card">
                    <h3 class="update-card-title">Update Booking</h3>
                    
                    <form action="update_booking.php?booking_id=<?php echo $booking_id; ?>" method="POST" id="update_form">
                        
                        <!-- Event Date -->
                        <div class="form-group">
                            <label for="event_date">Event Date</label>
                            <input type="date" id="event_date" name="event_date" value="<?php echo htmlspecialchars($booking_data['event_date']); ?>" required min="<?php echo date('Y-m-d'); ?>">
                        </div>
                        
                        <!-- Event Type Dropdown -->
                        <div class="form-group">
                            <label for="event_type">Event Type</label>
                            <select id="event_type" name="event_type" required>
                                <option value="Wedding" <?php echo ($booking_data['event_type'] == 'Wedding') ? 'selected' : ''; ?>>Wedding</option>
                                <option value="Corporate Seminar" <?php echo ($booking_data['event_type'] == 'Corporate Seminar') ? 'selected' : ''; ?>>Corporate Seminar</option>
                                <option value="Birthday Party" <?php echo ($booking_data['event_type'] == 'Birthday Party') ? 'selected' : ''; ?>>Birthday Party</option>
                                <option value="Concert/Exhibition" <?php echo ($booking_data['event_type'] == 'Concert/Exhibition') ? 'selected' : ''; ?>>Concert/Exhibition</option>
                            </select>
                        </div>
                        
                        <!-- Number of Guests -->
                        <div class="form-group">
                            <label for="guest_count">Number of Guests</label>
                            <input type="number" id="guest_count" name="guest_count" value="<?php echo htmlspecialchars($booking_data['guest_count']); ?>" min="1" required oninput="filterHallsByGuests()">
                        </div>
                        
                        <!-- Venue Hall Dropdown -->
                        <div class="form-group">
                            <label for="hall_id">Hall</label>
                            <select id="hall_id" name="hall_id" required>
                                <?php if ($halls_result && $halls_result->num_rows > 0): ?>
                                    <?php while($hall = $halls_result->fetch_assoc()): ?>
                                        <option value="<?php echo $hall['hall_id']; ?>" data-capacity="<?php echo $hall['hall_capacity']; ?>" <?php echo ($booking_data['hall_id'] == $hall['hall_id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($hall['hall_name']) . " (Max: " . $hall['hall_capacity'] . " pax)"; ?>
                                        </option>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </select>
                            <small id="hall_warning" class="warning-text"></small>
                        </div>
                        
                        <!-- Action Row Buttons side by side (Exactly like image_de647c.png with styling matched to EDIT buttons) -->
                        <div class="action-btn-row">
                            <button type="submit" name="update_booking" class="btn-update-booking">Update Booking</button>
                            <button type="submit" name="cancel_booking" class="btn-cancel-booking" onclick="return confirm('Are you sure you want to cancel this booking?');">Cancel Booking</button>
                        </div>
                        
                    </form>
                </div>
            </div>
        <?php else: ?>
            <div class="return-nav-wrapper">
                <a href="booking_status.php" class="btn-back">← Back to Booking History</a>
            </div>
        <?php endif; ?>
        
    </main>
</div>

<!-- CLIENT SIDE GUEST FILTERING -->
<script>
function filterHallsByGuests() {
    var guestInput = document.getElementById('guest_count').value;
    var guestCount = parseInt(guestInput) || 0;
    
    var hallSelect = document.getElementById('hall_id');
    var options = hallSelect.options;
    var warningText = document.getElementById('hall_warning');
    
    var visibleHallsCount = 0;
    var selectedOptionStillValid = false;
    var currentSelectedValue = hallSelect.value;

    for (var i = 0; i < options.length; i++) {
        var capacity = parseInt(options[i].getAttribute('data-capacity'));
        
        if (guestCount > capacity) {
            options[i].style.display = 'none';
        } else {
            options[i].style.display = 'block';
            visibleHallsCount++;
            if (options[i].value === currentSelectedValue) {
                selectedOptionStillValid = true;
            }
        }
    }
    
    // Automatically reset selected hall if the guest limit forces it to hide
    if (!selectedOptionStillValid && visibleHallsCount > 0) {
        hallSelect.value = "";
    }
    
    if (guestCount > 0 && visibleHallsCount === 0) {
        warningText.innerText = "❌ No halls are large enough to fit " + guestCount + " guests!";
        warningText.style.display = 'block';
    } else {
        warningText.style.display = 'none';
    }
}

// Run once on load to establish capacity state
document.addEventListener("DOMContentLoaded", filterHallsByGuests);
</script>

</body>
</html>
<?php
$conn->close();
?>