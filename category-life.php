<?php
// تفعيل السيسشن بأمان لضمان قراءة بيانات المستخدم المسجل
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/connection.php';
include 'includes/nav2.php';

// سحب بيانات الجلسة تلقائياً للمتأمن عليه
$user_name = isset($_SESSION['name']) ? $_SESSION['name'] : '';
$user_phone = isset($_SESSION['phone']) ? $_SESSION['phone'] : '';
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
                <h2>Secure Your Family's Future <i class='bx bxs-heart-letter'></i></h2>
                <p>Calculate your premium and protect the people you love the most</p>
            </div>

            <form id="lifeInsuranceForm">

                <h3>Primary member</h3>

                <div class="input-group">
                    <label>Birthdate*</label>
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1.2fr; gap: 15px;">
                        <input type="number" name="birth_day" placeholder="DD" min="1" max="31" required>
                        <input type="number" name="birth_month" placeholder="MM" min="1" max="12" required>
                        <input type="number" name="birth_year" placeholder="YYYY" min="1920" max="2026" required>
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
                            required>
                    </div>

                    <div class="input-group">
                        <label>Policy Duration</label>
                        <div class="custom-select-wrapper" id="policy-term-wrapper">
                            <div class="custom-select-trigger">
                                <span>Select Term</span>
                                <i class="fas fa-chevron-down"></i>
                            </div>
                            <div class="custom-options">
                                <span class="custom-option" data-value="10_years">10 Years</span>
                                <span class="custom-option" data-value="15_years">15 Years</span>
                                <span class="custom-option" data-value="20_years">20 Years</span>
                                <span class="custom-option" data-value="whole_life">Whole Life </span>
                            </div>
                        </div>
                        <input type="hidden" name="policy_term" id="policy-term-input" required>
                    </div>
                </div>

                <div class="input-row">
                    <div class="input-group">
                        <label for="beneficiary_name">Primary Beneficiary Name</label>
                        <input type="text" id="beneficiary_name" name="beneficiary_name"
                            placeholder="Who receives the payout?" required>
                    </div>

                    <div class="input-group">
                        <label>Relationship to You</label>
                        <div class="custom-select-wrapper" id="relation-wrapper">
                            <div class="custom-select-trigger">
                                <span>Select Relation</span>
                                <i class="fas fa-chevron-down"></i>
                            </div>
                            <div class="custom-options">
                                <span class="custom-option" data-value="spouse">Spouse</span>
                                <span class="custom-option" data-value="child">Child</span>
                                <span class="custom-option" data-value="parent">Parent</span>
                            </div>
                        </div>
                        <input type="hidden" name="beneficiary_relation" id="relation-input" required>
                    </div>
                </div>

                <button type="submit" class="check-btn" id="submitBtn">
                    <span class="btn-content" id="submitBtnText">
                        Show Results <i class="fa-solid fa-arrow-right btn-icon"></i>
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