<?php
// Start the session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Prevent caching
header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1
header("Pragma: no-cache"); // HTTP 1.0
header("Expires: 0"); // Proxies

// Include database connection
require 'db_connection.php';

// Redirect to login if user_id is not set or user is not an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    header("Location: login.php");
    exit();
}

// Fetch user details
$sql = "SELECT fullname, contact_number FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

// Initialize $user as an array with default values
$user = ['fullname' => 'Admin', 'contact_number' => ''];

// Check if the query was successful and fetch data
if ($result !== false) {
    $fetchedUser = $result->fetch_assoc();
    if (is_array($fetchedUser)) {
        $user = $fetchedUser; // Use fetched data if valid
    }
}

$stmt->close();

// Initialize order arrays to avoid undefined variable warnings
$reserved_orders = [];
$confirmed_orders = [];
$cancelled_orders = [];

// Fetch all orders
$reserved_result = $conn->query("SELECT * FROM orders WHERE status = 'Reserved'");
if ($reserved_result) {
    $reserved_orders = $reserved_result->fetch_all(MYSQLI_ASSOC);
}

$confirmed_result = $conn->query("SELECT * FROM orders WHERE status = 'Confirmed' ORDER BY appointment_date, appointment_time");
if ($confirmed_result) {
    $confirmed_orders = $confirmed_result->fetch_all(MYSQLI_ASSOC);
}

$cancelled_result = $conn->query("SELECT * FROM orders WHERE status = 'Cancelled'");
if ($cancelled_result) {
    $cancelled_orders = $cancelled_result->fetch_all(MYSQLI_ASSOC);
}

// Handle Order Confirmation
if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['confirm_order'])) {
    $order_id = $_POST['order_id'];
    $stmt = $conn->prepare("UPDATE orders SET status = 'Confirmed' WHERE order_id = ?");
    $stmt->bind_param("i", $order_id);
    if ($stmt->execute()) {
        echo "<script>alert('Order confirmed successfully!');</script>";
    } else {
        echo "<script>alert('Failed to confirm order.');</script>";
    }
    $stmt->close();
}

// Handle Order Cancellation
if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['cancel_order'])) {
    $order_id = $_POST['order_id'];
    $stmt = $conn->prepare("UPDATE orders SET status = 'Cancelled' WHERE order_id = ?");
    $stmt->bind_param("i", $order_id);
    if ($stmt->execute()) {
        echo "<script>alert('Order cancelled successfully!');</script>";
    } else {
        echo "<script>alert('Failed to cancel order.');</script>";
    }
    $stmt->close();
}

// Handle Pricing Update
if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['update_pricing'])) {
    $service_id = $_POST['service_id'];
    $price_small = $_POST['price_small'];
    $price_medium = $_POST['price_medium'];
    $price_large = $_POST['price_large'];

    // Handle file upload if a new image is provided
    $image = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/';
        $image_name = basename($_FILES['image']['name']);
        $image_path = $upload_dir . $image_name;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $image_path)) {
            $image = $image_path; // Save the new file path
        } else {
            echo "<script>alert('Failed to move uploaded file.');</script>";
            exit;
        }
    }

    // Prepare the SQL statement
    if ($image) {
        // Update pricing and image
        $sql = "UPDATE services SET price_small = ?, price_medium = ?, price_large = ?, image = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("dddssi", $price_small, $price_medium, $price_large, $image, $service_id);
    } else {
        // Update pricing only
        $sql = "UPDATE services SET price_small = ?, price_medium = ?, price_large = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("dddi", $price_small, $price_medium, $price_large, $service_id);
    }

    // Execute the statement
    if ($stmt->execute()) {
        echo "<script>alert('Pricing updated successfully!');</script>";
    } else {
        echo "<script>alert('Failed to update pricing: " . $stmt->error . "');</script>";
    }

    // Close the statement
    $stmt->close();
}

