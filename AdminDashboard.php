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

// Fetch agents
$agents = mysqli_query($connect, "SELECT * FROM users WHERE role = 'agent'");

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
$active_tab = isset($_GET['edit']) ? 'add' : (isset($_GET['tab']) ? $_GET['tab'] : 'manage');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: sans-serif; background: #f0f2f5; display: flex; min-height: 100vh; }

        /* ── Sidebar ── */
        .sidebar {
            width: 220px;
            min-height: 100vh;
            background: #1e2a38;
            display: flex;
            flex-direction: column;
            padding: 0;
            flex-shrink: 0;
        }
        .sidebar-header {
            padding: 24px 20px 20px;
            background: #16202c;
            color: #fff;
            font-size: 18px;
            font-weight: 700;
            letter-spacing: 0.5px;
            border-bottom: 1px solid #29394d;
        }
        .sidebar-header span { display: block; font-size: 12px; color: #8a9bb0; font-weight: 400; margin-top: 4px; }
        .sidebar nav { flex: 1; padding-top: 12px; }
        .sidebar nav a {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 13px 20px;
            color: #8a9bb0;
            text-decoration: none;
            font-size: 14px;
            transition: background 0.2s, color 0.2s;
            border-left: 3px solid transparent;
        }
        .sidebar nav a:hover { background: #253447; color: #fff; }
        .sidebar nav a.active { background: #253447; color: #fff; border-left-color: #4e9af1; }
        .sidebar nav a .icon { font-size: 16px; }
        .sidebar-footer {
            padding: 16px 20px;
            border-top: 1px solid #29394d;
        }
        .sidebar-footer a {
            display: block;
            text-align: center;
            padding: 9px;
            background: #c0392b;
            color: #fff;
            border-radius: 5px;
            text-decoration: none;
            font-size: 14px;
            transition: background 0.2s;
        }
        .sidebar-footer a:hover { background: #a93226; }

        /* ── Main content ── */
        .main-content { flex: 1; padding: 30px; overflow-y: auto; }
        .page-title { font-size: 22px; font-weight: 700; color: #1e2a38; margin-bottom: 6px; }
        .page-subtitle { font-size: 13px; color: #6b7c93; margin-bottom: 24px; }

        /* ── Alerts ── */
        .alert { padding: 11px 15px; margin-bottom: 20px; border-radius: 5px; font-size: 14px; }
        .alert-error { background: #fdecea; color: #b71c1c; border-left: 4px solid #e53935; }
        .alert-success { background: #e8f5e9; color: #1b5e20; border-left: 4px solid #43a047; }

        /* ── Form card ── */
        .card { background: #fff; border-radius: 8px; padding: 24px; box-shadow: 0 1px 4px rgba(0,0,0,0.08); margin-bottom: 24px; }
        .card h2 { font-size: 16px; font-weight: 600; color: #1e2a38; margin-bottom: 20px; padding-bottom: 12px; border-bottom: 1px solid #eef0f3; }
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-size: 13px; color: #4a5568; margin-bottom: 6px; font-weight: 500; }
        .form-group input {
            width: 100%; padding: 9px 12px; border: 1px solid #d1d5db;
            border-radius: 5px; font-size: 14px; color: #1e2a38;
            transition: border-color 0.2s;
        }
        .form-group input:focus { outline: none; border-color: #4e9af1; }
        .form-actions { display: flex; gap: 10px; margin-top: 20px; }
        .btn { padding: 9px 18px; border-radius: 5px; font-size: 14px; text-decoration: none; display: inline-block; cursor: pointer; border: none; font-family: sans-serif; }
        .btn-primary { background: #4e9af1; color: #fff; }
        .btn-primary:hover { background: #3a87e0; }
        .btn-cancel { background: #e5e7eb; color: #374151; }
        .btn-cancel:hover { background: #d1d5db; }

        /* ── Table ── */
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        thead tr { background: #f7f8fa; }
        th { padding: 11px 14px; text-align: left; font-size: 12px; font-weight: 600; color: #6b7c93; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid #eef0f3; }
        td { padding: 12px 14px; border-bottom: 1px solid #f0f2f5; color: #374151; vertical-align: middle; }
        tbody tr:hover { background: #f9fafb; }
        .badge { display: inline-block; padding: 3px 10px; border-radius: 12px; font-size: 12px; font-weight: 600; background: #e3f0ff; color: #1a73e8; }
        .btn-sm { padding: 5px 12px; font-size: 13px; }
        .btn-edit { background: #fff3e0; color: #e65100; }
        .btn-edit:hover { background: #ffe0b2; }
        .btn-delete { background: #fdecea; color: #c62828; }
        .btn-delete:hover { background: #ffcdd2; }
        .no-data { text-align: center; padding: 30px; color: #9ca3af; }
    </style>
</head>
<body>

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            Admin Panel
            <span>Welcome, <?php echo htmlspecialchars($_SESSION['name'] ?? 'Admin'); ?></span>
        </div>
        <nav>
            <a href="AdminDashboard.php?tab=add" class="<?php echo $active_tab === 'add' ? 'active' : ''; ?>">
                <span class="icon">➕</span> Add Agent
            </a>
            <a href="AdminDashboard.php?tab=manage" class="<?php echo $active_tab === 'manage' ? 'active' : ''; ?>">
                <span class="icon">👥</span> Manage Agents
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

        <!-- ── ADD AGENT TAB ── -->
        <?php if ($active_tab === 'add'): ?>
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
        <?php else: ?>
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
        <?php endif; ?>

    </div>
</body>
</html>
