<?php
require_once "pdo.php";
define('TIMEZONE', 'HongKong');
date_default_timezone_set(TIMEZONE);
session_start();

// Verify access permit
function accessDeny() {
    if(!isset($_SESSION['account'])) {
        die('ACCESS DENIED<p><a href="index.php">Return to main page.</a></p>');
    }
}

// Print error message or success message
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

// Generate hashed password
function hashPassword($pw) {
    $salt = 'ElecFypGroup64';
    $hash = hash('md5', $salt. $pw);
    return $hash;
}

// Check password length
function checkPasswordLength($pw) {
    if(strlen($pw) < 6 || strlen($pw) > 20) {
        $_SESSION['error'] = "Password length must be 6-20.";
        return false;
    }
    return true;
}

// Check password match
function checkPasswordMatch($pw1, $pw2) {
    if($pw1 !== $pw2) {
        $_SESSION['error'] = "Passwords do not match.";
        return false;
    }
    return true;
}

// Check parameter
function checkParameter($para) {
    if(!isset($para)) {
        return false;
    } else if(strlen($para) < 1) {
        return false;
    }
    return true;
}

// Check parameter: name
function checkName() {
    if(!isset($_REQUEST['name'])) {
        return false;
    } else if(strlen($_REQUEST['name']) < 1) {
        return false;
    }
    return true;
}

// Check parameter: email
function checkEmail() {
    if(!isset($_REQUEST['email'])) {
        return false;
    } else if(strlen($_REQUEST['email']) < 1) {
        return false;
    }
    return true;
}

// Check parameter: password
function checkPassword() {
    if(!isset($_REQUEST['password'])) {
        return false;
    } else if(strlen($_REQUEST['password']) < 1) {
        return false;
    }
    return true;
}

// Check user is admin
function checkAdmin($admin) {
    if($admin === $_SESSION['userId']) {
        return true;
    }
    return false;
}

// Insert into DeviceMap with family_id
function addDeviceMapWithFamily($pdo, $deviceId, $userId, $familyId) {
    $stmt = $pdo->prepare('INSERT INTO DeviceMap (device_id, user_id, family_id) values (:deviceId, :userId, :familyId)');
    $stmt->execute(array(
        ':deviceId' => $deviceId,
        ':userId' => $userId,
        ':familyId' => $familyId
    ));
    return;
}

// Insert into DeviceMap without family_id
function addDeviceMapDirectly($pdo, $deviceId, $userId) {
    $stmt = $pdo->prepare('INSERT INTO DeviceMap (device_id, user_id) values (:deviceId, :userId)');
    $stmt->execute(array(
        ':deviceId' => $deviceId,
        ':userId' => $userId
    ));
    return;
}

// Insert into Device
function addDevice($pdo, $name, $location, $type, $ip, $admin) {
    $stmt = $pdo->prepare('INSERT INTO Device (name, location, type, ip_address, admin) values (:name, :location, :type, :ip, :admin)');
    $stmt->execute(array(
        ':name' => $name,
        ':location' => $location,
        ':type' => $type,
        ':ip' => $ip,
        ':admin' => $admin
    ));
    return;
}

// Insert into Family
function addFamily($pdo, $name, $admin) {
    $stmt = $pdo->prepare('INSERT INTO Family (name, admin) values (:name, :admin)');
    $stmt->execute(array(
        ':name' => $name,
        ':admin' => $admin
    ));
    $familyId = $pdo->lastInsertId();
    return $familyId;
}

// Insert into FamilyMap
function addFamilyMap($pdo, $userId, $familyId) {
    $stmt = $pdo->prepare('INSERT INTO FamilyMap (user_id, family_id) values (:userId, :familyId)');
    $stmt->execute(array(
        ':userId' => $userId,
        ':familyId' => $familyId
    ));
    return;
}

// Delete from FamilyMap
function deleteFamilyMap($pdo, $userId, $familyId) {
    $stmt = $pdo->prepare('DELETE FROM FamilyMap WHERE user_id = :userId AND family_id = :familyId');
    $stmt->execute(array(
        ':userId' => $userId,
        ':familyId' => $familyId
    ));
    return;
}