//Add new service and or detailings
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_new'])) {
    // Handle file upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'C:/xampp/htdocs/cabswebsite/carwash-main/images/'; // Absolute path to the images directory
        $image_name = basename($_FILES['image']['name']); // Get only the filename

        // Move the uploaded file to the desired directory
        if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $image_name)) {
            // File uploaded successfully, save only the filename in the database
            $image = $image_name;
        } else {
            echo "<script>alert('Failed to move uploaded file.');</script>";
            exit;
        }
    } else {
        echo "<script>alert('No image uploaded or upload error.');</script>";
        exit;
    }

    // Get other form data
    $type = $_POST['type'];
    $name = $_POST['name'];
    $price_small = $_POST['price_small'];
    $price_medium = $_POST['price_medium'];
    $price_large = $_POST['price_large'];

    // Validate and sanitize inputs
    $name = htmlspecialchars($name);
    $image = htmlspecialchars($image);

    // Determine the table based on the type
    if ($type === 'service') {
        $sql = "INSERT INTO services (name, price_small, price_medium, price_large, image) VALUES (?, ?, ?, ?, ?)";
    } else {
        $sql = "INSERT INTO detailings (name, price_small, price_medium, price_large, image) VALUES (?, ?, ?, ?, ?)";
    }

    // Prepare the SQL statement
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }

    // Bind parameters
    $stmt->bind_param("sddds", $name, $price_small, $price_medium, $price_large, $image);

    // Execute the statement
    if ($stmt->execute()) {
        echo "<script>alert('New entry added successfully!');</script>";
    } else {
        echo "<script>alert('Failed to add new entry: " . $stmt->error . "');</script>";
    }

    // Close the statement
    $stmt->close();
}

// Handle Delete Service
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_service'])) {
    $service_id = $_POST['service_id'];

    // Prepare and execute the delete query
    $stmt = $conn->prepare("DELETE FROM services WHERE id = ?");
    $stmt->bind_param("i", $service_id);

    if ($stmt->execute()) {
        echo "<script>alert('Service deleted successfully!');</script>";
        // Refresh the page to reflect changes
        echo "<script>window.location.href = 'admindashboard.php';</script>";
    } else {
        echo "<script>alert('Failed to delete service.');</script>";
    }
    $stmt->close();
}

// Handle Delete Detailing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_detailing'])) {
    $detailing_id = $_POST['detaling_id'];

    // Prepare and execute the delete query
    $stmt = $conn->prepare("DELETE FROM detailings WHERE id = ?");
    $stmt->bind_param("i", $detailing_id);

    if ($stmt->execute()) {
        echo "<script>alert('Detailing deleted successfully!');</script>";
        // Refresh the page to reflect changes
        echo "<script>window.location.href = 'admindashboard.php';</script>";
    } else {
        echo "<script>alert('Failed to delete detailing.');</script>";
    }
    $stmt->close();
}

// Handle Find Function
if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['find_order'])) {
    $searchInput = trim($_POST['searchInput']);
    $searchParts = explode(",", $searchInput); // Split input by comma
    $order_id = trim($searchParts[0]); // First part is order_id
    $fullname = trim($searchParts[1] ?? ''); // Second part is fullname

    // If only one part is provided, treat it as either order_id or fullname
    if (empty($fullname) && !empty($order_id)) {
        // Check if the input is numeric (order_id) or a string (fullname)
        if (is_numeric($order_id)) {
            // Treat as order_id
            $query = "SELECT orders.*, users.fullname, users.contact_number, users.email 
                      FROM orders 
                      JOIN users ON orders.user_id = users.id 
                      WHERE orders.order_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $order_id); // Bind order_id as integer
        } else {
            // Treat as fullname
            $fullname = "%$order_id%"; // Add wildcards for LIKE query
            $query = "SELECT orders.*, users.fullname, users.contact_number, users.email
                      FROM orders 
                      JOIN users ON orders.user_id = users.id 
                      WHERE users.fullname LIKE ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("s", $fullname); // Bind fullname as string
        }
        $stmt->execute();
        $search_result = $stmt->get_result();
        $found_orders = $search_result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    } else {
        // If both order_id and fullname are provided
        $fullname = "%$fullname%"; // Add wildcards for LIKE query
        $query = "SELECT orders.*, users.fullname, users.contact_number, users.email 
                  FROM orders 
                  JOIN users ON orders.user_id = users.id 
                  WHERE orders.order_id = ? AND users.fullname LIKE ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("is", $order_id, $fullname); // Bind order_id and fullname
        $stmt->execute();
        $search_result = $stmt->get_result();
        $found_orders = $search_result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
}

