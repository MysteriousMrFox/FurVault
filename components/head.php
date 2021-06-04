<title><?php print(isset($page_title) && $page_title != "" ? (htmlspecialchars($page_title." • ")) : ""); ?><?php print(htmlspecialchars(PRODUCT_NAME)); ?></title>
<link rel="stylesheet" href="/dist/bootstrap/css/bootstrap.min.css" />
<style>
    html {
        position: relative;
        min-height: 100%;
    }

    body {
        height: 100%;
        min-height: 100%;
    }

    main {
        margin: 0 0 6rem 0;
    }

    footer {
        position: absolute;
        left: 0;
        bottom: 0;
        height: 3.5rem;
        padding: 1rem;
        width: 100%;
        overflow: hidden;
        background-color: #E7E8E9;
    }
</style>
<script src="/dist/bootstrap/js/bootstrap.bundle.min.js"></script>