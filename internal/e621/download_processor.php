<?php
    $require_vendor = true;

    function logCrashedDownload(){
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

        logEvent("Download Crashed", json_encode($error_report), EVENT_LOG_SEVERITY_CRITICAL, __FILE__, __LINE__, __FUNCTION__);
    }
    register_shutdown_function("logCrashedDownload");

    ignore_user_abort(true);
    set_time_limit(0);
    ini_set("memory_limit", "1024M");

    $disable_session = true;
    require($_SERVER["DOCUMENT_ROOT"]."/backend/init.php");

    if(!isset($_POST["key"]) || $_POST["key"] != INTERNAL_CALL_KEY){
        http_response_code(403);
        die();
    }

    $status = getCacheObject(CACHE_KEY_e621_DOWNLOAD_STATUS);

    if($status != CACHE_NO_OBJECT_DATA){
        http_response_code(409);
        finishWithJson([
            "data" => null,
            "status" => [
                "code" => 409,
                "message" => "Another download is in progress"
            ]
        ]);
    }

    $queue = getDownloadQueue();

    if($queue == false){
        http_response_code(409);
        finishWithJson([
            "data" => null,
            "status" => [
                "code" => 409,
                "message" => "No downloads are available in the queue"
            ]
        ]);
    }

    $download = $queue[0];

    $unpaused = false;

    if($download->pauseState != null){
        setCacheData(CACHE_KEY_e621_DOWNLOAD_STATUS, $download->pauseState);
        unpauseDownload($download->id);
        $unpaused = true;
    }else{
        setCacheObject(CACHE_KEY_e621_DOWNLOAD_STATUS, [
            "queue_id" => $download->buildQueueId,
            "status" => [
                "started" => EXECUTION_START_TIME,
                "updated" => EXECUTION_START_TIME
            ],
            "items" => [
                "available" => 0,
                "unavailable" => 0
            ],
            "current_item" => [
                "available" => 0,
                "unavailable" => 0
            ],
            "download" => [
                "thumbnail" => "",
                "md5" => "",
                "size" => 0,
                "current" => 0
            ]
        ]);
    }

    $build = getListBuildById($download->buildQueueId, true);

    if($build == false || $build->data == null){
        http_response_code(409);
        finishWithJson([
            "data" => null,
            "status" => [
                "code" => 409,
                "message" => "Completed list build failed to be retrieved"
            ]
        ]);
    }

    function downloadProgress($resource, $downloadSize, $downloaded, $uploadSize, $uploaded){
        global $status;

        $status->download->size = $downloadSize;
        $status->download->current = $downloaded;
        $status->status->updated = time();
        setCacheObject(CACHE_KEY_e621_DOWNLOAD_STATUS, $status);
    }

    $build_data = json_decode($build->data);

    $status = getCacheObject(CACHE_KEY_e621_DOWNLOAD_STATUS);
    
    if(!$unpaused){
        $status->items->available = $build_data->posts->available;
        $status->items->unavailable = $build_data->posts->unavailable;
    }

    $status->status->updated = time();
    setCacheObject(CACHE_KEY_e621_DOWNLOAD_STATUS, $status);

    while($status->current_item->available < $status->items->available){
        #region Cancel if requested
            $cancel = getCacheData(CACHE_KEY_e621_DOWNLOAD_CANCEL);

            if($cancel !== false){
                unsetCache(CACHE_KEY_e621_DOWNLOAD_CANCEL);
                unsetCache(CACHE_KEY_e621_DOWNLOAD_PAUSE);
                unsetCache(CACHE_KEY_e621_DOWNLOAD_STATUS);
                cancelListBuild($download->buildQueueId);
                cancelDownload($download->id);
                callDownloadProcessor();
                die();
            }
        #endregion

        #region Pause if requested
            $pause = getCacheData(CACHE_KEY_e621_DOWNLOAD_PAUSE);

            if($pause !== false){
                pauseDownload($download->id, $status);
                unsetCache(CACHE_KEY_e621_DOWNLOAD_STATUS);
                unsetCache(CACHE_KEY_e621_DOWNLOAD_CANCEL);
                unsetCache(CACHE_KEY_e621_DOWNLOAD_PAUSE);
                die();
            }
        #endregion

        #region Increment status position
            $status->current_item->available++;
            $status->status->updated = time();
            setCacheObject(CACHE_KEY_e621_DOWNLOAD_STATUS, $status);
        #endregion

        $post = $build_data->posts->data->available[$status->current_item->available - 1]; //Pick out current item to process

        #region Use provided download URL, or build one if not provided
            if($post->file->url == null && $post->file->md5 != null && $post->file->md5 != "" && $post->file->ext != null && $post->file->ext != ""){
                $post->file->url = "https://static1.e621.net/data/".substr($post->file->md5, 0, 2)."/".substr($post->file->md5, 2, 2)."/".$post->file->md5.".".$post->file->ext;
            }else if($post->file->url == null){
                createFailedDownload($post, "static_url_undetermined");
                setCacheData(CACHE_KEY_e621_DOWNLOAD_PAUSE, "");
                continue;
            }
        #endregion

        $item = getItemByMd5($post->file->md5); //Check items table for existing item with the provided md5

        if($item != false){
            //An item with the provided md5 exists, skip this item
            continue;
        }

        #region update status with current post info
            $status->download->thumbnail = $post->preview->url != null ? $post->preview->url : $post->file->url;
            $status->download->md5 = $post->file->md5;
            $status->status->updated = time();
            setCacheObject(CACHE_KEY_e621_DOWNLOAD_STATUS, $status);
        #endregion

        #region Download item and check the call status
            $download_call = curl_init($post->file->url);
            curl_setopt($download_call, CURLOPT_BUFFERSIZE, 64000);
            curl_setopt($download_call, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($download_call, CURLOPT_PROGRESSFUNCTION, "downloadProgress");
            curl_setopt($download_call, CURLOPT_NOPROGRESS, false);
            curl_setopt($download_call, CURLOPT_HEADER, false);
            curl_setopt($download_call, CURLOPT_USERAGENT, PRODUCT_NAME."/".PRODUCT_VERSION." (by ".PRODUCT_OPERATOR." on e621)");
            $download_data = curl_exec($download_call);
            $download_code = curl_getinfo($download_call, CURLINFO_HTTP_CODE);
            curl_close($download_call);

            if($download_code != 200){
                //Response code for item not 200, consider this download failed
                createFailedDownload($post, "http_code_invalid");
                setCacheData(CACHE_KEY_e621_DOWNLOAD_PAUSE, "");
                continue;
            }
        #endregion

        #region Compute item md5 and storage paths
            $md5 = md5($download_data);
            $md5_set1 = substr($md5, 0, 2);
            $md5_set2 = substr($md5, 2, 2);
            $filename = DATA_PATH_ITEMS."/".$md5_set1."/".$md5_set2."/".$md5.".".$post->file->ext;
            $preview_filename = DATA_PATH_ITEM_PREVIEWS."/".$md5_set1."/".$md5_set2."/".$md5.".png";
        #endregion

        if($post->file->md5 != $md5){
            //The downloaded file is corrupt, consider this download failed

            createFailedDownload($post, "md5_mismatch");
            setCacheData(CACHE_KEY_e621_DOWNLOAD_PAUSE, "");
            continue;
        }

        #region Check and create storage paths
            //Ensure md5 sorting directories exist for item
            if(!is_dir(DATA_PATH_ITEMS."/".$md5_set1)){
                mkdir(DATA_PATH_ITEMS."/".$md5_set1);
            }

            if(!is_dir(DATA_PATH_ITEMS."/".$md5_set1."/".$md5_set2)){
                mkdir(DATA_PATH_ITEMS."/".$md5_set1."/".$md5_set2);
            }

            //Ensure md5 sorting directories exist for preview
            if(!is_dir(DATA_PATH_ITEM_PREVIEWS."/".$md5_set1)){
                mkdir(DATA_PATH_ITEM_PREVIEWS."/".$md5_set1);
            }

            if(!is_dir(DATA_PATH_ITEM_PREVIEWS."/".$md5_set1."/".$md5_set2)){
                mkdir(DATA_PATH_ITEM_PREVIEWS."/".$md5_set1."/".$md5_set2);
            }
        #endregion

        #region Generate file preview
            //Check if this file type can be previewed

            $defer_preview_ffmpeg = false;

            if(in_array($post->file->ext, [
                "png",
                "jpg",
                "jpeg",
                "gif",
                "webp"
            ])){
                //File type is an image and can be previewed easily

                #region Generate image preview
                    $preview_image = imagecreatefromstring($download_data); //Load source image

                    if($preview_image == false){
                        //Image load into PHP failed, consider this download failed

                        createFailedDownload($post, "preview_import_failed");
                        setCacheData(CACHE_KEY_e621_DOWNLOAD_PAUSE, "");
                        continue;
                    }

                    //Enable transparency of source image
                    imagealphablending($preview_image, true);
                    imagesavealpha($preview_image, true);
        
                    $item_size = getimagesizefromstring($download_data); //Calculate source image original size
        
                    if($item_size[0] <= 200){
                        //Image is already small, just convert the format
                        
                        //Use buffer to hack PHP's crappy inability to output raw image data to a string and force that to happen
                        ob_start();
                        imagepng($preview_image);
                        $preview_data = ob_get_contents();
                        ob_end_clean();
                    }else{
                        //Image too big, scale it
                        $width_ratio = 200 / $item_size[0];
                        $ratio = min($width_ratio, 1);
                        
                        //Calculate scaled size maintaining aspect ratio
                        $new_width  = (int) $item_size[0]  * $ratio;
                        $new_height = (int) $item_size[1] * $ratio;
        
                        $preview_image_rebuilt = imagecreatetruecolor($new_width, $new_height); //Create destination image

                        //Enable transparency of destination image
                        imagealphablending($preview_image_rebuilt, true);
                        imagesavealpha($preview_image_rebuilt, true);

                        imagecopyresampled($preview_image_rebuilt, $preview_image, 0, 0, 0, 0, $new_width, $new_height, $item_size[0], $item_size[1]); //Map source image to destination image
        
                        //Use buffer to hack PHP's crappy inability to output raw image data to a string and force that to happen
                        ob_start();
                        imagepng($preview_image_rebuilt);
                        $preview_data = ob_get_contents();
                        ob_end_clean();
        
                        imagedestroy($preview_image_rebuilt); //Unload the destination image from memory now it has been captured
                    }

                    imagedestroy($preview_image); //Unload the source image from memory now it has been used
                #endregion
            }else if(in_array($post->file->ext, [
                "webm",
                "mp4"
            ])){
                //File type is a video, capture and manipulate a frame

                $defer_preview_ffmpeg = true;
            }else{
                //Preview cannot be generated, make a placeholder

                #region Generate placeholder image
                    //Configure font
                    $font = $_SERVER["DOCUMENT_ROOT"]."/backend/BalsamiqSans-Bold.ttf";
                    $font_size = 50;
                    $angle = 0;

                    //Allocate new image parts
                    $placeholder_image = imagecreate(200, 200);
                    $black = imagecolorallocate($placeholder_image, 0, 0, 0);

                    $text = strtoupper($post->file->ext); //Grab text to be rendered
                    
                    //Grab base image size
                    $placeholder_image_width = imagesx($placeholder_image);  
                    $placeholder_image_height = imagesy($placeholder_image);

                    $text_box = imagettfbbox($font_size, $angle, $font, $text); //Generate text box

                    //Grab text box size
                    $text_width = $text_box[2] - $text_box[0];
                    $text_height = $text_box[7] - $text_box[1];

                    //Map text box to center of base image
                    $x = ($placeholder_image_width / 2) - ($text_width / 2);
                    $y = ($placeholder_image_height / 2 ) - ($text_height / 2);

                    imagettftext($placeholder_image, $font_size, 0, $x, $y, $black, $font, $text); //Render text box

                    //Use buffer to hack PHP's crappy inability to output raw image data to a string and force that to happen
                    ob_start();
                    imagepng($placeholder_image);
                    $preview_data = ob_get_contents();
                    ob_end_clean();
                    imagedestroy($placeholder_image);
                #endregion
            }

            #region Save preview file
                if(!$defer_preview_ffmpeg){
                    $preview_savefile = fopen($preview_filename, "w");

                    if($preview_savefile == false || fwrite($preview_savefile, $preview_data) === false){
                        //File preview save failed, consider this download failed
                        fclose($preview_savefile);
                        createFailedDownload($post, "file_preview_save_failed");
                        setCacheData(CACHE_KEY_e621_DOWNLOAD_PAUSE, "");
                        continue;
                    }

                    fclose($preview_savefile);

                    if(!file_exists($preview_filename)){
                        createFailedDownload($post, "file_preview_save_postcheck_failed");
                        setCacheData(CACHE_KEY_e621_DOWNLOAD_PAUSE, "");
                        continue;
                    }
                }
            #endregion
        #endregion

        #region Save item
            $savefile = fopen($filename, "w");

            if($savefile == false || fwrite($savefile, $download_data) == false){
                //File save failed, consider this download failed

                fclose($savefile);
                createFailedDownload($post, "file_save_failed");
                setCacheData(CACHE_KEY_e621_DOWNLOAD_PAUSE, "");
                continue;
            }

            fclose($savefile);

            if(!file_exists($filename)){
                createFailedDownload($post, "file_save_postcheck_failed");
                setCacheData(CACHE_KEY_e621_DOWNLOAD_PAUSE, "");
                continue;
            }
        #endregion

        #region Check for deferred preview and generate if applicable
            $call_continue_after_ffmpeg_failure = false;

            if($defer_preview_ffmpeg){
                try{
                    $ffmpeg = FFMpeg\FFMpeg::create();
                    $video = $ffmpeg->open($filename);

                    $ffprobe = FFMpeg\FFProbe::create();
                    $duration = $ffprobe->format($filename)->get("duration");

                    $frame = $video->frame(FFMpeg\Coordinate\TimeCode::fromSeconds(floor($duration / 2)));
                    $frame->save($preview_filename);

                    if(!file_exists($preview_filename)){
                        createFailedDownload($post, "file_preview_ffmpeg_save_postcheck_failed");
                        setCacheData(CACHE_KEY_e621_DOWNLOAD_PAUSE, "");
                        $call_continue_after_ffmpeg_failure = true;
                    }
                }catch(exception $e){
                    //Delete savefiles as they will not be linked to the DB
                    unlink($filename);
                    unlink($preview_filename);
                    
                    createFailedDownload($post, "file_preview_ffmpeg_save_failed_".get_class($e));
                    setCacheData(CACHE_KEY_e621_DOWNLOAD_PAUSE, "");
                    $call_continue_after_ffmpeg_failure = true;
                }
            }

            if($call_continue_after_ffmpeg_failure){
                continue;
            }
        #endregion

        #region Compute tags
            $tags = [];

            foreach($post->tags as $type => $tagset){
                foreach($tagset as $tag){
                    array_push($tags, (object) [
                        "type" => $type,
                        "name" => $tag
                    ]);
                }
            }

            array_push($tags, (object) [
                "type" => "rating",
                "name" => $post->rating
            ]);
        #endregion
        
        #region Compute metadata
            $metadata = [
                ["e621", base64_encode(json_encode($post))],
                ["e621/id", $post->id],
                ["e621/md5", $post->file->md5],
                ["e621/ext", $post->file->ext],
                ["e621/static", $post->file->url],
                ["description", $post->description]
            ];

            if($post->preview->url != null){
                array_push($metadata, ["e621/preview", $post->preview->url]);
            }

            if($post->relationships->parent_id != null){
                array_push($metadata, ["e621/parent", $post->relationships->parent_id]);
                array_push($metadata, ["e621/parent_linked", "false"]);
            }

            foreach($post->relationships->children as $child){
                array_push($metadata, ["e621/child", $child]);
            }

            if(count($post->relationships->children) != 0){
                array_push($metadata, ["e621/children_linked", "false"]);
            }

            foreach($post->pools as $pool){
                array_push($metadata, ["e621/pool", $pool]);
            }

            if(count($post->pools) != 0){
                array_push($metadata, ["e621/pools_linked", "false"]);
            }

            foreach($post->sources as $source){
                array_push($metadata, ["e621/source", $source]);
            }
        #endregion

        #region Create item
            if(createItem($md5, $md5_set1."/".$md5_set2."/".$md5.".".$post->file->ext, $md5_set1."/".$md5_set2."/".$md5.".png", $tags, $metadata) == false){
                //Delete savefiles as it is not linked to the DB anymore
                unlink($filename);
                unlink($preview_filename);

                createFailedDownload($post, "item_create_failed");
                setCacheData(CACHE_KEY_e621_DOWNLOAD_PAUSE, "");
            }
        #endregion
    }

    while($status->current_item->unavailable < $status->items->unavailable){
        #region Cancel if requested
            $cancel = getCacheData(CACHE_KEY_e621_DOWNLOAD_CANCEL);

            if($cancel !== false){
                unsetCache(CACHE_KEY_e621_DOWNLOAD_CANCEL);
                unsetCache(CACHE_KEY_e621_DOWNLOAD_PAUSE);
                unsetCache(CACHE_KEY_e621_DOWNLOAD_STATUS);
                cancelListBuild($download->buildQueueId);
                cancelDownload($download->id);
                callDownloadProcessor();
                die();
            }
        #endregion

        #region Pause if requested
            $pause = getCacheData(CACHE_KEY_e621_DOWNLOAD_PAUSE);

            if($pause !== false){
                unsetCache(CACHE_KEY_e621_DOWNLOAD_CANCEL);
                unsetCache(CACHE_KEY_e621_DOWNLOAD_PAUSE);
                unsetCache(CACHE_KEY_e621_DOWNLOAD_STATUS);
                pauseDownload($download->id, $status);
                die();
            }
        #endregion

        #region Increment status position
            $status->current_item->unavailable++;
            $status->status->updated = time();
            setCacheObject(CACHE_KEY_e621_DOWNLOAD_STATUS, $status);
        #endregion

        $post = $build_data->posts->data->unavailable[$status->current_item->unavailable - 1]; //Pick out current item to process

        $md5 = "";
        
        if($post->file->md5 != null && $post->file->md5 != ""){
            $item = getItemByMd5($post->file->md5); //Check items table for existing item with the provided md5

            if($item != false){
                //An item with the provided md5 exists, skip this item
                continue;
            }

            $md5 = $post->file->md5;
        }

        #region update status with current post info
            $status->download->thumbnail = "";
            $status->download->md5 = $md5;
            $status->status->updated = time();
            setCacheObject(CACHE_KEY_e621_DOWNLOAD_STATUS, $status);
        #endregion

        #region Compute tags
            $tags = [];

            foreach($post->tags as $type => $tagset){
                foreach($tagset as $tag){
                    array_push($tags, (object) [
                        "type" => $type,
                        "name" => $tag
                    ]);
                }
            }

            array_push($tags, (object) [
                "type" => "rating",
                "name" => $post->rating
            ]);
        #endregion
        
        #region Compute metadata
            $metadata = [
                ["e621", base64_encode(json_encode($post))],
                ["e621/id", $post->id],
                ["e621/md5", $post->file->md5 != null ? $post->file->md5 : ""],
                ["e621/ext", $post->file->ext != null ? $post->file->ext : ""],
                ["description", $post->description]
            ];

            if($post->preview->url != null){
                array_push($metadata, ["e621/preview", $post->preview->url]);
            }

            if($post->relationships->parent_id != null){
                array_push($metadata, ["e621/parent", $post->relationships->parent_id]);
                array_push($metadata, ["e621/parent_linked", "false"]);
            }

            foreach($post->relationships->children as $child){
                array_push($metadata, ["e621/child", $child]);
            }

            if(count($post->relationships->children) != 0){
                array_push($metadata, ["e621/children_linked", "false"]);
            }

            foreach($post->pools as $pool){
                array_push($metadata, ["e621/pool", $pool]);
            }

            if(count($post->pools) != 0){
                array_push($metadata, ["e621/pools_linked", "false"]);
            }

            foreach($post->sources as $source){
                array_push($metadata, ["e621/source", $source]);
            }
        #endregion

        #region Create item
            if(createItem($md5, null, null, $tags, $metadata) == false){
                createFailedDownload($post, "unavailable_item_create_failed");
            }
        #endregion
    }

    markDownloadComplete($download->id);
    unsetCache(CACHE_KEY_e621_DOWNLOAD_STATUS);
    unsetCache(CACHE_KEY_e621_DOWNLOAD_CANCEL);
    unsetCache(CACHE_KEY_e621_DOWNLOAD_PAUSE);

    callDownloadProcessor();
?>