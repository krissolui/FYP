<?php
require_once "function.php";
session_start();
?>

<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <title>Contact Us</title>
    <link rel="stylesheet" href="css/main.css">
</head>
<body>
<header>
<div class="menu-btn">
    <span class="menu-btn__burger"></span>
</div>
<?php 
    if(isset($_SESSION['account'])) {
        printMainMenu('contact');
    } else {
        printMainMenu('unlogContact');
    }
    ?>
</header>
<main class="contact-page"> 
<section class="head-image">
    <h1 class="title">Smart Home - Power Consumption Monitoring System</h1>
    <h1 class="title subtitle">Contact Us</h1>
</section>
    <p>Email: elecFyp64@gmail.com</p>
</main>
<script src="js/main.js"></script>
</body>
</html>