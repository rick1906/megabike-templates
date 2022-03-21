<?php

namespace megabike\templates\nodes;

use megabike\templates\nodes\TreeNode;
use megabike\templates\nodes\AttrNode;
use megabike\templates\errors\ParseException;
use megabike\templates\parser\Parser;
use megabike\templates\parser\tokens\XmlTagCloseToken;
use megabike\templates\errors\NodeException;

//TODO: hide-if attrs, etc
abstract class ElementNode extends TreeNode
{
    const CLOSED_NONE = 0;
    const CLOSED_NORMAL = 1;

    protected $tagName;
    protected $closedState = 0;
    protected $attrNodes = array();

    public function __construct($tagName, $token = null)
    {
        parent::__construct($token);
        $this->tagName = $tagName;
    }

    public function getTagName()
    {
        return $this->tagName;
    }

    public function getTagId()
    {
        return $this->tagName;
    }

    public function buildStorage($storeMode = 1)
    {
        parent::buildStorage($storeMode);
        if (!$this->isOpenTagConstant() && $storeMode > 0) {
            $nextMode = $storeMode;
        } else {
            $nextMode = 0;
        }
        foreach ($this->attrNodes as $node) {
            $node->buildStorage($nextMode);
        }
    }

    public function resetStorage()
    {
        parent::resetStorage();
        foreach ($this->attrNodes as $node) {
            $node->resetStorage();
        }
    }

    public function resetInput()
    {
        foreach ($this->attrNodes as $node) {
            $node->resetInput();
        }
        parent::resetInput();
    }

    public function addAttrNode($node)
    {
        if ($node instanceof AttrNode) {
            $this->attrNodes[] = $node;
            $node->setParent($this);
        } else {
            throw new NodeException("Invalid attribute node supplied to ".get_class($this), $this);
        }
    }

    public function isClosed()
    {
        return $this->closedState !== self::CLOSED_NONE;
    }

    public function setClosed($state, $closeChildren = 0)
    {
        $this->closedState = (int)$state;
        if ($closeChildren) {
            foreach ($this->childNodes as $child) {
                if (($child instanceof ElementNode) && !$child->isClosed()) {
                    $child->setClosed($closeChildren, $closeChildren);
                }
            }
        }
    }

    protected function processCloseTag(Parser $parser, $token, $closeChildren = 0)
    {
        $this->setClosed(self::CLOSED_NORMAL, $closeChildren);
        if ($token instanceof XmlTagCloseToken) {
            $tokens = $token->getTokens();
            if (!empty($tokens)) {
                $parser->addWarning($token->getIndex(), "Content is not allowed in close-tag '{$token->getTagName()}'");
            }
        }
    }

    public function initializeNode(Parser $parser)
    {
        if (!$this->isClosed() && $this->tagName !== null) {
            throw new ParseException($parser->getSource(), $this->getIndex(), "Element '{$this->tagName}' is not closed properly");
        }
        foreach ($this->attrNodes as $node) {
            $node->initializeNode($parser);
        }
    }

    protected function checkCollectedNode(Parser $parser, $node)
    {
        return self::ACTION_ACCEPT;
    }

    protected function checkCollectedToken(Parser $parser, $token)
    {
        if ($this->tagName !== null && $token instanceof XmlTagCloseToken) {
            if ($this->getTagId() === $token->getTagId()) {
                $this->processCloseTag($parser, $token);
                return self::ACTION_EXIT;
            }
        }

        return self::ACTION_ACCEPT;
    }

}
