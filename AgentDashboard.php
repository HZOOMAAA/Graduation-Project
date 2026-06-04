<?php
require_once __DIR__ . '/includes/auth_check.php';
requireRole('agent');

$error   = '';
$success = '';
$agent_id = $_SESSION['user_id'];

// ── Handle Agent Decision (under_review → awaiting_payment / rejected) ──
if (isset($_POST['update_status'])) {
    $application_id = (int)$_POST['application_id'];
    $new_status = in_array($_POST['update_status'], ['awaiting_payment', 'rejected'])
        ? $_POST['update_status'] : null;

    if ($new_status) {
        $check = mysqli_query($connect, "SELECT application_id FROM applications WHERE application_id = $application_id AND agent_id = $agent_id AND status = 'under_review'");
        if (mysqli_num_rows($check) > 0) {
            mysqli_query($connect, "UPDATE applications SET status = '$new_status' WHERE application_id = $application_id");
            $msg = $new_status === 'awaiting_payment'
                ? "Application approved. Customer notified to proceed with payment."
                : "Application rejected. Please provide a rejection message below for the customer.";
            header("Location: AgentDashboard.php?tab=details&id=$application_id&success=" . urlencode($msg));
            exit();
        } else {
            $error = "You can only decide on applications that are 'Under Review'.";
        }
    }
}

