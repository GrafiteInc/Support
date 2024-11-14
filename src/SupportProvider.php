<?php

namespace Grafite\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\ServiceProvider;
use Grafite\Support\Commands\CloudflarePurge;
use Illuminate\Pagination\LengthAwarePaginator;

class SupportProvider extends ServiceProvider
{
    /**
     * Boot method.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/features.php' => base_path('config/features.php'),
        ]);

        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../views', 'support');

        Blade::if('feature', function ($key) {
            return request()->user()->hasFeatureAccess($key);
        });

        Blade::directive('version', function () {
            if (base_path('changelog.md')) {
                return '<?php echo preg_match("/## (.*?) -/", file_get_contents(base_path(\'changelog.md\')), $matches) ? str_replace(\'[\', \'\', str_replace(\']\', \'\', $matches[1])) : ""; ?>';
            }
            if (base_path('CHANGELOG.md')) {
                return '<?php echo preg_match("/## (.*?) -/", file_get_contents(base_path(\'CHANGELOG.md\')), $matches) ? str_replace(\'[\', \'\', str_replace(\']\', \'\', $matches[1])) : ""; ?>';
            }
        });

        Blade::directive('docs', function () {
            if (! app()->environment('production')) {
                return '<?php echo \Grafite\Support\Docs\Generate::handle() ?>';
            }
        });

        Blade::directive('session', function ($nonce) {
            return '<?php echo javascript_session_data('.$nonce.'); ?>';
        });

        Request::macro('routeIsWith', function ($route, $parameters = []) {
            if (is_array($route)) {
                $name = $route[0];
                unset($route[0]);
                $parameters = $route;
                $route = $name;
            }

            if (str_contains($route, '.*')) {
                return request()->routeIs($route);
            }

            try {
                $parsed = parse_url(url()->full());
                $realUrl= $parsed['scheme']. '://'. $parsed['host']. $parsed['path'];

                if ($realUrl === route($route, $parameters)) {
                    return true;
                }

                if (url()->full() === route($route, $parameters)) {
                    return true;
                }

                return false;
            } catch (\Throwable $th) {
                return false;
            }
        });

        Collection::macro('toObject', function () {
            return json_decode((json_encode($this)));
        });

        Collection::macro('paginate', function($perPage, $page = null, $pageName = 'page') {
            $page = $page ?: LengthAwarePaginator::resolveCurrentPage($pageName);
            return new LengthAwarePaginator(
                $this->forPage($page, $perPage), // $items
                $this->count(),                  // $total
                $perPage,
                $page,
                [                                // $options
                    'path' => LengthAwarePaginator::resolveCurrentPath(),
                    'pageName' => $pageName,
                ]
            );
        });

        Collection::macro('sortByDate', function ($column = 'created_at') {
            return $this->sortBy(function ($item) use ($column) {
                return strtotime($item->$column);
            }, SORT_REGULAR);
        });

        Collection::macro('sortByDateDesc', function ($column = 'created_at') {
            return $this->sortByDesc(function ($item) use ($column) {
                return strtotime($item->$column);
            }, SORT_REGULAR);
        });

        Collection::macro('chunkBy', function ($column = 'created_at') {
            $keys = array_values($this->pluck($column)->unique()->toArray());
            $result = [];

            foreach ($keys as $key) {
                $result[$key] = $this->filter(function ($item) use ($column, $key) {
                    return $key === $item->$column;
                });
            }

            return collect($result);
        });
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->commands([
            CloudflarePurge::class,
        ]);
    }
}
