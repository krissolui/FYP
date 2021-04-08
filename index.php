<?php
require_once "pdo.php";
define('TIMEZONE', 'HongKong');
date_default_timezone_set(TIMEZONE);
session_start();
?>

<html lang='en'>
<head>
<meta charset='UTF-8'>
<title>Smart Power Tracker</title>
</head>
<body>
<header>
<h1>Welcome to the Smart Power Tracker</h1>
<?php
    if(isset($_SESSION['account'])) {
        echo('<ul id="selectPage">
        <li id="page"><a href="index.php">Home</a></li>
        <li><a href="family.php">My Family</a></li>
        <li><a href="device.php">My Device</a></li>
        <li><a href="setting.php">Profile Setting</a></li>
        <li><a href="contact.php">Contact Us</a></li>
        <li><a href="logout.php">Log Out</a></li>
        </ul>');
        echo('<h2>Hello ' . $_SESSION['account'] . '!</h2>');
    }
?>
</header>

<main>
<?php
    if(isset($_SESSION['success'])) {
        echo('<p style="color: green">' . $_SESSION['success'] . '</p>');
        unset($_SESSION['success']);
    }

    //Not login
    if(!isset($_SESSION['account'])) {
        echo('<p><a href="login.php">Login</a></p>');
        echo('<p><a href="signup.php">Sign Up</a></p>');
    } else {
        //Login
    }
?>
</main>
</body>
</html>