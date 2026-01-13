<?php if (!empty($migration_filename) && !empty($migration_module) && !empty($migration_preText)) : ?>
    <h1><strong><?php echo $migration_class; ?> migration</strong></h1>
    <h3><strong>Important</strong> - please read before continuing:</h3>
    <?php echo HtmlBootstrap5::alertBox($migration_preText, 'alert-warning', false);
    echo HtmlBootstrap5::b(href: "/admin-migration#" . $prevpage, title: "Cancel Migration", confirm: "Are you sure you would like to cancel?", class: "btn btn-secondary");
    switch ($prevpage) {
        case "batch":
            echo HtmlBootstrap5::b(href: "/admin-migration/run/all?continuingrunall=true&prevpage=" . $prevpage, title: "Continue Running All Migrations", confirm: "Are you sure you would like to continue?", class: "btn btn-primary");
            break;
        case "individual":
            echo HtmlBootstrap5::b(
                href: "/admin-migration/run/" . $_migration_module . "/" . $_migration_filename . "?continuingrunall=true&prevpage=" . $prevpage,
                title: "Continue Migrations",
                confirm: "Are you sure you would like to continue?",
                class: "btn btn-primary"
            );
            break;
    }
else :
    $w->error("Not all migration fields specified", "/admin-migration#batch");
endif;
