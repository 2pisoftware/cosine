<h4 class="text-center">Favourites</h4><hr/>
<div class="tabs mt-4">
    <div class="tab-head">
        <?php if (!empty($categorisedFavorites)) {
            foreach ($categorisedFavorites as $className => $objects) {
                // Transform class into readable text
                // $t_class = preg_replace('/(?<=\\w)(?=[A-Z])/', " $1", $className);
                echo '<a href="#' . $className . '">' . str_replace(' ', '&nbsp;', $className) . '</a>';
            }
        } ?>
    </div>
    <div class="tab-body">
        <?php if (!empty($categorisedFavorites)) :
            foreach (array_filter($categorisedFavorites) as $className => $objects) : ?>
                <div id="<?php echo $className; ?>" class="pt-2">
                    <?php foreach ($objects as $templateData) : ?>
                        <div class="panel search-result">
                            <a class="d-block search-title" href="<?php echo $w->localUrl($templateData['url']); ?>"><?php echo $templateData['title']; ?></a>
                            <div class="d-block search-listing"><?php echo $templateData['listing']; ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach;
        endif; ?>
    </div>
</div>