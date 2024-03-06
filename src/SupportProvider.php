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

        Blade::if('feature', function ($key) {
            return request()->user()->hasFeatureAccess($key);
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

                return $realUrl === route($route, $parameters);
            } catch (\Throwable $th) {
                return false;
            }
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
