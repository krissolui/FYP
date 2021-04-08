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
<h1>Welcome to the Smart Power Tracker</h1>
<?php
    if(isset($_SESSION['account'])) {
        printTitleBar('home');
        echo('<h2>Hello ' . $_SESSION['account'] . '!</h2>');
    } else {
        printTitleBar('unlogHome');
    }
?>
</header>

<main>
<?php flashMessage() ?>
</main>
</body>
</html>