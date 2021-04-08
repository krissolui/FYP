<?php
require_once "pdo.php";
require_once "function.php";
session_start();
    //Return;
    if(isset($_POST['cancel'])) {
        header('Location: index.php');
        return;
    }

    if(isset($_POST['signUp'])) {
        if(!checkParameter($_POST['firstName']) || !checkParameter($_POST['lastName']) || !checkParameter($_POST['email']) || !checkParameter($_POST['password']) || !checkParameter($_POST['password2'])) {
            $_SESSION['error'] = 'All fields are required';
            header('Location: signup.php');
            return;
        } else {
            $stmt = $pdo->prepare("SELECT * FROM User WHERE email = :email");
            $stmt->execute(array(':email' => $_POST['email']));
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if($row !== false) {
                //Check if account exist
                $_SESSION['error'] = 'Account already exist. Please <a href="login.php">login here</a>.';
                header('Location: signup.php');
                return;
            } else if (strlen($_POST['password']) < 6 || strlen($_POST['password']) > 20) {
                //Check password length
                $_SESSION['error'] = 'Password length must be 6-20.';
                header('Location: signup.php');
                return;
            } else {
                //Check if passwords match
                if($_POST['password'] !== $_POST['password2']) {
                    //Passwords not match
                    $_SESSION['error'] = 'Passwords do not match.';
                    header('Location: signup.php');
                    return;
                } else {
                    //Passwords match
                    //Create user
                    $salt = 'ElecFypGroup64';
                    $pw = hash('md5', $salt. $_POST['password']);

                    $stmt = $pdo->prepare('INSERT INTO User (name, email, password) VALUES (:name, :email, :password)');
                    $stmt->execute(array(
                        ':name' => $_POST['firstName'] . ' ' . $_POST['lastName'],
                        ':email' => $_POST['email'],
                        ':password' => $pw
                    ));

                    //Get user id
                    $stmt = $pdo->prepare('SELECT id From User WHERE email = :email AND password = :password');
                    $stmt->execute(array(
                        ':email' => $_POST['email'],
                        ':password' => $pw
                    ));
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    $_SESSION['success'] = 'Welcome ' . $_POST['lastName'] . '!';
                    $_SESSION['account'] = $_POST['firstName'] . ' ' . $_POST['lastName'];
                    $_SESSION['userId'] = $row['id'];
                    header('Location: index.php');
                    return;
                }
            }
        }
    }

?>

<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sign Up</title>
</head>
<body>
    <header>
        <h1>Please Sign Up</h1>
        <?php printTitleBar('signup'); ?>
    </header>

    <main>
        <?php
            if(isset($_SESSION['error'])) {
                echo('<p style="color: red">' . $_SESSION['error'] . '</p>');
                unset($_SESSION['error']);
            }
        ?>
        <form method="post">
            <p>First Name: <input type="text" name="firstName"/></p>
            <p>Last Name: <input type="text" name="lastName"/></p>
            <p>Email: <input type="email" name="email" value="<?= $_SESSION['email']?>"/></p>
            <p>Password: <input type="password" name="password"/>
            <span style="color: red">^6-20 characters</span></p>
            <p>Confirm Password: <input type="password" name="password2"/></p>
            <p><input type="submit" name="signUp" value="Sign Up"/>&nbsp
            <input type='submit' name='cancel' value='Cancel'/></p>
        </form>
        <p style="color: red">* All fields are required!</p>
    </main>
</body>
</html>