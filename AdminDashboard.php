<?php
require_once __DIR__ . '/includes/auth_check.php';
requireRole('admin');
// Note: connection.php is already included by auth_check.php, no need to include it again

$error = '';
$success = '';

// Handle Add Agent
if (isset($_POST['add_agent'])) {
    $name = mysqli_real_escape_string($connect, $_POST['name']);
    $email = mysqli_real_escape_string($connect, $_POST['email']);
    $password = $_POST['password'];
    $role = 'agent';

    $check_email = mysqli_query($connect, "SELECT * FROM users WHERE email = '$email'");
    if (mysqli_num_rows($check_email) > 0) {
        $error = "Email already exists!";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);     
        $insert = mysqli_query($connect, "INSERT INTO users (name, email, password, role) VALUES ('$name', '$email', '$hashed_password', '$role')");
        if ($insert) {
            $success = "Agent added successfully!";
        } else {
            $error = "Failed to add agent! " . mysqli_error($connect);
        }
    }
}

// Handle Delete Agent
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $delete = mysqli_query($connect, "DELETE FROM users WHERE user_id = $id AND role = 'agent'");
    if ($delete) {
        $success = "Agent deleted successfully!";
        // Redirect to avoid re-deletion on refresh
        header("Location: AdminDashboard.php?success=" . urlencode($success));
        exit();
    } else {
        $error = "Failed to delete agent! " . mysqli_error($connect);
    }
}

// Handle Edit Agent
if (isset($_POST['edit_agent'])) {
    $id = (int)$_POST['user_id'];
    $name = mysqli_real_escape_string($connect, $_POST['name']);
    $email = mysqli_real_escape_string($connect, $_POST['email']);
    
    // Check email uniqueness excluding current user
    $check_email = mysqli_query($connect, "SELECT * FROM users WHERE email = '$email' AND user_id != $id");
    if (mysqli_num_rows($check_email) > 0) {
        $error = "Email already exists!";
    } else {
        if (!empty($_POST['password'])) {
            $hashed_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $update = mysqli_query($connect, "UPDATE users SET name = '$name', email = '$email', password = '$hashed_password' WHERE user_id = $id AND role = 'agent'");
        } else {
            $update = mysqli_query($connect, "UPDATE users SET name = '$name', email = '$email' WHERE user_id = $id AND role = 'agent'");
        }
        
        if ($update) {
            $success = "Agent updated successfully!";
            header("Location: AdminDashboard.php?success=" . urlencode($success));
            exit();
        } else {
            $error = "Failed to update agent! " . mysqli_error($connect);
        }
    }
}

// Handle Add Plan
if (isset($_POST['add_plan'])) {
    $name = mysqli_real_escape_string($connect, $_POST['name']);
    $category_id = (int)$_POST['category_id'];
    $company = mysqli_real_escape_string($connect, $_POST['insurance_company']);
    $details = mysqli_real_escape_string($connect, $_POST['bio']);
    $price = (float)$_POST['base_price'];
    
    $eligibility_raw = trim($_POST['eligibility_rules']);
    $eligibility = 'NULL';
    $json_is_valid = true;

    if ($eligibility_raw !== '') {
        if (json_decode($eligibility_raw) === null && json_last_error() !== JSON_ERROR_NONE) {
            $json_is_valid = false;
        } else {
            $eligibility = "'" . mysqli_real_escape_string($connect, $eligibility_raw) . "'";
        }
    }

    if (!$json_is_valid) {
        $error = "Eligibility rules must be valid JSON format (e.g. [\"Rule 1\", \"Rule 2\"])";
    } else {
        $insert = mysqli_query($connect, "INSERT INTO insurance_plans (name, category_id, insurance_company, bio, base_price, eligibility_rules) VALUES ('$name', $category_id, '$company', '$details', $price, $eligibility)");
        if ($insert) {
            $success = "Insurance plan added successfully!";
            header("Location: AdminDashboard.php?tab=add_plan&success=" . urlencode($success));
            exit();
        } else {
            $error = "Failed to add insurance plan! " . mysqli_error($connect);
        }
    }
}

