<?php
    function finishWithJson($mixed){
        header("Content-Type: application/json");
        die(json_encode($mixed));
    }
?>