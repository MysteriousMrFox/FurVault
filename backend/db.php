<?php
    try{
        $db = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8", DB_USERNAME, DB_PASSWORD);
    }catch(PDOException $e){
        die("Database error: ".$e->getMessage()."<br/>");
    }
?>