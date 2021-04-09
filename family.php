<?php
require_once "pdo.php";
require_once "function.php";
session_start();

//Access deny when not login
accessDeny();

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
            $familyId = addFamily($pdo, $_POST['familyName'], $_SESSION['userId']);
        } catch(Throwable $e) {
            header('Location: error.php');
            return;
        }
        
        //Add to FamilyMap
        try {
            addFamilyMap($pdo, $_SESSION['userId'], $familyId);
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
                    addFamilyMap($pdo, $_SESSION['userId'], $familyId);
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
                        addDeviceMapWithFamily($pdo, $dev['device_id'], $_SESSION['userId'], $familyId);
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
        if(checkAdmin($row['admin'])) {
            //Ask if delete the family
            header('Location: deleteFamily.php?familyId=' . $_POST['familyId']);
            return;
        }
    } catch(Throwable $e) {
        header('Location: error.php');
        return;
    }

    try {
        deleteFamilyMap($pdo, $_SESSION['userId'], $_POST['familyId']);

        deleteDeviceMapThroughFamily($pdo, $_SESSION['userId'], $_POST['familyId']);

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
    $families = $stmt->fetchAll();
} catch(Throwable $e) {
    header('Location: error.php');
    return;
}
?>

<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <title><?=$_SESSION['account']?>'s Family</title>
    <link rel="stylesheet" href="css/main.css">
</head>
<body>
<header>
    <div class="menu-btn">
      <span class="menu-btn__burger"></span>
    </div>
    <?php printMainMenu('family'); ?>
</header>

<main class="family-page">
    <section class="head-image">
        <h1 class="title">Smart Home - Power Consumption Monitoring System</h1>
        <h1 class="title subtitle"><?=$_SESSION['account']?>'s Family</h1>
    </section>
<?php flashMessage() ?>

<!-- Add family -->
<section id="add" hidden>
    <p><button onclick="toggleVisibility('add')">Hide</button></p>
    <div id="addNewFamily">
        <h3>New Family</h3>
        <form method="post">
            <p>Family Name: <input type="text" name="familyName"/></p>
            <p><input type="submit" name="newFamily" value="Add New Family" class="submitBtn"/></p>
        </form>
    </div>
    
    <div id="addExistingFamily">
        <h3>Existing Family</h3>
        <form method="post">
            <p>Family ID: <input type="text" name="familyId"/></p>
            <p><input type="submit" name="existingFamily" value="Add to Existing Family" class="submitBtn"/></p>
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
            <p id="removeBtns"><input type="submit" name="removeFamily" value="Yes" class="submitBtn"/>
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
        foreach($families as $family) {
            try {
                $stmt = $pdo->prepare('SELECT * FROM Family WHERE id = :familyId');
                $stmt->execute(array(':familyId' => $family['family_id']));
                $familyDetail = $stmt->fetch(PDO::FETCH_ASSOC);
    
                $stmt = $pdo->prepare('SELECT user_id FROM FamilyMap WHERE family_id = :familyId');
                $stmt->execute(array(':familyId' => $family['family_id']));
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
<script src="js/main.js"></script>
</body>
</html>