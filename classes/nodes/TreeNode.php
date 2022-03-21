<?php

namespace megabike\templates\nodes;

use megabike\templates\Input;
use megabike\templates\nodes\Node;
use megabike\templates\nodes\TextNode;
use megabike\templates\parser\Parser;
use megabike\templates\parser\TokensCollection;

abstract class TreeNode extends Node
{
    const ACTION_BREAK = 0;
    const ACTION_ACCEPT = 1;
    const ACTION_IGNORE = 2;
    const ACTION_EXIT = 3;

    protected $childNodes = array();
    protected $rootNode = null;

    protected function setParent(Node $parentNode)
    {
        $this->parentNode = $parentNode;
        $this->rootNode = $parentNode->getRoot();
        if ($this->childNodes && $this->rootNode) {
            foreach ($this->childNodes as $node) {
                $node->setParent($this);
            }
        }
    }

    public function getRoot()
    {
        return $this->rootNode;
    }

    public function buildStorage($storeMode = 1)
    {
        if (!$this->isContentConstant() && $storeMode > 0) {
            $nextMode = $storeMode;
        } else {
            $nextMode = 0;
        }
        foreach ($this->childNodes as $node) {
            $node->buildStorage($nextMode);
        }
        if ($nextMode > 0) {
            if (!isset($this->storage['contentArray'])) {
                $this->storage['contentArray'] = $this->buildContentArray($storeMode);
            }
        }
        if ($this->isConstant()) {
            if ($storeMode > 0) {
                $this->getValue(null, $storeMode);
            }
        } else {
            if ($this->isOpenTagConstant() && $storeMode > 0) {
                $this->getOpenTagValue(null, $storeMode);
            }
            if ($this->isCloseTagConstant() && $storeMode > 0) {
                $this->getCloseTagValue(null, $storeMode);
            }
            if ($this->isContentConstant() && $storeMode > 0) {
                $this->getContentValue(null, $storeMode);
            }
        }
    }

    public function resetStorage()
    {
        parent::resetStorage();
        foreach ($this->childNodes as $node) {
            $node->resetStorage();
        }
    }

    protected function computeIsConstant()
    {
        return $this->isOpenTagConstant() && $this->isContentConstant() && $this->isCloseTagConstant();
    }

    protected final function computeValue($input = null, $storeMode = 0)
    {
        return $this->getOpenTagValue($input, $storeMode).$this->getContentValue($input, $storeMode).$this->getCloseTagValue($input, $storeMode);
    }

    protected function execute($input, $storeMode = 0)
    {
        $this->executeOpenTag($input);
        if (!$this->isDisabled()) {
            $this->executeContent($input, $storeMode);
        }
        $this->executeCloseTag($input);
    }

    public function resetInput()
    {
        if (isset($this->storage[''])) {
            foreach ($this->childNodes as $node) {
                $node->resetInput();
            }
        }
        parent::resetInput();
    }

    public function isOpenTagConstant()
    {
        if (isset($this->storage['isOpenTagConstant'])) {
            return $this->storage['isOpenTagConstant'];
        } else {
            $value = $this->computeIsOpenTagConstant();
            $this->storage['isOpenTagConstant'] = $value;
            return $value;
        }
    }

    public function isContentConstant()
    {
        if (isset($this->storage['isContentConstant'])) {
            return $this->storage['isContentConstant'];
        } else {
            $value = $this->computeIsContentConstant();
            $this->storage['isContentConstant'] = $value;
            return $value;
        }
    }

    public function isCloseTagConstant()
    {
        return true;
    }

    public function getOpenTagValue(Input $input = null, $storeMode = 0)
    {
        if ($this->isDisabled()) {
            return '';
        } elseif ($this->isOpenTagConstant()) {
            if (isset($this->storage['openTagValue'])) {
                return $this->storage['openTagValue'];
            } else {
                $value = $this->computeOpenTagValue(null);
                if ($storeMode > 0) {
                    $this->storage['openTagValue'] = $value;
                }
                return $value;
            }
        } elseif ($input !== null) {
            $this->applyInput($input, $storeMode);
            return $this->computeOpenTagValue($input);
        } else {
            return null;
        }
    }

    public function getCloseTagValue(Input $input = null, $storeMode = 0)
    {
        if ($this->isDisabled()) {
            return '';
        } elseif ($this->isCloseTagConstant()) {
            if (isset($this->storage['closeTagValue'])) {
                return $this->storage['closeTagValue'];
            } else {
                $value = $this->computeCloseTagValue(null);
                if ($storeMode > 0) {
                    $this->storage['closeTagValue'] = $value;
                }
                return $value;
            }
        } elseif ($input !== null) {
            $this->applyInput($input, $storeMode);
            return $this->computeCloseTagValue($input);
        } else {
            return null;
        }
    }

