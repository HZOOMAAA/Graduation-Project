<?php
require '../includes/connection.php';

$error = '';
$success = '';


if (isset($_POST['register'])) {
    $fullname = mysqli_real_escape_string($connect, $_POST['fullname']);
    $email = mysqli_real_escape_string($connect, $_POST['email']);
    $password = $_POST['password'];
    $role = 'customer'; // Force all public registrations to be customers

    // Check if email exists
    $check_email = mysqli_query($connect, "SELECT * FROM users WHERE email = '$email'");
    if (mysqli_num_rows($check_email) > 0) {
        $error = "Email already exists!";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);     
        
        
        $insert = mysqli_query($connect, "INSERT INTO users (name, email, password, role) VALUES ('$fullname', '$email', '$hashed_password', '$role')");
        
        if ($insert) {
            $success = "Registration successful! You can now login.";
        } else {
            $error = "Registration failed! " . mysqli_error($connect);
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
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="auth-container">
        <h1>Register</h1>
        <?php if ($error): ?>
            <div style="color: red; margin-bottom: 10px;"><?php echo $error; ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div style="color: green; margin-bottom: 10px;"><?php echo $success; ?></div>
        <?php endif; ?>
        <form action="register.php" method="post">
            <label for="fullname">Full Name</label>
            <input type="text" id="fullname" name="fullname" required>
            <label for="email">Email</label>
            <input type="email" id="email" name="email" required>
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
            <button type="submit" name="register">Register</button>
        </form>
        <p>Already have an account? <a href="login.php">Login here</a></p>
    </div>
</body>
</html>
