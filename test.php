<?php
require_once "pdo.php";
require_once "function.php";
define('TIMEZONE', 'HongKong');
date_default_timezone_set(TIMEZONE);

session_start();


if(isset($_POST['add'])) {
	if(strlen($_POST['maxCurrent']) < 1) {
		$maxCurrent = 12;
	} else { 
		$maxCurrent = intval($_POST['maxCurrent']); 
	}
	if(strlen($_POST['minCurrent']) < 1) {
		$minCurrent = 0;
	} else { 
		$minCurrent = intval($_POST['minCurrent']); 
	}
	if(strlen($_POST['maxVoltage']) < 1) {
		$maxVoltage = 24;
	} else { 
		$maxVoltage = intval($_POST['maxVoltage']); 
	}
	if(strlen($_POST['minVoltage']) < 1) {
		$minVoltage = 5;
	} else { 
		$minVoltage = intval($_POST['minVoltage']); 
	}

	$_SESSION['error'] = 'Max Current:' . $maxCurrent . ' Min Current:' . $minCurrent . ' Max Voltage:' . $maxVoltage . ' Min Voltage:' . $minVoltage;

	if(isset($_POST['deviceId']) && strlen($_POST['deviceId'])) {
		try {
			$stmt = $pdo->prepare('SELECT id FROM Device WHERE id = :id');
			$stmt->execute(array(':id' => $_POST['deviceId']));
			$row = $stmt->fetch(PDO::FETCH_ASSOC);
			
			if($row) {
				$time = time();
				$num = 30 * 24 * 6; //Record every 10 minutes
				$diff = 60 * 10;

				for($i = 0; $i < $num; $i++) {
					$current = rand($minCurrent, $maxCurrent);
					$voltage = rand($minVoltage, $maxVoltage);
					$timeStamp = date('Y-m-d H:i:s', $time);

					$stmt = $pdo->prepare('INSERT INTO Record (device_id, time, current, voltage) values (:deviceId, :time, :current, :voltage)');
                	$stmt->execute(array(
                    ':deviceId' => $row['id'],
                    ':time' => $timeStamp,
                    ':current' => $current,
					':voltage' => $voltage
					));

					$time -= $diff;
				}
				$_SESSION['success'] = "Data added.";
				header('Location: test.php');
				return;
			}
		} catch(Throwable $e) {
			header('Location: error.php');
			return;
		}     
	}

}

if(isset($_POST['delete'])) {
	if(isset($_POST['deviceId']) && strlen($_POST['deviceId'])) {
		try {
			$stmt = $pdo->prepare('SELECT id FROM Device WHERE id = :id');
			$stmt->execute(array(':id' => $_POST['deviceId']));
			$row = $stmt->fetch(PDO::FETCH_ASSOC);

			if($row) {
				$stmt = $pdo->prepare('DELETE FROM Record WHERE device_id = :deviceId');
				$stmt->execute(array(':deviceId' => $_POST['deviceId']));

				$_SESSION['success'] = "Data deleted.";
				header('Location: test.php');
				return;
			}
		} catch(Throwable $e) {
			header('Location: error.php');
			return;
		}     
	}

}
?>


<!DOCTYPE HTML>
<html>
<head>

</head>
<body>
<?php flashMessage(); ?>

<form method="post">
<p>Device ID: <input type="text" name="deviceId"/></p>
<p>Max. Current <input type="text" name="maxCurrent"/> &nbsp Min. Current <input type="text" name="minCurrent"/></p>
<p>Max. Voltage: <input type="text" name="maxVoltage"/> &nbsp Min. Voltage <input type="text" name="minVoltage"/></p>
<p>Add 1 month of data to this device?</p>
<p><input type="submit" name="add" value="Yes"/></p>
</form>

<form method="post">
<p>Device ID: <input type="text" name="deviceId"/></p>
<p>Delete all data of this device?</p>
<p><input type="submit" name="delete" value="Yes"/></p>
</form>
</body>
</html>