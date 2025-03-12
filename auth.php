<?php
session_start();
require 'db_connection.php'; // Include database connection file

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'];

    if ($action == "signup") {
        // Handle user signup
        $fullname = trim($_POST['fullname']);
        $email = trim($_POST['email']);
        $contact_number = trim($_POST['contact_number']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

        if (empty($fullname) || empty($email) || empty($contact_number) || empty($_POST['password'])) {
            $_SESSION['error'] = "All fields are required!";
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

        // Insert new user into the database
        $stmt = $conn->prepare("INSERT INTO users (fullname, email, contact_number, password) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $fullname, $email, $contact_number, $password);

        if ($stmt->execute()) {
            $_SESSION['success'] = "Account created successfully! You can now login.";
            header("Location: login.php");
        } else {
            $_SESSION['error'] = "Something went wrong. Please try again.";
            header("Location: signup.php");
        }

        $stmt->close();
        $conn->close();
    } 

    elseif ($action == "login") {
        // Handle user login
        $email = trim($_POST['email']);
        $password = $_POST['password'];

        if (empty($email) || empty($password)) {
            $_SESSION['error'] = "Please enter both email and password!";
            header("Location: login.php");
            exit();
        }

        $stmt = $conn->prepare("SELECT id, fullname, password FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['fullname'] = $user['fullname'];
            header("Location: userdashboard.php"); // Redirect to user dashboard after login
        } else {
            $_SESSION['error'] = "Invalid email or password!";
            header("Location: login.php");
        }

        $stmt->close();
        $conn->close();
    }
} else {
    header("Location: login.php");
    exit();
}
?>
