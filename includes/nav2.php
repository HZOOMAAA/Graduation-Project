<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$is_logged_in = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coverly Insurance</title>
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="assets/css/nav2.css">
</head>
<body>

   <header>
    <div class="container">
        <!-- اللوجو على الشمال -->
        <a href="#" class="logo">COVERLY</a>
        
        <!-- الجزء اليمين (بيضم الـ 2 ناف مع بعض) -->
        <div class="header-right">
            
            <!-- الناف الأول (الروابط الأساسية) -->
            <nav class="main-nav">
                <ul>
                    <li><a href="homepage.php">Home</a></li>
                    <li class="dropdown">
                        <a href="#">Categories <i class="fas fa-chevron-down" style="font-size: 12px;"></i></a>
                        <ul class="dropdown-menu">
                            <li><a href="category-health.php">Health</a></li>
                            <li><a href="category-car.php">Car</a></li>
                            <li><a href="category-life.php">Life</a></li>
                            <li><a href="category-property.php">Property</a></li>
                        </ul>
                    </li>
                    <li><a href="about.php">About Us</a></li>
                    <!-- <li><a href="#"></a></li> -->
                </ul>
            </nav>

            <!-- الناف التاني (أزرار الدخول والتسجيل أو صورة/أيقونة البروفايل) -->
            <nav class="auth-nav">
                <?php if ($is_logged_in): ?>
                    <div class="profile-container">
                        <a href="profile.php" class="profile-btn" title="View Profile">
                            <i class="fa-regular fa-circle-user"></i>
                        </a>
                    </div>
                <?php else: ?>
                    <div class="auth-buttons">
                        <a href="auth/login.php" class="btn-login">Login</a>
                        <a href="auth/register.php" class="btn-register">Register</a>
                    </div>
                <?php endif; ?>
            </nav>

        </div>
    </div>
</header>
    
</body>
</html>