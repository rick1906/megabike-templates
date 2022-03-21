<?php

namespace megabike\templates\parser\tokens;

use megabike\templates\parser\Parser;
use megabike\templates\parser\tokens\Token;
use megabike\templates\parser\ParserStatus;

class EscapeToken extends Token
{

    public static function escape(ParserStatus $status, $index)
    {
        $last = $status->getLastToken();
        if ($last instanceof EscapeToken) {
            if ($last->getIndex() + 1 == $index) {
                $last->isEscape = true;
                return true;
            }
        }
        return false;
    }

    protected $isEscape = false;

    public function toString()
    {
        return '\\';
    }

    public function createNode(Parser $parser)
    {
        if ($this->isEscape) {
            return false;
        } else {
            return $parser->createDefaultNode('\\');
        }
    }

}