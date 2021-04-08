<?php
require_once "pdo.php";
session_start();

//Access deny when not login
if(!isset($_SESSION['account'])) {
    die("ACCESS DENIED");
}

//Check missing parameter
if(!isset($_GET['familyId']) || !isset($_GET['familyName'])) {
    $_SESSION['error'] = "Parameter missing.";
    header('Location: family.php');
    return;
} else if(strlen($_GET['familyId']) < 1 || strlen($_GET['familyName']) < 1) {
    $_SESSION['error'] = "Invalid parameter.";
    header('Location: family.php');
    return;
}

//Return to family.php if family not exist
try {
    $stmt = $pdo->prepare('SELECT * FROM Family WHERE id = :familyId AND name = :familyName');
    $stmt->execute(array(
        ':familyId' => $_GET['familyId'],
        ':familyName' => $_GET['familyName']
    ));
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if($row === false) {
        $_SESSION['error'] = "Family does not exist or you do not have permit to access. Please confirm your entries are correct. Email us if there are any problem.";
        header('Location: family.php');
        return;
    }
} catch(Throuwable $e) {
    header('Location: error.php');
    return;
}

//Access deny if not member of the family
try {
    $stmt = $pdo->prepare('SELECT user_id FROM FamilyMap WHERE family_id = :familyId AND user_id = :userId');
    $stmt->execute(array(
        ':familyId' => $_GET['familyId'],
        ':userId' => $_SESSION['userId']
    ));
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if($row === false) {
        $_SESSION['error'] = "Family does not exist or you do not have permit to access. Please confirm your entries are correct. Email us if there are any problem.";
        header('Location: family.php');
        return;
    }
} catch(Throuwable $e) {
    header('Location: error.php');
    return;
}

//Get family detail
try {
    $stmt = $pdo->prepare('SELECT * FROM Family WHERE id = :familyId AND name = :familyName');
    $stmt->execute(array(
        ':familyId' => $_GET['familyId'],
        ':familyName' => $_GET['familyName']
    ));
    $detail = $stmt->fetch(PDO::FETCH_ASSOC);
} catch(Throuwable $e) {
    header('Location: error.php');
    return;
}
?>

<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <title>Family Detail</title>
</head>
<body>
<header>
    <h1>Family Detail</h1>
    <ul id="selectPage">
        <li><a href="index.php">Home</a></li>
        <li><a href="family.php">My Family</a></li>
        <li><a href="device.php">My Device</a></li>
        <li><a href="setting.php">Profile Setting</a></li>
        <li><a href="contact.php">Contact Us</a></li>
        <li><a href="logout.php">Log Out</a></li>
    </ul>
</header>

<main>
    <p>Family Name: <?=$detail['name']?></p>
    <p>Family ID: <?=$detail['id']?></p>
    <p>Family Member: 
    <table>
    <?php
        $stmt = $pdo->prepare('SELECT user_id FROM FamilyMap WHERE family_id = :familyId');
        $stmt->execute(array(':familyId' => $_GET['familyId']));
        $member = $stmt->fetchAll();
        foreach($member as $mem) {
            $stmt = $pdo->prepare('SELECT name FROM User WHERE id = :memId');
            $stmt->execute(array(':memId' => $mem['user_id']));
            $name = $stmt->fetch(PDO::FETCH_ASSOC);
            echo('<tr><td>' . $name['name'] . '</td></tr>');
        }
    ?>
    </table>
    </p>
    <?php
        $stmt = $pdo->prepare('SELECT name FROM User WHERE id = :adminId');
        $stmt->execute(array(':adminId' => $detail['admin']));
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        echo('<p>Admin: ' . $row['name'] . '</p>');
    ?>
</main>
</body>
</html>