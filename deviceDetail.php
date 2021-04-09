<?php
require_once "pdo.php";
require_once "function.php";
define('TIMEZONE', 'HongKong');
date_default_timezone_set(TIMEZONE);
session_start();

//Access deny when not login
accessDeny();

//Check missing parameter
if(!checkParameter($_GET['deviceIp']) || !checkParameter($_GET['deviceName'])) {
    $_SESSION['error'] = "Missing parameter.";
    header('Location: device.php');
    return;
}

//Return to device.php if device not exist
try {
    $stmt = $pdo->prepare('SELECT * FROM Device WHERE ip_address = :deviceIp AND name = :deviceName');
    $stmt->execute(array(
        ':deviceIp' => $_GET['deviceIp'],
        ':deviceName' => $_GET['deviceName']
    ));
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if($row === false) {
        $_SESSION['error'] = "Device does not exist or you do not have permit to access. Please confirm your entries are correct. Email us if there are any problem.";
        header('Location: device.php');
        return;
    }
    $deviceId = $row['id'];
} catch(Throuwable $e) {
    header('Location: error.php');
    return;
}

//Access deny if not connect to the device
try {
    $stmt = $pdo->prepare('SELECT user_id FROM DeviceMap WHERE device_id = :deviceIp AND user_id = :userId');
    $stmt->execute(array(
        ':deviceIp' => $deviceId,
        ':userId' => $_SESSION['userId']
    ));
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if($row === false) {
        $_SESSION['error'] = "Device does not exist or you do not have permit to access. Please confirm your entries are correct. Email us if there are any problem.";
        header('Location: device.php');
        return;
    }
} catch(Throuwable $e) {
    header('Location: error.php');
    return;
}

//Add to other family
if(isset($_POST['addToFamily'])) {
    if(!checkParameter($_POST['familyId'])) {
        header('Location: deviceDetail.php?deviceName=' . $_GET['deviceName'] . '&deviceIp=' . $_GET['deviceIp']);
        return;
    } else {
        try {
            $stmt = $pdo->prepare('SELECT user_id FROM FamilyMap WHERE family_id = :familyId');
            $stmt->execute(array(':familyId' => $_POST['familyId']));
            $row = $stmt->fetchAll();
            // $row = getUserIdFromFamilyMap($pdo, $_POST['familyId']);

            foreach($row as $user) {
                addDeviceMapWithFamily($pdo, $deviceId, $user['user_id'], $_POST['familyId']);
            }
            $_SESSION['success'] = "Device added to new family.";
            header('Location: deviceDetail.php?deviceName=' . $_GET['deviceName'] . '&deviceIp=' . $_GET['deviceIp']);
            return;
        } catch(Throuwable $e) {
            header('Location: error.php');
            return;
        }
    }
}

//Check if connect directly
try {
    $stmt = $pdo->prepare('SELECT user_id FROM DeviceMap WHERE device_id = :deviceIp AND user_id = :userId AND family_id IS NULL');
    $stmt->execute(array(
        ':deviceIp' => $deviceId,
        ':userId' => $_SESSION['userId']
    ));
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if($row) {
        $connect = true;
        //List family
        $stmt = $pdo->prepare('SELECT * FROM FamilyMap WHERE user_id = :userId');
        $stmt->execute(array(':userId' => $_SESSION['userId']));
        $families = $stmt->fetchAll();

        $list = array();

        foreach($families as $family) {
            $stmt = $pdo->prepare('SELECT user_id FROM DeviceMap WHERE device_id = :deviceId AND user_id = :userId AND family_id = :familyId');
            $stmt->execute(array(
                ':deviceId' => $deviceId,
                ':userId' => $_SESSION['userId'],
                ':familyId' => $family['family_id']
            ));
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if(!$row) {
                array_push($list, $family['family_id']);
            }
        }
    } else {
        $connect = false;
    }
} catch(Throuwable $e) {
    header('Location: error.php');
    return;
}

//Get device detail
try {
    $stmt = $pdo->prepare('SELECT * FROM Device WHERE ip_address = :deviceIp AND name = :deviceName');
    $stmt->execute(array(
        ':deviceIp' => $_GET['deviceIp'],
        ':deviceName' => $_GET['deviceName']
    ));
    $detail = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare('SELECT name FROM Location WHERE id = :locationId');
    $stmt->execute(array(':locationId' => $detail['location']));
    $location = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare('SELECT name FROM Type WHERE id = :typeId');
    $stmt->execute(array(':typeId' => $detail['type']));
    $type = $stmt->fetch(PDO::FETCH_ASSOC);

} catch(Throwable $e) {
    header('Location: error.php');
    return;
}

//Prepare graph
if(checkParameter($_GET['displayTime'])) {
    $displayTime = $_GET['displayTime'];
} else {
    $displayTime = 'Day';
}

//$numOfRow: each 10 minutes
$now = time();
$numOfRow = 0;
$timeFormat = '';
$subtitle = '';
setDisplayTime($displayTime, $numOfRow, $timeFormat, $subtitle);
$start = $now - $numOfRow * 10 * 60;

