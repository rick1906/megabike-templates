<?php

namespace megabike\templates\nodes\operators;

use megabike\templates\Input;
use megabike\templates\nodes\Node;
use megabike\templates\nodes\AttrNode;
use megabike\templates\nodes\ElementNode;
use megabike\templates\nodes\modifiers\ModifierNode;
use megabike\templates\parser\Parser;
use megabike\templates\parser\tokens\OperatorCloseToken;
use megabike\templates\errors\NodeException;

abstract class OperatorNode extends ElementNode
{

    public function getValue(Input $input = null, $storeMode = 0)
    {
        if ($this->isDisabled()) {
            return '';
        } elseif ($input !== null) {
            $this->applyInput($input, $storeMode);
            return $this->computeOperatorValue($input, $storeMode);
        } else {
            return null;
        }
    }

    public function getOpenTagValue(Input $input = null, $storeMode = 0)
    {
        return '';
    }

    public function getCloseTagValue(Input $input = null, $storeMode = 0)
    {
        return '';
    }

    public function isOpenTagConstant()
    {
        return true;
    }

    public function isConstant()
    {
        return false;
    }

    public function isVirtual()
    {
        return true;
    }

    protected function computeIsConstant()
    {
        return false;
    }

    protected function computeIsOpenTagConstant()
    {
        return true;
    }

    protected function computeOpenTagValue($input = null)
    {
        return '';
    }

    protected function computeCloseTagValue($input = null)
    {
        return '';
    }

    protected function execute($input, $storeMode = 0)
    {
        $this->executeOpenTag($input);
        if (!$this->isDisabled()) {
            $this->executeOperator($input, $storeMode);
        }
        $this->executeCloseTag($input);
    }

    protected function executeOpenTag($input)
    {
        foreach ($this->attrNodes as $node) {
            $node->execute($input, 0);
        }
    }

    protected function executeCloseTag($input)
    {
        
    }

    protected function checkCollectedToken(Parser $parser, $token)
    {
        if ($this->tagName === null && $token instanceof OperatorCloseToken) {
            if ($token->getOperatorId() === $this->getOperatorId()) {
                $this->processCloseTag($parser, $token);
                return self::ACTION_EXIT;
            }
        }
        return parent::checkCollectedToken($parser, $token);
    }

    public function setOperatorCode($code, $tokens)
    {
        throw new NodeException("Operator '".$this->getOperatorId()."' does not support this syntax", $this);
    }

    public function addAttrNode($node)
    {
        if ($node instanceof ModifierNode) {
            $this->attrNodes[] = $node;
            $node->setParent($this);
        } elseif ($node instanceof AttrNode) {
            $param = $this->setParameter($node);
            if ($param !== false) {
                if ($param instanceof Node) {
                    $this->attrNodes[] = $node;
                    $node->setParent($this);
                }
            } else {
                throw new NodeException("Invalid attribute node '".$node->getAttrName()."' supplied to operator '".$this->getOperatorId()."'", $this);
            }
        } else {
            throw new NodeException("Invalid attribute node supplied to operator '".$this->getOperatorId()."'", $this);
        }
    }

    protected function getParameter($node, $input = null)
    {
        if ($node instanceof AttrNode) {
            if ($input === false) {
                if ($node->isConstant()) {
                    return $node->getAttrTextValue();
                } else {
                    throw new NodeException("Attribute '".$node->getAttrName()."' of operator '".$this->getOperatorId()."' must be constant", $this);
                }
            } elseif ($input === null) {
                return $node->isConstant() ? $node->getAttrTextValue() : $node;
            } else {
                return $node->getAttrTextValue($input);
            }
        } else {
            return $node;
        }
    }

    protected function getCodeParameter($node)
    {
        if ($node instanceof AttrNode) {
            return $node->transformToCode();
        } else {
            throw new NodeException("Invalid code attribute of class '".get_class($node)."' in operator '".$this->getOperatorId()."'", $this);
        }
    }

    protected function checkParameterIsAvailable(AttrNode $node)
    {
        $values = array_slice(func_get_args(), 1);
        foreach ($values as $value) {
            if ($value !== null) {
                throw new NodeException("Value for parameter '".$node->getAttrName()."' in operator '".$this->getOperatorId()."' is already set", $this);
            }
        }
    }

    public abstract function getOperatorId();

    protected abstract function setParameter(AttrNode $node);

    protected abstract function computeOperatorValue($input, $storeMode = 0);

    protected abstract function executeOperator($input, $storeMode = 0);
}
