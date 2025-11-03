<?php if (!empty($form)) {
    echo $is_multicol_form ? HtmlBootstrap5::multiColForm($form, "/report/exereport/" . $report->id, "POST", "Download", "report_partial_form") : HtmlBootstrap5::form($form);
} else {
    echo HtmlBootstrap5::alertBox('No report form data was returned', 'error');
}
?>
<script>
    document.getElementById("report_partial_form").addEventListener('submit', function(e) {
        var form = this;
        var action = form.getAttribute('action');
        var dtStart = document.getElementById("dt_start").value;
        var dtEnd = document.getElementById("dt_end").value;
        var format = document.querySelector("input[name='format']:checked").value;

        const url = new URL(action, window.location.href);
        url.searchParams.set("dt_start", dtStart);
        url.searchParams.set("dt_end", dtEnd);
        url.searchParams.set("format", format);

        form.setAttribute('action', url.href);
    });
</script>