    public function getContentValue(Input $input = null, $storeMode = 0)
    {
        if ($this->isDisabled()) {
            return '';
        } elseif ($this->isContentConstant()) {
            if (isset($this->storage['contentValue'])) {
                return $this->storage['contentValue'];
            } else {
                $value = $this->computeContentValue(null, $storeMode < 2 ? 0 : 2);
                if ($storeMode > 0) {
                    $this->storage['contentValue'] = $value;
                }
                return $value;
            }
        } elseif ($input !== null) {
            $this->applyInput($input, $storeMode);
            if (!empty($this->storage['contentArray'])) {
                return $this->computeContentArrayValue($this->storage['contentArray'], $input, $storeMode);
            } else {
                return $this->computeContentValue($input, $storeMode);
            }
        } else {
            return null;
        }
    }

    protected abstract function computeIsOpenTagConstant();

    protected abstract function computeOpenTagValue($input = null);

    protected abstract function computeCloseTagValue($input = null);

    protected abstract function executeOpenTag($input);

    protected abstract function executeCloseTag($input);

    protected function executeContent($input, $storeMode = 0)
    {
        foreach ($this->childNodes as $node) {
            if (!$node->isConstant()) {
                $node->applyInput($input, $storeMode);
            }
        }
    }

    protected function computeIsContentConstant()
    {
        foreach ($this->childNodes as $node) {
            if (!$node->isConstant() && !$node->isDetached()) { //TODO: think about detached
                return false;
            }
        }
        return true;
    }

    protected function buildContentArray($storeMode)
    {
        $nodes = array();
        $pos = -1;
        $count = 0;
        foreach ($this->childNodes as $node) {
            if ($node->isConstant()) {
                if ($pos < 0) {
                    $pos = count($nodes);
                    $nodes[] = (string)$node->getValue(null, $storeMode);
                } else {
                    $nodes[$pos] .= $node->getValue(null, $storeMode);
                }
                $count++;
            } else {
                $pos = -1;
                $nodes[] = $node;
            }
        }
        return $count > 0 ? $nodes : false;
    }

    protected function computeContentArrayValue($nodes, $input, $storeMode = 0)
    {
        //\Logger::line(__METHOD__.' '.$this->getOpenTagValue());
        $buffer = '';
        foreach ($nodes as $node) {
            if ($node instanceof TreeNode && !$node->isVirtual()) {
                $buffer .= $node->getOpenTagValue($input, $storeMode);
                $buffer .= $node->getContentValue($input, $storeMode);
                $buffer .= $node->getCloseTagValue($input, $storeMode);
            } elseif ($node instanceof Node) {
                $buffer .= $node->getValue($input, $storeMode);
            } else {
                $buffer .= $node;
            }
        }
        return $buffer;
    }

    protected function computeContentValue($input = null, $storeMode = 0)
    {
        $buffer = '';
        foreach ($this->childNodes as $i => $node) {
            if ($node instanceof TreeNode && !$node->isVirtual()) {
                $buffer .= $node->getOpenTagValue($input, $storeMode);
                $buffer .= $node->getContentValue($input, $storeMode);
                $buffer .= $node->getCloseTagValue($input, $storeMode);
                //TODO: text trim 1) при генерации шаблона, в спецтегах
                //TODO: text trim 2) после генерации шаблона, вообще по тексту (по превноде тримить текст любой ноды)
            } else {
                $buffer .= $node->getValue($input, $storeMode);
            }
        }
        return $buffer;
    }

    public function addChildNode($node)
    {
        $last = end($this->childNodes);
        if ($last && $last instanceof TextNode && $node instanceof TextNode) {
            if ($last->join($node)) {
                return true;
            }
        }

        $this->childNodes[] = $node;
        $node->setParent($this);
        return true;
    }

    public abstract function isClosed();

    protected function checkCollectedNode(Parser $parser, $node)
    {
        return self::ACTION_BREAK;
    }

    protected function checkCollectedToken(Parser $parser, $token)
    {
        return self::ACTION_BREAK;
    }

    public function collectChildNodes(Parser $parser, TokensCollection $tokens, $startIndex)
    {
        $index = $startIndex;

        while ($index < $tokens->count()) {
            if ($this->isClosed()) {
                return $index - $startIndex;
            }

            $token = $tokens[$index];
            $node = $parser->createNode($token);
            if ($node instanceof Node) {
                $action = $this->checkCollectedNode($parser, $node);
            } else {
                $action = $this->checkCollectedToken($parser, $token);
            }

            if ($action === self::ACTION_BREAK || $action === false) {
                break;
            } elseif ($action === self::ACTION_EXIT) {
                $index += 1;
                break;
            } elseif ($action === self::ACTION_IGNORE) {
                $index += 1;
                continue;
            }

            if ($node) {
                $this->addChildNode($node);
                $shift = $parser->processNode($node, $tokens, $index);
            } else {
                $shift = $parser->processNodeFallback($node, $tokens, $index);
            }

            $index += $shift;
        }

        return $index - $startIndex;
    }

}
