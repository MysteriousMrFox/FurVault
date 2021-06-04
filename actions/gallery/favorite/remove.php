<?php
    require($_SERVER["DOCUMENT_ROOT"]."/backend/init.php");
    
    checkCSRFToken(false);

    requireAuthentication();

    if(!isset($_GET["id"]) || $_GET["id"] == ""){
        setPersistentWarning("No item was specified");
        header("Location: /gallery/list/0");
        die();
    }

    $item = getItemById((int) $_GET["id"]);

    if($item == false){
        setPersistentError("The specified item does not exist");
        header("Location: /gallery/list/0");
        die();
    }

    removeFavorite($_SESSION[SESSION_USERID], $item->id);

    setPersistentMessage("Item unfavorited");
    header("Location: /gallery/view/".$item->id);
?>