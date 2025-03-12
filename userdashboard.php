<?php

// Start the session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
require 'db_connection.php';

// Redirect to login if user_id is not set
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    error_log("Session user_id is not set. Redirecting to login.");
    header("Location: login.php");
    exit();
}

// Fetch user details
$stmt = $conn->prepare("SELECT fullname FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

// Initialize $user as an array
$user = ['fullname' => 'Guest']; // Default value

// Check if the query was successful and fetch data
if ($result !== false) {
    $fetchedUser = $result->fetch_assoc();
    if (is_array($fetchedUser) && isset($fetchedUser['fullname'])) {
        $user = $fetchedUser; // Use fetched data if valid
    }
}

// Debugging: Log the fetched user data
error_log("Fetched User Data: " . var_export($user, true));

$stmt->close();

// ==================================================
// Fetch reserved orders for the user
$sql = "SELECT order_id, service, appointment_date, appointment_time, status 
        FROM orders 
        WHERE user_id = ? AND status = 'Reserved'";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$reserved_orders = [];
while ($row = $result->fetch_assoc()) {
    $row['services'] = json_decode($row['service'], true); // Decode services
    $reserved_orders[] = $row;

    // Debug: Log the fetched appointment date and time
    error_log("Fetched Appointment Date: " . $row['appointment_date']);
    error_log("Fetched Appointment Time: " . $row['appointment_time']);
}

$stmt->close();// ==================================================

// Fetch services and detailings
$services = $conn->query("SELECT id, name, price_small, price_medium, price_large, image FROM services");
if (!$services) {
    die("Error fetching services: " . $conn->error);
}
$services = $services->fetch_all(MYSQLI_ASSOC);

$detailings = $conn->query("SELECT id, name, price_small, price_medium, price_large, image FROM detailings");
if (!$detailings) {
    die("Error fetching detailings: " . $conn->error);
}
$detailings = $detailings->fetch_all(MYSQLI_ASSOC);

// Ensure session arrays are initialized
$_SESSION['cart'] = $_SESSION['cart'] ?? [];
$_SESSION['reserved_orders'] = $_SESSION['reserved_orders'] ?? [];

// Convert session cart to an array if it was mistakenly stored as a string
if (is_string($_SESSION['cart'])) {
    $_SESSION['cart'] = json_decode($_SESSION['cart'], true);
}

// Vehicle service booking
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['service_id'], $_POST['vehicle_type'])) {
    $service_id = $_POST['service_id'];
    $vehicle_type = $_POST['vehicle_type'];
    $service_found = false;

    foreach ($services as $service) {
        if ($service['id'] == $service_id) {
            $service_found = true;
            $price_key = "price_" . strtolower($vehicle_type);
            $price = $service[$price_key] ?? 0;
            $_SESSION['cart'][] = [
                'id' => $service_id,
                'name' => $service['name'],
                'price' => $price,
                'vehicle_type' => $vehicle_type
            ];
            header("Location: userdashboard.php");
            exit();
        }
    }

    if (!$service_found) {
        echo "<script>alert('Invalid service selected.');</script>";
    }
}

// Detailing service booking
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['detailing_id'], $_POST['vehicle_type'])) {
    $detailing_id = $_POST['detailing_id'];
    $vehicle_type = $_POST['vehicle_type'];
    $detailing_found = false;

    foreach ($detailings as $detailing) {
        if ($detailing['id'] == $detailing_id) {
            $detailing_found = true;
            $price_key = "price_" . strtolower($vehicle_type);
            $price = $detailing[$price_key] ?? 0;
            $_SESSION['cart'][] = [
                'id' => $detailing_id,
                'name' => $detailing['name'],
                'price' => $price,
                'vehicle_type' => $vehicle_type
            ];
            header("Location: userdashboard.php");
            exit();
        }
    }

    if (!$detailing_found) {
        echo "<script>alert('Invalid detailing service selected.');</script>";
    }
}

