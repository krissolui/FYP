<?php
require_once "pdo.php";
require_once "function.php";
session_start();

// Access deny when not login
accessDeny();

// Check missing parameter
if(!checkParameter($_GET['familyId']) || !checkParameter($_GET['familyName'])) {
    $_SESSION['error'] = "Missing parameter.";
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
    $familyDetail = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get admin name
    $stmt = $pdo->prepare('SELECT name FROM User WHERE id = :adminId');
    $stmt->execute(array(':adminId' => $familyDetail['admin']));
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    $familyDetail['adminName'] = $admin['name'];
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

//Get family device detail
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
    <title>Family Detail</title>
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
    <h1>Family Detail</h1>
    <?php printTitleBar('family'); ?>
</header>

<main>
<?php flashMessage(); ?>
<!-- Remove device -->
<?php printRemoveDevice(true); ?>

<!-- Print graph -->
<section id="diagram">
<!-- Select display type -->
<?php printDisplayTypeBar($displayTime, 'familyDetail.php?familyId=' . $_GET['familyId'] . '&familyName=' . $_GET['familyName'] . '&'); ?>

<!-- Select display time -->
<?php printDisplayTimeBar($displayType, 'familyDetail.php?familyId=' . $_GET['familyId'] . '&familyName=' . $_GET['familyName'] . '&'); ?>

<div id="chartContainer" style="height: 370px; width: 100%;" <?php if($total == 0) {echo('hidden');} ?>></div>
<p><strong>Average Power Consumption in Past <?=$displayTime?>:</strong> <?=number_format($total, 2, '.', '')?>W</p>
</section>

<!-- Display family detail -->
<section id="detail">
    <p>Family Name: <?=$familyDetail['name']?></p>
    <p>Family ID: <?=$familyDetail['id']?></p>
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
                if(checkAdmin($familyDetail['admin'])) {
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
            if(checkAdmin($familyDetail['admin'])) {
                echo('<td><button onclick="remove(\'' . $deviceDetail['name'] . '\', \'' . $deviceDetail['id'] . '\', \'' . $deviceDetail['ip_address'] . '\')">-</button></td>');
            }
            echo('</tr>');
        }
    ?>
    </table>
    </p>
    <p>Admin: <?=$familyDetail['adminName']?></p>
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