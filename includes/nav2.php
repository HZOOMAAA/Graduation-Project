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
        "SELECT a.application_id, a.final_price, a.status, a.application_data, p.name AS plan_name, cat.name AS category_name
         FROM applications a
         LEFT JOIN insurance_plans p  ON a.plan_id      = p.plan_id
         LEFT JOIN categories cat     ON a.category_id  = cat.category_id
         WHERE a.customer_id = $notif_uid AND a.status IN ('awaiting_payment','rejected')

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
        <a href="homepage.php" class="logo">COVERLY</a>
        
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

            <div class="notif-wrapper">
                <button class="notif-bell-btn <?php echo ($notif_count > 0) ? 'has-notif' : ''; ?>" id="notifBellBtn" aria-label="Payment Notifications">
                    <i class="fa-solid fa-bell"></i>
                    
                    <?php if ($notif_count > 0): ?>
                        <span class="notif-badge"><?php echo $notif_count; ?></span>
                    <?php endif; ?>
                </button>

                <div class="notif-dropdown" id="notifDropdown">
                    <div class="notif-dropdown-header">
                        <i class="fa-solid fa-bell"></i>
                        <?php if ($notif_count > 0): ?>
                            Notifications (<?php echo $notif_count; ?>)
                        <?php else: ?>
                            Notifications
                        <?php endif; ?>

                        <a class="notif-see-all-btn" href="profile.php" aria-label="See all insurance applications" onclick="event.stopPropagation(); window.location.href='profile.php#my-applications'; return false;">
                            See all
                        </a>
                    </div>
                    
                    <div class="notif-list">
                        <?php if ($notif_count > 0): ?>
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
                                    <?php if (($na['status'] ?? '') === 'awaiting_payment'): ?>
                                        <a href="/Graduation-Project/payment.php?app_id=<?php echo (int)$na['application_id']; ?>"
                                           class="notif-pay-btn">
                                            <i class="fa-solid fa-arrow-right"></i> Pay Now
                                        </a>
                                    <?php else: ?>
                                        <?php 
                                        $notif_app_data = json_decode($na['application_data'] ?? '{}', true);
                                        $rejection_msg = $notif_app_data['rejection_message'] ?? '';
                                        if ($rejection_msg !== ''): 
                                        ?>
                                            <div class="notif-rejection-reason" style="font-size: 11px; color: #c62828; margin-top: 4px; margin-bottom: 6px; padding: 4px 8px; background: #fdecea; border-radius: 4px; border-left: 3px solid #c62828; line-height: 1.4;">
                                                <strong>Reason:</strong> 
                                                <span class="rejection-text">
                                                    <?php echo htmlspecialchars($rejection_msg); ?>
                                                </span>
                                            </div>
                                        <?php endif; ?>
                                        <a href="/Graduation-Project/planDetails.php?application_id=<?php echo (int)$na['application_id']; ?>"
                                           class="notif-pay-btn" style="background: #c62828; text-decoration: none;">
                                            <i class="fa-solid fa-xmark"></i> Rejected
                                        </a>
                                    <?php endif; ?>

                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="notif-item" style="justify-content: center; padding: 30px 20px; text-align: center; color: #888;">
                                <div style="display: flex; flex-direction: column; align-items: center; gap: 8px;">
                                    <i class="fa-solid fa-bell-slash" style="font-size: 24px; color: #ccc;"></i>
                                    <span style="font-size: 13px; font-weight: 500; line-height: 1.5;">All caught up!<br>No pending payments.</span>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

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
  <script>
    document.addEventListener("DOMContentLoaded", function () {
    const notifBellBtn = document.getElementById("notifBellBtn");
    const notifDropdown = document.getElementById("notifDropdown");

    if (notifBellBtn && notifDropdown) {
        // 1. عند الضغط على زرار الجرس
        notifBellBtn.addEventListener("click", function (event) {
            event.stopPropagation(); // بيمنع الـ Click إنه يسمع في باقي الصفحة
            notifDropdown.classList.toggle("open");
        });

        // 2. حركة صايعة: لو داس في أي مكان بره الـ Dropdown يقفله فوراً
        document.addEventListener("click", function (event) {
            if (!notifDropdown.contains(event.target) && !notifBellBtn.contains(event.target)) {
                notifDropdown.classList.remove("open");
            }
        });
    }
});
  </script>  
</body>
</html>