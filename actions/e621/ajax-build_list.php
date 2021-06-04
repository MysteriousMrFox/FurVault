<?php
    ignore_user_abort(true);

    require($_SERVER["DOCUMENT_ROOT"]."/backend/init.php");

    if(!peekCSRFToken()){
        http_response_code(403);
        finishWithJson([
            "data" => null,
            "status" => [
                "code" => 403,
                "message" => "CSRF token check failure"
            ]
        ]);
    }

    requireAuthentication();

    if(!isset($_POST["search"]) || $_POST["search"] == ""){
        http_response_code(400);
        finishWithJson([
            "data" => null,
            "status" => [
                "code" => 400,
                "message" => "No search parameter was specified"
            ]
        ]);
    }

    if(!isset($_POST["name"]) || $_POST["name"] == ""){
        $_POST["name"] = "No Name";
    }

    queueListBuild($_POST["name"], $_POST["search"]);

    callListBuildProcessor();
?>