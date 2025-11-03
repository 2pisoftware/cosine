<?php echo !empty($createfeed) ? $createfeed : ''; ?>
<?php echo !empty($feedurl) ? $feedurl : ''; ?>

<span id="feedtext"><?php echo !empty($feedtext) ? $feedtext : ''; ?></span>

<script language="javascript">
    // Disable AJAX cache (not needed for fetch, but shown for parity)
    var feed_url = "/report/feedAjaxGetReportText?id=";

    var select = document.querySelector("select[id='rid']");
    if (select) {
        select.addEventListener('change', function() {
            var id = select.value;
            fetch(feed_url + id)
                .then(response => response.json())
                .then(result => {
                    document.getElementById('feedtext').innerHTML = result;
                });
        });
    }
</script>

