<?php
require_once "pdo.php";
define('TIMEZONE', 'HongKong');
date_default_timezone_set(TIMEZONE);
session_start();

function accessDeny() {
    if(!isset($_SESSION['account'])) {
        die("ACCESS DENIED");
    }
}

function flashMessage() {
    if(isset($_SESSION['error'])) {
        echo('<p style="color: red">' . $_SESSION['error'] . '</p>');
        unset($_SESSION['error']);
    }
    if(isset($_SESSION['success'])) {
        echo('<p style="color: green">' . $_SESSION['success'] . '</p>');
        unset($_SESSION['success']);
    }
}

function checkDeviceIp() {
    if(!isset($_REQUEST['deviceIp'])) {
        $_SESSION['error'] = "Parameter missing.";
        return false;
    } else if(strlen($_REQUEST['deviceIp']) < 1) {
        $_SESSION['error'] = "Invalid parameter.";
        return false;
    }
    return true;
}

function checkDeviceName() {
    if(!isset($_REQUEST['deviceName'])) {
        $_SESSION['error'] = "Parameter missing.";
        return false;
    } else if(strlen($_REQUEST['deviceName']) < 1) {
        $_SESSION['error'] = "Invalid parameter.";
        return false;
    }
    return true;
}

function checkDeviceLocation() {
    if(!isset($_REQUEST['deviceLocation'])) {
        $_SESSION['error'] = "Parameter missing.";
        return false;
    } else if(strlen($_REQUEST['deviceLocation']) < 1) {
        $_SESSION['error'] = "Invalid parameter.";
        return false;
    }
    return true;
}

function checkDeviceType() {
    if(!isset($_REQUEST['deviceType'])) {
        $_SESSION['error'] = "Parameter missing.";
        return false;
    } else if(strlen($_REQUEST['deviceType']) < 1) {
        $_SESSION['error'] = "Invalid parameter.";
        return false;
    }
    return true;
}

function checkDeviceExist($pdo, $deviceIp, $deviceName) {
    $stmt = $pdo->prepare('SELECT * FROM Device WHERE ip_address = :deviceIp AND name = :deviceName');
    $stmt->execute(array(
        ':deviceIp' => $deviceIp,
        ':deviceName' => $deviceName
    ));
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if($row === false) {
        $_SESSION['error'] = "Device does not exist or you do not have permit to access. Please confirm your entries are correct. Email us if there are any problem.";
        return false;
    }

    return true;
}

function getDeviceId($pdo, $deviceIp, $deviceName) {
    $stmt = $pdo->prepare('SELECT * FROM Device WHERE ip_address = :deviceIp AND name = :deviceName');
    $stmt->execute(array(
        ':deviceIp' => $deviceIp,
        ':deviceName' => $deviceName
    ));
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row['id'];
}

function getUserFamily($pdo) {
    $stmt = $pdo->prepare('SELECT * FROM FamilyMap WHERE user_id = :userId');
    $stmt->execute(array(':userId' => $_SESSION['userId']));
    $families = $stmt->fetchAll();
    return $families;
}
?>