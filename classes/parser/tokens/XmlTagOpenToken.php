<?php

namespace megabike\templates\parser\tokens;

use megabike\templates\parser\Parser;
use megabike\templates\parser\tokens\MasterToken;
use megabike\templates\parser\tokens\XmlAttrToken;
use megabike\templates\nodes\ElementNode;
use megabike\templates\nodes\XmlElementNode;
use megabike\templates\errors\ParseException;

class XmlTagOpenToken extends MasterToken
{
    protected $tagName;
    protected $isShortClosed = false;

    public function __construct($index, $tagName)
    {
        parent::__construct($index);
        $this->tagName = $tagName;
    }

    public function toString()
    {
        return '<'.$this->tagName.$this->getTokensString().($this->isShortClosed ? '/>' : '>');
    }

    public function setShortClosed($shortClosed)
    {
        $this->isShortClosed = (bool)$shortClosed;
    }

    public function getTagId()
    {
        return $this->tagName;
    }

    public function getTagName()
    {
        return $this->tagName;
    }

    protected function processAttributeTokens(ElementNode $node, Parser $parser)
    {
        foreach ($this->getTokens() as $token) {
            if (is_string($token)) {
                if (trim($token) === '') {
                    continue;
                } else {
                    throw new ParseException($parser->getSource(), $this->index, "Open-tag '{$this->tagName}' contains invalid content: '".trim($token)."'");
                    continue;
                }
            }
            if ($token instanceof XmlAttrToken) {
                $attrNode = $token->createNode($parser);
                $node->addAttrNode($attrNode);
                continue;
            }
            if ($token instanceof CodeSequence) {
                $attrNode = $token->createNode($parser);
                $node->addAttrNode($attrNode);
                continue;
            }
            throw new ParseException($parser->getSource(), $this->index, "Open-tag '{$this->tagName}' contains invalid content");
        }
        if ($this->isShortClosed) {
            $node->setClosed(XmlElementNode::CLOSED_SHORT);
        }
        return $node;
    }

    public function createNode(Parser $parser)
    {
        $class = $parser->getElementNodeClass($this->getTagId());
        if ($class !== null) {
            $node = new $class($this->tagName, $this);
            return $this->processAttributeTokens($node, $parser);
        }

        $node = new XmlElementNode($this->tagName, $this);
        return $this->processAttributeTokens($node, $parser);
    }

}