// ── Handle Rejection Message submission ──
if (isset($_POST['submit_rejection_message'])) {
    $application_id = (int)$_POST['application_id'];
    $rejection_message = trim($_POST['rejection_message'] ?? '');

    $check = mysqli_query($connect, "SELECT application_id FROM applications WHERE application_id = $application_id AND agent_id = $agent_id AND status = 'rejected'");
    if (mysqli_num_rows($check) > 0) {
        $stmt = mysqli_prepare($connect, "
            UPDATE applications 
            SET application_data = JSON_SET(COALESCE(application_data, '{}'), '$.rejection_message', ?) 
            WHERE application_id = ?
        ");
        mysqli_stmt_bind_param($stmt, 'si', $rejection_message, $application_id);
        if (mysqli_stmt_execute($stmt)) {
            $msg = "Rejection message saved and customer notified <i class='bx bx-check' style='color: var(--green);'></i>.";
            header("Location: AgentDashboard.php?tab=details&id=$application_id&success=" . urlencode($msg));
            exit();
        } else {
            $error = "Failed to save rejection message: " . mysqli_error($connect);
        }
        mysqli_stmt_close($stmt);
    } else {
        $error = "Application not found or is not in rejected status.";
    }
}

// ── Handle Policy Issuance (awaiting_payment → paid) ─────────────────
if (isset($_POST['issue_policy'])) {
    $application_id = (int)$_POST['application_id'];
    $payment_ref    = mysqli_real_escape_string($connect, trim($_POST['payment_ref'] ?? ''));
    $start_date     = mysqli_real_escape_string($connect, $_POST['start_date']);
    $end_date       = mysqli_real_escape_string($connect, $_POST['end_date']);

    $check = mysqli_query($connect, "SELECT application_id FROM applications WHERE application_id = $application_id AND agent_id = $agent_id AND status = 'awaiting_payment'");
    if (mysqli_num_rows($check) > 0) {
        // Generate policy number
        $policy_no = 'POL-' . strtoupper(substr(md5($application_id . time()), 0, 8));
        $doc_path  = "uploads/policies/policy_{$application_id}.pdf";

        // Insert policy record
        $ins = mysqli_query($connect, "INSERT INTO policies (application_id, policy_number, start_date, end_date, document_path, payment_ref, status)
            VALUES ($application_id, '$policy_no', '$start_date', '$end_date', '$doc_path', '$payment_ref', 'active')");

        if ($ins) {
            // Mark application as paid
            mysqli_query($connect, "UPDATE applications SET status = 'paid' WHERE application_id = $application_id");
            $msg = "Payment confirmed <i class='bx bx-check' style='color: var(--green);'></i> Policy issued: $policy_no";
            header("Location: AgentDashboard.php?tab=details&id=$application_id&success=" . urlencode($msg));
            exit();
        } else {
            $error = "Failed to issue policy: " . mysqli_error($connect);
        }
    } else {
        $error = "Policy can only be issued for applications awaiting payment.";
    }
}

$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'applications';

if (isset($_GET['success'])) $success = $_GET['success'];

// ── Badge helper ──────────────────────────────────────────────────────
function statusBadgeStyle($status) {
    $map = [
        'pending_selection' => 'background:#f1f3f4; color:#5f6368;',
        'waiting_docs'      => 'background:#e3f0ff; color:#1a73e8;',
        'under_review'      => 'background:#fff3e0; color:#e65100;',
        'awaiting_payment'  => 'background:#fce8ff; color:#7b1fa2;',
        'paid'              => 'background:#e8f5e9; color:#1b5e20;',
        'rejected'          => 'background:#fdecea; color:#c62828;',
    ];
    return $map[$status] ?? 'background:#f1f3f4; color:#5f6368;';
}

function statusLabel($status) {
    return ucfirst(str_replace('_', ' ', $status));
}

// ── Fetch applications list ────────────────────────────────────────────
$status_filter = '';
if ($active_tab === 'applications') {
    $status_filter = "AND a.status = 'under_review'";
} elseif ($active_tab === 'awaiting') {
    $status_filter = "AND a.status = 'awaiting_payment'";
} elseif ($active_tab === 'paid') {
    $status_filter = "AND a.status = 'paid'";
} elseif ($active_tab === 'rejected') {
    $status_filter = "AND a.status = 'rejected'";
}

$applications = mysqli_query($connect, "
    SELECT a.*,
           c.name as customer_name, c.email as customer_email, c.phone as customer_phone, c.address as customer_address,
           cat.name as category_name,
           p.name as plan_name, p.insurance_company,p.base_price
    FROM applications a
    LEFT JOIN users c ON a.customer_id = c.user_id
    LEFT JOIN categories cat ON a.category_id = cat.category_id
    LEFT JOIN insurance_plans p ON a.plan_id = p.plan_id 
    WHERE a.agent_id = $agent_id $status_filter
    ORDER BY a.created_at DESC
");

// ── Fetch single application details ──────────────────────────────────
$app_details  = null;
$documents    = null;
$policy       = null;
if ($active_tab === 'details' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $res = mysqli_query($connect, "
        SELECT a.*,
               c.name as customer_name, c.email, c.phone, c.address,
               cat.name as category_name,
               p.name as plan_name, p.insurance_company, p.bio as plan_bio, p.base_price, p.eligibility_rules
        FROM applications a
        LEFT JOIN users c ON a.customer_id = c.user_id
        LEFT JOIN categories cat ON a.category_id = cat.category_id
        LEFT JOIN insurance_plans p ON a.plan_id = p.plan_id 
        WHERE a.application_id = $id AND a.agent_id = $agent_id
    ");
    if ($res && mysqli_num_rows($res) > 0) {
        $app_details = mysqli_fetch_assoc($res);
        $documents   = mysqli_query($connect, "SELECT * FROM documents WHERE application_id = $id");
        $policy      = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM policies WHERE application_id = $id LIMIT 1"));
    } else {
        $error = "Application not found or access denied.";
        $active_tab = 'applications';
    }
}

// ── Sidebar counts ────────────────────────────────────────────────────
function sidebarCount($connect, $agent_id, $status) {
    $r = mysqli_fetch_assoc(mysqli_query($connect, "SELECT COUNT(*) as c FROM applications WHERE agent_id = $agent_id AND status = '$status'"));
    return (int)$r['c'];
}
$cnt = [
    'under_review'      => sidebarCount($connect, $agent_id, 'under_review'),
    'awaiting_payment'  => sidebarCount($connect, $agent_id, 'awaiting_payment'),
    'paid'              => sidebarCount($connect, $agent_id, 'paid'),
    'rejected'          => sidebarCount($connect, $agent_id, 'rejected'),
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agent Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/AgentDashboard.css">
</head>
<body>

<div class="sidebar">
    <div class="sidebar-header">
        Coverly
        <span>Welcome, <?php echo htmlspecialchars($_SESSION['name'] ?? 'Agent'); ?></span>
    </div>
    <nav>
        <?php
        $nav = [
            'applications' => ['<i class="bx bx-book-open"></i>', 'Under Review',      'under_review',     '#e65100'],
            'awaiting'     => ['<i class="bx bx-credit-card"></i>', 'Awaiting Payment',  'awaiting_payment', '#7b1fa2'],
            'paid'         => ['<i class="bx bx-check-circle"></i>', 'Paid / Issued',      'paid',             '#1b5e20'],
            'rejected'     => ['<i class="bx bx-x-circle"></i>', 'Rejected',           'rejected',         '#c62828'],
        ];
        foreach ($nav as $tab => [$icon, $label, $status, $color]):
            $is_active = ($active_tab === $tab || ($active_tab === 'details' && $tab === 'applications'));
        ?>
        <a href="AgentDashboard.php?tab=<?php echo $tab; ?>" class="<?php echo $is_active ? 'active' : ''; ?>">
            <span class="icon"><?php echo $icon; ?></span> <?php echo $label; ?>
            <?php if ($cnt[$status] > 0): ?>
                <span class="sidebar-badge" style="background:<?php echo $color; ?>;"><?php echo $cnt[$status]; ?></span>
            <?php endif; ?>
        </a>
        <?php endforeach; ?>
    </nav>
    <div class="sidebar-footer">
        <a href="/Graduation-Project/auth/logout.php"><i class='bx bx-log-out'></i> Logout</a>
    </div>
</div>

<div class="main-content">

    <?php if ($error): ?>
        <div class="alert alert-error"><i class='bx bx-error-circle'></i> <?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><i class='bx bx-check-circle'></i> <?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <?php
    $tab_config = [
        'applications' => ['<i class="bx bx-book-open"></i> My Work Queue',      'Applications assigned to you for document verification and approval.'],
        'awaiting'     => ['<i class="bx bx-credit-card"></i> Awaiting Payment',    'Approved applications waiting for customer payment and policy issuance.'],
        'paid'         => ['<i class="bx bx-check-circle"></i> Paid & Issued',        'Fully completed policies. Payment received and policy document issued.'],
        'rejected'     => ['<i class="bx bx-x-circle"></i> Rejected Applications','Applications that were rejected after review.'],
    ];
    $cols_review = ['App ID','Customer','Category','Chosen Plan','base Price','Date','Action'];
    $cols_awaiting = ['App ID','Customer','Category','Plan','base Price','Date','Action'];
    $cols_paid    = ['App ID','Customer','Email','Plan','base Price','Policy No.'];
    $cols_rejected = ['App ID','Customer','Category','Date','Action'];
    ?>

    <?php if (in_array($active_tab, ['applications','awaiting','paid','rejected'])): ?>
        <?php [$title, $subtitle] = $tab_config[$active_tab]; ?>
        <div class="page-header-block">
            <div class="page-title"><?php echo $title; ?></div>
            <div class="page-subtitle"><?php echo $subtitle; ?></div>
        </div>

        <div class="card card-table-wrapper">
            <div class="card-header-title">
                <h2><?php echo $title; ?></h2>
            </div>
            <div class="table-responsive-container">
                <table>
                    <thead>
                        <tr>
                            <?php
                            $headers = match($active_tab) {
                                'applications' => $cols_review,
                                'awaiting'     => $cols_awaiting,
                                'paid'         => $cols_paid,
                                default        => $cols_rejected,
                            };
                            foreach ($headers as $h) echo "<th>$h</th>";
                            ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($applications && mysqli_num_rows($applications) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($applications)): ?>
                                <?php
                                $row_policy = null;
                                if ($active_tab === 'paid') {
                                    $row_policy = mysqli_fetch_assoc(mysqli_query($connect, "SELECT policy_number FROM policies WHERE application_id = {$row['application_id']} LIMIT 1"));
                                }
                                ?>
                                <tr>
                                    <td><span class="txt-bold">#<?php echo $row['application_id']; ?></span></td>
                                    <td><span class="txt-medium"><?php echo htmlspecialchars($row['customer_name'] ?? 'Unknown'); ?></span></td>
                                    <?php if ($active_tab === 'paid'): ?>
                                        <td class="txt-muted"><?php echo htmlspecialchars($row['customer_email'] ?? 'N/A'); ?></td>
                                    <?php else: ?>
                                        <td><span class="badge badge-light-blue"><?php echo htmlspecialchars($row['category_name'] ?? 'N/A'); ?></span></td>
                                    <?php endif; ?>
                                    <?php if ($active_tab !== 'rejected'): ?>
                                        <td>
                                            <?php if ($row['plan_name']): ?>
                                                <span class="badge badge-plan"><?php echo htmlspecialchars($row['plan_name']); ?></span>
                                            <?php else: ?>—<?php endif; ?>
                                        </td>
                                        <td><?php echo $row['base_price'] ? '<span class="price-tag">$' . number_format($row['base_price'], 2) . '</span>' : '—'; ?></td>
                                    <?php endif; ?>
                                    <?php if ($active_tab === 'paid'): ?>
                                        <td>
                                            <?php if ($row_policy): ?>
                                                <code class="policy-code"><?php echo htmlspecialchars($row_policy['policy_number']); ?></code>
                                            <?php else: ?>—<?php endif; ?>
                                        </td>
                                    <?php else: ?>
                                        <td class="txt-muted"><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                                        <td>
                                            <a href="AgentDashboard.php?tab=details&id=<?php echo $row['application_id']; ?>" class="btn btn-sm btn-primary-action" title="View Details"><i class='bx bx-right-arrow-alt'></i></a>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="7" class="no-data"><i class='bx bx-folder-open'></i><br>No applications in this stage.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    <?php elseif ($active_tab === 'details' && $app_details): ?>
        <div class="page-header-block">
            <div class="page-title">Application #<?php echo $app_details['application_id']; ?></div>
            <div class="page-subtitle">Full application review — customer data, chosen plan, documents, and decisions.</div>
        </div>

        <div class="status-banner-card">
            <span class="status-label-text">Current Status:</span>
            <span class="badge badge-status-main" style="<?php echo statusBadgeStyle($app_details['status']); ?>">
                <?php echo statusLabel($app_details['status']); ?>
            </span>
        </div>

        <?php 
        $appDataDecoded = json_decode($app_details['application_data'] ?? '{}', true);
        if (!empty($appDataDecoded['was_rejected_before'])): 
        ?>
            <div class="alert alert-error" style="margin-bottom: 24px;">
                <i class="bx bxs-error-circle" style="font-size: 20px;"></i>
                <span><strong>Attention Agent:</strong> This application was previously rejected. It has been re-submitted with new/corrected documents for your review.</span>
            </div>
        <?php endif; ?>

        <div class="card">
            <h2><i class='bx bx-user-pin'></i> Customer Information</h2>
            <div class="info-grid-layout">
                <div class="info-item"><i class='bx bx-user'></i> <div><strong>Name:</strong> <span><?php echo htmlspecialchars($app_details['customer_name']); ?></span></div></div>
                <div class="info-item"><i class='bx bx-envelope'></i> <div><strong>Email:</strong> <span><?php echo htmlspecialchars($app_details['email']); ?></span></div></div>
                <div class="info-item"><i class='bx bx-phone'></i> <div><strong>Phone:</strong> <span><?php echo htmlspecialchars($app_details['phone'] ?? 'N/A'); ?></span></div></div>
                <div class="info-item"><i class='bx bx-map'></i> <div><strong>Address:</strong> <span><?php echo htmlspecialchars($app_details['address'] ?? 'N/A'); ?></span></div></div>
                <div class="info-item"><i class='bx bx-category'></i> <div><strong>Category:</strong> <span class="badge badge-light-blue"><?php echo htmlspecialchars($app_details['category_name']); ?></span></div></div>
                <div class="info-item"><i class='bx bx-calendar'></i> <div><strong>Applied On:</strong> <span><?php echo date('F d, Y h:i A', strtotime($app_details['created_at'])); ?></span></div></div>
            </div>
            
            <?php if ($app_details['application_data']): ?>
            <div class="app-data-section">
                <h3><i class='bx bx-edit-alt'></i> Application Technical Data</h3>
                <?php $data = json_decode($app_details['application_data'], true); ?>
                <?php if ($data): ?>
                    <div class="technical-grid-box">
                        <?php foreach ($data as $k => $v): 
                            $display_val = '';
                            if (is_bool($v)) {
                                $display_val = htmlspecialchars($v ? 'Yes' : 'No');
                            } elseif (is_null($v)) {
                                $display_val = 'N/A';
                            } elseif (is_array($v)) {
                                if (empty($v)) {
                                    $display_val = 'None';
                                } elseif (isset($v['day']) && isset($v['month']) && isset($v['year'])) {
                                    $display_val = htmlspecialchars(sprintf('%02d/%02d/%04d', $v['day'], $v['month'], $v['year']));
                                } else {
                                    $items = [];
                                    foreach ($v as $index => $item) {
                                        if (is_array($item)) {
                                            if (isset($item['day']) && isset($item['month']) && isset($item['year'])) {
                                                $items[] = sprintf('Child %d: %02d/%02d/%04d', $index + 1, $item['day'], $item['month'], $item['year']);
                                            } else {
                                                $items[] = json_encode($item);
                                            }
                                        } else {
                                            $items[] = (string)$item;
                                        }
                                    }
                                    $display_val = implode('<br>', array_map('htmlspecialchars', $items));
                                }
                            } else {
                                $display_val = htmlspecialchars((string)$v);
                            }
                        ?>
                            <p><strong><?php echo ucwords(str_replace('_',' ',$k)); ?>:</strong> <span><?php echo $display_val; ?></span></p>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <pre class="raw-json-box"><?php echo htmlspecialchars($app_details['application_data']); ?></pre>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>

        <?php if ($app_details['plan_name']): ?>
        <div class="card plan-showcase-card">
            <h2><i class='bx bxs-shield-quarter'></i> Customer's Chosen Plan</h2>
            <div class="info-grid-layout">
                <div class="info-item"><i class='bx bx-shield'></i> <div><strong>Plan Name:</strong> <span class="badge badge-plan"><?php echo htmlspecialchars($app_details['plan_name']); ?></span></div></div>
                <div class="info-item"><i class='bx bxs-bank'></i> <div><strong>Company:</strong> <span><?php echo htmlspecialchars($app_details['insurance_company']); ?></span></div></div>
                <div class="info-item"><i class='bx bx-money'></i> <div><strong>Base Price:</strong> <span class="txt-medium">$<?php echo number_format($app_details['base_price'], 2); ?></span></div></div>
            </div>
            <?php if ($app_details['plan_bio']): ?>
                <div class="plan-bio-text">
                    <i class='bx bx-info-circle'></i> <?php echo htmlspecialchars($app_details['plan_bio']); ?>
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="card">
            <h2><i class='bx bxs-file-doc'></i> Attached Verification Documents</h2>
            <?php if ($documents && mysqli_num_rows($documents) > 0): ?>
                <div class="table-responsive-container">
                    <table class="minimal-table">
                        <thead><tr><th>Document Type</th><th>File Path Name</th><th>Uploaded Date</th><th>Action</th></tr></thead>
                        <tbody>
                            <?php while ($doc = mysqli_fetch_assoc($documents)): ?>
                                <tr>
                                    <td><span class="txt-medium"><i class='bx bx-file' style="color:var(--action-blue);"></i> <?php echo htmlspecialchars($doc['doc_type']); ?></span></td>
                                    <td class="txt-muted font-mono"><?php echo htmlspecialchars($doc['file_path']); ?></td>
                                    <td class="txt-muted"><?php echo date('M d, Y', strtotime($doc['uploaded_at'])); ?></td>
                                    <td><a href="/Graduation-Project/<?php echo htmlspecialchars($doc['file_path']); ?>" target="_blank" class="btn btn-sm btn-view-doc"><i class='bx bx-show-alt'></i> View File</a></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="no-data-embedded"><i class='bx bx-shield-x'></i> No documents attached to this application.</div>
            <?php endif; ?>
        </div>

        <?php if ($app_details['status'] === 'under_review'): ?>
        <div class="card decision-card review-action-card">
            <h2><i class='bx bxs-cog animate-spin'></i> Require Action: Review Decision</h2>
            <p class="decision-desc">Please carefully review all the documentation and form parameters submitted above before rendering a decision.</p>
            <form action="AgentDashboard.php" method="post" class="decision-buttons-flex">
                <input type="hidden" name="application_id" value="<?php echo $app_details['application_id']; ?>">
                <button type="submit" name="update_status" value="awaiting_payment" class="btn btn-success"><i class='bx bxs-check-circle'></i> Approve & Request Payment</button>
                <button type="submit" name="update_status" value="rejected" class="btn btn-delete"><i class='bx bxs-x-circle'></i> Reject Application</button>
            </form>
        </div>



        <?php elseif ($app_details['status'] === 'paid' && $policy): ?>
        <div class="card decision-card policy-success-card">
            <h2><i class='bx bxs-badge-check' style="color: var(--success-green);"></i> Policy Legal Ledger Successfully Issued</h2>
            <div class="policy-details-grid">
                <p><strong><i class='bx bxs-key'></i> Policy Number:</strong> <code class="policy-code-large"><?php echo htmlspecialchars($policy['policy_number']); ?></code></p>
                <p><strong><i class='bx bx-receipt'></i> Payment Reference:</strong> <span class="txt-medium"><?php echo htmlspecialchars($policy['payment_ref'] ?? 'N/A'); ?></span></p>
                <p><strong><i class='bx bx-calendar-check'></i> Valid Commencement Date:</strong> <span class="txt-medium"><?php echo date('F d, Y', strtotime($policy['start_date'])); ?></span></p>
                <p><strong><i class='bx bx-calendar-x'></i> Valid Expiration Date:</strong> <span class="txt-medium"><?php echo date('F d, Y', strtotime($policy['end_date'])); ?></span></p>
                <p><strong><i class='bx bxs-info-circle'></i> Operations Status:</strong> <span class="badge badge-success-pill"><?php echo ucfirst($policy['status']); ?></span></p>
                <p><strong><i class='bx bx-download'></i> Legal Document:</strong> <a href="download_policy.php?id=<?php echo $app_details['application_id']; ?>" target="_blank" class="btn btn-sm btn-download-pdf"><i class='bx bxs-file-pdf'></i> Download Official PDF Ledger</a></p>
            </div>
        </div>

        <?php elseif ($app_details['status'] === 'rejected'): ?>
        <div class="card decision-card review-action-card">
            <h2><i class='bx bx-message-rounded-x' style="color: var(--error-red);"></i> Application Rejection Feedback</h2>
            <p class="decision-desc">Provide feedback or a message to the customer explaining why their application was rejected and what documents or information they need to provide.</p>
            
            <form action="AgentDashboard.php?tab=details&id=<?php echo $app_details['application_id']; ?>" method="post">
                <input type="hidden" name="application_id" value="<?php echo $app_details['application_id']; ?>">
                <div class="form-group" style="margin-bottom: 15px;">
                    <label for="rejection_message" style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 8px; color: var(--text-main);">Message to Customer:</label>
                    <textarea name="rejection_message" id="rejection_message" class="form-control" style="width: 100%; min-height: 100px; padding: 12px; border-radius: 6px; border: 1px solid var(--border-color); font-family: inherit; font-size: 14px; background: var(--white); resize: vertical;" placeholder="Type the reason why this application was rejected, or instructions for what new documents they should upload..." required><?php 
                        $app_data = json_decode($app_details['application_data'] ?? '{}', true);
                        echo htmlspecialchars($app_data['rejection_message'] ?? ''); 
                    ?></textarea>
                </div>
                <div class="form-submit-wrapper" style="margin-top: 15px;">
                    <button type="submit" name="submit_rejection_message" class="btn btn-delete"><i class='bx bx-send'></i> Send Message</button>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <div class="back-action-container">
            <a href="AgentDashboard.php?tab=applications" class="btn btn-cancel"><i class='bx bx-arrow-back'></i> Return to Work Queue</a>
        </div>

    <?php endif; ?>
</div>
</body>
</html>