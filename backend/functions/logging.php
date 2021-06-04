<?php
    function logEvent(string $message, string $additional, string $severity, string $__FILE__, int $__LINE__, string $__FUNCTION__, bool $halt = false){
        global $db;

        $final_message = $__FILE__." {".$__FUNCTION__."} [".$__LINE__."] : ".$message;

        $execution_start_time = EXECUTION_START_TIME;
        $event_log_application_name = EVENT_LOG_APPLICATION_NAME;

        $event_log_query = $db->prepare("INSERT INTO ".DB_TABLE_EVENT_LOG." (`timestamp`, `application`, `message`, `additional`, `severity`, `userid`, `ip`) VALUES (:timestamp, :application, :message, :additional, :severity, :userid, :ip)");
        $event_log_query->bindParam(":timestamp", $execution_start_time);
        $event_log_query->bindParam(":application", $event_log_application_name);
        $event_log_query->bindParam(":message", $final_message);
        $event_log_query->bindParam(":additional", $additional);
        $event_log_query->bindParam(":severity", $severity);
        $event_log_query->bindParam(":userid", $_SESSION[SESSION_USERID]);
        $event_log_query->bindParam(":ip", $_SERVER["REMOTE_ADDR"]);

        $optional_json = json_encode([
            "timestamp" => EXECUTION_START_TIME,
            "application" => EVENT_LOG_APPLICATION_NAME,
            "message" => $message,
            "execution" => [
                "file" => $__FILE__,
                "function" => $__FUNCTION__,
                "line" => $__LINE__
            ],
            "additional" => $additional,
            "severity" => $severity,
            "userid" => (int)$_SESSION[SESSION_USERID],
            "ip" => $_SERVER["REMOTE_ADDR"]
        ], JSON_PRETTY_PRINT);

        if(!$event_log_query->execute()){
            print("`".EXECUTION_START_TIME."` Failed to log an event! Event data: ```json\n".$optional_json."\n```");

            die("`".EXECUTION_START_TIME."` Event log failure explained: ```json\n".json_encode($event_log_query->errorInfo(), JSON_PRETTY_PRINT)."\n```");
        }else{
            if($severity == EVENT_LOG_SEVERITY_CRITICAL){
                die("A **__*CRITICAL*__** EVENT HAS OCCURED```json\n".$optional_json."\n```");
            }
    
            if($severity == EVENT_LOG_SEVERITY_ERROR){
                die("An **__*ERROR*__** EVENT HAS OCCURED```json\n".$optional_json."\n```");
            }
        }

        if($halt){
            die(EVENT_LOG_HALT_MESSAGE);
        }
    }
?>