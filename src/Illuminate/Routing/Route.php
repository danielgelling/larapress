<?php

namespace Larapress\Illuminate\Routing;

use ReflectionMethod;
use ReflectionFunction;
use Illuminate\Http\Request as Request;
use Illuminate\Routing\Route as BaseRoute;

class Route extends BaseRoute
{
    /**
     * Don't run the route action cause Wordpress will do that for us.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    public function run(Request $request) { }

    /**
     * Get the route's URI.
     *
     * @return string
     */
    public function getUri()
    {
        return $this->uri;
    }
}
