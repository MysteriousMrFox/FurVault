<?php
    require($_SERVER["DOCUMENT_ROOT"]."/backend/init.php");
    
    checkCSRFToken(false);

    requireAuthentication();

    $random = pickRandomItem();

    if($random == false){
        setPersistentWarning("A random item could not be picked");
        header("Location: /home");
        die();
    }

    header("Location: /gallery/view/".$random->id);
?>