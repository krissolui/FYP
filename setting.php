<?php
require_once "pdo.php";
require_once "function.php";
session_start();

//Access deny when not login
accessDeny();

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
    if(!checkName() || !checkEmail()) {
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
                updateUserProfile($pdo, $_POST['email'], $_POST['name'], $_SESSION['userId'], $detail['email'], $detail['name']);
    
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
    // $salt = 'ElecFypGroup64';
    $oldPw = hashPassword($_POST['oldPassword']);
    $newPw = hashPassword($_POST['newPassword']);
    
    if(!checkPasswordLength($_POST['oldPassword'])) {
        //Check old password entered
        $_SESSION['error'] = "Please enter you password for verification.";
        header('Location: setting.php');
        return;
    } else if(!checkPasswordLength($_POST['newPassword'])) {
        header('Location: setting.php');
        return;
    } else if(!checkPasswordMatch($_POST['newPassword'], $_POST['newPassword2'])) {
        header('Location: setting.php');
        return;
    } else {
        //Check old password correct
        try {
            if(!checkPasswordMatch($oldPw, $detail['password'])) {
                //Password incorrect
                $_SESSION['error'] = 'Incorrect password.';
                header('Location: setting.php');
                return;
            } else if(checkPasswordMatch($newPw, $detail['password'])) {
                //New password = Old password
                $_SESSION['error'] = "Please choose a new password.";
                header('Location: setting.php');
                return;
            } else {
                updateUserPassword($pdo, $newPw, $_SESSION['userId'], $detail['email']);

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
    <?php printTitleBar('setting'); ?>
</header>

<main> 
<?php flashMessage(); ?>

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
        <p>Current Password: <input type="password" name="oldPassword" class="password" equired/></p>
        <p>New Password: <input type="password" name="newPassword" class="password"/>
        <span style="color: red">^6-20 characters</span></p>
        <p>Confirm New Password: <input type="password" name="newPassword2" class="password"/></p>
        <p><input type="checkbox" onclick="togglePasswordVisibility('password')">Show Password</p>
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
    
    function togglePasswordVisibility(pw) {
        var elements = document.getElementsByClassName(pw);
        for(var i = 0; i < elements.length; i++) {
            var pass = elements[i];
            if (pass.type === "password") {
                pass.type = "text";
            } else {
                pass.type = "password";
            }
        }
    }
</script>
</main>
</body>
</html>