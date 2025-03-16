<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); // Redirect to login if not logged in
    exit();
}

// Database connection
require 'db_connection.php'; // Include your database connection file

// Fetch user data
$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $fullname = $_POST['fullname'];
    $contact_number = $_POST['contact_number'];
    $new_password = $_POST['new_password'];

    // Update query
    if (!empty($new_password)) {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $sql = "UPDATE users SET fullname = ?, contact_number = ?, password = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssi", $fullname, $contact_number, $hashed_password, $user_id);
    } else {
        $sql = "UPDATE users SET fullname = ?, contact_number = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssi", $fullname, $contact_number, $user_id);
    }

    if ($stmt->execute()) {
        $_SESSION['profile_update_success'] = true; // Set success flag
        header("Location: profile.php"); // Redirect to avoid form resubmission
        exit();
    } else {
        $_SESSION['profile_update_error'] = "Error updating profile: " . $stmt->error;
    }
}

// Handle suggestion/concern submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_suggestion'])) {
    $suggestion = $_POST['suggestion'];

    // Insert suggestion into a separate table (e.g., suggestions)
    $sql = "INSERT INTO suggestions (user_id, suggestion) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $user_id, $suggestion);

    if ($stmt->execute()) {
        $_SESSION['suggestion_submit_success'] = true; // Set success flag
        header("Location: profile.php"); // Redirect to avoid form resubmission
        exit();
    } else {
        $_SESSION['suggestion_submit_error'] = "Error submitting feedback: " . $stmt->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Page</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body {
            background-color: #1a1a1a;
            color: white;
        }

        h1, h2 {
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

        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            color: #FFD700;
        }

        input[type="text"], input[type="password"], textarea {
            width: 100%;
            padding: 8px;
            box-sizing: border-box;
            background-color: #444;
            color: white;
            border: 1px solid #FFD700;
            border-radius: 5px;
        }

        textarea {
            resize: vertical;
        }

        .toast {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            background-color: #333;
            border: 2px solid #FFD700;
            color: white;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(255, 215, 0, 0.7);
            display: none;
        }

        .toast.show {
            display: block;
            animation: fadeIn 0.5s, fadeOut 0.5s 2.5s;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes fadeOut {
            from { opacity: 1; }
            to { opacity: 0; }
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    <div class="container mt-5">
        <h1>Profile Page</h1>

        <!-- Toast Notifications -->
        <div id="profileUpdateToast" class="toast">Profile updated successfully!</div>
        <div id="suggestionSubmitToast" class="toast">Thank you for your feedback!</div>

        <?php if (isset($_SESSION['profile_update_error'])): ?>
            <div class="message error"><?php echo $_SESSION['profile_update_error']; ?></div>
            <?php unset($_SESSION['profile_update_error']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['suggestion_submit_error'])): ?>
            <div class="message error"><?php echo $_SESSION['suggestion_submit_error']; ?></div>
            <?php unset($_SESSION['suggestion_submit_error']); ?>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-6">
                <div class="card p-4">
                    <h2>Update Profile</h2>
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="fullname">Full Name:</label>
                            <input type="text" id="fullname" name="fullname" value="<?php echo htmlspecialchars($user['fullname']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="contact_number">Contact Number:</label>
                            <input type="text" id="contact_number" name="contact_number" value="<?php echo htmlspecialchars($user['contact_number']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="new_password">New Password (leave blank to keep current):</label>
                            <input type="password" id="new_password" name="new_password">
                        </div>
                        <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
                    </form>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card p-4">
                    <h2>Submit Your Suggestion/Concern</h2>
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="suggestion">Your Suggestion/Concern:</label>
                            <textarea id="suggestion" name="suggestion" rows="5" required></textarea>
                        </div>
                        <button type="submit" name="submit_suggestion" class="btn btn-primary">Submit</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php include 'footer.php'; ?>

    <script>
        // Show toast notifications
        document.addEventListener('DOMContentLoaded', function () {
            <?php if (isset($_SESSION['profile_update_success'])): ?>
                showToast('profileUpdateToast');
                <?php unset($_SESSION['profile_update_success']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['suggestion_submit_success'])): ?>
                showToast('suggestionSubmitToast');
                <?php unset($_SESSION['suggestion_submit_success']); ?>
            <?php endif; ?>
        });

        function showToast(toastId) {
            const toast = document.getElementById(toastId);
            toast.classList.add('show');
            setTimeout(() => {
                toast.classList.remove('show');
            }, 3000);
        }
    </script>
</body>
</html>