// Handle Assign Agent — only allowed when status = waiting_docs
if (isset($_POST['assign_agent'])) {
    $application_id = (int)$_POST['application_id'];
    $agent_id = (int)$_POST['agent_id'];

    // Ensure application is in waiting_docs before assigning
    $check = mysqli_query($connect, "SELECT application_id FROM applications WHERE application_id = $application_id AND status = 'waiting_docs'");
    if ($check && mysqli_num_rows($check) > 0) {
        $update = mysqli_query($connect, "UPDATE applications SET agent_id = $agent_id, status = 'under_review' WHERE application_id = $application_id");
        if ($update) {
            $success = "Agent assigned successfully! Application is now under review.";
            header("Location: AdminDashboard.php?tab=applications&success=" . urlencode($success));
            exit();
        } else {
            $error = "Failed to assign application! " . mysqli_error($connect);
        }
    } else {
        $error = "Can only assign an agent when application is in 'Waiting Docs' status.";
    }
}

// Handle Add Category
if (isset($_POST['add_category'])) {
    $cat_name = mysqli_real_escape_string($connect, $_POST['category_name']);
    $insert_cat = mysqli_query($connect, "INSERT INTO categories (name) VALUES ('$cat_name')");
    if ($insert_cat) {
        $success = "Category added successfully!";
        header("Location: AdminDashboard.php?tab=add_category&success=" . urlencode($success));
        exit();
    } else {
        $error = "Failed to add category! " . mysqli_error($connect);
    }
}

// Fetch agents
$agents = mysqli_query($connect, "SELECT * FROM users WHERE role = 'agent'");

// Fetch customers
$customers = mysqli_query($connect, "SELECT * FROM users WHERE role = 'customer'");

// Fetch categories for the plan form
$categories_result = mysqli_query($connect, "SELECT * FROM categories");
$categories = [];
if ($categories_result) {
    while ($cat = mysqli_fetch_assoc($categories_result)) {
        $categories[] = $cat;
    }
}

// Fetch applications — Admin manages only the early stages (assign agent)
$applications_query = "
    SELECT a.*,
           c.name as customer_name,
           cat.name as category_name,
           ag.name as agent_name,
           p.name as plan_name,
           p.insurance_company
    FROM applications a
    LEFT JOIN users c ON a.customer_id = c.user_id
    LEFT JOIN categories cat ON a.category_id = cat.category_id
    LEFT JOIN users ag ON a.agent_id = ag.user_id
    LEFT JOIN insurance_plans p ON a.plan_id = p.plan_id
    WHERE a.status IN ('pending_selection', 'waiting_docs')
    ORDER BY a.created_at DESC
";
$applications = mysqli_query($connect, $applications_query);

// Stats for overview
$stats = [];
foreach (['pending_selection','waiting_docs','under_review','awaiting_payment','paid','rejected'] as $s) {
    $r = mysqli_fetch_assoc(mysqli_query($connect, "SELECT COUNT(*) as cnt FROM applications WHERE status='$s'"));
    $stats[$s] = $r['cnt'];
}
$total_apps   = array_sum($stats);
$total_agents = mysqli_fetch_assoc(mysqli_query($connect, "SELECT COUNT(*) as cnt FROM users WHERE role='agent'"))['cnt'];
$total_customers = mysqli_fetch_assoc(mysqli_query($connect, "SELECT COUNT(*) as cnt FROM users WHERE role='customer'"))['cnt'];
$total_plans  = mysqli_fetch_assoc(mysqli_query($connect, "SELECT COUNT(*) as cnt FROM insurance_plans"))['cnt'];

// Fetch all plans for viewer
$all_plans = mysqli_query($connect, "
    SELECT p.*, cat.name as category_name
    FROM insurance_plans p
    LEFT JOIN categories cat ON p.category_id = cat.category_id
    ORDER BY cat.name, p.base_price ASC
");

// Edit agent fetch
$edit_agent = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $result = mysqli_query($connect, "SELECT * FROM users WHERE user_id = $id AND role = 'agent'");
    if (mysqli_num_rows($result) > 0) {
        $edit_agent = mysqli_fetch_assoc($result);
    }
}

if (isset($_GET['success'])) {
    $success = $_GET['success'];
}

