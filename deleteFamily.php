<?php
require_once "pdo.php";
require_once "function.php";
session_start();

//Access deny when not login
accessDeny();

//Check parameter
if(!checkParameter($_GET['familyId'])) {
    header('Location: family.php');
    return;
}

//Return to family.php
if(isset($_POST['return'])) {
    $_SESSION['error'] = "You are the only member of the family. The family will be deleted if you leave.";
    header('Location: family.php');
    return;
}
if(isset($_POST['returnFamily'])) {
    header('Location: family.php');
    return;
}

//Change admin
if(isset($_POST['changeAdmin'])) {
    //Check new admin parameter
    if(!isset($_POST['newAdminId']) || strlen($_POST['newAdminId']) < 1) {
        $_SESSION['error'] = "Please choose the new admin.";
        header('Location: deleteFamily.php');
        return;
    } else {
        //Change Family admin
        try {
            updateFamilyAdmin($pdo, $_POST['newAdminId'], $_GET['familyId'], $_SESSION['userId']);
        } catch(Throwable $e) {
            header('Location: error.php');
            return;
        }

        //Drop from DeviceMap
        try{
            deleteDeviceMapThroughFamily($pdo, $_SESSION['userId'], $_GET['familyId']);
        } catch(Throwable $e) {
            header('Location: error.php');
            return;
        }

        //Drop from FamilyMap
        try {
            deleteFamilyMap($pdo, $_SESSION['userId'], $_GET['familyId']);
    
            $_SESSION['success'] = 'Authority transfered to new admin and removed from family.';
            header('Location: family.php');
            return;
        } catch(Throwable $e) {
            header('Location: error.php');
            return;
        }
    }
}

//Delete family
if(isset($_POST['deleteFamily'])) {
    try {
        deleteFamily($pdo, $_GET['familyId'], $_SESSION['userId']);

        $_SESSION['success'] = "Family deleted.";
        header('Location: family.php');
        return;
    } catch(Throwable $e) {
        header('Location: error.php');
        return;
    }
}

//Get family members
try {
    $stmt = $pdo->prepare('SELECT * FROM FamilyMap WHERE family_id = :familyId AND user_id <> :userId ORDER BY rank');
    $stmt->execute(array(
        ':familyId' => $_GET['familyId'],
        ':userId' => $_SESSION['userId']
    ));
    $member = $stmt->fetchAll();
} catch(Throwable $e) {
    header('error.php');
    return;
}
?>

<html lang='en'>
<head>
<meta charset='UTF-8'>
<title>Delete Family?</title>
</head>
<body>
<header>
    <h1>Delete Family?</h1>
</header>

<main>
<?php flashMessage(); ?>

<!-- Ask if delete family -->
<p>You are the admin of the family. Do you want to delete the family?<p>
<p style="color: orange">*Cautious: Members of the family will no longer have access to devices that are connect through the family.</p>
<form method="post">
<div><input type="submit" name="deleteFamily" value="Yes"/></div>
<?php
    try {
        if($member) {
            echo('</form><div><button onclick="toggleVisibility(\'admin\')">No</button></div>');
        } else {
            echo('<div><input type="submit" name="return" value="No"/></div></form>');
        }
    } catch(Throwable $e) {
        header('error.php');
        return;
    }
?>

<!-- Ask to change admin -->
<section id="admin" hidden>
<p>Please choose the new admin.</p>
<form method="post">
<p>New Admin: 
<select name="newAdminId">
    <?php
        foreach($member as $mem) {
            $memId = $mem['user_id'];
            $stmt = $pdo->prepare('SELECT id, name FROM User WHERE id = :userId');
            $stmt->execute(array(':userId' => $memId));
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            echo('<option value="' . $user['id'] . '">' . $user['name'] . '</option>');
        }
    ?>
</select></p>
<p><input type="submit" name="changeAdmin" value="Submit"/>
<input type="submit" name="returnFamily" value="Cancel"/></p>
</form>
</section>

<script>
function toggleVisibility(id) {
    var element = document.getElementById(id);
    if(element.hasAttribute("hidden")) {
        element.removeAttribute("hidden");
    } else {
        element.setAttribute("hidden", "true");
    }
}
</script>
</main>
</html>