//Get device record
try {
    //Get max
    $sql = 'SELECT current, voltage FROM Record WHERE device_id = :deviceId AND time >= DATE_SUB(CURRENT_TIMESTAMP(), INTERVAL 1 ' . $displayTime . ') ORDER BY current DESC LIMIT 1';
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array(':deviceId' => $detail['id']));
    $records = $stmt->fetch(PDO::FETCH_ASSOC);
    if($records) {
        $max = $records['current'] * $records['voltage'];
    } else {
        $max = 0;
    }

    // Get average
    $sql = 'SELECT * FROM Record WHERE device_id = :deviceId AND time >= DATE_SUB(CURRENT_TIMESTAMP(), INTERVAL 1 ' . $displayTime . ') ORDER BY time ASC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array(':deviceId' => $detail['id']));
    $records = $stmt->fetchAll();
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

    //Get full record
    $sql = 'SELECT * FROM Record WHERE device_id = :deviceId AND time >= DATE_SUB(CURRENT_TIMESTAMP(), INTERVAL 1 ' . $displayTime . ') ORDER BY time ASC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array(':deviceId' => $detail['id']));
    $records = $stmt->fetchAll();

    if($records) {    
        $firstDataTime = strtotime($records[0]["time"]);
        $lastDataTime = strtotime(end($records)["time"]);

        //Add new data as 0
        addNewData($now, $lastDataTime, $records);
        //Add old data as 0a($now, $lastDataTime, $records);
        addOldData($start, $firstDataTime, $records);
    } else {
        $records = array();
        addNewData($now, $start - (10 * 60), $records);
    }
} catch(Throwable $e) {
    header('Location: error.php');
    return;
}
?>

<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <title>Device Detail</title>
    <link rel="stylesheet" href="css/main.css">
    <script src="https://canvasjs.com/assets/script/canvasjs.min.js"></script>
    <script type="text/javascript">
        window.onload = function () {
        var dataPoints = [
            <?php
            //Get data points
            $count = 0;
            foreach($records as $record) {
                if($count === 0) {
                    $time = $record['time'];
                    $power = $record['current'] * $record['voltage'];
    
                    echo('{x: new Date("' . $time . '"), y: ' . $power . '},');
                }
                switch($displayTime) {
                    case 'Day':
                        $count = 0; //Display every 10 minutes
                        break;
                    case 'Week':
                        $count = ($count + 1) % 6; //Display every 1 hour
                        break;
                    case 'Month':
                        $count = ($count + 1) % (3 * 6); //Display every 3 hours
                        break;
                    case 'Year':
                        $count = ($count + 1) % (12 * 6); //Display every 12 hours
                        break;
                }
            }
            ?>
        ];

        var chart = new CanvasJS.Chart("chartContainer",
        {
            animationEnabled: true,
            theme: "light2",
            zoomEnabled: true,
            
            title:{
                text: "Power Consumption - <?=$displayTime?>"
            },
            subtitles:[{
                text: "<?=$subtitle?>"
            }],
            axisX:{
                title:"Time",
                valueFormatString: "<?=$timeFormat?>",
                labelAngle: -50
            },
            axisY:{
                title:"Power Consumption",
                valueFormatString: "#0W",
                minimum: 0,
            },
            data: [{
                type: "area",
                color: "rgba(0,75,141,0.7)",
                xValueFormatString: "<?=$timeFormat?>",
                yValueFormatString: "#0W",
		        dataPoints: dataPoints
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

<main class="deviceDetail-page">
    <section class="head-image">
        <h1 class="title">Smart Home - Power Consumption Monitoring System</h1>
        <h1 class="title subtitle">Device Detail</h1>
    </section>
    <?php flashMessage(); ?>
    <div class="returnBtn"><a href="device.php"><button>Return to My Device</button></a></div>

<section id="diagram">
<!-- Change display type (hour, day, month, year) -->
<?php printDisplayTimeMenu('Device', $displayTime, 'deviceDetail.php?deviceName=' . $_GET['deviceName'] . '&deviceIp=' . $_GET['deviceIp'] . '&'); ?>

<div id="chartContainer" style="height: 370px; width: 100%;" <?php if(!$records){echo('hidden');}?>></div>

<p><strong>Max. Power Consumption:</strong> <?=$max?>W&nbsp <strong>Avg. Power Consumption in Past <?=$displayTime?>:</strong> <?=number_format($avg, 2, '.', '')?>W</p>

</section>

<section id="detail">
    <p>Device Name: <?=$detail['name']?></p>
    <p>Device Type: <?=$type['name']?></p>
    <p>Device Location: <?=$location['name']?></p>
    <?php
        $stmt = $pdo->prepare('SELECT name FROM User WHERE id = :adminId');
        $stmt->execute(array(':adminId' => $detail['admin']));
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        echo('<p>Admin: ' . $row['name'] . '</p>');
    ?>

<section id="addToFamily" <?php if(!$connect || count($list) < 1){echo('hidden');}?>>
    <form method="post">
        <p>Add this device to other family: 
            <select name="familyId">
                <option value="" selected> N/A </option>
                <?php
                    foreach($list as $id) {
                        try {
                            $stmt = $pdo->prepare('SELECT name FROM Family WHERE id = :familyId');
                            $stmt->execute(array(':familyId' => $id));
                            $familyName = $stmt->fetch(PDO::FETCH_ASSOC);
                            echo('<option value="' . $id . '">' . $familyName['name'] . '</option>');
                        } catch(Throwable $e) {
                            header('Location: error.php');
                            return;
                        }
                    }
                ?>
            </select>
            <input type="submit" name="addToFamily" value="Submit" class="submitBtn"/>
        </p>
    </form>
</section>
</section>

</main>
<script src="js/main.js"></script>
</body>
</html>