// Determine active tab: if editing, force "add" tab; otherwise use ?tab= param (default: manage)
$active_tab = isset($_GET['edit']) ? 'add' : (isset($_GET['tab']) ? $_GET['tab'] : 'overview');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="assets/css/AdminDashboard.css">
</head>
<body>

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            Admin Panel
            <span>Welcome, <?php echo htmlspecialchars($_SESSION['name'] ?? 'Admin'); ?></span>
        </div>
        <nav>
            <a href="AdminDashboard.php?tab=overview" class="<?php echo $active_tab === 'overview' ? 'active' : ''; ?>">
                <span class="icon">🏠</span> Overview
            </a>
            <a href="AdminDashboard.php?tab=applications" class="<?php echo $active_tab === 'applications' ? 'active' : ''; ?>">
                <span class="icon">📄</span> Applications
            </a>
            <a href="AdminDashboard.php?tab=plans" class="<?php echo $active_tab === 'plans' ? 'active' : ''; ?>">
                <span class="icon">🛡️</span> Insurance Plans
            </a>
            <a href="AdminDashboard.php?tab=manage" class="<?php echo $active_tab === 'manage' ? 'active' : ''; ?>">
                <span class="icon">👥</span> Manage Agents
            </a>
            <a href="AdminDashboard.php?tab=add" class="<?php echo $active_tab === 'add' ? 'active' : ''; ?>">
                <span class="icon">➕</span> Add Agent
            </a>
            <a href="AdminDashboard.php?tab=customers" class="<?php echo $active_tab === 'customers' ? 'active' : ''; ?>">
                <span class="icon">👤</span> Customers
            </a>
            <a href="AdminDashboard.php?tab=add_plan" class="<?php echo $active_tab === 'add_plan' ? 'active' : ''; ?>">
                <span class="icon">📋</span> Add Plan
            </a>
            <a href="AdminDashboard.php?tab=add_category" class="<?php echo $active_tab === 'add_category' ? 'active' : ''; ?>">
                <span class="icon">📁</span> Add Category
            </a>
        </nav>
        <div class="sidebar-footer">
            <a href="/Graduation-Project/auth/logout.php">🚪 Logout</a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <!-- ── OVERVIEW TAB ── -->
        <?php if ($active_tab === 'overview'): ?>
            <div class="page-title">Dashboard Overview</div>
            <div class="page-subtitle">A live summary of all activity across the insurance platform.</div>

            <div style="display:grid; grid-template-columns: repeat(4,1fr); gap:20px; margin-bottom:30px;">
                <div class="card" style="text-align:center; padding:24px;">
                    <div style="font-size:32px;">📄</div>
                    <div style="font-size:28px; font-weight:700; color:#1a73e8; margin:8px 0;"><?php echo $total_apps; ?></div>
                    <div style="color:#64748b; font-size:13px;">Total Applications</div>
                </div>
                <div class="card" style="text-align:center; padding:24px;">
                    <div style="font-size:32px;">👥</div>
                    <div style="font-size:28px; font-weight:700; color:#0f9d58; margin:8px 0;"><?php echo $total_agents; ?></div>
                    <div style="color:#64748b; font-size:13px;">Active Agents</div>
                </div>
                <div class="card" style="text-align:center; padding:24px;">
                    <div style="font-size:32px;">👤</div>
                    <div style="font-size:28px; font-weight:700; color:#e65100; margin:8px 0;"><?php echo $total_customers; ?></div>
                    <div style="color:#64748b; font-size:13px;">Registered Customers</div>
                </div>
                <div class="card" style="text-align:center; padding:24px;">
                    <div style="font-size:32px;">🛡️</div>
                    <div style="font-size:28px; font-weight:700; color:#c62828; margin:8px 0;"><?php echo $total_plans; ?></div>
                    <div style="color:#64748b; font-size:13px;">Insurance Plans</div>
                </div>
            </div>

            <div class="card">
                <h2>📊 Application Pipeline</h2>
                <table>
                    <thead><tr><th>Stage</th><th>Count</th><th>Description</th></tr></thead>
                    <tbody>
                        <tr><td><span class="badge" style="background:#f1f3f4;color:#5f6368;">Pending Selection</span></td><td><?php echo $stats['pending_selection']; ?></td><td style="color:#64748b;font-size:13px;">Customer submitted data — awaiting plan choice</td></tr>
                        <tr><td><span class="badge" style="background:#e3f0ff;color:#1a73e8;">Waiting Docs</span></td><td><?php echo $stats['waiting_docs']; ?></td><td style="color:#64748b;font-size:13px;">Plan selected — customer uploading documents. Admin should assign Agent.</td></tr>
                        <tr><td><span class="badge" style="background:#fff3e0;color:#e65100;">Under Review</span></td><td><?php echo $stats['under_review']; ?></td><td style="color:#64748b;font-size:13px;">Agent is verifying documents and customer data</td></tr>
                        <tr><td><span class="badge" style="background:#fce8ff;color:#7b1fa2;">Awaiting Payment</span></td><td><?php echo $stats['awaiting_payment']; ?></td><td style="color:#64748b;font-size:13px;">Agent approved — customer must complete payment for policy issuance</td></tr>
                        <tr><td><span class="badge" style="background:#e8f5e9;color:#1b5e20;">Paid</span></td><td><?php echo $stats['paid']; ?></td><td style="color:#64748b;font-size:13px;">Payment received — policy issued and active</td></tr>
                        <tr><td><span class="badge" style="background:#fdecea;color:#c62828;">Rejected</span></td><td><?php echo $stats['rejected']; ?></td><td style="color:#64748b;font-size:13px;">Application declined by agent</td></tr>
                    </tbody>
                </table>
            </div>

        <!-- ── ADD AGENT TAB ── -->
        <?php elseif ($active_tab === 'add'): ?>
            <div class="page-title"><?php echo $edit_agent ? 'Edit Agent' : 'Add New Agent'; ?></div>
            <div class="page-subtitle"><?php echo $edit_agent ? 'Update the agent\'s information below.' : 'Fill in the form below to create a new agent account.'; ?></div>

            <div class="card">
                <h2><?php echo $edit_agent ? '✏️ Edit Agent' : '➕ New Agent'; ?></h2>
                <form action="AdminDashboard.php" method="post">
                    <?php if ($edit_agent): ?>
                        <input type="hidden" name="user_id" value="<?php echo $edit_agent['user_id']; ?>">
                    <?php endif; ?>
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="name" value="<?php echo $edit_agent ? htmlspecialchars($edit_agent['name']) : ''; ?>" placeholder="Enter agent's full name" required>
                    </div>
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" name="email" value="<?php echo $edit_agent ? htmlspecialchars($edit_agent['email']) : ''; ?>" placeholder="Enter agent's email" required>
                    </div>
                    <div class="form-group">
                        <label>Password<?php echo $edit_agent ? ' <small style="color:#9ca3af;">(Leave blank to keep current)</small>' : ''; ?></label>
                        <input type="password" name="password" placeholder="<?php echo $edit_agent ? 'Leave blank to keep current password' : 'Enter a strong password'; ?>" <?php echo $edit_agent ? '' : 'required'; ?>>
                    </div>
                    <div class="form-actions">
                        <?php if ($edit_agent): ?>
                            <button type="submit" name="edit_agent" class="btn btn-primary">Update Agent</button>
                            <a href="AdminDashboard.php?tab=add" class="btn btn-cancel">Cancel</a>
                        <?php else: ?>
                            <button type="submit" name="add_agent" class="btn btn-primary">Add Agent</button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

        <!-- ── MANAGE AGENTS TAB ── -->
        <?php elseif ($active_tab === 'manage'): ?>
            <div class="page-title">Manage Agents</div>
            <div class="page-subtitle">View, edit or remove agent accounts from the system.</div>

            <div class="card">
                <h2>👥 Agent Accounts</h2>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($agents && mysqli_num_rows($agents) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($agents)): ?>
                                <tr>
                                    <td><?php echo $row['user_id']; ?></td>
                                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                                    <td><span class="badge">Agent</span></td>
                                    <td>
                                        <a href="AdminDashboard.php?edit=<?php echo $row['user_id']; ?>" class="btn btn-sm btn-edit">✏️ Edit</a>
                                        <a href="AdminDashboard.php?delete=<?php echo $row['user_id']; ?>" class="btn btn-sm btn-delete" onclick="return confirm('Are you sure you want to delete this agent?');">🗑️ Delete</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class="no-data">No agents found. <a href="AdminDashboard.php?tab=add">Add one now →</a></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        <!-- ── CUSTOMERS TAB ── -->
        <?php elseif ($active_tab === 'customers'): ?>
            <div class="page-title">Customers</div>
            <div class="page-subtitle">View registered customers in the system.</div>

            <div class="card">
                <h2>👤 Customer Accounts</h2>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Address</th>
                            <th>Role</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($customers && mysqli_num_rows($customers) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($customers)): ?>
                                <tr>
                                    <td><?php echo $row['user_id']; ?></td>
                                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                                    <td><?php echo htmlspecialchars($row['phone'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($row['address'] ?? 'N/A'); ?></td>
                                    <td><span class="badge" style="background:#e8f5e9; color:#1b5e20;">Customer</span></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="6" class="no-data">No customers found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        <!-- ── APPLICATIONS TAB ── -->
        <?php elseif ($active_tab === 'applications'): ?>
            <div class="page-title">New Customer Applications</div>
            <div class="page-subtitle">Showing only applications in <strong>Pending Selection</strong> or <strong>Waiting Docs</strong> status. Assign an agent once the customer has selected a plan and is ready to upload documents.</div>

            <div class="card">
                <h2>📄 Applications List</h2>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Customer</th>
                            <th>Category</th>
                            <th>Chosen Plan</th>
                            <th>Final Price</th>
                            <th>Status</th>
                            <th>Agent</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($applications && mysqli_num_rows($applications) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($applications)): ?>
                                <tr>
                                    <td><?php echo $row['application_id']; ?></td>
                                    <td><?php echo htmlspecialchars($row['customer_name'] ?? 'Unknown'); ?></td>
                                    <td><?php echo htmlspecialchars($row['category_name'] ?? 'N/A'); ?></td>
                                    <td>
                                        <?php if ($row['plan_name']): ?>
                                            <span class="badge" style="background:#fff3e0; color:#e65100;"><?php echo htmlspecialchars($row['plan_name']); ?></span><br>
                                            <small style="color:#9ca3af;"><?php echo htmlspecialchars($row['insurance_company']); ?></small>
                                        <?php else: ?>
                                            <span style="color:#9ca3af; font-size:12px;">Not chosen yet</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php echo $row['final_price'] ? '<strong>$' . number_format($row['final_price'], 2) . '</strong>' : '<span style="color:#9ca3af;">—</span>'; ?>
                                    </td>
                                    <td>
                                        <span class="badge" style="<?php
                                            if ($row['status'] === 'paid') echo 'background:#e8f5e9; color:#1b5e20;';
                                            elseif ($row['status'] === 'rejected') echo 'background:#fdecea; color:#c62828;';
                                            elseif ($row['status'] === 'under_review') echo 'background:#fff3e0; color:#e65100;';
                                            elseif ($row['status'] === 'waiting_docs') echo 'background:#e3f0ff; color:#1a73e8;';
                                            elseif ($row['status'] === 'awaiting_payment') echo 'background:#fce8ff; color:#7b1fa2;';
                                            else echo 'background:#f1f3f4; color:#5f6368;';
                                        ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $row['status'])); ?>
                                        </span>
                                    </td>
                                    <td><?php echo $row['agent_name'] ? htmlspecialchars($row['agent_name']) : '<span style="color:#9ca3af;">Unassigned</span>'; ?></td>
                                    <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                                    <td>
                                        <?php if ($row['status'] === 'waiting_docs'): ?>
                                            <form action="AdminDashboard.php" method="post" style="display:flex; gap:5px; align-items:center;">
                                                <input type="hidden" name="application_id" value="<?php echo $row['application_id']; ?>">
                                                <select name="agent_id" style="padding:5px; border-radius:4px; border:1px solid #ccc; font-size:12px;" required>
                                                    <option value="">Select Agent...</option>
                                                    <?php
                                                    if ($agents) {
                                                        mysqli_data_seek($agents, 0);
                                                        while ($ag = mysqli_fetch_assoc($agents)):
                                                    ?>
                                                        <option value="<?php echo $ag['user_id']; ?>" <?php echo ($row['agent_id'] == $ag['user_id']) ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($ag['name']); ?>
                                                        </option>
                                                    <?php endwhile; } ?>
                                                </select>
                                                <button type="submit" name="assign_agent" class="btn btn-sm btn-primary">Assign</button>
                                            </form>
                                        <?php elseif (in_array($row['status'], ['under_review','approved','rejected'])): ?>
                                            <span style="color:#9ca3af; font-size:12px;">Agent: <?php echo htmlspecialchars($row['agent_name'] ?? '—'); ?></span>
                                        <?php else: ?>
                                            <span style="color:#9ca3af; font-size:12px;">Awaiting plan choice</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="9" class="no-data">No applications found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        <!-- ── INSURANCE PLANS TAB ── -->
        <?php elseif ($active_tab === 'plans'): ?>
            <div class="page-title">Insurance Plans</div>
            <div class="page-subtitle">All available plans with their eligibility rules. Customers see these during instant quotation.</div>
            <div class="card">
                <h2>🛡️ Plans Catalogue</h2>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Plan Name</th>
                            <th>Category</th>
                            <th>Company</th>
                            <th>Base Price</th>
                            <th>Eligibility Rules</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($all_plans && mysqli_num_rows($all_plans) > 0): ?>
                            <?php while ($p = mysqli_fetch_assoc($all_plans)): ?>
                                <tr>
                                    <td><?php echo $p['plan_id']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($p['name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($p['category_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($p['insurance_company']); ?></td>
                                    <td><strong>$<?php echo number_format($p['base_price'], 2); ?></strong></td>
                                    <td>
                                        <?php
                                        $rules = json_decode($p['eligibility_rules'] ?? '{}', true);
                                        if ($rules) {
                                            echo '<ul style="margin:0; padding-left:16px; font-size:12px; color:#374151;">';
                                            foreach ($rules as $k => $v) {
                                                $val = is_array($v) ? implode(', ', $v) : $v;
                                                echo '<li><b>' . htmlspecialchars(str_replace('_',' ',$k)) . ':</b> ' . htmlspecialchars($val) . '</li>';
                                            }
                                            echo '</ul>';
                                        } else {
                                            echo '<span style="color:#9ca3af;">None</span>';
                                        }
                                        ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="6" class="no-data">No plans found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        <!-- ── ADD PLAN TAB ── -->
        <?php elseif ($active_tab === 'add_plan'): ?>
            <div class="page-title">Add New Insurance Plan</div>
            <div class="page-subtitle">Fill in the form below to create a new insurance plan.</div>

            <div class="card">
                <h2>➕ New Plan</h2>
                <form action="AdminDashboard.php" method="post">
                    <div class="form-group">
                        <label>Plan Name</label>
                        <input type="text" name="name" placeholder="Enter plan name" required>
                    </div>
                    <div class="form-group">
                        <label>Category</label>
                        <select name="category_id" required>
                            <option value="">Select a category</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['category_id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Insurance Company</label>
                        <input type="text" name="insurance_company" placeholder="Enter insurance company name" required>
                    </div>
                    <div class="form-group">
                        <label>Base Price</label>
                        <input type="number" step="0.01" name="base_price" placeholder="Enter base price" required>
                    </div>
                    <div class="form-group">
                        <label>Details / Bio</label>
                        <textarea name="bio" rows="4" placeholder="Enter plan details" required></textarea>
                    </div>
                    <div class="form-group">
                        <label>Eligibility Rules <small style="color:#9ca3af;">(Must be valid JSON)</small></label>
                        <textarea name="eligibility_rules" rows="4" placeholder='e.g. ["Rule 1", "Rule 2"]'></textarea>
                    </div>
                    <div class="form-actions">
                        <button type="submit" name="add_plan" class="btn btn-primary">Add Plan</button>
                    </div>
                </form>
            </div>

        <!-- ── ADD CATEGORY TAB ── -->
        <?php elseif ($active_tab === 'add_category'): ?>
            <div class="page-title">Add New Category</div>
            <div class="page-subtitle">Create a new category for insurance plans.</div>

            <div class="card">
                <h2>📁 New Category</h2>
                <form action="AdminDashboard.php" method="post">
                    <div class="form-group">
                        <label>Category Name</label>
                        <input type="text" name="category_name" placeholder="Enter category name" required>
                    </div>
                    <div class="form-actions">
                        <button type="submit" name="add_category" class="btn btn-primary">Add Category</button>
                    </div>
                </form>
            </div>
        <?php endif; ?>

    </div>
</body>
</html>
