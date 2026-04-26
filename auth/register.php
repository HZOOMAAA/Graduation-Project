<?php
session_start();
require '../includes/connection.php';

$error = '';
$success = '';

if (isset($_POST['register'])) {
    // 1. Sanitize Inputs
    $fullname = mysqli_real_escape_string($connect, trim($_POST['fullname']));
    $email    = mysqli_real_escape_string($connect, trim($_POST['email']));
    $phone    = mysqli_real_escape_string($connect, trim($_POST['phone']));
    $address  = mysqli_real_escape_string($connect, trim($_POST['address']));
    $password = $_POST['password'];
    $role     = 'customer';

    // 2. Validations (Step-by-Step)
    if (empty($fullname) || empty($email) || empty($password) || empty($phone) || empty($address)) {
        $error = "All fields are required!";
    } elseif (strlen($fullname) < 3) {
        $error = "Full name must be at least 3 characters long!";
    } elseif (strlen($phone) !== 11) {
        $error = "Phone number must be exactly 11 digits long!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) { // Check if email is actually an email
        $error = "Please enter a valid email address!";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long!";
    }

    // 3. Database Operations (Only if no validation errors occurred)
    if (empty($error)) {
        // Check if email exists
        $check_email = mysqli_query($connect, "SELECT * FROM users WHERE email = '$email'");
        
        if (mysqli_num_rows($check_email) > 0) {
            $error = "This email is already registered!";
        } else {
            // Securely Hash Password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT); 
            
            // Insert User
            $insert_query = "INSERT INTO users (name, email, password, role, phone, address) 
                            VALUES ('$fullname', '$email', '$hashed_password', '$role', '$phone', '$address')";
            
            if (mysqli_query($connect, $insert_query)) {
                // Success! Redirect to login
                header('location: login.php?msg=registered');
                exit();
            } else {
                $error = "Registration failed: " . mysqli_error($connect);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Smart Insurance</title>
    <link rel="stylesheet" href="../assets/css/register.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>

</head>
<body>
    <!-- to show error -->
        <?php if ($error): ?>
            <div class="error-popup" id="errorPopup">
                <div class="error-content">
                    <i class='bx bx-error-circle'></i>
                    <span><?php echo htmlspecialchars($error); ?></span>
                    <button onclick="closePopup()">&times;</button>
                </div>
            </div>
        <?php endif; ?>

        
    <div class="auth-container">
        <h1>Register</h1>

        <!-- form -->

        <form  method="post" novalidate>
            <label for="fullname">Full Name</label>
            <div class="input-group">
                <i class='bx bx-user'></i>
            <input type="text" id="fullname" name="fullname" required>
            </div>
            <label for="email">Email</label>
            <div class="input-group">
                <i class='bx bx-envelope'></i>
            <input type="email" id="email" name="email" required>
            </div>
            <label for="password">Password</label>
            <div class="input-group">
                <i class='bx bx-lock-alt'></i>
            <input type="password" id="password" name="password" required>
            </div>
            <label for="phone">Phone</label>
            <div class="input-group">
                <i class='bx bx-phone'></i>
            <input type="text" id="phone" name="phone" required>
            </div>
            <label for="address">Address</label>
            <div class="input-group">
                <i class='bx bx-map'></i>
            <input type="text" id="address" name="address" required>
            </div>
            <button type="submit" name="register">Register</button>
        </form>
        <p>Already have an account? <a href="login.php">Login here</a></p>
    </div>
</body>
</html>
