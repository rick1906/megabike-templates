<?php

namespace megabike\templates\parser\tokens;

use megabike\templates\parser\Parser;
use megabike\templates\parser\tokens\XmlTagOpenToken;
use megabike\templates\nodes\DoctypeNode;

class DoctypeToken extends XmlTagOpenToken
{

    public function toString()
    {
        return '<'.$this->tagName.$this->getTokensString().'>';
    }

    protected function getTokensString()
    {
        $string = '';
        if ($this->tokens) {
            foreach ($this->tokens as $token) {
                if ($token instanceof Token) {
                    $string .= ' '.$token->toString();
                } else {
                    $string .= ' '.(string)$token;
                }
            }
        }
        return $string;
    }

    public function createNode(Parser $parser)
    {
        $node = new DoctypeNode($this->tagName, $this);
        return $this->processAttributeTokens($node, $parser);
    }

}