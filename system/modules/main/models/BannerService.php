<?php

class BannerService extends DbService
{
    public function getAuthBanner()
    {
        $banner = LookupService::getInstance($this->w)->getLookupByTypeAndCodeV2("notification", "auth");

        if (empty($banner)) {
            return null;
        }

        return $banner->title;
    }

    public function getLoginBanner()
    {
        $banner = LookupService::getInstance($this->w)->getLookupByTypeAndCodeV2("notification", "login");

        if (empty($banner)) {
            return null;
        }

        return $banner->title;
    }
}
