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
                ? "Application approved ✅ — customer notified to proceed with payment."
                : "Application rejected ❌.";
            header("Location: AgentDashboard.php?tab=details&id=$application_id&success=" . urlencode($msg));
            exit();
        } else {
            $error = "You can only decide on applications that are 'Under Review'.";
        }
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
            $msg = "Payment confirmed ✅ Policy issued: $policy_no";
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
           p.name as plan_name, p.insurance_company
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
    <link rel="stylesheet" href="assets/css/AgentDashboard.css">
</head>
<body>

<div class="sidebar">
    <div class="sidebar-header">
        Agent Panel
        <span>Welcome, <?php echo htmlspecialchars($_SESSION['name'] ?? 'Agent'); ?></span>
    </div>
    <nav>
        <?php
        $nav = [
            'applications' => ['📋', 'Under Review',      'under_review',     '#e65100'],
            'awaiting'     => ['💳', 'Awaiting Payment',  'awaiting_payment', '#7b1fa2'],
            'paid'         => ['✅', 'Paid / Issued',      'paid',             '#1b5e20'],
            'rejected'     => ['❌', 'Rejected',           'rejected',         '#c62828'],
        ];
        foreach ($nav as $tab => [$icon, $label, $status, $color]):
            $is_active = ($active_tab === $tab || ($active_tab === 'details' && $tab === 'applications'));
        ?>
        <a href="AgentDashboard.php?tab=<?php echo $tab; ?>" class="<?php echo $is_active ? 'active' : ''; ?>">
            <span class="icon"><?php echo $icon; ?></span> <?php echo $label; ?>
            <?php if ($cnt[$status] > 0): ?>
                <span style="margin-left:auto; background:<?php echo $color; ?>; color:#fff; border-radius:12px; padding:2px 8px; font-size:11px;"><?php echo $cnt[$status]; ?></span>
            <?php endif; ?>
        </a>
        <?php endforeach; ?>
    </nav>
    <div class="sidebar-footer">
        <a href="/Graduation-Project/auth/logout.php">🚪 Logout</a>
    </div>
</div>

