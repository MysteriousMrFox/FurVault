<?php
    function getTagByName(string $name){
        global $db;

        $tag_query = $db->prepare("SELECT * FROM ".DB_TABLE_TAGS." WHERE name = :name");
        $tag_query->bindParam(":name", $name);
        if(!$tag_query->execute()) {
            logEvent("Failed to get tag by name", json_encode($tag_query->errorInfo()), EVENT_LOG_SEVERITY_CRITICAL, __FILE__, __LINE__, __FUNCTION__, true);
        }

        return $tag_query->fetch(PDO::FETCH_OBJ);
    }

    function getTagsBySearch(string $search){
        global $db;

        if($search == ""){
            return false;
        }

        $tags_without_types = [];
        $tags_with_types = [];

        $exploded_search = explode(" ", $search);

        foreach($exploded_search as $search_item){
            $tag_parts = explode(":", $search_item, 2);

            if(count($tag_parts) == 2){
                array_push($tags_with_types, (object) [
                    "type" => $tag_parts[0],
                    "name" => $tag_parts[1]
                ]);
            }else{
                array_push($tags_without_types, $tag_parts[0]);
            }
        }

        $tags_without_types_query = "";

        foreach($tags_without_types as $tag){
            if($tags_without_types_query != ""){
                $tags_without_types_query .= " OR ";
            }

            $tags_without_types_query .= "name = ".$db->quote($tag)."";
        }

        $tags_with_types_query = "";

        foreach($tags_with_types as $tag){
            if($tags_with_types_query != ""){
                $tags_with_types_query .= " OR ";
            }

            $tags_with_types_query .= "(name = ".$db->quote($tag->name)." AND type = ".$db->quote($tag->type).")";
        }

        if($tags_without_types_query != "" && $tags_with_types_query != ""){
            $final_where = "(".$tags_without_types_query.") OR (".$tags_with_types_query.")";
        }else if($tags_without_types_query != ""){
            $final_where = $tags_without_types_query;
        }else if($tags_with_types_query != ""){
            $final_where = $tags_with_types_query;
        }else{
            return false;
        }

        $tags_query = $db->query("SELECT id FROM ".DB_TABLE_TAGS." WHERE ".$final_where);
        if(!$tags_query->execute()) {
            logEvent("Failed to get tags by search", json_encode($tags_query->errorInfo()), EVENT_LOG_SEVERITY_CRITICAL, __FILE__, __LINE__, __FUNCTION__, true);
        }

        $tags = $tags_query->fetchAll(PDO::FETCH_OBJ);

        if(count($tags) < count($exploded_search)){
            return false;
        }

        return $tags;
    }

    function getTagsByItem(int $id){
        global $db;

        $tags_query = $db->prepare("SELECT * FROM ".DB_TABLE_TAGS." WHERE id IN (SELECT tagId FROM ".DB_TABLE_ITEM_TAG_MAPPINGS." WHERE itemId = :id)");
        $tags_query->bindParam(":id", $id);
        if(!$tags_query->execute()) {
            logEvent("Failed to get tags by item", json_encode($tags_query->errorInfo()), EVENT_LOG_SEVERITY_CRITICAL, __FILE__, __LINE__, __FUNCTION__, true);
        }

        return $tags_query->fetchAll(PDO::FETCH_OBJ);
    }

    function createTag(string $name, string $type, bool $haltOnFailure = true){
        global $db;

        $tag_create_query = $db->prepare("INSERT INTO ".DB_TABLE_TAGS." (`name`, `type`) VALUES (:name, :type)");
        $tag_create_query->bindParam(":name", $name);
        $tag_create_query->bindParam(":type", $type);
        if(!$tag_create_query->execute()) {
            logEvent("Failed to create tag", json_encode($tag_create_query->errorInfo()), EVENT_LOG_SEVERITY_CRITICAL, __FILE__, __LINE__, __FUNCTION__, $haltOnFailure);
            return false;
        }

        return $db->lastInsertId();
    }

    function assignTagToItem(int $tagId, int $itemId, bool $haltOnFailure = true){
        global $db;

        $tag_assign_query = $db->prepare("INSERT INTO ".DB_TABLE_ITEM_TAG_MAPPINGS." (`itemId`, `tagId`) VALUES (:itemId, :tagId)");
        $tag_assign_query->bindParam(":itemId", $itemId);
        $tag_assign_query->bindParam(":tagId", $tagId);
        if(!$tag_assign_query->execute()) {
            logEvent("Failed to assign tag to item", json_encode(["sql_error" => $tag_assign_query->errorInfo(), "item_id" => $itemId, "tag_id" => $tagId]), EVENT_LOG_SEVERITY_CRITICAL, __FILE__, __LINE__, __FUNCTION__, $haltOnFailure);
            return false;
        }

        return true;
    }

    function getTagCount(){
        global $db;

        $tag_count_query = $db->query("SELECT COUNT(*) FROM ".DB_TABLE_TAGS);
        if(!$tag_count_query->execute()) {
            logEvent("Failed to get tag count", json_encode($tag_count_query->errorInfo()), EVENT_LOG_SEVERITY_CRITICAL, __FILE__, __LINE__, __FUNCTION__, true);
        }

        return (int) $tag_count_query->fetchColumn();
    }
?>