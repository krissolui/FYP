<?php
require_once "pdo.php";
session_start();

//Access deny when not login
if(!isset($_SESSION['account'])) {
    die("ACCESS DENIED");
}

//Add new family
if(isset($_POST['newFamily'])) {
    //Missing field
    if(strlen($_POST['familyName']) < 1) {
        $_SESSION['error'] = "Family name is required.";
        header('Location: family.php');
        return;
    } else {
        //Add to Family
        try {
            $stmt = $pdo->prepare('INSERT INTO Family (name, admin) values (:familyName, :admin)');
            $stmt->execute(array(
                ':familyName' => $_POST['familyName'],
                ':admin' => $_SESSION['userId']
            ));
            $familyId = $pdo->lastInsertId();
        } catch(Throwable $e) {
            header('Location: error.php');
            return;
        }
        
        //Add to FamilyMap
        try {
            $stmt = $pdo->prepare('INSERT INTO FamilyMap (user_id, family_id) values (:userId, :familyId)');
            $stmt->execute(array(
                ':userId' => $_SESSION['userId'],
                ':familyId' => $familyId
            ));
        } catch(Throwable $e) {
            header('Location: error.php');
            return;
        }

        $_SESSION['success'] = 'New family created.';
        header('Location: family.php');
        return;
    }
}

//Add to existing family
if(isset($_POST['existingFamily'])) {
    //Missing field
    if(strlen($_POST['familyId']) < 1) {
        $_SESSION['error'] = "Family ID is required.";
        header('Location: family.php');
        return;
    } else {
        //Check if exist
        try {
            $stmt = $pdo->prepare('SELECT id FROM Family WHERE id = :familyId');
            $stmt->execute(array(':familyId' => $_POST['familyId']));
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $familyId = $row['id'];
        } catch(Throwable $e) {
            header('Location: error.php');
            return;
        }
        if($row === false) {
            $_SESSION['error'] = "Family does not exist. Please create new family.";
            header('Location: family.php');
            return;
        } else {
            //Check if already connect
            try {
                $stmt = $pdo->prepare('SELECT * FROM FamilyMap WHERE family_id = :familyId AND user_id = :userId');
                $stmt->execute(array(
                    ':familyId' => $familyId,
                    ':userId' => $_SESSION['userId']
                ));
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
            } catch(Throwable $e) {
                header('Location: error.php');
                return;
            }
            if($row !== false) {
                $_SESSION['error'] = "You are already in the family.";
                header('Location: family.php');
                return;
            } else {
                //Add to FamilyMap
                try {
                    $stmt = $pdo->prepare('INSERT INTO FamilyMap (user_id, family_id) values (:userId, :familyId)');
                    $stmt->execute(array(
                        ':userId' => $_SESSION['userId'],
                        ':familyId' => $familyId
                    ));
                } catch(Throwable $e) {
                    header('Location: error.php');
                    return;
                }

                //Add family's device
                try {
                    $stmt = $pdo->prepare('SELECT DISTINCT device_id FROM DeviceMap WHERE family_id = :familyId');
                    $stmt->execute(array(':familyId' => $familyId));
                    $device = $stmt->fetchAll();

                    foreach($device as $dev) {
                        $stmt = $pdo->prepare('INSERT INTO DeviceMap (device_id, user_id, family_id) values (:deviceId, :userId, :familyId)');
                        $stmt->execute(array(
                            ':deviceId' => $dev['device_id'],
                            ':userId' => $_SESSION['userId'],
                            ':familyId' => $familyId
                        ));
                    }
                } catch(Throwable $e) {
                    header('Location: error.php');
                    return;
                }

                $_SESSION['success'] = 'Added to family.';
                header('Location: family.php');
                return;
            }
        }
    }
}

//Remove family
if(isset($_POST['removeFamily']) && isset($_POST['familyId'])) {
    //Check if user is the admin
    try {
        $stmt = $pdo->prepare('SELECT admin FROM Family WHERE id = :familyId');
        $stmt->execute(array(':familyId' => $_POST['familyId']));
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if($row['admin'] === $_SESSION['userId']) {
            //Ask if delete the family
            header('Location: deleteFamily.php?familyId=' . $_POST['familyId']);
            return;
        }
    } catch(Throwable $e) {
        header('Location: error.php');
        return;
    }

    try {
        //Remove from FamilyMap
        $stmt = $pdo->prepare('DELETE FROM FamilyMap WHERE user_id = :userId AND family_id = :familyId');
        $stmt->execute(array(
            ':userId' => $_SESSION['userId'],
            ':familyId' => $_POST['familyId']
        ));

        //Remove device connected through family
        $stmt = $pdo->prepare('DELETE FROM DeviceMap WHERE user_id = :userId AND family_id = :familyId');
        $stmt->execute(array(
            ':userId' => $_SESSION['userId'],
            ':familyId' => $_POST['familyId']
        ));

        $_SESSION['success'] = 'Removed from family.';
        header('Location: family.php');
        return;
    } catch(Throwable $e) {
        header('Location: error.php');
        return;
    }
}

