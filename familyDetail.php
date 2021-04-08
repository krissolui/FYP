<?php
require_once "pdo.php";
require_once "function.php";
session_start();

// Access deny when not login
accessDeny();

// Check missing parameter
if(!checkParameter($_GET['familyId']) || !checkParameter($_GET['familyName'])) {
    header('Location: family.php');
    return;
}

// Return to family.php if family not exist
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

// Access deny if not member of the family
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

// Remove device from family
if(isset($_POST['removeDevice'])) {
    if(checkParameter($_POST['deviceId'])) {
        deleteFamilyDeviceMap($pdo, $_GET['familyId'], $_POST['deviceId']);
        $_SESSION['success'] = "Device removed from family.";
    }
    header('Location: familyDetail.php?familyId=' . $_GET['familyId'] . '&familyName=' . $_GET['familyName']);
        return;
}

// Get family detail
try {
    $stmt = $pdo->prepare('SELECT * FROM Family WHERE id = :familyId AND name = :familyName');
    $stmt->execute(array(
        ':familyId' => $_GET['familyId'],
        ':familyName' => $_GET['familyName']
    ));
    $detail = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get admin name
    $stmt = $pdo->prepare('SELECT name FROM User WHERE id = :adminId');
    $stmt->execute(array(':adminId' => $detail['admin']));
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    $detail['adminName'] = $admin['name'];
} catch(Throuwable $e) {
    header('Location: error.php');
    return;
}

// Get family members
try {
    $stmt = $pdo->prepare('SELECT user_id FROM FamilyMap WHERE family_id = :familyId');
    $stmt->execute(array(':familyId' => $_GET['familyId']));
    $member = $stmt->fetchAll();
} catch(Throuwable $e) {
    header('Location: error.php');
    return;
}

// Get family devices
try {
    $stmt = $pdo->prepare('SELECT DISTINCT device_id FROM DeviceMap WHERE family_id = :familyId');
    $stmt->execute(array(':familyId' => $_GET['familyId']));
    $devices = $stmt->fetchAll();
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
    <?php printTitleBar('family'); ?>
</header>

<main>
<?php flashMessage(); ?>
<!-- Remove device -->
<?php printRemoveDevice(true); ?>

<!-- Display family detail -->
<section id="detail">
    <p>Family Name: <?=$detail['name']?></p>
    <p>Family ID: <?=$detail['id']?></p>
    <p>Family Member: 
    <table>
    <?php
        foreach($member as $mem) {
            $stmt = $pdo->prepare('SELECT name FROM User WHERE id = :memId');
            $stmt->execute(array(':memId' => $mem['user_id']));
            $name = $stmt->fetch(PDO::FETCH_ASSOC);
            echo('<tr><td>' . $name['name'] . '</td></tr>');
        }
    ?>
    </table>
    </p>
    <p>Family Devices:
    <table>
        <tr>
            <th>Name</th>
            <?php
                if(checkAdmin($detail['admin'])) {
                    echo('<th>Remove From Family</th>');
                }
            ?>
        </tr>
    <?php
        foreach($devices as $device) {
            $stmt = $pdo->prepare('SELECT * FROM Device WHERE id = :id');
            $stmt->execute(array(':id' => $device['device_id']));
            $deviceDetail = $stmt->fetch(PDO::FETCH_ASSOC);

            echo('<tr>');
            echo('<td><a href="deviceDetail.php?deviceName=' . $deviceDetail['name'] . '&deviceIp=' . $deviceDetail['ip_address'] . '">' . $deviceDetail['name'] . '</a></td>');
            if(checkAdmin($detail['admin'])) {
                echo('<td><button onclick="remove(\'' . $deviceDetail['name'] . '\', \'' . $deviceDetail['id'] . '\', \'' . $deviceDetail['ip_address'] . '\')">-</button></td>');
            }
            echo('</tr>');
        }
    ?>
    </table>
    </p>
    <p>Admin: <?=$detail['adminName']?></p>
<section>
</main>

<script>
    function toggleVisibility(id) {
        var element = document.getElementById(id);
        if(element.hasAttribute("hidden")) {
            element.removeAttribute("hidden");
        } else {
            element.setAttribute("hidden", "true");
        }

        if(element === document.getElementById('remove')) {
            var add = document.getElementById('add');
            if(!add.hasAttribute("hidden")) {
                add.setAttribute("hidden", "true");
            }
        } else {
            var remove = document.getElementById('remove');
            if(!remove.hasAttribute("hidden")) {
                remove.setAttribute("hidden", "true");
            }
        }
    }

    function remove(name, id, ip) {
        var remove = document.getElementById('remove');
        var deviceName = document.getElementById('deviceName');
        var deviceId = document.getElementById('deviceId');
        var deviceIp = document.getElementById('deviceIp');
        var display = document.getElementById('showDeviceName');
        
        deviceName.setAttribute('value', name);
        deviceId.setAttribute('value', id);
        deviceIp.setAttribute('value', ip);
        display.textContent = "Device Name: ".concat(name);

        if(remove.hasAttribute("hidden")) {
            toggleVisibility('remove');
        }
    }
</script>
</body>
</html>