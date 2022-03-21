<?php

namespace megabike\templates\parser\tokens;

use megabike\templates\Content;
use megabike\templates\nodes\ElementNode;
use megabike\templates\nodes\HtmlElementNode;
use megabike\templates\parser\Parser;
use megabike\templates\parser\tokens\XmlAttrToken;
use megabike\templates\parser\tokens\XmlTagOpenToken;
use megabike\templates\parser\tokens\CodeSequence;

class HtmlTagOpenToken extends XmlTagOpenToken
{
    protected $tagId;

    public function __construct($index, $tagName)
    {
        parent::__construct($index, $tagName);
        $this->tagId = Content::toLower($tagName);
    }

    public function getTagId()
    {
        return $this->tagId;
    }

    protected function processAttributeTokens(ElementNode $node, Parser $parser)
    {
        foreach ($this->getTokens() as $token) {
            if (is_string($token)) {
                if (trim($token) === '') {
                    continue;
                } else {
                    $parser->addWarning($this->index, "Open-tag '{$this->tagName}' contains invalid content: '".trim($token)."'");
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
            $parser->addWarning($this->index, "Open-tag '{$this->tagName}' contains invalid content");
        }
        if ($this->isShortClosed) {
            $node->setClosed(HtmlElementNode::CLOSED_SHORT);
        }
        return $node;
    }

    public function createNode(Parser $parser)
    {
        $class = $parser->getElementNodeClass($this->getTagId());
        if ($class !== null) {
            $node = new $class($this->tagName, $this);
            if ($node instanceof HtmlElementNode) {
                return $this->processAttributeTokens($node, $parser);
            } else {
                return parent::processAttributeTokens($node, $parser);
            }
        }

        $node = new HtmlElementNode($this->tagName, $this);
        return $this->processAttributeTokens($node, $parser);
    }

}