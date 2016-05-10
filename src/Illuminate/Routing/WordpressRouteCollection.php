<?php

namespace Larapress\Illuminate\Routing;

use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use Illuminate\Routing\RouteCollection as BaseRouteCollection;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class WordpressRouteCollection extends BaseRouteCollection
{
    public function match(Request $request)
    {
        preg_match('/(\/wp-admin\/)([^\?]*)\??/', $request->server->get('REQUEST_URI'), $matches);
        $uri = $matches[2];

        $routes = $this->get($request->getMethod());

        foreach ($routes as $route)
            if ($route->getUri() == $uri)
                return $route->bind($request);

        throw new NotFoundHttpException;
    }
}
