<?php
// تفعيل السيسشن بأمان لضمان قراءة بيانات المستخدم المسجل
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/connection.php';
include 'includes/nav2.php';

// سحب بيانات الجلسة تلقائياً للمتأمن عليه
$user_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : '';
$user_phone = isset($_SESSION['user_phone']) ? $_SESSION['user_phone'] : '';
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
                                <span>Select Type</span>
                                <i class="fas fa-chevron-down"></i>
                            </div>
                            <div class="custom-options">
                                <span class="custom-option" data-value="apartment">Apartment</span>
                                <span class="custom-option" data-value="villa">Villa</span>
                                <span class="custom-option" data-value="office">Commercial Office</span>
                                <span class="custom-option" data-value="warehouse">Warehouse/Factory</span>
                            </div>
                        </div>
                        <input type="hidden" name="property_type" id="property-type-input" required>
                    </div>

                    <div class="input-group">
                        <label>Construction Year</label>
                        <div class="custom-select-wrapper" id="construction-year-wrapper">
                            <div class="custom-select-trigger">
                                <span>Select Year</span>
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
                        <input type="hidden" name="construction_year" id="construction-year-input" required>
                    </div>
                </div>

                <div class="input-row">
                    <div class="input-group">
                        <label for="property_value">Estimated Building Value (EGP)</label>
                        <input type="number" id="property_value" name="property_value" placeholder="e.g. 2000000" required>
                    </div>
                    <div class="input-group">
                        <label for="contents_value">Contents & Furniture Value (EGP)</label>
                        <input type="number" id="contents_value" name="contents_value" placeholder="e.g. 500000" required>
                    </div>
                </div>

                <div class="input-group">
                    <label>Required Coverage Type</label>
                    <div class="custom-select-wrapper" id="coverage-wrapper">
                        <div class="custom-select-trigger">
                            <span>Select Coverage Plan</span>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="custom-options">
                            <span class="custom-option" data-value="fire_natural">Fire & Natural Disasters Only</span>
                            <span class="custom-option" data-value="theft_burglary">Theft & Burglary Only</span>
                            <span class="custom-option" data-value="comprehensive">Comprehensive - All Risks</span>
                        </div>
                    </div>
                    <input type="hidden" name="coverage_type" id="coverage-input" required>
                </div>

                <div class="input-group">
                    <label for="property_address">Full Property Address</label>
                    <input type="text" id="property_address" name="property_address" placeholder="City, District, Street No." required style="width: 100%; padding: 12px 15px; border: 1px solid var(--border-color); border-radius: 6px; background-color: var(--hub-input-bg); font-size: 15px;">
                </div>

                <div class="radio-group">
                    <label class="radio-main-label">Property Usage Status:</label>
                    <div class="radio-options">
                        <label class="custom-radio">
                            <input type="radio" name="property_usage" value="owned" checked required>
                            <span class="radio-text">Owned</span>
                        </label>
                        <label class="custom-radio">
                            <input type="radio" name="property_usage" value="rented">
                            <span class="radio-text">Rented</span>
                        </label>
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
            <img src="/Graduation-Project/assets/img/home.jpg" alt="Property & Home Insurance">
        </div>

    </div>
</section>

<script src="/Graduation-Project/assets/js/category-property.js"></script>

<?php include 'includes/footer.php'; ?>