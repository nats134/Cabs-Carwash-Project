<?php
session_start(); // Start the session
require 'db_connection.php'; // Include the MySQLi database connection file

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action']; // Determine the action (signup or login)

    if ($action == "signup") {
        // Handle user signup
        $fullname = trim($_POST['fullname']);
        $email = trim($_POST['email']);
        $contact_number = trim($_POST['contact_number']);
        $password = $_POST['password'];

        // Validate inputs
        if (empty($fullname) || empty($email) || empty($contact_number) || empty($password)) {
            $_SESSION['error'] = "All fields are required!";
            header("Location: signup.php");
            exit();
        }

        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = "Invalid email format!";
            header("Location: signup.php");
            exit();
        }

        // Check if email already exists
        $checkEmail = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $checkEmail->bind_param("s", $email);
        $checkEmail->execute();
        $result = $checkEmail->get_result();

        if ($result->num_rows > 0) {
            $_SESSION['error'] = "Email already in use!";
            header("Location: signup.php");
            exit();
        }

        // Hash the password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Insert new user into the database
        $stmt = $conn->prepare("INSERT INTO users (fullname, email, contact_number, password) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $fullname, $email, $contact_number, $hashedPassword);

        if ($stmt->execute()) {
            $_SESSION['success'] = "Account created successfully! You can now login.";
            header("Location: login.php");
        } else {
            $_SESSION['error'] = "Something went wrong. Please try again.";
            header("Location: signup.php");
        }

        $stmt->close();
    } 

    elseif ($action == "login") {
        // Handle user login
        $email = trim($_POST['email']);
        $password = $_POST['password'];

        // Validate inputs
        if (empty($email) || empty($password)) {
            $_SESSION['error'] = "Please enter both email and password!";
            header("Location: login.php");
            exit();
        }

        // Fetch user from the database
        $stmt = $conn->prepare("SELECT id, fullname, password, role FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        // Verify password and set session
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['fullname'] = $user['fullname'];
            $_SESSION['role'] = $user['role']; // Store user role in session

            // Redirect based on role
            if ($user['role'] == 'admin') {
                header("Location: admindashboard.php"); // Redirect to admin dashboard
            } else {
                header("Location: userdashboard.php"); // Redirect to user dashboard
            }
        } else {
            $_SESSION['error'] = "Invalid email or password!";
            header("Location: login.php");
        }

        $stmt->close();
    }
} else {
    // Redirect to login if accessed directly
    header("Location: login.php");
    exit();
}

// Close the database connection
$conn->close();
?>