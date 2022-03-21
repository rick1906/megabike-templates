<?php

namespace megabike\templates\parser;

use megabike\templates\parser\Parser;

class TokensCollection implements \ArrayAccess, \Countable, \IteratorAggregate
{
    protected $parser;
    protected $tokens;

    public function __construct(Parser $parser, $tokens)
    {
        $this->parser = $parser;
        $this->tokens = $tokens;
    }

    public function token($index)
    {
        return isset($this->tokens[$index]) ? $this->tokens[$index] : null;
    }

    public function insert($tokens, $index)
    {
        array_splice($this->tokens, $index, 0, $tokens);
        return count($tokens);
    }

    public function replace($tokens, $index, $amount)
    {
        array_splice($this->tokens, $index, $amount, $tokens);
        return count($tokens);
    }

    public function count()
    {
        return count($this->tokens);
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->tokens);
    }

    public function offsetExists($offset)
    {
        return isset($this->tokens[$offset]);
    }

    public function offsetGet($offset)
    {
        return isset($this->tokens[$offset]) ? $this->tokens[$offset] : null;
    }

    public function offsetSet($offset, $value)
    {
        if ($offset !== null) {
            $this->tokens[$offset] = $value;
        } else {
            $this->tokens[] = $value;
        }
    }

    public function offsetUnset($offset)
    {
        if (isset($this->tokens[$offset])) {
            unset($this->tokens[$offset]);
        }
    }

}
