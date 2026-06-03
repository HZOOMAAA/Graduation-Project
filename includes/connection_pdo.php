<?php
/**
 * connection_pdo.php
 * Establish a secure database connection using PDO.
 * Designed specifically for transaction callback processing without affecting
 * existing procedural mysqli connections in the project.
 */

// Database credentials
$localhost = "localhost";
$username  = "root";
$password  = "";
$database  = "graduation_db";
$charset   = "utf8mb4";

try {
    // Data Source Name (DSN)
    $dsn = "mysql:host=$localhost;dbname=$database;charset=$charset";
    
    // Connection options for safety and performance
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Enable exceptions for queries
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Fetch arrays with column names
        PDO::ATTR_EMULATE_PREPARES   => false,                  // Use real prepared statements
    ];
    
    // Create PDO instance
    $pdo = new PDO($dsn, $username, $password, $options);
    
} catch (PDOException $e) {
    // Log the error message to system log instead of displaying database details publicly
    error_log("Database connection failed: " . $e->getMessage());
    
    // Respond with a generic 500 error code
    http_response_code(500);
    exit(json_encode([
        "status" => "error",
        "message" => "Database connection failed"
    ]));
}
?>
