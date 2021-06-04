<?php
    require($_SERVER["DOCUMENT_ROOT"]."/backend/init.php");

    if($_GET["id"] == ""){
        http_response_code(400);

        finishWithJson([
            "data" => null,
            "status" => [
                "code" => 400,
                "message" => "No item ID was specified"
            ]
        ]);
    }

    $item = getItemById($_GET["id"]);

    if($item == false){
        http_response_code(404);

        finishWithJson([
            "data" => null,
            "status" => [
                "code" => 404,
                "message" => "Item not found"
            ]
        ]);
    }

    $mime = getMimeFromFilename($item->storageLocation);

    if($mime != false){
        header("Content-Type: ".$mime);
    }else{
        http_response_code(500);

        finishWithJson([
            "data" => null,
            "status" => [
                "code" => 500,
                "message" => "The server was unable to determine the format in which to transmit the file"
            ]
        ]);
    }

    readfile(DATA_PATH_ITEMS."/".$item->storageLocation);
?>