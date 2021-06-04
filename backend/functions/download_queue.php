<?php
    function getDownloadQueue(bool $finished = false, bool $includeBuild = false){
        global $db;

        if(!$includeBuild){
            $queue_query = $db->prepare("SELECT * FROM ".DB_TABLE_DOWNLOAD_QUEUE." WHERE complete = :complete");
        }else{
            $queue_query = $db->prepare("SELECT dq.id as id, bq.search as search, bq.name as name, bq.id as buildQueueId, bq.createdTimestamp as createdTimestamp, bq.cancelled as cancelled, dq.pauseState as pauseState FROM ".DB_TABLE_DOWNLOAD_QUEUE." dq JOIN ".DB_TABLE_BUILD_QUEUE." bq ON bq.id = dq.buildQueueId WHERE dq.complete = :complete");
        }
        
        $queue_query->bindParam(":complete", $finished, PDO::PARAM_BOOL);
        if(!$queue_query->execute()) {
            logEvent("Failed to get download queue", json_encode($queue_query->errorInfo()), EVENT_LOG_SEVERITY_CRITICAL, __FILE__, __LINE__, __FUNCTION__, true);
        }

        return $queue_query->fetchAll(PDO::FETCH_OBJ);
    }

    function queueDownload(int $buildQueueId){
        global $db;

        $queue_insert_query = $db->prepare("INSERT INTO ".DB_TABLE_DOWNLOAD_QUEUE." (`buildQueueId`, `pauseState`, `complete`) VALUES (:buildQueueId, NULL, 0)");
        $queue_insert_query->bindParam(":buildQueueId", $buildQueueId);
        if(!$queue_insert_query->execute()) {
            logEvent("Failed to create download queue item", json_encode($queue_insert_query->errorInfo()), EVENT_LOG_SEVERITY_CRITICAL, __FILE__, __LINE__, __FUNCTION__, true);
        }

        return $db->lastInsertId();
    }

    function cancelDownload(int $id){
        global $db;

        $queue_cancel_query = $db->prepare("DELETE FROM ".DB_TABLE_DOWNLOAD_QUEUE." WHERE id = :id");
        $queue_cancel_query->bindParam(":id", $id);
        if(!$queue_cancel_query->execute()) {
            logEvent("Failed to cancel download queue item", json_encode($queue_cancel_query->errorInfo()), EVENT_LOG_SEVERITY_CRITICAL, __FILE__, __LINE__, __FUNCTION__, true);
        }
    }

    function pauseDownload(int $id, $status){
        global $db;

        $status = json_encode($status);

        $queue_pause_query = $db->prepare("UPDATE ".DB_TABLE_DOWNLOAD_QUEUE." SET pauseState = :pauseState WHERE id = :id");
        $queue_pause_query->bindParam(":pauseState", $status);
        $queue_pause_query->bindParam(":id", $id);
        if(!$queue_pause_query->execute()) {
            logEvent("Failed to pause download queue item", json_encode($queue_pause_query->errorInfo()), EVENT_LOG_SEVERITY_CRITICAL, __FILE__, __LINE__, __FUNCTION__, true);
        }
    }
    
    function pauseDownloadByBuildQueueId(int $buildQueueId, $status){
        global $db;

        $status = json_encode($status);

        $queue_pause_query = $db->prepare("UPDATE ".DB_TABLE_DOWNLOAD_QUEUE." SET pauseState = :pauseState WHERE buildQueueId = :buildQueueId");
        $queue_pause_query->bindParam(":pauseState", $status);
        $queue_pause_query->bindParam(":buildQueueId", $buildQueueId);
        if(!$queue_pause_query->execute()) {
            logEvent("Failed to pause download queue item by queue id", json_encode($queue_pause_query->errorInfo()), EVENT_LOG_SEVERITY_CRITICAL, __FILE__, __LINE__, __FUNCTION__, true);
        }
    }

    function unpauseDownload(int $id){
        global $db;

        $queue_unpause_query = $db->prepare("UPDATE ".DB_TABLE_DOWNLOAD_QUEUE." SET pauseState = NULL WHERE id = :id");
        $queue_unpause_query->bindParam(":id", $id);
        if(!$queue_unpause_query->execute()) {
            logEvent("Failed to unpause download queue item", json_encode($queue_unpause_query->errorInfo()), EVENT_LOG_SEVERITY_CRITICAL, __FILE__, __LINE__, __FUNCTION__, true);
        }
    }

    function markDownloadComplete(int $id){
        global $db;

        $download_complete_query = $db->prepare("UPDATE ".DB_TABLE_DOWNLOAD_QUEUE." SET pauseState = NULL, complete = 1 WHERE id = :id");
        $download_complete_query->bindParam(":id", $id);
        if(!$download_complete_query->execute()) {
            logEvent("Failed to mark download as complete", json_encode($download_complete_query->errorInfo()), EVENT_LOG_SEVERITY_CRITICAL, __FILE__, __LINE__, __FUNCTION__, true);
        }
    }

    function getDownloadQueueCount(){
        global $db;

        $queue_count_query = $db->prepare("SELECT COUNT(*) FROM ".DB_TABLE_DOWNLOAD_QUEUE." WHERE complete = 0");
        if(!$queue_count_query->execute()) {
            logEvent("Failed to get download queue count", json_encode($queue_count_query->errorInfo()), EVENT_LOG_SEVERITY_CRITICAL, __FILE__, __LINE__, __FUNCTION__, true);
        }

        return (int) $queue_count_query->fetchColumn();
    }

    function generateDownloadStatusBadge(){
        $download_status = getCacheObject(CACHE_KEY_e621_DOWNLOAD_STATUS);

        if($download_status == CACHE_NO_OBJECT_DATA){
            return "<span class=\"badge bg-secondary\">INACTIVE</span>";
        }else if($download_status->status->updated < (EXECUTION_START_TIME - 30)){
            return "<span class=\"badge bg-danger\">ACTION NEEDED</span>";
        }else{
            return "<span class=\"badge bg-primary\">ACTIVE</span>";
        }
    }

    function callDownloadProcessor($unpauseIfPaused = false){
        $download_queue = getDownloadQueue();
        $pausedAvailable = $download_queue != false && $download_queue[0]->pauseState != null;

        if(!$unpauseIfPaused && $pausedAvailable){
            return;
        }

        $handoff = curl_init("http".(INTERNAL_CALL_HTTPS ? "s" : "")."://".INTERNAL_CALL_HOST."/internal/e621/download_processor");
        curl_setopt($handoff, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($handoff, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($handoff, CURLOPT_RESOLVE, [
            INTERNAL_CALL_HOST.":127.0.0.1"
        ]);
        curl_setopt($handoff, CURLOPT_POSTFIELDS, "key=".urlencode(INTERNAL_CALL_KEY));
        curl_setopt($handoff, CURLOPT_TIMEOUT, 3); //Almost immediately drop connection
        curl_exec($handoff);
        curl_close($handoff);
    }
?>