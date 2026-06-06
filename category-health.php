<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'includes/connection.php';

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $birth_day = isset($_POST['birth_day']) ? intval($_POST['birth_day']) : 0;
    $birth_month = isset($_POST['birth_month']) ? intval($_POST['birth_month']) : 0;
    $birth_year = isset($_POST['birth_year']) ? intval($_POST['birth_year']) : 0;

    $client_name = isset($_POST['client_name']) ? trim(mysqli_real_escape_string($connect, $_POST['client_name'])) : '';
    $client_phone = isset($_POST['phone']) ? trim(mysqli_real_escape_string($connect, $_POST['phone'])) : '';

    $family_chronic = isset($_POST['family_chronic']) ? trim($_POST['family_chronic']) : 'no';

    $has_spouse = false;
    $spouse_data = null;
    if (!empty($_POST['spouse_day']) && !empty($_POST['spouse_month']) && !empty($_POST['spouse_year'])) {
        $has_spouse = true;
        $spouse_data = [
            'day' => intval($_POST['spouse_day']),
            'month' => intval($_POST['spouse_month']),
            'year' => intval($_POST['spouse_year']),
        ];
    }

    $children = [];
    if (!empty($_POST['child_day']) && is_array($_POST['child_day'])) {
        $days = $_POST['child_day'];
        $months = $_POST['child_month'] ?? [];
        $years = $_POST['child_year'] ?? [];

        for ($i = 0; $i < count($days); $i++) {
            if (!empty($days[$i]) && !empty($months[$i]) && !empty($years[$i])) {
                $children[] = [
                    'day' => intval($days[$i]),
                    'month' => intval($months[$i]),
                    'year' => intval($years[$i]),
                ];
            }
        }
    }

    $age = 0;
    if ($birth_year > 0 && $birth_month > 0 && $birth_day > 0) {
        $birthdate = new DateTime("$birth_year-$birth_month-$birth_day");
        $today = new DateTime();
        $age = $today->diff($birthdate)->y;
    }

    $errors = [];
    if ($birth_day < 1 || $birth_day > 31)
        $errors[] = 'Valid birth day is required.';
    if ($birth_month < 1 || $birth_month > 12)
        $errors[] = 'Valid birth month is required.';
    if ($birth_year < 1920 || $birth_year > date('Y'))
        $errors[] = 'Valid birth year is required.';
    if ($age < 18)
        $errors[] = 'Primary member must be at least 18 years old.';

    if (!empty($errors)) {
        $_SESSION['health_form_error'] = implode(' ', $errors);
        header('Location: /Graduation-Project/category-health.php');
        exit;
    }

    $applicationData = [
        'category' => 'health',
        'client_name' => $client_name,
        'client_phone' => $client_phone,
        'birth_day' => $birth_day,
        'birth_month' => $birth_month,
        'birth_year' => $birth_year,
        'age' => $age,
        'family_chronic' => $family_chronic,
        'has_spouse' => $has_spouse,
        'spouse' => $spouse_data,
        'children' => $children,
        'num_children' => count($children),
        'submitted_at' => date('Y-m-d H:i:s'),
    ];

    $catResult = mysqli_query($connect, "SELECT category_id FROM categories WHERE name LIKE '%Medical%' OR name LIKE '%health%' LIMIT 1");
    if (!$catResult || mysqli_num_rows($catResult) === 0) {
        $catResult = mysqli_query($connect, "SELECT category_id FROM categories WHERE category_id = 2 LIMIT 1");
    }
    $catRow = mysqli_fetch_assoc($catResult);
    $category_id = $catRow ? intval($catRow['category_id']) : 2;

    $_SESSION['temp_application_data'] = $applicationData;
    $_SESSION['temp_category_id'] = $category_id;

    if (!isset($_SESSION['user_id'])) {
        $_SESSION['redirect_after_login'] = '/Graduation-Project/category-health.php';
        header('Location: /Graduation-Project/auth/login.php');
        exit;
    }

    header('Location: /Graduation-Project/plans.php');
    exit;
}

require_once 'includes/connection.php';
include 'includes/nav2.php';

// سحب بيانات الجلسة تلقائياً للمستخدم المسجل
$user_name = isset($_SESSION['name']) ? $_SESSION['name'] : '';
$phone = isset($_SESSION['phone']) ? $_SESSION['phone'] : '';

