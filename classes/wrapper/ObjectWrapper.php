<?php

namespace megabike\templates\wrapper;

use \ReflectionObject;
use megabike\templates\wrapper\Wrapper;
use megabike\templates\errors\DataException;

class ObjectWrapper extends Wrapper
{
    protected $reflection = null;
    protected $getters = null;

    protected function init()
    {
        
    }

    /**
     * @return ReflectionObject
     */
    protected function getReflection()
    {
        if ($this->reflection === null) {
            $this->reflection = new ReflectionObject($this->data);
        }
        return $this->reflection;
    }

    protected function getGetterInfo($name)
    {
        if (isset($this->getters[$name])) {
            return $this->getters[$name];
        }

        $getter = 'get'.ucfirst($name);
        if (is_callable(array($this->data, $getter))) {
            $ref = $this->getReflection();
            $method = $ref->getMethod($getter);
            if ($method && $method->getName() === $getter && !$method->isStatic()) {
                if ($method->getNumberOfRequiredParameters() === 0) {
                    $this->getters[$name] = $getter;
                    return $getter;
                }
            }
        }

        if (property_exists($this->data, $name)) {
            $this->getters[$name] = 1;
            return 1;
        }

        if (is_callable(array($this->data, '__get'))) {
            $this->getters[$name] = 2;
            return 2;
        }

        if ($this->data instanceof \ArrayAccess) {
            $this->getters[$name] = 3;
            return 3;
        }

        return 0;
    }

    protected function nodeIsset($name)
    {
        $getter = $this->getGetterInfo($name);
        if (is_string($getter)) {
            $value = call_user_func(array($this->data, $getter));
            return $value !== null;
        } elseif ($getter === 1 || $getter === 2) {
            return isset($this->data->$name);
        } elseif ($getter === 3) {
            return isset($this->data[$name]);
        } else {
            return false;
        }
    }

    protected function nodeGet($name)
    {
        $getter = $this->getGetterInfo($name);
        if (is_string($getter)) {
            $value = call_user_func(array($this->data, $getter));
        } elseif ($getter === 1 || $getter === 2) {
            $value = $this->data->$name;
        } elseif ($getter === 3) {
            $value = $this->data[$name];
        } else {
            throw new DataException($this->getMessageChildNotExists($name));
        }
        return $this->getWrapper($name, $value);
    }

    public function offsetExists($name)
    {
        $getter = $this->getGetterInfo($name);
        if (is_string($getter)) {
            $value = call_user_func(array($this->data, $getter));
            return !is_array($value);
        } elseif ($getter === 1) {
            return !is_array($this->data->$name);
        } elseif ($getter === 2) {
            return isset($this->data->$name) && !is_array($this->data->$name);
        } elseif ($getter === 3 && $this->data->offsetExists($name)) {
            return !is_array($this->data[$name]);
        } else {
            return false;
        }
    }

    public function offsetGet($name)
    {
        $getter = $this->getGetterInfo($name);
        if (is_string($getter)) {
            $value = call_user_func(array($this->data, $getter));
        } elseif ($getter === 1 || $getter === 2) {
            $value = $this->data->$name;
        } elseif ($getter === 3) {
            $value = $this->data[$name];
        } else {
            return null;
        }
        if (!is_array($value)) {
            return $value;
        } else {
            return null;
        }
    }

    protected function call($name, $arguments)
    {
        if (is_callable(array($this->data, $name))) {
            return call_user_func_array(array($this->data, $name), $arguments);
        } else {
            throw new DataException($this->getMessageMethodNotExists($name));
        }
    }

    protected function objectFree($name)
    {
        unset($this->wrappers[$name]);
        unset($this->getters[$name]);
    }

    public function free($name = null)
    {
        parent::free($name);
        unset($this->getters[$name]);
    }

}