// Define days and calculate actual dates
$days = ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"]; // Ensure $days is initialized as an array
$dates = [];

if (is_array($days)) { // Check if $days is an array
    foreach ($days as $day) {
        $timestamp = strtotime("next " . $day); // Calculate the timestamp for the next occurrence of the day
        if ($timestamp !== false) { // Check if strtotime returned a valid timestamp
            $dates[$day] = date("Y-m-d", $timestamp); // Store the date in the $dates array
        } else {
            error_log("Warning: Invalid day format or strtotime failed for day: " . $day);
        }
    }
} else {
    error_log("Warning: \$days is not an array or is null.");
}

// Debugging: Log the $dates array
error_log("Dates: " . print_r($dates, true));

// Appointment booking
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['appointment_date'])) {
    $appointment_date = $_POST['appointment_date'];
    $appointment_time = $_POST['fixed_time'] ?? $_POST['custom_time'] ?? null;

    if (!$appointment_time) {
        echo "<script>alert('Please select a valid time.');</script>";
    } else {
        // Validate time within 7 AM - 5 PM
        $hour = (int)explode(":", $appointment_time)[0];

        if ($hour < 7 || $hour > 17) {
            echo "<script>alert('Please select a time between 7:00 AM and 5:00 PM.');</script>";
        } else {
            // Check if the selected date is valid (exists in the $dates array)
            if (!in_array($appointment_date, $dates)) {
                echo "<script>alert('Invalid appointment date selected.');</script>";
            } else {
                // Check availability
                $stmt = $conn->prepare("SELECT COUNT(*) as count FROM appointments WHERE appointment_date = ? AND appointment_time = ?");
                $stmt->bind_param("ss", $appointment_date, $appointment_time);
                $stmt->execute();
                $result = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if ($result['count'] == 0) {
                    $_SESSION['cart'][] = [
                        'name' => 'Appointment',
                        'date' => $appointment_date,
                        'time' => $appointment_time,
                        'price' => 0, // Add default price for appointments
                        'vehicle_type' => 'N/A' // Add default vehicle type for appointments
                    ];

                    $stmt = $conn->prepare("INSERT INTO appointments (user_id, appointment_date, appointment_time) VALUES (?, ?, ?)");
                    $stmt->bind_param("iss", $user_id, $appointment_date, $appointment_time);
                    if (!$stmt->execute()) {
                        error_log("Failed to insert appointment: " . $stmt->error);
                    }
                    $stmt->close();

                    echo "<script>alert('Appointment successfully added to cart!');</script>";
                } else {
                    echo "<script>alert('Selected time slot is not available.');</script>";
                }
            }
        }
    }
}

// Remove item from cart
if (isset($_GET['remove'])) {
    unset($_SESSION['cart'][$_GET['remove']]);
    $_SESSION['cart'] = array_values($_SESSION['cart']);
    header("Location: userdashboard.php");
    exit();
}

// Calculate total price of services in cart
$total = 0;
foreach ($_SESSION['cart'] as $item) {
    $total += $item['price'] ?? 0; // Use default value if price is missing
}

