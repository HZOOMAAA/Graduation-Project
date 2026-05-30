<?php
require_once 'includes/connection.php';
include 'includes/nav2.php';

// سحب بيانات الجلسة تلقائياً للمستخدم المسجل
$user_name = isset($_SESSION['name']) ? $_SESSION['name'] : '';
$user_phone = isset($_SESSION['phone']) ? $_SESSION['phone'] : '';
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
                <h1>Health insurance offers and prices for individuals and families in Egypt <span class="insurance-shield-icon"><i class="fa-solid fa-shield-heart"></i></span></h1>
                <p class="insurance-subtitle">Add your information and compare the best offers</p>
            </div>
            
            <form id="healthInsuranceForm">
                
                <h3 class="insurance-section-title">Primary member</h3>
                
                <div class="insurance-input-group">
                    <label>Birthdate*</label>
                    <div class="insurance-birthdate-grid">
                        <input type="number" name="birth_day" placeholder="DD" min="1" max="31" required>
                        <input type="number" name="birth_month" placeholder="MM" min="1" max="12" required>
                        <input type="number" name="birth_year" placeholder="YYYY" min="1920" max="2026" required>
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
                        <input type="tel" name="client_phone" value="<?php echo htmlspecialchars($user_phone); ?>" placeholder="010 01234567" readonly class="insurance-readonly-field">
                        <i class="fa-solid fa-phone insurance-phone-icon-right"></i>
                    </div>
                </div>

                <div class="insurance-dynamic-container">
                    <button type="button" class="insurance-add-btn" id="addSpouseBtn" onclick="addSpouseField()">
                        <i class="fa-solid fa-circle-plus"></i> Add a spouse
                    </button>
                    
                    <div id="spouse-dynamic-area"></div>

                    <button type="button" class="insurance-add-btn" id="addChildBtn" onclick="addChildField()">
                        <i class="fa-solid fa-circle-plus"></i> Add a child
                    </button>

                    <div id="children-dynamic-area"></div>
                </div>

                <hr class="insurance-divider">

                <div class="insurance-chronic-group">
                    <label class="insurance-chronic-label">Does any of your family members need monthly treatment or suffer from a chronic disease?*</label>
                    <div class="insurance-radio-options">
                        <label class="insurance-custom-radio">
                            <input type="radio" name="family_chronic" value="yes" required>
                            <span class="insurance-radio-circle"></span>
                            <span class="insurance-radio-text">Yes</span>
                        </label>
                        <label class="insurance-custom-radio">
                            <input type="radio" name="family_chronic" value="no" checked>
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
                    <a href="index.php" class="insurance-cancel-link">Cancel</a>
                </div>
                           
            </form>
        </div>

        <div class="insurance-image-side">
            <div class="insurance-image-sticky-wrapper">
                <img src="/Graduation-Project/assets/img/family.jpg" alt="Family Health Insurance">
                <div class="insurance-shape-bottom"></div>
            </div>
        </div>

    </div>
</section>

<script src="./assets/js/category-health.js?v=1.3"></script>
<?php include 'includes/footer.php'; ?>

booobobobobob