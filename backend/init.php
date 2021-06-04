<?php
    define("MICRO_EXECUTION_START_TIME", microtime(true));
    define("EXECUTION_START_TIME", time());

    date_default_timezone_set("UTC");

    if(isset($require_vendor) && $require_vendor){
        require($_SERVER["DOCUMENT_ROOT"]."/vendor/autoload.php");
    }

    require("constants.php");
    require("config.php");

    if(DEBUG){
        ini_set("display_errors", true);
        ini_set("display_startup_errors", true);
        error_reporting(E_ALL);
    }

    if(!isset($disable_session)){
        $disable_session = false;
    }
    
    if(!$disable_session){
        ini_set("session.cookie_httponly", true);
        ini_set("session.cookie_secure", false);
        session_name(COOKIE_SESSION);
        session_start();
    }

    require("db.php");

    require("functions/util.php");
    require("functions/logging.php");
    require("functions/cookie_message.php");
    require("functions/csrf.php");
    require("functions/response.php");
    require("functions/cache.php");
    require("functions/user.php");
    require("functions/item.php");
    require("functions/tag.php");
    require("functions/e621.php");
    require("functions/download_queue.php");
    require("functions/list_build_queue.php");
    require("functions/failed_download.php");
    require("functions/favorite.php");

    if(empty($_SESSION[SESSION_CSRF])) {
        generateCSRFToken();
    }

    if(!$disable_session){
        require("user_manager.php");
    }

    useCache();
?>