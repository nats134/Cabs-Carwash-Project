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
        /* Gallery section styling */
        .gallery-section {
            background-color: #222; /* Black background */
            padding: 50px 0;
            color: #000;
        }

        .gallery-section h2 {
            color: #ffcc00; /* Gold color */
            font-family: 'Arial Black', sans-serif;
            margin-bottom: 30px;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .gallery {
            display: flex;
            justify-content: space-between;
            gap: 20px;
        }

        /* Styled gallery item */
        .gallery-item {
            width: 380px;
            height: 300px;
            overflow: hidden;
            border-radius: 10px;
            background-color: #111; /* Darker black for contrast */
            position: relative;
            text-align: center;
            box-shadow: 0px 4px 15px rgba(255, 204, 0, 0.3); /* Gold shadow */
            display: flex;
            align-items: center;
            justify-content: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .gallery-item:hover {
            transform: translateY(-10px);
            box-shadow: 0px 8px 20px rgba(255, 204, 0, 0.5); /* Enhanced gold shadow on hover */
        }

        /* Smooth transition for images */
        .image-container {
            position: relative;
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .gallery-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            position: absolute;
            top: 0;
            left: 0;
            opacity: 0;
            transform: scale(1.2);
            transition: opacity 0.5s ease-in-out, transform 0.5s ease-in-out;
        }

        .gallery-item img.active {
            opacity: 1;
            transform: scale(1);
        }

        /* Navigation buttons */
        .nav-buttons {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            display: flex;
            justify-content: space-between;
            width: 100%;
            padding: 0 20px;
        }

        .nav-buttons button {
            background-color: rgba(255, 204, 0, 0.8); /* Gold color */
            color: #000; /* Black text */
            border: none;
            padding: 10px 15px;
            cursor: pointer;
            font-size: 18px;
            transition: background-color 0.3s;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .nav-buttons button:hover {
            background-color: rgba(255, 204, 0, 1); /* Brighter gold on hover */
        }

        /* Caption styling */
        .gallery-item .caption {
            position: absolute;
            bottom: 10px;
            left: 50%;
            transform: translateX(-50%);
            background-color: rgba(0, 0, 0, 0.7); /* Semi-transparent black */
            color: #ffcc00; /* Gold color */
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Services section styling */
        .services {
            background-color: #f5f5f5; /* Dirty white color */
            padding: 50px 0;
        }

        .services h2 {
            color: #222; /* Dark text for contrast */
            font-family: 'Arial Black', sans-serif;
            margin-bottom: 20px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .services p {
            color: #444; /* Slightly lighter dark text */
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 20px;
        }

        .services .btn-warning {
            background-color: #ffcc00; /* Gold color */
            color: #222; /* Dark text */
            border: none;
            padding: 10px 20px;
            font-size: 16px;
            text-transform: uppercase;
            font-weight: bold;
            letter-spacing: 1px;
            transition: background-color 0.3s ease;
        }

        .services .btn-warning:hover {
            background-color: #e6b800; /* Darker gold on hover */
        }

        .services .image-container img {
            border-radius: 10px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1); /* Subtle shadow */
        }
    </style>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            document.querySelectorAll(".gallery-item").forEach(gallery => {
                let images = gallery.querySelectorAll("img");
                let index = 0;
                images[index].classList.add("active");

                gallery.querySelector(".prev").addEventListener("click", function () {
                    images[index].classList.remove("active");
                    index = (index - 1 + images.length) % images.length;
                    images[index].classList.add("active");
                });

                gallery.querySelector(".next").addEventListener("click", function () {
                    images[index].classList.remove("active");
                    index = (index + 1) % images.length;
                    images[index].classList.add("active");
                });
            });
        });
    </script>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <section class="hero text-center text-white bg-black py-5">
        <div class="container">
            <div class="image-container mt-4">
                <img src="images/logo.png" class="img-fluid" alt="Hero Image">
            </div>
        </div>
    </section>
    
    <section class="services py-5">
        <div class="container d-flex align-items-center">
            <div class="image-container me-4">
                <img src="images/logo.png" class="img-fluid" alt="Services Image">
            </div>
            <div>
                <h2>Services, Detailing and Booking</h2>
                <p>We offer several services, whether it's a car wash or detailing. You can also book your appointment here.</p>
                <a href="#" class="btn btn-warning">Book Now</a>
            </div>
        </div>
    </section>
    
    <section class="gallery-section text-center">
        <div class="container">
            <h2>Slideshow Presentations</h2>
            <div class="gallery">
                <div class="gallery-item">
                    <div class="image-container">
                        <img src="images/logo.png" alt="Basic Wash">
                        <img src="images/logo.png" alt="Detailing">
                        <img src="images/logo.png" alt="Polishing">
                    </div>
                    <div class="nav-buttons">
                        <button class="prev">&#10094;</button>
                        <button class="next">&#10095;</button>
                    </div>
                </div>
                <div class="gallery-item">
                    <div class="image-container">
                        <img src="images/logo.png" alt="Paris">
                        <img src="images/logo.png" alt="Tokyo">
                        <img src="images/logo.png" alt="New York">
                    </div>
                    <div class="nav-buttons">
                        <button class="prev">&#10094;</button>
                        <button class="next">&#10095;</button>
                    </div>
                </div>
                <div class="gallery-item">
                    <div class="image-container">
                        <img src="images/logo3.png" alt="Pizza">
                        <img src="images/logo.png" alt="Sushi">
                        <img src="images/logo.png" alt="Burger">
                    </div>
                    <div class="nav-buttons">
                        <button class="prev">&#10094;</button>
                        <button class="next">&#10095;</button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php include 'footer.php'; ?>
</body>
</html>