<?php
// بدء الجلسة للتأكد من هوية المستخدم
session_start();

// 1️⃣ حماية هندسية: لو اليوزر مش عامل Login، يرجعه فوراً لصفحة الـ Login
if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit();
}

require_once 'includes/connection.php';
include 'includes/nav2.php';

// ── 🌟 تثبيت بيانات static مؤقتاً لضبط التصميم 🌟 ──
$company       = "Intermediate Insurance Company";
$customer_name = $_SESSION['name'] ?? "Mahmoud Diaa"; // هيقرأ اسمك لو متسجل أو يحط Mahmoud Diaa كـ fallback
$policy_number = "#INS-2026-7220536"; // كود مميز ومظبوط للتيست
$amount        = "4,500.00";
$app_id        = "1024"; // رقم طلب وهمي لربط روابط الـ التوجيه (Visa / Fawry)

?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Insurance Payment Gateway - COVERLY</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="/Graduation-Project/assets/css/payment.css">


<main class="main-content">
    <div class="container">
        <div class="payment-card">
            <div class="card-header">
                <h1 class="card-title">Insurance Payment Portal</h1>
                <p class="card-subtitle">Secure redirection to trusted payment providers</p>
            </div>

            <div class="info-grid">
                <div><span class="info-label">Company</span><h3 class="info-value"><?php echo $company; ?></h3></div>
                <div><span class="info-label">Customer Name</span><h3 class="info-value" id="customerName"><?php echo htmlspecialchars($customer_name); ?></h3></div>
                <div><span class="info-label">Policy Reference</span><h3 class="info-value"><?php echo $policy_number; ?></h3></div>
                <div class="info-amount"><span class="info-label">Total Amount</span><h2 class="amount-value">EGP <?php echo $amount; ?></h2></div>
            </div>

            <h2 class="section-title">Select Payment Method</h2>
            <p class="section-subtitle">Choose your preferred payment provider to continue securely.</p>

            <div class="payment-grid">
                
                <div class="payment-option" onclick="location.href='visa.php?app_id=<?php echo $app_id; ?>'">
                    <img src="https://cdn-icons-png.flaticon.com/512/349/349221.png" alt="Visa" class="payment-icon">
                    <h3 class="payment-name">Visa</h3>
                    <p class="payment-desc">Pay using your Visa card securely.</p>
                    <button class="btn-continue">Continue</button>
                </div>
                
                <div class="payment-option" onclick="location.href='fawry.php?app_id=<?php echo $app_id; ?>'">
                    <img src="https:cdn-icons-png.flaticon.com/512/2489/2489610.png" alt="Fawry" class="payment-icon" >
                    <h3 class="payment-name">Fawry</h3>
                    <p class="payment-desc">Complete your payment through Fawry services.</p>
                    <button class="btn-continue">Continue</button>
                </div>
                
                <div class="payment-option" onclick="location.href='visa.php?app_id=<?php echo $app_id; ?>&provider=mastercard'">
                    <img src="https://cdn-icons-png.flaticon.com/512/196/196561.png" alt="Mastercard" class="payment-icon">
                    <h3 class="payment-name">Mastercard</h3>
                    <p class="payment-desc">Use your Mastercard for online payment.</p>
                    <button class="btn-continue">Continue</button>
                </div>
                
            </div>

            <div class="secure-banner">
                <p><i class="fas fa-shield-alt icon-secure"></i> All payments are processed through third-party secure providers.</p>
            </div>
        </div>
    </div>
</main>
<?php include 'includes/footer.php'; ?>