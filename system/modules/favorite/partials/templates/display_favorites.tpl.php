<h2>Favorites</h2>
<?php
if (!empty($categorisedFavorites)) {
    foreach ($categorisedFavorites as $category => $favorites) {
        if (!empty($favorites)) :
            ?>
            <ul class="list-group shadow-none">
                <h4 class="fw-lighter p-2 pb-0"><?php echo $category; ?></h4>
                <?php foreach ($favorites as $object) : ?>
                    <li class="list-group-item">
                        <?php echo FavoriteService::getInstance($w)->getBootstrapButton($object); ?>
                        <span class="ms-2"><?php echo $object->toLink() ?></span>
                        
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif;
    }
}
