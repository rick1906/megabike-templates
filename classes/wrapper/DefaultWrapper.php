<?php

namespace megabike\templates\wrapper;

use megabike\templates\wrapper\Wrapper;
use megabike\templates\errors\DataException;

class DefaultWrapper extends Wrapper implements \IteratorAggregate, \Countable
{
    const TYPE_SCALAR = 0;
    const TYPE_ARRAY = 1;
    const TYPE_ASSOC = 2;

    protected $type = 0;

    protected function init()
    {
        if (is_array($this->data)) {
            $this->type = $this->initArray($this->data);
        } else {
            $this->type = self::TYPE_SCALAR;
        }
    }

    protected function initArray($data)
    {
        $keys = array_keys($data);
        if ($keys === array_keys($keys)) {
            return self::TYPE_ARRAY;
        } else {
            return self::TYPE_ASSOC;
        }
    }

    protected function call($name, $arguments)
    {
        throw new DataException($this->getMessageMethodNotExists($name));
    }

    protected function nodeIsset($name)
    {
        switch ($this->type) {
            case self::TYPE_ASSOC:
                return isset($this->data[$name]);
            case self::TYPE_ARRAY:
                if (isset($this->wrappers[0])) {
                    return $this->wrappers[0]->nodeIsset($name);
                } elseif (isset($this->data[0]) && !is_scalar($this->data[0])) {
                    $wrapper = $this->getWrapper(0, $this->data[0], $this->name);
                    return $wrapper->nodeIsset($name);
                } else {
                    return false;
                }
        }
        return false;
    }

    protected function nodeGet($name)
    {
        switch ($this->type) {
            case self::TYPE_ASSOC:
                if (array_key_exists($name, $this->data)) {
                    return $this->getWrapper($name, $this->data[$name]);
                } else {
                    throw new DataException($this->getMessageChildNotExists($name));
                }
            case self::TYPE_ARRAY:
                if (isset($this->wrappers[0])) {
                    return $this->wrappers[0]->nodeGet($name);
                } elseif (isset($this->data[0]) && !is_scalar($this->data[0])) {
                    $wrapper = $this->getWrapper(0, $this->data[0], $this->name);
                    return $wrapper->nodeGet($name);
                } else {
                    throw new DataException($this->getMessageChildNotExists($name));
                }
        }
        throw new DataException($this->getMessageChildNotExists($name));
    }

    public function offsetExists($name)
    {
        switch ($this->type) {
            case self::TYPE_ASSOC:
                return array_key_exists($name, $this->data) && is_scalar($this->data[$name]);
            case self::TYPE_ARRAY:
                return isset($this->wrappers[$name]) || array_key_exists($name, $this->data);
        }
        return false;
    }

    public function offsetGet($name)
    {
        switch ($this->type) {
            case self::TYPE_ASSOC:
                if (array_key_exists($name, $this->data) && is_scalar($this->data[$name])) {
                    return $this->data[$name];
                } else {
                    return null;
                }
            case self::TYPE_ARRAY:
                if (is_numeric($name)) {
                    if (isset($this->wrappers[$name])) {
                        return $this->wrappers[$name];
                    } elseif (array_key_exists($name, $this->data)) {
                        return $this->getWrapper($name, $this->data[$name], $this->name);
                    } else {
                        throw new DataException($this->getMessageIndexNotExists($name));
                    }
                } elseif (isset($this->wrappers[0])) {
                    return $this->wrappers[0][$name];
                } elseif (isset($this->data[0]) && !is_scalar($this->data[0])) {
                    $wrapper = $this->getWrapper(0, $this->data[0], $this->name);
                    return $wrapper[$name];
                } else {
                    return null;
                }
        }
        return null;
    }

    public function getIterator()
    {
        if ($this->type === self::TYPE_ARRAY) {
            return new DefaultWrapperIteraror($this);
        } else {
            throw new DataException("Cannot iterate node '{$this->name}'");
        }
    }

    public function count()
    {
        if ($this->type === self::TYPE_ARRAY) {
            return count($this->data);
        } else {
            return 1;
        }
    }

}

class DefaultWrapperIteraror implements \Iterator
{
    protected $wrapper;
    protected $index;

    public function __construct($wrapper)
    {
        $this->wrapper = $wrapper;
        $this->index = 0;
    }

    public function current()
    {
        return $this->wrapper[$this->index];
    }

    public function key()
    {
        return $this->index;
    }

    public function next()
    {
        $this->index++;
    }

    public function rewind()
    {
        $this->index = 0;
    }

    public function valid()
    {
        return isset($this->wrapper[$this->index]);
    }

}