if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['reserve_order'])) {
    if (!empty($_SESSION['cart'])) {
        $user_id = $_SESSION['user_id'] ?? 0; // Ensure user ID is set
        $status = "Reserved";
        $created_at = date('Y-m-d H:i:s');

        // Get appointment details from the cart
        $appointment_date = null;
        $appointment_time = null;

        // Find the appointment in the cart
        foreach ($_SESSION['cart'] as $item) {
            if (strtolower(trim($item['name'])) === 'appointment') {
                $appointment_date = $item['date'];
                $appointment_time = $item['time'];
                break;
            }
        }

        // If no appointment is found, use default values
        if (!$appointment_date || !$appointment_time) {
            $appointment_date = date('Y-m-d');
            $appointment_time = '00:00:00';
        }

        // Convert cart items into a JSON string to store in the service column
        $services_json = json_encode($_SESSION['cart']);

        // Prepare service_name, vehicle_type, and price as comma-separated strings
        $service_names = [];
        $vehicle_types = [];
        $prices = [];

        foreach ($_SESSION['cart'] as $item) {
            if ($item['name'] !== 'Appointment') {
                $service_names[] = $item['name'];
                $vehicle_types[] = $item['vehicle_type'];
                $prices[] = $item['price'];
            }
        }

        $service_name = implode(", ", $service_names);
        $vehicle_type = implode(", ", $vehicle_types);
        $price = array_sum($prices);

        // Insert into orders table as ONE row
        $stmt = $conn->prepare("INSERT INTO orders (user_id, service, service_name, vehicle_type, price, appointment_date, appointment_time, status, created_at) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssdssss", $user_id, $services_json, $service_name, $vehicle_type, $price, $appointment_date, $appointment_time, $status, $created_at);

        if ($stmt->execute()) {
            // Retrieve the auto-generated order_id
            $order_id = $conn->insert_id; // Get the last inserted ID
            echo "<pre>Order inserted successfully with order ID: " . $order_id . "</pre>"; // Debugging

            // Store the reserved order in session
            $_SESSION['reserved_orders'][] = [
                'order_id' => $order_id, // Use the auto-generated order_id
                'services' => $_SESSION['cart'],
                'appointment_date' => $appointment_date,
                'appointment_time' => $appointment_time,
                'status' => 'Reserved'
            ];

            // Debugging: Confirm session data
            echo "<pre>Session reserved_orders after insertion: " . print_r($_SESSION['reserved_orders'], true) . "</pre>";

            // Clear shopping cart after reserving
            $_SESSION['cart'] = [];

            echo "<script>alert('Order reserved successfully!');</script>";
        } else {
            echo "<script>alert('Failed to reserve order.');</script>";
            error_log("Error inserting order: " . $stmt->error);
        }

        $stmt->close();
    } else {
        echo "<script>alert('Cart is empty!');</script>";
    }
}

////////////////////

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    // Handle Order Confirmation
    if (isset($_POST['confirm_order'])) {
        $orderIndex = $_POST['order_index'];
        echo "<pre>Confirming order index: " . $orderIndex . "</pre>"; // Debugging

        if (isset($_SESSION['reserved_orders'][$orderIndex])) {
            // Get the order ID from the session
            $order_id = $_SESSION['reserved_orders'][$orderIndex]['order_id'];
            echo "<pre>Confirming order ID: " . $order_id . "</pre>"; // Debugging

            // Update the status in the database to "Confirmed"
            $stmt = $conn->prepare("UPDATE orders SET status = 'Confirmed' WHERE order_id = ?");
            $stmt->bind_param("i", $order_id); // Use "i" for integer order_id

            if ($stmt->execute()) {
                echo "<pre>Order status updated successfully for order ID: " . $order_id . "</pre>"; // Debugging
                // Remove the order from the reserved_orders array
                unset($_SESSION['reserved_orders'][$orderIndex]);
                // Reindex the reserved_orders array
                $_SESSION['reserved_orders'] = array_values($_SESSION['reserved_orders']);
                echo "<script>alert('Order confirmed successfully!');</script>";
            } else {
                echo "<pre>Failed to update order status in database: " . $stmt->error . "</pre>"; // Debugging
            }
            $stmt->close();
        } else {
            echo "<pre>Invalid order or no reserved orders found.</pre>"; // Debugging
            echo "<script>alert('Invalid order or no reserved orders found.');</script>";
        }
    }

    // Handle Order Cancellation
    if (isset($_POST['cancel_order'])) {
        $orderIndex = $_POST['order_index'];
        echo "<pre>Cancelling order index: " . $orderIndex . "</pre>"; // Debugging

        if (isset($_SESSION['reserved_orders'][$orderIndex])) {
            // Get the order ID from the session
            $order_id = $_SESSION['reserved_orders'][$orderIndex]['order_id'];
            echo "<pre>Cancelling order ID: " . $order_id . "</pre>"; // Debugging

            // Update the status in the database to "Cancelled"
            $stmt = $conn->prepare("UPDATE orders SET status = 'Cancelled' WHERE order_id = ?");
            $stmt->bind_param("i", $order_id); // Use "i" for integer order_id

            if ($stmt->execute()) {
                echo "<pre>Order status updated successfully for order ID: " . $order_id . "</pre>"; // Debugging
                // Remove the order from the reserved_orders array
                unset($_SESSION['reserved_orders'][$orderIndex]);
                // Reindex the reserved_orders array
                $_SESSION['reserved_orders'] = array_values($_SESSION['reserved_orders']);
                echo "<script>alert('Order has been cancelled!');</script>";
            } else {
                echo "<pre>Failed to update order status in database: " . $stmt->error . "</pre>"; // Debugging
            }
            $stmt->close();
        } else {
            echo "<pre>Invalid order or no reserved orders found.</pre>"; // Debugging
            echo "<script>alert('Invalid order or no reserved orders found.');</script>";
        }
    }
}

