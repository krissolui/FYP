<?php
require_once "pdo.php";
require_once "function.php";
session_start();

//Access deny when not login
accessDeny();

if(isset($_POST['logout'])) {
    //Log out
    session_destroy();
    header('Location: index.php');
    return;
} else if(isset($_POST['stay'])) {
    //Return to home page
    header('Location: index.php');
    return;
}
?>

<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <title>Log Out</title>
    <link rel="stylesheet" href="css/main.css">
</head>
<body>
    <h1>Do You Want To Log Out?</h1>
    <?php printTitleBar('logout'); ?>

    <form method='POST'>
        <p><input type='submit' name='logout' value='Yes'/> &nbsp
        <input type='submit' name='stay' value='No'/></p>
    </form>
</body>
</html>