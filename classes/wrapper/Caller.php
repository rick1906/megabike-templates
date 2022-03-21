<?php

namespace megabike\templates\wrapper;

use megabike\templates\TemplatesConfig;
use megabike\templates\errors\DataException;

abstract class Caller implements \ArrayAccess
{
    /**
     * @var array
     */
    protected $functions = null;

    /**
     * @var array
     */
    protected $classes = null;

    /**
     * @var boolean
     */
    protected $functionsAllow = false;

    /**
     * @var boolean
     */
    protected $classesAllow = false;

    /**
     * @var TemplatesConfig
     */
    protected $config = null;

    /**
     * @var string
     */
    protected $class = null;

    /**
     * @var array
     */
    protected $_reflections = null;

    /**
     * @var array
     */
    protected $_callers = null;

    public final function __construct($parent, $class = null)
    {
        if ($parent instanceof Caller) {
            $this->config = $parent->config;
            $this->functionsAllow = $parent->functionsAllow;
            $this->classesAllow = $parent->classesAllow;
            $this->functions = $parent->functions;
            $this->classes = $parent->classes;
            $this->initChild($parent);
        } else {
            $this->config = $parent;
            $this->initNew();
        }
        $this->class = $class;
    }

    protected function initNew()
    {
        $this->functionsAllow = $this->config['callerAllowFunctionsByDefault'];
        $this->classesAllow = $this->config['callerAllowClassesByDefault'];
    }

    protected function initChild($parent)
    {
        
    }

    public abstract function addUse($use, $as = null);

    public abstract function addUseFunction($use, $as = null);

    protected function buildFunctionsMap()
    {
        $map = array();
        foreach ($this->config['callerAllowedFunctions'] as $val) {
            $name = ltrim($val, '\\');
            $map[$name] = true;
        }
        foreach ($this->config['callerDisallowedFunctions'] as $val) {
            $name = ltrim($val, '\\');
            $map[$name] = false;
        }
        return $map;
    }

    protected function buildClassesMap()
    {
        $map = array();
        if ($this->classesAllow) {
            foreach ($this->config['callerAllowedClasses'] as $key => $val) {
                if (is_int($key)) {
                    $name = ltrim($val, '\\');
                    $value = true;
                } else {
                    $name = ltrim($key, '\\');
                    $value = $val;
                }
                if (is_array($value)) {
                    foreach ($value as $fval) {
                        $fname = ltrim($fval);
                        $map[$name][$fname] = true;
                    }
                } else {
                    $map[$name] = array('' => (bool)$value);
                }
            }
        }
        foreach ($this->config['callerDisallowedClasses'] as $key => $val) {
            if (is_int($key)) {
                $name = ltrim($val, '\\');
                $value = true;
            } else {
                $name = ltrim($key, '\\');
                $value = $val;
            }
            if (is_array($value)) {
                foreach ($value as $fval) {
                    $fname = ltrim($fval);
                    $map[$name][$fname] = false;
                }
            } else {
                $map[$name] = false;
            }
        }
    }

    public function getAllowedFunction($function, $rootNs = true)
    {
        if ($this->functions === null) {
            $this->functions = $this->buildFunctionsMap();
        }
        if (isset($this->functions[$function])) {
            return $this->functions[$function] ? $function : null;
        }
        return $this->functionsAllow ? $function : null;
    }

    public function getAllowedClass($class, $rootNs = true)
    {
        if ($this->classes === null) {
            $this->classes = $this->buildClassesMap();
        }
        if (isset($this->classes[$class])) {
            return $this->classes[$class] ? $class : null;
        }
        return $this->classesAllow ? $class : null;
    }

    public function getAllowedClassMethod($class, $method)
    {
        if ($this->classes === null) {
            $this->classes = $this->buildClassesMap();
        }
        if (isset($this->classes[$class])) {
            if (isset($this->classes[$class][$method])) {
                return $this->classes[$class][$method] ? array($class, $method) : null;
            }
            return !empty($this->classes[$class]['']) ? array($class, $method) : null;
        }
        return $this->classesAllow ? array($class, $method) : null;
    }

    protected function getClassConstant($name)
    {
        $refl = $this->getClassReflection();
        return $refl->getConstant($name);
    }

