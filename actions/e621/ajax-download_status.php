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

    $status = getCacheObject(CACHE_KEY_e621_DOWNLOAD_STATUS);

    if($status == CACHE_NO_OBJECT_DATA){
        http_response_code(409);
        finishWithJson([
            "data" => null,
            "status" => [
                "code" => 409,
                "message" => "No download is in progress"
            ]
        ]);
    }
    
    finishWithJson([
        "data" => $status,
        "status" => [
            "code" => 200,
            "message" => "Download in progress"
        ]
    ]);
?>