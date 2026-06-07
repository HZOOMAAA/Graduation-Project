<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'includes/connection.php';

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    header('Content-Type: application/json');

    $brand     = isset($_POST['brand'])     ? trim(mysqli_real_escape_string($connect, $_POST['brand']))     : '';
    $model     = isset($_POST['model'])     ? trim(mysqli_real_escape_string($connect, $_POST['model']))     : '';
    $year      = isset($_POST['year'])      ? intval($_POST['year'])                                         : 0;
    $raw_price = isset($_POST['price'])     ? str_replace(',', '', $_POST['price'])                         : '0';
    $price     = floatval($raw_price);
    $condition = isset($_POST['condition']) ? trim(mysqli_real_escape_string($connect, $_POST['condition'])) : '';

    $errors = [];
    if (empty($brand))     $errors[] = 'Car brand is required.';
    if (empty($model))     $errors[] = 'Car model is required.';
    if ($year < 1900)      $errors[] = 'Valid manufacture year is required.';
    if ($price < 100000)   $errors[] = 'Estimated price must be at least 100,000 LE.';
    if (!in_array($condition, ['new', 'used'])) $errors[] = 'Car condition must be new or used.';

    if (!empty($errors)) {
        echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
        exit;
    }

    $applicationData = json_encode([
        'category'  => 'car',
        'brand'     => $brand,
        'model'     => $model,
        'year'      => $year,
        'price'     => $price,
        'condition' => $condition,
        'submitted_at' => date('Y-m-d H:i:s'),
    ]);

    $customer_id = $_SESSION['user_id'] ?? null;

    $catResult = mysqli_query($connect, "SELECT category_id FROM categories WHERE name LIKE '%car%' LIMIT 1");
    if (!$catResult || mysqli_num_rows($catResult) === 0) {
        $catResult = mysqli_query($connect, "SELECT category_id FROM categories ORDER BY category_id ASC LIMIT 1");
    }
    $catRow     = mysqli_fetch_assoc($catResult);
    $category_id = $catRow ? intval($catRow['category_id']) : 1;

    $_SESSION['temp_application_data'] = [
        'category'  => 'car',
        'brand'     => $brand,
        'model'     => $model,
        'year'      => $year,
        'price'     => $price,
        'condition' => $condition,
        'submitted_at' => date('Y-m-d H:i:s'),
    ];
    $_SESSION['temp_category_id'] = $category_id;

    if (!isset($_SESSION['user_id'])) {
        $_SESSION['redirect_after_login'] = '/Graduation-Project/category-car.php';
        echo json_encode([
            'success' => false,
            'login_required' => true,
            'redirect_url' => '/Graduation-Project/auth/login.php'
        ]);
        exit;
    }

    echo json_encode([
        'success' => true,
        'message' => 'Car details saved to session draft successfully!',
    ]);
    exit;
}

require_once 'includes/connection.php';
include 'includes/nav2.php';

$draft = $_SESSION['temp_application_data'] ?? null;
$draft_brand = ($draft && isset($draft['category']) && $draft['category'] === 'car') ? $draft['brand'] : '';
$draft_model = ($draft && isset($draft['category']) && $draft['category'] === 'car') ? $draft['model'] : '';
$draft_year = ($draft && isset($draft['category']) && $draft['category'] === 'car') ? $draft['year'] : '';
$draft_price = ($draft && isset($draft['category']) && $draft['category'] === 'car') ? $draft['price'] : '';
$draft_condition = ($draft && isset($draft['category']) && $draft['category'] === 'car') ? $draft['condition'] : '';
?>  
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>

<link rel="stylesheet" href="/Graduation-Project/assets/css/category.css">

<!-- ── Success / Error Modal ─────────────────────────────────────────────── -->
<div id="appModal" class="app-modal-overlay" style="display:none;">
    <div class="app-modal-box">
        <div class="app-modal-icon" id="appModalIcon"></div>
        <h3 id="appModalTitle"></h3>
        <p id="appModalMsg"></p>
        <button class="app-modal-btn" onclick="closeAppModal()">OK</button>
    </div>
