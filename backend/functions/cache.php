<?php
    function useCache(){
        global $_CACHE;

        if(isset($_CACHE) && $_CACHE != null){
            return;
        }

        $_CACHE = new Memcached();
        $_CACHE->addServer(CACHE_HOST, CACHE_PORT);
    }

    function getCacheData(string $key){
        global $_CACHE;

        return $_CACHE->get(CACHE_PREFIX.$key);
    }

    function getCacheObject(string $key){
        $data = getCacheData($key);

        if($data == false){
            return CACHE_NO_OBJECT_DATA;
        }

        return json_decode($data);
    }

    function setCacheData(string $key, string $value){
        global $_CACHE;

        $_CACHE->set(CACHE_PREFIX.$key, $value);
    }

    function setCacheObject(string $key, $value){
        setCacheData($key, json_encode($value));
    }

    function unsetCache(string $key){
        global $_CACHE;

        $_CACHE->delete(CACHE_PREFIX.$key);
    }
?>