<?php
require_once __DIR__ . '/includes/auth_check.php';
requireRole('admin');

$error = '';
$success = '';

if (isset($_POST['add_agent'])) {
    $name = mysqli_real_escape_string($connect, $_POST['name']);
    $email = mysqli_real_escape_string($connect, $_POST['email']);
    $password = $_POST['password'];
    $role = 'agent';

    $check_email = mysqli_query($connect, "SELECT * FROM users WHERE email = '$email'");
    if (mysqli_num_rows($check_email) > 0) {
        $error = "Email already exists!";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters long!";
    } elseif (!preg_match('/[a-z]/', $password) || !preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
        $error = "Password must contain at least one lowercase letter, one uppercase letter, and one number!";
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

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $delete = mysqli_query($connect, "DELETE FROM users WHERE user_id = $id AND role = 'agent'");
    if ($delete) {
        $success = "Agent deleted successfully!";
        header("Location: AdminDashboard.php?tab=manage&success=" . urlencode($success));
        exit();
    } else {
        $error = "Failed to delete agent! " . mysqli_error($connect);
    }
}

if (isset($_GET['delete_plan'])) {
    $id = (int)$_GET['delete_plan'];
    $delete = mysqli_query($connect, "DELETE FROM insurance_plans WHERE plan_id = $id");
    if ($delete) {
        $success = "Insurance plan deleted successfully!";
        header("Location: AdminDashboard.php?tab=plans&success=" . urlencode($success));
        exit();
    } else {
        $error = "Failed to delete insurance plan! " . mysqli_error($connect);
    }
}

if (isset($_POST['edit_agent'])) {
    $id = (int)$_POST['user_id'];
    $name = mysqli_real_escape_string($connect, $_POST['name']);
    $email = mysqli_real_escape_string($connect, $_POST['email']);
    
    $check_email = mysqli_query($connect, "SELECT * FROM users WHERE email = '$email' AND user_id != $id");
    if (mysqli_num_rows($check_email) > 0) {
        $error = "Email already exists!";
    } else {
        $password_valid = true;
        if (!empty($_POST['password'])) {
            $password = $_POST['password'];
            if (strlen($password) < 8) {
                $error = "Password must be at least 8 characters long!";
                $password_valid = false;
            } elseif (!preg_match('/[a-z]/', $password) || !preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
                $error = "Password must contain at least one lowercase letter, one uppercase letter, and one number!";
                $password_valid = false;
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $update = mysqli_query($connect, "UPDATE users SET name = '$name', email = '$email', password = '$hashed_password' WHERE user_id = $id AND role = 'agent'");
            }
        } else {
            $update = mysqli_query($connect, "UPDATE users SET name = '$name', email = '$email' WHERE user_id = $id AND role = 'agent'");
        }
        
        if ($password_valid) {
            if ($update) {
                $success = "Agent updated successfully!";
                header("Location: AdminDashboard.php?tab=manage&success=" . urlencode($success));
                exit();
            } else {
                $error = "Failed to update agent! " . mysqli_error($connect);
            }
        }
    }
}

if (isset($_POST['add_plan'])) {
    $name = mysqli_real_escape_string($connect, $_POST['name']);
    $category_id = (int)$_POST['category_id'];
    $company = mysqli_real_escape_string($connect, $_POST['insurance_company']);
    $details = mysqli_real_escape_string($connect, $_POST['bio']);
    $price = (float)$_POST['base_price'];
    $commission_rate=(float)$_POST['commission_rate'];
    
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
        $logo_path = '';
        $logo_upload_error = '';
        if (!empty($_FILES['logo']['name'])) {
            $upload_dir = __DIR__ . '/assets/img/plans/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
            $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
            $allowed_exts = ['jpg', 'jpeg', 'png', 'webp', 'svg'];
            if (!in_array($ext, $allowed_exts)) {
                $logo_upload_error = "Logo must be JPG, PNG, WEBP, or SVG.";
            } elseif ($_FILES['logo']['size'] > 2 * 1024 * 1024) {
                $logo_upload_error = "Logo file size must not exceed 2MB.";
            } else {
                $logo_filename = 'plan_' . time() . '_' . uniqid() . '.' . $ext;
                if (move_uploaded_file($_FILES['logo']['tmp_name'], $upload_dir . $logo_filename)) {
                    $logo_path = 'assets/img/plans/' . $logo_filename;
                } else {
                    $logo_upload_error = "Failed to upload logo.";
                }
            }
        }

        if ($logo_upload_error !== '') {
            $error = $logo_upload_error;
        } else {
            $logo_escaped = mysqli_real_escape_string($connect, $logo_path);
            $insert = mysqli_query($connect, "INSERT INTO insurance_plans (name, category_id, insurance_company, bio, base_price, commission_rate, eligibility_rules, logo) VALUES ('$name', $category_id, '$company', '$details', $price,$commission_rate, $eligibility, '$logo_escaped')");
            if ($insert) {
                $success = "Insurance plan added successfully!";
                header("Location: AdminDashboard.php?tab=add_plan&success=" . urlencode($success));
                exit();
            } else {
                $error = "Failed to add insurance plan! " . mysqli_error($connect);
            }
        }
    }
}

if (isset($_POST['edit_plan'])) {
    $id = (int)$_POST['plan_id'];
    $name = mysqli_real_escape_string($connect, $_POST['name']);
    $category_id = (int)$_POST['category_id'];
    $company = mysqli_real_escape_string($connect, $_POST['insurance_company']);
    $details = mysqli_real_escape_string($connect, $_POST['bio']);
    $price = (float)$_POST['base_price'];
    $commission_rate=(float)$_POST['commission_rate'];

    
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
        $error = "Eligibility rules must be valid JSON format.";
    } else {
        $logo_sql_part = '';
        $logo_upload_error = '';
        if (!empty($_FILES['logo']['name'])) {
            $upload_dir = __DIR__ . '/assets/img/plans/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
            $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
            $allowed_exts = ['jpg', 'jpeg', 'png', 'webp', 'svg'];
            if (!in_array($ext, $allowed_exts)) {
                $logo_upload_error = "Logo must be JPG, PNG, WEBP, or SVG.";
            } elseif ($_FILES['logo']['size'] > 2 * 1024 * 1024) {
                $logo_upload_error = "Logo file size must not exceed 2MB.";
            } else {
                $logo_filename = 'plan_' . time() . '_' . uniqid() . '.' . $ext;
                if (move_uploaded_file($_FILES['logo']['tmp_name'], $upload_dir . $logo_filename)) {
                    $new_logo_path = mysqli_real_escape_string($connect, 'assets/img/plans/' . $logo_filename);
                    $logo_sql_part = ", logo = '$new_logo_path'";
                } else {
                    $logo_upload_error = "Failed to upload logo.";
                }
            }
        }

        if ($logo_upload_error !== '') {
            $error = $logo_upload_error;
        } else {
            $update = mysqli_query(
                $connect,
                "UPDATE insurance_plans SET name = '$name', category_id = $category_id, insurance_company = '$company', bio = '$details', base_price = $price, commission_rate = $commission_rate, eligibility_rules = $eligibility{$logo_sql_part} WHERE plan_id = $id"
            );
            if ($update) {
                $success = "Insurance plan updated successfully!";
                header("Location: AdminDashboard.php?tab=plans&success=" . urlencode($success));
                exit();
            } else {
                $error = "Failed to update insurance plan! " . mysqli_error($connect);
            }
        }
    }
}


