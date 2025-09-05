<?php

/**
 * Partial action that lists favorite objects
 * @author Steve Ryan steve@2pisoftware.com 2015
 */
function listfavorite_ALL(Web $w, $params)
{
	$user = AuthService::getInstance($w)->user();

	if (empty($user)) {
		return;
	}

	$results = FavoriteService::getInstance($w)->getFavoritesForUser($user->id);
	$favoritesCategorised = [];
	$service = new DBService($w);

	if (empty($results)) {
		return;
	}

	foreach ($results as $k => $favorite) {
		if (!array_key_exists($favorite->object_class, $favoritesCategorised)) {
			$favoritesCategorised[$favorite->object_class] = [];
		}

		$realObject = $service->getObject($favorite->object_class, $favorite->object_id);
		if (!empty($realObject)) {
			$templateData = [];
			$templateData['title'] = $realObject->printSearchTitle();
			$templateData['url'] = $realObject->printSearchUrl();
			$templateData['listing'] = $realObject->printSearchListing();
			$templateData["ref"] = $realObject;
			if ($realObject->canList($user) && $realObject->canView($user)) {
				array_push($favoritesCategorised[$favorite->object_class], $templateData);
			}
		}
	}
	$w->ctx('categorisedFavorites', $favoritesCategorised);
}
