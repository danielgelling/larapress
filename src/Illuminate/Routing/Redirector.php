<?php

namespace Larapress\Illuminate\Routing;

use Illuminate\Routing\Redirector as BaseRedirector;
use Illuminate\Http\RedirectResponse;
use Illuminate\Session\Store as SessionStore;

class Redirector extends BaseRedirector
{
    /**
     * Create a new Redirector instance.
     *
     * @param  \Illuminate\Routing\UrlGenerator  $generator
     * @return void
     */
    public function __construct(UrlGenerator $generator)
    {
        $this->generator = $generator;
    }
}