if (isset($_POST['assign_agent'])) {
    $application_id = (int)$_POST['application_id'];
    $agent_id = (int)$_POST['agent_id'];

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




$agents = mysqli_query($connect, "SELECT * FROM users WHERE role = 'agent'");

$customers = mysqli_query($connect, "SELECT * FROM users WHERE role = 'customer'");

$categories_result = mysqli_query($connect, "SELECT * FROM categories");
$categories = [];
if ($categories_result) {
    while ($cat = mysqli_fetch_assoc($categories_result)) {
        $categories[] = $cat;
    }
}

$applications_query = "
    SELECT a.*,
           c.name as customer_name,
           cat.name as category_name,
           ag.name as agent_name,
           p.name as plan_name,
           p.insurance_company,
           p.base_price as base_price
    FROM applications a
    LEFT JOIN users c ON a.customer_id = c.user_id
    LEFT JOIN categories cat ON a.category_id = cat.category_id
    LEFT JOIN users ag ON a.agent_id = ag.user_id
    LEFT JOIN insurance_plans p ON a.plan_id = p.plan_id
    WHERE a.status IN ('under_review') AND (a.agent_id IS NULL OR a.agent_id = 0)
    ORDER BY a.created_at DESC
";
$applications = mysqli_query($connect, $applications_query);

$unassigned_count_query = mysqli_query($connect, "
    SELECT COUNT(*) as cnt 
    FROM applications 
    WHERE status = 'under_review' AND (agent_id IS NULL OR agent_id = 0)
");
$unassigned_count_row = mysqli_fetch_assoc($unassigned_count_query);
$unassigned_count = (int)($unassigned_count_row['cnt'] ?? 0);

$recent_applications_query = "
    SELECT a.*,
           c.name as customer_name,
           cat.name as category_name,
           ag.name as agent_name
    FROM applications a
    LEFT JOIN users c ON a.customer_id = c.user_id
    LEFT JOIN categories cat ON a.category_id = cat.category_id
    LEFT JOIN users ag ON a.agent_id = ag.user_id
    WHERE a.status IN ('under_review')
    ORDER BY a.created_at DESC
    LIMIT 5 
";
$recent_applications = mysqli_query($connect, $recent_applications_query);

function getOverviewStatusStyle($status) {
    switch ($status) {
        case 'under_review':
            return ['Review', 'background: #FEF3C7; color: #92400E;'];

        default:
            return [ucfirst(str_replace('_', ' ', $status)), 'background: #F1F3F4; color: #5F6368;'];
    }
}

$category_distribution_query = "
    SELECT cat.category_id, cat.name,
           COUNT(DISTINCT a.application_id) as cnt,
           COUNT(DISTINCT p.plan_id) as plans_count
    FROM categories cat
    LEFT JOIN applications a ON cat.category_id = a.category_id
    LEFT JOIN insurance_plans p ON cat.category_id = p.category_id
    GROUP BY cat.category_id, cat.name
    ORDER BY cnt DESC, cat.name ASC
";
$category_distribution = mysqli_query($connect, $category_distribution_query);

$total_apps_all_categories_row = mysqli_fetch_assoc(mysqli_query($connect, "SELECT COUNT(*) as total FROM applications"));
$total_apps_all_categories = intval($total_apps_all_categories_row['total'] ?? 0);

function getCategoryDesignProps($cat_name) {
    $clean_name = strtolower($cat_name);
    if (strpos($clean_name, 'car') !== false || strpos($clean_name, 'vehicle') !== false) {
        return [
            'icon'  => "<i class='bx bx-car'></i>",
            'color' => 'var(--action-blue)',
            'bg'    => '#EFF6FF'
        ];
    } elseif (strpos($clean_name, 'health') !== false || strpos($clean_name, 'medical') !== false) {
        return [
            'icon'  => "<i class='bx bx-heart'></i>",
            'color' => '#10B981',
            'bg'    => '#ECFDF5'
        ];
    } elseif (strpos($clean_name, 'property') !== false || strpos($clean_name, 'home') !== false) {
        return [
            'icon'  => "<i class='bx bx-home'></i>",
            'color' => '#F59E0B',
            'bg'    => '#FFFBEB'
        ];
    } elseif (strpos($clean_name, 'life') !== false) {
        return [
            'icon'  => "<i class='bx bx-group'></i>",
            'color' => '#8B5CF6',
            'bg'    => '#F5F3FF'
        ];
    } elseif (strpos($clean_name, 'retir') !== false || strpos($clean_name, 'pension') !== false) {
        return [
            'icon'  => "<i class='bx bx-briefcase'></i>",
            'color' => '#EC4899',
            'bg'    => '#FDF2F8'
        ];
    } else {
        return [
            'icon'  => "<i class='bx bx-shield'></i>",
            'color' => '#6B7280',
            'bg'    => '#F9FAFB'
        ];
    }
}



$stats = [];
foreach (['under_review','awaiting_payment','paid','rejected'] as $s) {
    $r = mysqli_fetch_assoc(mysqli_query($connect, "SELECT COUNT(*) as cnt FROM applications WHERE status='$s'"));
    $stats[$s] = $r['cnt'];
}
$total_apps   = array_sum($stats);
$total_agents = mysqli_fetch_assoc(mysqli_query($connect, "SELECT COUNT(*) as cnt FROM users WHERE role='agent'"))['cnt'];
$total_customers = mysqli_fetch_assoc(mysqli_query($connect, "SELECT COUNT(*) as cnt FROM users WHERE role='customer'"))['cnt'];
$total_plans  = mysqli_fetch_assoc(mysqli_query($connect, "SELECT COUNT(*) as cnt FROM insurance_plans"))['cnt'];

// Calculate total revenue as SUM(base_price * commission_rate) for paid applications
$revenue_result = mysqli_fetch_assoc(mysqli_query($connect, "
    SELECT COALESCE(SUM(p.base_price * p.commission_rate), 0) as total_revenue
    FROM applications a
    JOIN insurance_plans p ON a.plan_id = p.plan_id
    WHERE a.status = 'paid'
"));
$total_revenue = (float)($revenue_result['total_revenue'] ?? 0);

$all_plans = mysqli_query($connect, "
    SELECT p.*, cat.name as category_name
    FROM insurance_plans p
    LEFT JOIN categories cat ON p.category_id = cat.category_id
    ORDER BY cat.name, p.base_price ASC
");

if (!function_exists('get_time_elapsed')) {
    function get_time_elapsed($datetime, $full = false) {
        $now = new DateTime;
        $ago = new DateTime($datetime);
        $diff = $now->diff($ago);

        $diff->w = floor($diff->d / 7);
        $diff->d -= $diff->w * 7;

        $string = array(
            'y' => 'year',
            'm' => 'month',
            'w' => 'week',
            'd' => 'day',
            'h' => 'hour',
            'i' => 'minute',
            's' => 'second',
        );
        foreach ($string as $k => &$v) {
            if ($diff->$k) {
                $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
            } else {
                unset($string[$k]);
            }
        }

        if (!$full) $string = array_slice($string, 0, 1);
        return $string ? implode(', ', $string) . ' ago' : 'Just Now';
    }
}

// Fetch recent transactions from database
$recent_transactions_query = "
    SELECT p.*, pol.policy_number 
    FROM payments p
    LEFT JOIN policies pol ON p.policy_id = pol.policy_id
    ORDER BY p.payment_date DESC
    LIMIT 10
";
$recent_transactions = mysqli_query($connect, $recent_transactions_query);

$days = [];
for ($i = 6; $i >= 0; $i--) {
    $date_str = date('Y-m-d', strtotime("-$i days"));
    $label_str = date('D (d M)', strtotime("-$i days"));
    $days[$date_str] = [
        'label' => $label_str,
        'revenue' => 0.0
    ];
}

$start_date = date('Y-m-d 00:00:00', strtotime("-6 days"));
$revenue_query = "
    SELECT DATE(pay.payment_date) as payment_day, 
           SUM(p.base_price * p.commission_rate) as daily_revenue 
    FROM payments pay
    JOIN policies pol ON pay.policy_id = pol.policy_id
    JOIN applications a ON pol.application_id = a.application_id
    JOIN insurance_plans p ON a.plan_id = p.plan_id
    WHERE pay.status = 'completed' AND pay.payment_date >= '$start_date'
    GROUP BY DATE(pay.payment_date)
";
$revenue_res = mysqli_query($connect, $revenue_query);
if ($revenue_res) {
    while ($row = mysqli_fetch_assoc($revenue_res)) {
        $day = $row['payment_day'];
        if (isset($days[$day])) {
            $days[$day]['revenue'] = (float)$row['daily_revenue'];
        }
    }
}
$chart_labels = [];
$chart_data = [];
foreach ($days as $date => $info) {
    $chart_labels[] = $info['label'];
    $chart_data[] = $info['revenue'];
}

$chart_labels_json = json_encode($chart_labels);
$chart_data_json = json_encode($chart_data);

mysqli_query($connect, "CREATE TABLE IF NOT EXISTS contact_messages (
    message_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    subject VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");
$all_messages = mysqli_query($connect, "SELECT * FROM contact_messages ORDER BY created_at DESC");

if (isset($_GET['success'])) {
    $success = $_GET['success'];
}
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'overview';
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
        
        <a href="AdminDashboard.php?tab=applications" class="<?php echo $active_tab === 'applications' ? 'active' : ''; ?>">
            <span class="icon"><i class='bx bx-book-content'></i></span> Applications
            <?php if ($unassigned_count > 0): ?>
                <span class="sidebar-badge" style="background: #e65100;"><?php echo $unassigned_count; ?></span>
            <?php endif; ?>
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
        <a href="AdminDashboard.php?tab=messages" class="<?php echo $active_tab === 'messages' ? 'active' : ''; ?>">
            <span class="icon"><i class='bx bx-envelope'></i></span> Messages  
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
            <div class="page-title"><i class='bx bx-home'></i> Dashboard Overview</div>
            <div class="page-subtitle">A live summary of all activity across the insurance platform.</div>


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
            <span class="label">Total applications</span>
            <span class="stat-trend-down">↓4.05%</span>
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

    <div class="stat-card">
        <div class="stat-card-icon" style="background: #ECFDF5; color: #10B981;"><i class='bx bx-dollar-circle'></i></div>
        <div class="stat-card-number">EGP <?php echo number_format($total_revenue, 2); ?></div>
        <div class="stat-card-meta">
            <span class="label">Total Revenue</span>
            <span class="stat-trend-up">↑ Commission Based</span>
        </div>
    </div>
</div>

            <div class="overview-twin-layout">
                
                <div class="card">
                    <div class="card-header-wrapper" style="margin-bottom: 4px;">
                        <h2 class="card-title-main">Insurance Categories</h2>
                        <span style="font-size: 11px; font-weight: 600; color: var(--text-muted); background: #F3F4F6; padding: 3px 10px; border-radius: 50px;">
                            <?php echo count($categories); ?> Total
                        </span>
                    </div>
                    <p class="card-subtitle-main">Distribution of applications per category</p>
                    
                    <div class="categories-progress-list">
                        <?php
                        if ($category_distribution && mysqli_num_rows($category_distribution) > 0) {
                            mysqli_data_seek($category_distribution, 0);
                            while ($cat_row = mysqli_fetch_assoc($category_distribution)):
                                $pct = 0;
                                if ($total_apps_all_categories > 0) {
                                    $pct = round(($cat_row['cnt'] / $total_apps_all_categories) * 100);
                                }
                                $design = getCategoryDesignProps($cat_row['name']);
                                $app_count   = intval($cat_row['cnt']);
                                
                        ?>
                            <div class="category-progress-item">
                                <div class="progress-item-info">
                                    <div class="cat-label-group">
                                        <span class="cat-icon-badge" style="background: <?php echo $design['bg']; ?>; color: <?php echo $design['color']; ?>;">
                                            <?php echo $design['icon']; ?>
                                        </span>
                                        <div>
                                            <div class="cat-name"><?php echo htmlspecialchars($cat_row['name']); ?></div>
                                            <div class="cat-meta">
                                                <?php echo $app_count; ?> application<?php echo $app_count != 1 ? 's' : ''; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="cat-pct-badge" style="color: <?php echo $design['color']; ?>; background: <?php echo $design['bg']; ?>">
                                        <?php echo $pct; ?>%
                                    </div>
                                </div>
                                <div class="progress-bar-bg">
                                    <div class="progress-bar-fill"
                                         data-width="<?php echo $pct; ?>"
                                         style="width: 0%; background: <?php echo $design['color']; ?>; transition: width 1s cubic-bezier(0.4, 0, 0.2, 1);"></div>
                                </div>
                            </div>
                        <?php 
                            endwhile;
                        } else {
                        ?>
                            <div style="text-align: center; padding: 30px 0;">
                                <i class='bx bx-category' style="font-size: 36px; color: var(--border-color);"></i>
                                <p style="color: var(--text-muted); font-style: italic; font-size: 13px; margin-top: 8px;">No categories found in the database.</p>
                            </div>
                        <?php } ?>
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
                            <?php if ($recent_applications && mysqli_num_rows($recent_applications) > 0): ?>
                                <?php while ($row = mysqli_fetch_assoc($recent_applications)): 
                                    [$statusLabel, $statusStyle] = getOverviewStatusStyle($row['status']);
                                ?>
                                    <tr>
                                        <td>
                                            <div class="customer-meta-name"><?php echo htmlspecialchars($row['customer_name'] ?? 'Unknown'); ?></div>
                                            <div class="customer-meta-id">ID: #<?php echo $row['application_id']; ?></div>
                                        </td>
                                        <td class="category-column-text"><?php echo htmlspecialchars($row['category_name'] ?? 'N/A'); ?></td>
                                        <td>
                                            <span class="badge" style="<?php echo $statusStyle; ?> font-size: 11px; font-weight: 600; padding: 4px 10px; border-radius: 6px;">
                                                <?php echo htmlspecialchars($statusLabel); ?>
                                            </span>
                                        </td>
                                        <td style="text-align: right;">
                                            <?php if ($row['status'] === 'under_review' && !$row['agent_id']): ?>
                                                <form action="AdminDashboard.php" method="post" class="quick-assign-form">
                                                    <input type="hidden" name="application_id" value="<?php echo $row['application_id']; ?>">
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
                                            <?php else: ?>
                                                <small style="color: var(--text-muted); font-weight: 500;">
                                                    <?php echo $row['agent_name'] ? 'Assigned to ' . htmlspecialchars($row['agent_name']) : 'Unassigned'; ?>
                                                </small>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="no-data" style="text-align: center; padding: 24px; color: var(--text-muted);">
                                        No recent applications found.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            </div>


            

            <div class="overview-twin-layout" style="margin-bottom: 30px;">


                <div class="card">
        <div class="card-header-wrapper">
            <h2 class="card-title-main"><i class='bx bx-trending-up' style="color: var(--action-blue);"></i> Revenue Analytics</h2>
            <span class="stat-trend-up">↑ 12.5% YoY</span>
        </div>
        <p class="card-subtitle-main">Daily overview of total premium collection over the last 7 days to monitor financial health.</p>
        
        <div id="revenueChart" style="min-height: 250px;"></div>
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
                                <?php if ($recent_transactions && mysqli_num_rows($recent_transactions) > 0): ?>
                                    <?php while ($txn = mysqli_fetch_assoc($recent_transactions)): 
                                        $method_label = 'Credit Card';
                                        $method_icon = 'bx bx-credit-card';
                                        switch (strtolower($txn['method'] ?? '')) {
                                            case 'paypal':
                                                $method_label = 'PayPal';
                                                $method_icon = 'bx bxl-paypal';
                                                break;
                                            case 'bank_transfer':
                                                $method_label = 'Bank Transfer';
                                                $method_icon = 'bx bx-transfer';
                                                break;
                                            case 'cash':
                                                $method_label = 'Cash';
                                                $method_icon = 'bx bx-money';
                                                break;
                                        }

                                        $status_label = 'Success';
                                        $status_style = 'background: #D1FAE5; color: #065F46;';
                                        if ($txn['status'] === 'pending') {
                                            $status_label = 'Pending';
                                            $status_style = 'background: #FEF3C7; color: #92400E;';
                                        } elseif ($txn['status'] === 'failed') {
                                            $status_label = 'Failed';
                                            $status_style = 'background: #FEE2E2; color: #991B1B;';
                                        }
                                    ?>
                                        <tr>
                                            <td><strong style="color: var(--primary-navy);">#<?= htmlspecialchars($txn['policy_number'] ?: 'N/A') ?></strong></td>
                                            <td style="font-weight: 600; color: var(--primary-navy);">EGP <?= number_format($txn['amount'], 2) ?></td>
                                            <td><span style="font-size: 12px; font-weight: 500; color: var(--text-main);"><i class='<?= $method_icon ?>'></i> <?= $method_label ?></span></td>
                                            <td style="color: var(--text-muted); font-size: 12px;"><?= get_time_elapsed($txn['payment_date']) ?></td>
                                            <td style="text-align: right;"><span class="badge" style="<?= $status_style ?> font-size: 11px; font-weight: 600; padding: 2px 8px; border-radius: 4px;"><?= $status_label ?></span></td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" style="text-align: center; padding: 24px; color: var(--text-muted);">No transactions found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>


        <?php elseif ($active_tab === 'add'): ?>
            <div class="page-title"><i class='bx bx-user-plus'></i> Add New Agent</div>
            <div class="page-subtitle">Fill in the form below to create a new agent account.</div>

            <div class="card">
                <h2>New Agent</h2>
                <form action="AdminDashboard.php" method="post">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="name" placeholder="Enter agent's full name" required>
                    </div>
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" name="email" placeholder="Enter agent's email" required>
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="password" placeholder="Enter a strong password" required>
                    </div>
                    <div class="form-actions">
                        <button type="submit" name="add_agent" class="btn btn-primary">Add Agent</button>
                    </div>
                </form>
            </div>

        <?php elseif ($active_tab === 'manage'): ?>
            <div class="page-title"><i class='bx bx-group'></i> Manage Agents</div>
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
                                        <button class="btn btn-sm btn-edit edit-agent-btn" 
                                                data-id="<?php echo $row['user_id']; ?>" 
                                                data-agent="<?php echo htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8'); ?>">
                                            <i class='bx bx-edit'></i>
                                        </button>
                                        <a href="AdminDashboard.php?delete=<?php echo $row['user_id']; ?>" 
                                           class="btn btn-sm btn-delete delete-confirm-btn" 
                                           data-message="Are you sure you want to delete this agent account?"><i class='bx bx-trash'></i></a>
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
            <div class="page-title"><i class='bx bx-user'></i> Customers</div>
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
            <div class="page-title"><i class='bx bx-list-ul'></i> New Customer Applications</div>
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
                            <th>Price</th>
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
                                        <?php echo $row['base_price'] ? '<strong>$' . number_format($row['base_price'], 2) . '</strong>' : '<span style="color:#9ca3af;">—</span>'; ?>
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
            <div class="page-title"><i class='bx bx-shield'></i> Insurance Plans</div>
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
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($all_plans && mysqli_num_rows($all_plans) > 0): ?>
                            <?php while ($p = mysqli_fetch_assoc($all_plans)): ?>
                                <tr>
                                    <td><?php echo $p['plan_id']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($p['name']); ?></strong></td>
                                    <td class="search-plan-category"><?php echo htmlspecialchars($p['category_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($p['insurance_company']); ?></td>
                                    <td><strong>$<?php echo number_format($p['base_price'], 2); ?></strong></td>
                                    <td>
                                        <button class="btn btn-sm btn-edit edit-plan-btn" 
                                                data-id="<?php echo $p['plan_id']; ?>" 
                                                data-plan="<?php echo htmlspecialchars(json_encode($p), ENT_QUOTES, 'UTF-8'); ?>">
                                            <i class='bx bx-edit'></i> Edit
                                        </button>
                                        <a href="AdminDashboard.php?delete_plan=<?php echo $p['plan_id']; ?>" 
                                           class="btn btn-sm btn-delete delete-confirm-btn" 
                                           style="margin-left: 6px; display: inline-flex; align-items: center;"
                                           data-message="Are you sure you want to delete this insurance plan?">
                                            <i class='bx bx-trash'></i> Delete
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="7" class="no-data">No plans found.</td></tr>
                        <?php endif; ?>
                        <tr id="planNoResultsRow" style="display: none;">
                            <td colspan="7" class="no-data" style="text-align: center; padding: 24px; color: var(--text-muted); font-style: italic;">
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
                <form action="AdminDashboard.php" method="post" id="addPlanForm" enctype="multipart/form-data">

                <div class="form-group">
    <label>Company Logo <small style="color:#9ca3af;">(JPG, PNG, WEBP, SVG — max 2MB)</small></label>
    <div class="logo-upload-zone" id="addLogoZone">
        <input type="file" name="logo" id="addLogoInput" accept=".jpg,.jpeg,.png,.webp,.svg" style="display:none;">
        <div class="logo-upload-preview" id="addLogoPreview">
            <i class='bx bx-image-add' style="font-size:32px; color:#9ca3af;"></i>
            <p style="margin:8px 0 0; font-size:13px; color:#9ca3af;">Click to upload logo</p>
        </div>
    </div>
</div>
                    <div class="form-group">
                        <label>Plan Name</label>
                        <input type="text" name="name" placeholder="Enter plan name" required>
                    </div>
                    <div class="form-group">
                        <label>Category</label>
                        <select name="category_id" id="planCategorySelect" required>
                            <option value="">Select a category</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['category_id']; ?>" data-name="<?php echo htmlspecialchars(strtolower($cat['name'])); ?>">
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </option>
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
                        <label>Commission Rate</label>
                        <input type="number" step="0.01" name="commission_rate" placeholder="Enter Commission Rate (e.g. 0.15)" required>
                    </div>
                    <div class="form-group">
                        <label>Details / Bio</label>
                        <textarea name="bio" rows="4" placeholder="Enter plan details" required></textarea>
                    </div>

                    <div class="form-group">
                        <label><i class='bx bx-list-check' style="margin-right:6px;"></i>Eligibility Rules <small style="color:#9ca3af;">(Auto-generated based on category)</small></label>
                        <p id="selectCategoryHint" style="color:#9ca3af; font-size:13px; margin:8px 0;">
                            <i class='bx bx-info-circle'></i> Please select a category above to see the eligibility rule fields.
                        </p>

                        <div id="rules-car" class="eligibility-rules-panel" style="display:none;">
                            <div class="rules-grid">
                                <div class="rule-field">
                                    <label>Car Condition Allowed</label>
                                    <div class="checkbox-group">
                                        <label class="checkbox-label"><input type="checkbox" class="rule-input" data-key="condition" data-type="array" value="new"> New</label>
                                        <label class="checkbox-label"><input type="checkbox" class="rule-input" data-key="condition" data-type="array" value="used"> Used</label>
                                    </div>
                                </div>
                                <div class="rule-field">
                                </div>
                                <div class="rule-field">
                                    <label>Min Year</label>
                                    <input type="number" class="rule-input" data-key="min_year" data-type="number" placeholder="e.g. 2015">
                                </div>
                                <div class="rule-field">
                                    <label>Max Year</label>
                                    <input type="number" class="rule-input" data-key="max_year" data-type="number" placeholder="e.g. 2025">
                                </div>
                                <div class="rule-field">
                                    <label>Min Car Value (EGP)</label>
                                    <input type="number" class="rule-input" data-key="min_price" data-type="number" placeholder="e.g. 100000">
                                </div>
                                <div class="rule-field">
                                    <label>Max Car Value (EGP)</label>
                                    <input type="number" class="rule-input" data-key="max_price" data-type="number" placeholder="e.g. 2000000">
                                </div>
                            </div>
                        </div>

                        <div id="rules-health" class="eligibility-rules-panel" style="display:none;">
                            <div class="rules-grid">
                                <div class="rule-field">
                                    <label>Min Age</label>
                                    <input type="number" class="rule-input" data-key="min_age" data-type="number" placeholder="e.g. 18">
                                </div>
                                <div class="rule-field">
                                    <label>Max Age</label>
                                    <input type="number" class="rule-input" data-key="max_age" data-type="number" placeholder="e.g. 65">
                                </div>
                                <div class="rule-field">
                                    <label>Chronic Conditions Allowed?</label>
                                    <select class="rule-input" data-key="chronic_allowed" data-type="boolean">
                                        <option value="">-- Leave empty --</option>
                                        <option value="true">Yes</option>
                                        <option value="false">No</option>
                                    </select>
                                </div>
                                <div class="rule-field">
                                    <label>Covers Spouse?</label>
                                    <select class="rule-input" data-key="covers_spouse" data-type="boolean">
                                        <option value="">-- Leave empty --</option>
                                        <option value="true">Yes</option>
                                        <option value="false">No</option>
                                    </select>
                                </div>
                                <div class="rule-field">
                                    <label>Max Children</label>
                                    <input type="number" class="rule-input" data-key="max_children" data-type="number" placeholder="e.g. 4">
                                </div>
                            </div>
                        </div>

                        <div id="rules-life" class="eligibility-rules-panel" style="display:none;">
                            <div class="rules-grid">
                                <div class="rule-field">
                                    <label>Min Age</label>
                                    <input type="number" class="rule-input" data-key="min_age" data-type="number" placeholder="e.g. 21">
                                </div>
                                <div class="rule-field">
                                    <label>Max Age</label>
                                    <input type="number" class="rule-input" data-key="max_age" data-type="number" placeholder="e.g. 60">
                                </div>
                                <div class="rule-field">
                                    <label>Min Coverage (EGP)</label>
                                    <input type="number" class="rule-input" data-key="min_coverage" data-type="number" placeholder="e.g. 50000">
                                </div>
                                <div class="rule-field">
                                    <label>Max Coverage (EGP)</label>
                                    <input type="number" class="rule-input" data-key="max_coverage" data-type="number" placeholder="e.g. 5000000">
                                </div>
                                <div class="rule-field" style="grid-column: 1 / -1;">
                                    <label>Allowed Policy Terms</label>
                                    <div class="checkbox-group">
                                        <label class="checkbox-label"><input type="checkbox" class="rule-input" data-key="allowed_terms" data-type="array" value="10_years"> 10 Years</label>
                                        <label class="checkbox-label"><input type="checkbox" class="rule-input" data-key="allowed_terms" data-type="array" value="20_years"> 20 Years</label>
                                        <label class="checkbox-label"><input type="checkbox" class="rule-input" data-key="allowed_terms" data-type="array" value="30_years"> 30 Years</label>
                                        <label class="checkbox-label"><input type="checkbox" class="rule-input" data-key="allowed_terms" data-type="array" value="whole_life"> Whole Life</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div id="rules-property" class="eligibility-rules-panel" style="display:none;">
                            <div class="rules-grid">
                                <div class="rule-field" style="grid-column: 1 / -1;">
                                    <label>Allowed Property Types</label>
                                    <div class="checkbox-group">
                                        <label class="checkbox-label"><input type="checkbox" class="rule-input" data-key="property_types_allowed" data-type="array" value="apartment"> Apartment</label>
                                        <label class="checkbox-label"><input type="checkbox" class="rule-input" data-key="property_types_allowed" data-type="array" value="villa"> Villa</label>
                                        <label class="checkbox-label"><input type="checkbox" class="rule-input" data-key="property_types_allowed" data-type="array" value="townhouse"> Townhouse</label>
                                        <label class="checkbox-label"><input type="checkbox" class="rule-input" data-key="property_types_allowed" data-type="array" value="commercial"> Commercial</label>
                                    </div>
                                </div>
                                <div class="rule-field">
                                    <label>Min Property Value (EGP)</label>
                                    <input type="number" class="rule-input" data-key="min_property_value" data-type="number" placeholder="e.g. 500000">
                                </div>
                                <div class="rule-field">
                                    <label>Max Property Value (EGP)</label>
                                    <input type="number" class="rule-input" data-key="max_property_value" data-type="number" placeholder="e.g. 10000000">
                                </div>
                                <div class="rule-field" style="grid-column: 1 / -1;">
                                    <label>Allowed Coverage Types</label>
                                    <div class="checkbox-group">
                                        <label class="checkbox-label"><input type="checkbox" class="rule-input" data-key="coverage_types_allowed" data-type="array" value="basic"> Basic</label>
                                        <label class="checkbox-label"><input type="checkbox" class="rule-input" data-key="coverage_types_allowed" data-type="array" value="comprehensive"> Comprehensive</label>
                                        <label class="checkbox-label"><input type="checkbox" class="rule-input" data-key="coverage_types_allowed" data-type="array" value="fire_only"> Fire Only</label>
                                        <label class="checkbox-label"><input type="checkbox" class="rule-input" data-key="coverage_types_allowed" data-type="array" value="natural_disaster"> Natural Disaster</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div id="jsonPreview" style="display:none; margin-top:12px;">
                            <pre id="jsonPreviewText" style=" display:none; background:#1a1a2e; color:#4ade80; padding:14px 18px; border-radius:10px; font-size:13px; overflow-x:auto; margin:0; white-space:pre-wrap; word-break:break-word;"></pre>
                        </div>

                        <input type="hidden" name="eligibility_rules" id="eligibilityRulesHidden">
                    </div>

                    <div class="form-actions">
                        <button type="submit" name="add_plan" class="btn btn-primary">Add Plan</button>
                    </div>
                </form>
            </div>



            <script>
            (function() {
                const categorySelect = document.getElementById('planCategorySelect');
                const hint = document.getElementById('selectCategoryHint');
                const preview = document.getElementById('jsonPreview');
                const previewText = document.getElementById('jsonPreviewText');
                const hiddenField = document.getElementById('eligibilityRulesHidden');
                const allPanels = document.querySelectorAll('.eligibility-rules-panel');

                function getCategoryKey(name) {
                    name = name.toLowerCase();
                    if (name.includes('car') || name.includes('motor') || name.includes('vehicle')) return 'car';
                    if (name.includes('health') || name.includes('medical')) return 'health';
                    if (name.includes('life')) return 'life';
                    if (name.includes('property') || name.includes('home')) return 'property';
                    return null;
                }

                categorySelect.addEventListener('change', function() {
                    const selected = this.options[this.selectedIndex];
                    const catName = selected.getAttribute('data-name') || '';
                    const key = getCategoryKey(catName);

                    allPanels.forEach(p => p.style.display = 'none');

                    if (key) {
                        const panel = document.getElementById('rules-' + key);
                        if (panel) {
                            panel.style.display = 'block';
                            hint.style.display = 'none';
                        }
                    } else {
                        hint.style.display = 'block';
                        preview.style.display = 'none';
                        hiddenField.value = '';
                    }
                    buildJson();
                });

                function buildJson() {
                    const visiblePanel = document.querySelector('.eligibility-rules-panel[style*="display: block"], .eligibility-rules-panel[style*="display:block"]');
                    if (!visiblePanel) {
                        hiddenField.value = '';
                        preview.style.display = 'none';
                        return;
                    }

                    const inputs = visiblePanel.querySelectorAll('.rule-input');
                    const json = {};

                    const arrayGroups = {};

                    inputs.forEach(input => {
                        const key = input.getAttribute('data-key');
                        const type = input.getAttribute('data-type');

                        if (type === 'array') {
                            if (!arrayGroups[key]) arrayGroups[key] = [];
                            if (input.checked) {
                                arrayGroups[key].push(input.value);
                            }
                        } else if (type === 'boolean') {
                            if (input.value !== '') {
                                json[key] = input.value === 'true';
                            }
                        } else if (type === 'number') {
                            if (input.value !== '') {
                                json[key] = parseFloat(input.value);
                            }
                        } else {
                            if (input.value.trim() !== '') {
                                json[key] = input.value.trim();
                            }
                        }
                    });

                    for (const [key, arr] of Object.entries(arrayGroups)) {
                        if (arr.length > 0) {
                            json[key] = arr;
                        }
                    }

                    if (Object.keys(json).length > 0) {
                        const jsonStr = JSON.stringify(json, null, 2);
                        hiddenField.value = JSON.stringify(json);
                        previewText.textContent = jsonStr;
                        preview.style.display = 'block';
                    } else {
                        hiddenField.value = '';
                        preview.style.display = 'none';
                    }
                }

                document.addEventListener('input', function(e) {
                    if (e.target.classList.contains('rule-input')) buildJson();
                });
                document.addEventListener('change', function(e) {
                    if (e.target.classList.contains('rule-input')) buildJson();
                });
            })();
            </script>

        <?php elseif ($active_tab === 'messages'): ?>
            <div class="page-title"><i class='bx bx-envelope'></i> Messeges</div>
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
                    <th style="width: 15%;">Email</th>
                    <th style="width: 15%;">Message</th>
                    <th style="width: 15%;">Date Received</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($all_messages && mysqli_num_rows($all_messages) > 0): ?>
                    <?php while ($msg = mysqli_fetch_assoc($all_messages)): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($msg['name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($msg['email']); ?></td>
                            <td style="max-width: 250px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: var(--text-main);">
                                <?php echo htmlspecialchars($msg['message']); ?>
                            </td>
                            <td style="color: var(--text-muted); font-size: 13px;"><?php echo date('M d, Y, h:i A', strtotime($msg['created_at'])); ?></td>
                        </tr>
                    <?php endwhile; ?>
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
   <div id="messageModal" class="custom-modal">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-title-wrapper">
                    <i class='bx bx-envelope' style="font-size: 20px; color: #1E4ED8; vertical-align: middle;"></i>
                    <h3 id="modalSubject" style="display: inline-block; margin-left: 5px;">Subject</h3>
                </div>
                <span class="close-btn" id="closeModalBtn">&times;</span>
            </div>
            <div class="modal-body">
                <div class="info-group">
                    <strong>Sender:</strong> <span id="modalSender"></span>
                </div>
                <div class="info-group">
                    <strong>Email:</strong> <span id="modalEmail"></span>
                </div>
                <div class="info-group">
                    <strong>Date:</strong> <span id="modalDate"></span>
                </div>
                <hr class="modal-divider">
                <div class="message-content">
                    <strong>Message:</strong>
                    <p id="modalMessageText" ></p>
                </div>
            </div>
        </div>
    </div>


        <?php endif; ?>

    </div>

<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    window.AdminDashboardRevenueData = <?= $chart_data_json ?>;
    window.AdminDashboardRevenueLabels = <?= $chart_labels_json ?>;
</script>
<script src="/Graduation-Project/assets/js/admindashboard.js"></script>

<div id="customConfirmModal" class="custom-modal">
    <div class="modal-content" style="max-width: 400px; text-align: center; padding: 30px 24px;">
        <div style="font-size: 48px; color: #EF4444; margin-bottom: 16px;">
            <i class='bx bx-error-circle'></i>
        </div>
        <h3 id="confirmModalTitle" style="font-size: 18px; font-weight: 700; color: var(--primary-navy); margin-bottom: 8px;">Are you sure?</h3>
        <p id="confirmModalMessage" style="font-size: 14px; color: var(--text-muted); margin-bottom: 24px; line-height: 1.5;">Do you really want to delete this item? This action cannot be undone.</p>
        <div style="display: flex; gap: 12px; justify-content: center;">
            <button id="confirmModalCancelBtn" class="btn btn-cancel" style="flex: 1; padding: 10px;">Cancel</button>
            <button id="confirmModalConfirmBtn" class="btn" style="flex: 1; padding: 10px; background-color: #EF4444; color: white;">Delete</button>
        </div>
    </div>
</div>

<div id="editAgentModal" class="custom-modal">
    <div class="modal-content" style="max-width: 500px;">
        <div class="modal-header">
            <div class="modal-title-wrapper">
                <i class='bx bx-user' style="font-size: 20px; color: #3B82F6; vertical-align: middle;"></i>
                <h3 style="display: inline-block; margin-left: 5px; font-size: 18px; font-weight: 700;">Edit Agent Account</h3>
            </div>
            <span class="close-btn" id="closeEditAgentModalBtn">&times;</span>
        </div>
        <div class="modal-body">
            <form action="AdminDashboard.php" method="post" id="editAgentForm">
                <input type="hidden" name="user_id" id="edit-agent-id">

                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="name" id="edit-agent-name" placeholder="Enter agent's full name" required>
                </div>
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" id="edit-agent-email" placeholder="Enter agent's email" required>
                </div>
                <div class="form-group">
                    <label>Password <small style="color:#9ca3af;">(Leave blank to keep current)</small></label>
                    <input type="password" name="password" id="edit-agent-password" placeholder="Leave blank to keep current password">
                </div>
                
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="submit" name="edit_agent" class="btn btn-primary" style="flex: 1;">Update Agent</button>
                    <button type="button" class="btn btn-cancel" id="cancelEditAgentBtn" style="flex: 1;">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="editPlanModal" class="custom-modal">
    <div class="modal-content" style="max-width: 700px; max-height: 90vh; overflow-y: auto;">
        <div class="modal-header">
            <div class="modal-title-wrapper">
                <i class='bx bx-shield' style="font-size: 20px; color: #3B82F6; vertical-align: middle;"></i>
                <h3 style="display: inline-block; margin-left: 5px; font-size: 18px; font-weight: 700;">Edit Insurance Plan</h3>
            </div>
            <span class="close-btn" id="closeEditPlanModalBtn">&times;</span>
        </div>
        <div class="modal-body">
            <form action="AdminDashboard.php" method="post" id="editPlanForm" enctype="multipart/form-data">
                <input type="hidden" name="plan_id" id="edit-plan-id">

                <div class="form-group">
                    <label>Company Logo <small style="color:#9ca3af;">(JPG, PNG, WEBP, SVG — max 2MB)</small></label>
                    <div class="logo-upload-zone" id="editLogoZone">
                        <input type="file" name="logo" id="editLogoInput" accept=".jpg,.jpeg,.png,.webp,.svg" style="display:none;">
                        <div class="logo-upload-preview" id="editLogoPreview">
                            <i class='bx bx-image-add' style="font-size:32px; color:#9ca3af;"></i>
                            <p style="margin:8px 0 0; font-size:13px; color:#9ca3af;">Click to upload logo</p>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Plan Name</label>
                    <input type="text" name="name" id="edit-plan-name" placeholder="Enter plan name" required>
                </div>
                <div class="form-group">
                    <label>Category</label>
                    <select name="category_id" id="edit-plan-category" required>
                        <option value="">Select a category</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['category_id']; ?>" data-name="<?php echo htmlspecialchars(strtolower($cat['name'])); ?>">
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Insurance Company</label>
                    <input type="text" name="insurance_company" id="edit-plan-company" placeholder="Enter insurance company name" required>
                </div>
                <div class="form-group">
                    <label>Base Price</label>
                    <input type="number" step="0.01" name="base_price" id="edit-plan-price" placeholder="Enter base price" required>
                </div>
                <div class="form-group">
                    <label>Commission Rate</label>
                    <input type="number" step="0.01" name="commission_rate" id="edit-plan-commission" placeholder="Commission Rate (e.g. 0.15)" required>
                </div>
                <div class="form-group">
                    <label>Details / Bio</label>
                    <textarea name="bio" id="edit-plan-bio" rows="3" placeholder="Enter plan details" required></textarea>
                </div>

                <div class="form-group">
                    <label><i class='bx bx-list-check' style="margin-right:6px;"></i>Eligibility Rules</label>
                    
                    <div id="edit-rules-car" class="edit-eligibility-rules-panel" style="display:none; background:white; border-radius:8px; padding:16px;">
                        <div class="rules-grid">
                            <div class="rule-field">
                                <label>Car Condition Allowed</label>
                                <div class="checkbox-group">
                                    <label class="checkbox-label"><input type="checkbox" class="edit-rule-input" data-key="condition" data-type="array" value="new"> New</label>
                                    <label class="checkbox-label"><input type="checkbox" class="edit-rule-input" data-key="condition" data-type="array" value="used"> Used</label>
                                </div>
                            </div>
                            <div class="rule-field">
                                <label>Min Year</label>
                                <input type="number" class="edit-rule-input" data-key="min_year" data-type="number" placeholder="e.g. 2015">
                            </div>
                            <div class="rule-field">
                                <label>Max Year</label>
                                <input type="number" class="edit-rule-input" data-key="max_year" data-type="number" placeholder="e.g. 2025">
                            </div>
                            <div class="rule-field">
                                <label>Min Car Value (EGP)</label>
                                <input type="number" class="edit-rule-input" data-key="min_price" data-type="number" placeholder="e.g. 100000">
                            </div>
                            <div class="rule-field">
                                <label>Max Car Value (EGP)</label>
                                <input type="number" class="edit-rule-input" data-key="max_price" data-type="number" placeholder="e.g. 2000000">
                            </div>
                        </div>
                    </div>

                    <div id="edit-rules-health" class="edit-eligibility-rules-panel" style="display:none; background:white; border-radius:8px; padding:16px;">
                        <div class="rules-grid">
                            <div class="rule-field">
                                <label>Min Age</label>
                                <input type="number" class="edit-rule-input" data-key="min_age" data-type="number" placeholder="e.g. 18">
                            </div>
                            <div class="rule-field">
                                <label>Max Age</label>
                                <input type="number" class="edit-rule-input" data-key="max_age" data-type="number" placeholder="e.g. 65">
                            </div>
                            <div class="rule-field">
                                <label>Chronic Conditions Allowed?</label>
                                <select class="edit-rule-input" data-key="chronic_allowed" data-type="boolean">
                                    <option value="">-- Leave empty --</option>
                                    <option value="true">Yes</option>
                                    <option value="false">No</option>
                                </select>
                            </div>
                            <div class="rule-field">
                                <label>Covers Spouse?</label>
                                <select class="edit-rule-input" data-key="covers_spouse" data-type="boolean">
                                    <option value="">-- Leave empty --</option>
                                    <option value="true">Yes</option>
                                    <option value="false">No</option>
                                </select>
                            </div>
                            <div class="rule-field">
                                <label>Max Children</label>
                                <input type="number" class="edit-rule-input" data-key="max_children" data-type="number" placeholder="e.g. 4">
                            </div>
                        </div>
                    </div>

                    <div id="edit-rules-life" class="edit-eligibility-rules-panel" style="display:none; background:white; border-radius:8px; padding:16px;">
                        <div class="rules-grid">
                            <div class="rule-field">
                                <label>Min Age</label>
                                <input type="number" class="edit-rule-input" data-key="min_age" data-type="number" placeholder="e.g. 21">
                            </div>
                            <div class="rule-field">
                                <label>Max Age</label>
                                <input type="number" class="edit-rule-input" data-key="max_age" data-type="number" placeholder="e.g. 60">
                            </div>
                            <div class="rule-field">
                                <label>Min Coverage (EGP)</label>
                                <input type="number" class="edit-rule-input" data-key="min_coverage" data-type="number" placeholder="e.g. 50000">
                            </div>
                            <div class="rule-field">
                                <label>Max Coverage (EGP)</label>
                                <input type="number" class="edit-rule-input" data-key="max_coverage" data-type="number" placeholder="e.g. 5000000">
                            </div>
                            <div class="rule-field" style="grid-column: 1 / -1;">
                                <label>Allowed Policy Terms</label>
                                <div class="checkbox-group">
                                    <label class="checkbox-label"><input type="checkbox" class="edit-rule-input" data-key="allowed_terms" data-type="array" value="10_years"> 10 Years</label>
                                    <label class="checkbox-label"><input type="checkbox" class="edit-rule-input" data-key="allowed_terms" data-type="array" value="20_years"> 20 Years</label>
                                    <label class="checkbox-label"><input type="checkbox" class="edit-rule-input" data-key="allowed_terms" data-type="array" value="30_years"> 30 Years</label>
                                    <label class="checkbox-label"><input type="checkbox" class="edit-rule-input" data-key="allowed_terms" data-type="array" value="whole_life"> Whole Life</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="edit-rules-property" class="edit-eligibility-rules-panel" style="display:none; background:white; border-radius:8px; padding:16px;">
                        <div class="rules-grid">
                            <div class="rule-field" style="grid-column: 1 / -1;">
                                <label>Allowed Property Types</label>
                                <div class="checkbox-group">
                                    <label class="checkbox-label"><input type="checkbox" class="edit-rule-input" data-key="property_types_allowed" data-type="array" value="apartment"> Apartment</label>
                                    <label class="checkbox-label"><input type="checkbox" class="edit-rule-input" data-key="property_types_allowed" data-type="array" value="villa"> Villa</label>
                                    <label class="checkbox-label"><input type="checkbox" class="edit-rule-input" data-key="property_types_allowed" data-type="array" value="townhouse"> Townhouse</label>
                                    <label class="checkbox-label"><input type="checkbox" class="edit-rule-input" data-key="property_types_allowed" data-type="array" value="commercial"> Commercial</label>
                                </div>
                            </div>
                            <div class="rule-field">
                                <label>Min Property Value (EGP)</label>
                                <input type="number" class="edit-rule-input" data-key="min_property_value" data-type="number" placeholder="e.g. 500000">
                            </div>
                            <div class="rule-field">
                                <label>Max Property Value (EGP)</label>
                                <input type="number" class="edit-rule-input" data-key="max_property_value" data-type="number" placeholder="e.g. 10000000">
                            </div>
                            <div class="rule-field" style="grid-column: 1 / -1;">
                                <label>Allowed Coverage Types</label>
                                <div class="checkbox-group">
                                    <label class="checkbox-label"><input type="checkbox" class="edit-rule-input" data-key="coverage_types_allowed" data-type="array" value="basic"> Basic</label>
                                    <label class="checkbox-label"><input type="checkbox" class="edit-rule-input" data-key="coverage_types_allowed" data-type="array" value="comprehensive"> Comprehensive</label>
                                    <label class="checkbox-label"><input type="checkbox" class="edit-rule-input" data-key="coverage_types_allowed" data-type="array" value="fire_only"> Fire Only</label>
                                    <label class="checkbox-label"><input type="checkbox" class="edit-rule-input" data-key="coverage_types_allowed" data-type="array" value="natural_disaster"> Natural Disaster</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="edit-jsonPreview" style="display:none; margin-top:12px;">
                        <pre id="edit-jsonPreviewText" style=" display:none; background:#1a1a2e; color:#4ade80; padding:14px 18px; border-radius:10px; font-size:13px; overflow-x:auto; margin:0; white-space:pre-wrap; word-break:break-word;"></pre>
                    </div>

                    <input type="hidden" name="eligibility_rules" id="edit-eligibilityRulesHidden">
                </div>

                <div class="form-actions">
                    <button type="submit" name="edit_plan" class="btn btn-primary">Save Changes</button>
                    <button type="button" class="btn btn-cancel" id="cancelEditPlanModalBtn">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function() {
    const editModal = document.getElementById('editPlanModal');
    const closeBtn = document.getElementById('closeEditPlanModalBtn');
    const cancelBtn = document.getElementById('cancelEditPlanModalBtn');
    const categorySelect = document.getElementById('edit-plan-category');
    const hiddenField = document.getElementById('edit-eligibilityRulesHidden');
    const preview = document.getElementById('edit-jsonPreview');
    const previewText = document.getElementById('edit-jsonPreviewText');
    const allPanels = document.querySelectorAll('.edit-eligibility-rules-panel');

    function getCategoryKey(name) {
        name = name.toLowerCase();
        if (name.includes('car') || name.includes('motor') || name.includes('vehicle')) return 'car';
        if (name.includes('health') || name.includes('medical')) return 'health';
        if (name.includes('life')) return 'life';
        if (name.includes('property') || name.includes('home')) return 'property';
        return null;
    }

    function toggleRulesPanel() {
        const selected = categorySelect.options[categorySelect.selectedIndex];
        const catName = selected ? (selected.getAttribute('data-name') || '') : '';
        const key = getCategoryKey(catName);

        allPanels.forEach(p => p.style.display = 'none');

        if (key) {
            const panel = document.getElementById('edit-rules-' + key);
            if (panel) {
                panel.style.display = 'block';
            }
        } else {
            preview.style.display = 'none';
            hiddenField.value = '';
        }
        buildJson();
    }

    categorySelect.addEventListener('change', toggleRulesPanel);

    function buildJson() {
        const visiblePanel = document.querySelector('.edit-eligibility-rules-panel[style*="display: block"], .edit-eligibility-rules-panel[style*="display:block"]');
        if (!visiblePanel) {
            hiddenField.value = '';
            preview.style.display = 'none';
            return;
        }

        const inputs = visiblePanel.querySelectorAll('.edit-rule-input');
        const json = {};
        const arrayGroups = {};

        inputs.forEach(input => {
            const key = input.getAttribute('data-key');
            const type = input.getAttribute('data-type');

            if (type === 'array') {
                if (!arrayGroups[key]) arrayGroups[key] = [];
                if (input.checked) {
                    arrayGroups[key].push(input.value);
                }
            } else if (type === 'boolean') {
                if (input.value !== '') {
                    json[key] = input.value === 'true';
                }
            } else if (type === 'number') {
                if (input.value !== '') {
                    json[key] = parseFloat(input.value);
                }
            } else {
                if (input.value.trim() !== '') {
                    json[key] = input.value.trim();
                }
            }
        });

        for (const [key, arr] of Object.entries(arrayGroups)) {
            if (arr.length > 0) {
                json[key] = arr;
            }
        }

        if (Object.keys(json).length > 0) {
            const jsonStr = JSON.stringify(json, null, 2);
            hiddenField.value = JSON.stringify(json);
            previewText.textContent = jsonStr;
            preview.style.display = 'block';
        } else {
            hiddenField.value = '';
            preview.style.display = 'none';
        }
    }

    // Attach listener for real-time validation / preview compilation
    document.addEventListener('input', function(e) {
        if (e.target.classList.contains('edit-rule-input')) buildJson();
    });
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('edit-rule-input')) buildJson();
    });

    // Populate Modal with Plan Data
    document.querySelectorAll('.edit-plan-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            const plan = JSON.parse(this.getAttribute('data-plan'));

            // Basic Fields
            document.getElementById('edit-plan-id').value = plan.plan_id;
            document.getElementById('edit-plan-name').value = plan.name;
            document.getElementById('edit-plan-category').value = plan.category_id;
            document.getElementById('edit-plan-company').value = plan.insurance_company;
            document.getElementById('edit-plan-price').value = plan.base_price;
            document.getElementById('edit-plan-commission').value = plan.commission_rate;
            document.getElementById('edit-plan-bio').value = plan.bio;

            // Trigger Rules Panel Display
            toggleRulesPanel();

            // Populate Rules from JSON
            let rules = {};
            try {
                rules = JSON.parse(plan.eligibility_rules || '{}');
            } catch(err) {}

            const visiblePanel = document.querySelector('.edit-eligibility-rules-panel[style*="display: block"], .edit-eligibility-rules-panel[style*="display:block"]');
            if (visiblePanel) {
                const inputs = visiblePanel.querySelectorAll('.edit-rule-input');
                inputs.forEach(input => {
                    const key = input.getAttribute('data-key');
                    const type = input.getAttribute('data-type');
                    const ruleVal = rules[key];

                    if (type === 'array') {
                        input.checked = Array.isArray(ruleVal) && ruleVal.includes(input.value);
                    } else if (type === 'boolean') {
                        if (ruleVal !== undefined) {
                            input.value = ruleVal ? 'true' : 'false';
                        } else {
                            input.value = '';
                        }
                    } else {
                        input.value = ruleVal !== undefined ? ruleVal : '';
                    }
                });
            }

            // Rebuild JSON to establish correct preview state
            buildJson();

            // Pre-populate logo preview
            const editLogoPreview = document.getElementById('editLogoPreview');
            const editLogoInput = document.getElementById('editLogoInput');
            if (editLogoInput) {
                editLogoInput.value = '';
            }
            if (editLogoPreview) {
                if (plan.logo) {
                    editLogoPreview.innerHTML = '<img src="/Graduation-Project/' + plan.logo + '" style="max-height:80px; max-width:160px; object-fit:contain; border-radius:6px;">'
                        + '<p style="margin:6px 0 0; font-size:12px; color:#6b7280;">Current Logo</p>';
                } else {
                    editLogoPreview.innerHTML = '<i class=\'bx bx-image-add\' style="font-size:32px; color:#9ca3af;"></i>'
                        + '<p style="margin:8px 0 0; font-size:13px; color:#9ca3af;">Click to upload logo</p>';
                }
            }

            // Open Modal
            editModal.classList.add('show');
        });
    });

    // Close Modal Event Handlers
    function closeModal() {
        editModal.classList.remove('show');
    }
    if (closeBtn) closeBtn.addEventListener('click', closeModal);
    if (cancelBtn) cancelBtn.addEventListener('click', closeModal);
    window.addEventListener('click', function(e) {
        if (e.target === editModal) closeModal();
    });
})();



(function addLogo(){
    const zone = document.getElementById('addLogoZone');
    const input = document.getElementById('addLogoInput');
    const preview = document.getElementById('addLogoPreview');
    if (!zone) return;
    zone.addEventListener('click', () => input.click());
    input.addEventListener('change', function() {
        if (this.files && this.files[0]) {
            const reader = new FileReader();
            reader.onload = e => {
                preview.innerHTML = '<img src="' + e.target.result + '" style="max-height:80px; max-width:160px; object-fit:contain; border-radius:6px;">'
                    + '<p style="margin:6px 0 0; font-size:12px; color:#6b7280;">' + this.files[0].name + '</p>';
            };
            reader.readAsDataURL(this.files[0]);
        }
    });
    zone.addEventListener('dragover', e => { e.preventDefault(); zone.classList.add('dragover'); });
    zone.addEventListener('dragleave', () => zone.classList.remove('dragover'));
    zone.addEventListener('drop', e => {
        e.preventDefault(); zone.classList.remove('dragover');
        input.files = e.dataTransfer.files;
        input.dispatchEvent(new Event('change'));
    });
})();

const editZone = document.getElementById('editLogoZone');
const editInput = document.getElementById('editLogoInput');
const editPreview = document.getElementById('editLogoPreview');
if (editZone) {
    editZone.addEventListener('click', () => editInput.click());
    editInput.addEventListener('change', function() {
        if (this.files && this.files[0]) {
            const reader = new FileReader();
            reader.onload = e => {
                editPreview.innerHTML = '<img src="' + e.target.result + '" style="max-height:60px; max-width:140px; object-fit:contain; border-radius:6px;">'
                    + '<p style="margin:4px 0 0; font-size:12px; color:#6b7280;">' + this.files[0].name + '</p>';
            };
            reader.readAsDataURL(this.files[0]);
        }
    });
    editZone.addEventListener('dragover', e => { e.preventDefault(); editZone.classList.add('dragover'); });
    editZone.addEventListener('dragleave', () => editZone.classList.remove('dragover'));
    editZone.addEventListener('drop', e => {
        e.preventDefault(); editZone.classList.remove('dragover');
        editInput.files = e.dataTransfer.files;
        editInput.dispatchEvent(new Event('change'));
    });
}

(function() {
    const editAgentModal = document.getElementById('editAgentModal');
    const closeBtn = document.getElementById('closeEditAgentModalBtn');
    const cancelBtn = document.getElementById('cancelEditAgentBtn');
    if (!editAgentModal) return;

    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.edit-agent-btn');
        if (btn) {
            e.preventDefault();
            const agentData = JSON.parse(btn.getAttribute('data-agent'));
            
            document.getElementById('edit-agent-id').value = agentData.user_id;
            document.getElementById('edit-agent-name').value = agentData.name;
            document.getElementById('edit-agent-email').value = agentData.email;
            document.getElementById('edit-agent-password').value = '';

            editAgentModal.classList.add('show');
        }
    });

    function closeModal() {
        editAgentModal.classList.remove('show');
    }
    if (closeBtn) closeBtn.addEventListener('click', closeModal);
    if (cancelBtn) cancelBtn.addEventListener('click', closeModal);
    window.addEventListener('click', function(e) {
        if (e.target === editAgentModal) closeModal();
    });
})();

(function() {
    const confirmModal = document.getElementById('customConfirmModal');
    const cancelBtn = document.getElementById('confirmModalCancelBtn');
    const confirmBtn = document.getElementById('confirmModalConfirmBtn');
    const messageEl = document.getElementById('confirmModalMessage');
    const titleEl = document.getElementById('confirmModalTitle');
    if (!confirmModal) return;

    let targetUrl = '';

    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.delete-confirm-btn');
        if (btn) {
            e.preventDefault();
            targetUrl = btn.getAttribute('href');
            
            const customMessage = btn.getAttribute('data-message');
            const customTitle = btn.getAttribute('data-title');
            
            if (messageEl && customMessage) {
                messageEl.textContent = customMessage;
            }
            if (titleEl && customTitle) {
                titleEl.textContent = customTitle;
            } else if (titleEl) {
                titleEl.textContent = 'Confirm Deletion';
            }

            confirmModal.classList.add('show');
        }
    });

    function closeModal() {
        confirmModal.classList.remove('show');
    }

    if (cancelBtn) cancelBtn.addEventListener('click', closeModal);
    if (confirmBtn) {
        confirmBtn.addEventListener('click', function() {
            if (targetUrl) {
                window.location.href = targetUrl;
            }
        });
    }

    window.addEventListener('click', function(e) {
        if (e.target === confirmModal) closeModal();
    });
})();

</script>

</body>
</html>
