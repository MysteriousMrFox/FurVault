<?php
    require($_SERVER["DOCUMENT_ROOT"]."/backend/init.php");

    checkCSRFToken(false);

    unset($_SESSION);
    session_unset();
    session_destroy();

    header("Location: /");
?>