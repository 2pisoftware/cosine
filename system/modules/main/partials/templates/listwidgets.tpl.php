<div class="row">
    <div class="col">
        <?php echo HtmlBootstrap5::box(href: "/main/addwidget/{$module}", title: "Add Widget", button: true, class: 'btn btn-primary'); ?>
    </div>
</div>
<?php if (!empty($widgets)) : ?>
    <div class="widget_container">
        <ul class="small-block-grid-1 medium-block-grid-3">
            <?php for ($i = 0; $i < count($widgets); $i++) : ?>
                <li class="widget">
                    <div class="widget_buttons">
                        <?php echo HtmlBootstrap5::box(href: "/main/configwidget/{$module}/{$widgets[$i]->id}", title: __("Config"), class: "btn btn-sm btn-secondary widget_config"); ?>
                        <?php echo HtmlBootstrap5::a(href: "/main/removewidget/{$module}/{$widgets[$i]->id}", title: __("Remove"), alt: __("Remove Widget"), class: "btn btn-sm btn-warning widget_remove"); ?>
                    </div>
                    <?php // echo $w->partial($widgets[$i]->widget_name, null, $widgets[$i]->source_module); ?>
                    <?php if (!empty($widgets[$i]->widget_class)) {
                        $widgets[$i]->widget_class->display();
                    } ?>
                </li>
            <?php endfor;?>
        </ul>
    </div>
    
<?php endif; ?>
<script type="text/javascript">
    (() => {
        document.querySelectorAll('.widget').forEach(function(widget) {
            widget.addEventListener('mouseenter', function() {
                var btn = widget.querySelector('.widget_buttons');
                if (btn) {
                    btn.style.transition = 'opacity 250ms';
                    btn.style.opacity = '1';
                }
            });
            widget.addEventListener('mouseleave', function() {
                var btn = widget.querySelector('.widget_buttons');
                if (btn) {
                    btn.style.transition = 'opacity 250ms';
                    btn.style.opacity = '0';
                }
            });
        });
    })();
</script>