// Fetch confirmed and cancelled orders from the database
$confirmed_orders = [];
$cancelled_orders = [];

// Fetch confirmed orders
$stmt = $conn->prepare("SELECT order_id, service, appointment_date, appointment_time, status 
                        FROM orders 
                        WHERE user_id = ? AND status = 'Confirmed'");
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            // Decode the JSON-encoded services
            $row['services'] = json_decode($row['service'], true);
            $confirmed_orders[] = $row;
        }
    } else {
        error_log("Failed to fetch confirmed orders: " . $stmt->error);
    }
    $stmt->close();
} else {
    error_log("Failed to prepare confirmed orders query: " . $conn->error);
}


// Fetch cancelled orders
$stmt = $conn->prepare("SELECT order_id, service, appointment_date, appointment_time, status 
                        FROM orders 
                        WHERE user_id = ? AND status = 'Cancelled'");
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            // Decode the JSON-encoded services
            $row['services'] = json_decode($row['service'], true);
            $cancelled_orders[] = $row;
        }
    } else {
        error_log("Failed to fetch cancelled orders: " . $stmt->error);
    }
    $stmt->close();
} else {
    error_log("Failed to prepare cancelled orders query: " . $conn->error);
}


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
    body {
        background-color: #1a1a1a;
        color: white;
    }

    h2, h3 {
        text-align: center;
        color: #FFD700;
        font-weight: bold;
    }

    .card {
        background-color: #333;
        border: 2px solid #FFD700;
        text-align: center;
        color: white;
        border-radius: 10px;
        transition: transform 0.3s ease-in-out, box-shadow 0.3s ease-in-out;
    }

    /* Hover effect */
    .card:hover {
        transform: scale(1.05);
        box-shadow: 0 0 15px rgba(255, 215, 0, 0.7); /* Glowing gold shadow */
    }

    .btn-primary {
        background-color: #FFD700;
        color: black;
        font-weight: bold;
        border: none;
        transition: 0.3s;
        border-radius: 30px;
    }

    .btn-primary:hover {
        background-color: #FFC107;
        transform: scale(1.05);
    }

    select {
        background-color: #333;
        color: white;
        border: 1px solid #FFD700;
        padding: 5px;
        border-radius: 5px;
    }

        .cart-icon {
        background: #FFD700;
        color: black;
        padding: 15px;
        font-size: 24px;
        font-weight: bold;
        border-radius: 50%;
        cursor: pointer;
        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        position: fixed;
        bottom: 20px;
        right: 20px;
        z-index: 1000;
    }

    .cart-container {
        background: #333;
        border: 2px solid #FFD700;
        border-radius: 10px;
        padding: 15px;
        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        position: fixed;
        bottom: 80px;
        right: 20px;
        width: 300px;
        display: none;
        z-index: 1000;
    }

    .service-image {
        width: 100%; /* Make images fill the container */
        height: 180px; /* Set a fixed height */
        object-fit: cover; /* Crop the image to maintain aspect ratio */
        border-radius: 10px; /* Add rounded corners */
        margin-bottom: 10px; /* Space between image and text */
    }

    .table-dark {
        background-color: #444;
        color: white;
    }

    .btn-danger {
        background-color: red;
        border: none;
        transition: 0.3s;
    }

    .btn-danger:hover {
        background-color: darkred;
        transform: scale(1.05);
    }

    input[type="time"] {
    background-color: #333;
    color: white;
    border: 1px solid #FFD700;
    padding: 8px;
    border-radius: 5px;
    width: 100%;
    font-size: 16px;
    text-align: center;
    cursor: pointer;
    appearance: none; /* Removes default styles */
    position: relative;
}