<div class="main-content">

    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <?php
    $tab_config = [
        'applications' => ['📋 My Work Queue',      'Applications assigned to you for document verification and approval.'],
        'awaiting'     => ['💳 Awaiting Payment',    'Approved applications waiting for customer payment and policy issuance.'],
        'paid'         => ['✅ Paid & Issued',        'Fully completed policies. Payment received and policy document issued.'],
        'rejected'     => ['❌ Rejected Applications','Applications that were rejected after review.'],
    ];
    $cols_review = ['App ID','Customer','Category','Chosen Plan','Final Price','Date','Action'];
    $cols_awaiting = ['App ID','Customer','Category','Plan','Final Price','Date','Action'];
    $cols_paid    = ['App ID','Customer','Email','Plan','Final Price','Policy No.'];
    $cols_rejected = ['App ID','Customer','Category','Date'];
    ?>

    <!-- ── LIST TABS ── -->
    <?php if (in_array($active_tab, ['applications','awaiting','paid','rejected'])): ?>
        <?php [$title, $subtitle] = $tab_config[$active_tab]; ?>
        <div class="page-title"><?php echo $title; ?></div>
        <div class="page-subtitle"><?php echo $subtitle; ?></div>

        <div class="card">
            <h2><?php echo $title; ?></h2>
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
                            // For paid rows we need policy info
                            $row_policy = null;
                            if ($active_tab === 'paid') {
                                $row_policy = mysqli_fetch_assoc(mysqli_query($connect, "SELECT policy_number FROM policies WHERE application_id = {$row['application_id']} LIMIT 1"));
                            }
                            ?>
                            <tr>
                                <td><?php echo $row['application_id']; ?></td>
                                <td><?php echo htmlspecialchars($row['customer_name'] ?? 'Unknown'); ?></td>
                                <?php if ($active_tab === 'paid'): ?>
                                    <td><?php echo htmlspecialchars($row['customer_email'] ?? 'N/A'); ?></td>
                                <?php else: ?>
                                    <td><?php echo htmlspecialchars($row['category_name'] ?? 'N/A'); ?></td>
                                <?php endif; ?>
                                <?php if ($active_tab !== 'rejected'): ?>
                                    <td>
                                        <?php if ($row['plan_name']): ?>
                                            <span class="badge" style="background:#fff3e0; color:#e65100;"><?php echo htmlspecialchars($row['plan_name']); ?></span>
                                        <?php else: ?>—<?php endif; ?>
                                    </td>
                                    <td><?php echo $row['final_price'] ? '<strong>$' . number_format($row['final_price'], 2) . '</strong>' : '—'; ?></td>
                                <?php endif; ?>
                                <?php if ($active_tab === 'paid'): ?>
                                    <td>
                                        <?php if ($row_policy): ?>
                                            <code style="font-size:12px; background:#f1f3f4; padding:2px 6px; border-radius:4px;"><?php echo htmlspecialchars($row_policy['policy_number']); ?></code>
                                        <?php else: ?>—<?php endif; ?>
                                    </td>
                                <?php else: ?>
                                    <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                                    <td>
                                        <a href="AgentDashboard.php?tab=details&id=<?php echo $row['application_id']; ?>" class="btn btn-sm btn-primary">📄 View</a>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="7" class="no-data">No applications in this stage.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    <!-- ── DETAILS TAB ── -->
    <?php elseif ($active_tab === 'details' && $app_details): ?>
        <div class="page-title">Application #<?php echo $app_details['application_id']; ?></div>
        <div class="page-subtitle">Full application review — customer data, chosen plan, documents, and decisions.</div>

        <!-- Status Banner -->
        <div style="display:flex; align-items:center; gap:12px; margin-bottom:20px; padding:14px 20px; background:#fff; border-radius:10px; border:1px solid #eef0f3;">
            <span style="font-size:13px; color:#64748b;">Current Status:</span>
            <span class="badge" style="font-size:14px; <?php echo statusBadgeStyle($app_details['status']); ?>">
                <?php echo statusLabel($app_details['status']); ?>
            </span>
        </div>

        <!-- Customer Info -->
        <div class="card">
            <h2 style="border-bottom:1px solid #eef0f3; padding-bottom:12px; margin-bottom:20px;">👤 Customer Information</h2>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px; font-size:14px; color:#374151;">
                <p><strong>Name:</strong> <?php echo htmlspecialchars($app_details['customer_name']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($app_details['email']); ?></p>
                <p><strong>Phone:</strong> <?php echo htmlspecialchars($app_details['phone'] ?? 'N/A'); ?></p>
                <p><strong>Address:</strong> <?php echo htmlspecialchars($app_details['address'] ?? 'N/A'); ?></p>
                <p><strong>Category:</strong> <?php echo htmlspecialchars($app_details['category_name']); ?></p>
                <p><strong>Applied On:</strong> <?php echo date('F d, Y h:i A', strtotime($app_details['created_at'])); ?></p>
            </div>
            <?php if ($app_details['application_data']): ?>
            <div style="margin-top:20px; padding-top:20px; border-top:1px solid #eef0f3;">
                <h3 style="font-size:15px; margin-bottom:10px;">📝 Application Data:</h3>
                <?php $data = json_decode($app_details['application_data'], true); ?>
                <?php if ($data): ?>
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; font-size:13px; background:#f7f8fa; padding:15px; border-radius:8px;">
                        <?php foreach ($data as $k => $v): ?>
                            <p><strong><?php echo ucwords(str_replace('_',' ',$k)); ?>:</strong> <?php echo htmlspecialchars((string)$v); ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <pre style="background:#f7f8fa; padding:15px; border-radius:5px; font-size:13px;"><?php echo htmlspecialchars($app_details['application_data']); ?></pre>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Chosen Plan -->
        <?php if ($app_details['plan_name']): ?>
        <div class="card">
            <h2>🛡️ Customer's Chosen Plan</h2>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px; font-size:14px; color:#374151;">
                <p><strong>Plan Name:</strong> <span class="badge" style="background:#fff3e0; color:#e65100;"><?php echo htmlspecialchars($app_details['plan_name']); ?></span></p>
                <p><strong>Insurance Company:</strong> <?php echo htmlspecialchars($app_details['insurance_company']); ?></p>
                <p><strong>Base Price:</strong> $<?php echo number_format($app_details['base_price'], 2); ?></p>
                <p><strong>Final Quoted Price:</strong> <span style="font-size:18px; font-weight:700; color:#1b5e20;">$<?php echo number_format($app_details['final_price'], 2); ?></span></p>
            </div>
            <?php if ($app_details['plan_bio']): ?>
                <p style="margin-top:12px; font-size:13px; color:#64748b; border-top:1px solid #eef0f3; padding-top:12px;"><?php echo htmlspecialchars($app_details['plan_bio']); ?></p>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Documents -->
        <div class="card">
            <h2>📁 Attached Documents</h2>
            <?php if ($documents && mysqli_num_rows($documents) > 0): ?>
                <table>
                    <thead><tr><th>Type</th><th>File</th><th>Uploaded</th><th>Action</th></tr></thead>
                    <tbody>
                        <?php while ($doc = mysqli_fetch_assoc($documents)): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($doc['doc_type']); ?></strong></td>
                                <td><?php echo htmlspecialchars($doc['file_path']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($doc['uploaded_at'])); ?></td>
                                <td><a href="/Graduation-Project/<?php echo htmlspecialchars($doc['file_path']); ?>" target="_blank" class="btn btn-sm btn-edit">🔍 View</a></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="no-data">No documents attached yet.</p>
            <?php endif; ?>
        </div>

        <!-- ── DECISION CARDS (context-sensitive) ── -->

        <?php if ($app_details['status'] === 'under_review'): ?>
        <!-- Agent Approval Decision -->
        <div class="card" style="background:#f8fafc; border:1px solid #e2e8f0;">
            <h2>⚙️ Application Decision</h2>
            <p style="font-size:14px; color:#64748b; margin-bottom:20px;">Review all data above, then make your decision. Approving will move the customer to the Payment stage.</p>
            <form action="AgentDashboard.php" method="post" style="display:flex; gap:15px;">
                <input type="hidden" name="application_id" value="<?php echo $app_details['application_id']; ?>">
                <button type="submit" name="update_status" value="awaiting_payment" class="btn btn-success">✅ Approve — Request Payment</button>
                <button type="submit" name="update_status" value="rejected" class="btn btn-delete">❌ Reject Application</button>
            </form>
        </div>

        <?php elseif ($app_details['status'] === 'awaiting_payment'): ?>
        <!-- Payment Confirmation & Policy Issuance -->
        <div class="card" style="background:#fdf4ff; border:1px solid #e1bee7;">
            <h2>💳 Confirm Payment & Issue Policy</h2>
            <p style="font-size:14px; color:#64748b; margin-bottom:20px;">
                Once the customer has paid (via Fawry, bank card, or cash), fill in the details below to issue the official policy.
                The system will auto-generate a policy number.
            </p>
            <form action="AgentDashboard.php" method="post">
                <input type="hidden" name="application_id" value="<?php echo $app_details['application_id']; ?>">
                <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:15px;">
                    <div class="form-group">
                        <label>Payment Reference</label>
                        <input type="text" name="payment_ref" placeholder="e.g. FAWRY-00123456" required>
                    </div>
                    <div class="form-group">
                        <label>Policy Start Date</label>
                        <input type="date" name="start_date" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Policy End Date</label>
                        <input type="date" name="end_date" value="<?php echo date('Y-m-d', strtotime('+1 year')); ?>" required>
                    </div>
                </div>
                <div style="margin-top:10px;">
                    <button type="submit" name="issue_policy" class="btn btn-success">🧾 Confirm Payment & Issue Policy</button>
                </div>
            </form>
        </div>

        <?php elseif ($app_details['status'] === 'paid' && $policy): ?>
        <!-- Policy Issued — Info Display -->
        <div class="card" style="background:#e8f5e9; border:1px solid #a5d6a7;">
            <h2>🧾 Policy Issued</h2>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px; font-size:14px; color:#1b5e20;">
                <p><strong>Policy Number:</strong> <code style="background:#c8e6c9; padding:3px 8px; border-radius:4px;"><?php echo htmlspecialchars($policy['policy_number']); ?></code></p>
                <p><strong>Payment Ref:</strong> <?php echo htmlspecialchars($policy['payment_ref'] ?? 'N/A'); ?></p>
                <p><strong>Valid From:</strong> <?php echo date('F d, Y', strtotime($policy['start_date'])); ?></p>
                <p><strong>Valid Until:</strong> <?php echo date('F d, Y', strtotime($policy['end_date'])); ?></p>
                <p><strong>Policy Status:</strong> <?php echo ucfirst($policy['status']); ?></p>
                <p><strong>Document:</strong> <a href="/Graduation-Project/<?php echo htmlspecialchars($policy['document_path']); ?>" class="btn btn-sm btn-edit">📥 Download PDF</a></p>
            </div>
        </div>

        <?php elseif ($app_details['status'] === 'rejected'): ?>
        <div class="card" style="background:#fdecea; border:1px solid #ef9a9a;">
            <p style="color:#c62828; font-weight:600;">❌ This application was rejected. No further action required.</p>
        </div>
        <?php endif; ?>

        <a href="AgentDashboard.php?tab=applications" class="btn btn-cancel">← Back to List</a>

    <?php endif; ?>
</div>
</body>
</html>