</div>

<section class="car-insurance-section">
    <div class="container grid-container">
        
        <div class="form-container">
            <div class="header">
                <h1>Best car insurance offers and prices for you  <i class='bx bxs-car-crash'></i></h1>
                <p>Insert car details to compare and pick the best offer</p>
            </div>
            
            <!-- action handled via JS/AJAX → submit_car_application.php -->
            <form id="carInsuranceForm">
                
                <div class="input-group">
                    <label>Car Brand</label>
                    <div class="custom-select-wrapper" id="brand-wrapper">
                        <div class="custom-select-trigger">
                            <span><?php echo $draft_brand ? htmlspecialchars(ucwords($draft_brand)) : 'Select Brand'; ?></span>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="custom-options" id="brand-options"></div>
                    </div>
                    <input type="hidden" name="brand" id="brand-input" value="<?php echo htmlspecialchars($draft_brand); ?>" >
                </div>

                <div class="input-group">
                    <label>Car Model</label>
                    <div class="custom-select-wrapper" id="model-wrapper">
                        <div class="custom-select-trigger">
                            <span><?php echo $draft_model ? htmlspecialchars(ucwords($draft_model)) : 'Select Model First'; ?></span>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="custom-options" id="model-options"></div>
                    </div>
                    <input type="hidden" name="model" id="model-input" value="<?php echo htmlspecialchars($draft_model); ?>">
                </div>

                    <div class="input-group">
                        <label>Manufacture Year</label>
                            <div class="custom-select-wrapper">
                                <div class="custom-select-trigger">
                                    <span><?php echo $draft_year ? htmlspecialchars($draft_year) : 'Select Year'; ?></span>
                                    <i class="fas fa-chevron-down"></i>
                                </div>
                                <div class="custom-options">
                                    <span class="custom-option" data-value="2024">2024</span>
                                    <span class="custom-option" data-value="2023">2023</span>
                                    <span class="custom-option" data-value="2022">2022</span>
                                    <span class="custom-option" data-value="2021">2021</span>
                                    <span class="custom-option" data-value="2020">2020</span>
                                </div>
                            </div>
                                <input type="hidden" name="year" id="year-input" value="<?php echo htmlspecialchars($draft_year); ?>" >                     
                             </div>

                <div class="input-group">
                    <label for="price">Estimated Price (EGP)</label>
                    <input type="text" id="price" name="price" placeholder="e.g. 500,000" value="<?php echo (is_numeric($draft_price) && $draft_price > 0) ? htmlspecialchars(number_format($draft_price)) : ''; ?>">
                </div>

                <div class="radio-group">
                    <label class="radio-main-label">Car Condition:</label>
                    <div class="radio-options">
                        <label class="custom-radio">
                            <input type="radio" name="condition" value="new" <?php echo $draft_condition === 'new' ? 'checked' : ''; ?> >
                            <span class="radio-text">New</span>
                        </label>
                        <label class="custom-radio">
                            <input type="radio" name="condition" value="used" <?php echo $draft_condition === 'used' ? 'checked' : ''; ?>>
                            <span class="radio-text">Used</span>
                        </label>
                    </div>
                </div>

                <div class="insurance-actions-footer">
                    <button type="submit" class="insurance-submit-btn" id="submitBtn">
                        <span id="submitBtnText"><i class="fa-solid fa-arrow-right"></i> Show Results</span>
                        <span id="submitBtnLoader" style="display:none;"><i class="fa-solid fa-spinner fa-spin"></i> Loading...</span>
                    </button>
                </div>
            </form>
        </div>

        <div class="image-container">
            <img src="/Graduation-Project/assets/img/car.jpg" alt="Car Insurance">
        </div>

    </div>
</section>
<script src="/Graduation-Project/assets/js/category.js"></script>
<script src="/Graduation-Project/assets/js/car_application.js"></script>

<?php include 'includes/footer.php'; ?>  