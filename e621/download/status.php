<?php
    require($_SERVER["DOCUMENT_ROOT"]."/backend/init.php");

    requireAuthentication();

    $page_title = "e621 Download Status";
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
                    <h5 class="card-title">Download Status</h5>

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

                        <div class="fs-5 mb-4"><span id="status-name"></span></div>
                        <div class="progress mb-1">
                            <div class="progress-bar progress-bar-striped progress-bar-animated" id="status-progressbar" role="progressbar" style="width: 0%"></div>
                        </div>
                        <div class="progress mb-1">
                            <div class="progress-bar bg-success progress-bar-striped progress-bar-animated" id="status-availablebar" role="progressbar" style="width: 0%"></div>
                        </div>
                        <div class="progress mb-1">
                            <div class="progress-bar bg-danger progress-bar-striped progress-bar-animated" id="status-unavailablebar" role="progressbar" style="width: 0%"></div>
                        </div>

                        <div class="mb-1 fs-5"><span class="badge bg-primary">Total <span id="status-total" class="badge bg-light text-dark"></span></span></div>
                        <div class="mb-1 fs-5"><span class="badge bg-secondary">Run Time <span class="badge bg-light text-dark"><span id="status-runtime"></span>s</span></span></div>
                        <div class="mb-1 fs-5"><span class="badge bg-secondary">Time Since Last Update <span class="badge bg-light text-dark"><span id="status-last"></span>s</span></span></div>
                        <div class="mb-4 fs-5"><span class="badge bg-secondary">ETA <span id="status-eta" class="badge bg-light text-dark"></span></span></div>

                        <button class="btn btn-danger mb-4" id="cancel-button" onclick="cancelDownload(CSRF);">Cancel</button>
                        <button class="btn btn-warning mb-4" id="pause-button" onclick="pauseDownload(CSRF);">Pause</button>
                        <div class="form-check mb-4">
                            <input class="form-check-input" type="checkbox" id="preview-enabled">
                            <label class="form-check-label" for="preview-enabled">
                                Preview
                            </label>
                        </div>

                        <img class="img-thumbnail d-block" style="max-height: 500px;" id="status-preview" />
                    </div>

                    <div class="mt-4" style="display: none;" data-statusbox="force save">
                        <span class="badge bg-danger fs-5 mb-4">ACTION NEEDED</span>

                        <h6 class="mb-4" id="status-force-save-text"></h6>

                        <div class="fs-5 mb-4"><span id="status-force-save-name"></span></div>

                        <div class="progress mb-1">
                            <div class="progress-bar progress-bar-striped progress-bar-animated" id="status-force-save-progressbar" role="progressbar" style="width: 0%"></div>
                        </div>
                        <div class="progress mb-1">
                            <div class="progress-bar bg-success progress-bar-striped progress-bar-animated" id="status-force-save-availablebar" role="progressbar" style="width: 0%"></div>
                        </div>
                        <div class="progress mb-1">
                            <div class="progress-bar bg-success progress-bar-striped progress-bar-animated" id="status-force-save-unavailablebar" role="progressbar" style="width: 0%"></div>
                        </div>

                        <div class="mb-1 fs-5"><span class="badge bg-primary">Total <span id="status-force-save-total" class="badge bg-light text-dark"></span></span></div>
                        <div class="mb-1 fs-5"><span class="badge bg-secondary">Run Time <span class="badge bg-light text-dark"><span id="status-force-save-runtime"></span>s</span></span></div>
                        <div class="mb-4 fs-5"><span class="badge bg-secondary">Time Since Last Update <span class="badge bg-light text-dark"><span id="status-force-save-last"></span>s</span></span></div>

                        <button class="btn btn-danger mb-4" onclick="document.getElementById('form-force-suspend').submit();">Suspend</button>

                        <form method="POST" action="/actions/e621/suspend_crashed_download" id="form-force-suspend"><input type="hidden" name="CSRF" value="<?php print($_SESSION[SESSION_CSRF]); ?>" /></form>
                    </div>
                </div>
            </div>
        </main>
        <?php require($_SERVER["DOCUMENT_ROOT"]."/components/footer.php"); ?>
        <script>const CSRF = "<?php print($_SESSION[SESSION_CSRF]); ?>";</script>
        <script src="/js/e621.min.js"></script>
        <script>
            var previewEnabled = document.querySelector("#preview-enabled");

            var statusNormal = {
                name: document.getElementById("status-name"),
                progressbar: document.getElementById("status-progressbar"),
                availablebar: document.getElementById("status-availablebar"),
                unavailablebar: document.getElementById("status-unavailablebar"),
                preview: document.getElementById("status-preview"),
                total: document.getElementById("status-total"),
                runtime: document.getElementById("status-runtime"),
                last: document.getElementById("status-last"),
                eta: document.getElementById("status-eta")
            };

            var statusForceSave = {
                name: document.getElementById("status-force-save-name"),
                progressbar: document.getElementById("status-force-save-progressbar"),
                availablebar: document.getElementById("status-force-save-availablebar"),
                unavailablebar: document.getElementById("status-force-save-unavailablebar"),
                total: document.getElementById("status-force-save-total"),
                runtime: document.getElementById("status-force-save-runtime"),
                last: document.getElementById("status-force-save-last"),
                text: document.getElementById("status-force-save-text")
            };

            var processingId = 0;

            function statusTimer(){
                var nowUnix = Math.floor(Date.now() / 1000);

                getDownloadStatus(CSRF).then(data => {
                    if(data == null){
                        hideAllBoxes();
                        showBox("none");
                    }else if(data.data.status.updated < (nowUnix - 30)){
                        statusForceSave.name.innerText = data.data.download.md5;

                        statusForceSave.progressbar.style.width = ((data.data.download.current / data.data.download.size) * 100).toString() + "%";

                        statusForceSave.availablebar.style.width = ((data.data.current_item.available / data.data.items.available) * 100).toString() + "%";
                        statusForceSave.availablebar.innerText = data.data.current_item.available + "/" + data.data.items.available;

                        statusForceSave.unavailablebar.style.width = ((data.data.current_item.unavailable / data.data.items.unavailable) * 100).toString() + "%";
                        statusForceSave.unavailablebar.innerText = data.data.current_item.unavailable + "/" + data.data.items.unavailable;
                        
                        statusForceSave.total.innerText = data.data.items.available + data.data.items.unavailable;

                        statusForceSave.runtime.innerText = nowUnix - data.data.status.started;
                        statusForceSave.last.innerText = nowUnix - data.data.status.updated;

                        statusForceSave.text.innerText = "The download has not updated for a while and appears to be stuck. If this message remains after 30 seconds, you may want to take action.";

                        hideAllBoxes();
                        showBox("force save");
                    }else{
                        var eta = new Date((data.data.status.started + ((nowUnix - data.data.status.started) / data.data.current_item.available) * data.data.items.available) * 1000);

                        statusNormal.name.innerText = data.data.download.md5;

                        statusNormal.progressbar.style.width = ((data.data.download.current / data.data.download.size) * 100).toString() + "%";

                        statusNormal.availablebar.style.width = ((data.data.current_item.available / data.data.items.available) * 100).toString() + "%";
                        statusNormal.availablebar.innerText = data.data.current_item.available + "/" + data.data.items.available;

                        statusNormal.unavailablebar.style.width = ((data.data.current_item.unavailable / data.data.items.unavailable) * 100).toString() + "%";
                        statusNormal.unavailablebar.innerText = data.data.current_item.unavailable + "/" + data.data.items.unavailable;

                        if(previewEnabled.checked){
                            if(statusNormal.preview.src != data.data.download.thumbnail){
                                statusNormal.preview.src = data.data.download.thumbnail;
                            }
                        }else{
                            statusNormal.preview.src = "";
                        }

                        statusNormal.total.innerText = data.data.items.available + data.data.items.unavailable;

                        statusNormal.runtime.innerText = nowUnix - data.data.status.started;
                        statusNormal.last.innerText = nowUnix - data.data.status.updated;
                        statusNormal.eta.innerText = eta.toLocaleDateString() + " @ " + eta.toLocaleTimeString();

                        hideAllBoxes();
                        showBox("status");
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