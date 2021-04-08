<?php
require_once "function.php";
session_start();
?>

<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <title>Contact Us</title>
</head>
<body>
<header>
    <h1>Contact Us</h1>
    <?php 
    if(isset($_SESSION['account'])) {
        printTitleBar('contact');
    } else {
        printTitleBar('unlogContact');
    }
    ?>
</header>
<main> 
    <p>Email: elecFyp64@gmail.com</p>
</main>
</body>
</html>