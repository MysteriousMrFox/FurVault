<?php
    function logCrashedBuild(){
        $error_report = error_get_last();

        if($error_report == null || in_array($error_report["type"], [
            2, //Warning
            8, //Notice
            32, //Core Warning
            128, //Compile Warning
            512, //User Warning
            1024 //User Notice
        ])){
            return;
        }

        logEvent("Build Crashed", json_encode($error_report), EVENT_LOG_SEVERITY_CRITICAL, __FILE__, __LINE__, __FUNCTION__);
    }
    register_shutdown_function("logCrashedBuild");

    ignore_user_abort(true);
    set_time_limit(0);
    ini_set("memory_limit", "1024M");

    $disable_session = true;
    require($_SERVER["DOCUMENT_ROOT"]."/backend/init.php");

    if(!isset($_POST["key"]) || $_POST["key"] != INTERNAL_CALL_KEY){
        http_response_code(403);
        die();
    }

    $status = getCacheObject(CACHE_KEY_e621_LIST_BUILD_STATUS);

    if($status != CACHE_NO_OBJECT_DATA){
        http_response_code(409);
        finishWithJson([
            "data" => null,
            "status" => [
                "code" => 409,
                "message" => "Another build is in progress"
            ]
        ]);
    }

    $queue = getListBuildQueue(false, true);

    if($queue == false){
        http_response_code(409);
        finishWithJson([
            "data" => null,
            "status" => [
                "code" => 409,
                "message" => "No builds are available in the queue"
            ]
        ]);
    }

    $latest_completed = getLatestCompletedListBuild($queue[0]->search);
    $page_mode = $latest_completed == false ? "b" : "a";

    $status = [
        "queue_id" => (int) $queue[0]->id,
        "name" => $queue[0]->name,
        "search" => $queue[0]->search,
        "status" => [
            "started" => EXECUTION_START_TIME,
            "updated" => EXECUTION_START_TIME,
            "finished" => 0
        ],
        "posts" => [
            "data" => [
                "available" => [],
                "unavailable" => []
            ],
            "available" => 0,
            "unavailable" => 0,
            "newest" => [
                "available" => 0,
                "unavailable" => 0
            ],
            "oldest" => [
                "available" => 0,
                "unavailable" => 0
            ]
        ],
        "page_calls" => [
            "available" => [],
            "unavailable" => []
        ],
        "next_page" => [
            "available" => $latest_completed == false ? 0 : $latest_completed->newestAvailablePost,
            "unavailable" => $latest_completed == false ? 0 : $latest_completed->newestUnavailablePost
        ]
    ];

    setCacheObject(CACHE_KEY_e621_LIST_BUILD_STATUS, $status);
    $status = getCacheObject(CACHE_KEY_e621_LIST_BUILD_STATUS);

    $mini_status = [
        "queue_id" => (int) $queue[0]->id,
        "name" => $queue[0]->name,
        "search" => $queue[0]->search,
        "status" => [
            "started" => EXECUTION_START_TIME,
            "updated" => EXECUTION_START_TIME,
            "finished" => 0
        ],
        "posts" => [
            "available" => 0,
            "unavailable" => 0,
            "newest" => [
                "available" => 0,
                "unavailable" => 0
            ],
            "oldest" => [
                "available" => 0,
                "unavailable" => 0
            ]
        ]
    ];

    setCacheObject(CACHE_KEY_e621_LIST_BUILD_MINI_STATUS, $mini_status);
    $mini_status = getCacheObject(CACHE_KEY_e621_LIST_BUILD_MINI_STATUS);

    while(true){
        //Record available

        sleep(1); //Keep to e621 rate limit

        $cancel = getCacheData(CACHE_KEY_e621_LIST_BUILD_CANCEL);

        if($cancel !== false){
            unsetCache(CACHE_KEY_e621_LIST_BUILD_CANCEL);
            unsetCache(CACHE_KEY_e621_LIST_BUILD_STATUS);
            unsetCache(CACHE_KEY_e621_LIST_BUILD_MINI_STATUS);
            cancelListBuild($queue[0]->id);
            callListBuildProcessor(); //Start next queue item if available
            die();
        }

        $call_data = (object) [
            "page_limit" => DOWNLOAD_PAGE_LIMIT,
            "page" => $page_mode.$status->next_page->available,
            "start_time" => time(),
            "end_time" => 0
        ];


        $page = e621GetPostPage($queue[0]->search, $page_mode.$status->next_page->available, DOWNLOAD_PAGE_LIMIT);

        if($page->status != 200){
            unsetCache(CACHE_KEY_e621_LIST_BUILD_CANCEL);
            unsetCache(CACHE_KEY_e621_LIST_BUILD_STATUS);
            unsetCache(CACHE_KEY_e621_LIST_BUILD_MINI_STATUS);
            cancelListBuild($queue[0]->id);
            logEvent("Build Auto Cancelled", "e621 returned a non-200 status code when requesting a page", EVENT_LOG_SEVERITY_CRITICAL, __FILE__, __LINE__, __FUNCTION__);
            die();
        }

        $call_data->end_time = time();
        array_push($status->page_calls->available, $call_data);

        if(count($page->data->posts) == 0){
            $status->status->updated = time();
            setCacheObject(CACHE_KEY_e621_LIST_BUILD_STATUS, $status);

            $mini_status = json_decode(json_encode($status));
            unset($mini_status->posts->data);
            unset($mini_status->page_calls);
            unset($mini_status->next_page);
            setCacheObject(CACHE_KEY_e621_LIST_BUILD_MINI_STATUS, $mini_status);

            break;
        }

        $status->next_page->available = $latest_completed == false ? $page->data->posts[count($page->data->posts) - 1]->id : $page->data->posts[0]->id;
        
        $status->posts->data->available = array_merge($status->posts->data->available, $page->data->posts);
        
        $status->posts->available += count($page->data->posts);

        $status->posts->newest->available = $status->posts->newest->available == 0 ? $page->data->posts[0]->id : $status->posts->newest->available;
        $status->posts->oldest->available = $page->data->posts[count($page->data->posts) - 1]->id;

        $status->status->updated = time();
        setCacheObject(CACHE_KEY_e621_LIST_BUILD_STATUS, $status);

        $mini_status = json_decode(json_encode($status));
        unset($mini_status->posts->data);
        unset($mini_status->page_calls);
        unset($mini_status->next_page);
        setCacheObject(CACHE_KEY_e621_LIST_BUILD_MINI_STATUS, $mini_status);
    }

    if(strpos($_POST["search"], "status:") === false){
        //If the download is not status biased

        while(true){
            //Record unavailable
    
            sleep(1); //Keep to e621 rate limit
    
            $cancel = getCacheData(CACHE_KEY_e621_LIST_BUILD_CANCEL);

            if($cancel !== false){
                unsetCache(CACHE_KEY_e621_LIST_BUILD_CANCEL);
                unsetCache(CACHE_KEY_e621_LIST_BUILD_STATUS);
                unsetCache(CACHE_KEY_e621_LIST_BUILD_MINI_STATUS);
                cancelListBuild($queue[0]->id);
                callListBuildProcessor(); //Start next queue item if available
                die();
            }
            
            $call_data = (object) [
                "page_limit" => DOWNLOAD_PAGE_LIMIT,
                "page" => $page_mode.$status->next_page->unavailable,
                "start_time" => time(),
                "end_time" => 0
            ];

            $page = e621GetPostPage($queue[0]->search." status:deleted", $page_mode.$status->next_page->unavailable, DOWNLOAD_PAGE_LIMIT);

            if($page->status != 200){
                unsetCache(CACHE_KEY_e621_LIST_BUILD_CANCEL);
                unsetCache(CACHE_KEY_e621_LIST_BUILD_STATUS);
                unsetCache(CACHE_KEY_e621_LIST_BUILD_MINI_STATUS);
                cancelListBuild($queue[0]->id);
                logEvent("Build Auto Cancelled", "e621 returned a non-200 status code when requesting a page", EVENT_LOG_SEVERITY_CRITICAL, __FILE__, __LINE__, __FUNCTION__);
                die();
            }

            $call_data->end_time = time();
            array_push($status->page_calls->unavailable, $call_data);
    
            if(count($page->data->posts) == 0){
                $status->status->updated = time();
                setCacheObject(CACHE_KEY_e621_LIST_BUILD_STATUS, $status);

                $mini_status = json_decode(json_encode($status));
                unset($mini_status->posts->data);
                unset($mini_status->page_calls);
                unset($mini_status->next_page);
                setCacheObject(CACHE_KEY_e621_LIST_BUILD_MINI_STATUS, $mini_status);

                break;
            }
    
            $status->next_page->unavailable = $latest_completed == false ? $page->data->posts[count($page->data->posts) - 1]->id : $page->data->posts[0]->id;
            
            $status->posts->data->unavailable = array_merge($status->posts->data->unavailable, $page->data->posts);
            
            $status->posts->unavailable += count($page->data->posts);

            $status->posts->newest->unavailable = $status->posts->newest->unavailable == 0 ? $page->data->posts[0]->id : $status->posts->newest->unavailable;
            $status->posts->oldest->unavailable = $page->data->posts[count($page->data->posts) - 1]->id;

            $status->status->updated = time();
            setCacheObject(CACHE_KEY_e621_LIST_BUILD_STATUS, $status);

            $mini_status = json_decode(json_encode($status));
            unset($mini_status->posts->data);
            unset($mini_status->page_calls);
            unset($mini_status->next_page);
            setCacheObject(CACHE_KEY_e621_LIST_BUILD_MINI_STATUS, $mini_status);
        }
    }

    $status->status->finished = time();
    setCacheObject(CACHE_KEY_e621_LIST_BUILD_STATUS, $status);

    $mini_status = json_decode(json_encode($status));
    unset($mini_status->posts->data);
    unset($mini_status->page_calls);
    unset($mini_status->next_page);
    setCacheObject(CACHE_KEY_e621_LIST_BUILD_MINI_STATUS, $mini_status);

    $newest_available_post = $status->posts->newest->available != 0 ? $status->posts->newest->available : ($latest_completed != null ? $latest_completed->newestAvailablePost : 0);
    $oldest_available_post = $status->posts->oldest->available != 0 ? $status->posts->newest->available : ($latest_completed != null ? $latest_completed->oldestAvailablePost : 0);
    $newest_unavailable_post = $status->posts->newest->unavailable != 0 ? $status->posts->newest->unavailable : ($latest_completed != null ? $latest_completed->newestUnavailablePost : 0);
    $oldest_unavailable_post = $status->posts->oldest->unavailable != 0 ? $status->posts->oldest->unavailable : ($latest_completed != null ? $latest_completed->oldestUnavailablePost : 0);
    
    updateListBuildData($queue[0]->id, $status, $newest_available_post, $oldest_available_post, $newest_unavailable_post, $oldest_unavailable_post, $status->posts->available, $status->posts->unavailable);
    queueDownload($queue[0]->id);

    unsetCache(CACHE_KEY_e621_LIST_BUILD_STATUS);
    unsetCache(CACHE_KEY_e621_LIST_BUILD_MINI_STATUS);
    unsetCache(CACHE_KEY_e621_LIST_BUILD_CANCEL);

    callDownloadProcessor();
    callListBuildProcessor();
?>