if (isset($_SESSION['user_id'])) {
    $user_id = intval($_SESSION['user_id']);
    $u_query = mysqli_query($connect, "SELECT name, phone FROM users WHERE user_id = $user_id");
    if ($u_query && mysqli_num_rows($u_query) > 0) {
        $u_row = mysqli_fetch_assoc($u_query);
        $user_name = $u_row['name'];
        $phone = $u_row['phone'];
        // Keep session in sync
        $_SESSION['name'] = $user_name;
        $_SESSION['phone'] = $phone;
    }
}

$draft = $_SESSION['temp_application_data'] ?? null;
$draft_bd = ($draft && isset($draft['category']) && $draft['category'] === 'health') ? $draft['birth_day'] : '';
$draft_bm = ($draft && isset($draft['category']) && $draft['category'] === 'health') ? $draft['birth_month'] : '';
$draft_by = ($draft && isset($draft['category']) && $draft['category'] === 'health') ? $draft['birth_year'] : '';
$draft_chronic = ($draft && isset($draft['category']) && $draft['category'] === 'health') ? $draft['family_chronic'] : 'no';
$draft_has_spouse = ($draft && isset($draft['category']) && $draft['category'] === 'health') ? $draft['has_spouse'] : false;
$draft_spouse = ($draft && isset($draft['category']) && $draft['category'] === 'health') ? $draft['spouse'] : null;
$draft_children = ($draft && isset($draft['category']) && $draft['category'] === 'health') ? $draft['children'] : [];
?>  

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<link rel="stylesheet" href="./assets/css/category-health.css?v=1.1">

<!-- ── Success / Error Modal ─────────────────────────────────────────────── -->
<div id="appModal" class="app-modal-overlay" style="display:none;">
    <div class="app-modal-box">
        <div class="app-modal-icon" id="appModalIcon"></div>
        <h3 id="appModalTitle"></h3>
        <p id="appModalMsg"></p>
        <button class="app-modal-btn" onclick="closeAppModal()">OK</button>
    </div>
</div>

