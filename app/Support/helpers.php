<?php

if (! function_exists('site_name')) {
    function site_name(): string
    {
        return app(\App\Services\Settings\SettingsService::class)->site()['name'];
    }
}
