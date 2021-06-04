<?php
    require($_SERVER["DOCUMENT_ROOT"]."/backend/init.php");

    requireAuthentication();

    $page_title = "Download Queue";

    $pending_presort = getDownloadQueue(false, true);
    $finished_presort = getDownloadQueue(true, true);

    $pending = [];
    $finished = [];

    foreach($pending_presort as $pending_item){
        if(!isset($pending[$pending_item->search])){
            $pending[$pending_item->search] = [];
        }
        
        array_push($pending[$pending_item->search], $pending_item);
    }

    foreach($finished_presort as $finished_item){
        if(!isset($finished[$finished_item->search])){
            $finished[$finished_item->search] = [];
        }
        
        array_push($finished[$finished_item->search], $finished_item);
    }

    $processing = getCacheObject(CACHE_KEY_e621_DOWNLOAD_STATUS);

    $accordian_first = true;
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
                    <h5 class="card-title">Download Queue</h5>

                    <ul class="nav nav-tabs mt-4" role="tablist">
                        <li class="nav-item" role="presentation">
                            <a class="nav-link active" id="pending-tab" data-bs-toggle="tab" href="#pending" role="tab">Pending</a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link" id="finished-tab" data-bs-toggle="tab" href="#finished" role="tab">Complete</a>
                        </li>
                    </ul>
                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="pending" role="tabpanel">
                            <div class="accordion" id="pendingAccordion">
                                <?php if(count($pending) != 0){ ?>
                                    <?php foreach($pending as $pending_group){ ?>
                                        <div class="accordion-item">
                                            <h2 class="accordion-header" id="pendingAccordion-header-<?php print($pending_group[0]->id); ?>">
                                                <button class="accordion-button <?php if(!$accordian_first){ ?>collapsed<?php } ?>" type="button" data-bs-toggle="collapse" data-bs-target="#pendingAccordion-collapse-<?php print($pending_group[0]->id); ?>">
                                                    <?php print(htmlspecialchars($pending_group[0]->name)); ?>&nbsp;<code>[<?php print(htmlspecialchars($pending_group[0]->search)); ?>]</code>
                                                </button>
                                            </h2>
                                            <div id="pendingAccordion-collapse-<?php print($pending_group[0]->id); ?>" class="accordion-collapse collapse <?php if($accordian_first){ ?>show<?php } ?>" data-bs-parent="#pendingAccordion">
                                                <div class="accordion-body">
                                                    <div class="row fw-bold">
                                                        <div class="col-sm">
                                                            Date @ Time <code>[ID]</code>
                                                        </div>
                                                        <div class="col-sm">
                                                            Status
                                                        </div>
                                                        <div class="col-sm">
                                                            Actions
                                                        </div>
                                                    </div>
                                                    <?php foreach($pending_group as $item){ ?>
                                                        <hr />
                                                        <div class="row">
                                                            <div class="col-sm">
                                                                <span><span data-timecalc-date="<?php print($item->createdTimestamp); ?>"></span> @ <span data-timecalc-time="<?php print($item->createdTimestamp); ?>"></span></span> <code>[<?php print($item->id); ?>]</code>
                                                            </div>

                                                            <div class="col-sm">
                                                                <?php if($processing != CACHE_NO_OBJECT_DATA && $item->buildQueueId == $processing->queue_id){ ?>
                                                                    <span class="badge bg-primary">In Progress</span>
                                                                <?php }else if($item->pauseState != false){ ?>
                                                                    <span class="badge bg-warning text-dark">Paused</span>
                                                                <?php }else if(($processing != CACHE_NO_OBJECT_DATA && $item->buildQueueId != $processing->queue_id) || $processing == CACHE_NO_OBJECT_DATA){ ?>
                                                                    <span class="badge bg-secondary">Pending</span>
                                                                <?php }else{ ?>
                                                                    <span class="badge bg-danger">Unknown</span>
                                                                <?php } ?>
                                                            </div>

                                                            <div class="col-sm">
                                                                <?php if($processing != CACHE_NO_OBJECT_DATA && $item->buildQueueId == $processing->queue_id){ ?>
                                                                    <a href="/e621/download/status"><button class="btn btn-primary">View</button></a>
                                                                <?php }else if($processing == CACHE_NO_OBJECT_DATA && $item->pauseState != false){ ?>
                                                                    <button class="btn btn-warning" id="resume-button" onclick="unpauseDownload(CSRF);">Resume</button>
                                                                <?php }else{ ?>
                                                                    <span class="fst-italic">No Actions Available</span>
                                                                <?php } ?>
                                                            </div>
                                                        </div>
                                                    <?php } ?>
                                                </div>
                                            </div>
                                        </div>
                                        <?php $accordian_first = false; ?>
                                    <?php } ?>
                                <?php }else{ ?>
                                    <div class="text-center text-muted fst-italic mt-4 mb-2">No pending downloads</div>
                                <?php } ?>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="finished" role="tabpanel">
                            <div class="accordion" id="finishedAccordion">
                                <?php if(count($finished) != 0){ ?>
                                    <?php $accordian_first = true; ?>
                                    <?php foreach($finished as $finished_group){ ?>
                                        <div class="accordion-item">
                                            <h2 class="accordion-header" id="finishedAccordion-header-<?php print($finished_group[0]->id); ?>">
                                                <button class="accordion-button <?php if(!$accordian_first){ ?>collapsed<?php } ?>" type="button" data-bs-toggle="collapse" data-bs-target="#finishedAccordion-collapse-<?php print($finished_group[0]->id); ?>">
                                                    <?php print(htmlspecialchars($finished_group[0]->name)); ?>&nbsp;<code>[<?php print(htmlspecialchars($finished_group[0]->search)); ?>]</code>
                                                </button>
                                            </h2>
                                            <div id="finishedAccordion-collapse-<?php print($finished_group[0]->id); ?>" class="accordion-collapse collapse <?php if($accordian_first){ ?>show<?php } ?>" data-bs-parent="#finishedAccordion">
                                                <div class="accordion-body">
                                                    <div class="row fw-bold">
                                                        <div class="col-sm">
                                                            Date @ Time <code>[ID]</code>
                                                        </div>
                                                    </div>
                                                    <?php foreach($finished_group as $item){ ?>
                                                        <hr />
                                                        <div class="row">
                                                            <div class="col-sm">
                                                                <span><span data-timecalc-date="<?php print($item->createdTimestamp); ?>"></span> @ <span data-timecalc-time="<?php print($item->createdTimestamp); ?>"></span></span> <code>[<?php print($item->id); ?>]</code>
                                                            </div>
                                                        </div>
                                                    <?php } ?>
                                                </div>
                                            </div>
                                        </div>
                                        <?php $accordian_first = false; ?>
                                    <?php } ?>
                                <?php }else{ ?>
                                    <div class="text-center text-muted fst-italic mt-4 mb-2">No finished downloads</div>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
        <?php require($_SERVER["DOCUMENT_ROOT"]."/components/footer.php"); ?>
        <?php require($_SERVER["DOCUMENT_ROOT"]."/components/scripts.php"); ?>
        <script>const CSRF = "<?php print($_SESSION[SESSION_CSRF]); ?>";</script>
        <script src="/js/e621.min.js"></script>
    </body>
</html>