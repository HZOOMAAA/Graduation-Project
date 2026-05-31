<?php
require_once 'includes/connection.php';
require_once 'includes/auth_check.php'; // ensures user is logged in

// ── Load the application (Check session first for deferred draft, fall back to DB) ──
$customer_id = $_SESSION['user_id'];
$application = null;
$appData = null;
$application_id = 0;

if (isset($_SESSION['temp_application_data']) && isset($_SESSION['temp_plan_id'])) {
    $appData = $_SESSION['temp_application_data'];
    $temp_plan_id = intval($_SESSION['temp_plan_id']);

    // Fetch the chosen plan's details
    $planStmt = mysqli_prepare($connect, "SELECT * FROM insurance_plans WHERE plan_id = ?");
    mysqli_stmt_bind_param($planStmt, 'i', $temp_plan_id);
    mysqli_stmt_execute($planStmt);
    $planResult = mysqli_stmt_get_result($planStmt);
    $plan = mysqli_fetch_assoc($planResult);

    if (!$plan) {
        header('Location: /Graduation-Project/homepage.php');
        exit;
    }

    // Fetch category name
    $category_id = $_SESSION['temp_category_id'] ?? 1;
    $catResult = mysqli_query($connect, "SELECT name FROM categories WHERE category_id = {$category_id} LIMIT 1");
    $catRow = mysqli_fetch_assoc($catResult);
    $category_name = $catRow ? $catRow['name'] : 'Car Insurance';

    $application = [
        'application_id' => 0, // Indicates session-based draft
        'customer_id' => $customer_id,
        'category_id' => $category_id,
        'plan_id' => $temp_plan_id,
        'status' => 'waiting_docs',
        'category_name' => $category_name,
        'plan_name' => $plan['name'],
        'insurance_company' => $plan['insurance_company'],
        'base_price' => $plan['base_price'],
        'plan_bio' => $plan['bio'],
        'eligibility_rules' => $plan['eligibility_rules'],
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
        "SELECT a.*,
                cat.name AS category_name,
                p.name AS plan_name,
                p.insurance_company,
                p.base_price,
                p.bio AS plan_bio,
                p.eligibility_rules
         FROM applications a
         LEFT JOIN categories cat ON a.category_id = cat.category_id
         LEFT JOIN insurance_plans p ON a.plan_id = p.plan_id
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

    // ── Guard: must have selected a plan ──────────────────────────────────────────
    if (!$application['plan_id']) {
        header('Location: /Graduation-Project/plans.php?application_id=' . $application_id);
        exit;
    }

    $appData = json_decode($application['application_data'] ?? '{}', true);
}

// Detect if category is health
$is_health = (isset($appData['category']) && $appData['category'] === 'health')
    || (isset($application['category_name']) && stripos($application['category_name'], 'health') !== false)
    || (isset($application['category_name']) && stripos($application['category_name'], 'medical') !== false)
    || (isset($application['category_id']) && intval($application['category_id']) == 2);

$is_life = (isset($appData['category']) && $appData['category'] === 'life')
    || (isset($application['category_name']) && stripos($application['category_name'], 'life') !== false)
    || (isset($application['category_id']) && intval($application['category_id']) == 5);

// ── Status helpers ─────────────────────────────────────────────────────────────
$allowedUploadStatuses = ['waiting_docs'];
$canUpload = in_array($application['status'], $allowedUploadStatuses);

$statusLabels = [
    'waiting_docs' => ['<i class="bx bxs-file-doc"></i> Awaiting Documents', '#1a73e8', '#e3f0ff'],
    'under_review' => ['<i class="bx bxs-hourglass"></i> Under Review', '#e65100', '#fff3e0'],
    'awaiting_payment' => ['<i class="bx bxs-credit-card"></i> Awaiting Payment', '#7b1fa2', '#fce8ff'],
    'paid' => ['<i class="bx bxs-shield"></i> Policy Issued', '#1b5e20', '#e8f5e9'],
    'rejected' => ['<i class="bx bxs-x-circle"></i> Rejected', '#c62828', '#fdecea'],
];
[$statusText, $statusColor, $statusBg] = $statusLabels[$application['status']]
    ?? ['— ' . ucfirst(str_replace('_', ' ', $application['status'])), '#64748b', '#f4f7f9'];

include 'includes/nav2.php';
?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- why choose -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="/Graduation-Project/assets/css/planDetails.css">

<!-- ── Submission Success Modal ── -->
<div class="pd-modal-overlay" id="pdModal">
    <div class="pd-modal">
        <div class="pd-modal-icon">
    <i class='bx bxs-check-shield'></i>
        </div>
        <h2>Application Submitted!</h2>
        <p>
            Your documents have been received successfully.<br>
            <strong>Please wait for an agent to review your application.</strong><br>
            We'll notify you once a decision has been made.
        </p>
        <a href="/Graduation-Project/homepage.php" class="pd-modal-btn">
            <i class="bx bxs-home"></i> Back to Home
        </a>
    </div>
</div>

<section class="pd-section">
    <div class="pd-wrapper">

        <!-- ── Hero ── -->
        <div class="pd-hero">
            <div class="pd-hero-inner">
                <div class="pd-hero-badge">
                    <i class="bx bxs-shield-plus"></i>
                    Application #<?php echo $application_id; ?>
                </div>
                <h1>Upload Your Documents</h1>
                <p>You've selected your plan — just upload the required documents to complete your application.</p>

                <!-- Step Progress -->
                <div class="pd-steps">
                    <div class="pd-step done">
                        <div class="pd-step-num"><i class="bx bx-check"></i></div>
                        <span>Apply</span>
                    </div>
                    <div class="pd-step-divider"></div>
                    <div class="pd-step done">
                        <div class="pd-step-num"><i class="bx bx-check"></i></div>
                        <span>Choose Plan</span>
                    </div>
                    <div class="pd-step-divider"></div>
                    <div class="pd-step <?php echo $canUpload ? 'active' : 'done'; ?>">
                        <div class="pd-step-num"><?php echo $canUpload ? '3' : '<i class="bx bx-check"></i>'; ?></div>
                        <span>Upload Docs</span>
                    </div>
                    <div class="pd-step-divider"></div>
                    <div class="pd-step <?php echo !$canUpload ? 'active' : ''; ?>">
                        <div class="pd-step-num">4</div>
                        <span>Agent Review</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Already Submitted Banner ── -->
        <?php if (!$canUpload): ?>
                <div class="pd-status-banner">
                    <i class="bx bxs-check-circle"></i>
                    <h2><?php echo $statusText; ?></h2>
                    <p>
                        Your application is currently <strong><?php echo htmlspecialchars(str_replace('_', ' ', $application['status'])); ?></strong>.
                        Our agents are working on it — we'll be in touch soon.
                    </p>
                </div>
        <?php endif; ?>

        <!-- ── Plan & Vehicle Summary Cards ── -->
        <div class="pd-grid">

            <!-- Plan Card -->
            <div class="pd-card">
                <div class="pd-card-header">
                    <i class="bx bxs-shield-plus"></i>
                    <div>
                        <h2>Selected Plan</h2>
                    </div>
                </div>
                <div class="pd-card-body">
                    <div class="pd-company-tag"><?php echo htmlspecialchars($application['insurance_company'] ?? '—'); ?></div>

                    <div class="pd-info-row">
                        <span class="pd-info-label">Plan Name</span>
                        <span class="pd-info-value"><?php echo htmlspecialchars($application['plan_name'] ?? '—'); ?></span>
                    </div>
                    <div class="pd-info-row">
                        <span class="pd-info-label">Annual Premium</span>
                        <span class="pd-info-value pd-price-big">
                            EGP <?php echo number_format($application['base_price'] ?? 0, 2); ?>
                        </span>
                    </div>
                    <?php if (!empty($application['plan_bio'])): ?>
                        <div class="pd-info-row" style="display:block; padding-top:12px;">
                            <p style="font-size:13px; color:var(--gray); line-height:1.6;">
                                <?php echo htmlspecialchars($application['plan_bio']); ?>
                            </p>
                        </div>
                    <?php endif; ?>
                    <div class="pd-info-row">
                        <span class="pd-info-label">Status</span>
                        <span class="pd-info-value">
                            <span style="background:<?php echo $statusBg; ?>; color:<?php echo $statusColor; ?>; padding:3px 12px; border-radius:20px; font-size:12px; font-weight:700;">
                                <?php echo $statusText; ?>
                            </span>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Vehicle Card / Applicant Details -->
            <div class="pd-card">
                <div class="pd-card-header">
                    <?php if ($is_health || $is_life): ?>
                            <i class="bx bxs-user-badge"></i>
                            <div>
                                <h2>Applicant Details</h2>
                            </div>
                    <?php else: ?>
                            <i class="bx bxs-car"></i>
                            <div>
                                <h2>Vehicle Details</h2>
                            </div>
                    <?php endif; ?>
                </div>
                <div class="pd-card-body">
                    <?php if ($is_health): ?>
                            <div class="pd-info-row">
                                <span class="pd-info-label">Name</span>
                                <span class="pd-info-value"><?php echo htmlspecialchars($appData['client_name'] ?? '—'); ?></span>
                            </div>
                            <div class="pd-info-row">
                                <span class="pd-info-label">Phone</span>
                                <span class="pd-info-value"><?php echo htmlspecialchars($appData['client_phone'] ?? '—'); ?></span>
                            </div>
                            <div class="pd-info-row">
                                <span class="pd-info-label">Age</span>
                                <span class="pd-info-value"><?php echo htmlspecialchars($appData['age'] ?? '—'); ?> years</span>
                            </div>
                            <div class="pd-info-row">
                                <span class="pd-info-label">Spouse Registered</span>
                                <span class="pd-info-value"><?php echo !empty($appData['has_spouse']) ? 'Yes' : 'No'; ?></span>
                            </div>
                            <div class="pd-info-row">
                                <span class="pd-info-label">Number of Children</span>
                                <span class="pd-info-value"><?php echo intval($appData['num_children'] ?? 0); ?></span>
                            </div>
                            <div class="pd-info-row">
                                <span class="pd-info-label">Chronic Conditions</span>
                                <span class="pd-info-value"><?php echo htmlspecialchars(ucfirst($appData['family_chronic'] ?? 'no')); ?></span>
                            </div>
                            <div class="pd-info-row">
                                <span class="pd-info-label">Category</span>
                                <span class="pd-info-value"><?php echo htmlspecialchars($application['category_name'] ?? '—'); ?></span>
                            </div>
                    <?php elseif ($is_life): ?>
                            <div class="pd-info-row">
                                <span class="pd-info-label">Name</span>
                                <span class="pd-info-value"><?php echo htmlspecialchars($appData['client_name'] ?? '—'); ?></span>
                            </div>
                            <div class="pd-info-row">
                                <span class="pd-info-label">Phone</span>
                                <span class="pd-info-value"><?php echo htmlspecialchars($appData['client_phone'] ?? '—'); ?></span>
                            </div>
                            <div class="pd-info-row">
                                <span class="pd-info-label">Age</span>
                                <span class="pd-info-value"><?php echo htmlspecialchars($appData['age'] ?? '—'); ?> years</span>
                            </div>
                            <div class="pd-info-row">
                                <span class="pd-info-label">Coverage Amount</span>
                                <span class="pd-info-value">EGP <?php echo number_format(floatval($appData['coverage_amount'] ?? 0)); ?></span>
                            </div>
                            <div class="pd-info-row">
                                <span class="pd-info-label">Policy Duration</span>
                                <span class="pd-info-value"><?php echo htmlspecialchars(str_replace('_', ' ', $appData['policy_term'] ?? '—')); ?></span>
                            </div>
                            <div class="pd-info-row">
                                <span class="pd-info-label">Beneficiary</span>
                                <span class="pd-info-value"><?php echo htmlspecialchars($appData['beneficiary_name'] ?? '—') . ' (' . htmlspecialchars(ucwords($appData['beneficiary_relation'] ?? '—')) . ')'; ?></span>
                            </div>
                            <div class="pd-info-row">
                                <span class="pd-info-label">Category</span>
                                <span class="pd-info-value"><?php echo htmlspecialchars($application['category_name'] ?? '—'); ?></span>
                            </div>
                    <?php else: ?>
                            <div class="pd-info-row">
                                <span class="pd-info-label">Brand</span>
                                <span class="pd-info-value"><?php echo htmlspecialchars(ucfirst($appData['brand'] ?? '—')); ?></span>
                            </div>
                            <div class="pd-info-row">
                                <span class="pd-info-label">Model</span>
                                <span class="pd-info-value"><?php echo htmlspecialchars($appData['model'] ?? '—'); ?></span>
                            </div>
                            <div class="pd-info-row">
                                <span class="pd-info-label">Year</span>
                                <span class="pd-info-value"><?php echo htmlspecialchars($appData['year'] ?? '—'); ?></span>
                            </div>
                            <div class="pd-info-row">
                                <span class="pd-info-label">Condition</span>
                                <span class="pd-info-value"><?php echo htmlspecialchars(ucfirst($appData['condition'] ?? '—')); ?></span>
                            </div>
                            <div class="pd-info-row">
                                <span class="pd-info-label">Estimated Value</span>
                                <span class="pd-info-value">EGP <?php echo number_format(floatval($appData['price'] ?? 0)); ?></span>
                            </div>
                            <div class="pd-info-row">
                                <span class="pd-info-label">Category</span>
                                <span class="pd-info-value"><?php echo htmlspecialchars($application['category_name'] ?? '—'); ?></span>
                            </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- ── Document Upload Form ── -->
        <?php if ($canUpload): ?>
            <div class="pd-upload-section">
                <div class="pd-upload-header">
                    <i class="bx bxs-file-plus"></i>
                    <div>
                        <h2>Required Documents</h2>
                        <p>Upload clear photos or scanned PDFs. Max 8 MB per file.</p>
                    </div>
                </div>
                <div class="pd-upload-body">

                    <!-- Error Alert (JS-injected) -->
                    <div class="pd-alert pd-alert--error" id="pd-error-alert" style="display:none;">
                        <i class="bx bxs-error-circle"></i>
                        <span id="pd-error-msg"></span>
                    </div>

                    <form id="pdDocsForm" enctype="multipart/form-data">
                        <input type="hidden" name="application_id" value="<?php echo $application_id; ?>">

                        <div class="pd-fields-grid">

                            <!-- National ID -->
                            <div class="pd-field">
                                <label class="pd-label">
                                    <i class="bx bxs-id-card"></i>
                                    National ID <span class="req">*</span>
                                </label>
                                <div class="pd-file-zone" id="zone-national_id">
                                    <input type="file" name="national_id" id="national_id"
                                           accept=".jpg,.jpeg,.png,.pdf,.webp" required>
                                    <i class="bx bx-upload pd-file-zone-icon"></i>
                                    <div class="pd-file-zone-text">
                                        <strong>Click to upload</strong> or drag & drop<br>
                                        JPG, PNG, PDF — max 8 MB
                                    </div>
                                    <div class="pd-file-preview" id="preview-national_id"></div>
                                </div>
                            </div>

                            <!-- Car License -->
                            <div class="pd-field">
                                <label class="pd-label">
                                    <i class="bx bxs-car-garage"></i>
                                    Car License <span class="req">*</span>
                                </label>
                                <div class="pd-file-zone" id="zone-car_license">
                                    <input type="file" name="car_license" id="car_license"
                                           accept=".jpg,.jpeg,.png,.pdf,.webp" required>
                                    <i class="bx bx-upload pd-file-zone-icon"></i>
                                    <div class="pd-file-zone-text">
                                        <strong>Click to upload</strong> or drag & drop<br>
                                        JPG, PNG, PDF — max 8 MB
                                    </div>
                                    <div class="pd-file-preview" id="preview-car_license"></div>
                                </div>
                            </div>

                            <!-- Car Photos (multiple) -->
                            <div class="pd-field pd-field-full">
                                <label class="pd-label">
                                    <i class="bx bxs-camera"></i>
                                    Car Photos <span style="color:var(--gray); font-weight:400;">(optional — multiple allowed)</span>
                                </label>
                                <div class="pd-file-zone" id="zone-car_photos">
                                    <input type="file" name="car_photos[]" id="car_photos"
                                           accept=".jpg,.jpeg,.png,.webp,.gif" multiple>
                                    <i class="bx bx-images pd-file-zone-icon"></i>
                                    <div class="pd-file-zone-text">
                                        <strong>Click to upload</strong> or drag & drop<br>
                                        JPG, PNG, WEBP — multiple files accepted
                                    </div>
                                    <div class="pd-file-preview" id="preview-car_photos"></div>
                                </div>
                            </div>

                            <!-- Notes -->
                            <div class="pd-field pd-field-full">
                                <label class="pd-label">
                                    <i class="bx bxs-note"></i>
                                    Additional Notes <span style="color:var(--gray); font-weight:400;">(optional)</span>
                                </label>
                                <textarea class="pd-textarea" name="notes"
                                          placeholder="Any additional information you'd like the agent to know about your vehicle or situation…"></textarea>
                            </div>
                        </div>

                        <!-- Submit -->
                        <button type="submit" class="pd-submit-btn" id="pdSubmitBtn">
                            <div class="pd-spinner" id="pdSpinner"></div>
                            <span id="pdSubmitText">
                                <i class="bx bxs-send"></i>
                                Submit Application
                            </span>
                        </button>
                    </form>
                </div>
            </div>
        <?php endif; ?>

    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function () {

    // ── File preview labels ────────────────────────────────────────────────────
    const fileInputs = ['national_id', 'car_license', 'car_photos'];
    fileInputs.forEach(function (id) {
        const input   = document.getElementById(id);
        const preview = document.getElementById('preview-' + id);
        const zone    = document.getElementById('zone-' + id);
        if (!input) return;

        input.addEventListener('change', function () {
            const files = Array.from(input.files);
            if (files.length === 0) {
                preview.textContent = '';
                return;
            }
            if (files.length === 1) {
                preview.textContent = ' ' + files[0].name;
            } else {
                preview.textContent = ' ' + files.length + ' files selected';
            }
        });

        // Drag-over styling
        zone.addEventListener('dragover', function (e) {
            e.preventDefault();
            zone.classList.add('dragover');
        });
        zone.addEventListener('dragleave', function () {
            zone.classList.remove('dragover');
        });
        zone.addEventListener('drop', function () {
            zone.classList.remove('dragover');
        });
    });

    // ── Form submission ────────────────────────────────────────────────────────
    const form      = document.getElementById('pdDocsForm');
    if (!form) return;

    const submitBtn  = document.getElementById('pdSubmitBtn');
    const submitText = document.getElementById('pdSubmitText');
    const spinner    = document.getElementById('pdSpinner');
    const errorAlert = document.getElementById('pd-error-alert');
    const errorMsg   = document.getElementById('pd-error-msg');
    const modal      = document.getElementById('pdModal');

    form.addEventListener('submit', async function (e) {
        e.preventDefault();

        // Hide any previous error
        errorAlert.style.display = 'none';

        // Loader on
        submitBtn.disabled      = true;
        spinner.style.display   = 'inline-block';
        submitText.style.display = 'none';

        try {
            const formData = new FormData(form);
            // car_photos input name workaround for multiple
            const photosInput = document.getElementById('car_photos');
            if (photosInput && photosInput.files.length > 0) {
                formData.delete('car_photos[]');
                Array.from(photosInput.files).forEach(function (file) {
                    formData.append('car_photos[]', file);
                });
            }

            const response = await fetch('/Graduation-Project/submit_documents.php', {
                method: 'POST',
                body:   formData,
            });

            const result = await response.json();

            if (result.success) {
                // Show success modal
                modal.classList.add('show');
            } else {
                errorMsg.textContent   = result.message || 'Something went wrong. Please try again.';
                errorAlert.style.display = 'flex';
                window.scrollTo({ top: errorAlert.getBoundingClientRect().top + window.scrollY - 100, behavior: 'smooth' });
            }
        } catch (err) {
            errorMsg.textContent   = 'Network error — please check your connection and try again.';
            errorAlert.style.display = 'flex';
        } finally {
            submitBtn.disabled       = false;
            spinner.style.display    = 'none';
            submitText.style.display = 'inline';
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?>
