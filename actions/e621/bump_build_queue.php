<?php
    require($_SERVER["DOCUMENT_ROOT"]."/backend/init.php");

    checkCSRFToken(false);

    requireAuthentication();

    callListBuildProcessor();

    setPersistentMessage("List build queue bumped");
    header("Location: /e621/build/queue");
?>