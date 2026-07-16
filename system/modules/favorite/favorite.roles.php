<?php

/**
 * favorites user roles
 *
 * @author Steve Ryan, steve@2pisoftware.com, 2015
 **/

function role_favorites_user_allowed($w, $path)
{
    return $w->checkUrl($path, "favorite", "*", "*");
}
