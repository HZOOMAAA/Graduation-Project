<?php
header('Content-Type: application/json');
require_once __DIR__ . '/connection.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request method.'
    ]);
    exit();
}

// 1. Retrieve and validate POST inputs
$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$message = isset($_POST['message']) ? trim($_POST['message']) : '';

if (empty($name) || empty($email) || empty($message)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'All fields are required.'
    ]);
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid email address.'
    ]);
    exit();
}

// 2. Escape inputs for secure SQL insertion
$name = mysqli_real_escape_string($connect, $name);
$email = mysqli_real_escape_string($connect, $email);
$message = mysqli_real_escape_string($connect, $message);

// 3. Auto-create contact_messages table if not exists
$table_query = "CREATE TABLE IF NOT EXISTS contact_messages (
    message_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    subject VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (!mysqli_query($connect, $table_query)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: failed to initialize tables. ' . mysqli_error($connect)
    ]);
    exit();
}

// 4. Insert contact message
$insert_query = "INSERT INTO contact_messages (name, email, message) VALUES ('$name', '$email', '$message')";

if (mysqli_query($connect, $insert_query)) {
    echo json_encode([
        'status' => 'success',
        'message' => 'Message sent successfully!'
    ]);
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: failed to submit message. ' . mysqli_error($connect)
    ]);
}
exit();
