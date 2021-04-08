<?php
require_once "pdo.php";
session_start();

//Access deny when not login
if(!isset($_SESSION['account'])) {
    die("ACCESS DENIED");
}

//Check parameter
if(!isset($_GET['deviceId']) || !isset($_GET['deviceIp'])) {
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
            $stmt = $pdo->prepare('UPDATE Device SET admin = :admin WHERE id = :deviceId AND ip_address = :deviceIp AND admin = :userId');
            $stmt->execute(array(
                ':admin' => $_POST['newAdminId'],
                ':deviceId' => $_GET['deviceId'],
                ':deviceIp' => $_GET['deviceIp'],
                ':userId' => $_SESSION['userId']
            ));
        } catch(Throwable $e) {
            header('Location: error.php');
            return;
        }

        //Drop from DeviceMap
        try {
            $stmt = $pdo->prepare('DELETE FROM DeviceMap WHERE user_id = :userId AND device_id = :deviceId AND family_id IS NULL');
            $stmt->execute(array(
                ':userId' => $_SESSION['userId'],
                ':deviceId' => $_GET['deviceId']
            ));
    
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
        $stmt = $pdo->prepare('DELETE FROM Device WHERE id = :deviceId AND ip_address = :deviceIp AND admin = :userId');
        $stmt->execute(array(
            ':deviceId' => $_GET['deviceId'],
            ':deviceIp' => $_GET['deviceIp'],
            ':userId' => $_SESSION['userId']
        ));

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
</head>
<body>
<header>
    <h1>Delete Device?</h1>
</header>

<main>
<?php
    if(isset($_SESSION['error'])) {
        echo('<p style="color: red">' . $_SESSION['error'] . '</p>');
        unset($_SESSION['error']);
    }
    if(isset($_SESSION['success'])) {
        echo('<p style="color: green">' . $_SESSION['success'] . '</p>');
        unset($_SESSION['success']);
    }
?>

<!-- Ask if delete device -->
<p>You are the admin of the device. Do you want to delete the device?<p>
<p style="color: orange">*Cautious: All device records will be deleted.</p>
<form method="post">
<div><input type="submit" name="deleteDevice" value="Yes"/></div>
<?php
    try {
        $stmt = $pdo->prepare('SELECT * FROM DeviceMap WHERE device_id = :deviceId AND user_id <> :userId AND family_id IS NULL ORDER BY rank');
        $stmt->execute(array(
            ':deviceId' => $_GET['deviceId'],
            ':userId' => $_SESSION['userId']
        ));
        $row = $stmt->fetchAll();

        if($row) {
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
        foreach($row as $member) {
            $memberId = $member['user_id'];
            $stmt = $pdo->prepare('SELECT id, name FROM User WHERE id = :userId');
            $stmt->execute(array(':userId' => $memberId));
            $mem = $stmt->fetch(PDO::FETCH_ASSOC);
            echo('<option value="' . $mem['id'] . '">' . $mem['name'] . '</option>');
        }
    ?>
</select></p>
<p><input type="submit" name="changeAdmin" value="Submit"/>
<input type="submit" name="returnDevice" value="Cancel"></p>
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