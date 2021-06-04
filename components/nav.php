<?php
    $list_build_queue_count = getListBuildQueueCount();
    $download_queue_count = getDownloadQueueCount();
?>
<nav class="navbar navbar-expand-lg navbar-light bg-light">
    <div class="container-fluid">
        <a class="navbar-brand" href="/home"><?php print(htmlspecialchars(PRODUCT_NAME)); ?></a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navContent"><span class="navbar-toggler-icon"></span></button>

        <div class="collapse navbar-collapse" id="navContent">
            <?php if(isAuthenticated()){ ?>
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <?php print(htmlspecialchars($user_data->displayName)); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><h6 class="dropdown-header">Gallery</h6></li>
                            <li><a class="dropdown-item" href="/gallery/favorites/0">Favorites</a></li>
                            <li><a class="dropdown-item disabled" href="/gallery/collections/list">Collections</a></li>

                            <li><hr class="dropdown-divider"></li>
                            
                            <li><h6 class="dropdown-header">Blacklist</h6></li>
                            <li><a class="dropdown-item disabled" href="#">Tags</a></li>
                            <li><a class="dropdown-item disabled" href="#">Items</a></li>
                        </ul>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link active" href="/gallery/list/0">Gallery</a>
                    </li>

                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            e621
                        </a>
                        <ul class="dropdown-menu">
                            <li><h6 class="dropdown-header">New Build</h6></li>
                            <li><a class="dropdown-item" href="/e621/tags">From Tag Search</a></li>
                            <li><a class="dropdown-item" href="/e621/faves">From User Favorites</a></li>

                            <li><hr class="dropdown-divider"></li>
                            
                            <li><h6 class="dropdown-header">Builds</h6></li>
                            <li><a class="dropdown-item" href="/e621/build/queue">Queue <span class="badge bg-secondary"><?php print($list_build_queue_count); ?></span></a></li>
                            <li><a class="dropdown-item" href="/e621/build/status">Status <?php print(generateListBuildStatusBadge()); ?></a></li>

                            <li><hr class="dropdown-divider"></li>

                            <li><h6 class="dropdown-header">Downloads</h6></li>
                            <li><a class="dropdown-item" href="/e621/download/queue">Queue <span class="badge bg-secondary"><?php print($download_queue_count); ?></span></a></li>
                            <li><a class="dropdown-item" href="/e621/download/status">Status <?php print(generateDownloadStatusBadge()); ?></a></li>
                        </ul>
                    </li>
                </ul>
                
                <form class="d-flex" method="POST" action="/actions/gallery/search">
                    <a class="me-2" href="/actions/logout?CSRF=<?php print($_SESSION[SESSION_CSRF]); ?>"><button class="btn btn-danger" type="button">Log&nbsp;Out</button></a>
                    <a class="me-2" href="/actions/gallery/random?CSRF=<?php print($_SESSION[SESSION_CSRF]); ?>"><button class="btn btn-outline-secondary" type="button">Random</button></a>

                    <input type="hidden" name="CSRF" value="<?php print($_SESSION[SESSION_CSRF]); ?>" />
                    <input class="form-control me-2" type="search" name="search" placeholder="Enter tags to search..." <?php if(isset($_GET["search"]) && $_GET["search"] != ""){ ?>value="<?php print(htmlspecialchars($_GET["search"])); ?>"<?php } ?>>
                    <button class="btn btn-secondary">Search</button>
                </form>
            <?php } ?>
        </div>
    </div>
</nav>