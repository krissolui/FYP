<?php
require_once "pdo.php";
require_once "function.php";
define('TIMEZONE', 'HongKong');
date_default_timezone_set(TIMEZONE);
session_start();
?>

<html lang='en'>
<head>
<meta charset='UTF-8'>
<title>Smart Power Tracker</title>
<link rel="stylesheet" href="css/main.css">
</head>
<body>
<header>
<div class="menu-btn">
    <span class="menu-btn__burger"></span>
</div>
<?php
    if(isset($_SESSION['account'])) {
        printMainMenu('home');
    } else {
        printMainMenu('unlogHome');
    }
?>

</header>

<main class="home-page">
<section class="head-image"> 
<h1 class="title">Smart Home - Power Consumption Monitoring System</h1>
<hr>
<?php
    if(isset($_SESSION['account'])) {
        echo('<h1 class="title subtitle">' . $_SESSION['account'] . '\'s Home</h1>');
    }
    flashMessage();
?>
</section>
</main>
<script src="js/main.js"></script>
</body>
</html>