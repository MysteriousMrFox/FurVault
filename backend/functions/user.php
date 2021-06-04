<?php
    function getUserById(int $userId){
        global $db;

        $user_data_query = $db->prepare("SELECT * FROM ".DB_TABLE_USERS." WHERE id = :userId");
        $user_data_query->bindParam(":userId", $userId);
        if(!$user_data_query->execute()) {
            logEvent("Failed to get user by ID", json_encode($user_data_query->errorInfo()), EVENT_LOG_SEVERITY_CRITICAL, __FILE__, __LINE__, __FUNCTION__, true);
        }

        return $user_data_query->fetch(PDO::FETCH_OBJ);
    }

    function getUserByDisplayName(string $displayName){
        global $db;

        $user_data_query = $db->prepare("SELECT * FROM ".DB_TABLE_USERS." WHERE displayName = :displayName");
        $user_data_query->bindParam(":displayName", $displayName);
        if(!$user_data_query->execute()) {
            logEvent("Failed to get user by Display Name", json_encode($user_data_query->errorInfo()), EVENT_LOG_SEVERITY_CRITICAL, __FILE__, __LINE__, __FUNCTION__, true);
        }

        return $user_data_query->fetch(PDO::FETCH_OBJ);
    }

    function getCurrentUser(){
        if(!$_SESSION[SESSION_LOGGEDIN]){
            return false;
        }

        return getUserById($_SESSION[SESSION_USERID]);
    }

    function getUserPermissions(int $userId){
        global $db;

        $user_permissions_query = $db->prepare("SELECT * FROM ".DB_TABLE_PERMISSIONS." WHERE userId = :userId");
        $user_permissions_query->bindParam(":userId", $userId);
        if(!$user_permissions_query->execute()) {
            logEvent("Failed to get user permissions", json_encode($user_permissions_query->errorInfo()), EVENT_LOG_SEVERITY_CRITICAL, __FILE__, __LINE__, __FUNCTION__, true);
        }

        return $user_permissions_query->fetchAll(PDO::FETCH_OBJ);
    }

    function completeUserLogin(int $userId){
        global $db;

        $_SESSION[SESSION_LOGGEDIN] = true;
        $_SESSION[SESSION_USERID] = $userId;
        generateCSRFToken();

        $execution_start_time = EXECUTION_START_TIME;

        $user_login_update_query = $db->prepare("UPDATE ".DB_TABLE_USERS." SET lastLoginTimestamp = :lastLoginTimestamp, lastLoginIp = :lastLoginIp WHERE id = :userId");
        $user_login_update_query->bindParam(":lastLoginTimestamp", $execution_start_time);
        $user_login_update_query->bindParam(":lastLoginIp", $_SERVER["REMOTE_ADDR"]);
        $user_login_update_query->bindParam(":userId", $userId);
        if(!$user_login_update_query->execute()) {
            logEvent("Failed to record user last login", json_encode($user_login_update_query->errorInfo()), EVENT_LOG_SEVERITY_CRITICAL, __FILE__, __LINE__, __FUNCTION__);

            unset($_SESSION);
            session_unset();
            session_destroy();

            die(EVENT_LOG_HALT_MESSAGE);
        }

        if(!isset($_POST["return"]) || $_POST["return"] == ""){
            header("Location: /home");
            die();
        }

        if(strpos($_POST["return"], ":") !== false){
            unset($_SESSION);
            session_unset();
            session_destroy();
            setPersistentError("Your login was cancelled due to a potentially forged login attempt");
            header("Location: /");
            die();
        }

        header("Location: ".$_POST["return"]);
        die();
    }
?>