/* Change color on focus */
input[type="time"]:focus {
    outline: none;
    border-color: #FFC107;
    box-shadow: 0 0 5px #FFD700;
}

/* Adjust styling when hovered */
input[type="time"]:hover {
    border-color: #FFC107;
    transform: scale(1.02);
}

/* Style for the clock icon */
input[type="time"]::-webkit-calendar-picker-indicator {
    filter: invert(1); /* Makes the icon white */
    cursor: pointer;
    transition: transform 0.3s ease-in-out;
}

/* Hover effect for the clock icon */
input[type="time"]:hover::-webkit-calendar-picker-indicator {
    transform: scale(1.2);
}

.receipt-icon {
    background: #FFD700;
    color: black;
    padding: 15px;
    font-size: 24px;
    font-weight: bold;
    border-radius: 50%;
    cursor: pointer;
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    position: fixed;
    bottom: 80px;
    right: 20px;
    z-index: 1000;
}

.receipt-container {
    background: #333;
    border: 2px solid #FFD700;
    border-radius: 10px;
    padding: 15px;
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    position: fixed;
    bottom: 140px;
    right: 20px;
    width: 300px;
    display: none;
    z-index: 1000;
}


    .scrollable-container {
        max-height: 400px; /* Set the maximum height for the scrollable area */
        overflow-y: auto; /* Enable vertical scrolling */
        border: 1px solid #ddd; /* Optional: Add a border for better visibility */
        
        max-width: 400px;
    }

    /* Optional: Style the scrollbar */
    .scrollable-container::-webkit-scrollbar {
        width: 10px; /* Width of the scrollbar */
    }

    .scrollable-container::-webkit-scrollbar-track {
        background: #f1f1f1; /* Color of the scrollbar track */
    }

    .scrollable-container::-webkit-scrollbar-thumb {
        background: #888; /* Color of the scrollbar thumb */
    }

    .scrollable-container::-webkit-scrollbar-thumb:hover {
        background: #555; /* Color of the scrollbar thumb on hover */
    }


</style>

