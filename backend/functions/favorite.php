<?php
    function addFavorite(int $userId, int $itemId){
        global $db;

        $favorite_create_query = $db->prepare("INSERT INTO ".DB_TABLE_FAVORITES." (`userId`, `itemId`) VALUES (:userId, :itemId)");
        $favorite_create_query->bindParam(":userId", $userId);
        $favorite_create_query->bindParam(":itemId", $itemId);
        if(!$favorite_create_query->execute()) {
            logEvent("Failed to create favorite", json_encode($favorite_create_query->errorInfo()), EVENT_LOG_SEVERITY_CRITICAL, __FILE__, __LINE__, __FUNCTION__, true);
        }
    }

    function removeFavorite(int $userId, int $itemId){
        global $db;

        $favorite_remove_query = $db->prepare("DELETE FROM ".DB_TABLE_FAVORITES." WHERE userId = :userId AND itemId = :itemId");
        $favorite_remove_query->bindParam(":userId", $userId);
        $favorite_remove_query->bindParam(":itemId", $itemId);
        if(!$favorite_remove_query->execute()) {
            logEvent("Failed to remove favorite", json_encode($favorite_remove_query->errorInfo()), EVENT_LOG_SEVERITY_CRITICAL, __FILE__, __LINE__, __FUNCTION__, true);
        }
    }

    function userHasFavorited(int $userId, int $itemId){
        global $db;

        $favorite_check_query = $db->prepare("SELECT id FROM ".DB_TABLE_FAVORITES." WHERE userId = :userId AND itemId = :itemId");
        $favorite_check_query->bindParam(":userId", $userId);
        $favorite_check_query->bindParam(":itemId", $itemId);
        if(!$favorite_check_query->execute()) {
            logEvent("Failed to check user favorited", json_encode($favorite_check_query->errorInfo()), EVENT_LOG_SEVERITY_CRITICAL, __FILE__, __LINE__, __FUNCTION__, true);
        }

        return $favorite_check_query->fetch(PDO::FETCH_OBJ) != false;
    }
?>