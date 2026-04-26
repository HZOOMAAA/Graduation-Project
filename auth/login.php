<?php
session_start(); // Essential for login sessions
require '../includes/connection.php';

$error = '';

if (isset($_POST['login'])) {
    // 1. Get and Sanitize data
    $email = mysqli_real_escape_string($connect, trim($_POST['email']));
    $password = $_POST['password'];

    // 2. Immediate Validation: Check if empty first
    if (empty($email) || empty($password)) {
        $error = "All fields are required!";
    } else {
        // 3. Query Database
        $query = mysqli_query($connect, "SELECT * FROM users WHERE email = '$email'");

        if (mysqli_num_rows($query) > 0) {
            $row = mysqli_fetch_assoc($query);
            
            // 4. Verify Password
            if (password_verify($password, $row['password'])) {
                $_SESSION['user_id'] = $row['user_id'];
                $_SESSION['role'] = $row['role'];
                $_SESSION['name'] = $row['name'];
                
                // 5. Redirect based on role (Fixed paths)
                if ($row['role'] == 'admin') {
                    header('location: ../AdminDashboard.php');
                } elseif ($row['role'] == 'agent') {
                    header('location: ../AgentDashboard.php');
                } else {
                    header('location: ../home.php');
                }
                exit();
            } else {
                $error = "Incorrect password!";
            }
        } else {
            $error = "User not found!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Smart Insurance</title>
    <link rel="stylesheet" href="../assets/css/login.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>
<body>
    <div class="auth-container">
        <h1>Login</h1>
        <?php if ($error): ?>
            <div id="errorPopup" class="error-popup">
                <div class="error-content">
                    <i class='bx bx-error-circle'></i>
                    <span><?php echo htmlspecialchars($error); ?></span>
                    <button onclick="closePopup()">&times;</button>
                </div>
            </div>        
        <?php endif; ?>
        <form action="login.php" method="post">
            <label for="email">Email</label>
            <div class="input-group">
                <i class='bx bx-envelope'></i>
                <input type="email" id="email" name="email" placeholder="name@email.com">
            </div>
            <label for="password">Password</label>
            <div class="input-group">
                <i class='bx bx-lock-alt'></i>
                <input type="password" id="password" name="password" placeholder="••••••••">
            </div>
            <button type="submit" name="login">Login</button>
        </form>
        <p>Don't have an account? <a href="register.php">Register here</a></p>
    </div>
    
    <script src="../assets/js/login.js"></script>
            

</body>
</html>