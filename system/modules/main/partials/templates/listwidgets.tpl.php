<?php
echo HtmlBootstrap5::box(href: "/main/addwidget/{$module}", title: "Add Widget", button: true, class: "btn btn-sm btn-primary mb-3 ms-0");

if (!empty($widgets)) : ?>
    <div class="container widget_container">
        <div class="row">
            <?php for ($i = 0; $i < count($widgets); $i++) : ?>
                <div class="col-12 col-md-6">
                    <div class="widget list-style-none">
                        <div class="widget_buttons">
                            <?php echo HtmlBootstrap5::box(href: "/main/configwidget/{$module}/{$widgets[$i]->id}", title: __("Config"), class: "btn btn-sm btn-secondary"); ?>
                            <?php echo HtmlBootstrap5::a(href: "/main/removewidget/{$module}/{$widgets[$i]->id}", title: __("Remove"), alt: __("Remove Widget"), class: "btn btn-sm btn-danger"); ?>
                        </div>
                        <?php // echo $w->partial($widgets[$i]->widget_name, null, $widgets[$i]->source_module); ?>
                        <?php if (!empty($widgets[$i]->widget_class)) {
                            $widgets[$i]->widget_class->display();
                        } ?>
                    </div>
                </div>
            <?php endfor; ?>
        </div>
    </div>
    <style>
        .widget .widget_buttons {
            opacity: 0;
            transition: opacity 250ms;
        }

        .widget:hover .widget_buttons {
            opacity: 1;
        }
    </style>
<?php endif;
