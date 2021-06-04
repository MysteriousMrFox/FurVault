<?php
    require($_SERVER["DOCUMENT_ROOT"]."/backend/init.php");

    requireAuthentication();

    if(!isset($_GET["id"]) || $_GET["id"] == ""){
        setPersistentWarning("No item was selected");
        header("Location: /gallery/list/0");
        die();
    }

    $item = getItemById((int) $_GET["id"]);

    if($item == false){
        setPersistentWarning("The selected item could not be found");
        header("Location: /gallery/list/0");
        die();
    }

    //Set order of groups. Any unspecified will be tacked on the end
    $tags = [
        "artist" => [],
        "copyright" => [],
        "character" => [],
        "species" => [],
        "general" => [],
        "rating" => [],
        "meta" => []
    ];

    $item_tags = getTagsByItem($item->id);

    foreach($item_tags as $tag){
        if(!isset($tags[$tag->type])){
            $tags[$tag->type] = [];
        }

        array_push($tags[$tag->type], $tag->name);
    }
    
    foreach($tags as $type => $names){
        if(count($names) == 0){
            unset($tags[$type]);
        }
    }

    $meta_e621_id = null;

    $metadata = getMetadataByItem($item->id);

    foreach($metadata as $meta){
        switch($meta->key){
            case "e621/id":
                $meta_e621_id = $meta->value;
                break;
        }
    }


    $launch_ruffle = false;
?>
<!DOCTYPE html>
<html>
    <head>
        <?php require($_SERVER["DOCUMENT_ROOT"]."/components/head.php"); ?>
        <style>
            .gallery-view {
                display: grid;
                grid-template-columns: 1fr 4fr;
                grid-gap: 1rem;
                width: 100%;
            }

            .gallery-view-content img, .gallery-view-content video {
                max-width: 100%;
            }

            .tag-item {
                text-decoration: none;
            }

            <?php foreach(TAG_TYPE_COLOURS as $tag_type => $tag_colours){ ?>
                .tag-item.tg<?php print($tag_type); ?> {
                    color: <?php print($tag_colours["normal"]); ?>;
                }

                .tag-item.tg<?php print($tag_type); ?>:hover {
                    color: <?php print($tag_colours["hover"]); ?>;
                }
            <?php } ?>
        </style>
    </head>
    <body>
        <?php require($_SERVER["DOCUMENT_ROOT"]."/components/nav.php"); ?>
        <main class="mt-4">
            <div class="container">
                <?php require($_SERVER["DOCUMENT_ROOT"]."/components/cookie_message_banners.php"); ?>
            </div>
            <div class="gallery-view px-4 mb-4">
                <div class="card">
                    <div class="card-body">
                        <h4>Tags</h4>
                        <?php foreach($tags as $type => $names){ ?>
                            <div class="fw-bold"><?php print(htmlspecialchars(ucfirst($type))); ?></div>
                            <ul>
                                <?php foreach($names as $name){ ?>
                                    <li><a class="tag-item tg<?php print(htmlspecialchars($type)); ?>" href="/gallery/list/0?search=<?php print(htmlspecialchars($type)); ?>:<?php print(htmlspecialchars($name)); ?>"><?php print(htmlspecialchars(str_replace("_", " ", $name))); ?></a></li>
                                <?php } ?>
                            </ul>
                        <?php } ?>

                        <h4>Details</h4>
                        <div><span class="fw-bold">MD5:</span> <code><?php print(htmlspecialchars($item->md5Checksum)); ?></code></div>
                        <?php if($meta_e621_id != null){ ?><div><span class="fw-bold">e621 ID:</span> <code><?php print(htmlspecialchars($meta_e621_id)); ?></code></div><?php } ?>
                    </div>
                </div>

                <div class="gallery-view-content">
                    <?php if(
                        endsWith($item->storageLocation, "png") ||
                        endsWith($item->storageLocation, "jpg") ||
                        endsWith($item->storageLocation, "jpeg") ||
                        endsWith($item->storageLocation, "gif") ||
                        endsWith($item->storageLocation, "webp")
                    ){ ?>
                        <img src="/api/item/view/<?php print($item->id); ?>" />
                    <?php }else if(
                        endsWith($item->storageLocation, "webm") ||
                        endsWith($item->storageLocation, "mp4")
                    ){ ?>
                        <video poster="/api/item/preview/<?php print($item->id); ?>" controls loop>
                            <source src="/api/item/view/<?php print($item->id); ?>" type="<?php print(htmlspecialchars(getMimeFromFilename($item->storageLocation))); ?>">
                            Video Not Supported
                        </video>
                    <?php }else if(endsWith($item->storageLocation, "swf")){ ?>
                        <div id="ruffle"></div>
                        <?php $launch_ruffle = true; ?>
                    <?php }else{ ?>
                        <h2>Preview Only</h2>
                        <img src="/api/item/preview/<?php print($item->id); ?>" />
                    <?php } ?>

                    <div class="my-4">
                        <a href="/api/item/view/<?php print($item->id); ?>"><button class="btn btn-secondary">View</button></a>
                        <?php if(userHasFavorited($_SESSION[SESSION_USERID], $item->id)){ ?>
                            <a href="/actions/gallery/favorite/remove?CSRF=<?php print($_SESSION[SESSION_CSRF]); ?>&id=<?php print($item->id); ?>"><button class="btn btn-danger">Unfavorite</button></a>
                        <?php }else{ ?>
                            <a href="/actions/gallery/favorite/add?CSRF=<?php print($_SESSION[SESSION_CSRF]); ?>&id=<?php print($item->id); ?>"><button class="btn btn-success">Favorite</button></a>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </main>
        <?php require($_SERVER["DOCUMENT_ROOT"]."/components/footer.php"); ?>
        <?php require($_SERVER["DOCUMENT_ROOT"]."/components/scripts.php"); ?>
        <?php if($launch_ruffle){ ?>
            <script>
                window.RufflePlayer = window.RufflePlayer || {};
                window.RufflePlayer.config = {
                    autoplay: "off"
                };
                window.addEventListener("load", event => {
                    const ruffle = window.RufflePlayer.newest();
                    const player = ruffle.createPlayer();
                    const container = document.querySelector("#ruffle");
                    container.appendChild(player);
                    player.load("/api/item/view/<?php print($item->id); ?>");
                });
            </script>
            <script src="/js/ruffle/ruffle.js"></script>
        <?php } ?>
    </body>
</html>