// Delete from Family
function deleteFamily($pdo, $familyId, $admin) {
    $stmt = $pdo->prepare('DELETE FROM Family WHERE id = :familyId AND admin = :admin');    
    $stmt->execute(array(
        ':familyId' => $familyId,
        ':admin' => $admin
    ));
    return;
}

// Delete from DeviceMap connect through family
function deleteDeviceMapThroughFamily($pdo, $userId, $familyId) {
    $stmt = $pdo->prepare('DELETE FROM DeviceMap WHERE user_id = :userId AND family_id = :familyId');
    $stmt->execute(array(
        ':userId' => $userId,
        ':familyId' => $familyId
    ));
    return;
}

// Delete from DeviceMap connect directly
function deleteDeviceMap($pdo, $userId, $deviceId) {
    $stmt = $pdo->prepare('DELETE FROM DeviceMap WHERE user_id = :userId AND device_id = :deviceId AND family_id IS NULL');
    $stmt->execute(array(
        ':userId' => $userId,
        ':deviceId' => $deviceId,
    ));
    return;
}

// Delete from DeviceMap for family
function deleteFamilyDeviceMap($pdo, $familyId, $deviceId) {
    $stmt = $pdo->prepare('DELETE FROM DeviceMap WHERE family_id = :familyId AND device_id = :deviceId');
    $stmt->execute(array(
        ':familyId' => $familyId,
        ':deviceId' => $deviceId,
    ));
    return;
}

// Delete from Device
function deleteDevice($pdo, $deviceId, $deviceIp, $userId) {
    $stmt = $pdo->prepare('DELETE FROM Device WHERE id = :deviceId AND ip_address = :deviceIp AND admin = :userId');
    $stmt->execute(array(
        ':deviceId' => $deviceId,
        ':deviceIp' => $deviceIp,
        ':userId' => $userId
    ));
    return;
}

// Update User password
function updateUserPassword($pdo, $newPw, $id, $email) {
    $stmt = $pdo->prepare('UPDATE User SET password = :newPw WHERE id = :id AND email = :email');
    $stmt->execute(array(
        ':newPw' => $newPw,
        ':id' => $id,
        ':email' => $email
    ));
    return;
}

// Update User profile
function updateUserProfile($pdo, $newEmail, $newName, $id, $oldEmail, $oldName) {
    $stmt = $pdo->prepare('UPDATE User SET email = :newEmail, name = :newName WHERE id = :id AND email = :email AND name = :name');
    $stmt->execute(array(
        ':newEmail' => $newEmail,
        ':newName' => $newName,
        ':id' => $id,
        ':email' => $oldEmail,
        ':name' => $oldName
    ));
    return;
}

// Update Family admin
function updateFamilyAdmin($pdo, $admin, $familyId, $userId) {
    $stmt = $pdo->prepare('UPDATE Family SET admin = :admin WHERE id = :familyId AND admin = :userId');
    $stmt->execute(array(
        ':admin' => $admin,
        ':familyId' => $familyId,
        ':userId' => $userId
    ));
    return;
}

// Update Device admin
function updateDeviceAdmin($pdo, $admin, $deviceId, $deviceIp, $userId) {
    $stmt = $pdo->prepare('UPDATE Device SET admin = :admin WHERE id = :deviceId AND ip_address = :deviceIp AND admin = :userId');
    $stmt->execute(array(
        ':admin' => $admin,
        ':deviceId' => $deviceId,
        ':deviceIp' => $deviceIp,
        ':userId' => $userId
    ));
    return;
}

// Set displayTime
function setDisplayTime($displayTime, &$numOfRow, &$timeFormat, &$subtitle = null) {
    switch($displayTime) {
        case 'Day':
            $numOfRow = 24 * 6;
            $timeFormat = "hh:mm TT";
            $subtitle = "Display every 10 minutes";
            return;
        case 'Week':
            $numOfRow = 7 * 24 * 6;
            $timeFormat = "D MMM hh TT";
            $subtitle = "Display every 1 hour";
            return;
        case 'Month':
            $numOfRow = 30 * 24 * 6;
            $timeFormat = "D MMM hh TT";
            $subtitle = "Display every 3 hours";
            return;
        case 'Year':
            $numOfRow = 365 * 24 * 6;
            $timeFormat = "D MMM YYYY hh TT";
            $subtitle = "Display every 12 hours";
            return;
    }
    return;
}

