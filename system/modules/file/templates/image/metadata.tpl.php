<?php

/**
 * @var Attachment $attachment
 * @var string $exif_table
 */
?>
<h3><?php echo StringSanitiser::sanitise("$attachment->title - Metadata"); ?></h3>
<?php echo $exif_table ?? "";
