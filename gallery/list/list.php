<?php
    require($_SERVER["DOCUMENT_ROOT"]."/backend/init.php");

    requireAuthentication();

    $page = 0;

    if(isset($_GET["page"]) && is_numeric($_GET["page"]) && (int) $_GET["page"] >= 0){
        $page = (int) $_GET["page"];
    }

    if(!isset($_GET["search"]) || $_GET["search"] == ""){
        $search = "";
    }else{
        $search = $_GET["search"];
    }

    $items = getItemsBySearch($search, 30, $page, !isset($_GET["unavailable"]));
    $rows = array_chunk($items->data, 5);

    //Pagination Setup
    $previous_pages = $items->pagination->page - floor(MAX_PAGINATION / 2);
    $next_pages = $items->pagination->page + floor(MAX_PAGINATION / 2);

    if($previous_pages <= 0){
        $previous_pages = 0;
        $next_pages = $items->pagination->max_page > MAX_PAGINATION ? MAX_PAGINATION : $items->pagination->max_page;
    }

    if($next_pages >= $items->pagination->max_page){
        $previous_pages = $items->pagination->max_page - MAX_PAGINATION < 0 ? 0 : $items->pagination->max_page - MAX_PAGINATION;
        $next_pages = $items->pagination->max_page;
    }
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
            <ul class="pagination justify-content-center">
                <li class="page-item <?php if($items->pagination->page == 0){ ?>disabled<?php } ?>">
                    <a class="page-link" href="/gallery/list/0<?php if($search != ""){ ?>?search=<?php print(htmlspecialchars($search)); } ?>">
                        <span>&lArr;</span>
                    </a>
                </li>

                <li class="page-item <?php if($items->pagination->page == 0){ ?>disabled<?php } ?>">
                    <a class="page-link" href="/gallery/list/<?php print($items->pagination->page - 1); ?><?php if($search != ""){ ?>?search=<?php print(htmlspecialchars($search)); } ?>">
                        <span>&laquo;</span>
                    </a>
                </li>

                <?php for($page_pos = $previous_pages; $page_pos <= $next_pages; $page_pos++){ ?>
                    <li class="page-item <?php if($page_pos == $items->pagination->page){ ?>active<?php } ?>">
                        <a class="page-link" href="/gallery/list/<?php print($page_pos); ?><?php if($search != ""){ ?>?search=<?php print(htmlspecialchars($search)); } ?>">
                            <span><?php print($page_pos); ?></span>
                        </a>
                    </li>
                <?php } ?>

                <li class="page-item <?php if($items->pagination->page == $items->pagination->max_page){ ?>disabled<?php } ?>">
                    <a class="page-link" href="/gallery/list/<?php print($items->pagination->page + 1); ?><?php if($search != ""){ ?>?search=<?php print(htmlspecialchars($search)); } ?>">
                        <span>&raquo;</span>
                    </a>
                </li>

                <li class="page-item <?php if($items->pagination->page == $items->pagination->max_page){ ?>disabled<?php } ?>">
                    <a class="page-link" href="/gallery/list/<?php print($items->pagination->max_page); ?><?php if($search != ""){ ?>?search=<?php print(htmlspecialchars($search)); } ?>">
                        <span>&rArr;</span>
                    </a>
                </li>
            </ul>

            <div class="card mb-4">
                <div class="card-body">
                    <?php if($search == ""){ ?>
                        <h5 class="card-title"><code>All Items</code> <span class="badge bg-secondary"><?php print($items->pagination->count); ?></span></h5>
                    <?php }else{ ?>
                        <h5 class="card-title"><code><?php print(htmlspecialchars($search)); ?></code> <span class="badge bg-secondary"><?php print($items->pagination->count); ?></span></h5>
                    <?php } ?>
                    
                    <div class="container mt-4">
                        <?php foreach($rows as $row){ ?>
                            <div class="row mb-4">
                                <?php foreach($row as $item){ ?>
                                    <div class="col-sm">
                                        <a href="/gallery/view/<?php print($item->id); ?>">
                                            <div class="card">
                                                <div class="card-body text-center">
                                                    <img class="img-thumbnail" src="/api/item/preview/<?php print($item->id); ?>" />
                                                </div>
                                            </div>
                                        </a>
                                    </div>
                                <?php } ?>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            </div>

            <ul class="pagination justify-content-center">
                <li class="page-item <?php if($items->pagination->page == 0){ ?>disabled<?php } ?>">
                    <a class="page-link" href="/gallery/list/0<?php if($search != ""){ ?>?search=<?php print(htmlspecialchars($search)); } ?>">
                        <span>&lArr;</span>
                    </a>
                </li>

                <li class="page-item <?php if($items->pagination->page == 0){ ?>disabled<?php } ?>">
                    <a class="page-link" href="/gallery/list/<?php print($items->pagination->page - 1); ?><?php if($search != ""){ ?>?search=<?php print(htmlspecialchars($search)); } ?>">
                        <span>&laquo;</span>
                    </a>
                </li>

                <?php for($page_pos = $previous_pages; $page_pos <= $next_pages; $page_pos++){ ?>
                    <li class="page-item <?php if($page_pos == $items->pagination->page){ ?>active<?php } ?>">
                        <a class="page-link" href="/gallery/list/<?php print($page_pos); ?><?php if($search != ""){ ?>?search=<?php print(htmlspecialchars($search)); } ?>">
                            <span><?php print($page_pos); ?></span>
                        </a>
                    </li>
                <?php } ?>

                <li class="page-item <?php if($items->pagination->page == $items->pagination->max_page){ ?>disabled<?php } ?>">
                    <a class="page-link" href="/gallery/list/<?php print($items->pagination->page + 1); ?><?php if($search != ""){ ?>?search=<?php print(htmlspecialchars($search)); } ?>">
                        <span>&raquo;</span>
                    </a>
                </li>

                <li class="page-item <?php if($items->pagination->page == $items->pagination->max_page){ ?>disabled<?php } ?>">
                    <a class="page-link" href="/gallery/list/<?php print($items->pagination->max_page); ?><?php if($search != ""){ ?>?search=<?php print(htmlspecialchars($search)); } ?>">
                        <span>&rArr;</span>
                    </a>
                </li>
            </ul>
        </main>
        <?php require($_SERVER["DOCUMENT_ROOT"]."/components/footer.php"); ?>
        <?php require($_SERVER["DOCUMENT_ROOT"]."/components/scripts.php"); ?>
    </body>
</html>