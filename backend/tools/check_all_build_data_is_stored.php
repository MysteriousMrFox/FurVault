<?php
    // +-----------------------------------------------------------------------------+
    // | Checks that all results from all builds have been stored in the Items table |
    // +-----------------------------------------------------------------------------+

    //Requires a table called tempmd5 to exist: INT ID, TEXT md5Checksum, BIT available

    //Recommended query after running is SELECT md5Checksum FROM tempmd5 WHERE md5Checksum NOT IN (SELECT md5Checksum FROM Items)
    //Remember to ignore any results what appear which are in FailedDownloads

    set_time_limit(0);
    ini_set("memory_limit", "2048M");
    ini_set("display_errors", true);
    ini_set("display_startup_errors", true);
    error_reporting(E_ALL);

    require($_SERVER["DOCUMENT_ROOT"]."/backend/init.php");

    print("Read<br>"); ob_flush(); flush();

    $builds_query = $db->query("SELECT data FROM ".DB_TABLE_BUILD_QUEUE);
    $builds_query->execute();
    $builds = $builds_query->fetchAll(PDO::FETCH_OBJ);

    $datasets = [];

    print("Decode<br>"); ob_flush(); flush();

    foreach($builds as $build){
        array_push($datasets, json_decode($build->data));
    }

    $md5s = [];

    print("Prepare<br>"); ob_flush(); flush();

    foreach($datasets as $dataset){
        foreach($dataset->posts->data->available as $post){
            array_push($md5s, ["md5" => $post->file->md5, "available" => true]);
        }

        foreach($dataset->posts->data->unavailable as $post){
            array_push($md5s, ["md5" => $post->file->md5, "available" => false]);
        }
    }

    print("Write<br>"); ob_flush(); flush();

    foreach($md5s as $md5){
        $md5_insert_query = $db->prepare("INSERT INTO tempmd5 (`md5Checksum`, `available`) VALUES (:md5, :ava)");
        $md5_insert_query->bindParam(":md5", $md5["md5"]);
        $md5_insert_query->bindParam(":ava", $md5["available"], PDO::PARAM_BOOL);
        $md5_insert_query->execute();
    }

    print("Done");
?>