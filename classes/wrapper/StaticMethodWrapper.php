<?php

namespace megabike\templates\wrapper;

class StaticMethodWrapper
{
    /**
     *
     * @var \ReflectionMethod
     */
    private $reflection;

    public function __construct($class, $method)
    {
        $this->reflection = new \ReflectionMethod($class, $method);
    }

    public function invoke()
    {
        return $this->reflection->invokeArgs(null, func_get_args());
    }

    public function getReflectionMethod()
    {
        return $this->reflection;
    }

}
