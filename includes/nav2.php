<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$is_logged_in = isset($_SESSION['user_id']);

// ── Fetch awaiting_payment notifications for logged-in customers ──
$notif_apps = [];
if ($is_logged_in && isset($_SESSION['role']) && $_SESSION['role'] === 'customer') {
    require_once __DIR__ . '/connection.php';
    $notif_uid = (int)$_SESSION['user_id'];
    $nq = mysqli_query($connect,
        "SELECT a.application_id, a.final_price, p.name AS plan_name, cat.name AS category_name
         FROM applications a
         LEFT JOIN insurance_plans p  ON a.plan_id      = p.plan_id
         LEFT JOIN categories cat     ON a.category_id  = cat.category_id
         WHERE a.customer_id = $notif_uid AND a.status = 'awaiting_payment'
         ORDER BY a.created_at DESC LIMIT 10"
    );
    if ($nq) {
        while ($nr = mysqli_fetch_assoc($nq)) $notif_apps[] = $nr;
    }
}
$notif_count = count($notif_apps);
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
                    <div style="display:flex;align-items:center;gap:10px;">

                        <?php if ($notif_count > 0): ?>
                        <!-- 🔔 Notification Bell -->
                        <div class="notif-wrapper">
                            <button class="notif-bell-btn has-notif" id="notifBellBtn" aria-label="Payment Notifications">
                                <i class="fa-solid fa-bell"></i>
                                <span class="notif-badge"><?php echo $notif_count; ?></span>
                            </button>
                            <div class="notif-dropdown" id="notifDropdown">
                                <div class="notif-dropdown-header">
                                    <i class="fa-solid fa-credit-card"></i>
                                    Payment Required (<?php echo $notif_count; ?>)
                                </div>
                                <div class="notif-list">
                                    <?php foreach ($notif_apps as $na): ?>
                                    <div class="notif-item">
                                        <div class="notif-item-icon">
                                            <i class="fa-solid fa-file-invoice-dollar"></i>
                                        </div>
                                        <div class="notif-item-body">
                                            <div class="notif-item-title">
                                                <?php echo htmlspecialchars($na['plan_name'] ?? 'Insurance Plan'); ?>
                                            </div>
                                            <div class="notif-item-sub">
                                                <?php echo htmlspecialchars($na['category_name'] ?? ''); ?>
                                                <?php if ($na['final_price'] > 0): ?>
                                                — EGP <?php echo number_format($na['final_price'], 2); ?>
                                                <?php endif; ?>
                                            </div>
                                            <a href="/Graduation-Project/payment.php?app_id=<?php echo (int)$na['application_id']; ?>"
                                               class="notif-pay-btn">
                                                <i class="fa-solid fa-arrow-right"></i> Pay Now
                                            </a>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Profile Icon -->
                        <div class="profile-container">
                            <a href="profile.php" class="profile-btn" title="View Profile">
                                <i class="fa-regular fa-circle-user"></i>
                            </a>
                        </div>
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