<?php


namespace ENovo\Container;

use Closure;
use ReflectionClass;
use ReflectionException;
use InvalidArgumentException;

class Container
{
    protected $shared = [];
    protected $bindings  = [];

    protected static $instance = null;

    public static function getInstance()
    {
        if(static::$instance == null){
            static::$instance = new  Container();
        }
        return static::$instance;
    }

    public static function setInstance(Container $container)
    {
        static::$instance = $container;
    }

    public function bind($name, $resolver, $shared = false)
    {
        $this->bindings[$name] = [
            'resolver' => $resolver,
            'shared' => $shared
        ];
    }

    public function make($name, array $arguments = array())
    {
        if (isset($this->shared[$name])){
            return $this->shared[$name];
        }

        if (isset($this->bindings[$name])){
            $resolver = $this->bindings[$name]['resolver'];
            $shared = $this->bindings[$name]['shared'];
        }else{
            $resolver = $name;
            $shared = false;
        }

        if($resolver instanceof Closure){
            $object = $resolver($this);
        }else {
            $object = $this->build($resolver, $arguments);
        }

        if($shared){
            $this->shared[$name] = $object;
        }
        return $object;
    }

    public function instance($name, $object)
    {
        $this->shared[$name] = $object;
    }

    public function singleton($name, $resolver)
    {
        $this->bind($name, $resolver, true);
    }

    public function build($name, array $arguments = array())
    {
        try{
            $reflection = new ReflectionClass($name);
        }catch (ReflectionException $e){
            throw new ContainerException("Unable to build [$name]: ".$e->getMessage(), null, $e);
        }

        if(!$reflection->isInstantiable()) {
            throw new InvalidArgumentException("$name is not instantiable");
        }

        $constructor = $reflection->getConstructor();

        if(is_null($constructor)){
            return new $name;
        }

        $constructorParameters = $constructor->getParameters(); //[ReflectionParameter]

        $dependencies = array();

        foreach ($constructorParameters as $constructorParameter){

            $parameterName = $constructorParameter->getName();

            if(isset($arguments[$parameterName])){
                $dependencies[] = $arguments[$parameterName];
                continue;
            }

            try{
                $parameterClass = $constructorParameter->getClass();
            }catch (ReflectionException $e){
                throw new ContainerException("Unable to build [$name]: ". $e->getMessage(), null, $e);
            }

            if ( $parameterClass != null){
                $parameterClassName = $parameterClass->getName();
                $dependencies[] = $this->build($parameterClassName, $arguments);
            }else{

                throw new ContainerException("Please provide the value of the parameter [$parameterName]");
            }
        }
        return $reflection->newInstanceArgs($dependencies);
    }

}