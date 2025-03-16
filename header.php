
<header class="bg-black text-white py-3">
    <div class="container d-flex justify-content-between align-items-center">
        <h1 class="logo">CABS</h1>
            <nav>
                <ul class="nav">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <!-- Navbar for logged-in users (userdashboard.php) -->
                        <li class="nav-item"><a class="nav-link" href="userdashboard.php">Home</a></li>
                        <li class="nav-item"><a class="nav-link" href="userdashboard.php#services">Services</a></li>
                        <li class="nav-item"><a class="nav-link" href="userdashboard.php#booking">Booking</a></li>
                        <li class="nav-item"><a class="nav-link" href="userdashboard.php#booking">Contact Us</a></li>
                    <?php else: ?>
                        <!-- Navbar for non-logged-in users (main.php) -->
                        <li class="nav-item"><a class="nav-link" href="main.php">Home</a></li>
                        <li class="nav-item"><a class="nav-link" href="services.php">Services</a></li>
                        <li class="nav-item"><a class="nav-link" href="booking.php">Booking</a></li>
                        <li class="nav-item"><a class="nav-link" href="contact.php">Contact Us</a></li>
                    <?php endif; ?>
                </ul>
            </nav>

            <?php if (isset($_SESSION['user_id'])): ?>
                <!-- Display Profile and Logout buttons for logged-in users -->
                <div class="user-menu">
                    <a href="profile.php" class="btn btn-profile">Profile</a>
                    <a href="logout.php" class="btn btn-logout">Logout</a>
                </div>
            <?php else: ?>
                <!-- Display Login button for non-logged-in users -->
                <a href="login.php" class="btn btn-login">Login</a>
            <?php endif; ?>
        </div>
    </header>

<style>
    header {
        background-color: #000;
        scroll-behavior: smooth;
    }
    .logo {
        color: #FDD101;
        font-size: 2rem;
        font-weight: bold;
    }
    .nav .nav-link {
        color: #FDD101 !important;
        font-weight: 500;
        transition: color 0.3s;
    }
    .nav .nav-link:hover {
        color: #fff !important;
    }
    .btn-login, .btn-logout, .btn-profile {
        background-color: #FDD101;
        color: black;
        font-weight: bold;
        padding: 10px 20px;
        border-radius: 30px;
        transition: all 0.3s ease-in-out;
        text-decoration: none;
        margin-left: 10px;
    }
    .btn-login:hover, .btn-logout:hover, .btn-profile:hover {
        background-color: #FFC107;
        transform: scale(1.05);
        color: black;
    }
    .user-menu {
        display: flex;
        gap: 10px;
    }

    html {
        scroll-behavior: smooth;
    }
</style>

