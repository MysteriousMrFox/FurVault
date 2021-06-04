<footer class="text-muted">
    <div class="float-start"><?php print(htmlspecialchars(PRODUCT_NAME)); ?> <?php print(htmlspecialchars(PRODUCT_VERSION)); ?></div>
    <div class="float-end"><span class="badge bg-secondary"><?php print(number_format(microtime(true) - MICRO_EXECUTION_START_TIME, 2, ".", "")); ?>s</span></div>
</footer>