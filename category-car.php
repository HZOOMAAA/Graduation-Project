<?php
require_once 'includes/connection.php';
include 'includes/header.php';
?>  
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
                <h2>Best car insurance offers and prices <i class='bx bxs-car-crash'></i></h2>
                <p>Insert car details to compare and pick the best offer</p>
            </div>
            
            <!-- action handled via JS/AJAX → submit_car_application.php -->
            <form id="carInsuranceForm">
                
                <div class="input-group">
    <label>Car Brand</label>
    <div class="custom-select-wrapper" id="brand-wrapper">
        <div class="custom-select-trigger">
            <span>Select Brand</span>
            <i class="fas fa-chevron-down"></i>
        </div>
        <div class="custom-options" id="brand-options"></div>
    </div>
    <input type="hidden" name="brand" id="brand-input" required>
</div>

<div class="input-group">
    <label>Car Model</label>
    <div class="custom-select-wrapper" id="model-wrapper">
        <div class="custom-select-trigger">
            <span>Select Model First</span>
            <i class="fas fa-chevron-down"></i>
        </div>
        <div class="custom-options" id="model-options"></div>
    </div>
    <input type="hidden" name="model" id="model-input" required>
</div>

                    <div class="input-group">
                        <label>Manufacture Year</label>
                            <div class="custom-select-wrapper">
                                <div class="custom-select-trigger">
                                    <span>Select Year</span>
                                    <i class="fas fa-chevron-down"></i>
                                </div>
                                <div class="custom-options">
                                    <span class="custom-option" data-value="2024">2024</span>
                                    <span class="custom-option" data-value="2023">2023</span>
                                    <span class="custom-option" data-value="2022">2022</span>
                                    <span class="custom-option" data-value="2021">2021</span>
                                </div>
                            </div>
                                <input type="hidden" name="year" id="year-input" required>                     
                             </div>

                <div class="input-group">
                    <label for="price">Estimated Price (EGP)</label>
                    <input type="number" id="price" name="price" placeholder="e.g. 500000" required>
                </div>

                <div class="radio-group">
                    <label class="radio-main-label">Car Condition:</label>
                    <div class="radio-options">
                        <label class="custom-radio">
                            <input type="radio" name="condition" value="new" required>
                            <span class="radio-text">New</span>
                        </label>
                        <label class="custom-radio">
                            <input type="radio" name="condition" value="used">
                            <span class="radio-text">Used</span>
                        </label>
                    </div>
                </div>

                <button type="submit" class="check-btn" id="submitBtn">
                    <span id="submitBtnText">Check</span>
                    <span id="submitBtnLoader" style="display:none;">
                        <i class="fas fa-spinner fa-spin"></i> Submitting...
                    </span>
                </button>
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