<?php
    //Note on e621 bandwidth limits https://e621.net/forum_topics/27779

    // +--------+
    // | System |
    // +--------+

    define("DEBUG", false);
    define("INTERNAL_CALL_HOST", "localhost");
    define("INTERNAL_CALL_HTTPS", false);
    define("INTERNAL_CALL_KEY", "Generate a long random string and place it in here");
    define("DOWNLOAD_PAGE_LIMIT", 320);
    define("MAX_PAGINATION", 10);
    define("PRODUCT_OPERATOR", "Put your e621 pseudonym here! Used for the user agent");
    

    // +----------+
    // | Database |
    // +----------+

    define("DB_HOST", "127.0.0.1");
    define("DB_USERNAME", "Set your MySQL database username here");
    define("DB_PASSWORD", "Set your MySQL database password here");
    define("DB_NAME", "Set your MySQL database name here");
    

    define("DB_TABLE_EVENT_LOG", "EventLog");

    define("DB_TABLE_USERS", "Users");
    define("DB_TABLE_PERMISSIONS", "Permissions");
    define("DB_TABLE_FAVORITES", "Favorites");

    define("DB_TABLE_ITEMS", "Items");
    define("DB_TABLE_ITEM_METADATA", "ItemMetadata");
    
    define("DB_TABLE_TAGS", "Tags");
    define("DB_TABLE_ITEM_TAG_MAPPINGS", "ItemTagMappings");

    define("DB_TABLE_BUILD_QUEUE", "BuildQueue");
    define("DB_TABLE_DOWNLOAD_QUEUE", "DownloadQueue");
    define("DB_TABLE_FAILED_DOWNLOADS", "FailedDownloads");


    // +---------+
    // | Logging |
    // +---------+

    define("EVENT_LOG_HALT_MESSAGE", "Something went wrong. Please try again.");

    
    // +---------+
    // | Cookies |
    // +---------+

    define("COOKIE_EXPIRE", 3600);
    define("COOKIE_SESSION", "token");
    define("COOKIE_MESSAGE", "message");
    define("COOKIE_ERROR", "error");
    define("COOKIE_WARNING", "warning");

    
    // +---------+
    // | Session |
    // +---------+

    define("SESSION_LOGGEDIN", "LoggedIn");
    define("SESSION_USERID", "UserId");
    define("SESSION_CSRF", "CrossSiteRequestForgeryToken");


    // +-------+
    // | Cache |
    // +-------+

    define("CACHE_HOST", "127.0.0.1");
    define("CACHE_PORT", 11211);
    define("CACHE_PREFIX", "furvault__");

    define("CACHE_KEY_e621_LIST_BUILD_STATUS", "e621_list_build_status");
    define("CACHE_KEY_e621_LIST_BUILD_MINI_STATUS", "e621_list_build_mini_status");
    define("CACHE_KEY_e621_LIST_BUILD_CANCEL", "e621_list_build_cancel");
    define("CACHE_KEY_e621_DOWNLOAD_STATUS", "e621_download_status");
    define("CACHE_KEY_e621_DOWNLOAD_CANCEL", "e621_download_cancel");
    define("CACHE_KEY_e621_DOWNLOAD_PAUSE", "e621_download_pause");


    // +-------------+
    // | Permissions |
    // +-------------+

    define("VALID_PERMISSIONS", [
        "canLogIn" => "User account is enabled"
    ]);
    

    // +------------------+
    // | Data Directories |
    // +------------------+

    define("DATA_PATH_ITEMS", $_SERVER["DOCUMENT_ROOT"]."/data/items");
    define("DATA_PATH_ITEM_PREVIEWS", $_SERVER["DOCUMENT_ROOT"]."/data/previews");


    // +-------+
    // | Fancy |
    // +-------+

    define("TAG_TYPE_COLOURS", [
        "artist" => [
            "normal" => "#F2AC08",
            "hover" => "#FBD67F"
        ],
        "character" => [
            "normal" => "#00AA00",
            "hover" => "#2BFF2B"
        ],
        "species" => [
            "normal" => "#ED5D1F",
            "hover" => "#F6B295"
        ],
        "copyright" => [
            "normal" => "#DD00DD",
            "hover" => "#FF5EFF"
        ],
        "meta" => [
            "normal" => "#000000",
            "hover" => "#555555"
        ]
    ]);
?>