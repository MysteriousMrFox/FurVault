<?php
    function generateCSRFToken(){
        $_SESSION[SESSION_CSRF] = bin2hex(random_bytes(32));
    }

    function checkCSRFToken(bool $postCheck = true){
        if(empty($_SESSION[SESSION_CSRF])){
            generateCSRFToken();
            setPersistentError("CSRF verification failure. Processing has halted. (0)");
            header("Location: /");
            die();
        }

        if($postCheck){
            if(empty($_POST["CSRF"])){
                generateCSRFToken();
                setPersistentError("CSRF verification failure. Processing has halted. (1)");
                header("Location: /");
                die();
            }

            $token_provided = $_POST["CSRF"];
        }else{
            if(empty($_GET["CSRF"])){
                generateCSRFToken();
                setPersistentError("CSRF verification failure. Processing has halted. (2)");
                header("Location: /");
                die();
            }

            $token_provided = $_GET["CSRF"];
        }

        if(!hash_equals($_SESSION[SESSION_CSRF], $token_provided)){
            generateCSRFToken();
            setPersistentError("CSRF verification failure. Processing has halted. (3)");
            header("Location: /");
            die();
        }
    }

    function peekCSRFToken(bool $postCheck = true){
        if(empty($_SESSION[SESSION_CSRF])){
            generateCSRFToken();
            return false;
        }

        if($postCheck){
            if(empty($_POST["CSRF"])){
                generateCSRFToken();
                return false;
            }

            $token_provided = $_POST["CSRF"];
        }else{
            if(empty($_GET["CSRF"])){
                generateCSRFToken();
                return false;
            }

            $token_provided = $_GET["CSRF"];
        }

        if(!hash_equals($_SESSION[SESSION_CSRF], $token_provided)){
            generateCSRFToken();
            return false;
        }

        return true;
    }
?>