<?php
    require($_SERVER["DOCUMENT_ROOT"]."/backend/init.php");

    requireAuthentication();

    $page_title = "Build Queue";

    $pending_presort = getListBuildQueue(false, true);
    $finished_presort = getListBuildQueue(true, false);

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

    $processing = getCacheObject(CACHE_KEY_e621_LIST_BUILD_MINI_STATUS);

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
                    <h5 class="card-title">Build Queue</h5>

                    <ul class="nav nav-tabs mt-4" role="tablist">
                        <li class="nav-item" role="presentation">
                            <a class="nav-link active" id="pending-tab" data-bs-toggle="tab" href="#pending" role="tab">Pending</a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link" id="finished-tab" data-bs-toggle="tab" href="#finished" role="tab">Complete <span class="badge bg-secondary" id="completedTodayCounter">? Today</span></a>
                        </li>
                    </ul>
                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="pending" role="tabpanel">
                            <div class="accordion" id="pendingAccordion">
                                <div class="my-4">
                                    <a href="/actions/e621/bump_build_queue?CSRF=<?php print($_SESSION[SESSION_CSRF]); ?>"><button class="btn btn-danger">Bump Queue Processor (Recheck pending if crashed)</button></a>
                                </div>
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
                                                                <?php if($processing != CACHE_NO_OBJECT_DATA && $item->id != $processing->queue_id){ ?>
                                                                    <span class="badge bg-secondary">Pending</span>
                                                                <?php }else{ ?>
                                                                    <span class="badge bg-primary">In Progress</span>
                                                                <?php } ?>
                                                            </div>
                                                            <div class="col-sm">
                                                                <?php if($processing != CACHE_NO_OBJECT_DATA && $item->id == $processing->queue_id){ ?>
                                                                    <a href="/e621/build/status"><button class="btn btn-primary">View</button></a>
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
                                    <div class="text-center text-muted fst-italic mt-4 mb-2">No pending builds</div>
                                <?php } ?>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="finished" role="tabpanel">
                            <div class="accordion" id="finishedAccordion">
                                <div class="my-4">
                                    <a href="/actions/e621/requeue_all_finished?CSRF=<?php print($_SESSION[SESSION_CSRF]); ?>"><button class="btn btn-primary">Queue All Update Builds <span class="badge bg-light text-dark" id="queueableCounter">?</span></button></a>
                                </div>
                                <?php if(count($finished) != 0){ ?>
                                    <?php $accordian_first = true; ?>
                                    <?php foreach($finished as $finished_group){ ?>
                                        <?php
                                            $available = 0;
                                            $unavailable = 0;

                                            foreach($finished_group as $item){
                                                $available += $item->availableFound;
                                                $unavailable += $item->unavailableFound;
                                            }
                                        ?>
                                        <div data-finished-header class="accordion-item">
                                            <h2 class="accordion-header" id="finishedAccordion-header-<?php print($finished_group[0]->id); ?>">
                                                <button class="accordion-button <?php if(!$accordian_first){ ?>collapsed<?php } ?>" type="button" data-bs-toggle="collapse" data-bs-target="#finishedAccordion-collapse-<?php print($finished_group[0]->id); ?>">
                                                    <span class="badge bg-secondary">Items <span class="badge bg-success"><?php print($available); ?></span> <span class="badge bg-danger"><?php print($unavailable); ?></span></span>&nbsp;&nbsp;<?php print(htmlspecialchars($finished_group[0]->name)); ?>&nbsp;<code>[<?php print(htmlspecialchars($finished_group[0]->search)); ?>]</code>
                                                </button>
                                            </h2>
                                            <div id="finishedAccordion-collapse-<?php print($finished_group[0]->id); ?>" class="accordion-collapse collapse <?php if($accordian_first){ ?>show<?php } ?>" data-bs-parent="#finishedAccordion">
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
                                                    <?php $first_finished_item = true; ?>
                                                    <?php foreach($finished_group as $item){ ?>
                                                        <hr />
                                                        <div class="row" data-day-of-creation="<?php print(strtotime(date("Y-m-d", $item->createdTimestamp))); ?>">
                                                            <div class="col-sm">
                                                                <span><span data-timecalc-date="<?php print($item->createdTimestamp); ?>"></span> @ <span data-timecalc-time="<?php print($item->createdTimestamp); ?>"></span></span> <code>[<?php print($item->id); ?>]</code>
                                                            </div>
                                                            <div class="col-sm">
                                                                <?php if($item->cancelled == false){ ?>
                                                                    <span class="badge bg-success">Completed</span> <span class="badge bg-secondary">Items <span class="badge bg-success"><?php print($item->availableFound); ?></span> <span class="badge bg-danger"><?php print($item->unavailableFound); ?></span></span>
                                                                <?php }else{ ?>
                                                                    <span class="badge bg-danger">Cancelled</span>
                                                                <?php } ?>
                                                            </div>
                                                            <div class="col-sm">
                                                                <?php if($first_finished_item){ ?>
                                                                    <?php if($item->cancelled == false){ ?>
                                                                        <button class="btn btn-primary" data-build-name="<?php print(htmlspecialchars($item->name)); ?>" data-build-search="<?php print(htmlspecialchars($item->search)); ?>" data-build-button onclick="document.querySelectorAll('[data-build-button]').forEach(element => element.classList.add('disabled')); buildDownloadList(CSRF, this.getAttribute('data-build-search'), this.getAttribute('data-build-name'));">Queue Update Build</button>
                                                                    <?php }else{ ?>
                                                                        <button class="btn btn-primary" data-build-name="<?php print(htmlspecialchars($item->name)); ?>" data-build-search="<?php print(htmlspecialchars($item->search)); ?>" data-build-button onclick="document.querySelectorAll('[data-build-button]').forEach(element => element.classList.add('disabled')); buildDownloadList(CSRF, this.getAttribute('data-build-search'), this.getAttribute('data-build-name'));">Requeue</button>
                                                                    <?php } ?>
                                                                <?php }else{ ?>
                                                                    <span class="fst-italic">No Actions Available</span>
                                                                <?php } ?>
                                                            </div>
                                                        </div>
                                                        <?php $first_finished_item = false; ?>
                                                    <?php } ?>
                                                </div>
                                            </div>
                                        </div>
                                        <?php $accordian_first = false; ?>
                                    <?php } ?>
                                <?php }else{ ?>
                                    <div class="text-center text-muted fst-italic mt-4 mb-2">No finished builds</div>
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
        <script>
            document.querySelector("#queueableCounter").innerText = document.querySelectorAll("[data-finished-header]").length;
            document.querySelector("#completedTodayCounter").innerText = document.querySelectorAll("[data-day-of-creation='<?php print(strtotime(date("Y-m-d", EXECUTION_START_TIME))); ?>']").length + " Today";
        </script>
    </body>
</html>