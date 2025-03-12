<?php
// contacts.php - Contact page with Google Maps and business details
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - Cab's Carwash</title>
    <link rel="stylesheet" href="assets/styles.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body {
            background-color: #222;
            color: #FFD700;
        }
        .contact-container {
            display: flex;
            gap: 30px;
            padding: 50px;
        }
        .map-container {
            flex: 1;
            background: #333;
            padding: 20px;
            border-radius: 10px;
            border: 2px solid #FFD700;
            text-align: center;
        }
        .map-container iframe {
            width: 100%;
            height: 400px;
            border: none;
        }
        .info-container {
            flex: 1;
            background: #111;
            padding: 20px;
            border-radius: 10px;
            border: 2px solid #FFD700;
        }
        .info-container h2 {
            font-size: 2rem;
            text-transform: uppercase;
            margin-bottom: 15px;
        }
        .info-container p {
            font-size: 1rem;
            color: #ccc;
            text-align: justify;
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
    </style>
</head>

<body>
    <?php include 'header.php'; ?>
    <section class="hero text-center text-white bg-black py-5">
        <div class="container">
            <div class="image-container mt-4">
                <img src="images/logo.png" class="img-fluid blurry-img" alt="Hero Image">
                <div class="overlay-text">CONTACTS</div>
            </div>
        </div>
    </section>

    <section class="contact-container">
        <div class="map-container">
            <h2>Our Location</h2>
            <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3738.555603373797!2d122.87113327475942!3d10.116682471016667!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x33ac1d79911064fd%3A0x845308808a141e34!2sSach%20Villa%2C%20Villa%20Julita%20Subdivision!5e1!3m2!1sen!2sph!4v1740800281295!5m2!1sen!2sph" width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
        </div>
        
        <div class="info-container">
            <h2>Contact Information</h2>
            <p><strong>Address:</strong> Villa Julita, Himamaylan, Philippines, 6108</p>
            <p><strong>Phone:</strong> (123)  0999 839 1967</p>
            <p><strong>Facebook Page:</strong> CAB'S Carwash and Detailing Services</p>
            
            <p>Feel free to reach out to us or visit us for the best car wash and detailing services in town.</p>
        </div>
    </section>

    <?php include 'footer.php'; ?>
</body>
</html>
