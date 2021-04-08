<?php
require_once "pdo.php";
define('TIMEZONE', 'HongKong');
date_default_timezone_set(TIMEZONE);

session_start();


if(isset($_POST['add'])) {
	if(strlen($_POST['max']) < 1) {
		$max = 12;
	} else { 
		$max = intval($_POST['max']); 
	}
	if(strlen($_POST['min']) < 1) {
		$min = 8;
	} else { 
		$min = intval($_POST['min']); 
	}
	if(strlen($_POST['voltage']) < 1) {
		$voltage = 50;
	} else { 
		$voltage = intval($_POST['voltage']); 
	}

	$_SESSION['error'] = 'Max:' . $max . ' Min:' . $min . ' Voltage:' . $voltage;

	if(isset($_POST['deviceId']) && strlen($_POST['deviceId'])) {
		try {
			$stmt = $pdo->prepare('SELECT id FROM Device WHERE id = :id');
			$stmt->execute(array(':id' => $_POST['deviceId']));
			$row = $stmt->fetch(PDO::FETCH_ASSOC);
			
			if($row) {
				$time = time();
				$num = 365 * 24 * 6; //Record every 10 minutes
				$diff = 60 * 10;

				for($i = 0; $i < $num; $i++) {
					$rand = rand($min, $max);
					$timeStamp = date('Y-m-d H:i:s', $time);

					$stmt = $pdo->prepare('INSERT INTO Record (device_id, time, current, voltage) values (:deviceId, :time, :current, :voltage)');
                	$stmt->execute(array(
                    ':deviceId' => $row['id'],
                    ':time' => $timeStamp,
                    ':current' => $rand,
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

<form method="post">
<p>Device ID: <input type="text" name="deviceId"/></p>
<p>Max. <input type="text" name="max"/> &nbsp Min. <input type="text" name="min"/></p>
<p>Voltage: <input type="text" name="voltage"/></p>
<p>Add 1 year of data to this device?</p>
<p><input type="submit" name="add" value="Yes"/></p>
</form>

<form method="post">
<p>Device ID: <input type="text" name="deviceId"/></p>
<p>Delete all data of this device?</p>
<p><input type="submit" name="delete" value="Yes"/></p>
</form>
</body>
</html>