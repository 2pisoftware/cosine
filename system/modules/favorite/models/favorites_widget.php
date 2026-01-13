<?php

/**
 * A widget for displaying favorites
 */
// phpcs:ignore
class favorites_widget extends ModuleWidget
{
    public static $widget_count = 0;

    public function getSettingsForm($current_settings = null)
    {
        return [];
    }

    public function display()
    {
        echo $this->w->partial("listfavorite", ['classname' => 'Favorite'], "favorite");
    }

    public function canView(User $user)
    {
        if (empty($user)) {
            $user = AuthService::getInstance($this->w)->user();
        }

        return $user->hasRole("favorites_user");
    }
}
