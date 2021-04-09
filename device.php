<?php
require_once "pdo.php";
require_once "function.php";
define('TIMEZONE', 'HongKong');
date_default_timezone_set(TIMEZONE);
session_start();

//Access deny when not login
accessDeny();

//Add new device
if(isset($_POST['newDevice'])) {
    $_SESSION['error'] = "All field are required.";
        header('Location: device.php');
        return;
    //Missing field
    if(!checkParameter($_POST['deviceName']) || !checkParameter($_POST['deviceLocation']) || !checkParameter($_POST['deviceIp']) || !checkParameter($_POST['deviceType'])) {
        $_SESSION['error'] = "All field are required.";
        header('Location: device.php');
        return;
    } else {
        //Check if exist
        try {
            $stmt = $pdo->prepare('SELECT id FROM Device WHERE ip_address = :deviceIp');
            $stmt->execute(array(':deviceIp' => $_POST['deviceIp']));
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch(Throwable $e) {
            header('Location: error.php');
            return;
        }
        if($row !== false) {
            $_SESSION['error'] = "Device is existing. Please add as existing device.";
            header('Location: device.php');
            return;
        } else {
            //Add to Device
            try {
                addDevice($pdo, $_POST['deviceName'], $_POST['deviceLocation'], $_POST['deviceType'], $_POST['deviceIp'], $_SESSION['userId']);
            } catch(Throwable $e) {
                header('Location: error.php');
                return;
            }

            //Add to DeviceMap
            try {
                $stmt = $pdo->prepare('SELECT id FROM Device WHERE name = :deviceName AND location = :deviceLocation AND type = :deviceType AND ip_address =:deviceIp');
                $stmt->execute(array(
                    ':deviceName' => $_POST['deviceName'],
                    ':deviceLocation' => $_POST['deviceLocation'],
                    ':deviceType' => $_POST['deviceType'],
                    ':deviceIp' => $_POST['deviceIp']
                ));
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                $deviceId = $row['id'];

                addDeviceMapDirectly($pdo, $deviceId, $_SESSION['userId']);

                if(strlen($_POST['family']) > 0) {
                    $stmt = $pdo->prepare('SELECT user_id FROM FamilyMap WHERE family_id = :familyId');
                    $stmt->execute(array(':familyId' => $_POST['family']));
                    $row = $stmt->fetchAll();
                    foreach($row as $user) {
                        addDeviceMapWithFamily($pdo, $deviceId, $user['user_id'], $_POST['family']);
                    }
                }
            } catch(Throwable $e) {
                header('Location: error.php');
                return;
            }
            
            $_SESSION['success'] = 'Device added.';
            header('Location: device.php');
            return;
        }
    }
}

//Add to existing device
if(isset($_POST['existingDevice'])) {
    //Missing field
    if(!checkParameter($_POST['deviceIp'])) {
        $_SESSION['error'] = "All field are required.";
        header('Location: device.php');
        return;
    } else {
        //Check if exist
        try {
            $stmt = $pdo->prepare('SELECT id FROM Device WHERE ip_address = :deviceIp');
            $stmt->execute(array(':deviceIp' => $_POST['deviceIp']));
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $deviceId = $row['id'];
        } catch(Throwable $e) {
            header('Location: error.php');
            return;
        }
        if($row === false) {
            $_SESSION['error'] = "Device does not exist. Please add as new device.";
            header('Location: device.php');
            return;
        } else {
            //Check if already connect
            try {
                $stmt = $pdo->prepare('SELECT * FROM DeviceMap WHERE device_id = :deviceId AND user_id = :userId AND family_id IS NULL');
                $stmt->execute(array(
                    ':deviceId' => $row['id'],
                    ':userId' => $_SESSION['userId']
                ));
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
            } catch(Throwable $e) {
                header('Location: error.php');
                return;
            }
            if($row !== false) {
                $_SESSION['error'] = "Device is already connected to your account.";
                header('Location: device.php');
                return;
            } else {
                //Add to DeviceMap
                try {
                    addDeviceMapDirectly($pdo, $deviceId, $_SESSION['userId']);
                    $_SESSION['success'] = 'Device added.';
                } catch(Throwable $e) {
                    header('Location: error.php');
                    return;
                }
                if(checkParameter($_POST['family'])) {
                    try {
                        $stmt = $pdo->prepare('SELECT * FROM DeviceMap WHERE device_id = :deviceId AND family_id = :familyId');
                        $stmt->execute(array(
                            ':deviceId' => $deviceId,
                            ':familyId' => $_POST['family']
                        ));
                        $row = $stmt->fetch(PDO::FETCH_ASSOC);
                        if($row) {
                            $_SESSION['error'] = "Device is already connected to the family.";
                            header('Location: device.php');
                            return;
                        } else {
                            $stmt = $pdo->prepare('SELECT user_id FROM FamilyMap WHERE family_id = :familyId');
                            $stmt->execute(array(':familyId' => $_POST['family']));
                            $row = $stmt->fetchAll();
                            foreach($row as $user) {
                                addDeviceMapWithFamily($pdo, $deviceId, $user['user_id'], $_POST['family']);
                            }
                        }
                    } catch(Throwable $e) {
                        header('Location: error.php');
                        return;
                    }
                }
                header('Location: device.php');
                return;
            }
        }
    }
}

//Remove device
if(isset($_POST['removeDevice'])) {
    try {
        $stmt = $pdo->prepare('SELECT * FROM DeviceMap WHERE device_id = :deviceId AND user_id = :userId AND family_id IS NULL');
        $stmt->execute(array(
            ':deviceId' => $_POST['deviceId'],
            ':userId' => $_SESSION['userId']
        ));
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if($row !== false) {
            //Check if user is the admin
            $stmt = $pdo->prepare('SELECT id, admin, ip_address FROM Device WHERE id = :deviceId');
            $stmt->execute(array(':deviceId' => $_POST['deviceId']));
            $device = $stmt->fetch(PDO::FETCH_ASSOC);
            if($device['admin'] === $_SESSION['userId']) {
                //Ask if delete device
                $_POST['deviceId'] = $device['id'];
                $_POST['deviceIp'] = $device['ip_address'];
                header('Location: deleteDevice.php?deviceId=' . $device['id'] . '&deviceIp=' . $device['ip_address']);
                return;
            }
            deleteDeviceMap($pdo, $_SESSION['userId'], $_POST['deviceId']);
    
            $_SESSION['success'] = 'Device removed.';
            header('Location: device.php');
            return;
        } else {
            //Request deny if device was connected through family
            $_SESSION['error'] = 'This device is connected to your account through a family. You do not have permission to remove this device.';
            header('Location: device.php');
            return;
        }

    } catch(Throwable $e) {
        header('Location: error.php');
        return;
    }
}

//Search connected device
$stmt = $pdo->prepare('SELECT DISTINCT device_id FROM DeviceMap WHERE user_id = :userId');
$stmt->execute(array(':userId' => $_SESSION['userId']));
$devices = $stmt->fetchAll();

//Get device detail
try {
    $details = array();
    foreach($devices as $device) {
        $stmt = $pdo->prepare('SELECT * FROM Device WHERE id = :deviceId');
        $stmt->execute(array(':deviceId' => $device['device_id']));
        $detail = $stmt->fetch(PDO::FETCH_ASSOC);
        array_push($details, $detail);
    }
} catch(Throwable $e) {
    header('Location: error.php');
    return;
}

// Prepare graph
// Set display type
if(checkParameter($_GET['displayType'])) {
    $displayType = $_GET['displayType'];
} else {
    $displayType = 'Device';
}

// Generate graph label
$labels = array();

switch($displayType) {
    case 'Device':
        foreach($details as $detail) {
            if(!array_key_exists($detail['id'], $labels)) {
                $labels[$detail['id']] = $detail['name'];
            }
        }
        break;
    case 'Type':
        foreach($details as $detail) {
            if(!array_key_exists($detail['type'], $labels)) {
                try {
                    $stmt = $pdo->prepare('SELECT name FROM Type WHERE id = :typeId');
                    $stmt->execute(array(':typeId' => $detail['type']));
                    $type = $stmt->fetch(PDO::FETCH_ASSOC);

                    $labels[$detail['type']] = $type['name'];
                } catch(Throwable $e) {
                    header('Location: error.php');
                    return;
                }
            }
        }
        break;
    case 'Location':
        foreach($details as $detail) {
            if(!array_key_exists($detail['location'], $labels)) {
                try {
                    $stmt = $pdo->prepare('SELECT name FROM Location WHERE id = :locationId');
                    $stmt->execute(array(':locationId' => $detail['location']));
                    $location = $stmt->fetch(PDO::FETCH_ASSOC);

                    $labels[$detail['location']] = $location['name'];
                } catch(Throwable $e) {
                    header('Location: error.php');
                    return;
                }                
            }
        }
        break;
}

// Set display time
if(isset($_GET['displayTime']) && strlen($_GET['displayTime']) > 0) {
    $displayTime = $_GET['displayTime'];
} else {
    $displayTime = 'Day';
}

// Generate devices' power consumption
// $numOfRow: every 10 minutes
$numOfRow = 0;
$timeFormat ='';
setDisplayTime($displayTime, $numOfRow, $timeFormat);

// Get each device's record
// Get average
$avgs = array();
foreach($devices as $device) {
    try {
        $sql = 'SELECT * FROM Record WHERE device_id = :deviceId AND time >= DATE_SUB(CURRENT_TIMESTAMP(), INTERVAL 1 ' . $displayTime . ') ORDER BY time ASC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array(':deviceId' => $device['device_id']));
        $records = $stmt->fetchAll();
    } catch(Throwable $e) {
        header('Location: error.php');
        return;
    }     
    
    if($records) {
        $sum = 0;
    
        foreach($records as $record) {
            $sum += $record['current'] * $record['voltage'];
        }
        // $avg = $sum / count($records);
        $avg = $sum / $numOfRow;
    } else {
        $avg = 0;
    }
    $avgs[$device['device_id']] = $avg;
}

//Generate $dataPoints for graph
$dataPoints = array();
$total = 0;
setPieDataPoints($displayType, $devices, $details, $labels, $avgs, $total, $dataPoints);
?>

<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?=$_SESSION['account']?>'s Device</title>
    <link rel="stylesheet" href="css/main.css">
    <script src="https://canvasjs.com/assets/script/canvasjs.min.js"></script>
    <script>
        window.onload = function () {
        
            var chart = new CanvasJS.Chart("chartContainer", {
                animationEnabled: true,
                title:{
                    text: "Average Power Consumption Per <?=$displayTime?> by <?=$displayType?>"
                },
                data: [{
                    type: "pie",
                    showInLegend: "true",
                    legendText: "{label}",
                    indexLabelFontSize: 16,
                    indexLabel: "{label} - #percent%",
                    yValueFormatString: "#W",
                    dataPoints: <?php echo json_encode($dataPoints, JSON_NUMERIC_CHECK); ?>
                }]
            });
            chart.render();
        
        }
    </script>

</head>
<body>
<header>
    <div class="menu-btn">
      <span class="menu-btn__burger"></span>
    </div>
    <?php printMainMenu('device'); ?>
</header>

<main class="device-page">
<section class="head-image">
    <h1 class="title">Smart Home - Power Consumption Monitoring System</h1>
    <h1 class="title subtitle"><?=$_SESSION['account']?>'s Device</h1>
</section>
    <?php flashMessage(); ?>

<!-- Print graph -->
<section id="diagram">
<!-- Select display type -->
<?php printDisplayTypeMenu($displayType, $displayTime, 'device.php?'); ?>

<!-- Select display time -->
<?php printDisplayTimeMenu($displayType, $displayTime, 'device.php?'); ?>

<div id="chartContainer" style="height: 370px; width: 100%;" <?php if($total == 0) {echo('hidden');} ?>></div>
<p><strong>Average Power Consumption in Past <?=$displayTime?>:</strong> <?=number_format($total, 2, '.', '')?>W</p>
</section>

<!-- Add device -->
<section id="add" hidden>
    <p><button onclick="toggleVisibility('add')">Hide</button></p>
    <div id="addNewDevice">
        <h3>New Device</h3>
        <form method="post">
            <p>Device Name: <input type="text" name="deviceName"/></p>
            <p>Type: 
            <select name="deviceType">
                <?php
                try {
                    $stmt = $pdo->prepare('SELECT * FROM Type ORDER BY name ASC');
                    $stmt->execute();
                    $types = $stmt->fetchAll();
                    foreach($types as $type) {
                        echo('<option value="' . $type['id'] .'">' . $type['name'] . ': ' . $type['text'] . '</option>');
                    }
                } catch(Throwable $e) {
                    header('Location: error.php');
                    return;
                }
                ?>
            </select></p>
            <p>Location: 
            <select name="deviceLocation">
                <?php
                try {
                    $stmt = $pdo->prepare('SELECT * FROM Location ORDER BY name ASC');
                    $stmt->execute();
                    $locations = $stmt->fetchAll();
                    foreach($locations as $location) {
                        echo('<option value="' . $location['id'] .'">' . $location['name'] . '</option>');
                    }
                } catch(Throwable $e) {
                    header('Location: error.php');
                    return;
                }
                ?>
            </select></p>
            <p>IP Address: <input type="text" name="deviceIp"/></p>
            <p>Add to Family: 
            <select name="family">
                <option value="" selected>N/A</option>
                <?php
                try {
                    $stmt = $pdo->prepare('SELECT family_id FROM FamilyMap WHERE user_id = :userId');
                    $stmt->execute(array(':userId' => $_SESSION['userId']));
                    $row = $stmt->fetchAll();
                    foreach($row as $family) {
                        $stmt = $pdo->prepare('SELECT name FROM Family WHERE id = :familyId');
                        $stmt->execute(array(':familyId' => $family['family_id']));
                        $familyName = $stmt->fetch(PDO::FETCH_ASSOC);
                        echo('<option value="' . $family['family_id'] . '">' . $familyName['name'] . '</option>');
                    }
                } catch(Throwable $e) {
                    header('Location: error.php');
                    return;
                }
                ?>
            </select></p>
            <p><input type="submit" name="newDevice" value="Add New Device" class="submitBtn"/></p>
        </form>
    </div>
    
    <div id="addExistingDevice">
        <h3>Existing Device</h3>
        <form method="post">
            <p>Device IP Address: <input type="text" name="deviceIp"/></p>
            <p>Add to Family: 
            <select name="family">
                <option value="" selected>N/A</option>
                <?php
                try {
                    $stmt = $pdo->prepare('SELECT family_id FROM FamilyMap WHERE user_id = :userId');
                    $stmt->execute(array(':userId' => $_SESSION['userId']));
                    $row = $stmt->fetchAll();
                    foreach($row as $family) {
                        $stmt = $pdo->prepare('SELECT name FROM Family WHERE id = :familyId');
                        $stmt->execute(array(':familyId' => $family['family_id']));
                        $familyName = $stmt->fetch(PDO::FETCH_ASSOC);
                        echo('<option value="' . $family['family_id'] . '">' . $familyName['name'] . '</option>');
                    }
                } catch(Throwable $e) {
                    header('Location: error.php');
                    return;
                }
                ?>
            </select></p>
            <p><input type="submit" name="existingDevice" value="Add to Existing Device" class="submitBtn"/></p>
        </form>
    </div>
</section>

<!-- Remove device -->
<?php printRemoveDevice(); ?>

<!-- Print table -->
<table>
    <tr>
        <th colspan="3">Device</th>
        <th><button onclick="toggleVisibility('add')">+</button></th>
    </tr>
    <tr>
        <th>Name</th>
        <th>Type</th>
        <th>Location</th>
        <th>Remove Device</th>
    </tr>
    <?php
        foreach($details as $deviceDetail) {
            //Get location name and type name
            try {
                $stmt = $pdo->prepare('SELECT name FROM Location WHERE id = :locationId');
                $stmt->execute(array(':locationId' => $deviceDetail['location']));
                $location = $stmt->fetch(PDO::FETCH_ASSOC);

                $stmt = $pdo->prepare('SELECT name FROM Type WHERE id = :typeId');
                $stmt->execute(array(':typeId' => $deviceDetail['type']));
                $type = $stmt->fetch(PDO::FETCH_ASSOC);

            } catch(Throwable $e) {
                header('Location: error.php');
                return;
            }

            echo('<tr>');
            echo('<td><a href="deviceDetail.php?deviceName=' . $deviceDetail['name'] . '&deviceIp=' . $deviceDetail['ip_address'] . '">' . $deviceDetail['name'] . '</a></td>');
            echo('<td>' . $type['name'] . '</td>');
            echo('<td>' . $location['name'] . '</td>');
            echo('<td><button onclick="remove(\'' . $deviceDetail['name'] . '\', \'' . $deviceDetail['id'] . '\', \'' . $deviceDetail['ip_address'] . '\')">-</button></td>');
            echo('</tr>');
        }
    ?>
</table>

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
<script src="js/main.js"></script>
</main>
</body>
</html>