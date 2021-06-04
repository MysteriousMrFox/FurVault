<?php
    function getListBuildQueue(bool $finished, bool $orderAscending){
        global $db;

        $rules = $finished ? "data IS NOT NULL OR cancelled = 1" : "data IS NULL AND cancelled = 0";

        $queue_query = $db->query("SELECT id, name, search, cancelled, createdTimestamp, availableFound, unavailableFound FROM ".DB_TABLE_BUILD_QUEUE." WHERE ".$rules." ORDER BY createdTimestamp ".($orderAscending ? "ASC" : "DESC"));
        if(!$queue_query->execute()) {
            logEvent("Failed to get build list queue", json_encode($queue_query->errorInfo()), EVENT_LOG_SEVERITY_CRITICAL, __FILE__, __LINE__, __FUNCTION__, true);
        }

        return $queue_query->fetchAll(PDO::FETCH_OBJ);
    }

    function queueListBuild(string $name, string $search){
        global $db;

        $execution_start_time = EXECUTION_START_TIME;

        $queue_insert_query = $db->prepare("INSERT INTO ".DB_TABLE_BUILD_QUEUE." (`name`, `search`, `data`, `cancelled`, `newestAvailablePost`, `oldestAvailablePost`, `newestUnavailablePost`, `oldestUnavailablePost`, `createdTimestamp`) VALUES (:name, :search, NULL, 0, 0, 0, 0, 0, :createdTimestamp)");
        $queue_insert_query->bindParam(":name", $name);
        $queue_insert_query->bindParam(":search", $search);
        $queue_insert_query->bindParam(":createdTimestamp", $execution_start_time);
        if(!$queue_insert_query->execute()) {
            logEvent("Failed to create build list queue item", json_encode($queue_insert_query->errorInfo()), EVENT_LOG_SEVERITY_CRITICAL, __FILE__, __LINE__, __FUNCTION__, true);
        }

        return $db->lastInsertId();
    }

    function updateListBuildData(int $id, $data, int $newestAvailablePost, int $oldestAvailablePost, int $newestUnavailablePost, int $oldestUnavailablePost, int $availableFound, int $unavailableFound){
        global $db;

        $data = json_encode($data);

        $queue_data_update_query = $db->prepare("UPDATE ".DB_TABLE_BUILD_QUEUE." SET data = :data, newestAvailablePost = :newestAvailablePost, oldestAvailablePost = :oldestAvailablePost, newestUnavailablePost = :newestUnavailablePost, oldestUnavailablePost = :oldestUnavailablePost, availableFound = :availableFound, unavailableFound = :unavailableFound WHERE id = :id");
        $queue_data_update_query->bindParam(":data", $data);
        $queue_data_update_query->bindParam(":newestAvailablePost", $newestAvailablePost);
        $queue_data_update_query->bindParam(":oldestAvailablePost", $oldestAvailablePost);
        $queue_data_update_query->bindParam(":newestUnavailablePost", $newestUnavailablePost);
        $queue_data_update_query->bindParam(":oldestUnavailablePost", $oldestUnavailablePost);
        $queue_data_update_query->bindParam(":availableFound", $availableFound);
        $queue_data_update_query->bindParam(":unavailableFound", $unavailableFound);
        $queue_data_update_query->bindParam(":id", $id);
        if(!$queue_data_update_query->execute()) {
            logEvent("Failed to update build list queue item data", json_encode($queue_data_update_query->errorInfo()), EVENT_LOG_SEVERITY_CRITICAL, __FILE__, __LINE__, __FUNCTION__, true);
        }
    }

    function cancelListBuild(int $id){
        global $db;

        $queue_cancel_query = $db->prepare("UPDATE ".DB_TABLE_BUILD_QUEUE." SET data = NULL, cancelled = 1 WHERE id = :id");
        $queue_cancel_query->bindParam(":id", $id);
        if(!$queue_cancel_query->execute()) {
            logEvent("Failed to cancel build list queue item", json_encode($queue_cancel_query->errorInfo()), EVENT_LOG_SEVERITY_CRITICAL, __FILE__, __LINE__, __FUNCTION__, true);
        }
    }

    function getLatestCompletedListBuild(string $search){
        global $db;

        $queue_query = $db->prepare("SELECT id, name, search, newestAvailablePost, oldestAvailablePost, newestUnavailablePost, oldestUnavailablePost, cancelled, createdTimestamp FROM ".DB_TABLE_BUILD_QUEUE." WHERE data IS NOT NULL AND cancelled = 0 AND search = :search ORDER BY createdTimestamp DESC");
        $queue_query->bindParam(":search", $search);
        if(!$queue_query->execute()) {
            logEvent("Failed to get latest completed list build by search", json_encode($queue_query->errorInfo()), EVENT_LOG_SEVERITY_CRITICAL, __FILE__, __LINE__, __FUNCTION__, true);
        }

        return $queue_query->fetch(PDO::FETCH_OBJ);
    }

    function getListBuildById(int $id, bool $finished = false){
        global $db;

        $queue_query = $db->prepare("SELECT id, name, search, data FROM ".DB_TABLE_BUILD_QUEUE." WHERE id = :id".($finished ? " AND data IS NOT NULL AND cancelled = 0" : ""));
        $queue_query->bindParam(":id", $id);
        if(!$queue_query->execute()) {
            logEvent("Failed to get list build by id", json_encode($queue_query->errorInfo()), EVENT_LOG_SEVERITY_CRITICAL, __FILE__, __LINE__, __FUNCTION__, true);
        }

        return $queue_query->fetch(PDO::FETCH_OBJ);
    }

    function getListBuildQueueCount(){
        global $db;

        $queue_count_query = $db->query("SELECT COUNT(*) FROM ".DB_TABLE_BUILD_QUEUE." WHERE data IS NULL AND cancelled = 0");
        if(!$queue_count_query->execute()) {
            logEvent("Failed to get list build count", json_encode($queue_count_query->errorInfo()), EVENT_LOG_SEVERITY_CRITICAL, __FILE__, __LINE__, __FUNCTION__, true);
        }

        return (int) $queue_count_query->fetchColumn();
    }

    function generateListBuildStatusBadge(){
        $list_build_status = getCacheObject(CACHE_KEY_e621_LIST_BUILD_MINI_STATUS);

        if($list_build_status == CACHE_NO_OBJECT_DATA){
            return "<span class=\"badge bg-secondary\">INACTIVE</span>";
        }else if($list_build_status->status->updated < (EXECUTION_START_TIME - 30) || ($list_build_status->status->finished != 0 && $list_build_status->status->finished < (EXECUTION_START_TIME - 10))){
            return "<span class=\"badge bg-danger\">ACTION NEEDED</span>";
        }else{
            return "<span class=\"badge bg-primary\">ACTIVE</span>";
        }
    }

    function callListBuildProcessor(){
        $handoff = curl_init("http".(INTERNAL_CALL_HTTPS ? "s" : "")."://".INTERNAL_CALL_HOST."/internal/e621/build_list_processor");
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