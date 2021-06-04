<?php
    //Initialise user data. If user is not logged in, the user will be false and the permissions will be empty
    $user_data = getCurrentUser();

    $user_permissions = [];
    
    if($user_data != false){
        $user_permissions_rows = getUserPermissions($user_data->id);

        foreach ($user_permissions_rows as $row) {
            array_push($user_permissions, $row->permission);
        }
    }


    //Functions for easy data query and action
    function hasPermission(string $permission_name){
        global $user_permissions;

        return in_array($permission_name, $user_permissions);
    }

    function requireAuthentication(){
        if(!$_SESSION[SESSION_LOGGEDIN]) {
            header("Location: /?return=".urlencode($_SERVER["REQUEST_URI"]));
            setPersistentWarning("Please log in to continue");
            die();
        }

        if(!hasPermission("canLogIn")){
            session_destroy();
            unset($_SESSION);
            setPersistentError("You have been logged out by an admin.");
            header("Location: /?return=".urlencode($_SERVER["REQUEST_URI"]));
            die();
        }
    }

    function isAuthenticated(){
        if(!$_SESSION[SESSION_LOGGEDIN]) {
            return false;
        }

        if(!hasPermission("canLogIn")){
            session_destroy();
            unset($_SESSION);
            return false;
        }

        return true;
    }

    function requireUnauthenticated(){
        if($_SESSION[SESSION_LOGGEDIN]) {
            header("Location: /home");
            die();
        }
    }

    function requirePermission($permission_name){
        if(!hasPermission($permission_name)) {
            setPersistentError("You do not have permission to do this");
            header("Location: /home");
            die();
        }
    }
?>