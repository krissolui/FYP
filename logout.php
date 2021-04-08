<?php
require_once "pdo.php";
session_start();

//Access deny when not login
if(!isset($_SESSION['account'])) {
    die("ACCESS DENIED");
}

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
</head>
<body>
    <h1>Do You Want To Log Out?</h1>

    <form method='POST'>
        <p><input type='submit' name='logout' value='Yes'/> &nbsp
        <input type='submit' name='stay' value='No'/></p>
    </form>
</body>
</html>