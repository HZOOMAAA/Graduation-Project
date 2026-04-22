<?php
require_once __DIR__ . '/includes/auth_check.php';
requireRole('agent');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agent Dashboard</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body { font-family: sans-serif; padding: 20px; }
        .dashboard-container { max-width: 800px; margin: 0 auto; background: #e9ecef; padding: 20px; border-radius: 8px; }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <h1>Welcome to the Agent Dashboard, <?php echo htmlspecialchars($_SESSION['name'] ?? 'Agent'); ?>!</h1>
        <p>This is a protected area. Only users with the <strong>Agent</strong> role can view this page.</p>
        
        <br>
        <a href="/Graduation-Project/auth/logout.php" style="padding: 10px 15px; background: #d9534f; color: white; text-decoration: none; border-radius: 4px;">Logout</a>
    </div>
</body>
</html>
