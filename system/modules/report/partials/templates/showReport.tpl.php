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

        action += "?dt_start=" + encodeURIComponent(dtStart) +
                  "&dt_end=" + encodeURIComponent(dtEnd) +
                  "&format=" + encodeURIComponent(format);

        form.setAttribute('action', action);
    });
</script>
