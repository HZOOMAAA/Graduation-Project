<?php
require_once 'includes/connection.php';
require_once 'includes/auth_check.php'; // ensures user is logged in

// ── Load the application (Check session first for deferred draft, fall back to DB) ──
$customer_id = $_SESSION['user_id'];
$application = null;
$appData = null;

if (isset($_SESSION['temp_application_data'])) {
    $appData = $_SESSION['temp_application_data'];
    $category_id = $_SESSION['temp_category_id'] ?? 1;

    // Fetch category name
    $catResult = mysqli_query($connect, "SELECT name FROM categories WHERE category_id = {$category_id} LIMIT 1");
    $catRow = mysqli_fetch_assoc($catResult);
    $category_name = $catRow ? $catRow['name'] : 'Car Insurance';

    $application = [
        'application_id' => 0, // Indicates session-based draft
        'customer_id' => $customer_id,
        'category_id' => $category_id,
        'status' => 'pending_selection',
        'plan_id' => null,
        'category_name' => $category_name,
        'application_data' => json_encode($appData)
    ];
} else {
    // Standard URL-based fallback
    $application_id = isset($_GET['application_id']) ? intval($_GET['application_id']) : 0;
    if ($application_id <= 0) {
        header('Location: /Graduation-Project/homepage.php');
        exit;
    }

    $appStmt = mysqli_prepare(
        $connect,
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
        header('Location: /Graduation-Project/homepage.php');
        exit;
    }
    $appData = json_decode($application['application_data'] ?? '{}', true);
}

// ── Only allow plan selection if still pending ────────────────────────────────
$already_selected = ($application['status'] !== 'pending_selection');

// Detect if category is health, life, or property
$is_health = (isset($appData['category']) && $appData['category'] === 'health')
    || (isset($application['category_name']) && stripos($application['category_name'], 'health') !== false)
    || (isset($application['category_name']) && stripos($application['category_name'], 'medical') !== false)
    || (isset($application['category_id']) && intval($application['category_id']) == 2);

$is_life = (isset($appData['category']) && $appData['category'] === 'life')
    || (isset($application['category_name']) && stripos($application['category_name'], 'life') !== false)
    || (isset($application['category_id']) && intval($application['category_id']) == 5);

$is_property = (isset($appData['category']) && $appData['category'] === 'property')
    || (isset($application['category_name']) && stripos($application['category_name'], 'property') !== false)
    || (isset($application['category_id']) && intval($application['category_id']) == 3);

if ($is_health) {
    $age = intval($appData['age'] ?? 0);
    $family_chronic = $appData['family_chronic'] ?? 'no';
    $has_chronic = ($family_chronic === 'yes');
    $has_spouse = !empty($appData['has_spouse']);
    $num_children = intval($appData['num_children'] ?? 0);
} else if ($is_life) {
    $age = intval($appData['age'] ?? 0);
    $coverage_amount = floatval($appData['coverage_amount'] ?? 0);
    $policy_term = $appData['policy_term'] ?? '';
    $beneficiary = $appData['beneficiary_name'] ?? '';
    $relation = $appData['beneficiary_relation'] ?? '';
} else if ($is_property) {
    $property_type = $appData['property_type'] ?? '';
    $construction_year = $appData['construction_year'] ?? '';
    $property_value = floatval($appData['property_value'] ?? 0);
    $contents_value = floatval($appData['contents_value'] ?? 0);
    $coverage_type = $appData['coverage_type'] ?? '';
    $property_address = $appData['property_address'] ?? '';
    $property_usage = $appData['property_usage'] ?? 'owned';
} else {
    $car_condition = strtolower($appData['condition'] ?? '');
    $car_year = intval($appData['year'] ?? 0);
    $car_price = floatval($appData['price'] ?? 0);
}