// Set pie chart dataPoints
function setPieDataPoints($displayType, $devices, $details, $labels, $avgs, &$total, &$dataPoints) {
    switch($displayType) {
        case 'Device':
            foreach($devices as $device) {
                $deviceId = $device['device_id'];
                array_push($dataPoints, array(
                    "label" => $labels[$deviceId],
                    "y" => $avgs[$deviceId]
                ));
                $total += $avgs[$deviceId];
            }
            return;
        case 'Type':
            foreach($labels as $label) {
                $typeId = array_keys($labels, $label)[0];
                $sum = 0;
    
                foreach($details as $detail) {
                    if($detail['type'] == $typeId) {
                        $sum += $avgs[$detail['id']];
                    }
                }
                
                array_push($dataPoints, array(
                    "label" => $label,
                    "y" => $sum
                ));
                $total += $sum;
            }
            return;
        case 'Location':
            foreach($labels as $label) {
                $locationId = array_keys($labels, $label)[0];
                $sum = 0;
    
                foreach($details as $detail) {
                    if($detail['location'] == $locationId) {
                        $sum += $avgs[$detail['id']];
                    }
                }
                
                array_push($dataPoints, array(
                    "label" => $label,
                    "y" => $sum
                ));
                $total += $sum;
            }
            return;
    }
    return;
}

