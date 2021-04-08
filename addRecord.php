<?php
require_once "pdo.php";
define('TIMEZONE', 'HongKong');
date_default_timezone_set(TIMEZONE);
session_start();

if(isset($_POST['ip']) &&  isset($_POST['current']) && isset($_POST['voltage'])) {
    //Get device ID
    try {
        $stmt = $pdo->prepare('SELECT id FROM Device WHERE ip_address = :ip');
        $stmt->execute(array(':ip' => $_POST['ip']));
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        //Add record
        if($row) {
            if(isset($_POST['time']) && strlen($_POST['time']) > 0) {
                $stmt = $pdo->prepare('INSERT INTO Record (device_id, time, current, voltage) values (:deviceId, :time, :current, :voltage');
                $stmt->execute(array(
                    ':deviceId' => $row['id'],
                    ':time' => $_POST['time'],
                    ':current' => $_POST['current'],
                    ':voltage' => $_POST['voltage']
                ));
            } else {
                $stmt = $pdo->prepare('INSERT INTO Record (device_id, current, voltage) values (:deviceId, :current, :voltage');
                $stmt->execute(array(
                    ':deviceId' => $row['id'],
                    ':current' => $_POST['current'],
                    ':voltage' => $_POST['voltage']
                ));
            }
        }
    } catch(Throwable $e) {
        return;
    }
}
    

?>

