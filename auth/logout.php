<?php
// No db connection needed for logout

session_start();
session_unset();
session_destroy();
header('Location: login.php');
exit;
