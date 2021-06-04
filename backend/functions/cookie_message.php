<?php
    function setPersistentMessage(string $message){
        setcookie(COOKIE_MESSAGE, $message, EXECUTION_START_TIME + COOKIE_EXPIRE, "/", "", false, true);
    }

    function setPersistentError(string $error){
        setcookie(COOKIE_ERROR, $error, EXECUTION_START_TIME + COOKIE_EXPIRE, "/", "", false, true);
    }

    function setPersistentWarning(string $warning){
        setcookie(COOKIE_WARNING, $warning, EXECUTION_START_TIME + COOKIE_EXPIRE, "/", "", false, true);
    }
    
    $status_message = htmlspecialchars($_COOKIE[COOKIE_MESSAGE]);
    $status_error = htmlspecialchars($_COOKIE[COOKIE_ERROR]);
    $status_warning = htmlspecialchars($_COOKIE[COOKIE_WARNING]);

    function setOverrideMessage(string $message){
        global $status_message;
        
        $status_message = $message;
    }

    function setOverrideError(string $error){
        global $status_error;
        
        $status_error = $error;
    }

    function setOverrideWarning(string $warning){
        global $status_warning;

        $status_warning = $warning;
    }

    setPersistentMessage("");
    setPersistentError("");
    setPersistentWarning("");
?>