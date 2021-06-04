<?php
    require($_SERVER["DOCUMENT_ROOT"]."/backend/init.php");

    checkCSRFToken(false);

    requireAuthentication();

    $finished_presort = getListBuildQueue(true, false);
    $finished = [];

    foreach($finished_presort as $finished_item){
        if(!isset($finished[$finished_item->search])){
            $finished[$finished_item->search] = [];
        }
        
        array_push($finished[$finished_item->search], $finished_item);
    }

    foreach($finished as $item){
        queueListBuild($item[0]->name, $item[0]->search);
    }

    callListBuildProcessor();

    setPersistentMessage("List builds queued");
    header("Location: /e621/build/queue");
?>