    protected function getClassStaticVariable($name)
    {
        $refl = $this->getClassReflection();
        return $refl->getStaticPropertyValue($name);
    }

    protected function getClassCaller($name)
    {
        $class = ltrim($name, '\\');
        if (isset($this->_callers[$class])) {
            return $this->_callers[$class];
        }
        $target = $this->getAllowedClass($class, isset($name[0]) && $name[0] === '\\');
        if ($target === null) {
            throw new DataException("Accessing class '{$class}' is not allowed");
        } else {
            $callerClass = get_class($this);
            $caller = new $callerClass($this, $target);
            $this->_callers[$class] = $caller;
            return $caller;
        }
    }

    protected function getFunctionReflection($name)
    {
        $function = ltrim($name, '\\');
        if ($this->class !== null) {
            throw new DataException("Accessing non-class function in class-related caller");
        } elseif (isset($this->_reflections[$function])) {
            return $this->_reflections[$function];
        } else {
            $target = $this->getAllowedFunction($function, isset($name[0]) && $name[0] === '\\');
            if ($target === null) {
                throw new DataException("Calling function '{$function}' is not allowed");
            } elseif (is_array($target)) {
                $refl = new StaticMethodWrapper($target[0], $target[1]);
                $this->_reflections[$function] = $refl;
                return $refl;
            } else {
                $refl = new \ReflectionFunction($target);
                $this->_reflections[$function] = $refl;
                return $refl;
            }
        }
    }

    protected function getClassReflection()
    {
        if ($this->class === null) {
            throw new DataException("Class is not specified in this caller");
        } elseif (isset($this->_reflections[''])) {
            return $this->_reflections[''];
        } else {
            $refl = new \ReflectionClass($this->class);
            $this->_reflections[''] = $refl;
            return $refl;
        }
    }

    protected function getClassMethodReflection($name)
    {
        if ($this->class === null) {
            throw new DataException("Class is not specified in this caller");
        } elseif (isset($this->_reflections[$name])) {
            return $this->_reflections[$name];
        } else {
            $target = $this->getAllowedClassMethod($this->class, $name);
            if ($target === null) {
                throw new DataException("Calling method '{$this->class}::{$name}' is not allowed");
            } else {
                $refl = new \ReflectionMethod($target[0], $target[1]);
                $this->_reflections[$name] = $refl;
                return $refl;
            }
        }
    }

    protected function callFunction($name, $arguments)
    {
        $function = ltrim($name, '\\');
        $target = $this->getAllowedFunction($function, isset($name[0]) && $name[0] === '\\');
        if ($target === null) {
            throw new DataException("Calling function '{$function}' is not allowed");
        } else {
            return call_user_func_array($target, $arguments);
        }
    }

    protected function callClassMethod($name, $arguments)
    {
        if ($this->class === null) {
            throw new DataException("Class is not specified in this caller");
        } else {
            $target = $this->getAllowedClassMethod($this->class, $name);
            if ($target === null) {
                throw new DataException("Calling method '{$this->class}::{$name}' is not allowed");
            } else {
                return call_user_func_array($target, $arguments);
            }
        }
    }

    public function __call($name, $arguments)
    {
        if ($this->class === null) {
            return $this->callFunction($name, $arguments);
        } else {
            return $this->callClassMethod($name, $arguments);
        }
    }

    public function __get($name)
    {
        return $this->getClassConstant($name);
    }

    public function offsetGet($offset)
    {
        if ($this->class === null) {
            if (isset($offset[0]) && $offset[0] === ':') {
                return $this->getClassCaller(substr($offset, 1));
            } else {
                return $this->getFunctionReflection($offset);
            }
        } elseif (isset($offset[0])) {
            if ($offset[0] === ':' && !isset($offset[1])) {
                return $this->class;
            } elseif ($offset[0] === '$') {
                return $this->getClassStaticVariable(substr($offset, 1));
            } else {
                return $this->getClassMethodReflection($offset);
            }
        } else {
            throw new DataException("Invalid function name");
        }
    }

    public function offsetExists($offset)
    {
        return true;
    }

    public function offsetSet($offset, $value)
    {
        return null;
    }

    public function offsetUnset($offset)
    {
        return null;
    }

}
