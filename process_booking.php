<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = htmlspecialchars($_POST["name"]);
    $email = htmlspecialchars($_POST["email"]);
    $service = htmlspecialchars($_POST["service"]);
    $date = htmlspecialchars($_POST["date"]);

    echo "<h2>Booking Confirmed!</h2>";
    echo "<p>Thank you, $name. Your $service appointment is scheduled for $date.</p>";
    echo "<a href='index.php'>Go Home</a>";
} else {
    header("Location: booking.php");
    exit();
}
?>
