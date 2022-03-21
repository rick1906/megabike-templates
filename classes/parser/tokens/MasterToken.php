<?php

namespace megabike\templates\parser\tokens;

use megabike\templates\parser\tokens\Token;

abstract class MasterToken extends Token
{
    protected $tokens;

    public function __construct($index)
    {
        parent::__construct($index);
        $this->tokens = null;
    }

    protected function getTokensString()
    {
        return $this->tokens ? static::tokensToString($this->tokens) : '';
    }

    public function addToken($token)
    {
        if ($this->tokens !== null) {
            $this->tokens[] = $token;
        } else {
            $this->tokens = array($token);
        }
    }

    public function getTokens()
    {
        return $this->tokens !== null ? $this->tokens : array();
    }

    public function getLastToken()
    {
        if ($this->tokens) {
            return end($this->tokens);
        } else {
            return null;
        }
    }

    public function transform()
    {
        if ($this->tokens) {
            $this->tokens = static::transformTokens($this->tokens);
        }
        return $this;
    }

}
