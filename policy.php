<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Web Policy - Event Hall Booking System</title>

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

        /* --- CENTRAL POLICY CONTENT --- */
        .policy-container {
            max-width: 900px;
            margin: 0 auto;
            padding: 140px 30px 40px;
            min-height: calc(100vh - 80px);
        }

        .policy-header {
            text-align: center;
            margin-bottom: 50px;
        }

        .policy-header h1 {
            font-size: 38px;
            color: #1e293b;
            margin-bottom: 10px;
        }

        .policy-header p {
            color: #64748b;
            font-size: 15px;
        }

        .policy-section {
            background: white;
            padding: 35px;
            border-radius: 12px;
            box-shadow: 0px 4px 15px rgba(0,0,0,0.03);
            border: 1px solid #e2e8f0;
            margin-bottom: 25px;
        }

        .policy-section h2 {
            font-size: 20px;
            color: #1e293b;
            margin-bottom: 15px;
            font-weight: 600;
        }

        .policy-section p {
            font-size: 15px;
            color: #64748b;
            line-height: 1.8;
            margin-bottom: 15px;
        }

        .policy-section ul {
            padding-left: 20px;
            color: #64748b;
        }

        .policy-section ul li {
            font-size: 15px;
            line-height: 1.8;
            margin-bottom: 8px;
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

        @media(max-width: 768px) {
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
            <li><a href="about.php">About</a></li>
            <li><a href="contact.php">Contact</a></li>
            <li><a href="policy.php" style="color: #4f46e5;">Web Policy</a></li>
            <li>
                <a href="auth/login.php" class="nav-btn">Login</a>
            </li>
        </ul>
    </nav>

    <main class="policy-container">
        <div class="policy-header">
            <h1>Web Policy & Terms</h1>
            <p>Last updated: July 2026</p>
        </div>

        <section class="policy-section">
            <h2>1. Account & Registration</h2>
            <p>
                Users must register an account to confirm any official booking reservations. You are responsible for maintaining the confidentiality of your credentials during registration and authentication cycles.
            </p>
        </section>

        <section class="policy-section">
            <h2>2. Booking & Cancellation Rules</h2>
            <ul>
                <li>Reservations are strictly subject to approval upon verification of capacity limits and scheduling availability.</li>
                <li>Cancellations requested within 48 hours of scheduled usage windows may encounter penalties defined by target venue protocols.</li>
                <li>Dynamic system price indices represent real-time updates and are frozen only upon verified payment transactions.</li>
            </ul>
        </section>

        <section class="policy-section">
            <h2>3. Privacy & Data Integrity</h2>
            <p>
                We value your security. Any personal data collected during the reservation process is encrypted securely and processed strictly for verification, booking validations, and legal auditing purposes.
            </p>
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