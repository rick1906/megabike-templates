<?php

namespace megabike\templates\parser\tokens;

use megabike\templates\nodes\AttrNode;
use megabike\templates\nodes\XmlAttrNode;
use megabike\templates\parser\tokens\Token;
use megabike\templates\parser\tokens\MasterToken;
use megabike\templates\parser\Parser;
use megabike\templates\Content;

class XmlAttrToken extends MasterToken
{
    protected $attrName;
    protected $quoteSymbol = '"';

    public function __construct($index, $attrName)
    {
        parent::__construct($index);
        $this->attrName = $attrName;
    }

    public function toString()
    {
        $value = $this->getTokensString();
        $nameString = $this->attrName !== '' ? ($this->attrName.'=') : '';
        if ($this->quoteSymbol === '') {
            return $nameString.'"'.$value.'"';
        } else {
            return $nameString.$this->quoteSymbol.$value.$this->quoteSymbol;
        }
    }

    public function setQuoteSymbol($symbol)
    {
        $this->quoteSymbol = $symbol;
    }

    public function setHasValue()
    {
        if ($this->tokens === null) {
            $this->tokens = array();
        }
    }

    protected function getTokensString()
    {
        if ($this->tokens) {
            $string = '';
            $tokens = $this->tokens;
            foreach ($tokens as $token) {
                if ($token instanceof Token) {
                    $string .= $token->toString();
                } elseif (is_array($token)) {
                    $string .= (string)$token[1];
                } else {
                    $string .= $this->transformAttrValue($token);
                }
            }
            return $string;
        }
        return '';
    }

    public function getAttrId()
    {
        return $this->attrName;
    }

    public function getAttrName()
    {
        return $this->attrName;
    }

    protected function transformAttrValue($string, $isXml = true)
    {
        if (!$isXml) {
            return Content::htmlDecode($string);
        } elseif ($this->quoteSymbol === '') {
            return str_replace('"', '&quot;', $string);
        } else {
            return $string;
        }
    }

    protected function processAttrValueTokens(AttrNode $node, Parser $parser)
    {
        if ($this->tokens !== null) {
            $isXml = ($node instanceof XmlAttrNode);
            $count = count($this->tokens);
            if ($count === 0) {
                $node->setValueNode('');
            } elseif ($count == 1) {
                $token = $this->tokens[0];
                if (is_string($token)) {
                    $node->setValueNode($this->transformAttrValue($token, $isXml));
                } elseif ($token instanceof CodeSequence) {
                    $node->setValueNode($token->createNode($parser), $node);
                } else {
                    $parser->addWarning($this->index, "Attribute '{$this->attrName}' contains invalid content");
                }
            } else {
                foreach ($this->getTokens() as $token) {
                    if ($token instanceof EscapeToken) {
                        continue;
                    } elseif (is_string($token)) {
                        $node->addValueNode($this->transformAttrValue($token, $isXml));
                    } elseif ($token instanceof CodeSequence) {
                        $node->addValueNode($token->createNode($parser), $node);
                    } else {
                        $parser->addWarning($this->index, "Attribute '{$this->attrName}' contains invalid content");
                    }
                }
            }
        }
        return $node;
    }

    public function createNode(Parser $parser)//TODO: create attributes (ONLY) in parent node method
    {
        $class = $parser->getAttrNodeClass($this->getAttrId());
        if ($class !== null) {
            $node = new $class($this->tagName, $this);
            return $this->processAttrValueTokens($node, $parser);
        }

        $node = new XmlAttrNode($this->attrName, $this);
        return $this->processAttrValueTokens($node, $parser);
    }

}
