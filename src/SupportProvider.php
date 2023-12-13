<?php

namespace Grafite\Support;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\ServiceProvider;
use Grafite\Support\Commands\CloudflarePurge;

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

            return url()->full() === route($route, $parameters);
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
