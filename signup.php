<?php
// signup.php - Signup page for the car wash website
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Signup | Cab's Carwash</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body {
            background-color: #000;
            color: #FFD700;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .container-fluid {
            max-width: 1200px;
        }
        .image-container {
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .image-container a {
            display: block;
            transition: transform 0.3s ease-in-out;
        }
        .image-container a:hover {
            transform: scale(1.05);
        }
        .image-container img {
            width: 100%;
            max-width: 450px; /* Ensures image is same width as form */
            height: auto;
        }
        .signup-container {
            background: #222;
            border: 2px solid #FFD700;
            border-radius: 10px;
            border-top-right-radius: 100px;
            padding: 40px;
            box-shadow: 0px 0px 15px rgba(255, 215, 0, 0.7);
            text-align: center;
            width: 100%;
            max-width: 450px;
        }
        .form-control {
            background: #333;
            color: white;
            border: none;
            padding: 10px;
        }
        .form-control::placeholder {
            color: #bbb;
        }
        .btn-signup {
            background-color: #FFD700; /* Gold */
            color: black;
            font-weight: bold;
            padding: 10px;
            width: 100%;
            border-radius: 30px;
            transition: 0.3s;
            border: 2px solid #FFD700;
        }
        .btn-signup:hover {
            background-color: black;
            color: #FFD700;
            border: 2px solid #FFD700;
        }
        a {
            color: #FFD700;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
        @media (max-width: 992px) {
            .row {
                flex-direction: column;
                align-items: center;
            }
            .image-container img {
                max-width: 300px;
                margin-bottom: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row justify-content-center align-items-center">
            
            <!-- Image on the Left -->
            <div class="col-md-6 text-center image-container">
                <a href="main.php">
                    <img src="images/logo3.png" alt="Car Wash Logo">
                </a>
            </div>

            <!-- Signup Container on the Right -->
            <div class="col-md-6 d-flex justify-content-center">
                <div class="signup-container">
                    <h2>Sign Up</h2>
                    <form action="auth.php" method="POST">
                        <input type="hidden" name="action" value="signup">
                        <div class="mb-3">
                            <input type="text" name="fullname" class="form-control" placeholder="Full Name" required>
                        </div>
                        <div class="mb-3">
                            <input type="email" name="email" class="form-control" placeholder="Email" required>
                        </div>
                        <div class="mb-3">
                            <input type="text" name="contact_number" class="form-control" placeholder="Contact Number" required>
                        </div>
                        <div class="mb-3">
                            <input type="password" name="password" class="form-control" placeholder="Password" required>
                        </div>
                        <button type="submit" class="btn btn-signup">Sign Up</button>
                        <p class="mt-3">Already have an account? <a href="login.php">Login</a></p>
                    </form>
                </div>
            </div>

        </div>
    </div>
</body>
</html>



