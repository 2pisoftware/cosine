<?php
/**
 * Partial action that lists favorite objects
 * @author Steve Ryan steve@2pisoftware.com 2015
 */
function listfavorite_ALL(Web $w, $params)
{
    $user = AuthService::getInstance($w)->user();
    if (!empty($user)) {
        $results = FavoriteService::getInstance($w)->getFavoritesForUser($user->id);
        $favoritesCategorised = [];
        if (!empty($results)) {
            foreach ($results as $k => $favorite) {
                if (!array_key_exists($favorite->object_class, $favoritesCategorised)) {
                    $favoritesCategorised[$favorite->object_class] = [];
                }
                $favourite_object = FavoriteService::getInstance($w)->getObject($favorite->object_class, $favorite->object_id);
                if (!empty($favourite_object) && $favourite_object->canList($user) && $favourite_object->canView($user)) {
                    array_push($favoritesCategorised[$favorite->object_class], [
                        'title' => $favourite_object->printSearchTitle(),
                        'url' => $favourite_object->printSearchUrl(),
                        'listing' => $favourite_object->printSearchListing()
                    ]);
                }
            }
        }
        $w->ctx('categorisedFavorites', $favoritesCategorised);
    }
}
