<?php

namespace Larapress\Illuminate\Foundation;

use Illuminate\Events\EventServiceProvider;
use Larapress\Illuminate\Routing\RoutingServiceProvider;
use Illuminate\Foundation\Application as BaseApplication;

class Application extends BaseApplication
{
    protected function registerBaseServiceProviders()
    {
        $this->register(new EventServiceProvider($this));

        $this->register(new RoutingServiceProvider($this));
    }
}
