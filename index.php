<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Hall Booking System</title>

    <!-- GOOGLE FONT -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>

        *{
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body{
            background-color: #f5f7fb;
        }

        /* NAVBAR */

        nav{
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

        nav h2{
            color: #2c3e50;
            font-weight: 700;
        }

        nav ul{
            list-style: none;
            display: flex;
            gap: 30px;
            align-items: center;
        }

        nav ul li a{
            text-decoration: none;
            color: #333;
            font-weight: 500;
            transition: 0.3s;
        }

        nav ul li a:hover{
            color: #4f46e5;
        }

        .nav-btn{
            background-color: #4f46e5;
            color: white !important;
            padding: 10px 18px;
            border-radius: 8px;
        }

        /* HERO SECTION */

        .hero{
            min-height: 100vh;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 140px 80px 80px;
            gap: 50px;
        }

        .hero-text{
            flex: 1;
        }

        .hero-text h1{
            font-size: 58px;
            line-height: 1.2;
            color: #1e293b;
            margin-bottom: 25px;
        }

        .hero-text p{
            font-size: 18px;
            color: #64748b;
            line-height: 1.8;
            margin-bottom: 35px;
        }

        .hero-buttons a{
            text-decoration: none;
        }

        .hero-buttons button{
            padding: 14px 28px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            margin-right: 15px;
            transition: 0.3s;
        }

        .book-btn{
            background-color: #4f46e5;
            color: white;
        }

        .explore-btn{
            background-color: white;
            border: 1px solid #dcdcdc !important;
        }

        .hero-buttons button:hover{
            transform: translateY(-2px);
        }

        /* HERO IMAGE */

        .hero-image{
            flex: 1;
        }

        .hero-image img{
            width: 100%;
            border-radius: 20px;
            box-shadow: 0px 10px 30px rgba(0,0,0,0.15);
        }

        /* FEATURES */

        .features{
            padding: 80px;
            text-align: center;
        }

        .features h2{
            font-size: 36px;
            margin-bottom: 15px;
            color: #1e293b;
        }

        .features p{
            color: #64748b;
            margin-bottom: 50px;
        }

        .feature-container{
            display: flex;
            justify-content: center;
            gap: 30px;
            flex-wrap: wrap;
        }

        .feature-card{
            background-color: white;
            width: 300px;
            padding: 35px 25px;
            border-radius: 18px;
            box-shadow: 0px 5px 20px rgba(0,0,0,0.05);
            transition: 0.3s;
        }

        .feature-card:hover{
            transform: translateY(-5px);
        }

        .feature-card h3{
            margin-bottom: 15px;
            color: #4f46e5;
        }

        .feature-card p{
            color: #64748b;
            line-height: 1.7;
        }

        /* FOOTER */

        footer{
            background-color: #111827;
            color: white;
            text-align: center;
            padding: 25px;
            margin-top: 60px;
        }

        /* RESPONSIVE */

        @media(max-width: 900px){

            nav{
                padding: 18px 30px;
            }

            .hero{
                flex-direction: column;
                text-align: center;
                padding: 140px 30px 60px;
            }

            .hero-text h1{
                font-size: 42px;
            }

            .hero-buttons button{
                margin-bottom: 15px;
            }

            .features{
                padding: 60px 30px;
            }
        }

    </style>
</head>
<body>

    <!-- NAVBAR -->

    <nav>

        <h2>EHBS</h2>

        <ul>
            <li><a href="#">Home</a></li>
            <li><a href="#">About</a></li>
            <li><a href="#">Contact</a></li>

            <li>
                <a href="auth/login.php" class="nav-btn">
                    Login
                </a>
            </li>
        </ul>

    </nav>

    <!-- HERO SECTION -->

    <section class="hero">

        <div class="hero-text">

            <h1>
                Book Your Perfect Event Hall Easily
            </h1>

            <p>
                Our Event Hall Booking System helps customers reserve halls
                for weddings, meetings, seminars, corporate events, and
                celebrations quickly and efficiently.
            </p>

            <div class="hero-buttons">

                <a href="auth/register.php">
                    <button class="book-btn">
                        Get Started
                    </button>
                </a>

                <a href="#">
                    <button class="explore-btn">
                        Explore Halls
                    </button>
                </a>

            </div>

        </div>

        <div class="hero-image">

            <img src="assets/images/hall.jpg" alt="Event Hall">

        </div>

    </section>

    <!-- FEATURES -->

    <section class="features">

        <h2>Why Choose Our System?</h2>

        <p>
            Manage your event hall booking process more efficiently.
        </p>

        <div class="feature-container">

            <div class="feature-card">
                <h3>Easy Booking</h3>

                <p>
                    Customers can easily book halls online anytime and anywhere.
                </p>
            </div>

            <div class="feature-card">
                <h3>Booking Tracking</h3>

                <p>
                    View booking status such as pending, approved, or rejected.
                </p>
            </div>

            <div class="feature-card">
                <h3>Hall Management</h3>

                <p>
                    Staff can manage halls and booking approvals efficiently.
                </p>
            </div>

        </div>

    </section>

    <!-- FOOTER -->

    <footer>

        <p>
            © 2026 Event Hall Booking System | All Rights Reserved
        </p>

    </footer>

</body>
</html>