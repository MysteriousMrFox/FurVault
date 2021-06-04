<?php
    function createFailedDownload($data, string $reason){
        global $db;

        $data = json_encode($data);

        $failed_download_insert_query = $db->prepare("INSERT INTO ".DB_TABLE_FAILED_DOWNLOADS." (`data`, `reason`) VALUES (:data, :reason)");
        $failed_download_insert_query->bindParam(":data", $data);
        $failed_download_insert_query->bindParam(":reason", $reason);
        if(!$failed_download_insert_query->execute()) {
            logEvent("Failed to save failed download", json_encode($failed_download_insert_query->errorInfo()), EVENT_LOG_SEVERITY_CRITICAL, __FILE__, __LINE__, __FUNCTION__, true);
        }
    }
?>