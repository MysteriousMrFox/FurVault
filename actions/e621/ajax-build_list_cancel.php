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

    $status = getCacheObject(CACHE_KEY_e621_LIST_BUILD_MINI_STATUS);

    if($status == CACHE_NO_OBJECT_DATA){
        http_response_code(409);
        finishWithJson([
            "data" => null,
            "status" => [
                "code" => 409,
                "message" => "No build is in progress"
            ]
        ]);
    }

    setCacheData(CACHE_KEY_e621_LIST_BUILD_CANCEL, "");
    
    finishWithJson([
        "data" => null,
        "status" => [
            "code" => 200,
            "message" => "Queue processing cancel requested"
        ]
    ]);
?>