<?php
    require($_SERVER["DOCUMENT_ROOT"]."/backend/init.php");

    if(!isAuthenticated()){
        http_response_code(403);
    }
?>