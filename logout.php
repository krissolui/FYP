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
<header>
    <div class="menu-btn">
      <span class="menu-btn__burger"></span>
    </div>
    <?php printMainMenu('logout'); ?>
</header>

<main class="logout-page">
<section class="head-image">
    <h1 class="title">Smart Home - Power Consumption Monitoring System</h1>
    <h1 class="title subtitle">Do You Want To Log Out?</h1>
    <form method='POST'>
        <p><input type='submit' name='logout' value='Yes'/> &nbsp
        <input type='submit' name='stay' value='No'/></p>
    </form>
</section>

</main>
    <script src="js/main.js"></script>
</body>
</html>