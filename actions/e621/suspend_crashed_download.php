<?php
    require($_SERVER["DOCUMENT_ROOT"]."/backend/init.php");

    checkCSRFToken();

    requireAuthentication();
    
    $status = getCacheObject(CACHE_KEY_e621_DOWNLOAD_STATUS);

    if($status == CACHE_NO_OBJECT_DATA){
        setPersistentWarning("No download is available");
        header("Location: /e621/download/status");
        die();
    }

    $seconds_since_last_update = time() - $status->status->updated;

    if($seconds_since_last_update < 60){
        setPersistentWarning("If you wish to suspend this download, please click Suspend again in ".(60 - $seconds_since_last_update)."s");
        header("Location: /e621/download/status");
        die();
    }

    #region Rewind broken part of download
        if($status->current_item->available != $status->items->available){
            //The available items downloader crashed. Reset the current item so the downloader can try again
            $status->current_item->available--;
        }else if($status->current_item->unavailable != $status->items->unavailable){
            //The unavailable items downloader crashed. Reset the current item so the downloader can try again
            $status->current_item->unavailable--;
        }else{
            setPersistentError("Suspend could not determine what part of the download process has crashed. Manual intervention required");
            header("Location: /e621/download/status");
            die();
        }
    #endregion

    pauseDownloadByBuildQueueId($status->queue_id, $status);

    unsetCache(CACHE_KEY_e621_DOWNLOAD_STATUS);
    unsetCache(CACHE_KEY_e621_DOWNLOAD_CANCEL);
    unsetCache(CACHE_KEY_e621_DOWNLOAD_PAUSE);

    setPersistentMessage("The download has been successfully suspended");
    setPersistentWarning("No data should have been lost in the suspend process. The item which was stuck has been reset so it can be retried");
    header("Location: /e621/download/status");
?>