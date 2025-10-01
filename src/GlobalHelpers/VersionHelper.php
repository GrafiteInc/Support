<?php

/**
 * Get the current application version.
 */
if (! function_exists('app_version')) {
    function app_version()
    {
        return \Grafite\Support\Helpers\Version::release();
    }
}
