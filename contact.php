<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - Event Hall Booking System</title>

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
            grid-template-columns: 1fr 1.2fr;
            gap: 60px;
            min-height: calc(100vh - 80px);
        }

        .contact-info h1 {
            font-size: 42px;
            line-height: 1.2;
            color: #1e293b;
            margin-bottom: 25px;
        }

        .contact-info p {
            font-size: 16px;
            color: #64748b;
            line-height: 1.8;
            margin-bottom: 35px;
        }

        .info-details {
            margin-top: 20px;
        }

        .info-block {
            margin-bottom: 25px;
        }

        .info-block h4 {
            color: #1e293b;
            font-size: 16px;
            margin-bottom: 5px;
        }

        .info-block p {
            font-size: 15px;
            margin-bottom: 0;
        }

        /* --- FORM STYLES --- */
        .contact-form-container {
            background: white;
            padding: 40px;
            border-radius: 14px;
            box-shadow: 0px 4px 15px rgba(0,0,0,0.04);
            border: 1px solid #e2e8f0;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-size: 14px;
            font-weight: 500;
            color: #334155;
            margin-bottom: 8px;
        }

        .form-control {
            width: 100%;
            padding: 12px 16px;
            font-size: 15px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            outline: none;
            transition: 0.3s;
        }

        .form-control:focus {
            border-color: #4f46e5;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }

        textarea.form-control {
            resize: vertical;
            min-height: 120px;
        }

        .submit-btn {
            width: 100%;
            background-color: #4f46e5;
            color: white;
            padding: 14px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
        }

        .submit-btn:hover {
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
            <li><a href="about.php">About</a></li>
            <li><a href="contact.php" style="color: #4f46e5;">Contact</a></li>
            <li><a href="policy.php">Web Policy</a></li>
            <li>
                <a href="auth/login.php" class="nav-btn">Login</a>
            </li>
        </ul>
    </nav>

    <main class="main-layout">
        <section class="contact-info">
            <h1>Get In Touch</h1>
            <p>
                Have questions regarding custom pricing setups, structural limitations, or dynamic booking configurations? Fill out the contact form or reach us via our operational support desk channels below.
            </p>
            
            <div class="info-details">
                <div class="info-block">
                    <h4>Our Office</h4>
                    <p>123 Corporate Tower, Suite 4B, Kuala Lumpur, Malaysia</p>
                </div>
                <div class="info-block">
                    <h4>Support Email</h4>
                    <p>support@ehbs.com</p>
                </div>
                <div class="info-block">
                    <h4>Phone Number</h4>
                    <p>+60 3-1234 5678</p>
                </div>
            </div>
        </section>

        <section class="contact-form-container">
            <form action="#" method="POST">
                <div class="form-group">
                    <label for="name">Your Name</label>
                    <input type="text" id="name" name="name" class="form-control" placeholder="John Doe" required>
                </div>
                <div class="form-group">
                    <label for="email">Your Email</label>
                    <input type="email" id="email" name="email" class="form-control" placeholder="john@example.com" required>
                </div>
                <div class="form-group">
                    <label for="message">Message Details</label>
                    <textarea id="message" name="message" class="form-control" placeholder="Describe your inquiry..." required></textarea>
                </div>
                <button type="submit" class="submit-btn">Send Inquiry</button>
            </form>
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