//Search connected family
try {
    $stmt = $pdo->prepare('SELECT * FROM FamilyMap WHERE user_id = :userId');
    $stmt->execute(array(':userId' => $_SESSION['userId']));
    $family = $stmt->fetchAll();
} catch(Throwable $e) {
    header('Location: error.php');
    return;
}
?>

<html lang='en'>
<head>
<meta charset='UTF-8'>
<title><?=$_SESSION['account']?>'s Family</title>
</head>
<body>
<header>
    <h1><?=$_SESSION['account']?>'s Family</h1>
    <ul id="selectPage">
        <li><a href="index.php">Home</a></li>
        <li id="page"><a href="family.php">My Family</a></li>
        <li><a href="device.php">My Device</a></li>
        <li><a href="setting.php">Profile Setting</a></li>
        <li><a href="contact.php">Contact Us</a></li>
        <li><a href="logout.php">Log Out</a></li>
    </ul>
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

<!-- Add family -->
<section id="add" hidden>
    <p><button onclick="toggleVisibility('add')">Hide</button></p>
    <div id="addNewFamily">
        <h3>New Family</h3>
        <form method="post">
            <p>Family Name: <input type="text" name="familyName"/></p>
            <p><input type="submit" name="newFamily" value="Add New Family"/></p>
        </form>
    </div>
    
    <div id="addExistingFamily">
        <h3>Existing Family</h3>
        <form method="post">
            <p>Family ID: <input type="text" name="familyId"/></p>
            <p><input type="submit" name="existingFamily" value="Add to Existing Family"/></p>
        </form>
    </div>
</section>

<!-- Remove family -->
<section id="remove" hidden>
    <div id="removeFromFamily">
        <h3>Do you sure you want to remove from the family?</h3>
        <form method="post">
            <p id="showFamilyName"></p>
            <input type="hidden" name="familyName" value="" id="familyName"/>
            <input type="hidden" name="familyId" value="" id="familyId"/>
            <p><input type="submit" name="removeFamily" value="Yes"/>
            <button onclick="toggleVisibility('remove')">No</button></p>
        </form>
    </div>
</section>

<!-- Print table -->
<table>
    <tr>
        <th colspan="2">Family</th>
        <th><button onclick="toggleVisibility('add')">+</button></th>
    </tr>
    <tr>
        <th>Family Name</th>
        <th>Members</th>
        <th>Remove from Family</th>
    </tr>
    <?php
        foreach($family as $fam) {
            try {
                $stmt = $pdo->prepare('SELECT * FROM Family WHERE id = :familyId');
                $stmt->execute(array(':familyId' => $fam['family_id']));
                $familyDetail = $stmt->fetch(PDO::FETCH_ASSOC);
    
                $stmt = $pdo->prepare('SELECT user_id FROM FamilyMap WHERE family_id = :familyId');
                $stmt->execute(array(':familyId' => $fam['family_id']));
                $member = $stmt->fetchAll();
            } catch(Throwable $e) {
                header('Location: error.php');
                return;
            }

            echo('<tr>');
            echo('<td><a href="familyDetail.php?familyName=' . $familyDetail['name'] . '&familyId=' . $familyDetail['id'] . '">' . $familyDetail['name'] . '</a></td><td>');
            foreach($member as $mem) {
                try {
                    $stmt = $pdo->prepare('SELECT name FROM User WHERE id = :userId');
                    $stmt->execute(array(':userId' => $mem['user_id']));
                    $memberDetail = $stmt->fetch(PDO::FETCH_ASSOC);
                    echo($memberDetail['name'] . '</br>');
                } catch(Throwable $e) {
                    header('Location: error.php');
                    return;
                }
            }
            echo('</td><td><button onclick="remove(\'' . $familyDetail['name'] . '\', \'' . $familyDetail['id'] . '\')">-</button></td></tr>');
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

    function remove(name, id) {
        var remove = document.getElementById('remove');
        var familyName = document.getElementById('familyName');
        var familyId = document.getElementById('familyId');
        var display = document.getElementById('showFamilyName');
        
        familyName.setAttribute('value', name);
        familyId.setAttribute('value', id);
        display.textContent = "Family Name: ".concat(name);

        if(remove.hasAttribute("hidden")) {
            toggleVisibility('remove');
        }
    }
</script>
</main>
</body>
</html>