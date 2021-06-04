<?php
    require($_SERVER["DOCUMENT_ROOT"]."/backend/init.php");

    checkCSRFToken();

    requireAuthentication();
    
    $status = getCacheObject(CACHE_KEY_e621_LIST_BUILD_MINI_STATUS);

    if($status == CACHE_NO_OBJECT_DATA){
        setPersistentWarning("No unsaved list is available");
        header("Location: /e621/build/status");
        die();
    }

    $seconds_since_last_update = time() - $status->status->updated;

    if($seconds_since_last_update < 60){
        setPersistentWarning("If you wish to abort this build, please click Force Abort again in ".(60 - $seconds_since_last_update)."s");
        header("Location: /e621/build/status");
        die();
    }

    cancelListBuild($status->queue_id);

    unsetCache(CACHE_KEY_e621_LIST_BUILD_STATUS);
    unsetCache(CACHE_KEY_e621_LIST_BUILD_MINI_STATUS);
    unsetCache(CACHE_KEY_e621_LIST_BUILD_CANCEL);

    setPersistentMessage("The unsaved list has been sucessfully force aborted");
    header("Location: /e621/build/status");
?>