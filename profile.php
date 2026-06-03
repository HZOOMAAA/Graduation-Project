<?php
require_once __DIR__ . '/includes/connection.php';
require_once __DIR__ . '/includes/auth_check.php';

// Only allow customers to access this page
requireRole('customer');

$user_id = $_SESSION['user_id'];

$error = '';
$success = '';

// Handle Profile Update
if (isset($_POST['update_profile'])) {
    // Get current user info to fall back to if some fields are not submitted (e.g. avatar-only form)
    $curr_stmt = mysqli_prepare($connect, "SELECT name, email, phone, address, photo FROM users WHERE user_id = ?");
    mysqli_stmt_bind_param($curr_stmt, "i", $user_id);
    mysqli_stmt_execute($curr_stmt);
    $curr_user = mysqli_fetch_assoc(mysqli_stmt_get_result($curr_stmt));
    mysqli_stmt_close($curr_stmt);

    $name = (isset($_POST['name']) && $_POST['name'] !== '') ? trim(mysqli_real_escape_string($connect, $_POST['name'])) : ($curr_user['name'] ?? '');
    $email = (isset($_POST['email']) && $_POST['email'] !== '') ? trim(mysqli_real_escape_string($connect, $_POST['email'])) : ($curr_user['email'] ?? '');
    $phone = isset($_POST['phone']) ? trim(mysqli_real_escape_string($connect, $_POST['phone'])) : ($curr_user['phone'] ?? '');
    $address = isset($_POST['address']) ? trim(mysqli_real_escape_string($connect, $_POST['address'])) : ($curr_user['address'] ?? '');
    $password = $_POST['password'] ?? '';

    // Basic Validations
    if (empty($name) || empty($email)) {
        $error = "Name and Email are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        // Check if email is already taken by another user
        $email_stmt = mysqli_prepare($connect, "SELECT user_id FROM users WHERE email = ? AND user_id != ?");
        mysqli_stmt_bind_param($email_stmt, "si", $email, $user_id);
        mysqli_stmt_execute($email_stmt);
        $email_res = mysqli_stmt_get_result($email_stmt);
        
        if (mysqli_num_rows($email_res) > 0) {
            $error = "This email is already registered to another account.";
        } else {
            $photo_path = $curr_user['photo'] ?? null;

            // Handle Profile Photo Upload
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                $fileTmpPath = $_FILES['photo']['tmp_name'];
                $fileName = $_FILES['photo']['name'];
                $fileSize = $_FILES['photo']['size'];
                $fileType = $_FILES['photo']['type'];
                
                $fileNameCmps = explode(".", $fileName);
                $fileExtension = strtolower(end($fileNameCmps));
                
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
                
                if (in_array($fileExtension, $allowedExtensions)) {
                    if ($fileSize <= 5 * 1024 * 1024) { // 5 MB Limit
                        $uploadFileDir = __DIR__ . '/uploads/profiles/';
                        if (!is_dir($uploadFileDir)) {
                            mkdir($uploadFileDir, 0755, true);
                        }
                        
                        $newFileName = 'user_' . $user_id . '_' . uniqid() . '.' . $fileExtension;
                        $dest_path = $uploadFileDir . $newFileName;
                        
                        if (move_uploaded_file($fileTmpPath, $dest_path)) {
                            // If user had a previous custom photo, delete the old file
                            if ($photo_path && file_exists(__DIR__ . '/' . $photo_path)) {
                                @unlink(__DIR__ . '/' . $photo_path);
                            }
                            $photo_path = 'uploads/profiles/' . $newFileName;
                        } else {
                            $error = "There was an error moving the uploaded photo.";
                        }
                    } else {
                        $error = "The uploaded image exceeds the 5MB size limit.";
                    }
                } else {
                    $error = "Unsupported image format. Allowed formats: " . implode(', ', $allowedExtensions);
                }
            }

            if (empty($error)) {
                // Determine whether password needs to be updated
                if (!empty($password)) {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $update_stmt = mysqli_prepare($connect, "UPDATE users SET name = ?, email = ?, phone = ?, address = ?, photo = ?, password = ? WHERE user_id = ?");
                    mysqli_stmt_bind_param($update_stmt, "ssssssi", $name, $email, $phone, $address, $photo_path, $hashed_password, $user_id);
                } else {
                    $update_stmt = mysqli_prepare($connect, "UPDATE users SET name = ?, email = ?, phone = ?, address = ?, photo = ? WHERE user_id = ?");
                    mysqli_stmt_bind_param($update_stmt, "sssssi", $name, $email, $phone, $address, $photo_path, $user_id);
                }

                if (mysqli_stmt_execute($update_stmt)) {
                    $success = "Your profile has been updated successfully!";
                    $_SESSION['name'] = $name; // Update session name
                    $_SESSION['phone'] = $phone;
                } else {
                    $error = "Failed to update profile details: " . mysqli_error($connect);
                }
                mysqli_stmt_close($update_stmt);
            }
        }
        mysqli_stmt_close($email_stmt);
    }
}