// Fetch services and detailings for pricing management
$services = $conn->query("SELECT * FROM services")->fetch_all(MYSQLI_ASSOC);
$detailings = $conn->query("SELECT * FROM detailings")->fetch_all(MYSQLI_ASSOC);

// Handle Clear Search
if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['clear_search'])) {
    unset($found_orders); // Clear search results
}

// Handle Profile Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $fullname = $_POST['fullname'];
    $contact_number = $_POST['contact_number'];
    $new_password = $_POST['new_password'];

    // Update query
    if (!empty($new_password)) {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $sql = "UPDATE users SET fullname = ?, contact_number = ?, password = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssi", $fullname, $contact_number, $hashed_password, $_SESSION['user_id']);
    } else {
        $sql = "UPDATE users SET fullname = ?, contact_number = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssi", $fullname, $contact_number, $_SESSION['user_id']);
    }

    if ($stmt->execute()) {
        echo "<script>alert('Profile updated successfully!');</script>";
    } else {
        echo "<script>alert('Failed to update profile.');</script>";
    }
    $stmt->close();
}

// Handle User Deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $user_id = $_POST['user_id'];

    // Prevent admin from deleting themselves
    if ($user_id == $_SESSION['user_id']) {
        echo "<script>alert('You cannot delete your own account.');</script>";
    } else {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);

        if ($stmt->execute()) {
            echo "<script>alert('User deleted successfully!');</script>";
            echo "<script>window.location.href = 'admindashboard.php';</script>";
        } else {
            echo "<script>alert('Failed to delete user.');</script>";
        }
        $stmt->close();
    }
}

