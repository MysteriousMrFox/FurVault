<?php
    function createItem(string $md5Checksum, ?string $storageLocation, ?string $previewStorageLocation, array $tags = [], array $metadata = [], bool $createTagIfNotExists = true){
        global $db;

        $execution_start_time = EXECUTION_START_TIME;

        $item_create_query = $db->prepare("INSERT INTO ".DB_TABLE_ITEMS." (`md5Checksum`, `storageLocation`, `previewStorageLocation`, `createdTimestamp`) VALUES (:md5Checksum, :storageLocation, :previewStorageLocation, :createdTimestamp)");
        $item_create_query->bindParam(":md5Checksum", $md5Checksum);
        $item_create_query->bindParam(":storageLocation", $storageLocation);
        $item_create_query->bindParam(":previewStorageLocation", $previewStorageLocation);
        $item_create_query->bindParam(":createdTimestamp", $execution_start_time);
        if(!$item_create_query->execute()) {
            logEvent("Failed to create item", json_encode($item_create_query->errorInfo()), EVENT_LOG_SEVERITY_CRITICAL, __FILE__, __LINE__, __FUNCTION__);
            return false;
        }

        $item_id = $db->lastInsertId();

        foreach($tags as $tag){
            $retrieved_tag = getTagByName($tag->name);

            if($retrieved_tag == false && $createTagIfNotExists){
                $new_tag = createTag($tag->name, $tag->type, false);

                if($new_tag == false){
                    deleteItemById($item_id, false);
                    return false;
                }

                if(assignTagToItem($new_tag, $item_id) == false){
                    deleteItemById($item_id, false);
                    return false;
                }
            }else{
                if(assignTagToItem($retrieved_tag->id, $item_id) == false){
                    deleteItemById($item_id, false);
                    return false;
                }
            }
        }

        foreach($metadata as $meta){
            if(createItemMetadata($item_id, $meta[0], $meta[1], false) == false){
                deleteItemById($item_id, false);
                return false;
            }
        }

        return $item_id;
    }

    function getItemById(int $id){
        global $db;

        $item_query = $db->prepare("SELECT * FROM ".DB_TABLE_ITEMS." WHERE id = :id");
        $item_query->bindParam(":id", $id);
        if(!$item_query->execute()) {
            logEvent("Failed to get item by id", json_encode($item_query->errorInfo()), EVENT_LOG_SEVERITY_CRITICAL, __FILE__, __LINE__, __FUNCTION__, true);
        }

        return $item_query->fetch(PDO::FETCH_OBJ);
    }

    function deleteItemById(int $id, bool $haltOnFailure = true){
        global $db;

        $item_delete_query = $db->prepare("DELETE FROM ".DB_TABLE_ITEMS." WHERE id = :id");
        $item_delete_query->bindParam(":id", $id);
        if(!$item_delete_query->execute()) {
            logEvent("Failed to delete item by id", json_encode($item_delete_query->errorInfo()), EVENT_LOG_SEVERITY_CRITICAL, __FILE__, __LINE__, __FUNCTION__, $haltOnFailure);
        }
    }

    function getItemByMd5(string $md5){
        global $db;

        $item_query = $db->prepare("SELECT * FROM ".DB_TABLE_ITEMS." WHERE md5Checksum = :md5Checksum");
        $item_query->bindParam(":md5Checksum", $md5);
        if(!$item_query->execute()) {
            logEvent("Failed to get item by md5", json_encode($item_query->errorInfo()), EVENT_LOG_SEVERITY_CRITICAL, __FILE__, __LINE__, __FUNCTION__, true);
        }

        return $item_query->fetch(PDO::FETCH_OBJ);
    }

    function createItemMetadata(int $itemId, string $key, string $value, bool $haltOnFailure = true){
        global $db;

        $item_metadata_create_query = $db->prepare("INSERT INTO ".DB_TABLE_ITEM_METADATA." (`itemId`, `key`, `value`) VALUES (:itemId, :key, :value)");
        $item_metadata_create_query->bindParam(":itemId", $itemId);
        $item_metadata_create_query->bindParam(":key", $key);
        $item_metadata_create_query->bindParam(":value", $value);
        if(!$item_metadata_create_query->execute()) {
            logEvent("Failed to create item metadata", json_encode(["query_error" => $item_metadata_create_query->errorInfo(), "key" => $key, "value" => $value]), EVENT_LOG_SEVERITY_CRITICAL, __FILE__, __LINE__, __FUNCTION__, $haltOnFailure);
            return false;
        }

        return true;
    }

    function getMetadataByItem(int $id){
        global $db;

        $item_metadata_query = $db->prepare("SELECT * FROM ".DB_TABLE_ITEM_METADATA." WHERE itemId = :itemId");
        $item_metadata_query->bindParam(":itemId", $id);
        if(!$item_metadata_query->execute()) {
            logEvent("Failed to get item metadata", json_encode($item_metadata_query->errorInfo()), EVENT_LOG_SEVERITY_CRITICAL, __FILE__, __LINE__, __FUNCTION__, true);
        }

        return $item_metadata_query->fetchAll(PDO::FETCH_OBJ);
    }

    function getItemsBySearch(string $search, int $limit = 30, int $page = 0, bool $available = true){
        return getItemsBySearchAlgo2($search, $limit, $page, $available);
    }

    function getItemsBySearchAlgo1(string $search, int $limit = 30, int $page = 0, bool $available = true){
        //Slow and unreliable. Preserved for future reference
        
        global $db;

        $offset = $page * $limit;
        $state = $available ? "NOT " : "";

        if($search == ""){
            return getLatestItems($limit, $page);
        }

        $tags = getTagsBySearch($search);

        if($tags == false){
            return false;
        }

        $tag_count = count($tags);
        $id_list = "";

        foreach($tags as $tag){
            if((int) $tag->id <= 0){
                continue;
            }

            if($id_list != ""){
                $id_list .= ", ";
            }

            $id_list .= $tag->id;
        }

        $items_query = $db->prepare("SELECT SQL_CALC_FOUND_ROWS DISTINCT itm.id FROM ".DB_TABLE_ITEM_TAG_MAPPINGS." mapping INNER JOIN ".DB_TABLE_ITEMS." itm ON mapping.itemId = itm.id INNER JOIN ".DB_TABLE_ITEM_TAG_MAPPINGS." mapping2 ON mapping2.itemId = itm.id WHERE itm.storageLocation IS ".$state."NULL AND mapping.tagId IN (".$id_list.") GROUP BY mapping2.tagId, itm.id HAVING COUNT(mapping.tagId) = :itemCount ORDER BY itm.id DESC LIMIT :lim OFFSET :ofs");
        $items_query->bindParam(":itemCount", $tag_count, PDO::PARAM_INT);
        $items_query->bindParam(":lim", $limit, PDO::PARAM_INT);
        $items_query->bindParam(":ofs", $offset, PDO::PARAM_INT);
        if(!$items_query->execute()) {
            logEvent("Failed to get items by search", json_encode($items_query->errorInfo()), EVENT_LOG_SEVERITY_CRITICAL, __FILE__, __LINE__, __FUNCTION__, true);
        }

        $items = $items_query->fetchAll(PDO::FETCH_OBJ);

        if($items != false){
            $found_rows = $db->query("SELECT FOUND_ROWS()")->fetch(PDO::FETCH_COLUMN);

            $max_page = ceil($found_rows / $limit) - 1;
        }else{
            $max_page = 0;
        }

        $item_ids = [];
        foreach($items as $item) {
            array_push($item_ids, $item->id);
        }

        return (object) [
            "data" => getItems($item_ids),
            "pagination" => (object) [
                "count" => $found_rows,
                "page" => $page,
                "max_page" => $max_page
            ]
        ];
    }

    function getItemsBySearchAlgo2(string $search, int $limit = 30, int $page = 0, bool $available = true){
        global $db;

        $offset = $page * $limit;
        $state = $available ? "NOT " : "";

        if($search == ""){
            return getLatestItems($limit, $page);
        }

        $tags = getTagsBySearch($search);

        if($tags == false){
            return false;
        }

        $tag_count = count($tags);
        $id_list = "";

        foreach($tags as $tag){
            if((int) $tag->id <= 0){
                continue;
            }

            if($id_list != ""){
                $id_list .= ", ";
            }

            $id_list .= $tag->id;
        }

        $items_query = $db->prepare("SELECT SQL_CALC_FOUND_ROWS mapping.itemId as id FROM ".DB_TABLE_ITEM_TAG_MAPPINGS." mapping JOIN ".DB_TABLE_ITEMS." itm ON itm.id = mapping.itemId WHERE mapping.tagId IN (".$id_list.") AND itm.storageLocation IS ".$state."NULL GROUP BY mapping.itemId HAVING COUNT(*) = :itemCount ORDER BY mapping.itemId DESC LIMIT :lim OFFSET :ofs");
        $items_query->bindParam(":itemCount", $tag_count, PDO::PARAM_INT);
        $items_query->bindParam(":lim", $limit, PDO::PARAM_INT);
        $items_query->bindParam(":ofs", $offset, PDO::PARAM_INT);
        if(!$items_query->execute()) {
            logEvent("Failed to get items by search", json_encode($items_query->errorInfo()), EVENT_LOG_SEVERITY_CRITICAL, __FILE__, __LINE__, __FUNCTION__, true);
        }

        $items = $items_query->fetchAll(PDO::FETCH_OBJ);

        if($items != false){
            $found_rows = $db->query("SELECT FOUND_ROWS()")->fetch(PDO::FETCH_COLUMN);

            $max_page = ceil($found_rows / $limit) - 1;
        }else{
            $max_page = 0;
        }

        $item_ids = [];
        foreach($items as $item) {
            array_push($item_ids, $item->id);
        }

        return (object) [
            "data" => getItems($item_ids),
            "pagination" => (object) [
                "count" => $found_rows,
                "page" => $page,
                "max_page" => $max_page
            ]
        ];
    }

    function getItems(array $ids){
        global $db;

        if(count($ids) == 0){
            return false;
        }

        $id_list = "";

        foreach($ids as $id){
            if((int) $id <= 0){
                continue;
            }

            if($id_list != ""){
                $id_list .= ", ";
            }

            $id_list .= $id;
        }

        $items_query = $db->query("SELECT * FROM ".DB_TABLE_ITEMS." WHERE id IN (".$id_list.") ORDER BY id DESC");
        if(!$items_query->execute()) {
            logEvent("Failed to get items in list", json_encode($items_query->errorInfo()), EVENT_LOG_SEVERITY_CRITICAL, __FILE__, __LINE__, __FUNCTION__, true);
        }

        return $items_query->fetchAll(PDO::FETCH_OBJ);
    }

    function getLatestItems(int $limit = 9, int $page = 0, bool $available = true){
        global $db;

        $offset = $page * $limit;
        $state = $available ? "NOT " : "";

        $latest_item_query = $db->prepare("SELECT SQL_CALC_FOUND_ROWS * FROM ".DB_TABLE_ITEMS." WHERE storageLocation IS ".$state."NULL ORDER BY id DESC LIMIT :lim OFFSET :ofs");
        $latest_item_query->bindParam(":lim", $limit, PDO::PARAM_INT);
        $latest_item_query->bindParam(":ofs", $offset, PDO::PARAM_INT);
        if(!$latest_item_query->execute()) {
            logEvent("Failed to get latest items", json_encode($latest_item_query->errorInfo()), EVENT_LOG_SEVERITY_CRITICAL, __FILE__, __LINE__, __FUNCTION__, true);
        }

        $result = $latest_item_query->fetchAll(PDO::FETCH_OBJ);

        if($result != false){
            $found_rows = $db->query("SELECT FOUND_ROWS()")->fetch(PDO::FETCH_COLUMN);

            $max_page = ceil($found_rows / $limit) - 1;
        }else{
            $max_page = 0;
        }

        return (object) [
            "data" => $result,
            "pagination" => (object) [
                "count" => $found_rows,
                "page" => $page,
                "max_page" => $max_page
            ]
        ];
    }

    function pickRandomItem(){
        global $db;

        $random_item_query = $db->prepare("SELECT * FROM ".DB_TABLE_ITEMS." WHERE storageLocation IS NOT NULL ORDER BY RAND() LIMIT 1");
        if(!$random_item_query->execute()) {
            logEvent("Failed to get random item", json_encode($random_item_query->errorInfo()), EVENT_LOG_SEVERITY_CRITICAL, __FILE__, __LINE__, __FUNCTION__, true);
        }

        return $random_item_query->fetch(PDO::FETCH_OBJ);
    }

    function getUserFavoriteItems(int $userId, int $limit = 30, int $page = 0, bool $available = true){
        global $db;

        $offset = $page * $limit;
        $state = $available ? "NOT " : "";

        $items_query = $db->prepare("SELECT SQL_CALC_FOUND_ROWS * FROM ".DB_TABLE_FAVORITES." fav JOIN ".DB_TABLE_ITEMS." itm ON itm.id = fav.itemId WHERE fav.userId = :userId AND itm.storageLocation IS ".$state."NULL ORDER BY fav.id DESC LIMIT :lim OFFSET :ofs");
        $items_query->bindParam(":userId", $userId);
        $items_query->bindParam(":lim", $limit, PDO::PARAM_INT);
        $items_query->bindParam(":ofs", $offset, PDO::PARAM_INT);
        if(!$items_query->execute()) {
            logEvent("Failed to get items by user favorites", json_encode($items_query->errorInfo()), EVENT_LOG_SEVERITY_CRITICAL, __FILE__, __LINE__, __FUNCTION__, true);
        }

        $items = $items_query->fetchAll(PDO::FETCH_OBJ);

        if($items != false){
            $found_rows = $db->query("SELECT FOUND_ROWS()")->fetch(PDO::FETCH_COLUMN);

            $max_page = ceil($found_rows / $limit) - 1;
        }else{
            $max_page = 0;
        }

        return (object) [
            "data" => $items == false ? [] : $items,
            "pagination" => (object) [
                "page" => $page,
                "max_page" => $max_page
            ]
        ];
    }

    function getItemCount(bool $available = true){
        global $db;

        $state = $available ? "NOT " : "";

        $item_count_query = $db->query("SELECT COUNT(*) FROM ".DB_TABLE_ITEMS." WHERE storageLocation IS ".$state."NULL");
        if(!$item_count_query->execute()) {
            logEvent("Failed to get item count", json_encode($item_count_query->errorInfo()), EVENT_LOG_SEVERITY_CRITICAL, __FILE__, __LINE__, __FUNCTION__, true);
        }

        return (int) $item_count_query->fetchColumn();
    }
?>