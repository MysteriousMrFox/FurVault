<?php
    require($_SERVER["DOCUMENT_ROOT"]."/backend/init.php");

    if(!peekCSRFToken(false)){
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

    callDownloadProcessor(true);
?>