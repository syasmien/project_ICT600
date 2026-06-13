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

// Hardcoded Customer ID for testing
$dummy_cust_id = 1; 

// 2. Handle Form Submission
$message = "";
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_booking'])) {
    $event_date = $_POST['event_date'];
    $event_type = mysqli_real_escape_string($conn, $_POST['event_type']);
    $guest_count = intval($_POST['guest_count']);
    $hall_id = intval($_POST['hall_id']);

    // Extra validation: backend check to ensure they didn't pick a past date
    $today = date('Y-m-d');

    if (!empty($event_date) && $event_date >= $today && !empty($event_type) && $guest_count > 0 && $hall_id > 0) {
        
        $stmt = $conn->prepare("INSERT INTO booking (cust_id, hall_id, event_type, event_date, guest_count, status) VALUES (?, ?, ?, ?, ?, 'Pending')");
        $stmt->bind_param("iissi", $dummy_cust_id, $hall_id, $event_type, $event_date, $guest_count);

        if ($stmt->execute()) {
            $message = "<div class='alert success'>🎉 Booking request submitted successfully! Status is Pending.</div>";
        } else {
            $message = "<div class='alert error'>❌ Error saving booking: " . $conn->error . "</div>";
        }
        $stmt->close();
    } else {
        $message = "<div class='alert error'>❌ Please fill in all fields correctly. (Note: Past dates are not allowed).</div>";
    }
}

// 3. Fetch All Available Halls
$halls_query = "SELECT hall_id, hall_name, hall_capacity FROM hall WHERE status = 'Available'";
$halls_result = $conn->query($halls_query);

// 4. Fetch Existing Bookings for Availability Column (Right Side)
$availability_query = "SELECT b.event_date, b.event_type, h.hall_name 
                       FROM booking b 
                       JOIN hall h ON b.hall_id = h.hall_id 
                       WHERE b.event_date >= CURDATE() 
                       ORDER BY b.event_date ASC";
$availability_result = $conn->query($availability_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Booking - Event Hall System</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container">
    <h1>Event Hall Booking System</h1>
    
    <?php echo $message; ?>

    <div class="page-layout">
        
        <div class="form-column">
            <h2>Create Reservation</h2>
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
                    <small id="hall_warning" style="color: #e74c3c; display: none; font-weight: bold; margin-top: 5px;"></small>
                </div>
                
                <button type="submit" name="submit_booking" class="btn-submit">Submit Booking</button>
            </form>
        </div>
        
        <div class="table-column">
            <h2>Current Venue Availability</h2>
            <p style="color: #7f8c8d; font-size: 14px;">Review booked slots below to discover a free place:</p>
            
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
                                <td><span style="color: #e74c3c; font-weight: bold;">🔴 Reserved (<?php echo htmlspecialchars($row['event_type']); ?>)</span></td>
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

<script>
function filterHallsByGuests() {
    // Get the number of guests typed by the user
    var guestInput = document.getElementById('guest_count').value;
    var guestCount = parseInt(guestInput) || 0;
    
    // Get the dropdown menu and all its options
    var hallSelect = document.getElementById('hall_id');
    var options = hallSelect.options;
    var warningText = document.getElementById('hall_warning');
    
    var visibleHallsCount = 0;
    
    // Reset selection to default if guest count changes
    hallSelect.value = "";

    // Loop through all options (skip index 0 which is the "-- Choose Venue Hall --" placeholder)
    for (var i = 1; i < options.length; i++) {
        var capacity = parseInt(options[i].getAttribute('data-capacity'));
        
        // If guest count is greater than hall capacity, hide the hall option
        if (guestCount > capacity) {
            options[i].style.display = 'none';
        } else {
            options[i].style.display = 'block';
            visibleHallsCount++;
        }
    }
    
    // If the user typed a number but no halls can hold that many people, show a warning
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