// Fetch current user details
$user_stmt = mysqli_prepare($connect, "SELECT * FROM users WHERE user_id = ?");
mysqli_stmt_bind_param($user_stmt, "i", $user_id);
mysqli_stmt_execute($user_stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($user_stmt));
mysqli_stmt_close($user_stmt);

// Fetch all applications submitted by this customer
$apps_stmt = mysqli_prepare($connect, "
    SELECT a.*, 
           cat.name as category_name, 
           p.name as plan_name, 
           p.insurance_company, 
           pol.policy_number, 
           pol.document_path
    FROM applications a
    LEFT JOIN categories cat ON a.category_id = cat.category_id
    LEFT JOIN insurance_plans p ON a.plan_id = p.plan_id
    LEFT JOIN policies pol ON a.application_id = pol.application_id
    WHERE a.customer_id = ?
    ORDER BY a.created_at DESC
");
mysqli_stmt_bind_param($apps_stmt, "i", $user_id);
mysqli_stmt_execute($apps_stmt);
$applications_res = mysqli_stmt_get_result($apps_stmt);
mysqli_stmt_close($apps_stmt);

// Include standard Nav Header
include __DIR__ . '/includes/nav2.php';
?>

<!-- Profile Custom Stylesheet -->
<link rel="stylesheet" href="assets/css/profile.css">

<section class="premium-profile-section">
    <div class="premium-container">
        
        <!-- Header Profile Card -->
        <div class="premium-header-card">
            <div class="header-cover-bg"></div>
            
            <div class="header-main-content">
                <form id="avatarForm" action="profile.php" method="POST" enctype="multipart/form-data" class="premium-avatar-wrapper">
                    <?php if (!empty($user['photo']) && file_exists(__DIR__ . '/' . $user['photo'])): ?>
                        <img src="<?= htmlspecialchars($user['photo']) ?>" id="avatarPreview" alt="Profile Photo">
                    <?php else: ?>
                        <div class="premium-avatar-placeholder" id="avatarIcon">
                            <i class="fa-solid fa-user"></i>
                        </div>
                        <img src="" id="avatarPreview" alt="Profile Photo" style="display: none;">
                    <?php endif; ?>
                    
                    <label for="photo-upload" class="avatar-upload-btn">
                        <i class="fa-solid fa-camera"></i>
                    </label>
                    <input type="file" name="photo" id="photo-upload" accept="image/*" onchange="previewImage(this)">
                    <input type="hidden" name="update_profile" value="1">
                </form>
                
                <div class="premium-user-info">
                    <div class="user-title-row">
                        <h1><?= htmlspecialchars($user['name'] ?? 'User Profile') ?></h1>
                        <span class="premium-badge"><i class="fa-solid fa-shield-halved"></i> <?= date('F Y', strtotime($user['created_at'] ?? 'now')) ?></span>
                
                    </div>
                    <p class="user-email-row"><i class="fa-solid fa-envelope"></i> <?= htmlspecialchars($user['email'] ?? '') ?></p>
                </div>
                
                <div class="premium-header-actions">
                    <a href="auth/logout.php" class="premium-btn-outline">
                        <i class="fa-solid fa-right-from-bracket"></i> Logout
                    </a>
                </div>
            </div>
            
            <!-- Premium Tabs -->
            <div class="premium-tabs">
                <button class="premium-tab-btn active" onclick="switchTab('view-profile', this)">
                    <i class="fa-solid fa-id-card"></i> Overview
                </button>
                <button class="premium-tab-btn" onclick="switchTab('edit-profile', this)">
                    <i class="fa-solid fa-user-gear"></i> Settings
                </button>
                <button class="premium-tab-btn" onclick="switchTab('my-applications', this)">
                    <i class="fa-solid fa-file-signature"></i> Applications
                    <?php if ($applications_res && mysqli_num_rows($applications_res) > 0): ?>
                        <span class="tab-badge"><?= mysqli_num_rows($applications_res) ?></span>
                    <?php endif; ?>
                </button>
            </div>
        </div>
<!--  -->
        
        <!-- Content Area -->
        <div class="premium-content-area">
            
            <!-- Display Alerts -->
            <?php if (!empty($error)): ?>
                <div class="premium-alert error">
                    <i class="fa-solid fa-circle-xmark"></i>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="premium-alert success">
                    <i class="fa-solid fa-circle-check"></i>
                    <span><?= htmlspecialchars($success) ?></span>
                </div>
            <?php endif; ?>
            
            <!-- Tab: Overview -->
            <div id="view-profile" class="premium-tab-content active">
                <div class="premium-section-header">
                    <h2>Personal Information</h2>
                    <p>Manage your personal data and contact details.</p>
                </div>
                
                <div class="info-cards-grid">
                    <div class="info-card">
                        <div class="info-icon"><i class="fa-regular fa-user"></i></div>
                        <div class="info-data">
                            <label>Full Name</label>
                            <p><?= htmlspecialchars($user['name'] ?? 'N/A') ?></p>
                        </div>
                    </div>
                    
                    <div class="info-card">
                        <div class="info-icon"><i class="fa-regular fa-envelope"></i></div>
                        <div class="info-data">
                            <label>Email Address</label>
                            <p><?= htmlspecialchars($user['email'] ?? 'N/A') ?></p>
                        </div>
                    </div>
                    
                    <div class="info-card">
                        <div class="info-icon"><i class="fa-solid fa-phone"></i></div>
                        <div class="info-data">
                            <label>Phone Number</label>
                            <p><?= htmlspecialchars(!empty($user['phone']) ? $user['phone'] : 'Not Provided') ?></p>
                        </div>
                    </div>
                    
            
                    
                    <div class="info-card full-width">
                        <div class="info-icon"><i class="fa-solid fa-location-dot"></i></div>
                        <div class="info-data">
                            <label>Residential Address</label>
                            <p><?= htmlspecialchars(!empty($user['address']) ? $user['address'] : 'Not Provided') ?></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Tab: Settings -->
            <div id="edit-profile" class="premium-tab-content">
                <div class="premium-section-header">
                    <h2>Account Settings</h2>
                    <p>Update your profile details and security settings.</p>
                </div>
                
                <div class="premium-form-card">
                    <form action="profile.php" method="POST" enctype="multipart/form-data">
                        <input type="file" name="photo" id="photo-upload-sync" style="display:none;" accept="image/*">
                        
                        <div class="premium-form-grid">
                            <div class="form-group">
                                <label>Full Name</label>
                                <div class="input-with-icon">
                                    <i class="fa-regular fa-user"></i>
                                    <input type="text" name="name" value="<?= htmlspecialchars($user['name'] ?? '') ?>" required>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label>Email Address</label>
                                <div class="input-with-icon">
                                    <i class="fa-regular fa-envelope"></i>
                                    <input type="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label>Phone Number</label>
                                <div class="input-with-icon">
                                    <i class="fa-solid fa-phone"></i>
                                    <input type="text" name="phone" placeholder="+20..." value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label>New Password <span class="optional">(Optional)</span></label>
                                <div class="input-with-icon">
                                    <i class="fa-solid fa-lock"></i>
                                    <input type="password" name="password" placeholder="Leave blank to keep current">
                                </div>
                            </div>
                            
                            <div class="form-group full-width">
                                <label>Residential Address</label>
                                <div class="input-with-icon align-top">
                                    <i class="fa-solid fa-location-dot"></i>
                                    <textarea name="address" placeholder="Enter your full address..."><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" name="update_profile" class="premium-btn-primary">
                                <i class="fa-solid fa-floppy-disk"></i> Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Tab: Applications -->
            <div id="my-applications" class="premium-tab-content">
                <div class="premium-section-header">
                    <h2>Insurance Applications</h2>
                    <p>Track the status of your requests and policies.</p>
                </div>
                
                <?php if ($applications_res && mysqli_num_rows($applications_res) > 0): ?>
                    <div class="app-cards-grid">
                        <?php while ($row = mysqli_fetch_assoc($applications_res)): ?>
                            <?php 
                                $status = $row['status'];
                                $status_text = str_replace('_', ' ', $status);
                                $status_color = 'gray';
                                $status_icon = 'fa-circle-notch';
                                
                                switch ($status) {
                                    case 'under_review': $status_color = 'orange'; $status_icon = 'fa-magnifying-glass'; break;
                                    case 'waiting_docs': $status_color = 'yellow'; $status_icon = 'fa-file-circle-exclamation'; break;
                                    case 'awaiting_payment': $status_color = 'blue'; $status_icon = 'fa-credit-card'; break;
                                    case 'paid': $status_color = 'green'; $status_icon = 'fa-circle-check'; break;
                                    case 'rejected': $status_color = 'red'; $status_icon = 'fa-circle-xmark'; break;
                                }
                            ?>
                            <div class="premium-app-card">
                                <div class="app-card-header">
                                    <span class="app-id">#<?= $row['application_id'] ?></span>
                                    <span class="app-status badge-<?= $status_color ?>">
                                        <i class="fa-solid <?= $status_icon ?>"></i> <?= $status_text ?>
                                    </span>
                                </div>
                                
                                <h3 class="app-plan-name"><?= htmlspecialchars($row['plan_name'] ?? 'Custom Plan') ?></h3>
                                
                                <div class="app-details-flex">
                                    <div class="detail-col">
                                        <span class="d-label">Company</span>
                                        <span class="d-value"><i class="fa-regular fa-building"></i> <?= htmlspecialchars($row['insurance_company'] ?? 'Partner') ?></span>
                                    </div>
                                    <div class="detail-col">
                                        <span class="d-label">Submitted</span>
                                        <span class="d-value"><i class="fa-regular fa-calendar"></i> <?= date('M d, Y', strtotime($row['created_at'])) ?></span>
                                    </div>
                                </div>
                                
                                <div class="app-card-footer">
                                    <?php if ($row['final_price'] > 0): ?>
                                        <div class="price-tag">EGP <?= number_format($row['final_price'], 2) ?></div>
                                    <?php else: ?>
                                        <div></div>
                                    <?php endif; ?>
                                    
                                    <div class="action-buttons">
                                        <?php if ($status === 'waiting_docs' || $status === 'rejected'): ?>
                                            <a href="planDetails.php?application_id=<?= $row['application_id'] ?>" class="btn-sm btn-yellow"><i class="fa-solid fa-upload"></i> Upload</a>
                                        <?php elseif ($status === 'paid' && !empty($row['document_path'])): ?>
                                            <a href="/Graduation-Project/<?= htmlspecialchars($row['document_path']) ?>" target="_blank" class="btn-sm btn-green"><i class="fa-solid fa-download"></i> Policy</a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="premium-empty-state">
                        <div class="empty-icon"><i class="fa-solid fa-folder-open"></i></div>
                        <h3>No Applications Yet</h3>
                        <p>You haven't submitted any insurance applications.</p>
                        <a href="index.php" class="premium-btn-primary mt-3">Explore Plans</a>
                    </div>
                <?php endif; ?>
            </div>
            
        </div>
    </div>
</section>

<script>
function switchTab(tabId, btn) {
    document.querySelectorAll('.premium-tab-content').forEach(c => c.classList.remove('active'));
    document.querySelectorAll('.premium-tab-btn').forEach(b => b.classList.remove('active'));
    
    document.getElementById(tabId).classList.add('active');
    btn.classList.add('active');
}

function previewImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById('avatarPreview');
            const icon = document.getElementById('avatarIcon');
            preview.src = e.target.result;
            preview.style.display = 'block';
            if (icon) icon.style.display = 'none';
            
            const syncInput = document.getElementById('photo-upload-sync');
            if (syncInput) syncInput.files = input.files;
            
            document.getElementById('avatarForm').submit();
        }
        reader.readAsDataURL(input.files[0]);
    }
}

document.getElementById('photo-upload-sync')?.addEventListener('change', function() {
    const mainUpload = document.getElementById('photo-upload');
    if (mainUpload) {
        mainUpload.files = this.files;
        previewImage(this);
    }
});
</script>

<?php 
// Include standard Footer
include __DIR__ . '/includes/footer.php'; 
?>