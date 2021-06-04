<?php
    function endsWith($haystack, $needle) {
        $length = strlen($needle);
        
        if ($length == 0) {
            return true;
        }

        return (substr($haystack, -$length) === $needle);
    }

    function getMimeFromFilename(string $filename){
        if(endsWith($filename, "jpg") || endsWith($filename, "jpeg")){
            return "image/jpeg";
        }else if(endsWith($filename, "png")){
            return "image/png";
        }else if(endsWith($filename, "gif")){
            return "image/gif";
        }else if(endsWith($filename, "webm")){
            return "video/webm";
        }else if(endsWith($filename, "webp")){
            return "image/webp";
        }else if(endsWith($filename, "swf")){
            return "application/x-shockwave-flash";
        }else{
            return false;
        }
    }
?>