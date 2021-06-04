<?php
    function e621GetPostPage(string $search, string $page = "", int $limit = 1){
        $page_append = $page == "" || $page == "0" || $page == "a0" || $page == "b0" ? "" : "&page=".$page;

        $page_call = curl_init("https://e621.net/posts.json?limit=".$limit.$page_append."&tags=".urlencode($search));
        curl_setopt($page_call, CURLOPT_USERAGENT, PRODUCT_NAME."/".PRODUCT_VERSION." (by ".PRODUCT_OPERATOR." on e621)");
        curl_setopt($page_call, CURLOPT_RETURNTRANSFER, true);
        $page = curl_exec($page_call);
        $page_status = curl_getinfo($page_call, CURLINFO_HTTP_CODE);
        $page_error = curl_error($page_call);
        curl_close($page_call);

        return (object)[
            "status" => $page_status,
            "error" =>  $page_error,
            "data" => json_decode($page)
        ];
    }
?>