// ── Fetch all plans for this category ────────────────────────────────────────
$plansStmt = mysqli_prepare(
    $connect,
    "SELECT * FROM insurance_plans WHERE category_id = ? ORDER BY base_price ASC"
);
mysqli_stmt_bind_param($plansStmt, 'i', $application['category_id']);
mysqli_stmt_execute($plansStmt);
$plansResult = mysqli_stmt_get_result($plansStmt);

// ── Filter plans by eligibility rules ────────────────────────────────────────
$matched_plans = [];
$excluded_plans = [];

while ($plan = mysqli_fetch_assoc($plansResult)) {
    $rules = json_decode($plan['eligibility_rules'] ?? 'null', true);
    $eligible = true;
    $fail_reasons = [];

    if (is_array($rules)) {
        if ($is_health) {
            // Age ranges: min_age & max_age
            if (isset($rules['min_age']) && $age < intval($rules['min_age'])) {
                $eligible = false;
                $fail_reasons[] = 'Primary member must be at least ' . $rules['min_age'] . ' years old (your age is ' . $age . ')';
            }
            if (isset($rules['max_age']) && $age > intval($rules['max_age'])) {
                $eligible = false;
                $fail_reasons[] = 'Primary member must not exceed ' . $rules['max_age'] . ' years old (your age is ' . $age . ')';
            }

            // Chronic allowed: if chronic_allowed is false and customer has chronic
            if (isset($rules['chronic_allowed']) && !$rules['chronic_allowed'] && $has_chronic) {
                $eligible = false;
                $fail_reasons[] = 'Plan does not accept applicants with pre-existing chronic conditions';
            }

            // Covers spouse: if covers_spouse is false and customer has a spouse
            if (isset($rules['covers_spouse']) && !$rules['covers_spouse'] && $has_spouse) {
                $eligible = false;
                $fail_reasons[] = 'Plan does not cover spouses (you have registered a spouse)';
            }

            // Max children
            if (isset($rules['max_children']) && $num_children > intval($rules['max_children'])) {
                $eligible = false;
                $fail_reasons[] = 'Plan covers a maximum of ' . $rules['max_children'] . ' children (you have ' . $num_children . ')';
            }
        } else if ($is_life) {
            // Life insurance eligibility rules: min_age, max_age, min_coverage, max_coverage, allowed_terms
            if (isset($rules['min_age']) && $age < intval($rules['min_age'])) {
                $eligible = false;
                $fail_reasons[] = 'Applicant age must be at least ' . $rules['min_age'] . ' years old (your age is ' . $age . ')';
            }
            if (isset($rules['max_age']) && $age > intval($rules['max_age'])) {
                $eligible = false;
                $fail_reasons[] = 'Applicant age must not exceed ' . $rules['max_age'] . ' years old (your age is ' . $age . ')';
            }

            if (isset($rules['min_coverage']) && $coverage_amount < floatval($rules['min_coverage'])) {
                $eligible = false;
                $fail_reasons[] = 'Requested coverage amount must be at least EGP ' . number_format($rules['min_coverage']);
            }
            if (isset($rules['max_coverage']) && $coverage_amount > floatval($rules['max_coverage'])) {
                $eligible = false;
                $fail_reasons[] = 'Requested coverage amount must not exceed EGP ' . number_format($rules['max_coverage']);
            }

            if (isset($rules['allowed_terms']) && is_array($rules['allowed_terms'])) {
                $term_clean = strtolower($policy_term);
                $allowed_clean = array_map('strtolower', $rules['allowed_terms']);
                if (!in_array($term_clean, $allowed_clean)) {
                    $eligible = false;
                    $fail_reasons[] = 'Requested policy term (' . str_replace('_', ' ', $policy_term) . ') is not allowed for this plan';
                }
            }
        } else if ($is_property) {
            // Property insurance eligibility rules: property_types_allowed, min_property_value, max_property_value, coverage_types_allowed
            if (isset($rules['property_types_allowed']) && is_array($rules['property_types_allowed'])) {
                $allowed = array_map('strtolower', $rules['property_types_allowed']);
                if (!in_array(strtolower($property_type), $allowed)) {
                    $eligible = false;
                    $fail_reasons[] = 'Property type must be: ' . implode(' or ', array_map('ucfirst', $rules['property_types_allowed']));
                }
            }

            if (isset($rules['min_property_value']) && $property_value < floatval($rules['min_property_value'])) {
                $eligible = false;
                $fail_reasons[] = 'Property value must be at least EGP ' . number_format($rules['min_property_value']);
            }
            if (isset($rules['max_property_value']) && $property_value > floatval($rules['max_property_value'])) {
                $eligible = false;
                $fail_reasons[] = 'Property value must not exceed EGP ' . number_format($rules['max_property_value']);
            }

            if (isset($rules['coverage_types_allowed']) && is_array($rules['coverage_types_allowed'])) {
                $allowed = array_map('strtolower', $rules['coverage_types_allowed']);
                if (!in_array(strtolower($coverage_type), $allowed)) {
                    $eligible = false;
                    $fail_reasons[] = 'Requested coverage type is not allowed for this plan';
                }
            }
        } else {
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
    }

    $plan['_fail_reasons'] = $fail_reasons;

    if ($eligible) {
        $matched_plans[] = $plan;
    } else {
        $excluded_plans[] = $plan;
    }
}

// ── Handle plan selection POST ────────────────────────────────────────────────
$select_error = '';
$select_success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['plan_id']) && !$already_selected) {
    $plan_id = intval($_POST['plan_id']);

    // Verify the chosen plan is among matched plans
    $matched_ids = array_column($matched_plans, 'plan_id');
    if (!in_array($plan_id, $matched_ids)) {
        $select_error = 'Invalid plan selection.';
    } else {
        if ($application['application_id'] === 0) {
            // Store selected plan ID inside the PHP Session
            $_SESSION['temp_plan_id'] = $plan_id;
            header('Location: /Graduation-Project/planDetails.php');
            exit;
        } else {
            $upd = mysqli_prepare(
                $connect,
                "UPDATE applications SET plan_id = ?, status = 'waiting_docs' WHERE application_id = ? AND customer_id = ?"
            );
            mysqli_stmt_bind_param($upd, 'iii', $plan_id, $application['application_id'], $customer_id);
            if (mysqli_stmt_execute($upd)) {
                // Redirect to plan details / document upload page
                header('Location: /Graduation-Project/planDetails.php?application_id=' . $application['application_id']);
                exit;
            } else {
                $select_error = 'Failed to save your plan selection. Please try again.';
            }
        }
    }
}

