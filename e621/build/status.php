<?php
    require($_SERVER["DOCUMENT_ROOT"]."/backend/init.php");

    requireAuthentication();

    $page_title = "e621 Build Status";
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
                    <h5 class="card-title">Build Status</h5>

                    <div class="mt-4" data-statusbox="loading">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>

                    <div class="mt-4" style="display: none;" data-statusbox="none">
                        <span class="badge bg-secondary fs-5">INACTIVE</span>
                    </div>

                    <div class="mt-4" style="display: none;" data-statusbox="status">
                        <span class="badge bg-primary fs-5 mb-4">ACTIVE</span>

                        <div class="fs-5 mb-4"><span id="status-name"></span> <code>[<span id="status-search"></span>]</code></div>

                        <div class="mb-1 mt-0 fs-5"><span class="badge bg-success">Available <span id="status-available" class="badge bg-light text-dark"></span></span></div>
                        <div class="mb-1 fs-5"><span class="badge bg-danger">Unavailable <span id="status-unavailable" class="badge bg-light text-dark"></span></span></div>
                        <div class="mb-1 fs-5"><span class="badge bg-primary">Total <span id="status-total" class="badge bg-light text-dark"></span></span></div>
                        <div class="mb-1 fs-5"><span class="badge bg-secondary">Run Time <span class="badge bg-light text-dark"><span id="status-runtime"></span>s</span></span></div>
                        <div class="mb-4 fs-5"><span class="badge bg-secondary">Time Since Last Update <span class="badge bg-light text-dark"><span id="status-last"></span>s</span></span></div>

                        <button class="btn btn-danger" id="cancel-button" onclick="cancelListBuild(CSRF);">Cancel</button>
                    </div>

                    <div class="mt-4" style="display: none;" data-statusbox="force save">
                        <span class="badge bg-danger fs-5 mb-4">ACTION NEEDED</span>

                        <h6 class="mb-4" id="status-force-save-text"></h6>

                        <div class="fs-5 mb-4"><span id="status-force-save-name"></span><code>[<span id="status-force-save-search"></span>]</code></div>

                        <div class="mb-1 mt-0 fs-5"><span class="badge bg-success">Available <span id="status-force-save-available" class="badge bg-light text-dark"></span></span></div>
                        <div class="mb-1 fs-5"><span class="badge bg-danger">Unavailable <span id="status-force-save-unavailable" class="badge bg-light text-dark"></span></span></div>
                        <div class="mb-1 fs-5"><span class="badge bg-primary">Total <span id="status-force-save-total" class="badge bg-light text-dark"></span></span></div>
                        <div class="mb-1 fs-5"><span class="badge bg-secondary">Run Time <span class="badge bg-light text-dark"><span id="status-force-save-runtime"></span>s</span></span></div>
                        <div class="mb-4 fs-5"><span class="badge bg-secondary">Time Since Last Update <span class="badge bg-light text-dark"><span id="status-force-save-last"></span>s</span></span></div>

                        <button class="btn btn-danger" onclick="document.getElementById('form-force-abort').submit();">Force Abort</button>
                        <button class="btn btn-secondary" onclick="document.getElementById('form-force-save').submit();">Force Save</button>

                        <form method="POST" action="/actions/e621/force_abort_finished_list" id="form-force-abort"><input type="hidden" name="CSRF" value="<?php print($_SESSION[SESSION_CSRF]); ?>" /></form>
                    </div>
                </div>
            </div>
        </main>
        <?php require($_SERVER["DOCUMENT_ROOT"]."/components/footer.php"); ?>
        <script>const CSRF = "<?php print($_SESSION[SESSION_CSRF]); ?>";</script>
        <script src="/js/e621.min.js"></script>
        <script>
            var statusNormal = {
                name: document.getElementById("status-name"),
                search: document.getElementById("status-search"),
                available: document.getElementById("status-available"),
                unavailable: document.getElementById("status-unavailable"),
                total: document.getElementById("status-total"),
                runtime: document.getElementById("status-runtime"),
                last: document.getElementById("status-last")
            };

            var statusForceSave = {
                name: document.getElementById("status-force-save-name"),
                search: document.getElementById("status-force-save-search"),
                available: document.getElementById("status-force-save-available"),
                unavailable: document.getElementById("status-force-save-unavailable"),
                total: document.getElementById("status-force-save-total"),
                runtime: document.getElementById("status-force-save-runtime"),
                last: document.getElementById("status-force-save-last"),
                text: document.getElementById("status-force-save-text")
            };

            var processingId = 0;

            function statusTimer(){
                getListBuildStatus(CSRF).then(data => {
                    if(data == null){
                        hideAllBoxes();
                        showBox("none");
                    }else if(data.data.status.updated < (Math.floor(Date.now() / 1000) - 30)){
                        statusForceSave.name.innerText = data.data.name;
                        statusForceSave.search.innerText = data.data.search;
                        statusForceSave.available.innerText = data.data.posts.available;
                        statusForceSave.unavailable.innerText = data.data.posts.unavailable;
                        statusForceSave.total.innerText = data.data.posts.available + data.data.posts.unavailable;
                        statusForceSave.runtime.innerText = Math.floor(Date.now() / 1000) - data.data.status.started;
                        statusForceSave.last.innerText = Math.floor(Date.now() / 1000) - data.data.status.updated;

                        statusForceSave.text.innerText = "The build has not updated for a while and appears to be stuck. If this message remains after 30 seconds, you may want to take action.";

                        hideAllBoxes();
                        showBox("force save");
                    }else if(data.data.status.finished == 0){
                        statusNormal.name.innerText = data.data.name;
                        statusNormal.search.innerText = data.data.search;
                        statusNormal.available.innerText = data.data.posts.available;
                        statusNormal.unavailable.innerText = data.data.posts.unavailable;
                        statusNormal.total.innerText = data.data.posts.available + data.data.posts.unavailable;
                        statusNormal.runtime.innerText = Math.floor(Date.now() / 1000) - data.data.status.started;
                        statusNormal.last.innerText = Math.floor(Date.now() / 1000) - data.data.status.updated;

                        hideAllBoxes();
                        showBox("status");
                    }else if(data.data.status.finished < (Math.floor(Date.now() / 1000) - 10)){
                        statusForceSave.name.innerText = data.data.name;
                        statusForceSave.search.innerText = data.data.search;
                        statusForceSave.available.innerText = data.data.posts.available;
                        statusForceSave.unavailable.innerText = data.data.posts.unavailable;
                        statusForceSave.total.innerText = data.data.posts.available + data.data.posts.unavailable;
                        statusForceSave.runtime.innerText = Math.floor(Date.now() / 1000) - data.data.status.started;
                        statusForceSave.last.innerText = Math.floor(Date.now() / 1000) - data.data.status.updated;

                        statusForceSave.text.innerText = "The build has been finished for a while but has not yet autosaved. If this message remains after 30 seconds, you may want to take action.";

                        hideAllBoxes();
                        showBox("force save");
                    }

                    if(data != null && data.data.queue_id != processingId){
                        document.querySelector("#cancel-button").classList.remove("disabled");
                    }

                    processingId = data != null ? data.data.queue_id : 0;
                });
            }

            function hideAllBoxes(){
                document.querySelectorAll("[data-statusbox]").forEach(element => { element.style.display = "none"; });
            }

            function showBox(box){
                document.querySelectorAll("[data-statusbox='" + box + "']").forEach(element => { element.style.display = "block"; });
            }

            statusTimer();
            setInterval(statusTimer, 250);
        </script>
        <?php require($_SERVER["DOCUMENT_ROOT"]."/components/scripts.php"); ?>
    </body>
</html>