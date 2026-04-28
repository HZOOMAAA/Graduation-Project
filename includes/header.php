<?php
// Shared site header and navigation.
// Use require_once '../connection/connection.php'; when pages need DB access.
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Insurance</title>
    <link rel="stylesheet" href="assets/css/nav.css">
</head>
<body>
<header>
    <div class="container">
        <img class="logo" src="images/DONE.jfif" alt="COVERLY">

        <nav>
            <ul class="nav-links">
                <li><a href="#">Home</a></li>
                
                <li class="dropdown">
                    <a href="#" class="dropbtn"> 
                        Categories <i class="fas fa-chevron-down"></i>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a href="#">Health</a></li>
                        <li><a href="#">Car</a></li>
                    </ul>
                </li>

                <li><a href="#">About Us</a></li>
                <li><a href="#">Contact Us</a></li>
                
                <li class="profile">
                    <a href="profile.html">
                        <i class="fa-regular fa-circle-user"></i>
                    </a>
                </li>
            </ul>
        </nav>
    </div>
</header>
<main>
