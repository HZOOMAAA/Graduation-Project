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
    $name = trim(mysqli_real_escape_string($connect, $_POST['name']));
    $email = trim(mysqli_real_escape_string($connect, $_POST['email']));
    $phone = trim(mysqli_real_escape_string($connect, $_POST['phone']));
    $address = trim(mysqli_real_escape_string($connect, $_POST['address']));
    $password = $_POST['password'];

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
            // Get current photo path to keep if no new photo uploaded
            $curr_stmt = mysqli_prepare($connect, "SELECT photo FROM users WHERE user_id = ?");
            mysqli_stmt_bind_param($curr_stmt, "i", $user_id);
            mysqli_stmt_execute($curr_stmt);
            $curr_user = mysqli_fetch_assoc(mysqli_stmt_get_result($curr_stmt));
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
                    $_SESSION['name'] = $name; 
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

<section class="profile-section">
    <div class="profile-container">
        
        <div class="profile-grid">
            
            <!-- Sidebar: Avatar and Tabs -->
            <div class="profile-sidebar">
                <form id="avatarForm" action="profile.php" method="POST" enctype="multipart/form-data">
                    <div class="avatar-wrapper">
                        <?php if (!empty($user['photo']) && file_exists(__DIR__ . '/' . $user['photo'])): ?>
                            <img src="<?= htmlspecialchars($user['photo']) ?>" class="profile-avatar-img" id="avatarPreview" alt="Profile Photo">
                        <?php else: ?>
                            <i class="fa-regular fa-circle-user default-avatar-icon" id="avatarIcon"></i>
                            <img src="" class="profile-avatar-img" id="avatarPreview" alt="Profile Photo" style="display: none;">
                        <?php endif; ?>
                        
                        <label for="photo-upload" class="avatar-upload-trigger">
                            <i class="fa-solid fa-camera"></i> Change
                        </label>
                        <input type="file" name="photo" id="photo-upload" class="file-upload-input" accept="image/*" onchange="previewImage(this)">
                    </div>
                    
                    <h2 class="user-title"><?= htmlspecialchars($user['name'] ?? 'User Profile') ?></h2>
                    <p class="user-email-subtitle"><?= htmlspecialchars($user['email'] ?? '') ?></p>
                    
                    <!-- Form Submission Trigger (hidden normally, handled via JS or on profile save) -->
                    <input type="hidden" name="update_profile" value="1">
                </form>
                
                <div class="profile-menu">
                    <button class="profile-menu-btn active" onclick="switchTab('view-profile', this)">
                        <i class="fa-solid fa-user"></i> View Profile
                    </button>
                    <button class="profile-menu-btn" onclick="switchTab('edit-profile', this)">
                        <i class="fa-solid fa-user-gear"></i> Update Profile
                    </button>
                    <button class="profile-menu-btn" onclick="switchTab('my-applications', this)">
                        <i class="fa-solid fa-file-invoice"></i> My Applications
                    </button>
                    <div class="logout-btn-sidebar">
                        <a href="auth/logout.php" class="profile-menu-btn logout-btn-link" style="text-decoration: none;">
                            <i class="fa-solid fa-right-from-bracket"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Main Details Panel -->
            <div class="profile-panel">
                
                <!-- Display Alerts -->
                <?php if (!empty($error)): ?>
                    <div class="profile-alert profile-alert-error">
                        <i class="fa-solid fa-circle-xmark"></i>
                        <span><?= htmlspecialchars($error) ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($success)): ?>
                    <div class="profile-alert profile-alert-success">
                        <i class="fa-solid fa-circle-check"></i>
                        <span><?= htmlspecialchars($success) ?></span>
                    </div>
                <?php endif; ?>
                
                <!-- Tab: View Profile -->
                <div id="view-profile" class="tab-content active">
                    <div class="panel-header">
                        <h1 class="panel-title">My Profile Details</h1>
                        <p class="panel-subtitle">Here is a summary of your account information on Coverly.</p>
                    </div>
                    
                    <div class="profile-details-display">
                        <div class="detail-row">
                            <span class="detail-label"><i class="fa-regular fa-user"></i> Full Name</span>
                            <span class="detail-value"><?= htmlspecialchars($user['name'] ?? 'N/A') ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label"><i class="fa-regular fa-envelope"></i> Email Address</span>
                            <span class="detail-value"><?= htmlspecialchars($user['email'] ?? 'N/A') ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label"><i class="fa-solid fa-phone"></i> Phone Number</span>
                            <span class="detail-value"><?= htmlspecialchars(!empty($user['phone']) ? $user['phone'] : 'Not Provided') ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label"><i class="fa-solid fa-location-dot"></i> Residential Address</span>
                            <span class="detail-value"><?= htmlspecialchars(!empty($user['address']) ? $user['address'] : 'Not Provided') ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label"><i class="fa-solid fa-user-shield"></i> Account Role</span>
                            <span class="detail-value" style="text-transform: capitalize;"><?= htmlspecialchars($user['role'] ?? 'Customer') ?></span>
                        </div>
                    </div>
                </div>
                
                <!-- Tab: Edit Profile -->
                <div id="edit-profile" class="tab-content">
                    <div class="panel-header">
                        <h1 class="panel-title">Personal Settings</h1>
                        <p class="panel-subtitle">Update your personal information, contact numbers, and login credentials.</p>
                    </div>
                    
                    <form action="profile.php" method="POST" enctype="multipart/form-data">
                        <!-- Keep photo upload synced with main form submit as fallback -->
                        <input type="file" name="photo" id="photo-upload-sync" class="file-upload-input" accept="image/*">
                        
                        <div class="form-grid">
                            
                            <!-- Full Name -->
                            <div class="form-group">
                                <label class="form-label" for="name">Full Name *</label>
                                <div class="input-container">
                                    <i class="fa-regular fa-user"></i>
                                    <input type="text" name="name" id="name" class="form-input" value="<?= htmlspecialchars($user['name'] ?? '') ?>" required>
                                </div>
                            </div>
                            
                            <!-- Email -->
                            <div class="form-group">
                                <label class="form-label" for="email">Email Address *</label>
                                <div class="input-container">
                                    <i class="fa-regular fa-envelope"></i>
                                    <input type="email" name="email" id="email" class="form-input" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
                                </div>
                            </div>
                            
                            <!-- Phone -->
                            <div class="form-group">
                                <label class="form-label" for="phone">Phone Number</label>
                                <div class="input-container">
                                    <i class="fa-solid fa-phone"></i>
                                    <input type="text" name="phone" id="phone" class="form-input" placeholder="e.g. +20123456789" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                                </div>
                            </div>
                            
                            <!-- Address -->
                            <div class="form-group form-group-full">
                                <label class="form-label" for="address">Residential Address</label>
                                <textarea name="address" id="address" class="form-textarea" placeholder="Enter your home address..."><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                            </div>
                            
                            <!-- Password Change -->
                            <div class="form-group form-group-full">
                                <label class="form-label" for="password">Change Password (leave blank to keep current)</label>
                                <div class="input-container">
                                    <i class="fa-solid fa-lock"></i>
                                    <input type="password" name="password" id="password" class="form-input" placeholder="Enter new password">
                                </div>
                            </div>
                            
                        </div>
                        
                        <div style="margin-top: 30px;">
                            <button type="submit" name="update_profile" class="submit-btn">
                                <i class="fa-solid fa-floppy-disk"></i> Save Profile Details
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- Tab: My Applications -->
                <div id="my-applications" class="tab-content">
                    <div class="panel-header">
                        <h1 class="panel-title">My Insurance Applications</h1>
                        <p class="panel-subtitle">Monitor the state of your application files and download your issued policies.</p>
                    </div>
                    
                    <div class="apps-list">
                        <?php if ($applications_res && mysqli_num_rows($applications_res) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($applications_res)): ?>
                                <?php 
                                    // Status styling configuration
                                    $status = $row['status'];
                                    $status_text = ucfirst(str_replace('_', ' ', $status));
                                    $status_desc = '';
                                    
                                    switch ($status) {
                                        case 'under_review':
                                            $status_desc = 'An agent is currently reviewing your documents.';
                                            break;
                                        case 'waiting_docs':
                                            $status_desc = 'Additional documents are required to process this request.';
                                            break;
                                        case 'awaiting_payment':
                                            $status_desc = 'Application approved! Please proceed with your payment.';
                                            break;
                                        case 'paid':
                                            $status_desc = 'Policy issued and active.';
                                            break;
                                        case 'rejected':
                                            $status_desc = 'This application was rejected by the agent. You can upload new documents to re-submit it.';
                                            break;
                                        default:
                                            $status_desc = 'Status pending selection.';
                                    }
                                ?>
                                <div class="app-card">
                                    <div class="app-details">
                                        <div class="app-header-row">
                                            <span class="app-id-badge">ID: #<?= $row['application_id'] ?></span>
                                            <span class="app-date">Submitted on: <?= date('M d, Y', strtotime($row['created_at'])) ?></span>
                                        </div>
                                        <h3 class="app-plan-title"><?= htmlspecialchars($row['plan_name'] ?? 'Custom Plan') ?></h3>
                                        <div class="app-meta">
                                            <div class="app-meta-item">
                                                <i class="fa-solid fa-layer-group"></i> <?= htmlspecialchars($row['category_name'] ?? 'General') ?>
                                            </div>
                                            <div class="app-meta-item">
                                                <i class="fa-solid fa-building"></i> <?= htmlspecialchars($row['insurance_company'] ?? 'Coverly Partner') ?>
                                            </div>
                                            <?php if ($row['final_price'] > 0): ?>
                                                <div class="app-meta-item">
                                                    <i class="fa-solid fa-coins"></i> EGP <?= number_format($row['final_price'], 2) ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <p style="font-size: 13px; color: var(--gray); margin: 8px 0 0 0;">
                                            <i class="fa-solid fa-info-circle" style="color: var(--accent); margin-right: 4px;"></i> <?= $status_desc ?>
                                        </p>
                                    </div>
                                    
                                    <div class="app-actions">
                                        <span class="status-badge status-<?= $status ?>">
                                            <?php
                                            // Icon for badge
                                            $icon = 'fa-clock';
                                            if ($status === 'paid') $icon = 'fa-circle-check';
                                            if ($status === 'rejected') $icon = 'fa-circle-xmark';
                                            if ($status === 'waiting_docs') $icon = 'fa-file-arrow-up';
                                            if ($status === 'awaiting_payment') $icon = 'fa-credit-card';
                                            ?>
                                            <i class="fa-solid <?= $icon ?>"></i> <?= $status_text ?>
                                        </span>
                                        
                                        <?php if ($status === 'waiting_docs' || $status === 'rejected'): ?>
                                            <a href="planDetails.php?application_id=<?= $row['application_id'] ?>" class="action-btn action-btn-primary">
                                                <i class="fa-solid fa-upload"></i> Upload Docs
                                            </a>
                                        <?php elseif ($status === 'awaiting_payment'): ?>
                                            <a href="payment.php?app_id=<?= $row['application_id'] ?>" class="action-btn action-btn-primary" style="background: linear-gradient(135deg, #7b1fa2 0%, #6f2cf3 100%); border: none; box-shadow: 0 4px 10px rgba(111, 44, 243, 0.2); text-decoration: none;">
                                                <i class="fa-solid fa-credit-card"></i> Pay Now
                                            </a>
                                        <?php elseif ($status === 'paid' && !empty($row['document_path'])): ?>
                                            <a href="/Graduation-Project/<?= htmlspecialchars($row['document_path']) ?>" target="_blank" class="action-btn action-btn-success">
                                                <i class="fa-solid fa-file-pdf"></i> Download Policy
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="app-card no-data-card">
                                <i class="fa-regular fa-folder-open"></i>
                                <h3>No applications found</h3>
                                <p>You have not submitted any insurance applications yet. Explore categories to apply.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
            </div>
            
        </div>
        
    </div>
</section>

<!-- Tab Management and Image Preview JavaScript -->
<script>
function switchTab(tabId, btn) {
    // Hide all tab contents
    const contents = document.querySelectorAll('.tab-content');
    contents.forEach(content => content.classList.remove('active'));
    
    // Remove active class from all buttons
    const buttons = document.querySelectorAll('.profile-menu-btn');
    buttons.forEach(button => button.classList.remove('active'));
    
    // Show active tab
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
            
            if (icon) {
                icon.style.display = 'none';
            }
            
            // Also sync the file selection with the hidden inputs in main details form
            const syncInput = document.getElementById('photo-upload-sync');
            if (syncInput) {
                syncInput.files = input.files;
            }
        }
        
        reader.readAsDataURL(input.files[0]);
    }
}

// Sync file selection from main form back to avatar form input if changed
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
