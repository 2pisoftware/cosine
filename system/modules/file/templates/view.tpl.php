<?php
/**
 * @var Attachment $attachment
 * @var User $user
 */
$filename = $attachment->filename;
$split_filename = explode(".", $filename);
?>
<h1><?php echo StringSanitiser::sanitise($attachment->title); ?></h1>
<p><?php echo StringSanitiser::sanitise($attachment->description); ?></p>

<?php
if (!empty($owner)) {
    $contact = $owner->getContact();
    if (!empty($contact)) {
        echo "Owner: " . StringSanitiser::sanitise($contact->getFullName() ?: $contact->email);
    } else {
        // if there isn't a contact for this user
        // fallback to the user id
        echo "Owner: User (" . $user->id . ")";
    }
}
?>

<p>
    Created by:
    <?php if (!empty($creator)) {
        $creator_contact = $creator->getContact();
        echo StringSanitiser::sanitise($creator_contact->getFullName() ?: $creator_contact->email);
    } ?>
</p>

<div>
    <a
        href="/file/atfile/<?php echo $attachment->id; ?>"
        target="_blank"
        class="btn btn-primary">
        Open in new tab
    </a>

    <a
        href="/file-image/metadata/<?php echo $attachment->id; ?>"
        target="_blank"
        class="btn btn-primary">
        View metadata
    </a>
</div>

<?php if ($attachment->isImage()) : ?>
    <img
        class="img-fluid"
        data-caption="<?php echo $attachment->title; ?>"
        src="<?php echo $attachment->getThumbnailUrl(); ?>" />
<?php elseif ($attachment->isDocument()) :
    echo $attachment->getDocumentEmbedHtml();
else : ?>
    <div
        class="img-thumbnail
            bi-filetype-doc
            bi-filetype-<?php echo end($split_filename) ?>
            text-center"
        style="font-size: 7rem; width: 210px; height: 210px;">
    </div>
<?php endif; ?>