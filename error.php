<?php
require_once "pdo.php";
require_once "function.php";
session_start();

?>

<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <title>Error Page</title>
    <link rel="stylesheet" href="css/main.css">
</head>
<body>
<header>
    <div class="menu-btn">
      <span class="menu-btn__burger"></span>
    </div>
    <?php 
    if(isset($_SESSION['account'])) {
        printMainMenu('unlog');
    }
    ?>
</header>
<main> 
<section class="head-image">
    <h1 class="title">Smart Home - Power Consumption Monitoring System</h1>
    <h1 class="title subtitle">Error Page</h1>
</section>
<?php flashMessage() ?>
    <p>Sorry. Please try again later or <a href="contact.php">contact us</a> if there are any problem.</p>
    <p></a href="index.php">Return to main page.</a></p>
</main>
<script src="js/main.js"></script>
</body>
</html>