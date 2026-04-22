<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$localhost = "localhost";
$username = "root";
$password = "";
$database ="graduation_db";

$connect = mysqli_connect($localhost, $username ,$password , $database);

if(isset($_POST['logout'])) {
    session_destroy();
    unset($_SESSION['email']);
    header('location:login.php');

}
?>