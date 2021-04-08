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
    <h1>Error Page</h1>
    <?php 
    if(isset($_SESSION['account'])) {
        printTitleBar();
    }
    ?>
</header>
<main> 
<?php flashMessage() ?>
    <p>Sorry. Please try again later or <a href="contact.php">contact us</a> if there are any problem.</p>
    <p></a href="index.php">Return to main page.</a></p>
</main>
</body>
</html>