<section class="insurance-health-section">
    <div class="insurance-main-container">
        
        <div class="insurance-form-side">
            <div class="insurance-form-header">
                <h1>Health insurance offers for individuals and families <span class="insurance-shield-icon"><i class="fa-solid fa-shield-heart"></i></span></h1>
                <p class="insurance-subtitle">Add your information and compare the best offers</p>
            </div>
            
            <form id="healthInsuranceForm">
                
                <h3 class="insurance-section-title">Primary member</h3>
                
                <div class="insurance-input-group">
                    <label>Birthdate*</label>
                    <div class="insurance-birthdate-grid">
                        <input type="number" name="birth_day" value="<?php echo htmlspecialchars($draft_bd); ?>" placeholder="DD" min="1" max="31" >
                        <input type="number" name="birth_month" value="<?php echo htmlspecialchars($draft_bm); ?>" placeholder="MM" min="1" max="12" >
                        <input type="number" name="birth_year" value="<?php echo htmlspecialchars($draft_by); ?>" placeholder="YYYY" min="1920" max="2026" >
                    </div>
                </div>

                <div class="insurance-input-group">
                    <label>Full name*</label>
                    <input type="text" name="client_name" value="<?php echo htmlspecialchars($user_name); ?>" placeholder="Full Name" readonly class="insurance-readonly-field">
                </div>

                <div class="insurance-input-group">
                    <label>Mobile Number</label>
                    <div class="insurance-phone-wrapper">
                        <div class="insurance-country-code">
                            <img src="https://flagcdn.com/w20/eg.png" alt="Egypt Flag">
                            <span>+20</span>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <input type="tel" name="phone" value="<?php echo htmlspecialchars($phone); ?>" placeholder="010 01234567" readonly class="insurance-readonly-field">
                        <i class="fa-solid fa-phone insurance-phone-icon-right"></i>
                    </div>
                </div>

                <div class="insurance-dynamic-container">
                    <button type="button" class="insurance-add-btn" id="addSpouseBtn" onclick="addSpouseField()" <?php echo $draft_has_spouse ? 'style="display:none;"' : ''; ?>>
                        <i class="fa-solid fa-circle-plus"></i> Add a spouse
                    </button>
                    
                    <div id="spouse-dynamic-area">
                        <?php if ($draft_has_spouse && $draft_spouse): ?>
                            <div class="insurance-member-card" id="spouse-card-node">
                                <button type="button" class="insurance-remove-btn" onclick="removeSpouseField()">
                                    <i class="fa-solid fa-trash-can"></i> Remove
                                </button>
                                <h4 style="margin-top:0; margin-bottom:12px; color:#111827; font-weight:600;">Spouse Birthdate</h4>
                                <div class="insurance-birthdate-grid">
                                    <input type="number" name="spouse_day" value="<?php echo htmlspecialchars($draft_spouse['day']); ?>" placeholder="DD" min="1" max="31" >
                                    <input type="number" name="spouse_month" value="<?php echo htmlspecialchars($draft_spouse['month']); ?>" placeholder="MM" min="1" max="12" >
                                    <input type="number" name="spouse_year" value="<?php echo htmlspecialchars($draft_spouse['year']); ?>" placeholder="YYYY" min="1920" max="2026" >
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <button type="button" class="insurance-add-btn" id="addChildBtn" onclick="addChildField()">
                        <i class="fa-solid fa-circle-plus"></i> Add a child
                    </button>

                    <div id="children-dynamic-area">
                        <?php if (!empty($draft_children)): ?>
                            <?php foreach ($draft_children as $idx => $child): $childNum = $idx + 1; ?>
                                <div class="insurance-member-card" id="child-card-<?php echo $childNum; ?>">
                                    <button type="button" class="insurance-remove-btn" onclick="removeChildField(<?php echo $childNum; ?>)">
                                        <i class="fa-solid fa-trash-can"></i> Remove
                                    </button>
                                    <h4 style="margin-top:0; margin-bottom:12px; color:#111827; font-weight:600;">Child <?php echo $childNum; ?> Birthdate</h4>
                                    <div class="insurance-birthdate-grid">
                                        <input type="number" name="child_day[]" value="<?php echo htmlspecialchars($child['day']); ?>" placeholder="DD" min="1" max="31" >
                                        <input type="number" name="child_month[]" value="<?php echo htmlspecialchars($child['month']); ?>" placeholder="MM" min="1" max="12" >
                                        <input type="number" name="child_year[]" value="<?php echo htmlspecialchars($child['year']); ?>" placeholder="YYYY" min="1920" max="2026" >
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <script>
                                document.addEventListener('DOMContentLoaded', function() {
                                    childCounter = <?php echo count($draft_children); ?>;
                                });
                            </script>
                        <?php endif; ?>
                    </div>
                </div>

                <hr class="insurance-divider">

                <div class="insurance-chronic-group">
                    <label class="insurance-chronic-label">Does any of your family members need monthly treatment or suffer from a chronic disease?*</label>
                    <div class="insurance-radio-options">
                        <label class="insurance-custom-radio">
                            <input type="radio" name="family_chronic" value="yes" <?php echo $draft_chronic === 'yes' ? 'checked' : ''; ?> >
                            <span class="insurance-radio-circle"></span>
                            <span class="insurance-radio-text">Yes</span>
                        </label>
                        <label class="insurance-custom-radio">
                            <input type="radio" name="family_chronic" value="no" <?php echo $draft_chronic === 'no' ? 'checked' : ''; ?> >
                            <span class="insurance-radio-circle"></span>
                            <span class="insurance-radio-text">No</span>
                        </label>
                    </div>
                </div>

                <div class="insurance-actions-footer">
                    <button type="submit" class="insurance-submit-btn" id="submitBtn">
                        <span id="submitBtnText"><i class="fa-solid fa-arrow-right"></i> Show Results</span>
                        <span id="submitBtnLoader" style="display:none;"><i class="fa-solid fa-spinner fa-spin"></i> Loading...</span>
                    </button>
                    <!-- <a href="index.php" class="insurance-cancel-link">Cancel</a> -->
                </div>
                           
            </form>
        </div>

        <div class="insurance-image-side">
            <div class="insurance-image-sticky-wrapper">
                <img src="/Graduation-Project/assets/img/health2.jpg" alt="Family Health Insurance">
                <div class="insurance-shape-bottom"></div>
            </div>
        </div>

    </div>
</section>

<script src="./assets/js/category-health.js?v=1.3"></script>
<?php include 'includes/footer.php'; ?>


