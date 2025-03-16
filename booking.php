<?php
// booking.php - Booking page with a calendar and appointment context
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking - Cab's Carwash</title>
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
        body {
            background-color: #222;
            color: #FFD700;
        }

        .booking-container {
            display: flex;
            gap: 30px;
            padding: 50px;
        }

        .calendar-container {
            flex: 1;
            background: #333;
            padding: 20px;
            border-radius: 10px;
            border: 2px solid #FFD700;
            text-align: center;
        }

        .context-container {
            flex: 1;
            background: #111;
            padding: 20px;
            border-radius: 10px;
            border: 2px solid #FFD700;
        }

        .calendar-container iframe {
            width: 100%;
            height: 400px;
            border: none;
        }

        .context-container h2 {
            font-size: 2rem;
            text-transform: uppercase;
            margin-bottom: 15px;
        }

        .context-container p {
            font-size: 1rem;
            color: #ccc;
            text-align: justify;
        }

        .blurry-img {
            filter: blur(5px);
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
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <section class="hero text-center text-white bg-black py-5">
        <div class="container">
            <div class="image-container mt-4">
                <img src="images/logo.png" class="img-fluid blurry-img" alt="Hero Image">
                <div class="overlay-text">BOOK APPOINTMENTS</div>
            </div>
        </div>
    </section>

    <section class="booking-container">
        <div class="calendar-container">
            <h2>Appointment Calendar</h2>
            <iframe src="https://calendar.google.com/calendar/embed?src=your_calendar_id&ctz=Your_Timezone"></iframe>
        </div>
        
        <div class="context-container">
            <h2>Booking Information</h2>
            <p>Welcome to our appointment calendar! Here, you can check our availability and find the best time to visit. We offer a range of car wash and detailing services to keep your vehicle in top condition.</p>
            <p>Appointments are recommended but not required. Walk-ins are always welcome, but scheduling ensures you receive prompt service.</p>
            <p><strong>Operating Hours:</strong></p>
            <ul>
                <li>7:30 AM - 5:00 PM Monday - Saturday</li>
                <li>Sunday: Closed</li>
            </ul>
            <p>For any inquiries, feel free to contact us at (123) 0999 839 1967 or message as on our facebook page at info@cabscarwash.com.</p>

            <a href="login.php" class="btn-service">Book Now</a>
        </div>
    
    </section>
    
    <script>
        (function(){if(!window.chatbase||window.chatbase("getState")!=="initialized"){window.chatbase=(...arguments)=>{if(!window.chatbase.q){window.chatbase.q=[]}window.chatbase.q.push(arguments)};window.chatbase=new Proxy(window.chatbase,{get(target,prop){if(prop==="q"){return target.q}return(...args)=>target(prop,...args)}})}const onLoad=function(){const script=document.createElement("script");script.src="https://www.chatbase.co/embed.min.js";script.id="tCOx2-6X6cn1Gh7YalMGY";script.domain="www.chatbase.co";document.body.appendChild(script)};if(document.readyState==="complete"){onLoad()}else{window.addEventListener("load",onLoad)}})();

    </script>
    <?php include 'footer.php'; ?>
</body>
</html>
