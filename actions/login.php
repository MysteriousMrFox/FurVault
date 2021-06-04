<?php
    require($_SERVER["DOCUMENT_ROOT"]."/backend/init.php");
    
    checkCSRFToken();

    requireUnauthenticated();

    if(!isset($_POST["username"]) || $_POST["username"] == "" || !isset($_POST["password"]) || $_POST["password"] == ""){
        setPersistentError("Username or password is incorrect");
        header("Location: /?fval");
        die();
    }

    $login_user = getUserByDisplayName($_POST["username"]);

    if($login_user == false){
        setPersistentError("Username or password is incorrect");
        header("Location: /");
        die();
    }

    if(!password_verify($_POST["password"], $login_user->password)){
        setPersistentError("Username or password is incorrect");
        header("Location: /");
        die();
    }

    completeUserLogin($login_user->id);
?>