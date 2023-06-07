<?php

namespace Grafite\Support\Models\Concerns;

trait Observable
{
    public static function bootObservable()
    {
        $observer = str_replace('App\\', 'App\Observers\\', self:: class) . 'Observer';

        self::observe(app ($observer));
    }
}
