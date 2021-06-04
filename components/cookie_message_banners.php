<?php if($status_message != "") { ?>
    <div class="alert alert-success" role="alert"><?php print(htmlspecialchars($status_message)); ?></div>
<?php } ?>
<?php if($status_error != "") { ?>
    <div class="alert alert-danger" role="alert"><?php print(htmlspecialchars($status_error)); ?></div>
<?php } ?>
<?php if($status_warning != "") { ?>
    <div class="alert alert-warning" role="alert"><?php print(htmlspecialchars($status_warning)); ?></div>
<?php } ?>