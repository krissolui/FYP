<?php
require_once "pdo.php";
session_start();

//Access deny when not login
if(!isset($_SESSION['account'])) {
    die("ACCESS DENIED");
}

//Get user detail
try {
    $stmt = $pdo->prepare('SELECT * FROM User WHERE id = :userId');
    $stmt->execute(array(':userId' => $_SESSION['userId']));
    $detail = $stmt->fetch(PDO::FETCH_ASSOC);
} catch(Throwable $e) {
    header('Location: error.php');
    return;
}

//Edit profile
if(isset($_POST['edit'])) {
    if(strlen($_POST['name']) < 1 || strlen($_POST['email']) < 1) {
        $_SESSION['error'] = "Name and email are required";
        header('Location: setting.php');
        return;
    } else {
        //Check if new email already exist
        try {
            $stmt = $pdo->prepare('SELECT email FROM User WHERE email = :email');
            $stmt->execute(array(':email' => $_SESSION['email']));
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
            if($row) {
                $_SESSION['error'] = "This email is connected to another account.";
                header('Location: setting.php');
                return;
            } else {
                $stmt = $pdo->prepare('UPDATE User SET email = :newEmail, name = :newName WHERE id = :userId AND name = :name AND email = :email');
                $stmt->execute(array(
                    ':newEmail' => $_POST['email'],
                    ':newName' => $_POST['name'],
                    ':userId' => $_SESSION['userId'],
                    ':name' => $detail['name'],
                    ':email' => $detail['email']
                ));
    
                $_SESSION['account'] = $_POST['name'];
                $_SESSION['success'] = "Profile updated.";
                header('Location: setting.php');
                return;
            }
        } catch(Throwable $e) {
            header('Location: error.php');
            return;
        }
    }
}

//Change password
if(isset($_POST['changePassword'])) {
    $salt = 'ElecFypGroup64';
    $oldPw = hash('md5', $salt. $_POST['oldPassword']);
    $newPw = hash('md5', $salt. $_POST['newPassword']);

    $_SESSION['success'] = 'OLD:' . $oldPw . '; NEW:' . $newPw . '; Saved:' . $detail['password'];
    
    if(strlen($_POST['oldPassword']) < 1) {
        //Check old password entered
        $_SESSION['error'] = "Please enter you password for verification.";
        header('Location: setting.php');
        return;
    } else if(strlen($_POST['newPassword']) < 6 || strlen($_POST['newPassword']) > 20) {
        //Check password length
        $_SESSION['error'] = "Password length must be 6-20.";
        header('Location: setting.php');
        return;
    } else if($_POST['newPassword'] !== $_POST['newPassword2']) {
        //Check if password match
        $_SESSION['error'] = "Passwords do not match.";
        header('Location: setting.php');
        return;
    } else {
        //Check old password correct
        try {
            if($oldPw !== $detail['password']) {
                //Password incorrect
                $_SESSION['error'] = 'Incorrect password.';
                header('Location: setting.php');
                return;
            } else if($newPw === $detail['password']) {
                //New password = Old password
                $_SESSION['error'] = "Please choose a new password.";
                header('Location: setting.php');
                return;
            } else {
                //Update password
                $stmt = $pdo->prepare('UPDATE User SET password = :newPw WHERE id = :userId AND email = :email');
                $stmt->execute(array(
                    ':newPw' => $newPw,
                    ':userId' => $_SESSION['userId'],
                    ':email' => $detail['email']
                ));

                $_SESSION['success'] = "Password updated.";
                header('Location: setting.php');
                return;
            }
        } catch(Throwable $e) {
            header('Location: error.php');
            return;
        }
    }
}

?>

<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <title>My Profile</title>
</head>
<body>
<header>
    <h1>My Profile</h1>
    <ul id="selectPage">
        <li><a href="index.php">Home</a></li>
        <li><a href="family.php">My Family</a></li>
        <li><a href="device.php">My Device</a></li>
        <li id="page"><a href="setting.php">Profile Setting</a></li>
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

<section id="current setting">
    <p>Name: <?=$detail['name']?></p>
    <p>Email: <?=$detail['email']?></p>
    <p><button onclick="toggleVisibility('edit', false)">Edit</button>
    <button onclick="toggleVisibility('changePassword', false)">Change My Password</button></p>
</section>

<section id="edit" hidden>
    <p><button onclick="toggleVisibility('edit', true)">Hide</button></p>
    <form method="post">
        <p>Name: <input type="text" name="name" value="<?=$detail['name']?>"/></p>
        <p>Email: <input type="email" name="email" value="<?=$detail['email']?>"/></p>
        <p><input type="submit" name="edit" value="Submit"/></p>
    </form>
</section>

<section id="changePassword" hidden>
<p><button onclick="toggleVisibility('changePassword', true)">Hide</button></p>
    <form method="post">
        <p>Current Password: <input type="password" name="oldPassword" required/></p>
        <p>New Password: <input type="password" name="newPassword"/>
        <span style="color: red">^6-20 characters</span></p>
        <p>Confirm New Password: <input type="password" name="newPassword2"/></p>
        <p><input type="submit" name="changePassword" value="Submit"/></p>
    </form>
</section>

<script>
    function toggleVisibility(id, reverse) {
        var element = document.getElementById(id);
        var edit = document.getElementById('edit');
        var changePw = document.getElementById('changePassword');

        if(element.hasAttribute("hidden")) {
            element.removeAttribute("hidden");
        } else if(reverse) {
            element.setAttribute("hidden", "true");
        }

        if(element === edit) {
            if(!changePw.hasAttribute("hidden")) {
                changePw.setAttribute("hidden", true);
            }
        } else {
            if(!edit.hasAttribute("hidden")) {
                edit.setAttribute("hidden", true);
            }
        }
    }
</script>
</main>
</body>
</html>