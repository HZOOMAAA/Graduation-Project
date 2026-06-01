<?php
session_start();

// Guard: must be logged in as customer
if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit();
}

require_once 'includes/connection.php';

$customer_id = (int)$_SESSION['user_id'];
$app_id      = isset($_GET['app_id']) ? (int)$_GET['app_id'] : 0;

// Validate app_id and ownership
if ($app_id <= 0) {
    header("Location: profile.php");
    exit();
}

$stmt = mysqli_prepare($connect,
    "SELECT a.application_id, p.base_price, a.status,
            u.name AS customer_name,
            p.name AS plan_name, p.insurance_company,
            cat.name AS category_name
     FROM applications a
     LEFT JOIN users u         ON a.customer_id = u.user_id
     LEFT JOIN insurance_plans p ON a.plan_id   = p.plan_id
     LEFT JOIN categories cat  ON a.category_id = cat.category_id
     WHERE a.application_id = ? AND a.customer_id = ?"
);
mysqli_stmt_bind_param($stmt, 'ii', $app_id, $customer_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$app = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

if (!$app) {
    header("Location: profile.php");
    exit();
}

// Only allow payment if status is awaiting_payment
if ($app['status'] !== 'awaiting_payment') {
    header("Location: profile.php");
    exit();
}

$customer_name   = htmlspecialchars($app['customer_name'] ?? $_SESSION['name'] ?? 'Customer');
$plan_name       = htmlspecialchars($app['plan_name'] ?? 'Insurance Plan');
$company         = htmlspecialchars($app['insurance_company'] ?? 'Coverly Partner');
$category_name   = htmlspecialchars($app['category_name'] ?? 'Insurance');
$amount          = number_format((float)$app['base_price'], 2);
$amount_raw      = (float)$app['base_price'];

// Category-based prefix preview for display
$cat_lc = strtolower($app['category_name'] ?? '');
if      (strpos($cat_lc, 'car')      !== false) $pol_prefix = 'CAR';
elseif  (strpos($cat_lc, 'health')   !== false) $pol_prefix = 'HLT';
elseif  (strpos($cat_lc, 'life')     !== false) $pol_prefix = 'LFE';
elseif  (strpos($cat_lc, 'property') !== false) $pol_prefix = 'PRP';
else                                              $pol_prefix = 'INS';

include 'includes/nav2.php';
?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Secure Payment — COVERLY</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
<link rel="stylesheet" href="/Graduation-Project/assets/css/payment.css">

<main class="main-content">
    <div class="payment-page-container"> <div class="payment-card">
            
            <div class="card-header">
                <i class='bx bx-shield-quarter' style="font-size: 48px; color: #1A2B48; margin-bottom: 12px; display: inline-block;"></i>
                <h1 class="card-title">Insurance Payment Portal</h1>
                <p class="card-subtitle">Secure payment encryption framework for your approved insurance policy</p>
            </div>

            <div class="info-grid">
                <div>
                    <span class="info-label"><i class='bx bxs-business' style="vertical-align: middle; margin-right: 4px;"></i> Company</span>
                    <h3 class="info-value"><?php echo $company; ?></h3>
                </div>
                <div>
                    <span class="info-label"><i class='bx bxs-user-detail' style="vertical-align: middle; margin-right: 4px;"></i> Customer Name</span>
                    <h3 class="info-value" id="customerName"><?php echo $customer_name; ?></h3>
                </div>
                <div>
                    <span class="info-label"><i class='bx bx-barcode' style="vertical-align: middle; margin-right: 4px;"></i> Policy Reference</span>
                    <h3 class="info-value">
                        <span style="background: rgba(37, 99, 235, 0.08); color: #2563eb; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 700; border: 1px solid rgba(37, 99, 235, 0.15);">
                            <?php echo $pol_prefix; ?>-<?php echo date('Y'); ?>-XXXXXXXX
                        </span>
                    </h3>
                </div>
                <div>
                    <span class="info-label"><i class='bx bxs-layer' style="vertical-align: middle; margin-right: 4px;"></i> Plan & Category</span>
                    <h3 class="info-value" style="font-size: 14px; font-weight: 600; line-height: 1.4;">
                        <?php echo $plan_name; ?> <br>
                        <small style="color: #64748b; font-weight: 400; font-size: 12px;">Category: <?php echo $category_name; ?></small>
                    </h3>
                </div>
                <div class="info-amount">
                    <span class="info-label"><i class='bx bx-credit-card-front' style="vertical-align: middle; margin-right: 4px;"></i> Total Amount Due</span>
                    <h2 class="amount-value">EGP <?php echo $amount; ?></h2>
                </div>
            </div>

            <h2 class="section-title">Select Payment Method</h2>
            <p class="section-subtitle">Choose your preferred audited payment merchant infrastructure below.</p>

            <div class="payment-grid">

                <div class="payment-option" onclick="location.href='paymob_initiate.php?app_id=<?php echo $app_id; ?>&provider=visa'">
                    <img src="https://cdn-icons-png.flaticon.com/512/349/349221.png" alt="Visa" class="payment-icon">
                    <h3 class="payment-name">Visa Card</h3>
                    <p class="payment-desc">Pay securely using international Visa gateway routed via Paymob framework.</p>
                    <button class="btn-continue">Proceed to Checkout <i class='bx bx-right-arrow-alt' style="vertical-align: middle; font-size: 16px; margin-left: 4px;"></i></button>
                </div>

                
                <!-- <div class="payment-option" onclick="location.href='fawry.php?app_id=<?php echo $app_id; ?>'">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/6/6f/Fawry_logo.svg/320px-Fawry_logo.svg.png" alt="Fawry" class="payment-icon" style="object-fit:contain;">
                    <h3 class="payment-name">Fawry Pay</h3>
                    <p class="payment-desc">Generate a secure reference payment code valid at any local Fawry retailer machine.</p>
                    <button class="btn-continue">Generate Code <i class='bx bx-right-arrow-alt' style="vertical-align: middle; font-size: 16px; margin-left: 4px;"></i></button>
                </div> -->

                <div class="payment-option" onclick="location.href='paymob_initiate.php?app_id=<?php echo $app_id; ?>&provider=mastercard'">
                    <img src="https://cdn-icons-png.flaticon.com/512/196/196561.png" alt="Mastercard" class="payment-icon">
                    <h3 class="payment-name">Mastercard</h3>
                    <p class="payment-desc">Secure end-to-end tokenized Mastercard processing through active Paymob API.</p>
                    <button class="btn-continue">Proceed to Checkout <i class='bx bx-right-arrow-alt' style="vertical-align: middle; font-size: 16px; margin-left: 4px;"></i></button>
                </div>

            </div>

            <div class="secure-banner">
                <p><i class='bx bxs-lock-alt icon-secure' style="font-size: 16px; color: #10b981; vertical-align: middle;"></i> <b>SSL Secured & Encrypted:</b> All data packets are strictly tokenized. Card telemetry is never logged on COVERLY main servers.</p>
            </div>
            
        </div>
    </div>
</main>
<?php include 'includes/footer.php'; ?>