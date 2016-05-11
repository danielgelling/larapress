<?php

namespace Larapress\Illuminate\Routing;

use App;
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
     * @return void
     */
    public function run(Request $request)
    {
        list($class, $method) = explode('@', $this->getAction()['uses']);
        $parameters = $this->signatureParameters();

        $controller = new $class;

        $args = [];

        foreach ($parameters as $parameter) {
            if( ! is_null($parameter->getClass())) {
                $args[] = App::make($parameter->getClass()->getName());
            } else {
                $args[] = App::make('request')->get($parameter->getName());
            }
        }

        call_user_func_array([$controller, $method], $args);
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

            $parameters = (new ReflectionMethod($class, $method))->getParameters();
        } else {
            $parameters = (new ReflectionFunction($action['uses']))->getParameters();
        }

        return is_null($subClass) ? $parameters : array_filter($parameters, function ($p) use ($subClass) {
            return $p->getClass() && $p->getClass()->isSubclassOf($subClass);
        });
    }
}