</head>
<body>
    <?php include 'header.php'; ?>
    <div class="container mt-5">
        
        <h2>Welcome, <?php echo htmlspecialchars($user['fullname'] ?? 'Guest'); ?>!</h2>
        <h3>Available Services</h3>
        <div class="row">
            <?php foreach ($services as $service): ?>
                <div class="col-md-3">
                    <div class="card p-3 mb-3">
                        <img src="images/<?php echo htmlspecialchars($service['image']); ?>" alt="<?php echo htmlspecialchars($service['name']); ?>" class="service-image">
                        <h4><?php echo htmlspecialchars($service['name']); ?></h4>
                        <form method="post">
                            <input type="hidden" name="service_id" value="<?php echo $service['id']; ?>">
                            <select name="vehicle_type" required>
                                <option value="Small">Small - ₱<?php echo number_format($service['price_small'], 2); ?></option>
                                <option value="Medium">Medium - ₱<?php echo number_format($service['price_medium'], 2); ?></option>
                                <option value="Large">Large - ₱<?php echo number_format($service['price_large'], 2); ?></option>
                            </select>
                            <button type="submit" class="btn btn-primary mt-2">Add to Cart</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <h3>Detailing Services</h3>
        <div class="row">
            <?php foreach ($detailings as $detailing): ?>
                <div class="col-md-3">
                    <div class="card p-3 mb-3">
                        <img src="images/<?php echo htmlspecialchars($detailing['image']); ?>" alt="<?php echo htmlspecialchars($detailing['name']); ?>" class="service-image">
                        <h4><?php echo htmlspecialchars($detailing['name']); ?></h4>
                        <form method="post">
                            <input type="hidden" name="detailing_id" value="<?php echo $detailing['id']; ?>">
                            <select name="vehicle_type" required>
                                <option value="Small">Small - ₱<?php echo number_format($detailing['price_small'], 2); ?></option>
                                <option value="Medium">Medium - ₱<?php echo number_format($detailing['price_medium'], 2); ?></option>
                                <option value="Large">Large - ₱<?php echo number_format($detailing['price_large'], 2); ?></option>
                            </select>
                            <button type="submit" class="btn btn-primary mt-2">Add to Cart</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        

        <h3>Book an Appointment</h3>
        <div class="row">
            <?php foreach ($days as $day): ?>
                <?php $realDate = $dates[$day]; ?>
                <div class="col-md-4">
                    <div class="card p-3 mb-3">
                        <h4><?php echo $day . " - " . date("F j, Y", strtotime($realDate)); ?></h4>
                        <form method="post">
                            <input type="hidden" name="appointment_date" value="<?php echo $realDate; ?>">
                            
                            <label for="fixed_time_<?php echo $day; ?>">Select Time:</label>
                            <select name="fixed_time" id="fixed_time_<?php echo $day; ?>">
                                <option value="">-- Select Time --</option>
                                <?php for ($hour = 7; $hour <= 17; $hour++): ?>
                                    <option value="<?php echo sprintf('%02d:00', $hour); ?>">
                                        <?php echo sprintf('%02d:00', $hour); ?>
                                    </option>
                                <?php endfor; ?>
                            </select>

                            <br>

                            <label for="custom_time_<?php echo $day; ?>">Or enter custom time:</label>
                            <input type="time" name="custom_time" id="custom_time_<?php echo $day; ?>" min="07:00" max="17:00">

                            <button type="submit" class="btn btn-primary mt-2">Check & Add to Cart</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if (isset($error)): ?>
            <p style="color: red;"> <?php echo $error; ?> </p>
        <?php endif; ?>
        <br>
    </div>

    <div class="cart-icon" onclick="toggleCart()">🛒</div>
    <div class="cart-container" id="cartContainer">
        <h4>Shopping Cart</h4>
        <table class="table table-dark">
            <tbody>
                <?php foreach ($_SESSION['cart'] as $index => $item): ?>
                    <tr>
                        <td>
                            <?php 
                            echo htmlspecialchars($item['name']);
                            if (isset($item['date'], $item['time']) && !empty($item['time'])) {
                                echo " - " . htmlspecialchars($item['date']) . " at " . htmlspecialchars($item['time']);
                            }
                            ?>
                        </td>
                        <?php if (!isset($item['date'])): // Show price only for services/detailing ?>
                            <td>₱<?php echo number_format($item['price'], 2); ?></td>
                        <?php else: ?>
                            <td>-</td> <!-- No price for appointments -->
                        <?php endif; ?>
                        <td><a href="?remove=<?php echo $index; ?>" class="btn btn-danger btn-sm">X</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <h5>Total: ₱<?php echo number_format(array_sum(array_column(array_filter($_SESSION['cart'], function ($item) {
            return isset($item['price']);
        }), 'price')), 2); ?></h5>

        <!-- Reserve Order Button -->
        <form method="post">
            <button type="submit" name="reserve_order" class="btn btn-primary w-100">Reserve Order</button>
        </form>
    </div>

    <!-- Receipt Icon -->
    <div class="receipt-icon" onclick="toggleReceipt()">🧾</div>
        
    <!-- Receipt System Container -->
    <div class="receipt-container" id="receiptContainer">
        <h4>Receipt System</h4>
        <ul class="nav nav-tabs">
            <li class="nav-item">
                <a class="nav-link active" id="reserved-tab" onclick="showTab('reserved')">Reserved Orders</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="confirmed-tab" onclick="showTab('confirmed')">Confirmed Orders</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="cancelled-tab" onclick="showTab('cancelled')">Cancelled Orders</a>
            </li>
        </ul>
        
        <div class="tab-content">
        <!-- Reserved Orders Tab -->
        <div id="reserved" class="tab-pane active">
            <div class="scrollable-container">
                <table class="table table-dark">
                    <tbody>
                        <?php if (!empty($_SESSION['reserved_orders'])): ?>
                            <?php foreach ($_SESSION['reserved_orders'] as $index => $order): ?>
                                <?php
                                    // Ensure date and time are set
                                    $appointment_date = $order['appointment_date'] ?? 'N/A';
                                    $appointment_time = $order['appointment_time'] ?? '00:00:00';

                                    // Ensure services is an array
                                    $services = $order['services'] ?? [];
                                ?>
                                <tr>
                                    <td>
                                        <!-- Display Order ID and Appointment Details -->
                                        <strong>Order <?= htmlspecialchars($index + 1) ?></strong> - <?= htmlspecialchars($appointment_date) ?> @ <?= htmlspecialchars($appointment_time) ?>
                                        <ul>
                                            <?php if (!empty($services)): ?>
                                                <?php foreach ($services as $service): ?>
                                                    <?php if ($service['name'] !== 'Appointment'): ?>
                                                        <li>
                                                            <?= htmlspecialchars($service['name'] ?? 'Unknown Service') ?> 
                                                            (<?= htmlspecialchars($service['vehicle_type'] ?? 'N/A') ?>) - 
                                                            ₱<?= number_format($service['price'] ?? 0, 2) ?>
                                                        </li>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <li>No services found for this order.</li>
                                            <?php endif; ?>
                                        </ul>
                                    </td>
                                    <td>
                                        <!-- Single Form for Confirm and Cancel -->
                                        <form method="POST">
                                            <!-- Hidden Input for Order Index -->
                                            <input type="hidden" name="order_index" value="<?= htmlspecialchars($index) ?>">

                                            <!-- Buttons -->
                                            <button type="submit" class="btn btn-success btn-sm" name="confirm_order">Confirm</button>
                                            <button type="submit" class="btn btn-danger btn-sm" name="cancel_order" onclick="return confirm('Are you sure you want to cancel this order?')">Cancel</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3">No reserved orders yet.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <!-- Confirmed Orders Tab -->
        <div id="confirmed" class="tab-pane">
            <div class="scrollable-container">
                <table class="table table-dark">
                    <tbody>
                        <?php if (!empty($confirmed_orders)): ?>
                            <?php foreach ($confirmed_orders as $index => $order): ?>
                                <?php
                                    // Ensure date and time are set
                                    $appointment_date = $order['appointment_date'] ?? 'N/A';
                                    $appointment_time = $order['appointment_time'] ?? '00:00:00';

                                    // Ensure services is an array
                                    $services = $order['services'] ?? [];
                                ?>
                                <tr>
                                    <td>
                                        <!-- Display Order ID and Appointment Details -->
                                        <strong>Order <?= htmlspecialchars($order['order_id']) ?></strong> - <?= htmlspecialchars($appointment_date) ?> @ <?= htmlspecialchars($appointment_time) ?>
                                        <ul>
                                            <?php if (!empty($services)): ?>
                                                <?php foreach ($services as $service): ?>
                                                    <?php if ($service['name'] !== 'Appointment'): ?>
                                                        <li>
                                                            <?= htmlspecialchars($service['name'] ?? 'Unknown Service') ?> 
                                                            (<?= htmlspecialchars($service['vehicle_type'] ?? 'N/A') ?>) - 
                                                            ₱<?= number_format($service['price'] ?? 0, 2) ?>
                                                        </li>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <li>No services found for this order.</li>
                                            <?php endif; ?>
                                        </ul>
                                    </td>
                                    <td>
                                        <!-- Status Display -->
                                        <span class="badge bg-success"><?= htmlspecialchars($order['status']) ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3">No confirmed orders yet.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Cancelled Orders Tab -->
        <div id="cancelled" class="tab-pane">
            <div class="scrollable-container">
                <table class="table table-dark">
                    <tbody>
                        <?php if (!empty($cancelled_orders)): ?>
                            <?php foreach ($cancelled_orders as $index => $order): ?>
                                <?php
                                    // Ensure date and time are set
                                    $appointment_date = $order['appointment_date'] ?? 'N/A';
                                    $appointment_time = $order['appointment_time'] ?? '00:00:00';

                                    // Ensure services is an array
                                    $services = $order['services'] ?? [];
                                ?>
                                <tr>
                                    <td>
                                        <!-- Display Order ID and Appointment Details -->
                                        <strong>Order <?= htmlspecialchars($order['order_id']) ?></strong> - <?= htmlspecialchars($appointment_date) ?> @ <?= htmlspecialchars($appointment_time) ?>
                                        <ul>
                                            <?php if (!empty($services)): ?>
                                                <?php foreach ($services as $service): ?>
                                                    <?php if ($service['name'] !== 'Appointment'): ?>
                                                        <li>
                                                            <?= htmlspecialchars($service['name'] ?? 'Unknown Service') ?> 
                                                            (<?= htmlspecialchars($service['vehicle_type'] ?? 'N/A') ?>) - 
                                                            ₱<?= number_format($service['price'] ?? 0, 2) ?>
                                                        </li>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <li>No services found for this order.</li>
                                            <?php endif; ?>
                                        </ul>
                                    </td>
                                    <td>
                                        <!-- Status Display -->
                                        <span class="badge bg-danger"><?= htmlspecialchars($order['status']) ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3">No cancelled orders yet.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

    <script>
    // Toggle cart visibility
    function toggleCart() {
        var cart = document.getElementById("cartContainer");
        cart.style.display = (cart.style.display === "none" || cart.style.display === "") ? "block" : "none";
    }

    // Toggle receipt visibility
    function toggleReceipt() {
        var receipt = document.getElementById("receiptContainer");
        receipt.style.display = (receipt.style.display === "none" || receipt.style.display === "") ? "block" : "none";
    }

    // Handle tab navigation
    document.querySelectorAll('.nav-link').forEach(tab => {
        tab.addEventListener('click', function() {
            // Remove active class from all tabs
            document.querySelectorAll('.nav-link').forEach(t => t.classList.remove('active'));
            // Remove active class from all panes
            document.querySelectorAll('.tab-pane').forEach(pane => pane.classList.remove('active'));
            
            // Add active class to the clicked tab
            this.classList.add('active');
            // Show the corresponding pane
            document.getElementById(this.id.replace('-tab', '')).classList.add('active');
        });
    });

    // Disable one input when the other is selected
    document.querySelectorAll('select[name="fixed_time"]').forEach(select => {
        select.addEventListener('change', function() {
            const customInput = this.closest('form').querySelector('input[name="custom_time"]');
            if (this.value) {
                customInput.disabled = true;
            } else {
                customInput.disabled = false;
            }
        });
    });

    document.querySelectorAll('input[name="custom_time"]').forEach(input => {
        input.addEventListener('input', function() {
            const selectDropdown = this.closest('form').querySelector('select[name="fixed_time"]');
            if (this.value) {
                selectDropdown.disabled = true;
            } else {
                selectDropdown.disabled = false;
            }
        });
    });

    // Automatically show the reserved tab on page load
    window.onload = function() {
        document.getElementById('reserved-tab').classList.add('active');
        document.getElementById('reserved').classList.add('active');
    };
    </script>

    <?php include 'footer.php'; ?>
</body>
</html>
