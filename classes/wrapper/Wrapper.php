<?php

namespace megabike\templates\wrapper;

use megabike\templates\Input;
use megabike\templates\errors\DataException;

abstract class Wrapper implements \ArrayAccess
{
    protected $name;
    protected $data;
    protected $input;
    protected $parent;
    protected $wrappers = null;

    public final function __construct($name, $data, Input $input, Wrapper $parent = null)
    {
        $this->name = $name;
        $this->data = $data;
        $this->input = $input;
        $this->parent = $parent;
        $this->init();
    }

    protected abstract function init();

    protected function getWrapper($id, $data, $name = null)
    {
        if (isset($this->wrappers[$id])) {
            return $this->wrappers[$id];
        }
        if ($name === null) {
            $name = $id;
        }

        $wrapper = $this->input->getChildWrapper($this, $name, $data);
        $this->wrappers[$id] = $wrapper;
        return $wrapper;
    }

    protected function getSpecialNode($name)
    {
        if ($name === 'node()') {
            return $this;
        }
        if ($name === 'parent()') {
            return $this->parent;
        }
        return null;
    }

    protected function getMessageChildNotExists($name)
    {
        return "Node '{$this->name}' does not have a child '{$name}'";
    }

    protected function getMessageAttrNotExists($name)
    {
        return "Node '{$this->name}' does not have an attribute '{$name}'";
    }

    protected function getMessageMethodNotExists($name)
    {
        return "Node '{$this->name}' does not have a method '{$name}'";
    }

    protected function getMessageIndexNotExists($name)
    {
        return "Node group '{$this->name}' does not have a node with index '{$index}'";
    }

    protected function getMessageReadOnly()
    {
        return "Node '{$this->name}' is read-only";
    }

    protected abstract function nodeIsset($name);

    protected abstract function nodeGet($name);

    protected abstract function call($name, $arguments);

    public function __isset($name)
    {
        if (isset($this->wrappers[$name])) {
            return true;
        }
        if (strpos($name, '(') !== false) {
            return false;
        }
        return $this->nodeIsset($name);
    }

    public function __get($name)
    {
        if (isset($this->wrappers[$name])) {
            return $this->wrappers[$name];
        }
        if (strpos($name, '(') !== false) {
            return $this->getSpecialNode($name);
        }
        return $this->nodeGet($name);
    }

    public function __call($name, $arguments)
    {
        if ($name === 'parent') {
            return $this->parent;
        }
        return $this->call($name, $arguments);
    }

    public function __set($name, $value)
    {
        throw new DataException($this->getMessageReadOnly());
    }

    public function __unset($name)
    {
        throw new DataException($this->getMessageReadOnly());
    }

    public abstract function offsetExists($name);

    public abstract function offsetGet($name);

    public function offsetSet($name, $value)
    {
        throw new DataException($this->getMessageReadOnly());
    }

    public function offsetUnset($name)
    {
        throw new DataException($this->getMessageReadOnly());
    }

    public function free($name = null)
    {
        if ($name === null) {
            $this->wrappers = null;
        } else {
            unset($this->wrappers[$name]);
        }
    }

    public function node($name = null)
    {
        if ($name === null) {
            return $this;
        } elseif (is_int($name)) {
            return $this[$name];
        } else {
            return $this->nodeGet($name);
        }
    }

    public function attr($name)
    {
        return $this->offsetGet($name);
    }

    public function value()
    {
        return $this->data;
    }

    public function __toString()
    {
        if (!is_array($this->data)) {
            return (string)$this->data;
        } else {
            return '';
        }
    }

}