<?php
require_once "pdo.php";
require_once "function.php";
session_start();

//Return;
if(isset($_POST['cancel'])) {
    header('Location: index.php');
    return;
}

if(isset($_POST['login'])) {
    if(!checkParameter($_POST['email']) || !checkParameter($_POST['password'])) {
        $_SESSION['error'] = 'Email and password are required';
        header('Location: login.php');
        return;
    } else {
        $pw = hashPassword($_POST['password']);

        $stmt = $pdo->prepare("SELECT * FROM User WHERE email = :email");
        $stmt->execute(array(':email' => $_POST['email']));
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if($row === false) {
            //Confirm account exist
            $_SESSION['error'] = 'Account not found';
            header('Location: login.php');
            return;
        } else {
            //Check password if account found
            if($pw === $row['password']) {
                //Password correct
                // $_SESSION['success'] = 'Welcome back ' . $row['name'] . '!';
                $_SESSION['account'] = $row['name'];
                $_SESSION['userId'] = $row['id'];
                header('Location: index.php');
                return;
            } else {
                //Password incorrect
                $_SESSION['error'] = 'Incorrect password.';
                header('Location: login.php');
                return;
            }
        }
    }
}

?>


<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <link rel="stylesheet" href="css/main.css">
</head>
<body>
    <header>
    <div class="menu-btn">
      <span class="menu-btn__burger"></span>
    </div>
    <?php printMainMenu('login'); ?>
</header>

<main class="login-page">
    <section class="head-image">
        <h1 class="title">Smart Home - Power Consumption Monitoring System</h1>
        <h1 class="title subtitle">Please Login</h1>
    </section>
        <?php flashMessage(); ?>
    <section class="login">
        <form method="post">
            <p>Email: <input type="email" name="email" value="<?= $_SESSION['email']?>"/></p>
            <p>Password: <input type="password" name="password" id="password"/></p>
            <p><input type="checkbox" onclick="toggleVisibility()"> Show Password</p>
            <p class="submitBtns"><input type="submit" name="login" value="Login" class="submitBtn"/>&nbsp
            <input type='submit' name='cancel' value='Cancel' class="submitBtn"/></p>
        </form>

    </section>
    </main>

    <script>
    function toggleVisibility() {
        var pass = document.getElementById("password");
        if (pass.type === "password") {
            pass.type = "text";
        } else {
            pass.type = "password";
        }
    }
    </script>
    <script src="js/main.js"></script>
</body>
</html>