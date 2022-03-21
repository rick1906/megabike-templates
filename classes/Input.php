<?php

namespace megabike\templates;

use megabike\templates\Template;
use megabike\templates\wrapper\Wrapper;
use megabike\templates\errors\DataException;

class Input
{
    private static $_id = 0;

    public static function create($template, $data)
    {
        if ($data instanceof Input && $data->template === $template) {
            return $data;
        } else {
            return new Input($template, null, $data);
        }
    }

    /**
     * @var Input
     */
    protected $parent = null;

    /**
     * @var Template
     */
    protected $template;

    /**
     * @var int
     */
    protected $id;

    /**
     * @var mixed
     */
    protected $data;

    /**
     * @var int
     */
    protected $mode = 0;

    /**
     * @var mixed
     */
    protected $vars = array();

    /**
     * @var Wrapper
     */
    protected $_wrapper = null;

    public final function __construct(Template $template, Input $parent = null, $data = null, $root = null)
    {
        $this->template = $template;
        $this->id = self::$_id++;
        $this->parent = $parent;
        if ($data instanceof Input) {
            $this->data = $data->data;
            $this->vars = $data->vars;
            $this->_wrapper = $data->_wrapper;
        } elseif ($data === null) {
            $this->data = $parent !== null ? $parent->data : array();
            $this->mode = 1;
        } elseif (is_array($data)) {
            $this->data = $data;
        } else {
            $this->data = array('' => $data);
        }
        if ($root !== null) {
            if ($root instanceof Wrapper) {
                $this->_wrapper = $root;
            } else {
                $this->data[''] = $root;
            }
        }
        $this->init();
    }

    protected function init()
    {
        
    }

    public function getId()
    {
        return $this->id;
    }

    public function getTemplate()
    {
        return $this->template;
    }

    public function getCaller()
    {
        return $this->template->getCaller();
    }

    public function getWrapper()
    {
        if ($this->_wrapper === null) {
            if (isset($this->data[''])) {
                $root = $this->data[''];
            } else {
                $root = $this->data;
            }
            $this->_wrapper = $this->createWrapper(null, '{root}', $root);
        }
        return $this->_wrapper;
    }

    public function getChildInput($template, $data = null, $root = null)
    {
        if ($template !== null && $template instanceof Template) {
            return new Input($template, $this, $data, $root);
        } else {
            return new Input($this->template, $this, $data, $root);
        }
    }

    public function getChildWrapper($parent, $name, $data)
    {
        return $this->createWrapper($parent, $name, $data);
    }

    protected function createWrapper($parent, $name, $data)
    {
        if (is_object($data)) {
            $class = $this->template->getWrapperClass(get_class($data));
        } else {
            $class = $this->template->getWrapperClass();
        }
        return new $class($name, $data, $this, $parent);
    }

    public function __isset($name)
    {
        if (isset($this->data[$name])) {
            return true;
        }
        if (isset($this->vars[$name])) {
            return true;
        }

        $parent = $this->parent;
        while ($parent !== null) {
            if (isset($parent->data[$name])) {
                return true;
            }
            if (isset($parent->vars[$name])) {
                return true;
            }
            $parent = $parent->parent;
        }
        return false;
    }

    public function &__get($name)
    {
        if ($this->data !== null && array_key_exists($name, $this->data)) {
            $value = $this->data[$name];
            return $value;
        }
        if ($this->vars !== null && array_key_exists($name, $this->vars)) {
            return $this->vars[$name];
        }

        $mode = $this->mode;
        $parent = $this->parent;
        while ($parent !== null) {
            if ($mode === 0 && array_key_exists($name, $parent->data)) {
                $value = $parent->data[$name];
                return $value;
            }
            if (array_key_exists($name, $parent->vars)) {
                $value = $parent->vars[$name];
                return $value;
            }
            $mode = $parent->mode;
            $parent = $parent->parent;
        }

        $null = null;
        return $null;
    }

    public function __set($name, $value)
    {
        if ($this->data !== null && array_key_exists($name, $this->data)) {
            throw new DataException("Cannot change input variable '{$name}'");
        }

        $this->vars[$name] = $value;
    }

    public function __unset($name)
    {
        if ($this->data !== null && array_key_exists($name, $this->data)) {
            throw new DataException("Cannot change input variable '{$name}'");
        }

        if ($this->parent !== null && $this->parent->__isset($name)) {
            $this->vars[$name] = null;
        } else {
            unset($this->vars[$name]);
        }
    }

    public function __call($name, $arguments)
    {
        $value = $this->__get($name);
        if ($value instanceof \Closure) {
            return call_user_func_array($value, $arguments);
        } else {
            throw new DataException("Calling variable '{$name}' that is not a closure");
        }
    }

}
