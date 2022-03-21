<?php

namespace megabike\templates\parser\tokens;

use megabike\templates\parser\Parser;

abstract class Token
{

    public static function transformTokens($tokens)
    {
        $newTokens = array();
        $last = null;
        $k = 0;
        foreach ($tokens as $i => $token) {
            if ($token instanceof Token) {
                $token = $token->transform();
                if ($token === null) {
                    continue;
                }
                if (is_array($token)) {
                    $transformed = static::transformTokens($token);
                    foreach ($transformed as $ttoken) {
                        $newTokens[$k++] = $ttoken;
                        $last = $ttoken;
                    }
                    continue;
                }
            } elseif ($i > 0) {
                if (is_string($last) && is_string($token)) {
                    $newTokens[$k - 1] .= $token;
                    continue;
                } elseif (is_array($last) && is_array($token) && $last[0] === $token[0]) {
                    $newTokens[$k - 1][1] .= $token[1];
                    continue;
                }
            }
            $newTokens[$k++] = $token;
            $last = $token;
        }
        return $newTokens;
    }

    public static function tokensToString($tokens)
    {
        $string = '';
        foreach ($tokens as $token) {
            if ($token instanceof Token) {
                $string .= $token->toString();
            } elseif (is_array($token)) {
                $string .= (string)$token[1];
            } else {
                $string .= (string)$token;
            }
        }
        return $string;
    }

    protected $index;

    public function __construct($index)
    {
        $this->index = $index;
    }

    public abstract function toString();

    public function getIndex()
    {
        return $this->index;
    }

    public function transform()
    {
        return $this;
    }

    public function createNode(Parser $parser)
    {
        return null;
    }

    public function fallback(Parser $parser)
    {
        return null;
    }

}
