<?php
require_once 'includes/connection.php';
require_once 'includes/auth_check.php'; // ensures user is logged in

// ── Validate application_id ───────────────────────────────────────────────────
$application_id = isset($_GET['application_id']) ? intval($_GET['application_id']) : 0;
if ($application_id <= 0) {
    header('Location: /Graduation-Project/home.php');
    exit;
}

// ── Load the application (must belong to this customer) ───────────────────────
$customer_id = $_SESSION['user_id'];
$appStmt = mysqli_prepare($connect,
    "SELECT a.*, cat.name as category_name
     FROM applications a
     LEFT JOIN categories cat ON a.category_id = cat.category_id
     WHERE a.application_id = ? AND a.customer_id = ?"
);
mysqli_stmt_bind_param($appStmt, 'ii', $application_id, $customer_id);
mysqli_stmt_execute($appStmt);
$appResult = mysqli_stmt_get_result($appStmt);
$application = mysqli_fetch_assoc($appResult);

if (!$application) {
    header('Location: /Graduation-Project/home.php');
    exit;
}

// ── Only allow plan selection if still pending ────────────────────────────────
$already_selected = ($application['status'] !== 'pending_selection');

// ── Decode submitted data ─────────────────────────────────────────────────────
$appData = json_decode($application['application_data'] ?? '{}', true);
$car_condition = strtolower($appData['condition'] ?? '');
$car_year      = intval($appData['year'] ?? 0);
$car_price     = floatval($appData['price'] ?? 0);

// ── Fetch all plans for this category ────────────────────────────────────────
$plansStmt = mysqli_prepare($connect,
    "SELECT * FROM insurance_plans WHERE category_id = ? ORDER BY base_price ASC"
);
mysqli_stmt_bind_param($plansStmt, 'i', $application['category_id']);
mysqli_stmt_execute($plansStmt);
$plansResult = mysqli_stmt_get_result($plansStmt);

// ── Filter plans by eligibility rules ────────────────────────────────────────
$matched_plans  = [];
$excluded_plans = [];

while ($plan = mysqli_fetch_assoc($plansResult)) {
    $rules    = json_decode($plan['eligibility_rules'] ?? 'null', true);
    $eligible = true;
    $fail_reasons = [];

    if (is_array($rules)) {
        // condition match: e.g. "condition": "new"  OR  "condition": ["new","used"]
        if (isset($rules['condition'])) {
            $allowed = is_array($rules['condition'])
                ? array_map('strtolower', $rules['condition'])
                : [strtolower($rules['condition'])];
            if (!in_array($car_condition, $allowed)) {
                $eligible = false;
                $fail_reasons[] = 'Car condition must be: ' . implode(' or ', $allowed);
            }
        }

        // year range: min_year / max_year
        if (isset($rules['min_year']) && $car_year < intval($rules['min_year'])) {
            $eligible = false;
            $fail_reasons[] = 'Car year must be ' . $rules['min_year'] . ' or newer';
        }
        if (isset($rules['max_year']) && $car_year > intval($rules['max_year'])) {
            $eligible = false;
            $fail_reasons[] = 'Car year must be ' . $rules['max_year'] . ' or older';
        }

        // price range: min_price / max_price  (car value)
        if (isset($rules['min_price']) && $car_price < floatval($rules['min_price'])) {
            $eligible = false;
            $fail_reasons[] = 'Car value must be at least EGP ' . number_format($rules['min_price']);
        }
        if (isset($rules['max_price']) && $car_price > floatval($rules['max_price'])) {
            $eligible = false;
            $fail_reasons[] = 'Car value must not exceed EGP ' . number_format($rules['max_price']);
        }
    }

    $plan['_fail_reasons'] = $fail_reasons;

    if ($eligible) {
        $matched_plans[] = $plan;
    } else {
        $excluded_plans[] = $plan;
    }
}