// Print main title bar
function printTitleBar($page = null) {
    echo('<ul id="selectPage">');
    switch ($page) {
        case 'home':
            echo('
            <li id="page"><a href="index.php">Home</a></li>
            <li><a href="family.php">My Family</a></li>
            <li><a href="device.php">My Device</a></li>
            <li><a href="setting.php">Profile Setting</a></li>
            <li><a href="contact.php">Contact Us</a></li>
            <li><a href="logout.php">Log Out</a></li>
            ');
            break;
        case 'family':
            echo('
            <li><a href="index.php">Home</a></li>
            <li id="page"><a href="family.php">My Family</a></li>
            <li><a href="device.php">My Device</a></li>
            <li><a href="setting.php">Profile Setting</a></li>
            <li><a href="contact.php">Contact Us</a></li>
            <li><a href="logout.php">Log Out</a></li>
            ');
            break;
        case 'device':
            echo('
            <li><a href="index.php">Home</a></li>
            <li><a href="family.php">My Family</a></li>
            <li id="page"><a href="device.php">My Device</a></li>
            <li><a href="setting.php">Profile Setting</a></li>
            <li><a href="contact.php">Contact Us</a></li>
            <li><a href="logout.php">Log Out</a></li>
            ');
            break;
        case 'setting':
            echo('
            <li><a href="index.php">Home</a></li>
            <li><a href="family.php">My Family</a></li>
            <li><a href="device.php">My Device</a></li>
            <li id="page"><a href="setting.php">Profile Setting</a></li>
            <li><a href="contact.php">Contact Us</a></li>
            <li><a href="logout.php">Log Out</a></li>
            ');
            break;
        case 'contact':
            echo('
            <li><a href="index.php">Home</a></li>
            <li><a href="family.php">My Family</a></li>
            <li><a href="device.php">My Device</a></li>
            <li><a href="setting.php">Profile Setting</a></li>
            <li id="page"><a href="contact.php">Contact Us</a></li>
            <li><a href="logout.php">Log Out</a></li>
            ');
            break;
        case 'logout':
            echo('
            <li><a href="index.php">Home</a></li>
            <li><a href="family.php">My Family</a></li>
            <li><a href="device.php">My Device</a></li>
            <li><a href="setting.php">Profile Setting</a></li>
            <li><a href="contact.php">Contact Us</a></li>
            <li id="page"><a href="logout.php">Log Out</a></li>
            ');
            break;
        case 'unlogHome':
            echo('
            <li id="page"><a href="index.php">Home</a></li>
            <li><a href="contact.php">Contact Us</a></li>
            <li><a href="login.php">Login</a></li>
            <li><a href="signup.php">Sign Up</a></li>
            ');
            break;
        case 'unlogContact':
            echo('
            <li><a href="index.php">Home</a></li>
            <li id="page"><a href="contact.php">Contact Us</a></li>
            <li><a href="login.php">Login</a></li>
            <li><a href="signup.php">Sign Up</a></li>
            ');
            break;
        case 'login':
            echo('
            <li><a href="index.php">Home</a></li>
            <li><a href="contact.php">Contact Us</a></li>
            <li id="page"><a href="login.php">Login</a></li>
            <li><a href="signup.php">Sign Up</a></li>
            ');
            break;
        case 'signup':
            echo('
            <li><a href="index.php">Home</a></li>
            <li><a href="contact.php">Contact Us</a></li>
            <li><a href="login.php">Login</a></li>
            <li id="page"><a href="signup.php">Sign Up</a></li>
            ');
            break;
        default:
            echo('
            <li><a href="index.php">Home</a></li>
            <li><a href="family.php">My Family</a></li>
            <li><a href="device.php">My Device</a></li>
            <li><a href="setting.php">Profile Setting</a></li>
            <li><a href="contact.php">Contact Us</a></li>
            <li><a href="logout.php">Log Out</a></li>
            ');
            break;
    }
    echo('</ul>');
}

// Print remove device
function printRemoveDevice($family = false) {
    echo('
        <section id="remove" hidden>
        <div id="removeDevice">
        <h3>Are you sure you want to remove this device?</h3>
        ');
    if($family) {
        echo('<p style="color: orange">*Family members will lost access to this device through family.</p>');
    }
    echo('
        <form method="post">
        <p id="showDeviceName"></p>
        <input type="hidden" name="deviceName" value="" id="deviceName"/>
        <input type="hidden" name="deviceId" value="" id="deviceId"/>
        <input type="hidden" name="deviceIp" value="" id="deviceIp"/>
        <p><input type="submit" name="removeDevice" value="Yes"/>
        <button onclick="toggleVisibility("remove")">No</button></p>
        </form>
        </div>
        </section>
        ');
    return;
}

// Print select displayType bar
function printDisplayTypeBar($displayTime, $page) {
    echo('
    <ul id="selectDisplayType">
    <li id="Device"><a href="' . $page . 'displayType=Device&displayTime=' . $displayTime . '">Device</a></li>
    <li id="Type"><a href="' . $page . 'displayType=Type&displayTime=' . $displayTime . '">Type</a></li>
    <li id="Location"><a href="' . $page . 'displayType=Location&displayTime=' . $displayTime . '">Location</a></li>
    </ul>
    ');
    return;
}

// Print select displayTime bar
function printDisplayTimeBar($displayType, $page) {
    echo('
    <ul id="selectDisplayTime">
    <li id="Day"><a href="' . $page . 'displayType=' . $displayType . '&displayTime=Day">Day</a></li>
    <li id="Week"><a href="' . $page . 'displayType=' . $displayType . '&displayTime=Week">Week</a></li>
    <li id="Month"><a href="' . $page . 'displayType=' . $displayType . '&displayTime=Month">Month</a></li>
    <li id="Year"><a href="' . $page . 'displayType=' . $displayType . '&displayTime=Year">Year</a></li>
    </ul>
    ');
    return;
}

// Add data in the past = 0
function addOldData($start, $firstDataTime, &$records) {
    while($start < ($firstDataTime - (10 * 60))) {
        array_unshift($records, array(
            "device_id" => $records[0]["device_id"],
            "time" => date('Y-m-d H:i:s',$firstDataTime - (10 * 60)),
            "current" => 0,
            "voltage" => 0
        ));
        $firstDataTime = strtotime($records[0]["time"]);
    }
    return;
}

// Add data to present = 0
function addNewData($now, $lastDataTime, &$records) {
    while($now > ($lastDataTime + (10 * 60))) {
        array_push($records, array(
            "device_id" => $records[0]["device_id"],
            "time" => date('Y-m-d H:i:s',$lastDataTime + (10 * 60)),
            "current" => 0,
            "voltage" => 0
        ));
        $lastDataTime = strtotime(end($records)["time"]);
    }
    return;
}
?>