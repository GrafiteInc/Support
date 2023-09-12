<?php

use Illuminate\Support\Arr;

if (!function_exists('route_link_class')) {
    function route_link_class($route, $active = 'active', $class = 'nav-link')
    {
        if (is_array($route) && Arr::isList($route) && request()->routeIs($route)) {
            return "{$class} {$active}";
        }

        if (request()->routeIsWith($route)) {
            return "{$class} {$active}";
        }

        return $class;
    }
}
