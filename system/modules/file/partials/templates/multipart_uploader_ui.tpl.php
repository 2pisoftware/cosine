<div id="multipart-uploader" class="mt-4">
    <multipart-uploader-component
        endpoint="<?php echo $endpoint; ?>"
        :calculateHash="<?php echo $calculateHash ? 'true' : 'false' ?>">
    </multipart-uploader-component>
</div>