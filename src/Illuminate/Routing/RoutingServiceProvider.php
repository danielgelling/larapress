<?php

namespace Larapress\Illuminate\Routing;

use Illuminate\Routing\RoutingServiceProvider as BaseProvider;

class RoutingServiceProvider extends BaseProvider
{
    /**
     * Register the router instance.
     *
     * @return void
     */
    protected function registerRouter()
    {
        $this->app['router'] = $this->app->share(function ($app) {
            return new Router($app['events'], $app);
        });
    }
}