// ── Handle plan selection POST ────────────────────────────────────────────────
$select_error   = '';
$select_success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['plan_id']) && !$already_selected) {
    $plan_id = intval($_POST['plan_id']);

    // Verify the chosen plan is among matched plans
    $matched_ids = array_column($matched_plans, 'plan_id');
    if (!in_array($plan_id, $matched_ids)) {
        $select_error = 'Invalid plan selection.';
    } else {
        $upd = mysqli_prepare($connect,
            "UPDATE applications SET plan_id = ?, status = 'waiting_docs' WHERE application_id = ? AND customer_id = ?"
        );
        mysqli_stmt_bind_param($upd, 'iii', $plan_id, $application_id, $customer_id);
        if (mysqli_stmt_execute($upd)) {
            // Redirect to plan details / document upload page
            header('Location: /Graduation-Project/planDetails.php?application_id=' . $application_id);
            exit;
        } else {
            $select_error = 'Failed to save your plan selection. Please try again.';
        }
    }
}

include 'includes/header.php';
?>
<link rel="stylesheet" href="/Graduation-Project/assets/css/plans.css">

<section class="plans-section">
    <div class="plans-wrapper">

        <!-- ── Page Header ── -->
        <div class="plans-hero">
            <div class="plans-hero-inner">
                <div class="hero-badge">
                    <i class="bx bxs-shield-plus"></i> Application #<?php echo $application_id; ?>
                </div>
                <h1>Choose Your Insurance Plan</h1>
                <p>Based on your submitted details, we found <strong><?php echo count($matched_plans); ?></strong> plan<?php echo count($matched_plans) !== 1 ? 's' : ''; ?> that match your vehicle.</p>

                <!-- Summary chips -->
                <div class="summary-chips">
                    <span class="chip chip--blue"><i class="bx bxs-car"></i> <?php echo htmlspecialchars(ucfirst($appData['brand'] ?? '—') . ' ' . ($appData['model'] ?? '')); ?></span>
                    <span class="chip chip--purple"><i class="bx bxs-calendar"></i> <?php echo htmlspecialchars($appData['year'] ?? '—'); ?></span>
                    <span class="chip chip--green"><i class="bx bxs-badge-check"></i> <?php echo htmlspecialchars(ucfirst($appData['condition'] ?? '—')); ?></span>
                    <span class="chip chip--orange"><i class="bx bxs-coin-stack"></i> EGP <?php echo number_format($car_price); ?></span>
                </div>
            </div>
        </div>

        <!-- ── Alerts ── -->
        <?php if ($select_success): ?>
            <div class="plans-alert plans-alert--success">
                <i class="bx bxs-check-circle"></i>
                <?php echo htmlspecialchars($select_success); ?>
            </div>
        <?php endif; ?>
        <?php if ($select_error): ?>
            <div class="plans-alert plans-alert--error">
                <i class="bx bxs-error-circle"></i>
                <?php echo htmlspecialchars($select_error); ?>
            </div>
        <?php endif; ?>
        <?php if ($already_selected && !$select_success): ?>
            <div class="plans-alert plans-alert--info">
                <i class="bx bxs-info-circle"></i>
                You have already selected a plan for this application. An agent will be assigned to you shortly.
            </div>
        <?php endif; ?>

        <!-- ── Matched Plans ── -->
        <?php if (!empty($matched_plans)): ?>
            <div class="plans-section-title">
                <i class="bx bxs-check-shield"></i> Matching Plans
                <span class="plans-count"><?php echo count($matched_plans); ?></span>
            </div>

            <div class="plans-grid">
                <?php foreach ($matched_plans as $i => $plan): ?>
                    <div class="plan-card plan-card--matched <?php echo $i === 0 ? 'plan-card--featured' : ''; ?>">
                        <?php if ($i === 0): ?>
                            <div class="plan-featured-badge"><i class="bx bxs-star"></i> Best Match</div>
                        <?php endif; ?>

                        <div class="plan-card-header">
                            <div class="plan-company"><?php echo htmlspecialchars($plan['insurance_company']); ?></div>
                            <h2 class="plan-name"><?php echo htmlspecialchars($plan['name']); ?></h2>
                            <div class="plan-price">
                                <span class="price-amount">EGP <?php echo number_format($plan['base_price'], 2); ?></span>
                                <span class="price-label">/ year</span>
                            </div>
                        </div>

                        <div class="plan-card-body">
                            <?php if (!empty($plan['bio'])): ?>
                                <p class="plan-bio"><?php echo htmlspecialchars($plan['bio']); ?></p>
                            <?php endif; ?>

                            <?php
                            $rules = json_decode($plan['eligibility_rules'] ?? 'null', true);
                            if (is_array($rules) && !empty($rules)):
                            ?>
                                <div class="plan-rules">
                                    <div class="plan-rules-title"><i class="bx bxs-list-check"></i> Eligibility</div>
                                    <ul class="plan-rules-list">
                                        <?php foreach ($rules as $k => $v): ?>
                                            <li>
                                                <i class="bx bxs-check-circle rule-ok"></i>
                                                <strong><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $k))); ?>:</strong>
                                                <?php
                                                $display = is_array($v) ? implode(', ', $v) : $v;
                                                echo htmlspecialchars($display);
                                                ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="plan-card-footer">
                            <?php if (!$already_selected): ?>
                                <form method="POST">
                                    <input type="hidden" name="plan_id" value="<?php echo $plan['plan_id']; ?>">
                                    <button type="submit" class="btn-select-plan <?php echo $i === 0 ? 'btn-select-plan--featured' : ''; ?>">
                                        <i class="bx bxs-check-circle"></i> Select This Plan
                                    </button>
                                </form>
                            <?php else: ?>
                                <?php if ($application['plan_id'] == $plan['plan_id']): ?>
                                    <div class="plan-selected-badge"><i class="bx bxs-check-circle"></i> Selected</div>
                                <?php else: ?>
                                    <div class="plan-unavailable">Selection Locked</div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php else: ?>
            <div class="plans-empty">
                <i class="bx bxs-error-alt"></i>
                <h3>No Matching Plans Found</h3>
                <p>Unfortunately, no insurance plans match your vehicle details. Please contact our support team for assistance.</p>
            </div>
        <?php endif; ?>

        <!-- ── Excluded Plans (collapsed) ── -->
        <?php if (!empty($excluded_plans)): ?>
            <details class="excluded-section">
                <summary>
                    <i class="bx bxs-x-circle"></i>
                    Plans You Don't Qualify For
                    <span class="plans-count plans-count--red"><?php echo count($excluded_plans); ?></span>
                </summary>

                <div class="plans-grid plans-grid--excluded">
                    <?php foreach ($excluded_plans as $plan): ?>
                        <div class="plan-card plan-card--excluded">
                            <div class="plan-card-header">
                                <div class="plan-company"><?php echo htmlspecialchars($plan['insurance_company']); ?></div>
                                <h2 class="plan-name"><?php echo htmlspecialchars($plan['name']); ?></h2>
                                <div class="plan-price plan-price--muted">
                                    <span class="price-amount">EGP <?php echo number_format($plan['base_price'], 2); ?></span>
                                </div>
                            </div>
                            <div class="plan-card-body">
                                <?php if (!empty($plan['_fail_reasons'])): ?>
                                    <div class="plan-rules">
                                        <div class="plan-rules-title plan-rules-title--red"><i class="bx bxs-x-circle"></i> Why you don't qualify</div>
                                        <ul class="plan-rules-list">
                                            <?php foreach ($plan['_fail_reasons'] as $reason): ?>
                                                <li><i class="bx bxs-x-circle rule-fail"></i> <?php echo htmlspecialchars($reason); ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </details>
        <?php endif; ?>

    </div>
</section>

<?php include 'includes/footer.php'; ?>
