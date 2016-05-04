<?php

namespace Larapress\Illuminate\Routing;

use ReflectionMethod;
use ReflectionFunction;
use Illuminate\Http\Request as Request;
use Illuminate\Routing\Route as BaseRoute;

class Route extends BaseRoute
{
    /**
     * Don't run the route action cause Wordpress will.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    public function run(Request $request)
    {
    }
    public function match()
    {
    }
    public function getUri()
    {
        return $this->uri;
    }
    /**
     * Get the parameters that are listed in the route / controller signature.
     *
     * @param string|null  $subClass
     * @return array
     */
    public function signatureParameters($subClass = null)
    {
        $action = $this->getAction();

        if (is_string($action['uses'])) {
            list($class, $method) = explode('@', $action['uses']);

            $parameters = (new ReflectionMethod($class, $method))->getParameters();
        } else {
            $parameters = (new ReflectionFunction($action['uses']))->getParameters();
        }

        return is_null($subClass) ? $parameters : array_filter($parameters, function ($p) use ($subClass) {
            return $p->getClass() && $p->getClass()->isSubclassOf($subClass);
        });
    }
}
