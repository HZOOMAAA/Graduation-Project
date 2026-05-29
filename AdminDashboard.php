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
    $check = mysqli_query($connect, "SELECT application_id FROM applications WHERE application_id = $application_id AND status = 'under_review'");
    if ($check && mysqli_num_rows($check) > 0) {
        $update = mysqli_query($connect, "UPDATE applications SET agent_id = $agent_id, status = 'under_review' WHERE application_id = $application_id");
        if ($update) {
            $success = "Agent assigned successfully!";
            header("Location: AdminDashboard.php?tab=applications&success=" . urlencode($success));
            exit();
        } else {
            $error = "Failed to assign application! " . mysqli_error($connect);
        }
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
    WHERE a.status IN ('under_review')
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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/AdminDashboard.css">
</head>
<body>

    <div class="sidebar">
    <div class="sidebar-header">
        Coverly 
        <span>Welcome, <?php echo htmlspecialchars($_SESSION['name'] ?? 'Admin'); ?></span>
    </div>
    <nav>
        <a href="AdminDashboard.php?tab=overview" class="<?php echo $active_tab === 'overview' ? 'active' : ''; ?>">
            <span class="icon"><i class='bx bx-home'></i></span> Overview
        </a>
        
        <a href="AdminDashboard.php?tab=profile" class="<?php echo $active_tab === 'profile' ? 'active' : ''; ?>">
            <span class="icon"><i class='bx bx-user-circle'></i></span> Admin Profile
        </a>

        <a href="AdminDashboard.php?tab=applications" class="<?php echo $active_tab === 'applications' ? 'active' : ''; ?>">
            <span class="icon"><i class='bx bx-book-content'></i></span> Applications
        </a>
        <a href="AdminDashboard.php?tab=plans" class="<?php echo $active_tab === 'plans' ? 'active' : ''; ?>">
            <span class="icon"><i class='bx bx-shield'></i></span> Insurance Plans
        </a>
        <a href="AdminDashboard.php?tab=manage" class="<?php echo $active_tab === 'manage' ? 'active' : ''; ?>">
            <span class="icon"><i class='bx bx-group'></i></span> Manage Agents
        </a>
        <a href="AdminDashboard.php?tab=add" class="<?php echo $active_tab === 'add' ? 'active' : ''; ?>">
            <span class="icon"><i class='bx bx-user-plus'></i></span> Add Agent
        </a>
        <a href="AdminDashboard.php?tab=customers" class="<?php echo $active_tab === 'customers' ? 'active' : ''; ?>">
            <span class="icon"><i class='bx bx-user'></i></span> Customers
        </a>
        <a href="AdminDashboard.php?tab=add_plan" class="<?php echo $active_tab === 'add_plan' ? 'active' : ''; ?>">
            <span class="icon"><i class='bx bx-add-to-queue'></i></span> Add Plan
        </a>
        <a href="AdminDashboard.php?tab=add_category" class="<?php echo $active_tab === 'add_category' ? 'active' : ''; ?>">
            <span class="icon"><i class='bx bx-envelope'></i></span> Messeges
        </a>
    </nav>
    <div class="sidebar-footer">
        <a href="/Graduation-Project/auth/logout.php"><i class='bx bx-log-out'></i>Logout</a>
    </div>
</div>

    <div class="main-content">

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if ($active_tab === 'overview'): ?>
            <div class="page-title">Dashboard Overview</div>
            <div class="page-subtitle">A live summary of all activity across the insurance platform.</div>
            <!-- test marwan wael -->


            <div class="overview-stats-grid">
                <div class="stat-card">
                    <div class="stat-card-icon"><i class='bx bx-user'></i></div>
                    <div class="stat-card-number"><?php echo $total_customers; ?></div>
                    <div class="stat-card-meta">
                        <span class="label">Customers</span>
                        <span class="stat-trend-up">↑ 11.01%</span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-card-icon"><i class='bx bx-book-content'></i></div>
                    <div class="stat-card-number"><?php echo $total_apps; ?></div>
                    <div class="stat-card-meta">
                        <span class="label">Total Requests</span>
                        <span class="stat-trend-down">↓ 4.05%</span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-card-icon"><i class='bx bx-group'></i></div>
                    <div class="stat-card-number"><?php echo $total_agents; ?></div>
                    <div class="stat-card-meta">
                        <span class="label">Active Agents</span>
                        <span class="stat-trend-up">↑ 2.3%</span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-card-icon"><i class='bx bx-shield'></i></div>
                    <div class="stat-card-number"><?php echo $total_plans; ?></div>
                    <div class="stat-card-meta">
                        <span class="label">Insurance Plans</span>
                        <span class="stat-trend-stable">Stable</span>
                    </div>
                </div>
            </div>

            <div class="overview-twin-layout">
                
                <div class="card">
                    <h2 class="card-title-main">Insurance Categories</h2>
                    <p class="card-subtitle-main">Distribution of customer requests based on category</p>
                    
                    <div class="categories-progress-list">
                        <div>
                            <div class="progress-item-info">
                                <span><i class='bx bx-car'></i> Car Insurance</span>
                                <span>60%</span>
                            </div>
                            <div class="progress-bar-bg">
                                <div class="progress-bar-fill" style="width: 60%; background: var(--action-blue);"></div>
                            </div>
                        </div>

                        <div>
                            <div class="progress-item-info">
                                <span><i class='bx bx-heart'></i> Health Insurance</span>
                                <span>25%</span>
                            </div>
                            <div class="progress-bar-bg">
                                <div class="progress-bar-fill" style="width: 25%; background: #10B981;"></div>
                            </div>
                        </div>

                        <div>
                            <div class="progress-item-info">
                                <span><i class='bx bx-home'></i> Property Insurance</span>
                                <span>10%</span>
                            </div>
                            <div class="progress-bar-bg">
                                <div class="progress-bar-fill" style="width: 10%; background: #F59E0B;"></div>
                            </div>
                        </div>

                        <div>
                            <div class="progress-item-info">
                                <span><i class='bx bx-group'></i> Life Insurance</span>
                                <span>5%</span>
                            </div>
                            <div class="progress-bar-bg">
                                <div class="progress-bar-fill" style="width: 5%; background: #8B5CF6;"></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header-wrapper">
                        <h2 class="card-title-main">Recent Applications</h2>
                        <a href="AdminDashboard.php?tab=applications" class="btn btn-sm btn-edit">See all</a>
                    </div>

                    <table class="recent-apps-table">
                        <thead>
                            <tr>
                                <th>Customer</th>
                                <th>Category</th>
                                <th>Status</th>
                                <th style="text-align: right;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <div class="customer-meta-name">Mahmoud Diaa</div>
                                    <div class="customer-meta-id">ID: #1024</div>
                                </td>
                                <td class="category-column-text">Car Insurance</td>
                                <td>
                                    <span class="badge" style="background: #D1FAE5; color: #065F46; font-size: 11px; font-weight: 600; padding: 4px 10px; border-radius: 6px;">Paid</span>
                                </td>
                                <td style="text-align: right;">
                                    <small style="color: var(--text-muted); font-weight: 500;">Assigned</small>
                                </td>
                            </tr>
                            
                            <tr>
                                <td>
                                    <div class="customer-meta-name">Yasmine Hamed</div>
                                    <div class="customer-meta-id">ID: #1025</div>
                                </td>
                                <td class="category-column-text">Health Insurance</td>
                                <td>
                                    <span class="badge" style="background: #FEF3C7; color: #92400E; font-size: 11px; font-weight: 600; padding: 4px 10px; border-radius: 6px;">Review</span>
                                </td>
                                <td style="text-align: right;">
                                    <form action="AdminDashboard.php" method="post" class="quick-assign-form">
                                        <input type="hidden" name="application_id" value="1025">
                                        <select name="agent_id" class="quick-assign-select" required>
                                            <option value="">Assign...</option>
                                            <?php
                                            if ($agents) {
                                                mysqli_data_seek($agents, 0);
                                                while ($ag = mysqli_fetch_assoc($agents)):
                                            ?>
                                                <option value="<?php echo $ag['user_id']; ?>"><?php echo htmlspecialchars($ag['name']); ?></option>
                                            <?php endwhile; } ?>
                                        </select>
                                        <button type="submit" name="assign_agent" class="btn btn-primary quick-assign-submit">Go</button>
                                    </form>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

            </div>


            <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 30px; margin-top: 30px; align-items: start;">
                
                <div class="card" style="border-left: 4px solid var(--error-red);">
                    <h2 class="card-title-main" style="color: var(--primary-navy);"><i class='bx bx-bell-off' style="color: var(--error-red); vertical-align: middle; margin-right: 5px;"></i> Action Required</h2>
                    <p class="card-subtitle-main">Critical items that require immediate administrator supervision.</p>
                    
                    <div style="display: flex; flex-direction: column; gap: 12px; margin-top: 15px;">
                        
                        <div style="display: flex; align-items: center; gap: 12px; padding: 12px; background: #FFF5F5; border-radius: 8px;">
                            <i class='bx bx-error-alt' style="font-size: 20px; color: var(--error-red);"></i>
                            <div>
                                <div style="font-size: 13px; font-weight: 600; color: #9B1C1C;">3 Unassigned Claims</div>
                                <div style="font-size: 11px; color: #111827; opacity: 0.8;">Accident reports waiting for agent verification.</div>
                            </div>
                        </div>

                        <div style="display: flex; align-items: center; gap: 12px; padding: 12px; background: #FFFBEB; border-radius: 8px;">
                            <i class='bx bx-time-five' style="font-size: 20px; color: #D97706;"></i>
                            <div>
                                <div style="font-size: 13px; font-weight: 600; color: #92400E;">5 Policies Expiring</div>
                                <div style="font-size: 11px; color: #111827; opacity: 0.8;">Customer renewals notice needs to be sent this week.</div>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="card">
                    <div class="card-header-wrapper">
                        <h2 class="card-title-main"><i class='bx bx-credit-card-front' style="color: var(--action-blue); vertical-align: middle; margin-right: 5px;"></i> Recent Transactions</h2>
                        <span style="font-size: 12px; font-weight: 600; color: #10B981; background: #D1FAE5; padding: 4px 10px; border-radius: 50px;">Live Revenue</span>
                    </div>
                    <p class="card-subtitle-main" style="margin-top: -10px;">Latest successful premium payments received through the hub.</p>

                    <div class="table-responsive-container" style="max-height: 200px; margin-top: 15px;">
                        <table class="recent-apps-table">
                            <thead>
                                <tr>
                                    <th>Policy ID</th>
                                    <th>Amount</th>
                                    <th>Method</th>
                                    <th>Date</th>
                                    <th style="text-align: right;">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong style="color: var(--primary-navy);">#POL-9942</strong></td>
                                    <td style="font-weight: 600; color: var(--primary-navy);">EGP 8,500.00</td>
                                    <td><span style="font-size: 12px; font-weight: 500; color: var(--text-main);"><i class='bx bx-credit-card'></i> Credit Card</span></td>
                                    <td style="color: var(--text-muted); font-size: 12px;">Just Now</td>
                                    <td style="text-align: right;"><span class="badge" style="background: #D1FAE5; color: #065F46; font-size: 11px; font-weight: 600; padding: 2px 8px; border-radius: 4px;">Success</span></td>
                                </tr>
                                <tr>
                                    <td><strong style="color: var(--primary-navy);">#POL-1082</strong></td>
                                    <td style="font-weight: 600; color: var(--primary-navy);">EGP 3,500.00</td>
                                    <td><span style="font-size: 12px; font-weight: 500; color: var(--text-main);"><i class='bx bxl-paypal'></i> PayPal</span></td>
                                    <td style="color: var(--text-muted); font-size: 12px;">20 mins ago</td>
                                    <td style="text-align: right;"><span class="badge" style="background: #D1FAE5; color: #065F46; font-size: 11px; font-weight: 600; padding: 2px 8px; border-radius: 4px;">Success</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>


            <div class="overview-twin-layout" style="margin-bottom: 30px;">
    
    <div class="card">
        <div class="card-header-wrapper">
            <h2 class="card-title-main"><i class='bx bx-trending-up' style="color: var(--action-blue);"></i> Revenue Analytics</h2>
            <span class="stat-trend-up">↑ 12.5% YoY</span>
        </div>
        <p class="card-subtitle-main">Monthly overview of total premium collection to monitor financial health.</p>
        
        <div id="revenueChart" style="min-height: 250px;"></div>
    </div>

    <div class="card">
        <div class="card-header-wrapper">
            <h2 class="card-title-main"><i class='bx bx-pie-chart-alt-2' style="color: #10B981;"></i> Risk Retention (Loss Ratio)</h2>
            <span style="font-size: 11px; font-weight: 600; color: #1E4ED8; background: #EBF5FF; padding: 2px 8px; border-radius: 4px;">Critical KPI</span>
        </div>
        <p class="card-subtitle-main">Comparing total collected premiums against paid insurance claims.</p>
        
        <div id="lossRatioChart" style="min-height: 250px; display: flex; align-items: center; justify-content: center;"></div>
    </div>

</div>
            
            <!-- end test marwan wael -->


        <?php elseif ($active_tab === 'add'): ?>
            <div class="page-title"><?php echo $edit_agent ? 'Edit Agent' : 'Add New Agent'; ?></div>
            <div class="page-subtitle"><?php echo $edit_agent ? 'Update the agent\'s information below.' : 'Fill in the form below to create a new agent account.'; ?></div>

            <div class="card">
                <h2><?php echo $edit_agent ? ' Edit Agent' : ' New Agent'; ?></h2>
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

        <?php elseif ($active_tab === 'manage'): ?>
            <div class="page-title"><i class='bx bx-group'></i>Manage Agents</div>
            <div class="page-subtitle">View, edit or remove agent accounts from the system.</div>
                        <div class="card">
    
                <div class="card-search-header">
                    <h2><i class='bx bx-user'></i> Agents Accounts</h2>
        
                <div class="search-input-wrapper">
                    <i class='bx bx-search'></i>
                    <input type="text" id="agentSearchInput" class="search-grid-input" placeholder="Search by name, email or phone...">
                 </div>
            </div>
            <div class="table-responsive-container">
                <table id="agentTable">
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
                                    <td><span class="badge" style="background:#EBF5FF; color:#1E4ED8;">Agent</span></td>
                                    <td>
                                        <a href="AdminDashboard.php?edit=<?php echo $row['user_id']; ?>" class="btn btn-sm btn-edit"><i class='bx bx-edit'></i></a>
                                        <a href="AdminDashboard.php?delete=<?php echo $row['user_id']; ?>" class="btn btn-sm btn-delete" onclick="return confirm('Are you sure you want to delete this agent?');"><i class='bx bx-trash'></i></a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class="no-data">No agents found. <a href="AdminDashboard.php?tab=add">Add one now →</a></td></tr>
                        <?php endif; ?>
                        <tr id="customerNoResultsRow" style="display: none;">
                            <td colspan="6" class="no-data" style="text-align: center; padding: 24px; color: var(--text-muted); font-style: italic;">
                                <i class='bx bx-search-alt' style="font-size: 20px; vertical-align: middle; margin-right: 5px;"></i> No matching customers found.
                            </td>
                        </tr>

                    </tbody>
                </table>
            </div>

        <?php elseif ($active_tab === 'customers'): ?>
            <div class="page-title"><i class='bx bx-user'></i>Customers</div>
            <div class="page-subtitle">View registered customers in the system.</div>

            <div class="card">
    
                <div class="card-search-header">
                    <h2><i class='bx bx-user'></i> Customer Accounts</h2>
        
                <div class="search-input-wrapper">
                    <i class='bx bx-search'></i>
                    <input type="text" id="customerSearchInput" class="search-grid-input" placeholder="Search by name, email or phone...">
                 </div>
            </div>

            <div class="table-responsive-container">
                <table id="customersTable">
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
                            <tr class="no-data-row"><td colspan="6" class="no-data">No customers found.</td></tr>
                        <?php endif; ?>
                        <tr id="customerNoResultsRow" style="display: none;">
                            <td colspan="6" class="no-data" style="text-align: center; padding: 24px; color: var(--text-muted); font-style: italic;">
                                <i class='bx bx-search-alt' style="font-size: 20px; vertical-align: middle; margin-right: 5px;"></i> No matching customers found.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

        <?php elseif ($active_tab === 'applications'): ?>
            <div class="page-title">New Customer Applications</div>
            <div class="page-subtitle">Assign an agent once the customer has selected a plan and uploaded the required documents.</div>

            <div class="table-responsive-container">
                <h2><i class='bx bx-book-content'></i> Applications List</h2>
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
                                        <?php if ($row['status'] === 'under_review'): ?>
                                            <form action="AdminDashboard.php" method="post" style="display:flex; gap:5px; align-items:center;">
                                                <input type="hidden" name="application_id" value="<?php echo $row['application_id']; ?>">
                                                <select name="agent_id" style="padding:6px; border-radius:6px; border:1px solid #D1D5DB; font-size:12px; background-color: var(--input-bg);" required>
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
                                            <small style="color:#9ca3af;">Agent: <?php echo htmlspecialchars($row['agent_name'] ?? '—'); ?></small>
                                        <?php else: ?>
                                            <small style="color:#9ca3af;">Awaiting plan choice</small>
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

        <?php elseif ($active_tab === 'plans'): ?>
            <div class="page-title"><i class='bx bx-shield'></i>Insurance Plans</div>
            <div class="page-subtitle">All available plans with their eligibility rules. Customers see these during instant quotation.</div>

            <div class="card">
    
                <div class="card-search-header">
                    <h2><i class='bx bx-shield'></i> Plans Catalogue</h2>  
                    
                    <div style="flex: 1; min-width: 150px;">
                <select id="planCategoryFilterInput" class="quick-assign-select" style="width: 100%; padding: 10px 12px; height: 40px; border-radius: 8px; font-size: 13px;">
                    <option value="">All Categories</option>
                    <option value="Car Insurance">Car Insurance</option>
                    <option value="Medical Insurance">Medical Insurance</option>
                    <option value="Property Insurance">Property Insurance</option>
                    <option value="Retirement">Retirement</option>
                </select>
            </div>
                    <div class="search-input-wrapper">
                        <i class='bx bx-search'></i>
                        <input type="text" id="planSearchInput" class="search-grid-input" placeholder="Search by plan name or category...">
                    </div>
                </div>


            <div class="table-responsive-container">
                <table id="plansTable">
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
                                            echo '<ul style="margin:0; padding-left:16px; font-size:12px; color:#374151; line-height:1.6;">';
                                            foreach ($rules as $k => $v) {
                                                $val = is_array($v) ? implode(', ', $v) : $v;
                                                echo '<li><b>' . htmlspecialchars(str_replace('_',' ',$k)) . ':</b> ' . htmlspecialchars($val) . '</li>';
                                            }
                                            echo '</ul>';
                                        } else {
                                            echo '<small style="color:#9ca3af;">None</small>';
                                        }
                                        ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="6" class="no-data">No plans found.</td></tr>
                        <?php endif; ?>
                        <tr id="planNoResultsRow" style="display: none;">
                            <td colspan="6" class="no-data" style="text-align: center; padding: 24px; color: var(--text-muted); font-style: italic;">
                                <i class='bx bx-search-alt'></i> No matching insurance plans found.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

        <?php elseif ($active_tab === 'add_plan'): ?>
            <div class="page-title"><i class='bx bx-add-to-queue'></i> Add New Insurance Plan</div>
            <div class="page-subtitle">Fill in the form below to create a new insurance plan.</div>

            <div class="card">
                <h2><i class='bx bx-add-to-queue'></i> Add New Plan</h2>
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
                        <textarea name="eligibility_rules" rows="4" placeholder='e.g. {"Rule 1", "Rule 2"}'></textarea>
                    </div>
                    <div class="form-actions">
                        <button type="submit" name="add_plan" class="btn btn-primary">Add Plan</button>
                    </div>
                </form>
            </div>

        <?php elseif ($active_tab === 'add_category'): ?>
            <div class="page-title"><i class='bx bx-envelope'></i>Messeges</div>
            <div class="page-subtitle">All messages received from customers.</div>

           <div class="card">
    
    <div class="card-search-header">
        <h2><i class='bx bx-envelope'></i> Contact Messages</h2>
        
        <div class="search-input-wrapper">
            <i class='bx bx-search'></i>
            <input type="text" id="messageSearchInput" class="search-grid-input" placeholder="Search messages by name, email or topic...">
        </div>
    </div>

    <div class="table-responsive-container">
        <table id="messagesTable">
            <thead>
                <tr>
                    <th style="width: 15%;">Sender</th>
                    <th style="width: 20%;">Email</th>
                    <th style="width: 20%;">Subject</th>
                    <th style="width: 30%;">Message</th>
                    <th style="width: 15%;">Date Received</th>
                </tr>
            </thead>
            <tbody>
                <?php if (true): // هنسيبها true مؤقتاً عشان الداتا الوهمية تظهر وتشوف الديزاين ?>
                    
                    <tr>
                        <td><strong>Ahmed Ali</strong></td>
                        <td>ahmed.ali@mail.com</td>
                        <td><span class="badge" style="background: #EBF5FF; color: #1E4ED8; font-weight: 600;">Car Quote Query</span></td>
                        <td style="max-width: 250px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: var(--text-main);">
                            Hello, I tried to submit a car insurance request for my Tesla but the price estimation didn't load. Please help.
                        </td>
                        <td style="color: var(--text-muted); font-size: 13px;">Today, 02:30 PM</td>
                    </tr>

                    <tr>
                        <td><strong>Sarah Mostafa</strong></td>
                        <td>sara.m@mail.com</td>
                        <td><span class="badge" style="background: #EBF5FF; color: #1E4ED8; font-weight: 600;">Medical Insurance</span></td>
                        <td style="max-width: 250px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: var(--text-main);">
                            Do you have corporate family discounts for medical coverage? We are a team of 15 people.
                        </td>
                        <td style="color: var(--text-muted); font-size: 13px;">Yesterday, 11:15 AM</td>
                    </tr>
                    
                <?php else: ?>
                    <tr class="no-data-row"><td colspan="5" class="no-data">No messages received yet.</td></tr>
                <?php endif; ?>
                
                <tr id="messageNoResultsRow" style="display: none;">
                    <td colspan="5" class="no-data" style="text-align: center; padding: 24px; color: var(--text-muted); font-style: italic;">
                        <i class='bx bx-search-alt' style="font-size: 20px; vertical-align: middle; margin-right: 5px;"></i> No matching messages found.
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
        <?php endif; ?>

    </div>

<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script src="/Graduation-Project/assets/js/admindashboard.js"></script>

</body>
</html>
