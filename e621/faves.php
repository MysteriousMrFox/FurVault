<?php
    require($_SERVER["DOCUMENT_ROOT"]."/backend/init.php");

    requireAuthentication();

    $page_title = "Import e621 Favorites";
?>
<!DOCTYPE html>
<html>
    <head>
        <?php require($_SERVER["DOCUMENT_ROOT"]."/components/head.php"); ?>
    </head>
    <body>
        <?php require($_SERVER["DOCUMENT_ROOT"]."/components/nav.php"); ?>
        <main class="container mt-4">
            <?php require($_SERVER["DOCUMENT_ROOT"]."/components/cookie_message_banners.php"); ?>
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Import Favorites</h5>

                    <div class="input-group mt-4 mb-3">
                        <span class="input-group-text">@</span>
                        <input type="text" class="form-control" id="username" placeholder="Username">
                    </div>
                    <button type="button" class="btn btn-primary" data-build-button onclick="document.querySelectorAll('[data-build-button]').forEach(element => element.classList.add('disabled')); buildDownloadList(CSRF, 'fav:' + document.querySelector('#username').value, 'Import Favorites for ' + document.querySelector('#username').value);">Build Download List</button>
                </div>
            </div>
        </main>
        <?php require($_SERVER["DOCUMENT_ROOT"]."/components/footer.php"); ?>
        <?php require($_SERVER["DOCUMENT_ROOT"]."/components/scripts.php"); ?>
        <script>const CSRF = "<?php print($_SESSION[SESSION_CSRF]); ?>";</script>
        <script src="/js/e621.min.js"></script>
    </body>
</html>