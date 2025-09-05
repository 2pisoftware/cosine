<?php if (!empty($categorisedFavorites)) : ?>
    <?php foreach ($categorisedFavorites as $category => $favourites) : ?>
        <h2><?php echo $category ?></h2>
        <?php foreach ($favourites as $favourite) : ?>
            <div class="card">
                <div class="card-body">
                    <div class="card-title">
                        <?php
                        echo FavoriteService::getInstance($w)->getBootstrapButton($favourite["ref"]);
                        ?>
                        <a href="<?php echo $favourite["url"] ?>">
                            <?php echo $favourite["title"]; ?>
                        </a>
                    </div>

                    <div>
                        <?php echo $favourite["listing"]; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endforeach; ?>
<?php endif;
