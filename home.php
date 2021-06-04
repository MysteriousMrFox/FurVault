<?php
    require($_SERVER["DOCUMENT_ROOT"]."/backend/init.php");

    requireAuthentication();

    $available_item_count = getItemCount();
    $unavailable_item_count = getItemCount(false);
    $tag_count = getTagCount();

    $random_item = pickRandomItem();
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
                    <h5 class="card-title">System Information</h5>
                    
                    <a href="/gallery/list/0"><button class="btn btn-success mt-4">Available Items <span class="badge bg-light text-dark"><?php print($available_item_count); ?></span></button></a>
                    <button class="btn btn-danger mt-4">Unavailable Items <span class="badge bg-light text-dark"><?php print($unavailable_item_count); ?></span></button>
                    <button class="btn btn-primary mt-4">Tags <span class="badge bg-light text-dark"><?php print($tag_count); ?></span></button>
                    <a href="/e621/build/queue"><button class="btn btn-secondary mt-4">Build Queue <span class="badge bg-light text-dark"><?php print($list_build_queue_count); ?></span></button></a>
                    <a href="/e621/download/queue"><button class="btn btn-secondary mt-4">Download Queue <span class="badge bg-light text-dark"><?php print($download_queue_count); ?></span></button></a>
                </div>
            </div>

            <?php if($random_item != false){ ?>
                <div class="card mt-4">
                    <div class="card-body text-center">
                        <a href="/gallery/view/<?php print($random_item->id); ?>"><img style="max-width: 100%;" src="/api/item/view/<?php print($random_item->id); ?>" /></a>
                    </div>
                </div>
            <?php } ?>
        </main>
        <?php require($_SERVER["DOCUMENT_ROOT"]."/components/footer.php"); ?>
        <?php require($_SERVER["DOCUMENT_ROOT"]."/components/scripts.php"); ?>
    </body>
</html>