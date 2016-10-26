<?php

namespace Larapress\Illuminate\Routing;

use ReflectionMethod;
use ReflectionFunction;
use Illuminate\Http\Request as Request;
use Illuminate\Routing\Route as BaseRoute;

class WordpressRoute extends BaseRoute
{
    /**
     * Don't run the route action cause Wordpress will.
     *
     * @param  array|string  $methods
     * @param  string  $uri
     * @param  \Closure|array  $action
     * @param  mixed  $params
     * @return void
     */
    public function __construct($methods, $uri, $action, $params)
    {
        $this->uri = $uri;
        $this->params = $params;
        $this->methods = (array) $methods;
        $this->action = $this->parseAction($action);

        if (in_array('GET', $this->methods) && ! in_array('HEAD', $this->methods)) {
            $this->methods[] = 'HEAD';
        }

        if (isset($this->action['prefix'])) {
            $this->prefix($this->action['prefix']);
        }
    }

    /**
     * Don't run the route action cause Wordpress will.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    public function run(Request $request)
    {
        // if($this->action['uses'] instanceof \Closure) {
        //     $this->router->callClosure($this->action);
        // } else {
        //     $this->router->callControllerMethod($this->action);
        // }
    }

    /**
     * Get the route's URI.
     *
     * @return string
     */
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

            // If method doesn't exist, return empty array. It might be a
            // magic method (__call() might be used).
            if (! method_exists($class, $method)) return [];

            $parameters = (new ReflectionMethod($class, $method))->getParameters();
        } else {
            $parameters = (new ReflectionFunction($action['uses']))->getParameters();
        }

        return is_null($subClass) ? $parameters : array_filter($parameters, function ($p) use ($subClass) {
            return $p->getClass() && $p->getClass()->isSubclassOf($subClass);
        });
    }
}
