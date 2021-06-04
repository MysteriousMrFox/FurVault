<?php
    require($_SERVER["DOCUMENT_ROOT"]."/backend/init.php");
    
    checkCSRFToken();

    requireAuthentication();

    if(!isset($_POST["search"]) || $_POST["search"] == ""){
        setPersistentWarning("No search query or MD5 was provided");
        header("Location: /gallery/list/0");
        die();
    }

    if(preg_match('/^[a-f0-9]{32}$/', $_POST["search"])){
        $item = getItemByMd5($_POST["search"]);

        if($item == false){
            setPersistentWarning("An item with that MD5 could not be found");
            header("Location: /home");
            die();
        }

        header("Location: /gallery/view/".$item->id);
        die();
    }

    header("Location: /gallery/list/0?search=".urlencode($_POST["search"]));
?>