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

// 2. Fetch all available halls (Removed image_path)
$sql = "SELECT hall_id, hall_name, hall_capacity, price FROM hall";
$result = $conn->query($sql);
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
            background-color: white; /* Clean white background from your original design */
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
            color: #4f46e5; /* The Indigo/Blue accent on hover */
        }

        .nav-btn {
            background-color: #4f46e5; /* Your preferred blue */
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
            grid-template-columns: 1fr 1.5fr; /* Wireframe layout: Left side (Hero), Right side (Featured Halls) */
            gap: 60px;
            min-height: calc(100vh - 80px);
        }

        /* --- HERO SECTION (LEFT) --- */
        .hero-text {
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
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
            margin-right: 15px;
            transition: 0.3s;
        }

        .book-btn {
            background-color: #4f46e5; /* Your preferred blue */
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

        .hero-image {
            margin-top: 40px;
            width: 100%;
        }

        .hero-image img {
            width: 100%;
            border-radius: 15px;
            box-shadow: 0px 5px 20px rgba(0,0,0,0.08);
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
            background: linear-gradient(to bottom, #e0f2fe, #bcbeff); /* Updated placeholder gradient to match your blue style */
            position: relative;
        }

        /* Cloud visual representation from the wireframe */
        .hall-card-img::before {
            content: '';
            position: absolute;
            top: 35%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            width: 45px;
            height: 25px;
            border-radius: 20px;
            opacity: 0.85;
        }

        .hall-card-body {
            padding: 20px;
            display: flex;
            flex-direction: column;
            flex-grow: 1;
            justify-content: space-between;
        }

        .hall-title {
            font-size: 18px;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 10px;
        }

        .hall-capacity {
            font-size: 14px;
            color: #64748b;
            margin-bottom: 8px;
        }

        .hall-price {
            font-size: 14px;
            color: #94a3b8;
            margin-bottom: 20px;
        }

        .view-btn {
            display: block;
            background-color: #4f46e5; /* Adjusted to match your primary blue theme */
            color: white;
            text-decoration: none;
            padding: 10px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            transition: 0.2s;
        }

        .view-btn:hover {
            background-color: #4338ca;
        }

        /* --- FOOTER --- */
        footer {
            background-color: #111827;
            color: white;
            text-align: center;
            padding: 25px;
            margin-top: 60px;
        }

        /* --- RESPONSIVE ADJUSTMENTS --- */
        @media(max-width: 1024px) {
            .main-layout {
                grid-template-columns: 1fr;
                padding: 120px 30px 40px;
                gap: 40px;
            }
            nav {
                padding: 18px 30px;
            }
        }
    </style>
</head>
<body>

    <nav>
        <h2>EHBS</h2>
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
                <a href="#featured-section">
                    <button class="explore-btn">Explore Halls</button>
                </a>
            </div>

            <div class="hero-image">
                <img src="assets/images/hall.jpg" alt="Event Hall Display">
            </div>
        </section>

        <section class="featured-halls" id="featured-section">
            <h2>Featured Event Halls</h2>
            
            <div class="halls-grid">
    <?php
    // Check if there are any halls in the database
    if ($result && $result->num_rows > 0) {
        // Loop through each row of data
        while($row = $result->fetch_assoc()) {
            ?>
            
            <div class="hall-card">
                <div class="hall-card-img"></div>

                <div class="hall-card-body">
                    <div class="hall-title"><?php echo htmlspecialchars($row['hall_name']); ?></div>
                    
                    <div class="hall-capacity"><?php echo htmlspecialchars($row['hall_capacity']); ?> Guests</div>
                    
                    <div class="hall-price">RM <?php echo number_format($row['price'], 2); ?></div>
                    
                    <a href="hall-details.php?id=<?php echo $row['hall_id']; ?>" class="view-btn">View Details</a>
                </div>
            </div>

            <?php
        }
    } else {
        // Fallback if database table is empty
        echo "<p style='grid-column: 1/-1; color:#64748b;'>No halls available at the moment.</p>";
    }
    
    // Close connection after rendering
    $conn->close();
    ?>
</div>
        </section>

    </main>

    <footer>
        <div class="footer-nav">
           <a href="about.php">About Us</a>
           <a href="contact.php">Contact Support</a>
           <a href="policy.php">Privacy & Terms</a>
        </div>
        <p>© 2026 Event Hall Booking System | All Rights Reserved</p>
    </footer>

</body>
</html>