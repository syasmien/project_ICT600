<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - Event Hall Booking System</title>

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
            grid-template-columns: 1.2fr 1fr;
            gap: 60px;
            min-height: calc(100vh - 80px);
        }

        .content-section h1 {
            font-size: 42px;
            line-height: 1.2;
            color: #1e293b;
            margin-bottom: 25px;
        }

        .content-section p {
            font-size: 16px;
            color: #64748b;
            line-height: 1.8;
            margin-bottom: 20px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-top: 40px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0px 4px 15px rgba(0,0,0,0.03);
            border: 1px solid #e2e8f0;
        }

        .stat-card h3 {
            font-size: 28px;
            color: #4f46e5;
            margin-bottom: 5px;
        }

        .stat-card p {
            font-size: 14px;
            margin-bottom: 0;
        }

        .side-image img {
            width: 100%;
            border-radius: 15px;
            box-shadow: 0px 5px 20px rgba(0,0,0,0.08);
        }

        /* --- FOOTER --- */
        footer {
            background-color: #111827;
            color: white;
            text-align: center;
            padding: 25px;
            margin-top: 60px;
        }

        .footer-nav {
            margin-bottom: 10px;
        }

        .footer-nav a {
            color: #94a3b8;
            text-decoration: none;
            margin: 0 10px;
            font-size: 14px;
        }

        .footer-nav a:hover {
            color: white;
        }

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
            <li><a href="index.php">Home</a></li>
            <li><a href="about.php" style="color: #4f46e5;">About</a></li>
            <li><a href="contact.php">Contact</a></li>
            <li><a href="policy.php">Web Policy</a></li>
            <li>
                <a href="auth/login.php" class="nav-btn">Login</a>
            </li>
        </ul>
    </nav>

    <main class="main-layout">
        <section class="content-section">
            <h1>About Our System</h1>
            <p>
                The Event Hall Booking System (EHBS) is designed to streamline the venue management and booking workflow. We bridge the gap between event organizers and premium venues, eliminating the traditional hassle of manual reservations.
            </p>
            <p>
                Whether you are planning an intimate wedding, a large-scale corporate seminar, or a family celebration, our platform gives you instant access to real-time venue availability, capacity checks, and pricing models transparently.
            </p>

            <div class="stats-grid">
                <div class="stat-card">
                    <h3>15+</h3>
                    <p>Premium Venues</p>
                </div>
                <div class="stat-card">
                    <h3>2k+</h3>
                    <p>Happy Events</p>
                </div>
                <div class="stat-card">
                    <h3>100%</h3>
                    <p>Secure Bookings</p>
                </div>
            </div>
        </section>

        <section class="side-image">
            <img src="assets/images/hall.jpg" alt="About Our Venues" onerror="this.style.background='#bcbeff'; this.style.height='350px';">
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