include 'includes/nav2.php';
?>
<link rel="stylesheet" href="/Graduation-Project/assets/css/plans.css">

<section class="plans-section">
    <div class="plans-wrapper">

        <!-- ── Page Header ── -->
        <div class="plans-hero">
            <div class="plans-hero-inner">
              
                <h1>Choose Your Insurance Plan</h1>
                <p>Based on your submitted details, we found <strong><?php echo count($matched_plans); ?></strong> plan<?php echo count($matched_plans) !== 1 ? 's' : ''; ?> that match your <?php 
                    if ($is_health) echo 'health profile';
                    else if ($is_life) echo 'life insurance profile';
                    else if ($is_property) echo 'property details';
                    else echo 'vehicle';
                ?>.</p>

                <!-- Summary chips -->
                <div class="summary-chips">
                    <?php if ($is_health): ?>
                            <span class="chip chip--blue"><i class="bx bxs-user"></i> Age: <?php echo htmlspecialchars($age); ?> yrs</span>
                            <span class="chip chip--purple"><i class="bx bxs-group"></i> Spouse: <?php echo $has_spouse ? 'Yes' : 'No'; ?></span>
                            <span class="chip chip--green"><i class="bx bx-child"></i> Children: <?php echo $num_children; ?></span>
                            <span class="chip chip--orange"><i class="bx bxs-first-aid"></i> Chronic: <?php echo $has_chronic ? 'Yes' : 'No'; ?></span>
                    <?php elseif ($is_life): ?>
                            <span class="chip chip--blue"><i class="bx bxs-user"></i> Age: <?php echo htmlspecialchars($age); ?> yrs</span>
                            <span class="chip chip--purple"><i class="bx bxs-coin-stack"></i> Limit: EGP <?php echo number_format($coverage_amount); ?></span>
                            <span class="chip chip--green"><i class="bx bxs-time"></i> Term: <?php echo htmlspecialchars(str_replace('_', ' ', $policy_term)); ?></span>
                            <span class="chip chip--orange"><i class="bx bxs-heart"></i> Beneficiary: <?php echo htmlspecialchars(ucwords($relation)); ?></span>
                    <?php elseif ($is_property): ?>
                            <span class="chip chip--blue"><i class="bx bxs-home"></i> <?php echo htmlspecialchars(ucfirst($property_type)); ?></span>
                            <span class="chip chip--purple"><i class="bx bxs-coin-stack"></i> Value: EGP <?php echo number_format($property_value); ?></span>
                            <span class="chip chip--green"><i class="bx bxs-box"></i> Contents: EGP <?php echo number_format($contents_value); ?></span>
                            <span class="chip chip--orange"><i class="bx bxs-shield"></i> <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $coverage_type))); ?></span>
                    <?php else: ?>
                            <span class="chip chip--blue"><i class="bx bxs-car"></i> <?php echo htmlspecialchars(ucfirst($appData['brand'] ?? '—') . ' ' . ($appData['model'] ?? '')); ?></span>
                            <!-- <span class="chip chip--purple"><i class="bx bxs-calendar"></i> <?php echo htmlspecialchars($appData['year'] ?? '—'); ?></span>
                            <span class="chip chip--green"><i class="bx bxs-badge-check"></i> <?php echo htmlspecialchars(ucfirst($appData['condition'] ?? '—')); ?></span>
                            <span class="chip chip--orange"><i class="bx bxs-coin-stack"></i> EGP <?php echo number_format($car_price); ?></span> -->
                    <?php endif; ?>
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
     <div class="plan-company">
    <?php if (!empty($plan['logo'])): ?>
        <img src="/Graduation-Project/<?php echo htmlspecialchars($plan['logo']); ?>" 
             alt="<?php echo htmlspecialchars($plan['insurance_company']); ?> Logo" 
             class="company-logo-img"
             onerror="this.style.display='none'">
    <?php else: ?>
        <i class='bx bxs-shield' style="font-size:20px; color:#9ca3af; margin-right:6px;"></i>
    <?php endif; ?>
    <?php echo htmlspecialchars($plan['insurance_company']); ?>
</div>
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
                                                                if (is_bool($v)) {
                                                                    $display = $v ? 'Yes' : 'No';
                                                                } else {
                                                                    $display = is_array($v) ? implode(', ', $v) : $v;
                                                                }
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
                    <p>Unfortunately, no insurance plans match your <?php 
                        if ($is_health) echo 'health profile';
                        else if ($is_life) echo 'life insurance profile';
                        else if ($is_property) echo 'property details';
                        else echo 'vehicle details';
                    ?>. Please contact our support team for assistance.</p>
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
                                        <div class="plan-company">
                                            <?php if (!empty($plan['logo'])): ?>
                                                <img src="/Graduation-Project/<?php echo htmlspecialchars($plan['logo']); ?>" 
                                                     alt="<?php echo htmlspecialchars($plan['insurance_company']); ?> Logo" 
                                                     class="company-logo-img"
                                                     onerror="this.style.display='none'">
                                            <?php else: ?>
                                                <i class='bx bxs-shield' style="font-size:20px; color:#9ca3af; margin-right:6px;"></i>
                                            <?php endif; ?>
                                            <?php echo htmlspecialchars($plan['insurance_company']); ?>
                                        </div>
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
