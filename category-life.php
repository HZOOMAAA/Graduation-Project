<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'includes/connection.php';

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    header('Content-Type: application/json');

    $birth_day   = isset($_POST['birth_day'])   ? intval($_POST['birth_day'])   : 0;
    $birth_month = isset($_POST['birth_month']) ? intval($_POST['birth_month']) : 0;
    $birth_year  = isset($_POST['birth_year'])  ? intval($_POST['birth_year'])  : 0;

    $client_name  = isset($_POST['client_name'])  ? trim(mysqli_real_escape_string($connect, $_POST['client_name']))  : '';
    $client_phone = isset($_SESSION['phone'])     ? trim(mysqli_real_escape_string($connect, $_SESSION['phone']))     : '';

    $coverage_amount      = isset($_POST['coverage_amount'])      ? floatval($_POST['coverage_amount'])                      : 0;
    $policy_term          = isset($_POST['policy_term'])          ? trim(mysqli_real_escape_string($connect, $_POST['policy_term'])) : '';
    $beneficiary_name     = isset($_POST['beneficiary_name'])     ? trim(mysqli_real_escape_string($connect, $_POST['beneficiary_name'])) : '';
    $beneficiary_relation = isset($_POST['beneficiary_relation']) ? trim(mysqli_real_escape_string($connect, $_POST['beneficiary_relation'])) : '';

    $age = 0;
    if ($birth_year > 0 && $birth_month > 0 && $birth_day > 0) {
        $birthdate = new DateTime("$birth_year-$birth_month-$birth_day");
        $today     = new DateTime();
        $age       = $today->diff($birthdate)->y;
    }

    $errors = [];
    if ($birth_day < 1 || $birth_day > 31)     $errors[] = 'Valid birth day is required.';
    if ($birth_month < 1 || $birth_month > 12) $errors[] = 'Valid birth month is required.';
    if ($birth_year < 1920 || $birth_year > date('Y')) $errors[] = 'Valid birth year is required.';
    if ($age < 18)                             $errors[] = 'Applicant must be at least 18 years old to apply for life insurance.';
    if ($coverage_amount <= 0)                 $errors[] = 'Insurance coverage amount must be positive.';
    if (empty($policy_term))                   $errors[] = 'Policy term is required.';
    if (empty($beneficiary_name))              $errors[] = 'Beneficiary name is required.';
    if (empty($beneficiary_relation))          $errors[] = 'Beneficiary relationship is required.';

    if (!empty($errors)) {
        echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
        exit;
    }

    $applicationData = [
        'category'             => 'life',
        'client_name'          => $client_name,
        'client_phone'         => $client_phone,
        'birth_day'            => $birth_day,
        'birth_month'          => $birth_month,
        'birth_year'           => $birth_year,
        'age'                  => $age,
        'coverage_amount'      => $coverage_amount,
        'policy_term'          => $policy_term,
        'beneficiary_name'     => $beneficiary_name,
        'beneficiary_relation' => $beneficiary_relation,
        'submitted_at'         => date('Y-m-d H:i:s'),
    ];

    $catResult = mysqli_query($connect, "SELECT category_id FROM categories WHERE name LIKE '%Life%' LIMIT 1");
    if (!$catResult || mysqli_num_rows($catResult) === 0) {
        mysqli_query($connect, "INSERT INTO categories (name) VALUES ('Life Insurance')");
        $category_id = mysqli_insert_id($connect);
    } else {
        $catRow      = mysqli_fetch_assoc($catResult);
        $category_id = intval($catRow['category_id']);
    }

    $_SESSION['temp_application_data'] = $applicationData;
    $_SESSION['temp_category_id']      = $category_id;

    if (!isset($_SESSION['user_id'])) {
        $_SESSION['redirect_after_login'] = '/Graduation-Project/category-life.php';
        echo json_encode([
            'success' => false,
            'login_required' => true,
            'redirect_url' => '/Graduation-Project/auth/login.php'
        ]);
        exit;
    }

    echo json_encode([
        'success' => true,
        'message' => 'Life insurance application draft saved successfully!',
    ]);
    exit;
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/connection.php';
include 'includes/nav2.php';

// سحب بيانات الجلسة تلقائياً للمتأمن عليه
$user_name = isset($_SESSION['name']) ? $_SESSION['name'] : '';
$user_phone = isset($_SESSION['phone']) ? $_SESSION['phone'] : '';

$draft = $_SESSION['temp_application_data'] ?? null;
$draft_bd = ($draft && isset($draft['category']) && $draft['category'] === 'life') ? $draft['birth_day'] : '';
$draft_bm = ($draft && isset($draft['category']) && $draft['category'] === 'life') ? $draft['birth_month'] : '';
$draft_by = ($draft && isset($draft['category']) && $draft['category'] === 'life') ? $draft['birth_year'] : '';
$draft_coverage = ($draft && isset($draft['category']) && $draft['category'] === 'life') ? $draft['coverage_amount'] : '';
$draft_term = ($draft && isset($draft['category']) && $draft['category'] === 'life') ? $draft['policy_term'] : '';
$draft_beneficiary = ($draft && isset($draft['category']) && $draft['category'] === 'life') ? $draft['beneficiary_name'] : '';
$draft_relation = ($draft && isset($draft['category']) && $draft['category'] === 'life') ? $draft['beneficiary_relation'] : '';
?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<link href="https://cdn.jsdelivr.net/npm/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
<link rel="stylesheet" href="/Graduation-Project/assets/css/category-life.css">

