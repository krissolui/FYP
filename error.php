<?php
require_once "pdo.php";
session_start();
?>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <title>Error Page</title>
</head>
<body>
<header>
    <h1>Error Page</h1>
    <ul>
        <li><a href="index.php">Home</a></li>
        <li><a href="family.php">My Family</a></li>
        <li><a href="device.php">My Device</a></li>
        <li><a href="setting.php">Profile Setting</a></li>
        <li><a href="contact.php">Contact Us</a></li>
        <li><a href="logout.php">Log Out</a></li>
    </ul>
</header>
<main> 
<?php
    if(isset($_SESSION['error'])) {
        echo('<p style="color: red">' . $_SESSION['error'] . '</p>');
        unset($_SESSION['error']);
    }
    if(isset($_SESSION['success'])) {
        echo('<p style="color: green">' . $_SESSION['success'] . '</p>');
        unset($_SESSION['success']);
    }
?>
    <p>Sorry. Please try again later or <a href="contact.php">contact us</a> if there are any problem.</p>
</main>
</body>
</html>