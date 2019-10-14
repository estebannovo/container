<?php

namespace ENovo\Container;

class Application
{
    /**
     * @var Container
     */
    protected $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function registerProviders(array  $providers)
    {
        foreach ($providers as $provider){
            $p = new $provider($this->container);
            $p->register();
        }
    }
}