<div id="appModal" class="app-modal-overlay" style="display:none;">
    <div class="app-modal-box">
        <div class="app-modal-icon" id="appModalIcon"></div>
        <h3 id="appModalTitle"></h3>
        <p id="appModalMsg"></p>
        <button class="app-modal-btn" onclick="closeAppModal()">OK</button>
    </div>
</div>

<section class="car-insurance-section">
    <div class="grid-container">

        <div class="form-container">
            <div class="header">
                <h2>Secure Your Family's Future <i class='bx bx-calendar-heart'></i></h2>
                <p>Calculate your premium and protect the people you love the most</p>
            </div>

            <form id="lifeInsuranceForm">

                <h3>Primary member</h3>

                <div class="input-group">
                    <label>Birthdate*</label>
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1.2fr; gap: 15px;">
                        <input type="number" id="birth_day" name="birth_day" value="<?php echo htmlspecialchars($draft_bd); ?>" placeholder="DD" min="1" max="31" >
                        <input type="number" id="birth_month" name="birth_month" value="<?php echo htmlspecialchars($draft_bm); ?>" placeholder="MM" min="1" max="12" >
                        <input type="number" id="birth_year" name="birth_year" value="<?php echo htmlspecialchars($draft_by); ?>" placeholder="YYYY" min="1920" max="2026" >
                    </div>
                </div>

                <div class="input-group">
                    <label>Full name*</label>
                    <input type="text" name="client_name" value="<?php echo htmlspecialchars($user_name); ?>"
                        placeholder="Full Name" readonly style="opacity: 0.75; cursor: not-allowed;">
                </div>

                <div class="input-row">
                    <div class="input-group">
                        <label for="coverage_amount">Insurance Coverage Amount (EGP)</label>
                        <input type="number" id="coverage_amount" name="coverage_amount" placeholder="e.g. 1000000"
                            value="<?php echo htmlspecialchars($draft_coverage); ?>" >
                    </div>

                    <div class="input-group">
                        <label>Policy Duration</label>
                        <div class="custom-select-wrapper" id="policy-term-wrapper">
                            <div class="custom-select-trigger">
                                <span><?php 
                                    if ($draft_term === '10_years') echo '10 Years';
                                    elseif ($draft_term === '15_years') echo '15 Years';
                                    elseif ($draft_term === '20_years') echo '20 Years';
                                    elseif ($draft_term === 'whole_life') echo 'Whole Life';
                                    else echo 'Select Term';
                                ?></span>
                                <i class="fas fa-chevron-down"></i>
                            </div>
                            <div class="custom-options">
                                <span class="custom-option" data-value="10_years">10 Years</span>
                                <span class="custom-option" data-value="15_years">15 Years</span>
                                <span class="custom-option" data-value="20_years">20 Years</span>
                                <span class="custom-option" data-value="whole_life">Whole Life </span>
                            </div>
                        </div>
                        <input type="hidden" name="policy_term" id="policy-term-input" value="<?php echo htmlspecialchars($draft_term); ?>" >
                    </div>
                </div>

                <div class="input-row">
                    <div class="input-group">
                        <label for="beneficiary_name">Primary Beneficiary Name</label>
                        <input type="text" id="beneficiary_name" name="beneficiary_name"
                            placeholder="Who receives the payout?" value="<?php echo htmlspecialchars($draft_beneficiary); ?>" >
                    </div>

                    <div class="input-group">
                        <label>Relationship to You</label>
                        <div class="custom-select-wrapper" id="relation-wrapper">
                            <div class="custom-select-trigger">
                                <span><?php
                                    if ($draft_relation === 'spouse') echo 'Spouse';
                                    elseif ($draft_relation === 'child') echo 'Child';
                                    elseif ($draft_relation === 'parent') echo 'Parent';
                                    else echo 'Select Relation';
                                ?></span>
                                <i class="fas fa-chevron-down"></i>
                            </div>
                            <div class="custom-options">
                                <span class="custom-option" data-value="spouse">Spouse</span>
                                <span class="custom-option" data-value="child">Child</span>
                                <span class="custom-option" data-value="parent">Parent</span>
                            </div>
                        </div>
                        <input type="hidden" name="beneficiary_relation" id="relation-input" value="<?php echo htmlspecialchars($draft_relation); ?>" >
                    </div>
                </div>

                <button type="submit" class="check-btn" id="submitBtn">
                    <span class="btn-content" id="submitBtnText">
                        Check Offers <i class="fa-solid fa-arrow-right btn-icon"></i>
                    </span>
                    <span id="submitBtnLoader" style="display:none;">
                        <i class="fas fa-spinner fa-spin"></i> Submitting...
                    </span>
                </button>

            </form>
        </div>

        <div class="image-container">
            <img src="/Graduation-Project/assets/img/lifeinsuerance.jpg" alt="Family Future Insurance">
        </div>

    </div>
</section>

<script src="/Graduation-Project/assets/js/category-life.js?v=1.3"></script>
<?php include 'includes/footer.php'; ?>