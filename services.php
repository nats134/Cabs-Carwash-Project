<?php
// index.php - Main page of the car wash website
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cab's Carwash and Detailing</title>
    <link rel="stylesheet" href="assets/styles.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        /* Hero Section */
        .hero {
            position: relative;
            text-align: center;
        }

        .hero .overlay-text {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 3rem;
            font-weight: bold;
            background: rgba(0, 0, 0, 0.7);
            padding: 20px 40px;
            border-radius: 10px;
            color: #FFD700;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
            letter-spacing: 2px;
        }

        /* Services Section */
        .service-section {
            padding: 80px 0;
            background-color: #222;
            color: #FFD700;
            text-align: center;
        }

        .service-section h2 {
            font-size: 2.5rem;
            margin-bottom: 30px;
            text-transform: uppercase;
        }

        .service-container {
            display: flex;
            justify-content: center;
            gap: 30px;
            flex-wrap: wrap;
        }

        .service-box {
            background-color: #333;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            width: 300px;
            border: 2px solid #FFD700;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .service-box img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 10px;
        }

        .service-box h3 {
            font-size: 1.5rem;
            margin-top: 15px;
            color: #fff;
        }

        .service-box p {
            font-size: 1rem;
            color: #ccc;
            text-align: justify;
            flex-grow: 1;
        }

        .btn-service {
            background-color: #FFD700;
            color: black;
            padding: 10px 20px;
            border-radius: 30px;
            text-decoration: none;
            font-weight: bold;
            transition: 0.3s;
            display: inline-block;
            margin-top: 15px;
        }

        .btn-service:hover {
            background-color: #FFC107;
            transform: scale(1.05);
        }

        .blurry-img {
            filter: blur(5px);
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <!-- Hero Section -->
    <section class="hero text-center text-white bg-black py-5">
        <div class="container">
            <div class="image-container mt-4">
                <img src="images/logo.png" class="img-fluid blurry-img" alt="Hero Image">
                <div class="overlay-text">OUR SERVICES</div>
            </div>
        </div>
    </section>

    <!-- Car Wash Services Section -->
    <section class="service-section">
        <div class="container">
            <h2>Car Wash Services</h2>
            <div class="service-container">
                <div class="service-box">
                    <img src="images/carwash1.jpg" alt="Express Wash">
                    <h3>Express Wash</h3>
                    <p>A quick wash including exterior cleaning and light drying. Great for regular maintenance.</p>
                    <a href="booking.php" class="btn-service">Book Now</a>
                </div>

                <div class="service-box">
                    <img src="images/carwash2.jpg" alt="Deluxe Wash">
                    <h3>Deluxe Wash</h3>
                    <p>Includes Express Wash plus undercarriage cleaning and waxing for added shine.</p>
                    <a href="booking.php" class="btn-service">Book Now</a>
                </div>

                <div class="service-box">
                    <img src="images/carwash3.jpg" alt="Supreme Wash">
                    <h3>Supreme Wash</h3>
                    <p>Hand drying, vacuuming, dashboard cleaning, and a high-quality wax finish.</p>
                    <a href="booking.php" class="btn-service">Book Now</a>
                </div>

                <div class="service-box">
                    <img src="images/carwash4.jpg" alt="Ultimate Wash">
                    <h3>Ultimate Wash</h3>
                    <p>Complete wash with deep interior cleaning, premium waxing, tire detailing, and coatings.</p>
                    <a href="booking.php" class="btn-service">Book Now</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Detailing Services Section -->
    <section class="service-section" style="background-color: #111;">
        <div class="container">
            <h2>Detailing Services</h2>
            <div class="service-container">
                <div class="service-box">
                    <img src="images/detailing1.jpg" alt="Interior Detailing">
                    <h3>Interior Detailing</h3>
                    <p>Deep clean of seats, carpets, dashboard, and windows with stain removal.</p>
                    <a href="booking.php" class="btn-service">Book Now</a>
                </div>

                <div class="service-box">
                    <img src="images/detailing2.jpg" alt="Exterior Detailing">
                    <h3>Exterior Detailing</h3>
                    <p>Waxing, polishing, paint correction, and ceramic coating for long-lasting shine.</p>
                    <a href="booking.php" class="btn-service">Book Now</a>
                </div>
            </div>
        </div>
    </section>

    <?php include 'footer.php'; ?>
</body>
</html>
