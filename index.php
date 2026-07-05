<?php
// 1. Establish database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "event_hall_booking"; 

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// 2. Fetch only 2 featured halls for the main page view
$sql_featured = "SELECT hall_id, hall_name, hall_capacity, price, image FROM hall LIMIT 2";
$result_featured = $conn->query($sql_featured);

// 3. Fetch ALL halls to feed the "Explore Halls" grid popup
$sql_all = "SELECT hall_id, hall_name, hall_capacity, price, image FROM hall";
$result_all = $conn->query($sql_all);

// Keep an array cache for client-side popups
$all_halls_data = [];
if ($result_all && $result_all->num_rows > 0) {
    while($row = $result_all->fetch_assoc()) {
        $all_halls_data[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Hall Booking System</title>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background-color: #f5f7fb;
        }

        /* --- NAVBAR --- */
        nav {
            width: 100%;
            padding: 18px 80px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: white; 
            box-shadow: 0px 2px 10px rgba(0,0,0,0.05);
            position: fixed;
            top: 0;
            z-index: 100;
        }

        nav h2 {
            color: #2c3e50;
            font-weight: 700;
        }

        nav ul {
            list-style: none;
            display: flex;
            gap: 30px;
            align-items: center;
        }

        nav ul li a {
            text-decoration: none;
            color: #333;
            font-weight: 500;
            transition: 0.3s;
        }

        nav ul li a:hover {
            color: #4f46e5; 
        }

        .nav-btn {
            background-color: #4f46e5; 
            color: white !important;
            padding: 10px 18px;
            border-radius: 8px;
            font-weight: 600;
        }

        .nav-btn:hover {
            background-color: #4338ca;
        }

        /* --- MAIN SPLIT CONTAINER --- */
        .main-layout {
            max-width: 1400px;
            margin: 0 auto;
            padding: 140px 80px 40px;
            display: grid;
            grid-template-columns: 1fr 1.5fr; 
            gap: 60px;
            min-height: calc(100vh - 80px);
        }

        /* --- HERO SECTION (LEFT) --- */
        .hero-text {
            display: flex;
            flex-direction: column;
            justify-content: center; 
        }

        .hero-text h1 {
            font-size: 46px;
            line-height: 1.2;
            color: #1e293b;
            margin-bottom: 25px;
        }

        .hero-text p {
            font-size: 16px;
            color: #64748b;
            line-height: 1.8;
            margin-bottom: 35px;
        }

        .hero-buttons {
            display: flex;
            gap: 15px;
        }

        .hero-buttons a {
            text-decoration: none;
        }

        .hero-buttons button {
            padding: 14px 28px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: 0.3s;
        }

        .book-btn {
            background-color: #4f46e5; 
            color: white;
        }

        .book-btn:hover {
            background-color: #4338ca;
        }

        .explore-btn {
            background-color: white;
            border: 1px solid #dcdcdc !important;
            color: #333;
        }

        .hero-buttons button:hover {
            transform: translateY(-2px);
        }

        /* --- FEATURED HALLS CONTAINER (RIGHT) --- */
        .featured-halls {
            background-color: transparent;
        }

        .featured-halls h2 {
            font-size: 28px;
            color: #1e293b;
            margin-bottom: 25px;
        }

        .halls-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
        }

        /* --- HALL CARD STYLES --- */
        .hall-card {
            background-color: white;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0px 4px 15px rgba(0,0,0,0.04);
            border: 1px solid #e2e8f0;
            display: flex;
            flex-direction: column;
            text-align: center;
            transition: 0.3s;
        }

        .hall-card:hover {
            transform: translateY(-4px);
            box-shadow: 0px 8px 25px rgba(0,0,0,0.08);
        }

        .hall-card-img {
            height: 150px;
            background: linear-gradient(to bottom, #e0f2fe, #bcbeff); 
            position: relative;
        }

        .hall-card-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .hall-card-body {
            padding: 20px;
            display: flex;
            flex-direction: column;
            flex-grow: 1;
            justify-content: space-between;
            gap: 15px; /* Added spacing between title and button */
        }

        .hall-title {
            font-size: 18px;
            font-weight: 600;
            color: #1e293b;
        }

        .view-btn {
            display: block;
            width: 100%;
            background-color: #4f46e5; 
            color: white;
            text-decoration: none;
            padding: 10px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: 0.2s;
            text-align: center;
        }

        .view-btn:hover {
            background-color: #4338ca;
        }

        /* --- MODAL POPUP SYSTEM --- */
        .modal-overlay {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(15, 23, 42, 0.5);
            backdrop-filter: blur(4px);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }

        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 16px;
            max-width: 480px;
            width: 90%;
            position: relative;
            box-shadow: 0 20px 25px -5px rgba(0,0,0,0.15);
            text-align: left;
        }

        .modal-content.large-grid {
            max-width: 900px;
            max-height: 85vh;
            overflow-y: auto;
        }

        .close-modal-btn {
            position: absolute; top: 15px; right: 20px;
            font-size: 28px; cursor: pointer; color: #64748b;
            border: none; background: none; line-height: 1;
        }

        .close-modal-btn:hover { color: #1e293b; }

        /* --- FOOTER --- */
        footer {
            background-color: #111827;
            color: white;
            text-align: center;
            padding: 25px;
            margin-top: 60px;
        }

        @media(max-width: 1024px) {
            .main-layout {
                grid-template-columns: 1fr;
                padding: 120px 30px 40px; gap: 40px;
            }
            nav { padding: 18px 30px; }
        }
    </style>
</head>
<body>

    <nav>
        <h2>EVENT HALL BOOKING</h2>
        <ul>
            <li><a href="#">Home</a></li>
            <li><a href="about.php">About</a></li>
            <li><a href="contact.php">Contact</a></li>
            <li><a href="policy.php">Web Policy</a></li>
            <li>
                <a href="auth/login.php" class="nav-btn">Login</a>
            </li>
        </ul>
    </nav>

    <main class="main-layout">

        <section class="hero-text">
            <h1>Welcome to Event Hall Booking System</h1>
            <p>
                Find and book the perfect event hall for your special occasions. 
                Our system helps customers reserve spaces for weddings, meetings, 
                seminars, corporate events, and celebrations quickly and efficiently.
            </p>

            <div class="hero-buttons">
                <a href="auth/register.php">
                    <button class="book-btn">Book Now</button>
                </a>
                <button class="explore-btn" onclick="openExploreModal()">Explore Halls</button>
            </div>
        </section>

        <section class="featured-halls">
            <h2>Featured Event Halls</h2>
            
            <div class="halls-grid">
                <?php
                if ($result_featured && $result_featured->num_rows > 0) {
                    while($row = $result_featured->fetch_assoc()) {
                        $has_image = (!empty($row['image']) && file_exists('assets/images/' . $row['image'])) ? 1 : 0;
                        $image_filename = $has_image ? $row['image'] : '';
                        ?>
                        
                        <div class="hall-card">
                            <div class="hall-card-img">
                                <?php if ($has_image): ?>
                                    <img src="assets/images/<?php echo $row['image']; ?>" alt="<?php echo htmlspecialchars($row['hall_name']); ?>">
                                <?php endif; ?>
                            </div>

                            <div class="hall-card-body">
                                <div class="hall-title"><?php echo htmlspecialchars($row['hall_name']); ?></div>
                                
                                <button type="button" class="view-btn" onclick="openHallDetails(
                                    '<?php echo mysqli_real_escape_string($conn, $row['hall_name']); ?>',
                                    '<?php echo $row['hall_capacity']; ?>',
                                    '<?php echo number_format($row['price'], 2); ?>',
                                    '<?php echo $image_filename; ?>'
                                )">View Details</button>
                            </div>
                        </div>

                        <?php
                    }
                } else {
                    echo "<p style='grid-column: 1/-1; color:#64748b;'>No halls available at the moment.</p>";
                }
                ?>
            </div>
        </section>

    </main>

    <div id="exploreModal" class="modal-overlay">
        <div class="modal-content large-grid">
            <button class="close-modal-btn" onclick="closeExploreModal()">&times;</button>
            <h2 style="font-size: 26px; color: #1e293b; margin-bottom: 25px;">All Available Event Halls</h2>
            
            <div class="halls-grid">
                <?php if (!empty($all_halls_data)): ?>
                    <?php foreach($all_halls_data as $hall): ?>
                        <?php 
                        $has_img = (!empty($hall['image']) && file_exists('assets/images/' . $hall['image'])) ? 1 : 0; 
                        $img_file = $has_img ? $hall['image'] : '';
                        ?>
                        <div class="hall-card">
                            <div class="hall-card-img">
                                <?php if ($has_img): ?>
                                    <img src="assets/images/<?php echo $hall['image']; ?>" alt="<?php echo htmlspecialchars($hall['hall_name']); ?>">
                                <?php endif; ?>
                            </div>
                            <div class="hall-card-body">
                                <div class="hall-title"><?php echo htmlspecialchars($hall['hall_name']); ?></div>
                                
                                <button type="button" class="view-btn" onclick="openHallDetails(
                                    '<?php echo mysqli_real_escape_string($conn, $hall['hall_name']); ?>',
                                    '<?php echo $hall['hall_capacity']; ?>',
                                    '<?php echo number_format($hall['price'], 2); ?>',
                                    '<?php echo $img_file; ?>'
                                )">View Details</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="grid-column: 1/-1; color:#64748b;">No halls available at the moment.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div id="detailsModal" class="modal-overlay" style="z-index: 1100;">
        <div class="modal-content">
            <button class="close-modal-btn" onclick="closeHallDetails()">&times;</button>
            <div id="modalDynamicBody">
                </div>
        </div>
    </div>

    <footer>
        <div class="footer-nav">
           <a href="about.php">About Us</a>
           <a href="contact.php">Contact Support</a>
           <a href="policy.php">Privacy & Terms</a>
        </div>
        <p>© 2026 Event Hall Booking System | All Rights Reserved</p>
    </footer>

    <script>
    function openExploreModal() {
        document.getElementById('exploreModal').style.display = 'flex';
    }
    function closeExploreModal() {
        document.getElementById('exploreModal').style.display = 'none';
    }

    function openHallDetails(name, capacity, price, image) {
        let displayContent = '';
        
        if (image !== '') {
            displayContent += `<img src="assets/images/${image}" style="width:100%; height:220px; object-fit:cover; border-radius:10px; margin-bottom:18px;">`;
        } else {
            displayContent += `<div style="width:100%; height:220px; background:linear-gradient(to bottom, #e0f2fe, #bcbeff); border-radius:10px; margin-bottom:18px;"></div>`;
        }
        
        displayContent += `
            <h3 style="font-size:24px; font-weight:600; color:#1e293b; margin-bottom:12px;">${name}</h3>
            <p style="font-size:15px; color:#64748b; margin-bottom:8px;"><strong>Maximum Capacity:</strong> ${capacity} pax</p>
            <p style="font-size:18px; font-weight:600; color:#4f46e5; margin-bottom:24px;">Rental Price: RM ${price}</p>
            <a href="auth/register.php" style="text-decoration:none; display:block; background-color:#4f46e5; color:white; text-align:center; padding:12px; border-radius:8px; font-weight:600; transition:0.2s;" onmouseover="this.style.backgroundColor='#4338ca'" onmouseout="this.style.backgroundColor='#4f46e5'">
                Book Venue Now
            </a>
        `;
        
        document.getElementById('modalDynamicBody').innerHTML = displayContent;
        document.getElementById('detailsModal').style.display = 'flex';
    }

    function closeHallDetails() {
        document.getElementById('detailsModal').style.display = 'none';
    }

    window.onclick = function(event) {
        let exploreModal = document.getElementById('exploreModal');
        let detailsModal = document.getElementById('detailsModal');
        
        if (event.target === exploreModal) {
            closeExploreModal();
        }
        if (event.target === detailsModal) {
            closeHallDetails();
        }
    }
    </script>
</body>
</html>
<?php $conn->close(); ?>