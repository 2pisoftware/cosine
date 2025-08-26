<?php echo $widgetform; ?>
<script>
    (() => {
        var widgetName = document.getElementById("widget_name");
        if (widgetName && widgetName.parentElement.tagName.toLowerCase() === "tr") {
            widgetName.parentElement.style.display = "none";
        }

        console.log(widgetName);
        var sourceModule = document.getElementById("source_module");
        console.log(sourceModule);
        if (sourceModule) {
            sourceModule.addEventListener("change", function(e) {
                console.log(e);
                if (e.target.selectedIndex > 0) {
                    fetch("/main/ajax_getwidgetnames?source=" + encodeURIComponent(sourceModule.value))
                        .then(function(response) {
                            if (!response.ok) throw new Error("Network response was not ok");
                            return response.json();
                        })
                        .then(function(parsed) {
                            widgetName.innerHTML = "";
                            for (var i in parsed) {
                                var option = document.createElement("option");
                                option.value = parsed[i];
                                option.textContent = parsed[i];
                                widgetName.appendChild(option);
                            }
                            if (widgetName.parentElement.tagName.toLowerCase() === "tr") {
                                widgetName.parentElement.style.display = "";
                            }
                        })
                        .catch(function(error) {
                            console.error("Fetch error:", error);
                        });
                } else {
                    widgetName.innerHTML = "";
                    var option = document.createElement("option");
                    option.textContent = "-- Select --";
                    widgetName.appendChild(option);
                }
            });
        }
    })();
</script>