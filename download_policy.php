<?php
/**
 * download_policy.php
 * Dynamic and secure policy certificate generator.
 * Authenticates user, verifies policy ownership, and renders a premium print-ready PDF certificate.
 */
session_start();
require_once 'includes/connection.php';

// Guard: User must be logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$role = $_SESSION['role'] ?? 'customer';
$app_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($app_id <= 0) {
    die("<div style='font-family:sans-serif;padding:30px;text-align:center;'><h3>Error: Invalid application ID.</h3><a href='profile.php'>Return to Profile</a></div>");
}

// ── 1. Fetch Policy, Application, Plan & Customer Details ─────────────────────
$stmt = mysqli_prepare($connect,
    "SELECT a.*,
            u.name AS customer_name, u.email AS customer_email, u.phone AS customer_phone, u.address AS customer_address,
            p.name AS plan_name, p.insurance_company, p.base_price AS plan_base_price,
            cat.name AS category_name,
            pol.policy_number, pol.start_date, pol.end_date, pol.payment_ref, pol.status AS policy_status
     FROM applications a
     LEFT JOIN users u           ON a.customer_id = u.user_id
     LEFT JOIN insurance_plans p ON a.plan_id     = p.plan_id
     LEFT JOIN categories cat    ON a.category_id = cat.category_id
     LEFT JOIN policies pol      ON a.application_id = pol.application_id
     WHERE a.application_id = ?"
);
mysqli_stmt_bind_param($stmt, 'i', $app_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$app = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

if (!$app) {
    die("<div style='font-family:sans-serif;padding:30px;text-align:center;'><h3>Error: Policy not found.</h3><a href='profile.php'>Return to Profile</a></div>");
}

// ── 2. Security Guard: Prevent cross-user snooping ────────────────────────────
$access_granted = false;
if ($role === 'admin') {
    $access_granted = true;
} elseif ($role === 'agent' && (int)$app['agent_id'] === $user_id) {
    $access_granted = true;
} elseif ($role === 'customer' && (int)$app['customer_id'] === $user_id) {
    $access_granted = true;
}

if (!$access_granted) {
    die("<div style='font-family:sans-serif;padding:30px;text-align:center;color:#c62828;'><h3>Access Denied: You do not have permission to view this policy.</h3><a href='profile.php'>Return to Profile</a></div>");
}

// ── 3. Check Policy Status ────────────────────────────────────────────────────
if ($app['status'] !== 'paid' || empty($app['policy_number'])) {
    die("<div style='font-family:sans-serif;padding:30px;text-align:center;'><h3>Policy status is inactive or awaiting payment.</h3><a href='profile.php'>Return to Profile</a></div>");
}

// Extract and decode application data JSON
$app_data = json_decode($app['application_data'] ?? '{}', true);

// Remove internal/rejection keys from print details
$exclude_keys = ['rejection_message', 'was_rejected_before'];
$display_specs = [];
if (is_array($app_data)) {
    foreach ($app_data as $key => $val) {
        if (in_array($key, $exclude_keys)) continue;
        $display_specs[$key] = $val;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Policy Certificate - <?php echo htmlspecialchars($app['policy_number']); ?></title>
    <!-- Premium Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@500;700;800&family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <link rel="stylesheet" href="assets/css/policy.css">
</head>
<body>

    <!-- Floating controls (hidden during print) -->
    <div class="controls-bar">
        <a href="<?php echo ($role === 'agent') ? 'AgentDashboard.php?tab=paid' : (($role === 'admin') ? 'AdminDashboard.php' : 'profile.php'); ?>" class="btn-back">
            <i class="fa-solid fa-arrow-left"></i> Back to Dashboard
        </a>
        <button onclick="window.print()" class="btn-print">
            <i class="fa-solid fa-print"></i> Print / Save PDF
        </button>
    </div>

    <!-- Certificate Card -->
    <div class="certificate-container">
        <div class="certificate-watermark"></div>
        
        <div class="certificate-border-outer">
            <div class="certificate-border-inner">
                
                <!-- Logo & Heading -->
                <div class="cert-header">
                    <div class="cert-logo">
                        <i class="fa-solid fa-shield-halved"></i> Coverly
                    </div>
                    <h1 class="cert-title-badge">Certificate of Insurance</h1>
                    <div class="cert-subtitle">Official Policy Certificate Ledger</div>
                    <p class="cert-description">
                        This document certifies that the insurance policy described below has been fully paid, authorized, and issued. The coverage is subject to all the terms, exclusions, and conditions outlined in the master plan agreements of the participating underwriter.
                    </p>
                </div>

                <!-- Policy Hero Data Row -->
                <div class="policy-hero-row">
                    <div class="hero-data-item">
                        <span class="hero-lbl">Policy Number</span>
                        <span class="hero-val policy-no-badge"><?php echo htmlspecialchars($app['policy_number']); ?></span>
                    </div>
                    <div class="hero-data-item">
                        <span class="hero-lbl">Total Paid (EGP)</span>
                        <span class="hero-val">EGP <?php echo number_format($app['final_price'] ?: $app['plan_base_price'], 2); ?></span>
                    </div>
                </div>

                <!-- Main Info Details (Insured vs Plan) -->
                <div class="cert-info-grid">
                    <div>
                        <h3 class="info-section-title"><i class="fa-solid fa-user-shield"></i> Policyholder Details</h3>
                        <ul class="info-list">
                            <li><strong>Full Name:</strong> <?php echo htmlspecialchars($app['customer_name'] ?? 'N/A'); ?></li>
                            <li><strong>Email Address:</strong> <?php echo htmlspecialchars($app['customer_email'] ?? 'N/A'); ?></li>
                            <li><strong>Phone Number:</strong> <?php echo htmlspecialchars($app['customer_phone'] ?? 'N/A'); ?></li>
                            <li><strong>Address:</strong> <?php echo htmlspecialchars($app['customer_address'] ?? 'Not Provided'); ?></li>
                        </ul>
                    </div>
                    
                    <div>
                        <h3 class="info-section-title"><i class="fa-solid fa-shield-heart"></i> Underwriting Plan</h3>
                        <ul class="info-list">
                            <li><strong>Plan Name:</strong> <?php echo htmlspecialchars($app['plan_name'] ?? 'N/A'); ?></li>
                            <li><strong>Provider:</strong> <?php echo htmlspecialchars($app['insurance_company'] ?? 'N/A'); ?></li>
                            <li><strong>Category:</strong> <?php echo htmlspecialchars($app['category_name'] ?? 'N/A'); ?></li>
                            <li><strong>Effective Date:</strong> <?php echo date('M d, Y', strtotime($app['start_date'])); ?></li>
                            <li><strong>Expiry Date:</strong> <?php echo date('M d, Y', strtotime($app['end_date'])); ?></li>
                        </ul>
                    </div>
                </div>

                <!-- Decoded Application Data (Risk/Coverage specifics) -->
                <?php if (!empty($display_specs)): ?>
                    <div class="coverage-table-wrapper">
                        <h3 class="info-section-title"><i class="fa-solid fa-list-check"></i> Declared Risk & Coverage Parameters</h3>
                        <table class="coverage-table">
                            <thead>
                                <tr>
                                    <th class="prop-name">Parameter Field</th>
                                    <th class="prop-val">Declared Detail / Declared Value</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($display_specs as $key => $val): 
                                    $display_key = ucwords(str_replace('_', ' ', $key));
                                    $display_val = '';
                                    
                                    if (is_bool($val)) {
                                        $display_val = $val ? 'Yes' : 'No';
                                    } elseif (is_null($val) || $val === '') {
                                        $display_val = 'N/A';
                                    } elseif (is_array($val)) {
                                        if (empty($val)) {
                                            $display_val = 'None';
                                        } elseif (isset($val['day']) && isset($val['month']) && isset($val['year'])) {
                                            $display_val = sprintf('%02d/%02d/%04d', $val['day'], $val['month'], $val['year']);
                                        } else {
                                            $items = [];
                                            foreach ($val as $index => $item) {
                                                if (is_array($item)) {
                                                    if (isset($item['day']) && isset($item['month']) && isset($item['year'])) {
                                                        $items[] = sprintf('Child %d Date of Birth: %02d/%02d/%04d', $index + 1, $item['day'], $item['month'], $item['year']);
                                                    } else {
                                                        $items[] = json_encode($item);
                                                    }
                                                } else {
                                                    $items[] = (string)$item;
                                                }
                                            }
                                            $display_val = implode(', ', $items);
                                        }
                                    } else {
                                        $display_val = (string)$val;
                                    }
                                ?>
                                    <tr>
                                        <td class="prop-name"><?php echo htmlspecialchars($display_key); ?></td>
                                        <td class="prop-val"><?php echo htmlspecialchars($display_val); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

                <!-- Seals and Signature Rows -->
                <div class="cert-footer-row">
                    <div class="footer-stamp-box">
                        <!-- Custom SVG circular seal stamp -->
                        <svg class="official-seal" viewBox="0 0 100 100">
                            <circle cx="50" cy="50" r="45" fill="none" stroke="#d97706" stroke-width="2" stroke-dasharray="2,2"/>
                            <circle cx="50" cy="50" r="40" fill="none" stroke="#581c87" stroke-width="1.5"/>
                            <path id="sealTextPath" d="M 18,50 A 32,32 0 1,1 82,50 A 32,32 0 1,1 18,50" fill="none"/>
                            <text fill="#581c87" font-size="7" font-weight="700" letter-spacing="1">
                                <textPath href="#sealTextPath" startOffset="0%">
                                    • COVERLY INSURANCE SERVICES • SECURED DIGITAL CERTIFICATE
                                </textPath>
                            </text>
                            <!-- Center Icon -->
                            <path d="M50 32 L58 48 H52 V62 H48 V48 H42 L50 32 Z" fill="#d97706" />
                            <circle cx="50" cy="50" r="2" fill="#581c87" />
                        </svg>
                        
                        <!-- QR Code verification stamp -->
                        <div style="display:flex; flex-direction:column; gap:4px; font-size:10px; color:var(--text-light);">
                            <div style="width:50px; height:50px; border:1px solid #cbd5e1; padding:3px; background:white;">
                                <!-- QR Mockup SVG -->
                                <svg viewBox="0 0 25 25" style="width:100%; height:100%;">
                                    <path d="M0 0h7v7H0zm1 1v5h5V1zm1 1h3v3H2zm8-2h7v7h-7zm1 1v5h5V1zm1 1h3v3h-3zm8-2h7v7h-7zm1 1v5h5V1zm1 1h3v3h-3zM0 10h7v7H0zm1 1v5h5v-5zm1 1h3v3H2zm10 2h3v3h-3zm3-4h3v3h-3zm-3-2h3v3h-3zm9 6h1v1h-1zm-1-3h1v1h-1zm3 3h1v1h-1zm0-3h1v1h-1zm-3-3h1v1h-1zm3 0h1v1h-1z" fill="#1e1b4b"/>
                                </svg>
                            </div>
                            <span>Scan to verify</span>
                        </div>
                    </div>

                    <div class="sig-container">
                        <div class="sig-handwritten coverly-officer">Coverly Official</div>
                        <div class="sig-line">Authorized Signatory</div>
                    </div>
                    
                    <div class="sig-container">
                        <div class="sig-handwritten" style="font-family:'Outfit', sans-serif; font-size:14px; font-weight:600; text-transform:uppercase;">
                            <?php 
                                $names = explode(' ', trim($app['customer_name'] ?? 'CUSTOMER'));
                                echo htmlspecialchars($names[0] . (isset($names[1]) ? ' ' . substr($names[1], 0, 1) . '.' : ''));
                            ?>
                        </div>
                        <div class="sig-line">Signature of Insured</div>
                    </div>
                </div>

                <!-- Legal Disclaimer footer -->
                <div class="legal-disclaimer">
                    This electronic certificate is a direct representation of the transaction ledger stored within the Coverly Database. Transaction Reference: <?php echo htmlspecialchars($app['payment_ref'] ?? 'N/A'); ?>. Issued via Paymob secure payment integrations on <?php echo date('F d, Y - h:i A'); ?>. If any discrepancies arise, please contact support at support@coverly.com.
                </div>

            </div>
        </div>
    </div>

    <!-- Automatically open print prompt if 'print' param is passed -->
    <?php if (isset($_GET['print']) && $_GET['print'] === 'true'): ?>
    <script>
        window.addEventListener('DOMContentLoaded', () => {
            setTimeout(() => {
                window.print();
            }, 500);
        });
    </script>
    <?php endif; ?>

</body>
</html>
