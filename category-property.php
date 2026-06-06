<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'includes/connection.php';

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    header('Content-Type: application/json');

    $property_type      = isset($_POST['property_type'])      ? trim(mysqli_real_escape_string($connect, $_POST['property_type']))      : '';
    $construction_year  = isset($_POST['construction_year'])  ? trim(mysqli_real_escape_string($connect, $_POST['construction_year']))  : '';
    $property_value     = isset($_POST['property_value'])     ? floatval($_POST['property_value'])                                      : 0;
    $contents_value     = isset($_POST['contents_value'])     ? floatval($_POST['contents_value'])                                      : 0;
    $coverage_type      = isset($_POST['coverage_type'])      ? trim(mysqli_real_escape_string($connect, $_POST['coverage_type']))      : '';
    $property_address   = isset($_POST['property_address'])   ? trim(mysqli_real_escape_string($connect, $_POST['property_address']))   : '';
    $property_usage     = isset($_POST['property_usage'])     ? trim(mysqli_real_escape_string($connect, $_POST['property_usage']))     : 'owned';

    $client_name  = isset($_SESSION['name'])  ? trim(mysqli_real_escape_string($connect, $_SESSION['name']))  : 'Valued Customer';
    $client_phone = isset($_SESSION['phone']) ? trim(mysqli_real_escape_string($connect, $_SESSION['phone'])) : '';

    $errors = [];
    if (empty($property_type))     $errors[] = 'Property type is required.';
    if (empty($construction_year)) $errors[] = 'Construction year is required.';
    if ($property_value <= 0)      $errors[] = 'Property building value must be a positive number.';
    if ($contents_value < 0)       $errors[] = 'Contents value cannot be negative.';
    if (empty($coverage_type))     $errors[] = 'Coverage type is required.';
    if (empty($property_address))   $errors[] = 'Property address is required.';

    if (!empty($errors)) {
        echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
        exit;
    }

    $applicationData = [
        'category'          => 'property',
        'client_name'       => $client_name,
        'client_phone'      => $client_phone,
        'property_type'     => $property_type,
        'construction_year' => $construction_year,
        'property_value'    => $property_value,
        'contents_value'    => $contents_value,
        'coverage_type'     => $coverage_type,
        'property_address'  => $property_address,
        'property_usage'    => $property_usage,
        'submitted_at'      => date('Y-m-d H:i:s'),
    ];

    $catResult = mysqli_query($connect, "SELECT category_id FROM categories WHERE name LIKE '%Property%' LIMIT 1");
    if (!$catResult || mysqli_num_rows($catResult) === 0) {
        mysqli_query($connect, "INSERT INTO categories (name) VALUES ('Property Insurance')");
        $category_id = mysqli_insert_id($connect);
    } else {
        $catRow      = mysqli_fetch_assoc($catResult);
        $category_id = intval($catRow['category_id']);
    }

    $_SESSION['temp_application_data'] = $applicationData;
    $_SESSION['temp_category_id']      = $category_id;

    if (!isset($_SESSION['user_id'])) {
        $_SESSION['redirect_after_login'] = '/Graduation-Project/category-property.php';
        echo json_encode([
            'success' => false,
            'login_required' => true,
            'redirect_url' => '/Graduation-Project/auth/login.php'
        ]);
        exit;
    }

    echo json_encode([
        'success' => true,
        'message' => 'Property insurance application draft saved successfully!',
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

if (isset($_SESSION['user_id'])) {
    $user_id = intval($_SESSION['user_id']);
    $u_query = mysqli_query($connect, "SELECT name, phone FROM users WHERE user_id = $user_id");
    if ($u_query && mysqli_num_rows($u_query) > 0) {
        $u_row = mysqli_fetch_assoc($u_query);
        $user_name = $u_row['name'];
        $user_phone = $u_row['phone'];
        // Keep session in sync
        $_SESSION['name'] = $user_name;
        $_SESSION['phone'] = $user_phone;
    }
}

$draft = $_SESSION['temp_application_data'] ?? null;
$draft_type = ($draft && isset($draft['category']) && $draft['category'] === 'property') ? $draft['property_type'] : '';
$draft_year = ($draft && isset($draft['category']) && $draft['category'] === 'property') ? $draft['construction_year'] : '';
$draft_prop_val = ($draft && isset($draft['category']) && $draft['category'] === 'property') ? $draft['property_value'] : '';
$draft_cont_val = ($draft && isset($draft['category']) && $draft['category'] === 'property') ? $draft['contents_value'] : '';
$draft_coverage = ($draft && isset($draft['category']) && $draft['category'] === 'property') ? $draft['coverage_type'] : '';
$draft_address = ($draft && isset($draft['category']) && $draft['category'] === 'property') ? $draft['property_address'] : '';
$draft_usage = ($draft && isset($draft['category']) && $draft['category'] === 'property') ? $draft['property_usage'] : 'owned';
?>  

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<link href="https://cdn.jsdelivr.net/npm/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
<link rel="stylesheet" href="/Graduation-Project/assets/css/category-property.css">

<div id="appModal" class="app-modal-overlay" style="display:none;">
    <div class="app-modal-box">
        <div class="app-modal-icon" id="appModalIcon"></div>
        <h3 id="appModalTitle"></h3>
        <p id="appModalMsg"></p>
        <button class="app-modal-btn" onclick="closeAppModal()">OK</button>
    </div>
</div>

<section class="car-insurance-section"> <div class="grid-container">
        
        <div class="form-container">
            <div class="header">
                <h2>Secure Your Property & Assets <i class='bx bxs-home-heart'></i></h2>
                <p>Insert property info to get the best security and coverage prices</p>
            </div>
            
            <form id="propertyInsuranceForm">
                
                <div class="input-row">
                    <div class="input-group">
                        <label>Property Type</label>
                        <div class="custom-select-wrapper" id="property-type-wrapper">
                            <div class="custom-select-trigger">
                                <span><?php 
                                    if ($draft_type === 'apartment') echo 'Apartment';
                                    elseif ($draft_type === 'villa') echo 'Villa';
                                    elseif ($draft_type === 'office') echo 'Commercial Office';
                                    elseif ($draft_type === 'warehouse') echo 'Warehouse/Factory';
                                    else echo 'Select Type';
                                ?></span>
                                <i class="fas fa-chevron-down"></i>
                            </div>
                            <div class="custom-options">
                                <span class="custom-option" data-value="apartment">Apartment</span>
                                <span class="custom-option" data-value="villa">Villa</span>
                                <span class="custom-option" data-value="office">Commercial Office</span>
                                <span class="custom-option" data-value="warehouse">Warehouse/Factory</span>
                            </div>
                        </div>
                        <input type="hidden" name="property_type" id="property-type-input" value="<?php echo htmlspecialchars($draft_type); ?>">
                    </div>

                    <div class="input-group">
                        <label>Construction Year</label>
                        <div class="custom-select-wrapper" id="construction-year-wrapper">
                            <div class="custom-select-trigger">
                                <span><?php 
                                    if ($draft_year === 'before_2020') echo 'Before 2020';
                                    elseif ($draft_year) echo htmlspecialchars($draft_year);
                                    else echo 'Select Year';
                                ?></span>
                                <i class="fas fa-chevron-down"></i>
                            </div>
                            <div class="custom-options">
                                <span class="custom-option" data-value="2026">2026</span>
                                <span class="custom-option" data-value="2025">2025</span>
                                <span class="custom-option" data-value="2024">2024</span>
                                <span class="custom-option" data-value="2023">2023</span>
                                <span class="custom-option" data-value="2022">2022</span>
                                <span class="custom-option" data-value="before_2020">Before 2020</span>
                            </div>
                        </div>
                        <input type="hidden" name="construction_year" id="construction-year-input" value="<?php echo htmlspecialchars($draft_year); ?>" >
                    </div>
                </div>

                <div class="input-row">
                    <div class="input-group">
                        <label for="property_value">Estimated Building Value (EGP)</label>
                        <input type="number" id="property_value" name="property_value" placeholder="e.g. 2000000" value="<?php echo htmlspecialchars($draft_prop_val); ?>">
                    </div>
                    <div class="input-group">
                        <label for="contents_value">Contents & Furniture Value (EGP)</label>
                        <input type="number" id="contents_value" name="contents_value" placeholder="e.g. 500000" value="<?php echo htmlspecialchars($draft_cont_val); ?>">
                    </div>
                </div>

                <div class="input-group">
                    <label>Required Coverage Type</label>
                    <div class="custom-select-wrapper" id="coverage-wrapper">
                        <div class="custom-select-trigger">
                            <span><?php 
                                if ($draft_coverage === 'fire_natural') echo 'Fire & Natural Disasters Only';
                                elseif ($draft_coverage === 'theft_burglary') echo 'Theft & Burglary Only';
                                elseif ($draft_coverage === 'comprehensive') echo 'Comprehensive - All Risks';
                                else echo 'Select Coverage Plan';
                            ?></span>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="custom-options">
                            <span class="custom-option" data-value="fire_natural">Fire & Natural Disasters Only</span>
                            <span class="custom-option" data-value="theft_burglary">Theft & Burglary Only</span>
                            <span class="custom-option" data-value="comprehensive">Comprehensive - All Risks</span>
                        </div>
                    </div>
                    <input type="hidden" name="coverage_type" id="coverage-input" value="<?php echo htmlspecialchars($draft_coverage); ?>">
                </div>

                <div class="input-group">
                    <label for="property_address">Full Property Address</label>
                    <input type="text" id="property_address" name="property_address" placeholder="City, District, Street No." value="<?php echo htmlspecialchars($draft_address); ?>" style="width: 100%; padding: 12px 15px; border: 1px solid var(--border-color); border-radius: 6px; background-color: var(--hub-input-bg); font-size: 15px;">
                </div>

                <div class="radio-group">
                    <label class="radio-main-label">Property Usage Status:</label>
                    <div class="radio-options">
                        <label class="custom-radio">
                            <input type="radio" name="property_usage" value="owned" <?php echo $draft_usage === 'owned' ? 'checked' : ''; ?> >
                            <span class="radio-text">Owned</span>
                        </label>
                        <label class="custom-radio">
                            <input type="radio" name="property_usage" value="rented" <?php echo $draft_usage === 'rented' ? 'checked' : ''; ?>>
                            <span class="radio-text">Rented</span>
                        </label>
                    </div>
                </div>

                <button type="submit" class="check-btn" id="submitBtn">
                    <span class="btn-content" id="submitBtnText">
                       <i class="fa-solid fa-arrow-right btn-icon"></i> Check Offers 
                    </span>
                    <span id="submitBtnLoader" style="display:none;">
                        <i class="fas fa-spinner fa-spin"></i> Submitting...
                    </span>
                </button>
            </form>
        </div>

        <div class="image-container">
            <img src="/Graduation-Project/assets/img/home.jpg" alt="Property & Home Insurance">
        </div>

    </div>
</section>

<script src="/Graduation-Project/assets/js/category-property.js?v=1.1"></script>

<?php include 'includes/footer.php'; ?>