<?php
require_once "pdo.php";
require_once "function.php";
session_start();

//Access deny when not login
accessDeny();

//Check parameter
if(!checkParameter($_GET['deviceId']) || !checkParameter($_GET['deviceIp'])) {
    $_SESSION['error'] = "Missing parameter.";
    header('Location: device.php');
    return;
}

//Return to device.php
if(isset($_POST['return'])) {
    $_SESSION['error'] = "You are the only user of this device. The device will be deleted if you remove it from your list.";
    header('Location: device.php');
    return;
}

if(isset($_POST['returnDevice'])) {
    header('Location: device.php');
    return;
}

//Change admin
if(isset($_POST['changeAdmin'])) {
    //Check new admin parameter
    if(!isset($_POST['newAdminId']) || strlen($_POST['newAdminId']) < 1) {
        $_SESSION['error'] = "Please choose the new admin.";
        header('Location: deleteDevice.php');
        return;
    } else {
        //Change Device admin
        try {
            updateDeviceAdmin($pdo, $_POST['newAdminId'], $_GET['deviceId'], $_GET['deviceIp'], $_SESSION['userId']);
        } catch(Throwable $e) {
            header('Location: error.php');
            return;
        }

        //Drop from DeviceMap
        try {
            deleteDeviceMap($pdo, $_SESSION['userId'], $_GET['deviceId']);
    
            $_SESSION['success'] = 'Authority transfered to new admin. Device removed from you list.';
            header('Location: device.php');
            return;
        } catch(Throwable $e) {
            header('Location: error.php');
            return;
        }
    }
}

//Delete device
if(isset($_POST['deleteDevice'])) {
    try {
        deleteDevice($pdo, $_GET['deviceId'], $_GET['deviceIp'], $_SESSION['userId']);

        $_SESSION['success'] = "Device deleted.";
        header('Location: device.php');
        return;
    } catch(Throwable $e) {
        header('Location: error.php');
        return;
    }
}
?>

<html lang='en'>
<head>
<meta charset='UTF-8'>
<title>Delete Device?</title>
<link rel="stylesheet" href="css/main.css">
</head>
<body>
<header>
</header>
    
<main class="deleteDevice-page">
<section class="head-image">
    <h1 class="title">Smart Home - Power Consumption Monitoring System</h1>
    <h1 class="title subtitle">Delete Device?</h1>
</section>
<?php flashMessage(); ?>

<!-- Ask if delete device -->
<section id="delete">
<p>You are the admin of the device. Do you want to delete the device?<p>
<p style="color: orange">*Cautious: All device records will be deleted.</p>
<form method="post">
<div class="removeBtns"><input type="submit" name="deleteDevice" value="Yes" class="submitBtn"/>
<?php
    try {
        $stmt = $pdo->prepare('SELECT * FROM DeviceMap WHERE device_id = :deviceId AND user_id <> :userId AND family_id IS NULL ORDER BY rank');
        $stmt->execute(array(
            ':deviceId' => $_GET['deviceId'],
            ':userId' => $_SESSION['userId']
        ));
        $row = $stmt->fetchAll();

        if($row) {
            echo('</div></form><div class="removeBtns"><button onclick="toggleVisibility(\'admin\')">No</button></div>');
        } else {
            echo('<input type="submit" name="return" value="No" class="submitBtn"/></div></form>');
        }
    } catch(Throwable $e) {
        header('error.php');
        return;
    }
?>

</section>

<!-- Ask to change admin -->
<section id="admin" hidden>
<p>Please choose the new admin.</p>
<form method="post">
<p>New Admin: 
<select name="newAdminId">
    <?php
        foreach($row as $member) {
            $memberId = $member['user_id'];
            $stmt = $pdo->prepare('SELECT id, name FROM User WHERE id = :userId');
            $stmt->execute(array(':userId' => $memberId));
            $mem = $stmt->fetch(PDO::FETCH_ASSOC);
            echo('<option value="' . $mem['id'] . '">' . $mem['name'] . '</option>');
        }
    ?>
</select></p>
<p class="removeBtns"><input type="submit" name="changeAdmin" value="Submit" class="submitBtn"/>
<input type="submit" name="returnDevice" value="Cancel" class="submitBtn"></p>
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
<script src="js/main.js"></script>
</main>
</html>