// Handle Role Change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_role'])) {
    $user_id = $_POST['user_id'];
    $new_role = $_POST['new_role'];

    // Prevent admin from changing their own role
    if ($user_id == $_SESSION['user_id']) {
        echo "<script>alert('You cannot change your own role.');</script>";
    } else {
        $stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
        $stmt->bind_param("si", $new_role, $user_id);

        if ($stmt->execute()) {
            echo "<script>alert('User role updated successfully!');</script>";
            echo "<script>window.location.href = 'admindashboard.php';</script>";
        } else {
            echo "<script>alert('Failed to update user role.');</script>";
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        /* General Styling */
        body {
            background-color: #1a1a1a; /* Dark gray */
            color: #e0e0e0; /* Light gray */
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
        }

        .page-title {
            color: #FFD700; /* Gold */
            text-align: center;
            margin-bottom: 30px;
            font-size: 2.5rem;
            font-weight: bold;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }

        /* Button Styling */
        .btn-gold {
            background: linear-gradient(45deg, #FFD700, #C5A600); /* Gold gradient */
            color: #000; /* Black text */
            border: none;
            padding: 12px 24px;
            font-size: 16px;
            cursor: pointer;
            border-radius: 8px;
            transition: transform 0.2s ease, box-shadow 0.3s ease;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
        }

        .btn-gold:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.3);
        }

        .btn-gold:active {
            transform: translateY(0);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
        }

        /* Form Styling */
        .form-container {
            background-color: #2a2a2a; /* Dark gray */
            padding: 25px;
            border-radius: 12px;
            margin-top: 20px;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #FFD700; /* Gold */
            font-weight: bold;
        }

        .gold-input {
            width: 100%;
            padding: 10px;
            border: 1px solid #FFD700; /* Gold */
            border-radius: 8px;
            background-color: #1a1a1a; /* Dark gray */
            color: #FFD700; /* Gold */
            font-size: 16px;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }

        .gold-input:focus {
            outline: none;
            border-color: #C5A600; /* Darker gold */
            box-shadow: 0 0 8px rgba(255, 215, 0, 0.5);
        }

        /* Card Styling */
        .card {
            background-color: #2a2a2a; /* Dark gray */
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            border: 1px solid #444; /* Light gray border */
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.4);
        }

        .card h3 {
            color: #FFD700; /* Gold */
            margin-top: 0;
            font-size: 1.5rem;
            font-weight: bold;
        }

        /* Pricing Table */
        .pricing-table {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        /* Tabs */
        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        .tab {
            padding: 12px 24px;
            cursor: pointer;
            background-color: #444; /* Light gray */
            color: #e0e0e0; /* Light gray text */
            border-radius: 8px;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }

        .tab.active {
            background-color: #FFD700; /* Gold */
            color: #000; /* Black text */
            font-weight: bold;
        }

        .tab:hover {
            background-color: #C5A600; /* Darker gold */
            color: #000; /* Black text */
            transform: translateY(-2px);
        }

        /* Table Styling */
        /* Table Styling */
        .table {
            width: 100%;
            border-collapse: collapse;
            background-color: #2a2a2a; /* Dark gray background */
            color: #e0e0e0; /* Light gray text */
            border: 1px solid #444; /* Light gray border */
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }

        .table th, .table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #444; /* Light gray border */
        }

        .table th {
            background-color: #1a1a1a; /* Darker gray for header */
            color: #FFD700; /* Gold text for header */
            font-weight: bold;
        }

        .table tr:hover {
            background-color: #333; /* Slightly lighter gray on hover */
        }

        /* Scrollable Tables */
        .table-container {
            max-height: 600px;
            overflow-y: auto;
            border: 1px solid #444; /* Light gray border */
            border-radius: 12px;
            margin-bottom: 20px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }

        /* Badges */
        .badge {
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: bold;
        }

        .badge.reserved {
            background-color: #FFD700; /* Gold */
            color: #000; /* Black text */
        }

        .badge.confirmed {
            background-color: #28a745; /* Green */
            color: white;
        }

        .badge.cancelled {
            background-color: #dc3545; /* Red */
            color: white;
        }

        /* Sidebar */
        .sidebar {
            width: 250px;
            background-color: #1a1a1a; /* Dark gray */
            color: #e0e0e0; /* Light gray */
            padding: 20px 0;
            border-right: 1px solid #444; /* Light gray border */
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }

        .sidebar .logo {
            padding: 0 20px 20px;
            font-size: 24px;
            font-weight: bold;
            border-bottom: 1px solid #444; /* Light gray border */
            margin-bottom: 20px;
            color: #FFD700; /* Gold */
        }

        .sidebar .menu li {
            padding: 12px 20px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .sidebar .menu li:hover, .sidebar .menu li.active {
            background-color: #FFD700; /* Gold */
            color: #000; /* Black text */
        }

        /* Main Content */
        .content {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
        }

        /* Search Bar */
        .search-bar {
            max-width: 500px;
            width: 100%;
            margin-bottom: 20px;
        }

        .search-bar input {
            background-color: #2a2a2a; /* Dark gray */
            color: #e0e0e0; /* Light gray */
            border: 1px solid #FFD700; /* Gold */
            border-radius: 8px;
            padding: 10px;
            width: 70%;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }

        .search-bar input:focus {
            border-color: #C5A600; /* Darker gold */
            box-shadow: 0 0 8px rgba(255, 215, 0, 0.5);
        }

        .search-bar button {
            background-color: #FFD700; /* Gold */
            color: #000; /* Black text */
            border: none;
            border-radius: 8px;
            padding: 10px 20px;
            margin-left: 10px;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }

        .search-bar button:hover {
            background-color: #C5A600; /* Darker gold */
            transform: translateY(-2px);
        }

        /* Pricing Buttons */
        .pricing-table .btn-gold {
            background: linear-gradient(45deg, #FFD700, #C5A600); /* Gold gradient */
            color: #000; /* Black text */
            border: none;
            padding: 12px 24px;
            font-size: 16px;
            cursor: pointer;
            border-radius: 8px;
            transition: transform 0.2s ease, box-shadow 0.3s ease;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
        }

        .pricing-table .btn-gold:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.3);
        }

        .pricing-table .btn-gold:active {
            transform: translateY(0);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
        }

        /* Button Styling with Transitions */
        .btn-gold {
            background: linear-gradient(45deg, #FFD700, #C5A600); /* Gold gradient */
            color: #000; /* Black text */
            border: none;
            padding: 12px 24px;
            font-size: 16px;
            cursor: pointer;
            border-radius: 8px;
            transition: background 0.3s ease, transform 0.2s ease, box-shadow 0.3s ease;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
        }

        .btn-gold:hover {
            background: linear-gradient(45deg, #C5A600, #FFD700); /* Reverse gold gradient */
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.3);
        }

        .btn-gold:active {
            transform: translateY(0);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
        }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3 sidebar">
            <div class="logo">Welcome, Admin <?php echo htmlspecialchars($user['fullname'] ?? 'Guest'); ?>!</div>
            <ul class="menu">
                <li onclick="showSection('orders')">Order Management</li>
                <li onclick="showSection('pricing')">Pricing Management</li>
                <li onclick="showSection('userm')">User Management</li>
                <li onclick="logout()">Logout</li>
            </ul>
        </div>
        
        <!-- Main Content -->
        <div class="col-md-9 content">
            <div class="header">
                <h1>Admin Dashboard</h1>
            </div>

            <!-- Search Bar -->
            <div class="search-bar">
                <form method="POST" action="" class="d-flex">
                    <input type="text" id="searchInput" name="searchInput" placeholder="Search by ID or name" class="form-control me-2">
                    <button type="submit" name="find_order" class="btn btn-primary">Find</button>
                    <button type="submit" name="clear_search" class="btn btn-secondary">Clear</button>
                </form>
            </div>

            <!-- Display Found Orders -->
            <?php if (isset($found_orders)): ?>
                <div class="found-orders">
                    <h3>Search Results</h3>
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>User ID</th>
                                    <th>Full Name</th>
                                    <th>Contact Number</th>
                                    <th>email</th>
                                    <th>Service</th>
                                    <th>Appointment Date</th>
                                    <th>Appointment Time</th>
                                    <th>Status</th>
                                    <?php if (in_array('Reserved', array_column($found_orders, 'status'))) : ?>
                                        <th>Actions</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($found_orders as $order): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($order['order_id']) ?></td>
                                        <td><?= htmlspecialchars($order['user_id']) ?></td>
                                        <td><?= htmlspecialchars($order['fullname']) ?></td>
                                        <td><?= htmlspecialchars($order['contact_number']) ?></td>
                                        <td><?= htmlspecialchars($order['email']) ?></td>
                                        <td><?= htmlspecialchars($order['service_name']) ?></td>
                                        <td><?= htmlspecialchars($order['appointment_date']) ?></td>
                                        <td><?= htmlspecialchars($order['appointment_time']) ?></td>
                                        <td><span class="badge <?= strtolower($order['status']) ?>"><?= htmlspecialchars($order['status']) ?></span></td>
                                        <?php if ($order['status'] === 'Reserved'): ?>
                                            <td>
                                                <form method="POST" style="display:inline;">
                                                    <input type="hidden" name="order_id" value="<?= htmlspecialchars($order['order_id']) ?>">
                                                    <button type="submit" name="confirm_order" class="btn btn-success btn-sm">Confirm</button>
                                                </form>
                                                <form method="POST" style="display:inline;">
                                                    <input type="hidden" name="order_id" value="<?= htmlspecialchars($order['order_id']) ?>">
                                                    <button type="submit" name="cancel_order" class="btn btn-danger btn-sm">Cancel</button>
                                                </form>
                                            </td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Orders Section -->
            <div id="orders" class="section active">
                <h2 class="page-title">Order Management</h2>
                
                <div class="tabs">
                    <div class="tab active" onclick="showTab('reserved')">Reserved Orders</div>
                    <div class="tab" onclick="showTab('confirmed')">Confirmed Orders</div>
                    <div class="tab" onclick="showTab('cancelled')">Cancelled Orders</div>
                </div>
                
                <!-- Reserved Orders Tab -->
                <div class="table-container">
                    <div id="reserved" class="tab-content active">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>User ID</th>
                                    <th>Service</th>
                                    <th>Requested Date</th>
                                    <th>Requested Time</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($reserved_orders as $order): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($order['order_id']) ?></td>
                                        <td><?= htmlspecialchars($order['user_id']) ?></td>
                                        <td><?= htmlspecialchars($order['service_name']) ?></td>
                                        <td><?= htmlspecialchars($order['appointment_date']) ?></td>
                                        <td><?= htmlspecialchars($order['appointment_time']) ?></td>
                                        <td><span class="badge reserved">Reserved</span></td>
                                        <td>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="order_id" value="<?= htmlspecialchars($order['order_id']) ?>">
                                                <button type="submit" name="confirm_order" class="btn btn-success btn-sm">Confirm</button>
                                            </form>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="order_id" value="<?= htmlspecialchars($order['order_id']) ?>">
                                                <button type="submit" name="cancel_order" class="btn btn-danger btn-sm">Cancel</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Confirmed Orders Tab -->
                <div id="confirmed" class="tab-content">
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>User ID</th>
                                    <th>Service</th>
                                    <th>Appointment Date</th>
                                    <th>Appointment Time</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($confirmed_orders as $order): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($order['order_id']) ?></td>
                                        <td><?= htmlspecialchars($order['user_id']) ?></td>
                                        <td><?= htmlspecialchars($order['service_name']) ?></td>
                                        <td><?= htmlspecialchars($order['appointment_date']) ?></td>
                                        <td><?= htmlspecialchars($order['appointment_time']) ?></td>
                                        <td><span class="badge confirmed">Confirmed</span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Cancelled Orders Tab -->
                <div id="cancelled" class="tab-content">
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>User ID</th>
                                    <th>Service</th>
                                    <th>Appointment Date</th>
                                    <th>Appointment Time</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cancelled_orders as $order): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($order['order_id']) ?></td>
                                        <td><?= htmlspecialchars($order['user_id']) ?></td>
                                        <td><?= htmlspecialchars($order['service_name']) ?></td>
                                        <td><?= htmlspecialchars($order['appointment_date']) ?></td>
                                        <td><?= htmlspecialchars($order['appointment_time']) ?></td>
                                        <td><span class="badge cancelled">Cancelled</span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Pricing Management Section -->
            <div id="pricing" class="section">
                <h2 class="page-title">Pricing Management</h2>
                
                <!-- Button to toggle the form for adding a new service or detailing -->
                <button id="addNewButton" class="btn btn-gold">Add New Service/Detailing</button>

                <!-- Form for adding a new service or detailing (initially hidden) -->
                <div id="addNewForm" class="form-container" style="display: none;">
                    <form method="POST" action="" enctype="multipart/form-data">
                        <div class="form-group">
                            <label>Type:</label>
                            <select name="type" class="gold-input" required>
                                <option value="service">Service</option>
                                <option value="detailing">Detailing</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Name:</label>
                            <input type="text" name="name" class="gold-input" required>
                        </div>
                        <div class="form-group">
                            <label>Image:</label>
                            <input type="file" name="image" class="gold-input" required>
                        </div>
                        <div class="form-group">
                            <label>Small Vehicle Price:</label>
                            <input type="number" name="price_small" class="gold-input" step="0.01" required>
                        </div>
                        <div class="form-group">
                            <label>Medium Vehicle Price:</label>
                            <input type="number" name="price_medium" class="gold-input" step="0.01" required>
                        </div>
                        <div class="form-group">
                            <label>Large Vehicle Price:</label>
                            <input type="number" name="price_large" class="gold-input" step="0.01" required>
                        </div>
                        <button type="submit" name="add_new" class="btn btn-gold">Add</button>
                    </form>
                </div>

                <!-- Existing pricing table -->
                <div class="pricing-table">
                    <?php foreach ($services as $service): ?>
                        <div class="card">
                            <h3><?= htmlspecialchars($service['name']) ?></h3>
                            <!-- Display the image if it exists -->
                            <?php if (!empty($service['image'])): ?>
                                <img src="images/<?= htmlspecialchars($service['image']) ?>" alt="<?= htmlspecialchars($service['name']) ?>">
                            <?php else: ?>
                                <p>No image available</p>
                            <?php endif; ?>
                            <form method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="service_id" value="<?= htmlspecialchars($service['id']) ?>">
                                <div class="form-group">
                                    <label>Image:</label>
                                    <input type="file" name="image" class="gold-input">
                                </div>
                                <div class="form-group">
                                    <label>Small Vehicle Price:</label>
                                    <input type="number" name="price_small" value="<?= htmlspecialchars($service['price_small']) ?>" class="gold-input" step="0.01" required>
                                </div>
                                <div class="form-group">
                                    <label>Medium Vehicle Price:</label>
                                    <input type="number" name="price_medium" value="<?= htmlspecialchars($service['price_medium']) ?>" class="gold-input" step="0.01" required>
                                </div>
                                <div class="form-group">
                                    <label>Large Vehicle Price:</label>
                                    <input type="number" name="price_large" value="<?= htmlspecialchars($service['price_large']) ?>" class="gold-input" step="0.01" required>
                                </div>
                                <button type="submit" name="update_pricing" class="btn btn-gold">Update Pricing</button>
                                <button type="submit" name="delete_service" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this service?');">Delete Service</button>
                            </form>
                        </div>
                    <?php endforeach; ?>

                    <?php foreach ($detailings as $detailing): ?>
                        <div class="card">
                            <h3><?= htmlspecialchars($detailing['name']) ?></h3>
                            <!-- Display the image if it exists -->
                            <?php if (!empty($detailing['image'])): ?>
                                <img src="images/<?= htmlspecialchars($detailing['image']) ?>" alt="<?= htmlspecialchars($detailing['name']) ?>">
                            <?php else: ?>
                                <p>No image available</p>
                            <?php endif; ?>
                            <form method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="detaling_id" value="<?= htmlspecialchars($detailing['id']) ?>">
                                <div class="form-group">
                                    <label>Image:</label>
                                    <input type="file" name="image" class="gold-input">
                                </div>
                                <div class="form-group">
                                    <label>Small Vehicle Price:</label>
                                    <input type="number" name="price_small" value="<?= htmlspecialchars($detailing['price_small']) ?>" class="gold-input" step="0.01" required>
                                </div>
                                <div class="form-group">
                                    <label>Medium Vehicle Price:</label>
                                    <input type="number" name="price_medium" value="<?= htmlspecialchars($detailing['price_medium']) ?>" class="gold-input" step="0.01" required>
                                </div>
                                <div class="form-group">
                                    <label>Large Vehicle Price:</label>
                                    <input type="number" name="price_large" value="<?= htmlspecialchars($detailing['price_large']) ?>" class="gold-input" step="0.01" required>
                                </div>
                                <button type="submit" name="update_pricing" class="btn btn-gold">Update Pricing</button>
                                <button type="submit" name="delete_detailing" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this detailing?');">Delete Detailing</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- User Management Section -->
            <!-- User Management Section -->
            <div id="userm" class="section">
                <h2 class="page-title">User Management</h2>

                <!-- Admin Profile Update Form -->
                <div class="card">
                    <h3>Edit Your Profile</h3>
                    <form method="POST" action="">
                        <div class="form-group">
                            <label>Full Name:</label>
                            <input type="text" name="fullname" value="<?= htmlspecialchars($user['fullname']) ?>" class="gold-input" required>
                        </div>
                        <div class="form-group">
                            <label>Contact Number:</label>
                            <input type="text" name="contact_number" value="<?= htmlspecialchars($user['contact_number']) ?>" class="gold-input" required>
                        </div>
                        <div class="form-group">
                            <label>New Password (leave blank to keep current):</label>
                            <input type="password" name="new_password" class="gold-input">
                        </div>
                        <button type="submit" name="update_profile" class="btn btn-gold">Update Profile</button>
                    </form>
                </div>

                <!-- User List -->
                <div class="table-container">
                    <h3>Manage Users</h3>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Full Name</th>
                                <th>Email</th>
                                <th>Contact Number</th>
                                <th>Role</th>
                                <th>Change Role</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Fetch all users from the database
                            $users = $conn->query("SELECT * FROM users");
                            while ($user_row = $users->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($user_row['id']) ?></td>
                                    <td><?= htmlspecialchars($user_row['fullname']) ?></td>
                                    <td><?= htmlspecialchars($user_row['email']) ?></td>
                                    <td><?= htmlspecialchars($user_row['contact_number']) ?></td>
                                    <td><?= htmlspecialchars($user_row['role']) ?></td>
                                    <td>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="user_id" value="<?= $user_row['id'] ?>">
                                            <select name="new_role" class="gold-input" onchange="this.form.submit()">
                                                <option value="admin" <?= $user_row['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                                                <option value="user" <?= $user_row['role'] === 'user' ? 'selected' : '' ?>>User</option>
                                            </select>
                                        </form>
                                    </td>
                                    <td>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="user_id" value="<?= $user_row['id'] ?>">
                                            <button type="submit" name="delete_user" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this user?');">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Suggestions Section -->
                <div class="table-container">
                    <h3>User Suggestions</h3>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>User ID</th>
                                <th>Full Name</th>
                                <th>Email</th>
                                <th>Suggestion</th>
                                <th>Submitted At</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Fetch all suggestions from the database
                            $suggestions = $conn->query("SELECT suggestions.*, users.fullname, users.email 
                                                        FROM suggestions 
                                                        JOIN users ON suggestions.user_id = users.id");
                            while ($suggestion = $suggestions->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($suggestion['id']) ?></td>
                                    <td><?= htmlspecialchars($suggestion['user_id']) ?></td>
                                    <td><?= htmlspecialchars($suggestion['fullname']) ?></td>
                                    <td><?= htmlspecialchars($suggestion['email']) ?></td>
                                    <td><?= htmlspecialchars($suggestion['suggestion']) ?></td>
                                    <td><?= htmlspecialchars($suggestion['submitted_at']) ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

    <script>
        // Toggle Add New Form
        document.getElementById('addNewButton').addEventListener('click', function() {
            var form = document.getElementById('addNewForm');
            form.style.display = form.style.display === 'none' ? 'block' : 'none';
        });

        // Show Sections
        function showSection(sectionId) {
            document.querySelectorAll('.section').forEach(section => {
                section.style.display = 'none';
            });
            document.getElementById(sectionId).style.display = 'block';
        }

        // Show Tabs
        function showTab(tabId) {
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.style.display = 'none';
            });
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });
            document.getElementById(tabId).style.display = 'block';
            document.querySelector(`[onclick="showTab('${tabId}')"]`).classList.add('active');
        }

        // Logout Function
        function logout() {
            window.location.href = 'logout.php';
        }

        // Show orders section by default
        showSection('orders');
        showTab('reserved');
    </script>
</body>
</html> 